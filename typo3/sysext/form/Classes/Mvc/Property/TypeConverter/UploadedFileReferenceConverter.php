<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Mvc\Property\TypeConverter;

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

use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\File as File;
use TYPO3\CMS\Core\Resource\FileReference as CoreFileReference;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\AbstractFileFolder;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;
use TYPO3\CMS\Form\Mvc\Property\Exception\TypeConverterException;
use TYPO3\CMS\Form\Service\TranslationService;
use TYPO3\CMS\Form\Slot\ResourcePublicationSlot;

/**
 * Class UploadedFileReferenceConverter
 *
 * Scope: frontend
 * @internal
 */
class UploadedFileReferenceConverter extends AbstractTypeConverter
{

    /**
     * Folder where the file upload should go to (including storage).
     */
    const CONFIGURATION_UPLOAD_FOLDER = 1;

    /**
     * How to handle a upload when the name of the uploaded file conflicts.
     */
    const CONFIGURATION_UPLOAD_CONFLICT_MODE = 2;

    /**
     * Random seed to be used for deriving storage sub-folders.
     */
    const CONFIGURATION_UPLOAD_SEED = 3;

    /**
     * Validator for file types
     */
    const CONFIGURATION_FILE_VALIDATORS = 4;

    /**
     * @var string
     */
    protected $defaultUploadFolder = '1:/user_upload/';

    /**
     * One of 'cancel', 'replace', 'rename'
     *
     * @var string
     */
    protected $defaultConflictMode = 'rename';

    /**
     * @var array
     */
    protected $sourceTypes = ['array'];

    /**
     * @var string
     */
    protected $targetType = PseudoFileReference::class;

    /**
     * Take precedence over the available FileReferenceConverter
     *
     * @var int
     */
    protected $priority = 12;

    /**
     * @var PseudoFileReference[]
     */
    protected $convertedResources = [];

    /**
     * @var ResourceFactory
     */
    protected $resourceFactory;

