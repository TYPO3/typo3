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

namespace TYPO3\CMS\Lowlevel\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Lowlevel\Service\CleanUpLocalProcessedFilesService;

class CleanUpLocalProcessedFilesCommand extends Command
{
    public function __construct(
        private readonly CleanUpLocalProcessedFilesService $cleanProcessedFilesService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription(
                'Deletes local processed files from local storage that are no longer referenced and ' .
                'deletes references to processed files that do no longer exist'
            )
            ->setHelp('If you want to get more detailed information, use the --verbose option.')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'If set, the records and files which would be deleted are displayed.'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force cleanup. When set the confirmation question will be skipped. When using --no-interaction, --force will be set automatically.'
            );
    }

    /**
     * Executes the command to find processed files from local storages that
     * are no longer referenced and deletes references to files that no longer exist
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $files = $this->cleanProcessedFilesService->getFilesToClean();
        $records = $this->cleanProcessedFilesService->getRecordsToClean();

        if ($output->isVerbose()) {
            foreach ($records as $record) {
                $output->writeln('[RECORD] Would delete ' . $record['identifier'] . ' UID:' . $record['uid']);
            }

            /** @var \SplFileInfo $file */
            foreach ($files as $file) {
                $path = PathUtility::stripPathSitePrefix($file->getRealPath());
                $output->writeln('[FILE] Would delete ' . $path);
            }
        }

        if ($files === [] && $records === []) {
            $output->writeln('<fg=green>✓</> No processed files or processed records found. Nothing to be done');

            return Command::SUCCESS;
        }

        $output->writeln('Found <options=bold,underscore>' . count($files) . ' files</> and <options=bold,underscore>' . (count($records) + count($files)) . ' processed records</>');

        if ($input->getOption('dry-run')) {
            return Command::SUCCESS;
        }

        // Do not ask for confirmation when running the command in EXT:scheduler
        if (!$input->isInteractive()) {
            $input->setOption('force', true);
        }

        if (!$input->getOption('force')) {
            /** @var QuestionHelper $questionHelper */
            $questionHelper = $this->getHelper('question');
            $fileDeleteQuestion = new ConfirmationQuestion(
                'Are you sure you want to delete these processed files and records [default: no] ? ',
                false
            );
            $answerDeleteFiles = $questionHelper->ask($input, $output, $fileDeleteQuestion);
        }

        if (($answerDeleteFiles ?? false) || $input->getOption('force')) {
            [$success, $error] = $this->deleteFile($input, $output, $files);
            // Reload the list auf records to get the files deleted using deleteFile() as well
            $recordsIncludingDeleted = $this->cleanProcessedFilesService->getRecordsToClean();
            $deletedRecordsCount = $this->cleanProcessedFilesService->deleteRecord(array_column($recordsIncludingDeleted, 'uid'));

            $failedRecordCount = count($records) - $deletedRecordsCount;
            $failedRecords = '';
            if ($failedRecordCount > 0) {
                $failedRecords = 'Failed to delete <fg=red>' . $failedRecordCount . '</> records.';
            }

            $failedFiles = '';
            if (count($error) > 0) {
                $failedFiles = 'Failed to delete <fg=red>' . count($error) . '</> files.';
            }

            $output->writeln('');

            if (count($records) > 0) {
                $output->writeln('Deleted <fg=green>' . $deletedRecordsCount . '</> processed records. ' . $failedRecords);
            }

            if (count($files) > 0) {
                $output->writeln('Deleted <fg=green>' . count($success) . '</> processed files. ' . $failedFiles);
            }
        }

        return Command::SUCCESS;
    }

    protected function deleteFile(InputInterface $input, OutputInterface $output, array $files): array
    {
        $isVerbose = $output->isVerbose();
        if (!$isVerbose) {
            $io = new SymfonyStyle($input, $output);
            $progressBar = $io->createProgressBar(count($files));
        }
        $error = [];
        $success = [];

        foreach ($files as $file) {
            $path = PathUtility::stripPathSitePrefix($file->getRealPath());
            if (unlink($file->getRealPath()) === false) {
                $error[] = $file;
                $isVerbose ? $output->writeln('[FILE] Failed to delete ' . $path) : $progressBar->advance();
            } else {
                $success[] = $file;
                $isVerbose ? $output->writeln('[FILE] Successfully deleted ' . $path) : $progressBar->advance();
            }
        }

        return [$success, $error];
    }
}
