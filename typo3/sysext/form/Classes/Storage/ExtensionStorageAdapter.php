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
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Form\Domain\Configuration\PersistenceConfigurationService;
use TYPO3\CMS\Form\Domain\DTO\FormData;
use TYPO3\CMS\Form\Domain\DTO\FormMetadata;
use TYPO3\CMS\Form\Domain\DTO\SearchCriteria;
use TYPO3\CMS\Form\Domain\ValueObject\FormIdentifier;
use TYPO3\CMS\Form\Mvc\Configuration\Exception\NoSuchFileException;
use TYPO3\CMS\Form\Mvc\Configuration\YamlSource;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\PersistenceManagerException;

/**
 * Storage adapter for extension-based form persistence
 *
 * @internal
 */
class ExtensionStorageAdapter extends AbstractFileStorageAdapter implements StorageAdapterInterface
{
    public function __construct(
        protected readonly YamlSource $yamlSource,
        protected readonly ResourceFactory $resourceFactory,
        protected readonly PersistenceConfigurationService $storageConfiguration,
        #[Autowire(service: 'cache.runtime')]
        protected readonly FrontendInterface $runtimeCache,
    ) {}

    public function read(FormIdentifier $identifier): FormData
    {
        $this->ensureValidPersistenceIdentifier($identifier->identifier);
        $file = $identifier->identifier;
        $formDefinition = $this->yamlSource->load([$file]);
        $this->generateErrorsIfFormDefinitionIsValidButHasInvalidFileExtension($formDefinition, $identifier->identifier);
        return FormData::fromArray($formDefinition);
    }

    public function write(FormIdentifier $identifier, FormData $data): void
    {
        if (!$this->hasValidFileExtension($identifier->identifier)) {
            throw new PersistenceManagerException(sprintf('The file "%s" could not be saved.', $identifier->identifier), 1764879569);
        }

        if (!$this->storageConfiguration->isAllowedToSaveToExtensionPaths()) {
            throw new PersistenceManagerException('Save to extension paths is not allowed.', 1764879520);
        }
        if (!$this->isFileWithinAccessibleExtensionFolders($identifier->identifier)) {
            throw new PersistenceManagerException(
                sprintf('The file "%s" could not be saved. Please check your configuration option "persistenceManager.allowedExtensionPaths"', $identifier->identifier),
                1484073571
            );
        }
        $fileToSave = GeneralUtility::getFileAbsFileName($identifier->identifier);

        try {
            $this->yamlSource->save($fileToSave, $data->toArray());
        } catch (\Exception $e) {
            throw new PersistenceManagerException(
                sprintf('The file "%s" could not be saved: %s', $identifier->identifier, $e->getMessage()),
                1764879589,
                $e
            );
        }
    }

    public function delete(FormIdentifier $identifier): void
    {
        if (!$this->hasValidFileExtension($identifier->identifier)) {
            throw new PersistenceManagerException(sprintf('The file "%s" could not be removed.', $identifier->identifier), 1764879609);
        }
        if (!$this->exists($identifier)) {
            throw new PersistenceManagerException(sprintf('The file "%s" could not be removed.', $identifier->identifier), 1764879543);
        }
        if (!$this->storageConfiguration->isAllowedToDeleteFromExtensionPaths()) {
            throw new PersistenceManagerException(sprintf('The file "%s" could not be removed.', $identifier->identifier), 1472239536);
        }
        if (!$this->isFileWithinAccessibleExtensionFolders($identifier->identifier)) {
            $message = sprintf('The file "%s" could not be removed. Please check your configuration option "persistenceManager.allowedExtensionPaths"', $identifier->identifier);
            throw new PersistenceManagerException($message, 1484073878);
        }
        $fileToDelete = GeneralUtility::getFileAbsFileName($identifier->identifier);
        unlink($fileToDelete);
    }

    public function exists(FormIdentifier $identifier): bool
    {
        $exists = false;
        if ($this->hasValidFileExtension($identifier->identifier)) {
            if ($this->isFileWithinAccessibleExtensionFolders($identifier->identifier)) {
                $exists = file_exists(GeneralUtility::getFileAbsFileName($identifier->identifier));
            }
        }
        return $exists;
    }

    public function findAll(SearchCriteria $criteria): array
    {
        $results = [];
        foreach ($this->retrieveYamlFilesFromExtensionFolders() as $identifier) {
            $formMetadata = $this->loadMetaData($identifier);

            if (!$this->looksLikeAFormDefinition($formMetadata)) {
                continue;
            }

            if (!$this->hasValidFileExtension($identifier)) {
                continue;
            }

            $readOnly = !$this->storageConfiguration->isAllowedToSaveToExtensionPaths();
            $formMetadata = $formMetadata->withReadOnly($readOnly);

            $removable = $this->storageConfiguration->isAllowedToDeleteFromExtensionPaths();
            $formMetadata = $formMetadata->withRemovable($removable);

            if (!$this->matchesCriteria($formMetadata, $criteria)) {
                continue;
            }

            $results[] = $formMetadata;
        }

        return $results;
    }

