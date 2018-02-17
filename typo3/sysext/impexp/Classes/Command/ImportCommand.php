<?php
namespace TYPO3\CMS\Impexp\Command;

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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fileName = $input->getArgument('file');
        $fileName = GeneralUtility::getFileAbsFileName($fileName);
        if (empty($fileName) || !file_exists($fileName)) {
            throw new Exception\InvalidFileException('The given filename "' . ($fileName ?? $input->getArgument('file')) . '" could not be found', 1484483040);
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
        // Enables logging of database actions
        $import->enableLogging = (bool)($input->hasOption('enableLog') && $input->getOption('enableLog'));

        if (!$import->loadFile($fileName, true)) {
            $io->error($import->errorLog);
            throw new Exception\LoadingFileFailedException('Loading of the import file failed.', 1484484619);
        }

        $messages = $import->checkImportPrerequisites();
        if (!empty($messages)) {
            $io->error($messages);
            throw new Exception\PrerequisitesNotMetException('Prerequisites for file import are not met.', 1484484612);
        }

        $import->importData($pageId);
        if (!empty($import->errorLog)) {
            $io->error($import->errorLog);
            throw new Exception\ImportFailedException('The import has failed.', 1484484613);
        }

        $io->success('Imported ' . $input->getArgument('file') . ' to page ' . $pageId . ' successfully');
    }
}
