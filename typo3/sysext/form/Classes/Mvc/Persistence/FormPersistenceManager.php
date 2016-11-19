<?php
declare(strict_types=1);
namespace TYPO3\CMS\Form\Mvc\Persistence;

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

use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Form\Mvc\Configuration\YamlSource;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\NoUniqueIdentifierException;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\NoUniquePersistenceIdentifierException;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\PersistenceManagerException;

/**
 * Concrete implementation of the FormPersistenceManagerInterface
 *
 * Scope: frontend / backend
 */
class FormPersistenceManager implements FormPersistenceManagerInterface
{

    /**
     * @var \TYPO3\CMS\Form\Mvc\Configuration\YamlSource
     */
    protected $yamlSource;

    /**
     * @var \TYPO3\CMS\Core\Resource\StorageRepository
     */
    protected $storageRepository;

    /**
     * @var array
     */
    protected $formSettings;

    /**
     * @param \TYPO3\CMS\Form\Mvc\Configuration\YamlSource $yamlSource
     * @internal
     */
    public function injectYamlSource(\TYPO3\CMS\Form\Mvc\Configuration\YamlSource $yamlSource)
    {
        $this->yamlSource = $yamlSource;
    }

    /**
     * @param \TYPO3\CMS\Core\Resource\StorageRepository $storageRepository
     * @internal
     */
    public function injectStorageRepository(\TYPO3\CMS\Core\Resource\StorageRepository $storageRepository)
    {
        $this->storageRepository = $storageRepository;
    }

