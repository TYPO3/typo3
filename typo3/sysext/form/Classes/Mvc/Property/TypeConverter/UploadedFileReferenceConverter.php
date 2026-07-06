<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Form\Mvc\Property\TypeConverter;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Exception\Crypto\InvalidHashStringException;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference as CoreFileReference;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceInstructionTrait;
use TYPO3\CMS\Core\Resource\Security\FileNameValidator;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter;
use TYPO3\CMS\Form\Mvc\Property\Exception\TypeConverterException;
use TYPO3\CMS\Form\Security\HashScope;
use TYPO3\CMS\Form\Service\TranslationService;
use TYPO3\CMS\Form\Slot\ResourcePublicationSlot;

/**
 * Scope: frontend
 * @internal
 */
class UploadedFileReferenceConverter extends AbstractTypeConverter implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    use ResourceInstructionTrait;

    /**
     * Folder where the file upload should go to (including storage).
     */
    public const CONFIGURATION_UPLOAD_FOLDER = 1;

    /**
     * How to handle a upload when the name of the uploaded file conflicts.
     */
    public const CONFIGURATION_UPLOAD_CONFLICT_MODE = 2;

    /**
     * Random seed to be used for deriving storage sub-folders.
     */
    public const CONFIGURATION_UPLOAD_SEED = 3;

    /**
     * Whether the user is allowed to remove previously uploaded files.
     */
    public const CONFIGURATION_ALLOW_REMOVAL = 5;

    /**
     * Validators implementing ObjectStorageElementValidatorInterface to run on
     * PseudoFile before the file is written to FAL storage.
     */
    public const CONFIGURATION_PRE_STORAGE_VALIDATORS = 6;

    protected string $defaultUploadFolder = '1:/user_upload/';

    /**
     * One of 'cancel', 'replace', 'rename'
     */
    protected DuplicationBehavior $defaultConflictMode = DuplicationBehavior::RENAME;

    /**
     * @var PseudoFileReference[]
     */
    protected array $convertedResources = [];
    protected ResourceFactory $resourceFactory;
    protected HashService $hashService;
    protected PersistenceManagerInterface $persistenceManager;
    protected StorageRepository $storageRepository;

    /**
     * @internal
     */
    public function injectResourceFactory(ResourceFactory $resourceFactory): void
    {
        $this->resourceFactory = $resourceFactory;
    }

    /**
     * @internal
     */
    public function injectHashService(HashService $hashService): void
    {
        $this->hashService = $hashService;
    }

    /**
     * @internal
     */
    public function injectPersistenceManager(PersistenceManagerInterface $persistenceManager): void
    {
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * @internal
     */
    public function injectStorageRepository(StorageRepository $storageRepository): void
    {
        $this->storageRepository = $storageRepository;
    }

    /**
     * Actually convert from $source to $targetType, taking into account the fully
     * built $convertedChildProperties and $configuration.
     *
     * @param array|UploadedFile|string $source
     * @param string $targetType
     * @return FileReference|ObjectStorage|Error|null
     * @internal
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], ?PropertyMappingConfigurationInterface $configuration = null)
    {
        if ($source === '' || $source === []) {
            return null;
        }

        if ($source instanceof UploadedFile) {
            return $this->convertSingleUpload(
                $this->convertUploadedFileToUploadInfoArray($source),
                $convertedChildProperties,
                $configuration,
            );
        }

        $allowRemoval = $configuration?->getConfigurationValue(self::class, self::CONFIGURATION_ALLOW_REMOVAL) ?? false;

        $deleteFileIndices = [];
        $filesToDelete = [];
        if ($allowRemoval) {
            [$deleteFileIndices, $filesToDelete] = $this->extractFileDeletionData($source);
            $this->deleteUploadedFiles($filesToDelete);
        }
        unset($source['__deleteFile']);

        if ($this->isMultiUploadTarget($source, $targetType)) {
            return $this->handleMultiUploadSource($source, $deleteFileIndices, $convertedChildProperties, $configuration);
        }

        return $this->handleSingleUploadSource($source, $filesToDelete, $allowRemoval, $convertedChildProperties, $configuration);
    }

    /**
     * Determines whether the source should be treated as a multi-file upload.
     *
     * Multi-upload is detected when:
     *  - the target type is ObjectStorage, or
     *  - the source contains '__submittedFiles' with more than one entry, or
     *  - the source contains numeric keys holding sub-arrays / UploadedFile objects
     *    (i.e. no flat 'error' key at the top level).
     */
    private function isMultiUploadTarget(array $source, string $targetType): bool
    {
        if (is_a($targetType, ObjectStorage::class, true)) {
            return true;
        }
        // Fallback heuristic for edge cases where targetType is not ObjectStorage
        // but the source structure clearly indicates multi-upload.
        if (isset($source['__submittedFiles']) && count($source['__submittedFiles']) > 1) {
            return true;
        }
        return !array_key_exists('error', $source) && !isset($source['submittedFile']) && !isset($source['__submittedFiles']);
    }

    /**
     * Handles single file upload: standard upload, existing resource pointer only,
     * or new upload replacing a previous file.
     *
     * '__submittedFiles' contains existing resource pointers from hidden inputs.
     * 'error' is PHP's native UPLOAD_ERR_* constant from $_FILES.
     *
     * @param list<int> $filesToDelete
     */
    private function handleSingleUploadSource(
        array $source,
        array $filesToDelete,
        bool $allowRemoval,
        array $convertedChildProperties,
        ?PropertyMappingConfigurationInterface $configuration,
    ): FileReference|Error|null {
        // Extract the submitted file resource pointer from __submittedFiles
        $submittedResourcePointer = null;
        if (is_array($source['__submittedFiles'] ?? null)) {
            $firstSubmitted = reset($source['__submittedFiles']);
            $submittedResourcePointer = $firstSubmitted['submittedFile']['resourcePointer'] ?? null;
        }
        unset($source['__submittedFiles']);

        // Build a flat source array for convertSingleUpload compatibility
        if ($submittedResourcePointer !== null) {
            $source['submittedFile']['resourcePointer'] = $submittedResourcePointer;
        }

        if ($allowRemoval && $filesToDelete !== []) {
            unset($source['submittedFile']['resourcePointer']);
        }

        // New upload replaces existing file – clean up the old one
        if (isset($source['submittedFile']['resourcePointer'], $source['error']) && $source['error'] === \UPLOAD_ERR_OK
        ) {
            $this->deletePreviousUpload($source);
            unset($source['submittedFile']);
        }

        return $this->convertSingleUpload($source, $convertedChildProperties, $configuration);
    }

    /**
     * @param list<int> $deleteFileIndices
     */
    private function handleMultiUploadSource(
        array $source,
        array $deleteFileIndices,
        array $convertedChildProperties,
        ?PropertyMappingConfigurationInterface $configuration,
    ): ObjectStorage|Error {
        $files = new ObjectStorage();

        // Extract existing file resource pointers from the dedicated sub-key.
        // These are stored separately to avoid index collision with new UploadedFile
        // objects during array_replace_recursive() in RequestBuilder.
        $submittedFiles = [];
        if (is_array($source['__submittedFiles'] ?? null)) {
            $submittedFiles = $source['__submittedFiles'];
        }
        unset($source['__submittedFiles']);

        // Process existing files (resource pointers from previous uploads)
        $existingFileIndex = 0;
        foreach ($submittedFiles as $file) {
            if (in_array($existingFileIndex, $deleteFileIndices, true)) {
                $existingFileIndex++;
                continue;
            }
            if (is_array($file)) {
                $convertedFile = $this->convertSingleUpload(
                    $file,
                    $convertedChildProperties,
                    $configuration,
                );
                if ($convertedFile instanceof Error) {
                    return $convertedFile;
                }
                if ($convertedFile !== null) {
                    $files->attach($convertedFile);
                }
            }
            $existingFileIndex++;
        }

        // Process new file uploads
        foreach ($source as $file) {
            if ($file instanceof UploadedFile || is_array($file)) {
                $convertedFile = $this->convertSingleUpload(
                    $file instanceof UploadedFile ? $this->convertUploadedFileToUploadInfoArray($file) : $file,
                    $convertedChildProperties,
                    $configuration,
                );
                if ($convertedFile instanceof Error) {
                    return $convertedFile;
                }
                if ($convertedFile !== null) {
                    $files->attach($convertedFile);
                }
            }
        }

        return $files;
    }

    /**
     * Core conversion: upload info array → FileReference, Error, or null.
     */
    private function convertSingleUpload(
        array $source,
        array $convertedChildProperties,
        ?PropertyMappingConfigurationInterface $configuration,
    ): FileReference|Error|null {
        $resourcePublicationSlot = GeneralUtility::makeInstance(ResourcePublicationSlot::class);

        if (!isset($source['error']) || $source['error'] === \UPLOAD_ERR_NO_FILE) {
            if (isset($source['submittedFile']['resourcePointer'])) {
                try {
                    $resourcePointer = $this->hashService->validateAndStripHmac(
                        $source['submittedFile']['resourcePointer'],
                        HashScope::ResourcePointer->prefix(),
                    );
                    if (str_starts_with($resourcePointer, 'file:')) {
                        $fileUid = (int)substr($resourcePointer, 5);
                        $resource = $this->createFileReferenceFromFalFileObject(
                            $this->resourceFactory->getFileObject($fileUid),
                        );
                    } else {
                        $resource = $this->createFileReferenceFromFalFileReferenceObject(
                            $this->resourceFactory->getFileReferenceObject((int)$resourcePointer),
                            (int)$resourcePointer,
                        );
                    }
                    $resourcePublicationSlot->add($resource->getOriginalResource()->getOriginalFile());
                    return $resource;
                } catch (\InvalidArgumentException) {
                    // No file uploaded and resource pointer is invalid – discard.
                }
            }
            return null;
        }

        if ($source['error'] !== \UPLOAD_ERR_OK) {
            return GeneralUtility::makeInstance(Error::class, $this->getUploadErrorMessage($source['error']), 1471715915);
        }

        if (isset($this->convertedResources[$source['tmp_name']])) {
            return $this->convertedResources[$source['tmp_name']];
        }

        if ($configuration === null) {
            throw new \InvalidArgumentException('Argument $configuration must not be null', 1589183114);
        }

        try {
            $resource = $this->importUploadedResource($source, $configuration);
            $resourcePublicationSlot->add($resource->getOriginalResource()->getOriginalFile());
        } catch (TypeConverterException $e) {
            return $e->getError();
        } catch (\Exception $e) {
            return GeneralUtility::makeInstance(Error::class, $e->getMessage(), $e->getCode());
        }

        $this->convertedResources[$source['tmp_name']] = $resource;
        return $resource;
    }

    /**
     * Deletes a previously uploaded file referenced by submittedFile.resourcePointer.
     */
    private function deletePreviousUpload(array $source): void
    {
        if (!isset($source['submittedFile']['resourcePointer'])) {
            return;
        }
        try {
            $resourcePointer = $this->hashService->validateAndStripHmac(
                $source['submittedFile']['resourcePointer'],
                HashScope::ResourcePointer->prefix(),
            );
            $fileUid = str_starts_with($resourcePointer, 'file:')
                ? (int)substr($resourcePointer, 5)
                : null;
            if ($fileUid !== null) {
                $this->deleteUploadedFiles([$fileUid]);
            }
        } catch (InvalidHashStringException) {
            // Invalid resource pointer – nothing to delete.
        }
    }

    /**
     * Extracts and validates file deletion data from the source array.
     *
     * @return array{0: list<int>, 1: list<int>} Array containing [deleteFileIndices, filesToDelete]
     */
    private function extractFileDeletionData(array $source): array
    {
        $deleteFileIndices = [];
        $filesToDelete = [];

        if (!array_key_exists('__deleteFile', $source) || !is_array($source['__deleteFile'])) {
            return [$deleteFileIndices, $filesToDelete];
        }

        foreach ($source['__deleteFile'] as $signedValue) {
            try {
                $deleteData = $this->hashService->validateAndStripHmac(
                    $signedValue,
                    HashScope::DeleteFile->prefix()
                );
                $deleteData = json_decode($deleteData, true, 512, JSON_THROW_ON_ERROR);
                if (isset($deleteData['fileIndex'])) {
                    $deleteFileIndices[] = (int)$deleteData['fileIndex'];
                }
                if (isset($deleteData['fileUid'])) {
                    $filesToDelete[] = (int)$deleteData['fileUid'];
                }
            } catch (InvalidHashStringException $e) {
                $this->logger?->warning(
                    'Invalid file deletion request: HMAC validation failed.',
                    ['exception' => $e]
                );
            } catch (\JsonException $e) {
                $this->logger?->warning(
                    'Invalid file deletion request: JSON decoding failed.',
                    ['exception' => $e]
                );
            }
        }

        return [$deleteFileIndices, $filesToDelete];
    }

    /**
     * Deletes uploaded files from the server and cleans up empty upload folders.
     *
     * @param list<int> $fileUids
     */
    private function deleteUploadedFiles(array $fileUids): void
    {
        foreach ($fileUids as $fileUid) {
            try {
                $file = $this->resourceFactory->getFileObject($fileUid);
                $parentFolder = $file->getParentFolder();
                $file->delete();
                $this->deleteEmptyUploadFolder($parentFolder);
            } catch (\Exception $e) {
                $this->logger?->warning(
                    'Could not delete uploaded file with uid {fileUid}.',
                    ['fileUid' => $fileUid, 'exception' => $e]
                );
            }
        }
    }

    /**
     * Deletes the upload folder if it's empty and was created by the form framework.
     */
    private function deleteEmptyUploadFolder(?Folder $folder): void
    {
        if ($folder === null) {
            return;
        }
        if (!str_starts_with($folder->getName(), 'form_')) {
            return;
        }
        if ($folder->getFileCount() === 0
            && $folder->getStorage()->countFoldersInFolder($folder) === 0
        ) {
            $folder->delete();
        }
    }

    /**
     * Import a resource and respect configuration given for properties
     */
    protected function importUploadedResource(
        array $uploadInfo,
        PropertyMappingConfigurationInterface $configuration
    ): PseudoFileReference {
        if (!GeneralUtility::makeInstance(FileNameValidator::class)->isValid($uploadInfo['name'])) {
            throw new TypeConverterException('Uploading files with PHP file extensions is not allowed!', 1471710357);
        }
        // `CONFIGURATION_UPLOAD_SEED` is expected to be defined
        // if it's not given any random seed is generated, instead of throwing an exception
        $seed = $configuration->getConfigurationValue(self::class, self::CONFIGURATION_UPLOAD_SEED)
            ?: GeneralUtility::makeInstance(Random::class)->generateRandomHexString(40);
        $uploadFolderId = $configuration->getConfigurationValue(self::class, self::CONFIGURATION_UPLOAD_FOLDER) ?: $this->defaultUploadFolder;
        $conflictMode = DuplicationBehavior::tryFrom($configuration->getConfigurationValue(self::class, self::CONFIGURATION_UPLOAD_CONFLICT_MODE)) ?? $this->defaultConflictMode;
        $pseudoFile = GeneralUtility::makeInstance(PseudoFile::class, $uploadInfo);

        $preStorageValidators = $configuration->getConfigurationValue(self::class, self::CONFIGURATION_PRE_STORAGE_VALIDATORS) ?? [];
        foreach ($preStorageValidators as $validator) {
            $validationResult = $validator->validate($pseudoFile);
            if ($validationResult->hasErrors()) {
                $firstError = current($validationResult->getErrors());
                throw TypeConverterException::fromError($firstError);
            }
        }

        $uploadFolder = $this->provideUploadFolder($uploadFolderId);
        // current folder name, derived from public random seed (`formSession`)
        $currentName = 'form_' . $this->hashService->hmac($seed, self::class);
        // sub-folder in $uploadFolder with 160 bit of derived entropy (.../form_<40-chars-hash>/actual.file)
        $uploadFolder = $this->provideTargetFolder($uploadFolder, $currentName);
        // allow skipping the consistency check, since custom validators have already been executed
        $this->skipResourceConsistencyCheckForUploads($uploadFolder->getStorage(), $uploadInfo);
        /** @var File $uploadedFile */
        $uploadedFile = $uploadFolder->addUploadedFile($uploadInfo, $conflictMode);

        $resourcePointer = isset($uploadInfo['submittedFile']['resourcePointer']) && !str_contains($uploadInfo['submittedFile']['resourcePointer'], 'file:')
            ? (int)$this->hashService->validateAndStripHmac($uploadInfo['submittedFile']['resourcePointer'], HashScope::ResourcePointer->prefix())
            : null;

        $fileReferenceModel = $this->createFileReferenceFromFalFileObject($uploadedFile, $resourcePointer);

        return $fileReferenceModel;
    }

    protected function createFileReferenceFromFalFileObject(
        File $file,
        ?int $resourcePointer = null
    ): PseudoFileReference {
        $fileReference = $this->resourceFactory->createFileReferenceObject(
            [
                'uid_local' => $file->getUid(),
                'uid_foreign' => StringUtility::getUniqueId('NEW_'),
                'uid' => StringUtility::getUniqueId('NEW_'),
                'crop' => null,
            ]
        );
        return $this->createFileReferenceFromFalFileReferenceObject($fileReference, $resourcePointer);
    }

    /**
     * In case no $resourcePointer is given a new file reference domain object
     * will be returned. Otherwise the file reference is reconstituted from
     * storage and will be updated(!) with the provided $falFileReference.
     */
    protected function createFileReferenceFromFalFileReferenceObject(
        CoreFileReference $falFileReference,
        ?int $resourcePointer = null
    ): PseudoFileReference {
        if ($resourcePointer === null) {
            $fileReference = GeneralUtility::makeInstance(PseudoFileReference::class);
        } else {
            $fileReference = $this->persistenceManager->getObjectByIdentifier($resourcePointer, PseudoFileReference::class, false);
        }

        $fileReference->setOriginalResource($falFileReference);
        return $fileReference;
    }

    /**
     * Returns a human-readable message for the given PHP file upload error
     * constant.
     */
    protected function getUploadErrorMessage(int $errorCode): string
    {
        $logMessage = match ($errorCode) {
            \UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
            \UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
            \UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
            \UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            \UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
            \UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            \UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.',
            default => 'Unknown upload error.',
        };
        $this->logger?->error($logMessage);

        $translationKey = match ($errorCode) {
            \UPLOAD_ERR_INI_SIZE, \UPLOAD_ERR_FORM_SIZE => 'upload.error.150530345',
            \UPLOAD_ERR_PARTIAL => 'upload.error.150530346',
            \UPLOAD_ERR_NO_FILE => 'upload.error.150530347',
            default => 'upload.error.150530348',
        };

        return GeneralUtility::makeInstance(TranslationService::class)->translate(
            $translationKey,
            null,
            'EXT:form/Resources/Private/Language/locallang.xlf'
        );
    }

    /**
     * Ensures that upload folder exists, creates it if it does not.
     */
    protected function provideUploadFolder(string $uploadFolderIdentifier): Folder
    {
        try {
            return $this->resourceFactory->getFolderObjectFromCombinedIdentifier($uploadFolderIdentifier);
        } catch (FolderDoesNotExistException $exception) {
            [$storageId, $storagePath] = explode(':', $uploadFolderIdentifier, 2);
            $storage = $this->storageRepository->getStorageObject($storageId);
            $folderNames = GeneralUtility::trimExplode('/', $storagePath, true);
            $uploadFolder = $this->provideTargetFolder($storage->getRootLevelFolder(), ...$folderNames);
            $this->provideFolderInitialization($uploadFolder);
            return $uploadFolder;
        }
    }

    /**
     * Ensures that particular target folder exists, creates it if it does not.
     */
    protected function provideTargetFolder(Folder $parentFolder, string $folderName): Folder
    {
        return $parentFolder->hasFolder($folderName)
            ? $parentFolder->getSubfolder($folderName)
            : $parentFolder->createFolder($folderName);
    }

    /**
     * Creates empty index.html file to avoid directory indexing,
     * in case it does not exist yet.
     */
    protected function provideFolderInitialization(Folder $parentFolder): void
    {
        if (!$parentFolder->hasFile('index.html')) {
            $parentFolder->createFile('index.html');
        }
    }

    protected function convertUploadedFileToUploadInfoArray(UploadedFile $uploadedFile): array
    {
        return [
            'name' => $uploadedFile->getClientFilename(),
            'tmp_name' => $uploadedFile->getTemporaryFileName(),
            'size' => $uploadedFile->getSize(),
            'error' => $uploadedFile->getError(),
            'type' => $uploadedFile->getClientMediaType(),
        ];
    }
}
