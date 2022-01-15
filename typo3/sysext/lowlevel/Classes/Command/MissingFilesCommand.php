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
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Finds files which are referenced by TYPO3 but not found in the file system
 */
class MissingFilesCommand extends Command
{
    /**
     * @var ConnectionPool
     */
    private $connectionPool;

    public function __construct(ConnectionPool $connectionPool)
    {
        $this->connectionPool = $connectionPool;
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
- relevant soft reference parsers applied everywhere file references are used inline

Files may be missing for these reasons (except software bugs):
- someone manually deleted the file inside fileadmin/ or another user maintained folder. If the reference was a soft reference (opposite to a DataHandler managed file relation from "group" type fields), technically it is not an error although it might be a mistake that someone did so.
- someone manually deleted the file inside the uploads/ folder (typically containing managed files) which is an error since no user interaction should take place there.

Manual repair suggestions (using --dry-run):
- Managed files: You might be able to locate the file and re-insert it in the correct location. However, no automatic fix can do that for you.
- Soft References: You should investigate each case and edit the content accordingly. A soft reference to a file could be in an HTML image tag (for example <img src="missing_file.jpg" />) and you would have to either remove the whole tag, change the filename or re-create the missing file.

If the option "--dry-run" is not set, all managed files (TCA/FlexForm attachments) will silently remove the reference
from the record since the file is missing. For this reason you might prefer a manual approach instead.
All soft references with missing files require manual fix if you consider it an error.

If you want to get more detailed information, use the --verbose option.')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'If this option is set, the references will not be removed, but just the output which files would be deleted are shown'
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
     * - find data in sys_refindex (softrefs and regular references) where the actual file does not exist (anymore)
     * - remove these files if --dry-run is not set (not possible for refindexes)
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

        // Update the reference index
        $this->updateReferenceIndex($input, $io);

        // Find missing soft references (cannot be updated / deleted)
        $missingSoftReferencedFiles = $this->findMissingSoftReferencedFiles();
        if (count($missingSoftReferencedFiles)) {
            $io->note('Found ' . count($missingSoftReferencedFiles) . ' soft-referenced files that need manual repair.');
            $io->listing($missingSoftReferencedFiles);
        }

        // Find missing references
        $missingReferencedFiles = $this->findMissingReferencedFiles();
        if (count($missingReferencedFiles)) {
            $io->note('Found ' . count($missingReferencedFiles) . ' references to non-existing files.');

            $this->removeReferencesToMissingFiles($missingReferencedFiles, $dryRun, $io);
            $io->success('All references were updated accordingly.');
        }

        if (!count($missingSoftReferencedFiles) && !count($missingReferencedFiles)) {
            $io->success('Nothing to do, no missing files found. Everything is in place.');
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
        $io->note('Finding missing files referenced by TYPO3 requires a clean reference index (sys_refindex)');
        if ($input->hasOption('update-refindex') && $input->getOption('update-refindex')) {
            $updateReferenceIndex = true;
        } elseif ($input->isInteractive()) {
            $updateReferenceIndex = $io->confirm('Should the reference index be updated right now?', false);
        } else {
            $updateReferenceIndex = false;
        }

        // Update the reference index
        if ($updateReferenceIndex) {
            $progressListener = GeneralUtility::makeInstance(ReferenceIndexProgressListener::class);
            $progressListener->initialize($io);
            $referenceIndex = GeneralUtility::makeInstance(ReferenceIndex::class);
            $referenceIndex->updateIndex(false, $progressListener);
        } else {
            $io->writeln('Reference index is assumed to be up to date, continuing.');
        }
    }

    /**
     * Find file references that points to non-existing files in system
     * Fix methods: API in \TYPO3\CMS\Core\Database\ReferenceIndex that allows to
     * change the value of a reference (or remove it)
     *
     * @return array an array of records within sys_refindex
     */
    protected function findMissingReferencedFiles(): array
    {
        $missingReferences = [];
        // Select all files in the reference table
        $queryBuilder = $this->connectionPool
            ->getQueryBuilderForTable('sys_refindex');

        $result = $queryBuilder
            ->select('*')
            ->from('sys_refindex')
            ->where(
                $queryBuilder->expr()->eq('ref_table', $queryBuilder->createNamedParameter('_FILE', \PDO::PARAM_STR)),
                $queryBuilder->expr()->isNull('softref_key')
            )
            ->executeQuery();

        // Traverse the references and check if the files exists
        while ($record = $result->fetchAssociative()) {
            $fileName = $this->getFileNameWithoutAnchor($record['ref_string']);
            if (empty($record['softref_key']) && !@is_file(Environment::getPublicPath() . '/' . $fileName)) {
                $missingReferences[$fileName][$record['hash']] = $this->formatReferenceIndexEntryToString($record);
            }
        }

        return $missingReferences;
    }

    /**
     * Find file references that points to non-existing files in system
     * registered as soft references (checked for "softref_key")
     *
     * @return array an array of the data within soft references
     */
    protected function findMissingSoftReferencedFiles(): array
    {
        $missingReferences = [];
        // Select all files in the reference table
        $queryBuilder = $this->connectionPool
            ->getQueryBuilderForTable('sys_refindex');

        $result = $queryBuilder
            ->select('*')
            ->from('sys_refindex')
            ->where(
                $queryBuilder->expr()->eq('ref_table', $queryBuilder->createNamedParameter('_FILE', \PDO::PARAM_STR)),
                $queryBuilder->expr()->isNotNull('softref_key')
            )
            ->executeQuery();

        // Traverse the references and check if the files exists
        while ($record = $result->fetchAssociative()) {
            $fileName = $this->getFileNameWithoutAnchor($record['ref_string']);
            if (!@is_file(Environment::getPublicPath() . '/' . $fileName)) {
                $missingReferences[] = $fileName . ' - ' . $record['hash'] . ' - ' . $this->formatReferenceIndexEntryToString($record);
            }
        }
        return $missingReferences;
    }

    /**
     * Remove a possible anchor like 'my-path/file.pdf#page15'
     *
     * @param string $fileName a filename as found in sys_refindex.ref_string
     * @return string the filename but leaving everything behind #page15 behind
     */
    protected function getFileNameWithoutAnchor(string $fileName): string
    {
        if (str_contains($fileName, '#')) {
            [$fileName] = explode('#', $fileName);
        }
        return $fileName;
    }

    /**
     * Removes all references in the sys_file_reference where files were not found
     *
     * @param array $missingManagedFiles Contains the records of sys_refindex which need to be updated
     * @param bool $dryRun if set, the references are just displayed, but not removed
     * @param SymfonyStyle $io the IO object for output
     */
    protected function removeReferencesToMissingFiles(array $missingManagedFiles, bool $dryRun, SymfonyStyle $io)
    {
        foreach ($missingManagedFiles as $fileName => $references) {
            if ($io->isVeryVerbose()) {
                $io->writeln('Deleting references to missing file "' . $fileName . '"');
            }
            foreach ($references as $hash => $recordReference) {
                $io->writeln('Removing reference in record "' . $recordReference . '"');
                if (!$dryRun) {
                    $sysRefObj = GeneralUtility::makeInstance(ReferenceIndex::class);
                    $error = $sysRefObj->setReferenceValue($hash, null);
                    if ($error) {
                        $io->error('ReferenceIndex::setReferenceValue() reported "' . $error . '"');
                    }
                }
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
        return $record['tablename'] . ':' . $record['recuid'] . ':' . $record['field'] . ':' . $record['flexpointer'] . ':' . $record['softref_key'];
    }
}
