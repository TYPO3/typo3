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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Backend\Command\ProgressListener\ReferenceIndexProgressListener;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\File\BasicFileUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Finds files within uploads/ which are used multiple times by relations within the database
 */
class FilesWithMultipleReferencesCommand extends Command
{
    /**
     * @var ConnectionPool
     */
    private $connectionPool;

    /**
     * @var BasicFileUtility
     */
    private $basicFileUtility;

    public function __construct(ConnectionPool $connectionPool, BasicFileUtility $basicFileUtility)
    {
        $this->connectionPool = $connectionPool;
        $this->basicFileUtility = $basicFileUtility;
        parent::__construct();
    }

    /**
     * Configure the command by defining the name, options and arguments
     */
    public function configure()
    {
        $this
            ->setHelp('
Assumptions:
- a perfect integrity of the reference index table (always update the reference index table before using this tool!)
- files found in deleted records are included (otherwise you would see a false list of lost files)

Files attached to records in TYPO3 using a "group" type configuration in TCA or FlexForm DataStructure are managed exclusively by the system and there must always exist a 1-1 reference between the file and the reference in the record.
This tool will expose when such files are referenced from multiple locations which is considered an integrity error.
If a multi-reference is found it was typically created because the record was copied or modified outside of DataHandler which will otherwise maintain the relations correctly.
Multi-references should be resolved to 1-1 references as soon as possible. The danger of keeping multi-references is that if the file is removed from one of the referring records it will actually be deleted in the file system, leaving missing files for the remaining referrers!

If the option "--dry-run" is not set, the files that are referenced multiple times are copied with a new name
and the references are updated accordingly.
Warning: First, make sure those files are not used somewhere TYPO3 does not know about!

If you want to get more detailed information, use the --verbose option.')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'If this option is set, the files will not actually be deleted, but just the output which files would be deleted are shown'
            )
            ->addOption(
                'update-refindex',
                null,
                InputOption::VALUE_NONE,
                'Setting this option automatically updates the reference index and does not ask on command line. Alternatively, use -n to avoid the interactive mode'
            );
    }

    /**
     * Executes the command to
     * - optionally update the reference index (to have clean data)
     * - find files within the reference index which are referenced more than once
     * - copy these files if --dry-run is not set and update the references accordingly
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Make sure the _cli_ user is loaded
        Bootstrap::initializeBackendAuthentication();

        $io = new SymfonyStyle($input, $output);
        $io->title($this->getDescription());

        $dryRun = $input->hasOption('dry-run') && $input->getOption('dry-run') != false ? true : false;

        $this->updateReferenceIndex($input, $io);

        // Find files which are referenced multiple times
        $doubleFiles = $this->findMultipleReferencedFiles();

        if (count($doubleFiles)) {
            if (!$io->isQuiet()) {
                $io->note('Found ' . count($doubleFiles) . ' files that are referenced more than once.');
                if ($io->isVerbose()) {
                    $io->listing($doubleFiles);
                }
            }

            $this->copyMultipleReferencedFiles($doubleFiles, $dryRun, $io);
            $io->success('Cleaned up ' . count($doubleFiles) . ' files which have been referenced multiple times.');
        } else {
            $io->success('Nothing to do, no files found which are referenced more than once.');
        }
        return 0;
    }

    /**
     * Function to update the reference index
     * - if the option --update-refindex is set, do it
     * - otherwise, if in interactive mode (not having -n set), ask the user
     * - otherwise assume everything is fine
     *
     * @param InputInterface $input holds information about entered parameters
     * @param SymfonyStyle $io necessary for outputting information
     */
    protected function updateReferenceIndex(InputInterface $input, SymfonyStyle $io)
    {
        // Check for reference index to update
        $io->note('Finding files referenced multiple times in records managed by TYPO3 requires a clean reference index (sys_refindex)');
        $updateReferenceIndex = false;
        if ($input->hasOption('update-refindex') && $input->getOption('update-refindex')) {
            $updateReferenceIndex = true;
        } elseif ($input->isInteractive()) {
            $updateReferenceIndex = $io->confirm('Should the reference index be updated right now?', false);
        }

        // Update the reference index
        if ($updateReferenceIndex) {
            $progressListener = GeneralUtility::makeInstance(ReferenceIndexProgressListener::class);
            $progressListener->initialize($io);

            $referenceIndex = GeneralUtility::makeInstance(ReferenceIndex::class);
            $io->section('Reference Index is now being updated');
            $referenceIndex->updateIndex(false, $progressListener);
        } else {
            $io->writeln('Reference index is assumed to be up to date, continuing.');
        }
    }

