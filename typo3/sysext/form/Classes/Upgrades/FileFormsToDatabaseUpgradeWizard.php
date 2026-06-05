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

namespace TYPO3\CMS\Form\Upgrades;

use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Attribute\UpgradeWizard;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Upgrades\ConfirmableInterface;
use TYPO3\CMS\Core\Upgrades\Confirmation;
use TYPO3\CMS\Core\Upgrades\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Core\Upgrades\UpgradeWizardInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Form\Domain\Configuration\PersistenceConfigurationService;
use TYPO3\CMS\Form\Domain\DTO\FormData;
use TYPO3\CMS\Form\Domain\DTO\FormMetadata;
use TYPO3\CMS\Form\Domain\Repository\FormDefinitionRepository;
use TYPO3\CMS\Form\Mvc\Configuration\Exception\NoSuchFileException;
use TYPO3\CMS\Form\Mvc\Configuration\YamlSource;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\PersistenceManagerException;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface;
use TYPO3\CMS\Form\Service\FormTransferService;

/**
 * Migrate file-based form definitions (YAML) to database storage.
 *
 * This wizard reads all form definitions from the paths configured in
 * `persistenceManager.allowedFileMounts` (deprecated since v14.2),
 * imports them into the form_definition database table,
 * and updates all tt_content references (FlexForm persistenceIdentifier) to
 * point to the new database records.
 *
 * After successful migration, the original YAML files are deleted from
 * file storage to avoid duplicates.
 *
 * When removing this class in v16, also remove all classes and methods tagged with
 * `@deprecated Remove in v16 along with the FileFormsToDatabaseUpgradeWizard`.
 *
 * @since 14.2
 * @internal
 */
