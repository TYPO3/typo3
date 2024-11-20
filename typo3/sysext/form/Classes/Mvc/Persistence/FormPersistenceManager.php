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

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Form\Mvc\Configuration\Exception\FileWriteException;
use TYPO3\CMS\Form\Mvc\Configuration\Exception\NoSuchFileException;
use TYPO3\CMS\Form\Mvc\Configuration\TypoScriptService;
use TYPO3\CMS\Form\Mvc\Configuration\YamlSource;
use TYPO3\CMS\Form\Mvc\Persistence\Event\AfterFormDefinitionLoadedEvent;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\NoUniqueIdentifierException;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\NoUniquePersistenceIdentifierException;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\PersistenceManagerException;
use TYPO3\CMS\Form\Slot\FilePersistenceSlot;

/**
 * Concrete implementation of the FormPersistenceManagerInterface
 *
 * Scope: frontend / backend
 * @internal
 */
#[AsAlias(FormPersistenceManagerInterface::class, public: true)]
readonly class FormPersistenceManager implements FormPersistenceManagerInterface
{
    public function __construct(
        protected YamlSource $yamlSource,
        protected StorageRepository $storageRepository,
        protected FilePersistenceSlot $filePersistenceSlot,
        protected ResourceFactory $resourceFactory,
        #[Autowire(service: 'cache.runtime')]
        protected FrontendInterface $runtimeCache,
        protected EventDispatcherInterface $eventDispatcher,
        protected TypoScriptService $typoScriptService,
    ) {}

    /**
     * Load the array formDefinition identified by $persistenceIdentifier,
     * let event listeners modify it, override it by TypoScript settings, and
     * return it. Only files with the extension .yaml or .form.yaml are loaded.
     */
    public function load(string $persistenceIdentifier, array $formSettings, array $typoScriptSettings): array
    {
        $cacheKey = 'ext-form-load-' . hash('xxh3', $persistenceIdentifier);
        if ($this->runtimeCache->has($cacheKey)) {
            $formDefinition = $this->runtimeCache->get($cacheKey);
        } else {
            if (PathUtility::isExtensionPath($persistenceIdentifier)) {
                $this->ensureValidPersistenceIdentifier($persistenceIdentifier, $formSettings);
                $file = $persistenceIdentifier;
            } else {
                $file = $this->retrieveFileByPersistenceIdentifier($persistenceIdentifier, $formSettings);
            }
            try {
                $formDefinition = $this->yamlSource->load([$file]);
                $this->generateErrorsIfFormDefinitionIsValidButHasInvalidFileExtension($formDefinition, $persistenceIdentifier);
            } catch (\Exception $e) {
                $formDefinition = [
                    'type' => 'Form',
                    'identifier' => $persistenceIdentifier,
                    'label' => $e->getMessage(),
                    'invalid' => true,
                ];
            }
            $this->runtimeCache->set($cacheKey, $formDefinition);
        }
        $formDefinition = $this->eventDispatcher
            ->dispatch(new AfterFormDefinitionLoadedEvent($formDefinition, $persistenceIdentifier, $cacheKey))
            ->getFormDefinition();
        if (empty($typoScriptSettings['formDefinitionOverrides'][$formDefinition['identifier']] ?? null)) {
            return $formDefinition;
        }
        $formDefinitionOverrides = $this->typoScriptService
            ->resolvePossibleTypoScriptConfiguration($typoScriptSettings['formDefinitionOverrides'][$formDefinition['identifier']]);
        ArrayUtility::mergeRecursiveWithOverrule($formDefinition, $formDefinitionOverrides);
        return $formDefinition;
    }

    /**
     * Save the array form representation identified by $persistenceIdentifier.
     *
     * Only files with the extension .form.yaml are saved.
     * If the formDefinition is located within an EXT: resource, save is only allowed if the
     * configuration path persistenceManager.allowSaveToExtensionPaths is set to true.
     *
     * @throws PersistenceManagerException
     */
    public function save(string $persistenceIdentifier, array $formDefinition, array $formSettings): void
    {
        if (!$this->hasValidFileExtension($persistenceIdentifier)) {
            throw new PersistenceManagerException(sprintf('The file "%s" could not be saved.', $persistenceIdentifier), 1477679820);
        }
        if ($this->pathIsIntendedAsExtensionPath($persistenceIdentifier)) {
            if (!($formSettings['persistenceManager']['allowSaveToExtensionPaths'] ?? false)) {
                throw new PersistenceManagerException('Save to extension paths is not allowed.', 1477680881);
            }
            if (!$this->isFileWithinAccessibleExtensionFolders($persistenceIdentifier, $formSettings)) {
                throw new PersistenceManagerException(
                    sprintf('The file "%s" could not be saved. Please check your configuration option "persistenceManager.allowedExtensionPaths"', $persistenceIdentifier),
                    1484073571
                );
            }
            $fileToSave = GeneralUtility::getFileAbsFileName($persistenceIdentifier);
        } else {
            $fileToSave = $this->getOrCreateFile($persistenceIdentifier);
        }
        try {
            $this->yamlSource->save($fileToSave, $formDefinition);
        } catch (FileWriteException $e) {
            throw new PersistenceManagerException(
                sprintf('The file "%s" could not be saved: %s', $persistenceIdentifier, $e->getMessage()),
                1512582637,
                $e
            );
        }
    }

    /**
     * Delete the form representation identified by $persistenceIdentifier.
     * Only files with the extension .form.yaml are removed.
     *
     * @throws PersistenceManagerException
     */
    public function delete(string $persistenceIdentifier, array $formSettings): void
    {
        if (!$this->hasValidFileExtension($persistenceIdentifier)) {
            throw new PersistenceManagerException(sprintf('The file "%s" could not be removed.', $persistenceIdentifier), 1472239534);
        }
        if (!$this->exists($persistenceIdentifier, $formSettings)) {
            throw new PersistenceManagerException(sprintf('The file "%s" could not be removed.', $persistenceIdentifier), 1472239535);
        }
        if ($this->pathIsIntendedAsExtensionPath($persistenceIdentifier)) {
            if (!$formSettings['persistenceManager']['allowDeleteFromExtensionPaths']) {
                throw new PersistenceManagerException(sprintf('The file "%s" could not be removed.', $persistenceIdentifier), 1472239536);
            }
            if (!$this->isFileWithinAccessibleExtensionFolders($persistenceIdentifier, $formSettings)) {
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
     * List all form definitions which can be loaded through this form persistence manager.
     *
     * Returns an associative array with each item containing the keys 'name' (the human-readable name of the form)
     * and 'persistenceIdentifier' (the unique identifier for the Form Persistence Manager e.g. the path to the saved form definition).
     *
     * @return array in the format [['name' => 'Form 01', 'persistenceIdentifier' => 'path1'], [ .... ]]
     */
    public function listForms(array $formSettings): array
    {
        $identifiers = [];
        $forms = [];
        foreach ($this->retrieveYamlFilesFromStorageFolders($formSettings) as $file) {
            $form = $this->loadMetaData($file, $formSettings);
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
        foreach ($this->retrieveYamlFilesFromExtensionFolders($formSettings) as $file) {
            $form = $this->loadMetaData($file, $formSettings);
            if ($this->looksLikeAFormDefinition($form)) {
                if ($this->hasValidFileExtension($file)) {
                    $forms[] = [
                        'identifier' => $form['identifier'],
                        'name' => $form['label'] ?? $form['identifier'],
                        'persistenceIdentifier' => $file,
                        'readOnly' => !$formSettings['persistenceManager']['allowSaveToExtensionPaths'],
                        'removable' => (bool)$formSettings['persistenceManager']['allowDeleteFromExtensionPaths'],
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
        return $this->sortForms($forms, $formSettings);
    }

    /**
     * Check if any form definition is available
     */
    public function hasForms(array $formSettings): bool
    {
        foreach ($this->retrieveYamlFilesFromStorageFolders($formSettings) as $file) {
            $form = $this->loadMetaData($file, $formSettings);
            if ($this->looksLikeAFormDefinition($form)) {
                return true;
            }
        }
        foreach ($this->retrieveYamlFilesFromExtensionFolders($formSettings) as $file) {
            $form = $this->loadMetaData($file, $formSettings);
            if ($this->looksLikeAFormDefinition($form)) {
                return true;
            }
        }
        return false;
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
    public function getAccessibleFormStorageFolders(array $formSettings): array
    {
        $storageFolders = [];
        if (!(is_array($formSettings['persistenceManager']['allowedFileMounts'] ?? false))) {
            return $storageFolders;
        }
        foreach ($formSettings['persistenceManager']['allowedFileMounts'] as $allowedFileMount) {
            $allowedFileMount = rtrim($allowedFileMount, '/') . '/';
            // $fileMountPath is like "/form_definitions/" or "/group_homes/1/form_definitions/"
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
     * Return a list of all accessible extension folders
     *
     * Only registered mount points from
     * persistenceManager.allowedExtensionPaths
     * are listed.
     */
    public function getAccessibleExtensionFolders(array $formSettings): array
    {
        if ($this->runtimeCache->has('ext-form-accessibleExtensionFolders')) {
            return $this->runtimeCache->get('ext-form-accessibleExtensionFolders');
        }
        $extensionFolders = [];
        if (empty($formSettings['persistenceManager']['allowedExtensionPaths'])
            || !is_array($formSettings['persistenceManager']['allowedExtensionPaths'])
        ) {
            $this->runtimeCache->set('formAccessibleExtensionFolders', $extensionFolders);
            return $extensionFolders;
        }
        foreach ($formSettings['persistenceManager']['allowedExtensionPaths'] as $allowedExtensionPath) {
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
        $this->runtimeCache->set('ext-form-accessibleExtensionFolders', $extensionFolders);
        return $extensionFolders;
    }

    /**
     * This takes a form identifier and returns a unique persistence identifier for it.
     * By default, this is just similar to the identifier. But if a form with the same persistence identifier already
     * exists a suffix is appended until the persistence identifier is unique.
     *
     * @param string $formIdentifier lowerCamelCased form identifier
     * @return string unique form persistence identifier
     * @throws NoUniquePersistenceIdentifierException
     */
    public function getUniquePersistenceIdentifier(string $formIdentifier, string $savePath, array $formSettings): string
    {
        $savePath = rtrim($savePath, '/') . '/';
        $formPersistenceIdentifier = $savePath . $formIdentifier . self::FORM_DEFINITION_FILE_EXTENSION;
        if (!$this->exists($formPersistenceIdentifier, $formSettings)) {
            return $formPersistenceIdentifier;
        }
        for ($attempts = 1; $attempts < 100; $attempts++) {
            $formPersistenceIdentifier = $savePath . sprintf('%s_%d', $formIdentifier, $attempts) . self::FORM_DEFINITION_FILE_EXTENSION;
            if (!$this->exists($formPersistenceIdentifier, $formSettings)) {
                return $formPersistenceIdentifier;
            }
        }
        $formPersistenceIdentifier = $savePath . sprintf('%s_%d', $formIdentifier, time()) . self::FORM_DEFINITION_FILE_EXTENSION;
        if (!$this->exists($formPersistenceIdentifier, $formSettings)) {
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
     * @return string unique form identifier
     * @throws NoUniqueIdentifierException
     */
    public function getUniqueIdentifier(array $formSettings, string $identifier): string
    {
        $originalIdentifier = $identifier;
        if ($this->checkForDuplicateIdentifier($formSettings, $identifier)) {
            for ($attempts = 1; $attempts < 100; $attempts++) {
                $identifier = sprintf('%s_%d', $originalIdentifier, $attempts);
                if (!$this->checkForDuplicateIdentifier($formSettings, $identifier)) {
                    return $identifier;
                }
            }
            $identifier = $originalIdentifier . '_' . time();
            if ($this->checkForDuplicateIdentifier($formSettings, $identifier)) {
                throw new NoUniqueIdentifierException(
                    sprintf('Could not find a unique identifier for form identifier "%s" after %d attempts', $identifier, $attempts),
                    1477688567
                );
            }
        }
        return $identifier;
    }

    /**
     * Check if a persistence path or if a persistence identifier path is configured within the
     * form setup "persistenceManager.allowedExtensionPaths" or persistenceManager.allowedFileMounts".
     * If the input is a persistence identifier an additional check for a valid file extension is performed.
     */
    public function isAllowedPersistencePath(string $persistencePath, array $formSettings): bool
    {
        $pathinfo = PathUtility::pathinfo($persistencePath);
        $persistencePathIsFile = isset($pathinfo['extension']);
        if ($persistencePathIsFile
            && $this->pathIsIntendedAsExtensionPath($persistencePath)
            && $this->hasValidFileExtension($persistencePath)
            && $this->isFileWithinAccessibleExtensionFolders($persistencePath, $formSettings)
        ) {
            return true;
        }
        if ($persistencePathIsFile
            && $this->pathIsIntendedAsFileMountPath($persistencePath)
            && $this->hasValidFileExtension($persistencePath)
            && $this->isFileWithinAccessibleFormStorageFolders($formSettings, $persistencePath)
        ) {
            return true;
        }
        if (!$persistencePathIsFile
            && $this->pathIsIntendedAsExtensionPath($persistencePath)
            && $this->isAccessibleExtensionFolder($persistencePath, $formSettings)
        ) {
            return true;
        }
        if (!$persistencePathIsFile
            && $this->pathIsIntendedAsFileMountPath($persistencePath)
            && $this->isAccessibleFormStorageFolder($formSettings, $persistencePath)
        ) {
            return true;
        }
        return false;
    }

    public function hasValidFileExtension(string $fileName): bool
    {
        return str_ends_with($fileName, self::FORM_DEFINITION_FILE_EXTENSION);
    }

    /**
     * Check whether a form with the specified $persistenceIdentifier exists.
     *
     * @return bool TRUE if a form with the given $persistenceIdentifier can be loaded
     */
    protected function exists(string $persistenceIdentifier, array $formSettings): bool
    {
        $exists = false;
        if ($this->hasValidFileExtension($persistenceIdentifier)) {
            if ($this->pathIsIntendedAsExtensionPath($persistenceIdentifier)) {
                if ($this->isFileWithinAccessibleExtensionFolders($persistenceIdentifier, $formSettings)) {
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
     * Retrieves yaml files from extension folders for further processing.
     * At this time it's not determined yet, whether these files contain form data.
     *
     * @return string[]
     */
    protected function retrieveYamlFilesFromExtensionFolders(array $formSettings): array
    {
        $filesFromExtensionFolders = [];
        foreach ($this->getAccessibleExtensionFolders($formSettings) as $relativePath => $fullPath) {
            foreach (new \DirectoryIterator($fullPath) as $fileInfo) {
                if ($fileInfo->getExtension() !== 'yaml') {
                    continue;
                }
                $filesFromExtensionFolders[] = $relativePath . $fileInfo->getFilename();
            }
        }
        return $filesFromExtensionFolders;
    }

    /**
     * Retrieves yaml files from storage folders for further processing.
     * At this time it's not determined yet, whether these files contain form data.
     *
     * @return File[]
     */
    protected function retrieveYamlFilesFromStorageFolders(array $formSettings): array
    {
        $filesFromStorageFolders = [];
        $fileExtensionFilter = GeneralUtility::makeInstance(FileExtensionFilter::class);
        $fileExtensionFilter->setAllowedFileExtensions(['yaml']);
        foreach ($this->getAccessibleFormStorageFolders($formSettings) as $folder) {
            $storage = $folder->getStorage();
            $storage->setFileAndFolderNameFilters([
                [$fileExtensionFilter, 'filterFileList'],
            ]);
            $files = $folder->getFiles(0, 0, Folder::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS, true);
            $filesFromStorageFolders = array_merge($filesFromStorageFolders, array_values($files));
            $storage->resetFileAndFolderNameFiltersToDefault();
        }
        return $filesFromStorageFolders;
    }

    /**
     * Check if an identifier is already used by a formDefinition.
     */
    protected function checkForDuplicateIdentifier(array $formSettings, string $identifier): bool
    {
        $identifierUsed = false;
        foreach ($this->listForms($formSettings) as $formDefinition) {
            if ($formDefinition['identifier'] === $identifier) {
                $identifierUsed = true;
                break;
            }
        }
        return $identifierUsed;
    }

    protected function pathIsIntendedAsExtensionPath(string $path): bool
    {
        return PathUtility::isExtensionPath($path);
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

    /**
     * @param string|File $persistenceIdentifier
     * @throws NoSuchFileException
     */
    protected function loadMetaData(string|File $persistenceIdentifier, array $formSettings): array
    {
        $file = null;
        if ($persistenceIdentifier instanceof File) {
            $file = $persistenceIdentifier;
            $persistenceIdentifier = $file->getCombinedIdentifier();
            $rawYamlContent = $file->getContents();
        } elseif (PathUtility::isExtensionPath($persistenceIdentifier)) {
            $this->ensureValidPersistenceIdentifier($persistenceIdentifier, $formSettings);
            $rawYamlContent = false;
            $absoluteFilePath = GeneralUtility::getFileAbsFileName($persistenceIdentifier);
            if ($absoluteFilePath !== '' && file_exists($absoluteFilePath)) {
                $rawYamlContent = file_get_contents($absoluteFilePath);
            }
        } else {
            $file = $this->retrieveFileByPersistenceIdentifier($persistenceIdentifier, $formSettings);
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
                } catch (ParseException) {
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
     * @throws PersistenceManagerException
     */
    protected function generateErrorsIfFormDefinitionIsValidButHasInvalidFileExtension(array $formDefinition, string $persistenceIdentifier): void
    {
        if ($this->looksLikeAFormDefinition($formDefinition) && !$this->hasValidFileExtension($persistenceIdentifier)) {
            throw new PersistenceManagerException(sprintf('Form definition "%s" does not end with ".form.yaml".', $persistenceIdentifier), 1531160649);
        }
    }

    /**
     * @throws PersistenceManagerException
     * @throws NoSuchFileException
     */
    protected function retrieveFileByPersistenceIdentifier(string $persistenceIdentifier, array $formSettings): File
    {
        $this->ensureValidPersistenceIdentifier($persistenceIdentifier, $formSettings);
        try {
            $file = $this->resourceFactory->retrieveFileOrFolderObject($persistenceIdentifier);
        } catch (\Exception) {
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
     * @throws PersistenceManagerException
     */
    protected function ensureValidPersistenceIdentifier(string $persistenceIdentifier, array $formSettings): void
    {
        if (pathinfo($persistenceIdentifier, PATHINFO_EXTENSION) !== 'yaml') {
            throw new PersistenceManagerException(sprintf('The file "%s" could not be loaded.', $persistenceIdentifier), 1477679819);
        }
        if ($this->pathIsIntendedAsExtensionPath($persistenceIdentifier)
            && !$this->isFileWithinAccessibleExtensionFolders($persistenceIdentifier, $formSettings)
        ) {
            throw new PersistenceManagerException(
                sprintf('The file "%s" could not be loaded. Please check your configuration option "persistenceManager.allowedExtensionPaths"', $persistenceIdentifier),
                1484071985
            );
        }
    }

    protected function isFileWithinAccessibleExtensionFolders(string $fileName, array $formSettings): bool
    {
        $pathInfo = PathUtility::pathinfo($fileName, PATHINFO_DIRNAME);
        $dirName = rtrim($pathInfo, '/') . '/';
        return array_key_exists($dirName, $this->getAccessibleExtensionFolders($formSettings));
    }

    protected function isFileWithinAccessibleFormStorageFolders(array $formSettings, string $fileName): bool
    {
        $pathInfo = PathUtility::pathinfo($fileName, PATHINFO_DIRNAME);
        $dirName = rtrim($pathInfo, '/') . '/';
        foreach (array_keys($this->getAccessibleFormStorageFolders($formSettings)) as $allowedPath) {
            if (str_starts_with($dirName, $allowedPath)) {
                return true;
            }
        }
        return false;
    }

    protected function isAccessibleExtensionFolder(string $folderName, array $formSettings): bool
    {
        $folderName = rtrim($folderName, '/') . '/';
        return array_key_exists($folderName, $this->getAccessibleExtensionFolders($formSettings));
    }

    protected function isAccessibleFormStorageFolder(array $formSettings, string $folderName): bool
    {
        $folderName = rtrim($folderName, '/') . '/';
        return array_key_exists($folderName, $this->getAccessibleFormStorageFolders($formSettings));
    }

    protected function looksLikeAFormDefinition(array $data): bool
    {
        return !empty($data['identifier']) && trim($data['type'] ?? '') === 'Form';
    }

    protected function sortForms(array $forms, array $formSettings): array
    {
        $keys = $formSettings['persistenceManager']['sortByKeys'] ?? ['name', 'fileUid'];
        usort($forms, static function (array $a, array $b) use ($keys) {
            foreach ($keys as $key) {
                if (isset($a[$key]) && isset($b[$key])) {
                    $diff = strcasecmp((string)$a[$key], (string)$b[$key]);
                    if ($diff) {
                        return $diff;
                    }
                }
            }
            return false;
        });
        $ascending = $formSettings['persistenceManager']['sortAscending'] ?? true;
        return $ascending ? $forms : array_reverse($forms);
    }
}
