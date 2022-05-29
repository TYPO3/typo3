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

/*
 * Inspired by and partially taken from the Neos.Form package (www.neos.io)
 */

namespace TYPO3\CMS\Form\Mvc\Persistence;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Form\Mvc\Configuration\Exception\FileWriteException;
use TYPO3\CMS\Form\Mvc\Configuration\Exception\NoSuchFileException;
use TYPO3\CMS\Form\Mvc\Configuration\YamlSource;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\NoUniqueIdentifierException;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\NoUniquePersistenceIdentifierException;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\PersistenceManagerException;
use TYPO3\CMS\Form\Slot\FilePersistenceSlot;

/**
 * Concrete implementation of the FormPersistenceManagerInterface
 *
 * Scope: frontend / backend
 */
class FormPersistenceManager implements FormPersistenceManagerInterface
{
    const FORM_DEFINITION_FILE_EXTENSION = '.form.yaml';

    protected YamlSource $yamlSource;
    protected StorageRepository $storageRepository;
    protected FilePersistenceSlot $filePersistenceSlot;
    protected ResourceFactory $resourceFactory;
    protected array $formSettings;
    protected FrontendInterface $runtimeCache;

    public function __construct(
        YamlSource $yamlSource,
        StorageRepository $storageRepository,
        FilePersistenceSlot $filePersistenceSlot,
        ResourceFactory $resourceFactory,
        ConfigurationManagerInterface $configurationManager,
        CacheManager $cacheManager
    ) {
        $this->yamlSource = $yamlSource;
        $this->storageRepository = $storageRepository;
        $this->filePersistenceSlot = $filePersistenceSlot;
        $this->resourceFactory = $resourceFactory;
        $this->formSettings = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_YAML_SETTINGS, 'form');
        $this->runtimeCache = $cacheManager->getCache('runtime');
    }

    /**
     * Load the array formDefinition identified by $persistenceIdentifier, and return it.
     * Only files with the extension .yaml or .form.yaml are loaded.
     *
     * @param string $persistenceIdentifier
     * @return array
     * @internal
     */
    public function load(string $persistenceIdentifier): array
    {
        $cacheKey = 'formLoad' . md5($persistenceIdentifier);

        $yaml = $this->runtimeCache->get($cacheKey);
        if ($yaml !== false) {
            return $yaml;
        }

        if (PathUtility::isExtensionPath($persistenceIdentifier)) {
            $this->ensureValidPersistenceIdentifier($persistenceIdentifier);
            $file = $persistenceIdentifier;
        } else {
            $file = $this->retrieveFileByPersistenceIdentifier($persistenceIdentifier);
        }

        try {
            $yaml = $this->yamlSource->load([$file]);
            $this->generateErrorsIfFormDefinitionIsValidButHasInvalidFileExtension($yaml, $persistenceIdentifier);
        } catch (\Exception $e) {
            $yaml = [
                'type' => 'Form',
                'identifier' => $persistenceIdentifier,
                'label' => $e->getMessage(),
                'invalid' => true,
            ];
        }
        $this->runtimeCache->set($cacheKey, $yaml);

        return $yaml;
    }

    /**
     * Save the array form representation identified by $persistenceIdentifier.
     * Only files with the extension .form.yaml are saved.
     * If the formDefinition is located within an EXT: resource, save is only
     * allowed if the configuration path
     * TYPO3.CMS.Form.persistenceManager.allowSaveToExtensionPaths
     * is set to true.
     *
     * @param string $persistenceIdentifier
     * @param array $formDefinition
     * @throws PersistenceManagerException
     * @internal
     */
    public function save(string $persistenceIdentifier, array $formDefinition)
    {
        if (!$this->hasValidFileExtension($persistenceIdentifier)) {
            throw new PersistenceManagerException(sprintf('The file "%s" could not be saved.', $persistenceIdentifier), 1477679820);
        }

        if ($this->pathIsIntendedAsExtensionPath($persistenceIdentifier)) {
            if (!$this->formSettings['persistenceManager']['allowSaveToExtensionPaths']) {
                throw new PersistenceManagerException('Save to extension paths is not allowed.', 1477680881);
            }
            if (!$this->isFileWithinAccessibleExtensionFolders($persistenceIdentifier)) {
                $message = sprintf('The file "%s" could not be saved. Please check your configuration option "persistenceManager.allowedExtensionPaths"', $persistenceIdentifier);
                throw new PersistenceManagerException($message, 1484073571);
            }
            $fileToSave = GeneralUtility::getFileAbsFileName($persistenceIdentifier);
        } else {
            $fileToSave = $this->getOrCreateFile($persistenceIdentifier);
        }

        try {
            $this->yamlSource->save($fileToSave, $formDefinition);
        } catch (FileWriteException $e) {
            throw new PersistenceManagerException(sprintf(
                'The file "%s" could not be saved: %s',
                $persistenceIdentifier,
                $e->getMessage()
            ), 1512582637, $e);
        }
    }

    /**
     * Delete the form representation identified by $persistenceIdentifier.
     * Only files with the extension .form.yaml are removed.
     *
     * @param string $persistenceIdentifier
     * @throws PersistenceManagerException
     * @internal
     */
    public function delete(string $persistenceIdentifier)
    {
        if (!$this->hasValidFileExtension($persistenceIdentifier)) {
            throw new PersistenceManagerException(sprintf('The file "%s" could not be removed.', $persistenceIdentifier), 1472239534);
        }
        if (!$this->exists($persistenceIdentifier)) {
            throw new PersistenceManagerException(sprintf('The file "%s" could not be removed.', $persistenceIdentifier), 1472239535);
        }
        if ($this->pathIsIntendedAsExtensionPath($persistenceIdentifier)) {
            if (!$this->formSettings['persistenceManager']['allowDeleteFromExtensionPaths']) {
                throw new PersistenceManagerException(sprintf('The file "%s" could not be removed.', $persistenceIdentifier), 1472239536);
            }
            if (!$this->isFileWithinAccessibleExtensionFolders($persistenceIdentifier)) {
                $message = sprintf('The file "%s" could not be removed. Please check your configuration option "persistenceManager.allowedExtensionPaths"', $persistenceIdentifier);
                throw new PersistenceManagerException($message, 1484073878);
            }
            $fileToDelete = GeneralUtility::getFileAbsFileName($persistenceIdentifier);
            unlink($fileToDelete);
        } else {
            [$storageUid, $fileIdentifier] = explode(':', $persistenceIdentifier, 2);
            $storage = $this->getStorageByUid((int)$storageUid);
            $file = $storage->getFile($fileIdentifier);
            if (!$storage->checkFileActionPermission('delete', $file)) {
                throw new PersistenceManagerException(sprintf('No delete access to file "%s".', $persistenceIdentifier), 1472239516);
            }
            $storage->deleteFile($file);
        }
    }

    /**
     * Check whether a form with the specified $persistenceIdentifier exists
     *
     * @param string $persistenceIdentifier
     * @return bool TRUE if a form with the given $persistenceIdentifier can be loaded, otherwise FALSE
     * @internal
     */
    public function exists(string $persistenceIdentifier): bool
    {
        $exists = false;
        if ($this->hasValidFileExtension($persistenceIdentifier)) {
            if ($this->pathIsIntendedAsExtensionPath($persistenceIdentifier)) {
                if ($this->isFileWithinAccessibleExtensionFolders($persistenceIdentifier)) {
                    $exists = file_exists(GeneralUtility::getFileAbsFileName($persistenceIdentifier));
                }
            } else {
                [$storageUid, $fileIdentifier] = explode(':', $persistenceIdentifier, 2);
                $storage = $this->getStorageByUid((int)$storageUid);
                $exists = $storage->hasFile($fileIdentifier);
            }
        }
        return $exists;
    }

    /**
     * List all form definitions which can be loaded through this form persistence
     * manager.
     *
     * Returns an associative array with each item containing the keys 'name' (the human-readable name of the form)
     * and 'persistenceIdentifier' (the unique identifier for the Form Persistence Manager e.g. the path to the saved form definition).
     *
     * @return array in the format [['name' => 'Form 01', 'persistenceIdentifier' => 'path1'], [ .... ]]
     * @internal
     */
    public function listForms(): array
    {
        $identifiers = [];
        $forms = [];

        foreach ($this->retrieveYamlFilesFromStorageFolders() as $file) {
            $form = $this->loadMetaData($file);

            if (!$this->looksLikeAFormDefinition($form)) {
                continue;
            }

            $persistenceIdentifier = $file->getCombinedIdentifier();
            if ($this->hasValidFileExtension($persistenceIdentifier)) {
                $forms[] = [
                    'identifier' => $form['identifier'],
                    'name' => $form['label'] ?? $form['identifier'],
                    'persistenceIdentifier' => $persistenceIdentifier,
                    'readOnly' => false,
                    'removable' => true,
                    'location' => 'storage',
                    'duplicateIdentifier' => false,
                    'invalid' => $form['invalid'] ?? false,
                    'fileUid' => $form['fileUid'] ?? 0,
                ];
                if (!isset($identifiers[$form['identifier']])) {
                    $identifiers[$form['identifier']] = 0;
                }
                $identifiers[$form['identifier']]++;
            }
        }

        foreach ($this->retrieveYamlFilesFromExtensionFolders() as $fullPath => $fileName) {
            $form = $this->loadMetaData($fullPath);

            if ($this->looksLikeAFormDefinition($form)) {
                if ($this->hasValidFileExtension($fileName)) {
                    $forms[] = [
                        'identifier' => $form['identifier'],
                        'name' => $form['label'] ?? $form['identifier'],
                        'persistenceIdentifier' => $fullPath,
                        'readOnly' => $this->formSettings['persistenceManager']['allowSaveToExtensionPaths'] ? false: true,
                        'removable' => $this->formSettings['persistenceManager']['allowDeleteFromExtensionPaths'] ? true: false,
                        'location' => 'extension',
                        'duplicateIdentifier' => false,
                        'invalid' => $form['invalid'] ?? false,
                        'fileUid' => $form['fileUid'] ?? 0,
                    ];
                    if (!isset($identifiers[$form['identifier']])) {
                        $identifiers[$form['identifier']] = 0;
                    }
                    $identifiers[$form['identifier']]++;
                }
            }
        }

        foreach ($identifiers as $identifier => $count) {
            if ($count > 1) {
                foreach ($forms as &$formDefinition) {
                    if ($formDefinition['identifier'] === $identifier) {
                        $formDefinition['duplicateIdentifier'] = true;
                    }
                }
            }
        }

        return $this->sortForms($forms);
    }

    /**
     * Retrieves yaml files from storage folders for further processing.
     * At this time it's not determined yet, whether these files contain form data.
     *
     * @return File[]
     * @internal
     */
    public function retrieveYamlFilesFromStorageFolders(): array
    {
        $filesFromStorageFolders = [];

        $fileExtensionFilter = GeneralUtility::makeInstance(FileExtensionFilter::class);
        $fileExtensionFilter->setAllowedFileExtensions(['yaml']);

        foreach ($this->getAccessibleFormStorageFolders() as $folder) {
            $storage = $folder->getStorage();
            $storage->setFileAndFolderNameFilters([
                [$fileExtensionFilter, 'filterFileList'],
            ]);

            $files = $folder->getFiles(
                0,
                0,
                Folder::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS,
                true
            );
            $filesFromStorageFolders = array_merge($filesFromStorageFolders, array_values($files));
            $storage->resetFileAndFolderNameFiltersToDefault();
        }

        return $filesFromStorageFolders;
    }

    /**
     * Retrieves yaml files from extension folders for further processing.
     * At this time it's not determined yet, whether these files contain form data.
     *
     * @return array<string, string>
     * @internal
     */
    public function retrieveYamlFilesFromExtensionFolders(): array
    {
        $filesFromExtensionFolders = [];

        foreach ($this->getAccessibleExtensionFolders() as $relativePath => $fullPath) {
            foreach (new \DirectoryIterator($fullPath) as $fileInfo) {
                if ($fileInfo->getExtension() !== 'yaml') {
                    continue;
                }
                $filesFromExtensionFolders[$relativePath . $fileInfo->getFilename()] = $fileInfo->getFilename();
            }
        }

        return $filesFromExtensionFolders;
    }

    /**
     * Return a list of all accessible file mountpoints for the
     * current backend user.
     *
     * Only registered mountpoints from
     * TYPO3.CMS.Form.persistenceManager.allowedFileMounts
     * are listed.
     *
     * @return Folder[]
     * @internal
     */
    public function getAccessibleFormStorageFolders(): array
    {
        $storageFolders = [];

        if (
            !isset($this->formSettings['persistenceManager']['allowedFileMounts'])
            || !is_array($this->formSettings['persistenceManager']['allowedFileMounts'])
            || empty($this->formSettings['persistenceManager']['allowedFileMounts'])
        ) {
            return $storageFolders;
        }

        foreach ($this->formSettings['persistenceManager']['allowedFileMounts'] as $allowedFileMount) {
            $allowedFileMount = rtrim($allowedFileMount, '/') . '/';
            // $fileMountPath is like "/form_definitions/" or "/group_homes/1/form_definitions/"
            [$storageUid, $fileMountPath] = explode(':', $allowedFileMount, 2);

            try {
                $storage = $this->getStorageByUid((int)$storageUid);
            } catch (PersistenceManagerException $e) {
                continue;
            }

            $isStorageFileMount = false;
            $parentFolder = $storage->getRootLevelFolder(false);

            foreach ($storage->getFileMounts() as $storageFileMount) {
                /** @var Folder */
                $storageFileMountFolder = $storageFileMount['folder'];

                // Normally should use ResourceStorage::isWithinFolder() to check if the configured file mount path is within a storage file mount but this requires a valid Folder object and thus a directory which already exists. And the folder could simply not exist yet.
                if (str_starts_with($fileMountPath, $storageFileMountFolder->getIdentifier())) {
                    $isStorageFileMount = true;
                    $parentFolder = $storageFileMountFolder;
                }
            }

            // Get storage folder object, create it if missing
            try {
                $fileMountFolder = $storage->getFolder($fileMountPath);
            } catch (InsufficientFolderAccessPermissionsException $e) {
                continue;
            } catch (FolderDoesNotExistException $e) {
                if ($isStorageFileMount) {
                    $fileMountPath = substr(
                        $fileMountPath,
                        strlen($parentFolder->getIdentifier())
                    );
                }

                try {
                    $fileMountFolder = $storage->createFolder($fileMountPath, $parentFolder);
                } catch (InsufficientFolderAccessPermissionsException $e) {
                    continue;
                }
            }

            $storageFolders[$allowedFileMount] = $fileMountFolder;
        }
        return $storageFolders;
    }

    /**
     * Return a list of all accessible extension folders
     *
     * Only registered mountpoints from
     * TYPO3.CMS.Form.persistenceManager.allowedExtensionPaths
     * are listed.
     *
     * @return array
     * @internal
     */
    public function getAccessibleExtensionFolders(): array
    {
        $extensionFolders = $this->runtimeCache->get('formAccessibleExtensionFolders');

        if ($extensionFolders !== false) {
            return $extensionFolders;
        }

        $extensionFolders = [];
        if (
            !isset($this->formSettings['persistenceManager']['allowedExtensionPaths'])
            || !is_array($this->formSettings['persistenceManager']['allowedExtensionPaths'])
            || empty($this->formSettings['persistenceManager']['allowedExtensionPaths'])
        ) {
            $this->runtimeCache->set('formAccessibleExtensionFolders', $extensionFolders);
            return $extensionFolders;
        }

        foreach ($this->formSettings['persistenceManager']['allowedExtensionPaths'] as $allowedExtensionPath) {
            if (!$this->pathIsIntendedAsExtensionPath($allowedExtensionPath)) {
                continue;
            }

            $allowedExtensionFullPath = GeneralUtility::getFileAbsFileName($allowedExtensionPath);
            if (!file_exists($allowedExtensionFullPath)) {
                continue;
            }
            $allowedExtensionPath = rtrim($allowedExtensionPath, '/') . '/';
            $extensionFolders[$allowedExtensionPath] = $allowedExtensionFullPath;
        }

        $this->runtimeCache->set('formAccessibleExtensionFolders', $extensionFolders);
        return $extensionFolders;
    }

    /**
     * This takes a form identifier and returns a unique persistence identifier for it.
     * By default this is just similar to the identifier. But if a form with the same persistence identifier already
     * exists a suffix is appended until the persistence identifier is unique.
     *
     * @param string $formIdentifier lowerCamelCased form identifier
     * @param string $savePath
     * @return string unique form persistence identifier
     * @throws NoUniquePersistenceIdentifierException
     * @internal
     */
    public function getUniquePersistenceIdentifier(string $formIdentifier, string $savePath): string
    {
        $savePath = rtrim($savePath, '/') . '/';
        $formPersistenceIdentifier = $savePath . $formIdentifier . self::FORM_DEFINITION_FILE_EXTENSION;
        if (!$this->exists($formPersistenceIdentifier)) {
            return $formPersistenceIdentifier;
        }
        for ($attempts = 1; $attempts < 100; $attempts++) {
            $formPersistenceIdentifier = $savePath . sprintf('%s_%d', $formIdentifier, $attempts) . self::FORM_DEFINITION_FILE_EXTENSION;
            if (!$this->exists($formPersistenceIdentifier)) {
                return $formPersistenceIdentifier;
            }
        }
        $formPersistenceIdentifier = $savePath . sprintf('%s_%d', $formIdentifier, time()) . self::FORM_DEFINITION_FILE_EXTENSION;
        if (!$this->exists($formPersistenceIdentifier)) {
            return $formPersistenceIdentifier;
        }

        throw new NoUniquePersistenceIdentifierException(
            sprintf('Could not find a unique persistence identifier for form identifier "%s" after %d attempts', $formIdentifier, $attempts),
            1476010403
        );
    }

    /**
     * This takes a form identifier and returns a unique identifier for it.
     * If a formDefinition with the same identifier already exists a suffix is
     * appended until the identifier is unique.
     *
     * @param string $identifier
     * @return string unique form identifier
     * @throws NoUniqueIdentifierException
     * @internal
     */
    public function getUniqueIdentifier(string $identifier): string
    {
        $originalIdentifier = $identifier;
        if ($this->checkForDuplicateIdentifier($identifier)) {
            for ($attempts = 1; $attempts < 100; $attempts++) {
                $identifier = sprintf('%s_%d', $originalIdentifier, $attempts);
                if (!$this->checkForDuplicateIdentifier($identifier)) {
                    return $identifier;
                }
            }
            $identifier = $originalIdentifier . '_' . time();
            if ($this->checkForDuplicateIdentifier($identifier)) {
                throw new NoUniqueIdentifierException(
                    sprintf('Could not find a unique identifier for form identifier "%s" after %d attempts', $identifier, $attempts),
                    1477688567
                );
            }
        }
        return  $identifier;
    }

    /**
     * Check if an identifier is already used by a formDefinition.
     *
     * @param string $identifier
     * @return bool
     * @internal
     */
    public function checkForDuplicateIdentifier(string $identifier): bool
    {
        $identifierUsed = false;
        foreach ($this->listForms() as $formDefinition) {
            if ($formDefinition['identifier'] === $identifier) {
                $identifierUsed = true;
                break;
            }
        }
        return $identifierUsed;
    }

    /**
     * Check if a persistence path or if a persistence identifier path
     * is configured within the form setup
     * (TYPO3.CMS.Form.persistenceManager.allowedExtensionPaths / TYPO3.CMS.Form.persistenceManager.allowedFileMounts).
     * If the input is a persistence identifier an additional check for a
     * valid file extension will be performed.
     * .
     * @param string $persistencePath
     * @return bool
     * @internal
     */
    public function isAllowedPersistencePath(string $persistencePath): bool
    {
        $pathinfo = PathUtility::pathinfo($persistencePath);
        $persistencePathIsFile = isset($pathinfo['extension']);

        if (
            $persistencePathIsFile
            && $this->pathIsIntendedAsExtensionPath($persistencePath)
            && $this->hasValidFileExtension($persistencePath)
            && $this->isFileWithinAccessibleExtensionFolders($persistencePath)
        ) {
            return true;
        }
        if (
            $persistencePathIsFile
            && $this->pathIsIntendedAsFileMountPath($persistencePath)
            && $this->hasValidFileExtension($persistencePath)
            && $this->isFileWithinAccessibleFormStorageFolders($persistencePath)
        ) {
            return true;
        }
        if (
            !$persistencePathIsFile
            && $this->pathIsIntendedAsExtensionPath($persistencePath)
            && $this->isAccessibleExtensionFolder($persistencePath)
        ) {
            return true;
        }
        if (
            !$persistencePathIsFile
            && $this->pathIsIntendedAsFileMountPath($persistencePath)
            && $this->isAccessibleFormStorageFolder($persistencePath)
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param string $path
     * @return bool
     */
    protected function pathIsIntendedAsExtensionPath(string $path): bool
    {
        return PathUtility::isExtensionPath($path);
    }

    /**
     * @param string $path
     * @return bool
     */
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

    /**
     * Returns a File object for a given $persistenceIdentifier.
     * If no file for this identifier exists a new object will be
     * created.
     *
     * @param string $persistenceIdentifier
     * @return File
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
        } catch (InsufficientFolderAccessPermissionsException $e) {
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

    /**
     * Returns a ResourceStorage for a given uid
     *
     * @param int $storageUid
     * @return ResourceStorage
     * @throws PersistenceManagerException
     */
    protected function getStorageByUid(int $storageUid): ResourceStorage
    {
        $storage = $this->storageRepository->findByUid($storageUid);
        if (
            !$storage instanceof ResourceStorage
            || !$storage->isBrowsable()
        ) {
            throw new PersistenceManagerException(sprintf('Could not access storage with uid "%d".', $storageUid), 1471630581);
        }
        return $storage;
    }

    /**
     * @param string|File $persistenceIdentifier
     * @return array
     * @throws NoSuchFileException
     */
    protected function loadMetaData($persistenceIdentifier): array
    {
        $file = null;
        if ($persistenceIdentifier instanceof File) {
            $file = $persistenceIdentifier;
            $persistenceIdentifier = $file->getCombinedIdentifier();
            $rawYamlContent = $file->getContents();
        } elseif (PathUtility::isExtensionPath($persistenceIdentifier)) {
            $this->ensureValidPersistenceIdentifier($persistenceIdentifier);
            $rawYamlContent = false;
            $absoluteFilePath = GeneralUtility::getFileAbsFileName($persistenceIdentifier);
            if ($absoluteFilePath !== '' && file_exists($absoluteFilePath)) {
                $rawYamlContent = file_get_contents($absoluteFilePath);
            }
        } else {
            $file = $this->retrieveFileByPersistenceIdentifier($persistenceIdentifier);
            $rawYamlContent = $file->getContents();
        }

        try {
            if ($rawYamlContent === false) {
                throw new NoSuchFileException(sprintf('YAML file "%s" could not be loaded', $persistenceIdentifier), 1524684462);
            }

            $yaml = $this->extractMetaDataFromCouldBeFormDefinition($rawYamlContent);
            $this->generateErrorsIfFormDefinitionIsValidButHasInvalidFileExtension($yaml, $persistenceIdentifier);
            if ($file !== null) {
                $yaml['fileUid'] = $file->getUid();
            }
        } catch (\Exception $e) {
            $yaml = [
                'type' => 'Form',
                'identifier' => $persistenceIdentifier,
                'label' => $e->getMessage(),
                'invalid' => true,
            ];
        }

        return $yaml;
    }

    /**
     * @param string $maybeRawFormDefinition
     * @return array
     */
    protected function extractMetaDataFromCouldBeFormDefinition(string $maybeRawFormDefinition): array
    {
        $metaDataProperties = ['identifier', 'type', 'label', 'prototypeName'];
        $metaData = [];
        foreach (explode(LF, $maybeRawFormDefinition) as $line) {
            if (empty($line) || $line[0] === ' ') {
                continue;
            }

            $parts = explode(':', $line, 2);
            $key = trim($parts[0]);
            if (!($parts[1] ?? null) || !in_array($key, $metaDataProperties, true)) {
                continue;
            }

            if ($key === 'label') {
                try {
                    $parsedLabelLine = Yaml::parse($line);
                    $value = $parsedLabelLine['label'] ?? '';
                } catch (ParseException $e) {
                    $value = '';
                }
            } else {
                $value = trim($parts[1], " '\"\r");
            }

            $metaData[$key] = $value;
        }

        return $metaData;
    }

    /**
     * @param array $formDefinition
     * @param string $persistenceIdentifier
     * @throws PersistenceManagerException
     */
    protected function generateErrorsIfFormDefinitionIsValidButHasInvalidFileExtension(array $formDefinition, string $persistenceIdentifier): void
    {
        if (
            $this->looksLikeAFormDefinition($formDefinition)
            && !$this->hasValidFileExtension($persistenceIdentifier)
        ) {
            throw new PersistenceManagerException(sprintf('Form definition "%s" does not end with ".form.yaml".', $persistenceIdentifier), 1531160649);
        }
    }

    /**
     * @param string $persistenceIdentifier
     * @return File
     * @throws PersistenceManagerException
     * @throws NoSuchFileException
     */
    protected function retrieveFileByPersistenceIdentifier(string $persistenceIdentifier): File
    {
        $this->ensureValidPersistenceIdentifier($persistenceIdentifier);

        try {
            $file = $this->resourceFactory->retrieveFileOrFolderObject($persistenceIdentifier);
        } catch (\Exception $e) {
            // Top level catch to ensure useful following exception handling, because FAL throws top level exceptions.
            $file = null;
        }

        if ($file === null) {
            throw new NoSuchFileException(sprintf('YAML file "%s" could not be loaded', $persistenceIdentifier), 1524684442);
        }

        if (!$file->getStorage()->checkFileActionPermission('read', $file)) {
            throw new PersistenceManagerException(sprintf('No read access to file "%s".', $persistenceIdentifier), 1471630578);
        }

        return $file;
    }

    /**
     * @param string $persistenceIdentifier
     * @throws PersistenceManagerException
     * @throws NoSuchFileException
     */
    protected function ensureValidPersistenceIdentifier(string $persistenceIdentifier): void
    {
        if (pathinfo($persistenceIdentifier, PATHINFO_EXTENSION) !== 'yaml') {
            throw new PersistenceManagerException(sprintf('The file "%s" could not be loaded.', $persistenceIdentifier), 1477679819);
        }

        if (
            $this->pathIsIntendedAsExtensionPath($persistenceIdentifier)
            && !$this->isFileWithinAccessibleExtensionFolders($persistenceIdentifier)
        ) {
            $message = sprintf('The file "%s" could not be loaded. Please check your configuration option "persistenceManager.allowedExtensionPaths"', $persistenceIdentifier);
            throw new PersistenceManagerException($message, 1484071985);
        }
    }

    /**
     * @param string $fileName
     * @return bool
     * @internal only to be used within TYPO3 Core, not part of TYPO3 Core API
     */
    public function hasValidFileExtension(string $fileName): bool
    {
        return str_ends_with($fileName, self::FORM_DEFINITION_FILE_EXTENSION);
    }

    /**
     * @param string $fileName
     * @return bool
     */
    protected function isFileWithinAccessibleExtensionFolders(string $fileName): bool
    {
        $pathInfo = PathUtility::pathinfo($fileName, PATHINFO_DIRNAME);
        $pathInfo = is_string($pathInfo) ? $pathInfo : '';
        $dirName = rtrim($pathInfo, '/') . '/';
        return array_key_exists($dirName, $this->getAccessibleExtensionFolders());
    }

    /**
     * @param string $fileName
     * @return bool
     */
    protected function isFileWithinAccessibleFormStorageFolders(string $fileName): bool
    {
        $pathInfo = PathUtility::pathinfo($fileName, PATHINFO_DIRNAME);
        $pathInfo = is_string($pathInfo) ? $pathInfo : '';
        $dirName = rtrim($pathInfo, '/') . '/';
        return array_key_exists($dirName, $this->getAccessibleFormStorageFolders());
    }

    /**
     * @param string $folderName
     * @return bool
     */
    protected function isAccessibleExtensionFolder(string $folderName): bool
    {
        $folderName = rtrim($folderName, '/') . '/';
        return array_key_exists($folderName, $this->getAccessibleExtensionFolders());
    }

    /**
     * @param string $folderName
     * @return bool
     */
    protected function isAccessibleFormStorageFolder(string $folderName): bool
    {
        $folderName = rtrim($folderName, '/') . '/';
        return array_key_exists($folderName, $this->getAccessibleFormStorageFolders());
    }

    /**
     * @param array $data
     * @return bool
     */
    protected function looksLikeAFormDefinition(array $data): bool
    {
        return isset($data['identifier'], $data['type']) && !empty($data['identifier']) && trim($data['type']) === 'Form';
    }

    /**
     * @param array $forms
     * @return array
     */
    protected function sortForms(array $forms): array
    {
        $keys = $this->formSettings['persistenceManager']['sortByKeys'] ?? ['name', 'fileUid'];
        $ascending = $this->formSettings['persistenceManager']['sortAscending'] ?? true;

        usort($forms, static function (array $a, array $b) use ($keys) {
            foreach ($keys as $key) {
                if (isset($a[$key]) && isset($b[$key])) {
                    $diff = strcasecmp((string)$a[$key], (string)$b[$key]);
                    if ($diff) {
                        return $diff;
                    }
                }
            }
        });

        return ($ascending) ? $forms : array_reverse($forms);
    }
}