    /**
     * @var \TYPO3\CMS\Extbase\Security\Cryptography\HashService
     */
    protected $hashService;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @param ResourceFactory $resourceFactory
     * @internal
     */
    public function injectResourceFactory(ResourceFactory $resourceFactory)
    {
        $this->resourceFactory = $resourceFactory;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Security\Cryptography\HashService $hashService
     * @internal
     */
    public function injectHashService(\TYPO3\CMS\Extbase\Security\Cryptography\HashService $hashService)
    {
        $this->hashService = $hashService;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface $persistenceManager
     * @internal
     */
    public function injectPersistenceManager(\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface $persistenceManager)
    {
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * Actually convert from $source to $targetType, taking into account the fully
     * built $convertedChildProperties and $configuration.
     *
     * @param array $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface $configuration
     * @return AbstractFileFolder|Error|null
     * @internal
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null)
    {
        // slot/listener using `FileDumpController` instead of direct public URL in (later) rendering process
        $resourcePublicationSlot = GeneralUtility::makeInstance(ResourcePublicationSlot::class);
        if (!isset($source['error']) || $source['error'] === \UPLOAD_ERR_NO_FILE) {
            if (isset($source['submittedFile']['resourcePointer'])) {
                try {
                    // File references use numeric resource pointers, direct
                    // file relations are using "file:" prefix (e.g. "file:5")
                    $resourcePointer = $this->hashService->validateAndStripHmac($source['submittedFile']['resourcePointer']);
                    if (strpos($resourcePointer, 'file:') === 0) {
                        $fileUid = (int)substr($resourcePointer, 5);
                        $resource = $this->createFileReferenceFromFalFileObject($this->resourceFactory->getFileObject($fileUid));
                    } else {
                        $resource = $this->createFileReferenceFromFalFileReferenceObject(
                            $this->resourceFactory->getFileReferenceObject($resourcePointer),
                            (int)$resourcePointer
                        );
                    }
                    $resourcePublicationSlot->add($resource->getOriginalResource()->getOriginalFile());
                    return $resource;
                } catch (\InvalidArgumentException $e) {
                    // Nothing to do. No file is uploaded and resource pointer is invalid. Discard!
                }
            }
            return null;
        }

        if ($source['error'] !== \UPLOAD_ERR_OK) {
            return $this->objectManager->get(Error::class, $this->getUploadErrorMessage($source['error']), 1471715915);
        }

        if (isset($this->convertedResources[$source['tmp_name']])) {
            return $this->convertedResources[$source['tmp_name']];
        }

        try {
            $resource = $this->importUploadedResource($source, $configuration);
            $resourcePublicationSlot->add($resource->getOriginalResource()->getOriginalFile());
        } catch (TypeConverterException $e) {
            return $e->getError();
        } catch (\Exception $e) {
            return $this->objectManager->get(Error::class, $e->getMessage(), $e->getCode());
        }

        $this->convertedResources[$source['tmp_name']] = $resource;
        return $resource;
    }

    /**
     * Import a resource and respect configuration given for properties
     *
     * @param array $uploadInfo
     * @param PropertyMappingConfigurationInterface $configuration
     * @return PseudoFileReference
     */
    protected function importUploadedResource(
        array $uploadInfo,
        PropertyMappingConfigurationInterface $configuration
    ): PseudoFileReference {
        if (!GeneralUtility::verifyFilenameAgainstDenyPattern($uploadInfo['name'])) {
            throw new TypeConverterException('Uploading files with PHP file extensions is not allowed!', 1471710357);
        }
        // `CONFIGURATION_UPLOAD_SEED` is expected to be defined
        // if it's not given any random seed is generated, instead of throwing an exception
        $seed = $configuration->getConfigurationValue(self::class, self::CONFIGURATION_UPLOAD_SEED)
            ?: GeneralUtility::makeInstance(Random::class)->generateRandomHexString(40);
        $uploadFolderId = $configuration->getConfigurationValue(self::class, self::CONFIGURATION_UPLOAD_FOLDER) ?: $this->defaultUploadFolder;
        $conflictMode = $configuration->getConfigurationValue(self::class, self::CONFIGURATION_UPLOAD_CONFLICT_MODE) ?: $this->defaultConflictMode;
        $pseudoFile = GeneralUtility::makeInstance(PseudoFile::class, $uploadInfo);

        $validators = $configuration->getConfigurationValue(self::class, self::CONFIGURATION_FILE_VALIDATORS);
        if (is_array($validators)) {
            foreach ($validators as $validator) {
                if ($validator instanceof AbstractValidator) {
                    $validationResult = $validator->validate($pseudoFile);
                    if ($validationResult->hasErrors()) {
                        throw TypeConverterException::fromError($validationResult->getErrors()[0]);
                    }
                }
            }
        }

        $uploadFolder = $this->provideUploadFolder($uploadFolderId);
        // current folder name, derived from public random seed (`formSession`)
        $currentName = 'form_' . GeneralUtility::hmac($seed, self::class);
        $uploadFolder = $this->provideTargetFolder($uploadFolder, $currentName);
        // sub-folder in $uploadFolder with 160 bit of derived entropy (.../form_<40-chars-hash>/actual.file)
        $uploadedFile = $uploadFolder->addUploadedFile($uploadInfo, $conflictMode);

        $resourcePointer = isset($uploadInfo['submittedFile']['resourcePointer']) && strpos($uploadInfo['submittedFile']['resourcePointer'], 'file:') === false
            ? (int)$this->hashService->validateAndStripHmac($uploadInfo['submittedFile']['resourcePointer'])
            : null;

        $fileReferenceModel = $this->createFileReferenceFromFalFileObject($uploadedFile, $resourcePointer);

        return $fileReferenceModel;
    }

    /**
     * @param File $file
     * @param int $resourcePointer
     * @return PseudoFileReference
     */
    protected function createFileReferenceFromFalFileObject(
        File $file,
        int $resourcePointer = null
    ): PseudoFileReference {
        $fileReference = $this->resourceFactory->createFileReferenceObject(
            [
                'uid_local' => $file->getUid(),
                'uid_foreign' => uniqid('NEW_'),
                'uid' => uniqid('NEW_'),
                'crop' => null,
            ]
        );
        return $this->createFileReferenceFromFalFileReferenceObject($fileReference, $resourcePointer);
    }

    /**
     * In case no $resourcePointer is given a new file reference domain object
     * will be returned. Otherwise the file reference is reconstituted from
     * storage and will be updated(!) with the provided $falFileReference.
     *
     * @param CoreFileReference $falFileReference
     * @param int $resourcePointer
     * @return PseudoFileReference
     */
    protected function createFileReferenceFromFalFileReferenceObject(
        CoreFileReference $falFileReference,
        int $resourcePointer = null
    ): PseudoFileReference {
        if ($resourcePointer === null) {
            $fileReference = $this->objectManager->get(PseudoFileReference::class);
        } else {
            $fileReference = $this->persistenceManager->getObjectByIdentifier($resourcePointer, PseudoFileReference::class, false);
        }

        $fileReference->setOriginalResource($falFileReference);
        return $fileReference;
    }

    /**
     * Returns a human-readable message for the given PHP file upload error
     * constant.
     *
     * @param int $errorCode
     * @return string
     */
    protected function getUploadErrorMessage(int $errorCode): string
    {
        $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
        switch ($errorCode) {
            case \UPLOAD_ERR_INI_SIZE:
                $logger->error('The uploaded file exceeds the upload_max_filesize directive in php.ini.', []);
                return TranslationService::getInstance()->translate('upload.error.150530345', null, 'EXT:form/Resources/Private/Language/locallang.xlf');
            case \UPLOAD_ERR_FORM_SIZE:
                $logger->error('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.', []);
                return TranslationService::getInstance()->translate('upload.error.150530345', null, 'EXT:form/Resources/Private/Language/locallang.xlf');
            case \UPLOAD_ERR_PARTIAL:
                $logger->error('The uploaded file was only partially uploaded.', []);
                return TranslationService::getInstance()->translate('upload.error.150530346', null, 'EXT:form/Resources/Private/Language/locallang.xlf');
            case \UPLOAD_ERR_NO_FILE:
                $logger->error('No file was uploaded.', []);
                return TranslationService::getInstance()->translate('upload.error.150530347', null, 'EXT:form/Resources/Private/Language/locallang.xlf');
            case \UPLOAD_ERR_NO_TMP_DIR:
                $logger->error('Missing a temporary folder.', []);
                return TranslationService::getInstance()->translate('upload.error.150530348', null, 'EXT:form/Resources/Private/Language/locallang.xlf');
            case \UPLOAD_ERR_CANT_WRITE:
                $logger->error('Failed to write file to disk.', []);
                return TranslationService::getInstance()->translate('upload.error.150530348', null, 'EXT:form/Resources/Private/Language/locallang.xlf');
            case \UPLOAD_ERR_EXTENSION:
                $logger->error('File upload stopped by extension.', []);
                return TranslationService::getInstance()->translate('upload.error.150530348', null, 'EXT:form/Resources/Private/Language/locallang.xlf');
            default:
                $logger->error('Unknown upload error.', []);
                return TranslationService::getInstance()->translate('upload.error.150530348', null, 'EXT:form/Resources/Private/Language/locallang.xlf');
        }
    }

    /**
     * Ensures that upload folder exists, creates it if it does not.
     *
     * @param string $uploadFolderIdentifier
     * @return Folder
     */
    protected function provideUploadFolder(string $uploadFolderIdentifier): Folder
    {
        try {
            return $this->resourceFactory->getFolderObjectFromCombinedIdentifier($uploadFolderIdentifier);
        } catch (FolderDoesNotExistException $exception) {
            [$storageId, $storagePath] = explode(':', $uploadFolderIdentifier, 2);
            $storage = $this->resourceFactory->getStorageObject($storageId);
            $folderNames = GeneralUtility::trimExplode('/', $storagePath, true);
            $uploadFolder = $this->provideTargetFolder($storage->getRootLevelFolder(), ...$folderNames);
            $this->provideFolderInitialization($uploadFolder);
            return $uploadFolder;
        }
    }

    /**
     * Ensures that particular target folder exists, creates it if it does not.
     *
     * @param Folder $parentFolder
     * @param string $folderName
     * @return Folder
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
     *
     * @param Folder $parentFolder
     */
    protected function provideFolderInitialization(Folder $parentFolder): void
    {
        if (!$parentFolder->hasFile('index.html')) {
            $parentFolder->createFile('index.html');
        }
    }
}
