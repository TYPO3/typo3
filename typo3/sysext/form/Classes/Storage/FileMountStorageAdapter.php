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

namespace TYPO3\CMS\Form\Storage;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Form\Domain\Configuration\PersistenceConfigurationService;
use TYPO3\CMS\Form\Domain\DTO\FormData;
use TYPO3\CMS\Form\Domain\DTO\FormMetadata;
use TYPO3\CMS\Form\Domain\DTO\SearchCriteria;
use TYPO3\CMS\Form\Domain\ValueObject\FormIdentifier;
use TYPO3\CMS\Form\Mvc\Configuration\Exception\NoSuchFileException;
use TYPO3\CMS\Form\Mvc\Configuration\YamlSource;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\PersistenceManagerException;
use TYPO3\CMS\Form\Slot\FilePersistenceSlot;

/**
 * Storage adapter for filemount-based form persistence
 *
 * @internal
 */
class FileMountStorageAdapter extends AbstractFileStorageAdapter implements StorageAdapterInterface
{
    public function __construct(
        protected readonly YamlSource $yamlSource,
        protected readonly ResourceFactory $resourceFactory,
        protected readonly PersistenceConfigurationService $storageConfiguration,
        protected readonly FilePersistenceSlot $filePersistenceSlot,
        #[Autowire(service: 'cache.runtime')]
        protected readonly FrontendInterface $runtimeCache,
    ) {}

    public function read(FormIdentifier $identifier): FormData
    {
        $file = $this->retrieveFileByPersistenceIdentifier($identifier->identifier);
        $formDefinition = $this->yamlSource->load([$file]);
        $this->generateErrorsIfFormDefinitionIsValidButHasInvalidFileExtension($formDefinition, $identifier->identifier);
        return FormData::fromArray($formDefinition);
    }

    public function write(FormIdentifier $identifier, FormData $data): void
    {
        if (!$this->hasValidFileExtension($identifier->identifier)) {
            throw new PersistenceManagerException(sprintf('The file "%s" could not be saved.', $identifier->identifier), 1477679820);
        }

        $fileToSave = $this->getOrCreateFile($identifier->identifier);

        try {
            $this->yamlSource->save($fileToSave, $data->toArray());
        } catch (\Exception $e) {
            throw new PersistenceManagerException(
                sprintf('The file "%s" could not be saved: %s', $identifier->identifier, $e->getMessage()),
                1512582637,
                $e
            );
        }
    }

    public function delete(FormIdentifier $identifier): void
    {
        if (!$this->hasValidFileExtension($identifier->identifier)) {
            throw new PersistenceManagerException(sprintf('The file "%s" could not be removed.', $identifier->identifier), 1472239534);
        }
        if (!$this->exists($identifier)) {
            throw new PersistenceManagerException(sprintf('The file "%s" could not be removed.', $identifier->identifier), 1764879545);
        }
        [$storageUid, $fileIdentifier] = explode(':', $identifier->identifier, 2);
        $storage = $this->getStorageByUid((int)$storageUid);
        $file = $storage->getFile($fileIdentifier);
        if (!$storage->checkFileActionPermission('delete', $file)) {
            throw new PersistenceManagerException(sprintf('No delete access to file "%s".', $identifier->identifier), 1472239516);
        }
        $storage->deleteFile($file);
    }

    public function exists(FormIdentifier $identifier): bool
    {
        $exists = false;
        if ($this->hasValidFileExtension($identifier->identifier)) {
            [$storageUid, $fileIdentifier] = explode(':', $identifier->identifier, 2);
            $storage = $this->getStorageByUid((int)$storageUid);
            $exists = $storage->hasFile($fileIdentifier);
        }
        return $exists;
    }

    public function findAll(SearchCriteria $criteria): array
    {
        $results = [];
        foreach ($this->retrieveYamlFilesFromStorageFolders() as $file) {
            $formMetadata = $this->loadMetaData($file);

            if (!$this->looksLikeAFormDefinition($formMetadata)) {
                continue;
            }

            if (!$this->hasValidFileExtension($file->getCombinedIdentifier())) {
                continue;
            }

            if (!$this->matchesCriteria($formMetadata, $criteria)) {
                continue;
            }

            $results[] = $formMetadata;
        }
        return $results;
    }