    /**
     * Find files which are referenced multiple times in uploads/ folder
     *
     * @return array an array of files and their reference hashes that are referenced multiple times
     */
    protected function findMultipleReferencedFiles(): array
    {
        $multipleReferencesList = [];

        // Select all files in the reference table not found by a soft reference parser (thus TCA configured)
        $queryBuilder = $this->connectionPool
            ->getQueryBuilderForTable('sys_refindex');

        $result = $queryBuilder
            ->select('*')
            ->from('sys_refindex')
            ->where(
                $queryBuilder->expr()->eq('ref_table', $queryBuilder->createNamedParameter('_FILE', \PDO::PARAM_STR)),
                $queryBuilder->expr()->eq('softref_key', $queryBuilder->createNamedParameter('', \PDO::PARAM_STR))
            )
            ->executeQuery();

        // Traverse the files and put into a large table
        $allReferencesToFiles = [];
        while ($record = $result->fetchAssociative()) {
            // Compile info string for location of reference
            $infoString = $this->formatReferenceIndexEntryToString($record);
            $hash = $record['hash'];
            $fileName = $record['ref_string'];
            // Add entry if file has multiple references pointing to it
            if (isset($allReferencesToFiles[$fileName])) {
                if (!is_array($multipleReferencesList[$fileName])) {
                    $multipleReferencesList[$fileName] = [];
                    $multipleReferencesList[$fileName][$allReferencesToFiles[$fileName]['hash']] = $allReferencesToFiles[$fileName]['infoString'];
                }
                $multipleReferencesList[$fileName][$hash] = $infoString;
            } else {
                $allReferencesToFiles[$fileName] = [
                    'infoString' => $infoString,
                    'hash' => $hash,
                ];
            }
        }

        return ArrayUtility::sortByKeyRecursive($multipleReferencesList);
    }

    /**
     * Copies files which are referenced multiple times and updates the reference index so they are only used once
     *
     * @param array $multipleReferencesToFiles Contains files which have been referenced multiple times
     * @param bool $dryRun if set, the info is just displayed, but no files are copied nor reference index updated
     * @param SymfonyStyle $io the IO object for output
     */
    protected function copyMultipleReferencedFiles(array $multipleReferencesToFiles, bool $dryRun, SymfonyStyle $io)
    {
        $referenceIndex = GeneralUtility::makeInstance(ReferenceIndex::class);

        foreach ($multipleReferencesToFiles as $fileName => $usages) {
            $absoluteFileName = GeneralUtility::getFileAbsFileName($fileName);
            if ($absoluteFileName && @is_file($absoluteFileName)) {
                if ($io->isVeryVerbose()) {
                    $io->writeln('Processing file "' . $absoluteFileName . '"');
                }
                $counter = 0;
                foreach ($usages as $hash => $recReference) {
                    if ($counter++ === 0) {
                        $io->writeln('Keeping "' . $fileName . '" for record "' . $recReference . '"');
                    } else {
                        // Create unique name for file
                        $newName = (string)$this->basicFileUtility->getUniqueName(PathUtility::basename($fileName), PathUtility::dirname($absoluteFileName));
                        $io->writeln('Copying "' . $fileName . '" to "' . PathUtility::stripPathSitePrefix($newName) . '" for record "' . $recReference . '"');
                        if (!$dryRun) {
                            GeneralUtility::upload_copy_move($absoluteFileName, $newName);
                            clearstatcache();
                            if (@is_file($newName)) {
                                $error = $referenceIndex->setReferenceValue($hash, PathUtility::basename($newName));
                                if ($error) {
                                    $io->error('ReferenceIndex::setReferenceValue() reported "' . $error . '"');
                                }
                            } else {
                                $io->error('File "' . $newName . '" could not be created.');
                            }
                        }
                    }
                }
            } else {
                $io->error('File "' . $absoluteFileName . '" was not found.');
            }
        }
    }

    /**
     * Formats a sys_refindex entry to something readable
     *
     * @param array $record
     * @return string
     */
    protected function formatReferenceIndexEntryToString(array $record): string
    {
        return $record['tablename']
            . ':' . $record['recuid']
            . ':' . $record['field']
            . ($record['flexpointer'] ? ':' . $record['flexpointer'] : '')
            . ($record['softref_key'] ? ':' . $record['softref_key'] . ' (Soft Reference) ' : '');
    }
}
