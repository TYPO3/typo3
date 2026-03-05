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
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Form\Service\CleanupFormUploadsService;

/**
 * CLI command to clean up old file upload folders created by the TYPO3 Form Framework.
 *
 * When users upload files via ext:form, the files are stored in `form_<hash>` sub-folders.
 * Over time these folders accumulate — both from completed and incomplete form submissions.
 * Since files are not moved upon submission, there is no way to distinguish between
 * the two. This command removes form upload folders older than a configurable retention period.
 *
 * Usage examples:
 *   # Dry-run: list form upload folders older than 2 weeks (default)
 *   bin/typo3 form:cleanup:uploads 1:/user_upload/ --dry-run
 *
 *   # Delete folders older than 48 hours in specific upload folders
 *   bin/typo3 form:cleanup:uploads 1:/user_upload/ 2:/custom_uploads/ --retention-period=48
 *
 *   # Force deletion without confirmation (e.g. for scheduler)
 *   bin/typo3 form:cleanup:uploads 1:/user_upload/ --force
 */
#[AsCommand('form:cleanup:uploads', 'Remove old form file upload folders based on retention period.')]
class CleanupFormUploadsCommand extends Command
{
    private const DEFAULT_RETENTION_PERIOD_HOURS = 336;

    public function __construct(
        private readonly CleanupFormUploadsService $cleanupService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp(
                'Removes old form upload folders (form_<hash>) that were created by file uploads ' .
                'in ext:form.' . LF . LF .
                'Since uploaded files are not moved when a form is submitted, the command cannot ' . LF .
                'distinguish between folders from completed and abandoned submissions. It uses ' . LF .
                'the folder modification time and a configurable retention period to decide ' . LF .
                'which folders to remove.' . LF . LF .
                'You must specify at least one upload folder to scan. Each form element can configure ' . LF .
                'a different saveToFileMount; pass all relevant folders as arguments.' . LF . LF .
                'Use --verbose for detailed output about each folder found.'
            )
            ->addArgument(
                'upload-folder',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'Combined folder identifier(s) to scan (e.g. "1:/user_upload/").',
            )
            ->addOption(
                'retention-period',
                'r',
                InputOption::VALUE_REQUIRED,
                'Minimum age in hours before a form upload folder is considered for removal.',
                (string)self::DEFAULT_RETENTION_PERIOD_HOURS,
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Only list expired folders without deleting them.',
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Skip the confirmation question. Automatically set when using --no-interaction.',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $retentionHours = (int)$input->getOption('retention-period');
        if ($retentionHours < 1) {
            $io->error('The retention period must be at least 1 hour.');
            return Command::FAILURE;
        }

        $maximumAgeSeconds = $retentionHours * 3600;
        /** @var list<string> $uploadFolders */
        $uploadFolders = $input->getArgument('upload-folder');
        $isDryRun = (bool)$input->getOption('dry-run');

        $io->section(sprintf(
            'Scanning %s for form upload folders older than %d hour(s)',
            'folders: ' . implode(', ', $uploadFolders),
            $retentionHours,
        ));

        $expiredFolders = $this->cleanupService->getExpiredFolders($maximumAgeSeconds, $uploadFolders);

        if ($expiredFolders === []) {
            $io->success('No expired form upload folders found. Nothing to do.');
            return Command::SUCCESS;
        }

        if ($output->isVerbose()) {
            foreach ($expiredFolders as $folder) {
                $age = time() - $folder->getModificationTime();
                $ageHours = round($age / 3600, 1);
                $fileCount = $folder->getFileCount();
                $io->writeln(sprintf(
                    '  [FOLDER] %s (age: %s hours, files: %d)',
                    $folder->getCombinedIdentifier(),
                    $ageHours,
                    $fileCount,
                ));
            }
        }

        $totalFiles = 0;
        foreach ($expiredFolders as $folder) {
            $totalFiles += $folder->getFileCount();
        }

        $io->writeln(sprintf(
            'Found <options=bold>%d folder(s)</> containing <options=bold>%d file(s)</>.',
            count($expiredFolders),
            $totalFiles,
        ));

        if ($isDryRun) {
            $io->note('Dry-run mode: no folders were deleted.');
            return Command::SUCCESS;
        }

        // Do not ask for confirmation when running the command in EXT:scheduler
        if (!$input->isInteractive()) {
            $input->setOption('force', true);
        }

        if (!$input->getOption('force')) {
            /** @var QuestionHelper $questionHelper */
            $questionHelper = $this->getHelper('question');
            $question = new ConfirmationQuestion(
                sprintf(
                    'Are you sure you want to delete %d folder(s) with %d file(s)? [default: no] ',
                    count($expiredFolders),
                    $totalFiles,
                ),
                false,
            );
            if (!$questionHelper->ask($input, $output, $question)) {
                $io->note('Aborted by user.');
                return Command::SUCCESS;
            }
        }

        $result = $this->cleanupService->deleteFolders($expiredFolders);

        if ($result['deleted'] > 0) {
            $io->success(sprintf('Successfully deleted %d form upload folder(s).', $result['deleted']));
        }

        if ($result['failed'] > 0) {
            $io->warning(sprintf('Failed to delete %d folder(s).', $result['failed']));
            if ($output->isVerbose()) {
                foreach ($result['errors'] as $error) {
                    $io->writeln(sprintf('  [ERROR] %s: %s', $error['folder'], $error['message']));
                }
            }
        }

        return $result['failed'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