    /**
     * Return a list of all accessible file mountpoints for the
     * current backend user.
     *
     * Only registered mount points from
     * persistenceManager.allowedFileMounts
     * are listed.
     *
     * @return Folder[]
     */
    public function getAccessibleFormStorageFolders(): array
    {
        $storageFolders = [];
        $allowedFileMounts = $this->storageConfiguration->getAllowedFileMounts();

        if (empty($allowedFileMounts)) {
            return $storageFolders;
        }

        foreach ($allowedFileMounts as $allowedFileMount) {
            $allowedFileMount = rtrim($allowedFileMount, '/') . '/';
            [$storageUid, $fileMountPath] = explode(':', $allowedFileMount, 2);
            try {
                $storage = $this->getStorageByUid((int)$storageUid);
            } catch (PersistenceManagerException) {
                continue;
            }
            $isStorageFileMount = false;
            $parentFolder = $storage->getRootLevelFolder(false);
            foreach ($storage->getFileMounts() as $storageFileMount) {
                $storageFileMountFolder = $storageFileMount['folder'];
                // Normally should use ResourceStorage::isWithinFolder() to check if the configured file mount path is within
                // a storage file mount but this requires a valid Folder object and thus a directory which already exists.
                // And the folder could simply not exist yet.
                if (str_starts_with($fileMountPath, $storageFileMountFolder->getIdentifier())) {
                    $isStorageFileMount = true;
                    $parentFolder = $storageFileMountFolder;
                }
            }
            // Get storage folder object, create it if missing
            try {
                $fileMountFolder = $storage->getFolder($fileMountPath);
            } catch (InsufficientFolderAccessPermissionsException) {
                continue;
            } catch (FolderDoesNotExistException) {
                if ($isStorageFileMount) {
                    $fileMountPath = substr(
                        $fileMountPath,
                        strlen($parentFolder->getIdentifier())
                    );
                }
                try {
                    $fileMountFolder = $storage->createFolder($fileMountPath, $parentFolder);
                } catch (InsufficientFolderAccessPermissionsException) {
                    continue;
                }
            }
            $storageFolders[$allowedFileMount] = $fileMountFolder;
        }
        return $storageFolders;
    }

    /**
     * Check if a persistence path or if a persistence identifier path is configured within the
     * form setup "persistenceManager.allowedExtensionPaths" or persistenceManager.allowedFileMounts".
     * If the input is a persistence identifier an additional check for a valid file extension is performed.
     */
    public function isAllowedPersistencePath(string $persistencePath): bool
    {
        $pathinfo = PathUtility::pathinfo($persistencePath);
        $persistencePathIsFile = isset($pathinfo['extension']);
        if ($persistencePathIsFile
            && $this->pathIsIntendedAsFileMountPath($persistencePath)
            && $this->hasValidFileExtension($persistencePath)
            && $this->isFileWithinAccessibleFormStorageFolders($persistencePath)
        ) {
            return true;
        }
        if (!$persistencePathIsFile
            && $this->pathIsIntendedAsFileMountPath($persistencePath)
            && $this->isAccessibleFormStorageFolder($persistencePath)
        ) {
            return true;
        }
        return false;
    }

    /**
     * Retrieves yaml files from storage folders for further processing.
     * At this time it's not determined yet, whether these files contain form data.
     *
     * @return File[]
     */
    private function retrieveYamlFilesFromStorageFolders(): array
    {
        $filesFromStorageFolders = [];
        $fileExtensionFilter = GeneralUtility::makeInstance(FileExtensionFilter::class);
        $fileExtensionFilter->setAllowedFileExtensions(['yaml']);
        foreach ($this->getAccessibleFormStorageFolders() as $folder) {
            $storage = $folder->getStorage();
            $storage->setFileAndFolderNameFilters([
                [$fileExtensionFilter, 'filterFileList'],
            ]);
            $files = $folder->getFiles(0, 0, Folder::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS, true);
            array_push($filesFromStorageFolders, ...array_values($files));
            $storage->resetFileAndFolderNameFiltersToDefault();
        }
        return $filesFromStorageFolders;
    }

    /**
     * Returns a ResourceStorage for a given uid
     *
     * @throws PersistenceManagerException
     */
    protected function getStorageByUid(int $storageUid): ResourceStorage
    {
        $storage = $this->storageRepository->findByUid($storageUid);
        if (!$storage?->isBrowsable()) {
            throw new PersistenceManagerException(sprintf('Could not access storage with uid "%d".', $storageUid), 1471630581);
        }
        return $storage;
    }

    protected function pathIsIntendedAsFileMountPath(string $path): bool
    {
        if (empty($path)) {
            return false;
        }
        [$storageUid, $pathIdentifier] = explode(':', $path, 2);
        if (empty($storageUid) || empty($pathIdentifier)) {
            return false;
        }
        return MathUtility::canBeInterpretedAsInteger($storageUid);
    }

