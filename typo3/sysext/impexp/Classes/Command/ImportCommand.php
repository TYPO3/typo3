<?php

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

namespace TYPO3\CMS\Impexp\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Impexp\Command\Exception\ImportFailedException;
use TYPO3\CMS\Impexp\Command\Exception\InvalidFileException;
use TYPO3\CMS\Impexp\Command\Exception\LoadingFileFailedException;
use TYPO3\CMS\Impexp\Command\Exception\PrerequisitesNotMetException;
use TYPO3\CMS\Impexp\Import;

/**
 * Command for importing T3D/XML data files
 */
class ImportCommand extends Command
{
    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure()
    {
        $this
            ->setDescription('Imports a T3D / XML file with content into a page tree')
            ->addArgument(
                'file',
                InputArgument::REQUIRED,
                'The path and filename to import (.t3d or .xml)'
            )
            ->addArgument(
                'pageId',
                InputArgument::OPTIONAL,
                'The page ID to start from. If empty, the root level (= pageId=0) is used.'
            )->addOption(
                'updateRecords',
                null,
                InputOption::VALUE_NONE,
                'If set, existing records with the same UID will be updated instead of inserted'
            )->addOption(
                'ignorePid',
                null,
                InputOption::VALUE_NONE,
                'If set, page IDs of updated records are not corrected (only works in conjunction with the updateRecords option)'
            )->addOption(
                'forceUid',
                null,
                InputOption::VALUE_NONE,
                'If set, UIDs from file will be forced.'
            )->addOption(
                'enableLog',
                null,
                InputOption::VALUE_NONE,
                'If set, all database actions are logged'
            );
    }

    /**
     * Executes the command for importing a t3d/xml file into the TYPO3 system
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fileName = (string)$input->getArgument('file');
        $fileName = GeneralUtility::getFileAbsFileName($fileName);
        if ($fileName === '' || !file_exists($fileName)) {
            throw new InvalidFileException('The given filename "' . $fileName . '" could not be found', 1484483040);
        }

        $io = new SymfonyStyle($input, $output);

        // Ensure the _cli_ user is authenticated
        Bootstrap::initializeBackendAuthentication();

        $pageId = (int)$input->getArgument('pageId');

        $import = GeneralUtility::makeInstance(Import::class);
        $import->init();
        $import->update = (bool)($input->hasOption('updateRecords') && $input->getOption('updateRecords'));
        // Only used when $updateRecords is "true"
        $import->global_ignore_pid = (bool)($input->hasOption('ignorePid') && $input->getOption('ignorePid'));
        // Force using UIDs from File
        $import->force_all_UIDS = (bool)($input->hasOption('forceUid') && $input->getOption('forceUid'));
        // Enables logging of database actions
        $import->enableLogging = (bool)($input->hasOption('enableLog') && $input->getOption('enableLog'));

        if (!$import->loadFile($fileName, true)) {
            $io->error($import->errorLog);
            throw new LoadingFileFailedException('Loading of the import file failed.', 1484484619);
        }

        $messages = $import->checkImportPrerequisites();
        if (!empty($messages)) {
            $io->error($messages);
            throw new PrerequisitesNotMetException('Prerequisites for file import are not met.', 1484484612);
        }

        $import->importData($pageId);
        if (!empty($import->errorLog)) {
            $io->error($import->errorLog);
            throw new ImportFailedException('The import has failed.', 1484484613);
        }

        $io->success('Imported ' . $input->getArgument('file') . ' to page ' . $pageId . ' successfully');
        return 0;
    }
}