#[UpgradeWizard('formFileFormsToDatabaseMigration')]
final readonly class FileFormsToDatabaseUpgradeWizard implements UpgradeWizardInterface, ConfirmableInterface
{
    public function __construct(
        private FormDefinitionRepository $formDefinitionRepository,
        private FormTransferService $formTransferService,
        private LoggerInterface $logger,
        private YamlSource $yamlSource,
        private ResourceFactory $resourceFactory,
        private PersistenceConfigurationService $storageConfiguration,
        private StorageRepository $storageRepository,
    ) {}

    public function getTitle(): string
    {
        return 'Migrate file-based forms to database storage';
    }

    public function getDescription(): string
    {
        $forms = $this->getFileBasedForms();
        $count = count($forms);

        if ($count === 0) {
            return 'No file-based form definitions found.';
        }

        $description = sprintf(
            'Found %d file-based form definition(s) that will be migrated to database storage:',
            $count
        );

        foreach ($forms as $form) {
            $description .= LF . sprintf(
                '  • %s (%s)',
                $form->name,
                $form->persistenceIdentifier ?? $form->identifier
            );
        }

        $description .= LF . LF . 'After migration, all tt_content references will be updated automatically.';
        $description .= LF . LF . 'Note: Only references in tt_content (CType form_formframework) are updated '
            . 'automatically. If your installation uses form persistence identifiers in custom '
            . 'database tables or FlexForm fields outside tt_content, these references must be '
            . 'updated manually.';
        $description .= LF . LF . 'The original YAML files WILL BE DELETED after successful migration. '
            . 'Please ensure backups exist. Failures will be logged and can be found in their '
            . 'configured log locations after execution, and should be reviewed.';

        return $description;
    }

    public function getConfirmation(): Confirmation
    {
        return new Confirmation(
            'Migrate forms to database?',
            'This will move all filemount-based form definitions into the database '
            . 'and update content element references in tt_content. '
            . 'References in custom database tables or FlexForm fields outside tt_content '
            . 'are NOT updated automatically and must be migrated manually. '
            . 'The original YAML files will be deleted after successful migration. '
            . 'YAML files in extension directories are not affected. '
            . 'Please make sure you have a backup before proceeding.',
            false,
            'Yes, migrate forms to database',
            'No, keep file-based storage'
        );
    }

    public function updateNecessary(): bool
    {
        return $this->getFileBasedForms() !== [];
    }

    public function executeUpdate(): bool
    {
        $forms = $this->getFileBasedForms();

        if ($forms === []) {
            return true;
        }

        $success = true;
        $migrationMap = [];
        $migratedFiles = [];

        foreach ($forms as $form) {
            $persistenceIdentifier = $form->persistenceIdentifier ?? $form->identifier;

            try {
                $formData = $this->readForm($persistenceIdentifier);
            } catch (\Exception $e) {
                $this->logger->error('Failed to load form definition from "{identifier}": {message}', [
                    'identifier' => $persistenceIdentifier,
                    'message' => $e->getMessage(),
                ]);
                $success = false;
                continue;
            }

            // Check if this form was already migrated (by identifier)
            $existingUid = $this->formDefinitionRepository->findUidByFormIdentifier($formData->identifier);
            if ($existingUid !== null) {
                $this->logger->info('Form "{identifier}" already exists in database, skipping.', [
                    'identifier' => $formData->identifier,
                ]);
                // Still track mapping for reference updates
                $migrationMap[$persistenceIdentifier] = $existingUid;
                $migratedFiles[] = $persistenceIdentifier;
                continue;
            }

            // Write to database using raw insert (no DataHandler required,
            // so this works in Install Tool context without a backend user)
            try {
                $newUid = $this->formDefinitionRepository->addRaw(0, $formData);
            } catch (\Exception $e) {
                $this->logger->error('Database insert failed for form "{identifier}": {message}', [
                    'identifier' => $formData->identifier,
                    'message' => $e->getMessage(),
                ]);
                $newUid = null;
            }

            if ($newUid === null) {
                $this->logger->error('Failed to insert form "{identifier}" into database.', [
                    'identifier' => $formData->identifier,
                    'persistenceIdentifier' => $persistenceIdentifier,
                ]);
                $success = false;
                continue;
            }

            $migrationMap[$persistenceIdentifier] = $newUid;
            $migratedFiles[] = $persistenceIdentifier;

            $this->logger->info('Migrated form "{identifier}" from "{file}" to database UID {uid}.', [
                'identifier' => $formData->identifier,
                'file' => $persistenceIdentifier,
                'uid' => $newUid,
            ]);
        }

        // Update tt_content FlexForm references
        if ($migrationMap !== []) {
            // Convert int UIDs to string for the service (persistenceIdentifier values are always strings)
            $stringMap = array_map(strval(...), $migrationMap);
            $referencesUpdated = $this->formTransferService->updateContentElementReferences($stringMap);
            $this->logger->info('Updated {count} content element reference(s).', [
                'count' => $referencesUpdated,
            ]);
        }

        // Delete original YAML files only when all forms were migrated successfully.
        // If any migration failed, keep all files to allow re-running the wizard.
        if ($success && $migratedFiles !== []) {
            $deletedCount = $this->deleteOriginalFiles($migratedFiles);
            $this->logger->info('Deleted {count} of {total} original YAML file(s).', [
                'count' => $deletedCount,
                'total' => count($migratedFiles),
            ]);
        } elseif (!$success && $migratedFiles !== []) {
            $this->logger->warning(
                'Some forms could not be migrated. Original YAML files were kept to allow re-running the wizard.'
            );
        }

        return $success;
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * Find all YAML form definitions in configured file mounts.
     *
     * @return list<FormMetadata>
     */
    private function getFileBasedForms(): array
    {
        try {
            $results = [];
            foreach ($this->retrieveYamlFilesFromStorageFolders() as $file) {
                $formMetadata = $this->loadMetaData($file);

                if (!$this->looksLikeAFormDefinition($formMetadata)) {
                    continue;
                }

                if (!$this->hasValidFileExtension($file->getCombinedIdentifier())) {
                    continue;
                }
                $results[] = $formMetadata;
            }
            return $results;
        } catch (\Exception $e) {
            $this->logger->warning('Could not list file-based forms: {message}', [
                'message' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Delete original YAML files from file storage after successful migration.
     *
     * Files that cannot be deleted (e.g. due to permissions) are logged but
     * do not cause the overall migration to fail — the database records are
     * already the authoritative source at this point.
     *
     * @param list<string> $persistenceIdentifiers Combined identifiers (e.g. "1:/form_definitions/contact.form.yaml")
     * @return int Number of successfully deleted files
     */
    private function deleteOriginalFiles(array $persistenceIdentifiers): int
    {
        $deletedCount = 0;

        foreach ($persistenceIdentifiers as $persistenceIdentifier) {
            try {
                $this->deleteForm($persistenceIdentifier);
                $deletedCount++;

                $this->logger->info('Deleted original YAML file "{identifier}".', [
                    'identifier' => $persistenceIdentifier,
                ]);
            } catch (\Exception $e) {
                $this->logger->warning('Could not delete original YAML file "{identifier}": {message}', [
                    'identifier' => $persistenceIdentifier,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $deletedCount;
    }

    private function readForm(string $identifier): FormData
    {
        $file = $this->retrieveFileByPersistenceIdentifier($identifier);
        $formDefinition = $this->yamlSource->load([$file]);
        $this->generateErrorsIfFormDefinitionIsValidButHasInvalidFileExtension($formDefinition, $identifier);
        return FormData::fromArray($formDefinition);
    }

    private function deleteForm(string $identifier): void
    {
        if (!$this->hasValidFileExtension($identifier)) {
            throw new PersistenceManagerException(sprintf('The file "%s" could not be removed.', $identifier), 1472239534);
        }
        if (!$this->exists($identifier)) {
            throw new PersistenceManagerException(sprintf('The file "%s" could not be removed.', $identifier), 1764879545);
        }
        [$storageUid, $fileIdentifier] = explode(':', $identifier, 2);
        $storage = $this->getStorageByUid((int)$storageUid);
        $file = $storage->getFile($fileIdentifier);
        if (!$storage->checkFileActionPermission('delete', $file)) {
            throw new PersistenceManagerException(sprintf('No delete access to file "%s".', $identifier), 1472239516);
        }
        $storage->deleteFile($file);
    }

    /**
     * @throws PersistenceManagerException
     * @throws NoSuchFileException
     */
    private function retrieveFileByPersistenceIdentifier(string $identifier): File
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
    private function ensureValidPersistenceIdentifier(string $identifier): void
    {
        if (pathinfo($identifier, PATHINFO_EXTENSION) !== 'yaml') {
            throw new PersistenceManagerException(sprintf('The file "%s" could not be loaded.', $identifier), 1477679819);
        }
    }

    /**
     * @throws PersistenceManagerException
     */
    private function generateErrorsIfFormDefinitionIsValidButHasInvalidFileExtension(array $formDefinition, string $identifier): void
    {
        if ($this->looksLikeAFormDefinitionArray($formDefinition) && !$this->hasValidFileExtension($identifier)) {
            throw new PersistenceManagerException(sprintf('Form definition "%s" does not end with ".form.yaml".', $identifier), 1780660703);
        }
    }

    /**
     * Check if array looks like a form definition
     */
    private function looksLikeAFormDefinitionArray(array $data): bool
    {
        return !empty($data['identifier']) && trim($data['type'] ?? '') === 'Form';
    }

    private function hasValidFileExtension(string $identifier): bool
    {
        return str_ends_with($identifier, FormPersistenceManagerInterface::FORM_DEFINITION_FILE_EXTENSION);
    }

    private function exists(string $identifier): bool
    {
        $exists = false;
        if ($this->hasValidFileExtension($identifier) && $this->pathIsIntendedAsFileMountPath($identifier)) {
            [$storageUid, $fileIdentifier] = explode(':', $identifier, 2);
            $storage = $this->getStorageByUid((int)$storageUid);
            $exists = $storage->hasFile($fileIdentifier);
        }
        return $exists;
    }

    /**
     * Returns a ResourceStorage for a given uid
     *
     * @throws PersistenceManagerException
     */
    private function getStorageByUid(int $storageUid): ResourceStorage
    {
        $storage = $this->storageRepository->findByUid($storageUid);
        if (!$storage?->isBrowsable()) {
            throw new PersistenceManagerException(sprintf('Could not access storage with uid "%d".', $storageUid), 1471630581);
        }
        return $storage;
    }

    private function pathIsIntendedAsFileMountPath(string $path): bool
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
     * Return a list of all accessible file mountpoints for the
     * current backend user.
     *
     * Only registered mount points from
     * persistenceManager.allowedFileMounts
     * are listed.
     *
     * @return Folder[]
     */
    private function getAccessibleFormStorageFolders(): array
    {
        $storageFolders = [];
        $allowedFileMounts = $this->storageConfiguration->getPersistenceManagerSettings()['allowedFileMounts'];

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

    private function loadMetaData(File $file): FormMetadata
    {
        $persistenceIdentifier = $file->getCombinedIdentifier();
        $rawYamlContent = $file->getContents();

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

    private function extractMetaDataFromCouldBeFormDefinition(string $maybeRawFormDefinition): array
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

    private function looksLikeAFormDefinition(FormMetadata $formMetadata): bool
    {
        return !empty($formMetadata->identifier) && trim($formMetadata->type) === 'Form';
    }

}