    protected function loadMetaData(string|File $fileOrIdentifier): FormMetadata
    {
        if ($fileOrIdentifier instanceof File) {
            $file = $fileOrIdentifier;
            $persistenceIdentifier = $file->getCombinedIdentifier();
            $rawYamlContent = $file->getContents();
        } else {
            $persistenceIdentifier = $fileOrIdentifier;
            $file = $this->retrieveFileByPersistenceIdentifier($fileOrIdentifier);
            $rawYamlContent = $file->getContents();
        }

        try {
            $yaml = $this->extractMetaDataFromCouldBeFormDefinition($rawYamlContent);
            $this->generateErrorsIfFormDefinitionIsValidButHasInvalidFileExtension($yaml, $persistenceIdentifier);
            return FormMetadata::createFromYaml(
                $yaml,
                $persistenceIdentifier,
                $file->getUid()
            );
        } catch (\Exception $e) {
            return FormMetadata::createInvalid($persistenceIdentifier, $e->getMessage());
        }
    }

    /**
     * @throws PersistenceManagerException
     * @throws NoSuchFileException
     */
    protected function retrieveFileByPersistenceIdentifier(string $identifier): File
    {
        $this->ensureValidPersistenceIdentifier($identifier);
        try {
            $file = $this->resourceFactory->retrieveFileOrFolderObject($identifier);
        } catch (\Exception) {
            // Top level catch to ensure useful following exception handling, because FAL throws top level exceptions.
            $file = null;
        }
        if ($file === null) {
            throw new NoSuchFileException(sprintf('YAML file "%s" could not be loaded', $identifier), 1524684442);
        }
        if (!$file->getStorage()->checkFileActionPermission('read', $file)) {
            throw new PersistenceManagerException(sprintf('No read access to file "%s".', $identifier), 1471630578);
        }
        return $file;
    }

    /**
     * @throws PersistenceManagerException
     */
    protected function ensureValidPersistenceIdentifier(string $identifier): void
    {
        if (pathinfo($identifier, PATHINFO_EXTENSION) !== 'yaml') {
            throw new PersistenceManagerException(sprintf('The file "%s" could not be loaded.', $identifier), 1477679819);
        }
    }

    protected function isFileWithinAccessibleFormStorageFolders(string $fileName): bool
    {
        $pathInfo = PathUtility::pathinfo($fileName, PATHINFO_DIRNAME);
        $dirName = rtrim($pathInfo, '/') . '/';
        foreach (array_keys($this->getAccessibleFormStorageFolders()) as $allowedPath) {
            if (str_starts_with($dirName, $allowedPath)) {
                return true;
            }
        }
        return false;
    }

    protected function isAccessibleFormStorageFolder(string $folderName): bool
    {
        $folderName = rtrim($folderName, '/') . '/';
        return array_key_exists($folderName, $this->getAccessibleFormStorageFolders());
    }

    /**
     * Returns a File object for a given $persistenceIdentifier.
     * If no file for this identifier exists a new object will be
     * created.
     *
     * @throws PersistenceManagerException
     */
    protected function getOrCreateFile(string $persistenceIdentifier): File
    {
        [$storageUid, $fileIdentifier] = explode(':', $persistenceIdentifier, 2);
        $storage = $this->getStorageByUid((int)$storageUid);
        $pathinfo = PathUtility::pathinfo($fileIdentifier);
        if (!$storage->hasFolder($pathinfo['dirname'])) {
            throw new PersistenceManagerException(sprintf('Could not create folder "%s".', $pathinfo['dirname']), 1471630579);
        }
        try {
            $folder = $storage->getFolder($pathinfo['dirname']);
        } catch (InsufficientFolderAccessPermissionsException) {
            throw new PersistenceManagerException(sprintf('No read access to folder "%s".', $pathinfo['dirname']), 1512583307);
        }
        if (!$storage->checkFolderActionPermission('write', $folder)) {
            throw new PersistenceManagerException(sprintf('No write access to folder "%s".', $pathinfo['dirname']), 1471630580);
        }
        if (!$storage->hasFile($fileIdentifier)) {
            $this->filePersistenceSlot->allowInvocation(
                FilePersistenceSlot::COMMAND_FILE_CREATE,
                $folder->getCombinedIdentifier() . $pathinfo['basename']
            );
            $file = $folder->createFile($pathinfo['basename']);
        } else {
            $file = $storage->getFile($fileIdentifier);
        }
        return $file;
    }

    public function getTypeIdentifier(): string
    {
        return 'filemount';
    }

    public function supports(string $identifier): bool
    {
        // File mount identifiers follow the pattern: "storageUid:/path/to/file"
        // Examples: "1:/forms/contact.form.yaml", "2:/user_forms/survey.form.yaml"
        return $this->pathIsIntendedAsFileMountPath($identifier);
    }

    public function getPriority(): int
    {
        // Normal priority - fallback for non-extension paths
        return 50;
    }
}
