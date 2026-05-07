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

namespace TYPO3\CMS\Form\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Form\Service\FormTransferService;

/**
 * CLI command to transfer form definitions between storage backends.
 *
 * Usage examples:
 *   # Transfer all forms from extension storage to database
 *   bin/typo3 form:definition:transfer --source=extension --target=database
 *
 *   # Transfer a specific form
 *   bin/typo3 form:definition:transfer --source=extension --target=database --form-identifier=contact
 *
 *   # Move forms (transfer + delete source)
 *   bin/typo3 form:definition:transfer --source=filemount --target=database --move
 *
 *   # Dry-run: preview what would be transferred
 *   bin/typo3 form:definition:transfer --source=extension --target=database --dry-run
 *
 *   # Transfer to a specific target location (PID for database)
 *   bin/typo3 form:definition:transfer --source=extension --target=database --target-location=42
 */
#[AsCommand('form:definition:transfer', 'Transfer form definitions between storage backends')]
class TransferFormDefinitionCommand extends Command
{
    public function __construct(
        private readonly FormTransferService $formTransferService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp(
                'Transfers form definitions from one storage backend to another.' . LF . LF
                . 'Available storage types depend on the installed adapters. Core provides:' . LF
                . '  - database:   Database storage (default target)' . LF
                . '  - extension:  Extension paths (EXT:...)' . LF
                . '  - filemount:  File mount storage (deprecated since v14.2)' . LF . LF
                . 'Target location (--target-location / -l) per storage type:' . LF
                . '  - database:   Always "0" (fixed; forms are stored at the root level).' . LF
                . '  - extension:  An EXT: path configured in "persistenceManager.allowedExtensionPaths",' . LF
                . '                with "persistenceManager.allowSaveToExtensionPaths: true" set.' . LF
                . '                e.g. --target-location="EXT:my_extension/Resources/Private/Forms/"' . LF . LF
                . 'Use --dry-run to preview which forms would be transferred.' . LF
                . 'Use --move to delete the source form after successful transfer.' . LF . LF
                . 'After a successful transfer, content element references in "tt_content" are automatically' . LF
                . 'updated to point to the new storage location. No other tables are updated.'
            )
            ->addOption(
                'source',
                null,
                InputOption::VALUE_REQUIRED,
                'Source storage type identifier (e.g., "extension", "filemount", "database").',
            )
            ->addOption(
                'target',
                null,
                InputOption::VALUE_REQUIRED,
                'Target storage type identifier (e.g., "database", "extension").',
            )
            ->addOption(
                'target-location',
                'l',
                InputOption::VALUE_REQUIRED,
                'Target storage location. For "database": always "0". For "extension": EXT: path from allowedExtensionPaths.',
                '0',
            )
            ->addOption(
                'form-identifier',
                'f',
                InputOption::VALUE_REQUIRED,
                'Transfer only the form with this identifier. If omitted, all forms from the source are transferred.',
            )
            ->addOption(
                'move',
                'm',
                InputOption::VALUE_NONE,
                'Delete the source form after successful transfer (move operation).',
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Only list forms that would be transferred without making changes.',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Bootstrap::initializeBackendAuthentication();

        // @todo: ConfigurationManager triggered by PersistenceConfigurationService needs a Request
        $request = (new ServerRequest('https://localhost/', 'GET'));
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
                           ->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $io = new SymfonyStyle($input, $output);
        $sourceType = $input->getOption('source');
        $targetType = $input->getOption('target');
        $targetLocation = $input->getOption('target-location');
        $formIdentifier = $input->getOption('form-identifier');
        $isMove = (bool)$input->getOption('move');
        $isDryRun = (bool)$input->getOption('dry-run');

        if ($sourceType === null || $targetType === null) {
            $io->error('Both --source and --target options are required.');
            $io->note(sprintf(
                'Available storage types: %s',
                implode(', ', $this->formTransferService->getAvailableStorageTypes()),
            ));
            return Command::FAILURE;
        }

        if (!$this->formTransferService->hasStorageType($sourceType)) {
            $io->error(sprintf(
                'Unknown source storage type "%s". Available types: %s',
                $sourceType,
                implode(', ', $this->formTransferService->getAvailableStorageTypes()),
            ));
            return Command::FAILURE;
        }

        if (!$this->formTransferService->hasStorageType($targetType)) {
            $io->error(sprintf(
                'Unknown target storage type "%s". Available types: %s',
                $targetType,
                implode(', ', $this->formTransferService->getAvailableStorageTypes()),
            ));
            return Command::FAILURE;
        }

        if ($sourceType === $targetType && $formIdentifier === null) {
            $io->error('Source and target storage types are identical. Use --form-identifier to transfer a specific form, or choose different storage types.');
            return Command::FAILURE;
        }

        $targetAdapter = $this->formTransferService->getAdapter($targetType);
        if (!$targetAdapter->isAllowedStorageLocation($targetLocation)) {
            $hint = match ($targetType) {
                'database' => 'For database storage the only valid location is "0" (default). The option can be omitted.',
                'extension' => 'For extension storage, provide an EXT: path that is registered in "persistenceManager.allowedExtensionPaths"' . LF
                    . 'and ensure "persistenceManager.allowSaveToExtensionPaths: true" is set in your form YAML setup.' . LF
                    . 'Example: --target-location="EXT:my_extension/Resources/Private/Forms/"',
                default => 'Check the storage adapter documentation for valid location formats.',
            };
            $io->error(sprintf(
                'The target location "%s" is not valid for the "%s" storage adapter.' . LF . '%s',
                $targetLocation,
                $targetType,
                $hint,
            ));
            return Command::FAILURE;
        }

        $sourceForms = $this->formTransferService->listSourceForms($sourceType, $formIdentifier);

        if ($sourceForms === []) {
            $message = $formIdentifier !== null
                ? sprintf('No form with identifier "%s" found in "%s" storage.', $formIdentifier, $sourceType)
                : sprintf('No forms found in "%s" storage.', $sourceType);
            $io->warning($message);
            return Command::SUCCESS;
        }

        $operation = $isMove ? 'move' : 'transfer';
        $io->section(sprintf(
            'Found %d form(s) to %s from "%s" to "%s"',
            count($sourceForms),
            $operation,
            $sourceType,
            $targetType,
        ));

        if ($isDryRun) {
            $rows = [];
            foreach ($sourceForms as $form) {
                $rows[] = [
                    $form->identifier,
                    $form->name,
                    $form->persistenceIdentifier ?? '-',
                    '<comment>would ' . $operation . '</comment>',
                ];
            }
            $io->table(['Identifier', 'Name', 'Source', 'Status'], $rows);
            $io->note('Dry-run mode: no forms were transferred.');
            return Command::SUCCESS;
        }

        $transferred = 0;
        $failed = 0;
        $results = [];
        $migrationMap = [];

        foreach ($sourceForms as $form) {
            try {
                $result = $this->formTransferService->transferForm(
                    $form,
                    $sourceType,
                    $targetType,
                    $targetLocation,
                    $isMove,
                );

                $status = '<info>success</info>';
                if ($isMove && $result->sourceDeleted) {
                    $status = '<info>moved</info>';
                } elseif ($isMove && $result->deletionError !== null) {
                    $status = '<info>transferred</info>, <comment>source deletion failed: ' . $result->deletionError . '</comment>';
                }

                $results[] = [
                    $result->formIdentifier,
                    $result->formName,
                    $result->sourceIdentifier,
                    $result->targetIdentifier,
                    $status,
                ];
                $migrationMap[$result->sourceIdentifier] = $result->targetIdentifier;
                $transferred++;
            } catch (\Exception $e) {
                $results[] = [
                    $form->identifier,
                    $form->name,
                    $form->persistenceIdentifier ?? '-',
                    '-',
                    '<error>' . $e->getMessage() . '</error>',
                ];
                $failed++;
                if ($output->isVerbose()) {
                    $io->error(sprintf('Failed to transfer "%s": %s', $form->identifier, $e->getMessage()));
                }
            }
        }

        $io->table(['Identifier', 'Name', 'Source', 'Target', 'Status'], $results);

        if ($migrationMap !== []) {
            $referencesUpdated = $this->formTransferService->updateContentElementReferences($migrationMap);
            if ($referencesUpdated > 0) {
                $io->note(sprintf('Updated %d content element reference(s).', $referencesUpdated));
            }
        }

        if ($transferred > 0) {
            $verb = $isMove ? 'moved' : 'transferred';
            $io->success(sprintf('Successfully %s %d form(s) from "%s" to "%s".', $verb, $transferred, $sourceType, $targetType));
        }
        if ($failed > 0) {
            $io->warning(sprintf('Failed to transfer %d form(s).', $failed));
        }

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