    /**
     * @internal
     */
    public function initializeObject()
    {
        $this->formSettings = GeneralUtility::makeInstance(ObjectManager::class)
            ->get(ConfigurationManagerInterface::class)
            ->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_YAML_SETTINGS, 'form');
    }

    /**
     * Load the array formDefinition identified by $persistenceIdentifier, and return it.
     * Only files with the extension .yaml are loaded.
     * At this place there is no check if the file location is allowed.
     *
     * @param string $persistenceIdentifier
     * @return array
     * @throws PersistenceManagerException
     * @internal
     */
    public function load(string $persistenceIdentifier): array
    {
        if (pathinfo($persistenceIdentifier, PATHINFO_EXTENSION) !== 'yaml') {
            throw new PersistenceManagerException(sprintf('The file "%s" could not be loaded.', $persistenceIdentifier), 1477679819);
        }

        if (strpos($persistenceIdentifier, 'EXT:') === 0) {
            $file = $persistenceIdentifier;
        } else {
            $file = $this->getFileByIdentifier($persistenceIdentifier);
        }
        return $this->yamlSource->load([$file]);
    }

    /**
     * Save the array form representation identified by $persistenceIdentifier.
     * Only files with the extension .yaml are saved.
     * If the formDefinition is located within a EXT: resource, save is only
     * allowed if the configuration path
     * TYPO3.CMS.Form.persistenceManager.allowSaveToExtensionPaths
     * is set to true.
     *
     * @param string $persistenceIdentifier
     * @param array $formDefinition
     * @return void
     * @throws PersistenceManagerException
     * @internal
     */
    public function save(string $persistenceIdentifier, array $formDefinition)
    {
        if (pathinfo($persistenceIdentifier, PATHINFO_EXTENSION) !== 'yaml') {
            throw new PersistenceManagerException(sprintf('The file "%s" could not be saved.', $persistenceIdentifier), 1477679820);
        }

        if (strpos($persistenceIdentifier, 'EXT:') === 0) {
            if (!$this->formSettings['persistenceManager']['allowSaveToExtensionPaths']) {
                throw new PersistenceManagerException('Save to extension paths is not allowed.', 1477680881);
            }
            $fileToSave = GeneralUtility::getFileAbsFileName($persistenceIdentifier);
        } else {
            $fileToSave = $this->getOrCreateFile($persistenceIdentifier);
        }

        $this->yamlSource->save($fileToSave, $formDefinition);
    }

    /**
     * Delete the form representation identified by $persistenceIdentifier.
     * Only files with the extension .yaml are removed.
     * formDefinitions within an EXT: resource are not removable.
     *
     * @param string $persistenceIdentifier
     * @return void
     * @throws PersistenceManagerException
     * @internal
     */
    public function delete(string $persistenceIdentifier)
    {
        if (pathinfo($persistenceIdentifier, PATHINFO_EXTENSION) !== 'yaml') {
            throw new PersistenceManagerException(sprintf('The file "%s" could not be removed.', $persistenceIdentifier), 1472239534);
        }
        if (!$this->exists($persistenceIdentifier)) {
            throw new PersistenceManagerException(sprintf('The file "%s" could not be removed.', $persistenceIdentifier), 1472239535);
        }
        if (strpos($persistenceIdentifier, 'EXT:') === 0) {
            throw new PersistenceManagerException(sprintf('The file "%s" could not be removed.', $persistenceIdentifier), 1472239536);
        }

        list($storageUid, $fileIdentifier) = explode(':', $persistenceIdentifier, 2);
        $storage = $this->getStorageByUid((int)$storageUid);
        $file = $storage->getFile($fileIdentifier);
        if (!$storage->checkFileActionPermission('delete', $file)) {
            throw new PersistenceManagerException(sprintf('No delete access to file "%s".', $persistenceIdentifier), 1472239516);
        }
        $storage->deleteFile($file);
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
        if (pathinfo($persistenceIdentifier, PATHINFO_EXTENSION) === 'yaml') {
            if (strpos($persistenceIdentifier, 'EXT:') === 0) {
                $exists = file_exists(GeneralUtility::getFileAbsFileName($persistenceIdentifier));
            } else {
                list($storageUid, $fileIdentifier) = explode(':', $persistenceIdentifier, 2);
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
        $fileExtensionFilter = GeneralUtility::makeInstance(FileExtensionFilter::class);
        $fileExtensionFilter->setAllowedFileExtensions(['yaml']);

        $identifiers = [];
        $forms = [];
        /** @var \TYPO3\CMS\Core\Resource\Folder $folder */
        foreach ($this->getAccessibleFormStorageFolders() as $folder) {
            $storage = $folder->getStorage();
            $storage->addFileAndFolderNameFilter([$fileExtensionFilter, 'filterFileList']);

            $files = $folder->getFiles(0, 0, Folder::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS, true);
            foreach ($files as $file) {
                $persistenceIdentifier = $storage->getUid() . ':' . $file->getIdentifier();

                $form = $this->load($persistenceIdentifier);
                $forms[] = [
                    'identifier' => $form['identifier'],
                    'name' => isset($form['label']) ? $form['label'] : $form['identifier'],
                    'persistenceIdentifier' => $persistenceIdentifier,
                    'readOnly' => false,
                    'location' => 'storage',
                    'duplicateIdentifier' => false,
                ];
                $identifiers[$form['identifier']]++;
            }
            $storage->resetFileAndFolderNameFiltersToDefault();
        }

        foreach ($this->getAccessibleExtensionFolders() as $relativePath => $fullPath) {
            $relativePath = rtrim($relativePath, '/') . '/';
            foreach (new \DirectoryIterator($fullPath) as $fileInfo) {
                if ($fileInfo->getExtension() !== 'yaml') {
                    continue;
                }
                $form = $this->load($relativePath . $fileInfo->getFilename());
                $forms[] = [
                    'identifier' => $form['identifier'],
                    'name' => isset($form['label']) ? $form['label'] : $form['identifier'],
                    'persistenceIdentifier' => $relativePath . $fileInfo->getFilename(),
                    'readOnly' => $this->formSettings['persistenceManager']['allowSaveToExtensionPaths'] ? false: true,
                    'location' => 'extension',
                    'duplicateIdentifier' => false,
                ];
                $identifiers[$form['identifier']]++;
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

        return $forms;
    }

    /**
     * Return a list of all accessible file mountpoints for the
     * current backend user.
     *
     * Only registered mountpoints from
     * TYPO3.CMS.Form.persistenceManager.allowedFileMounts
     * are listet.
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
            list($storageUid, $fileMountIdentifier) = explode(':', $allowedFileMount, 2);
            $fileMountIdentifier = rtrim($fileMountIdentifier, '/') . '/';

            try {
                $storage = $this->getStorageByUid((int)$storageUid);
            } catch (PersistenceManagerException $e) {
                continue;
            }

            try {
                $folder = $storage->getFolder($fileMountIdentifier);
            } catch (InsufficientFolderAccessPermissionsException $e) {
                continue;
            }
            $storageFolders[$allowedFileMount] = $folder;
        }
        return $storageFolders;
    }

    /**
     * Return a list of all accessible extension folders
     *
     * Only registered mountpoints from
     * TYPO3.CMS.Form.persistenceManager.allowedExtensionPaths
     * are listet.
     *
     * @return array
     * @internal
     */
    public function getAccessibleExtensionFolders(): array
    {
        $extensionFolders = [];
        if (
            !isset($this->formSettings['persistenceManager']['allowedExtensionPaths'])
            || !is_array($this->formSettings['persistenceManager']['allowedExtensionPaths'])
            || empty($this->formSettings['persistenceManager']['allowedExtensionPaths'])
        ) {
            return $extensionFolders;
        }

        foreach ($this->formSettings['persistenceManager']['allowedExtensionPaths'] as $allowedExtensionPath) {
            if (strpos($allowedExtensionPath, 'EXT:') !== 0) {
                continue;
            }

            $allowedExtensionFullPath = GeneralUtility::getFileAbsFileName($allowedExtensionPath);
            if (!file_exists($allowedExtensionFullPath)) {
                continue;
            }
            $extensionFolders[$allowedExtensionPath] = $allowedExtensionFullPath;
        }
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
        $formPersistenceIdentifier = $savePath . $formIdentifier . '.yaml';
        if (!$this->exists($formPersistenceIdentifier)) {
            return $formPersistenceIdentifier;
        }
        for ($attempts = 1; $attempts < 100; $attempts++) {
            $formPersistenceIdentifier = $savePath . sprintf('%s_%d', $formIdentifier, $attempts) . '.yaml';
            if (!$this->exists($formPersistenceIdentifier)) {
                return $formPersistenceIdentifier;
            }
        }
        $formPersistenceIdentifier = $savePath . sprintf('%s_%d', $formIdentifier, time()) . '.yaml';
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
     * Check if a identifier is already used by a formDefintion.
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
     * Returns a File object for a given $persistenceIdentifier
     *
     * @param string $persistenceIdentifier
     * @return File
     * @throws PersistenceManagerException
     */
    protected function getFileByIdentifier(string $persistenceIdentifier): File
    {
        list($storageUid, $fileIdentifier) = explode(':', $persistenceIdentifier, 2);
        $storage = $this->getStorageByUid((int)$storageUid);
        $file = $storage->getFile($fileIdentifier);
        if (!$storage->checkFileActionPermission('read', $file)) {
            throw new PersistenceManagerException(sprintf('No read access to file "%s".', $persistenceIdentifier), 1471630578);
        }
        return $file;
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
        list($storageUid, $fileIdentifier) = explode(':', $persistenceIdentifier, 2);
        $storage = $this->getStorageByUid((int)$storageUid);
        $pathinfo = PathUtility::pathinfo($fileIdentifier);

        if (!$storage->hasFolder($pathinfo['dirname'])) {
            throw new PersistenceManagerException(sprintf('Could not create folder "%s".', $pathinfo['dirname']), 1471630579);
        }
        $folder = $storage->getFolder($pathinfo['dirname']);
        if (!$storage->checkFolderActionPermission('write', $folder)) {
            throw new PersistenceManagerException(sprintf('No write access to folder "%s".', $pathinfo['dirname']), 1471630580);
        }

        if (!$storage->hasFile($fileIdentifier)) {
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
}