    /**
     * Return a list of all accessible extension folders
     *
     * Only registered mount points from
     * persistenceManager.allowedExtensionPaths
     * are listed.
     */
    public function getAccessibleExtensionFolders(): array
    {
        $cacheKey = 'ext-form-accessibleExtensionFolders';

        if ($this->runtimeCache->has($cacheKey)) {
            return $this->runtimeCache->get($cacheKey);
        }

        $extensionFolders = [];
        $allowedExtensionPaths = $this->storageConfiguration->getAllowedExtensionPaths();

        if (empty($allowedExtensionPaths)) {
            $this->runtimeCache->set($cacheKey, $extensionFolders);
            return $extensionFolders;
        }

        foreach ($allowedExtensionPaths as $allowedExtensionPath) {
            if (!PathUtility::isExtensionPath($allowedExtensionPath)) {
                continue;
            }
            $allowedExtensionFullPath = GeneralUtility::getFileAbsFileName($allowedExtensionPath);
            if (!file_exists($allowedExtensionFullPath)) {
                continue;
            }
            $allowedExtensionPath = rtrim($allowedExtensionPath, '/') . '/';
            $extensionFolders[$allowedExtensionPath] = $allowedExtensionFullPath;
        }

        $this->runtimeCache->set($cacheKey, $extensionFolders);
        return $extensionFolders;
    }

    /**
     * Retrieves yaml files from extension folders for further processing.
     * At this time it's not determined yet, whether these files contain form data.
     *
     * @return string[]
     */
    protected function retrieveYamlFilesFromExtensionFolders(): array
    {
        $filesFromExtensionFolders = [];
        foreach ($this->getAccessibleExtensionFolders() as $relativePath => $fullPath) {
            foreach (new \DirectoryIterator($fullPath) as $fileInfo) {
                if ($fileInfo->getExtension() !== 'yaml') {
                    continue;
                }
                $filesFromExtensionFolders[] = $relativePath . $fileInfo->getFilename();
            }
        }
        return $filesFromExtensionFolders;
    }

    protected function loadMetaData(string $fileOrIdentifier): FormMetadata
    {
        $this->ensureValidPersistenceIdentifier($fileOrIdentifier);
        $persistenceIdentifier = $fileOrIdentifier;
        $rawYamlContent = false;
        $absoluteFilePath = GeneralUtility::getFileAbsFileName($fileOrIdentifier);
        if ($absoluteFilePath !== '' && file_exists($absoluteFilePath)) {
            $rawYamlContent = file_get_contents($absoluteFilePath);
        }

        try {
            if ($rawYamlContent === false) {
                throw new NoSuchFileException(sprintf('YAML file "%s" could not be loaded', $persistenceIdentifier), 1524684462);
            }
            $yaml = $this->extractMetaDataFromCouldBeFormDefinition($rawYamlContent);
            $this->generateErrorsIfFormDefinitionIsValidButHasInvalidFileExtension($yaml, $persistenceIdentifier);
            return FormMetadata::createFromYaml(
                $yaml,
                $persistenceIdentifier,
            );
        } catch (\Exception $e) {
            return FormMetadata::createInvalid($persistenceIdentifier, $e->getMessage());
        }
    }

    public function getTypeIdentifier(): string
    {
        return 'extension';
    }

    public function supports(string $identifier): bool
    {
        return PathUtility::isExtensionPath($identifier);
    }

    public function getPriority(): int
    {
        // High priority - extension paths should be checked first
        return 100;
    }

    protected function isAccessibleExtensionFolder(string $folderName): bool
    {
        $folderName = rtrim($folderName, '/') . '/';
        return array_key_exists($folderName, $this->getAccessibleExtensionFolders());
    }

    protected function isFileWithinAccessibleExtensionFolders(string $fileName): bool
    {
        $pathInfo = PathUtility::pathinfo($fileName, PATHINFO_DIRNAME);
        $dirName = rtrim($pathInfo, '/') . '/';
        return array_key_exists($dirName, $this->getAccessibleExtensionFolders());
    }

    /**
     * @throws PersistenceManagerException
     */
    protected function ensureValidPersistenceIdentifier(string $identifier): void
    {
        if (pathinfo($identifier, PATHINFO_EXTENSION) !== 'yaml') {
            throw new PersistenceManagerException(sprintf('The file "%s" could not be loaded.', $identifier), 1764879628);
        }
        if (PathUtility::isExtensionPath($identifier)
            && !$this->isFileWithinAccessibleExtensionFolders($identifier)
        ) {
            throw new PersistenceManagerException(
                sprintf('The file "%s" could not be loaded. Please check your configuration option "persistenceManager.allowedExtensionPaths"', $identifier),
                1484071985
            );
        }
    }

    /**
     * Check if a persistence path or if a persistence identifier path is configured within the
     * form setup "persistenceManager.allowedExtensionPaths"
     * If the input is a persistence identifier an additional check for a valid file extension is performed.
     */
    public function isAllowedPersistencePath(string $persistencePath): bool
    {
        $pathinfo = PathUtility::pathinfo($persistencePath);
        $persistencePathIsFile = isset($pathinfo['extension']);
        if ($persistencePathIsFile
            && $this->hasValidFileExtension($persistencePath)
            && $this->isFileWithinAccessibleExtensionFolders($persistencePath)
        ) {
            return true;
        }
        if (!$persistencePathIsFile
            && $this->isAccessibleExtensionFolder($persistencePath)
        ) {
            return true;
        }
        return false;
    }
}
