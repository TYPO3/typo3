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
use TYPO3\CMS\Core\Attribute\UpgradeWizard;
use TYPO3\CMS\Core\Upgrades\ConfirmableInterface;
use TYPO3\CMS\Core\Upgrades\Confirmation;
use TYPO3\CMS\Core\Upgrades\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Core\Upgrades\UpgradeWizardInterface;
use TYPO3\CMS\Form\Domain\DTO\FormMetadata;
use TYPO3\CMS\Form\Domain\Repository\FormDefinitionRepository;
use TYPO3\CMS\Form\Service\FormTransferService;

/**
 * Migrate file-based form definitions (YAML) to database storage.
 *
 * This wizard uses the FormTransferService to read all form definitions from
 * the configured file mount storage, imports them into the form_definition
 * database table, and updates all tt_content references (FlexForm
 * persistenceIdentifier) to point to the new database records.
 *
 * After successful migration, the original YAML files are deleted from
 * file storage to avoid duplicates.
 *
 * @internal
 */
#[UpgradeWizard('formFileFormsToDatabaseMigration')]
final readonly class FileFormsToDatabaseUpgradeWizard implements UpgradeWizardInterface, ConfirmableInterface
{
    private const SOURCE_STORAGE_TYPE = 'filemount';

    public function __construct(
        private FormDefinitionRepository $formDefinitionRepository,
        private FormTransferService $formTransferService,
        private LoggerInterface $logger,
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

            // Read form definition from file storage via the transfer service
            try {
                $formData = $this->formTransferService->readForm(self::SOURCE_STORAGE_TYPE, $persistenceIdentifier);
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
     * Find all YAML form definitions in configured file mounts
     * using the FormTransferService.
     *
     * @return list<FormMetadata>
     */
    private function getFileBasedForms(): array
    {
        try {
            return $this->formTransferService->listSourceForms(self::SOURCE_STORAGE_TYPE);
        } catch (\Exception $e) {
            $this->logger->warning('Could not list file-based forms: {message}', [
                'message' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Delete original YAML files from file storage after successful migration
     * using the FormTransferService.
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
                $this->formTransferService->deleteForm(self::SOURCE_STORAGE_TYPE, $persistenceIdentifier);
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
}
