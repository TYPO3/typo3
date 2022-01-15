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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Finds (and fixes) all records that have an invalid / deleted page ID
 */
class OrphanRecordsCommand extends Command
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
            ->setHelp('Assumption: All actively used records on the website from TCA configured tables are located in the page tree exclusively.

All records managed by TYPO3 via the TCA array configuration has to belong to a page in the page tree, either directly or indirectly as a version of another record.
VERY TIME, CPU and MEMORY intensive operation since the full page tree is looked up!

Automatic Repair of Errors:
- Silently deleting the orphaned records. In theory they should not be used anywhere in the system, but there could be references. See below for more details on this matter.

Manual repair suggestions:
- Possibly re-connect orphaned records to page tree by setting their "pid" field to a valid page id. A lookup in the sys_refindex table can reveal if there are references to a orphaned record. If there are such references (from records that are not themselves orphans) you might consider to re-connect the record to the page tree, otherwise it should be safe to delete it.

 If you want to get more detailed information, use the --verbose option.')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'If this option is set, the records will not actually be deleted, but just the output which records would be deleted are shown'
            );
    }

    /**
     * Executes the command to find records not attached to the pagetree
     * and permanently delete these records
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

        if ($io->isVerbose()) {
            $io->section('Searching the database now for orphaned records.');
        }

        // type unsafe comparison and explicit boolean setting on purpose
        $dryRun = $input->hasOption('dry-run') && $input->getOption('dry-run') != false ? true : false;

        // find all records that should be deleted
        $allRecords = $this->findAllConnectedRecordsInPage(0, 10000);

        // Find orphans
        $orphans = [];
        foreach (array_keys($GLOBALS['TCA']) as $tableName) {
            $idList = [0];
            if (is_array($allRecords[$tableName] ?? false) && !empty($allRecords[$tableName])) {
                $idList = $allRecords[$tableName];
            }
            // Select all records that are NOT connected
            $queryBuilder = $this->connectionPool
                ->getQueryBuilderForTable($tableName);
            $queryBuilder->getRestrictions()->removeAll();
            $queryBuilder
                ->from($tableName)
                ->where(
                    $queryBuilder->expr()->notIn(
                        'uid',
                        // do not use named parameter here as the list can get too long
                        array_map('intval', $idList)
                    )
                );

            $countQueryBuilder = clone $queryBuilder;
            $rowCount = $countQueryBuilder->count('uid')->executeQuery()->fetchOne();
            if ($rowCount) {
                $queryBuilder->select('uid')->orderBy('uid');
                $result = $queryBuilder->executeQuery();

                $orphans[$tableName] = [];
                while ($orphanRecord = $result->fetchAssociative()) {
                    $orphans[$tableName][$orphanRecord['uid']] = $orphanRecord['uid'];
                }

                if (count($orphans[$tableName])) {
                    $io->note('Found ' . count($orphans[$tableName]) . ' orphan records in table "' . $tableName . '" with following ids: ' . implode(', ', $orphans[$tableName]));
                }
            }
        }

        if (count($orphans)) {
            $io->section('Deletion process starting now.' . ($dryRun ? ' (Not deleting now, just a dry run)' : ''));

            // Actually permanently delete them
            $this->deleteRecords($orphans, $dryRun, $io);

            $io->success('All done!');
        } else {
            $io->success('No orphan records found.');
        }
        return 0;
    }

    /**
     * Recursive traversal of page tree to fetch all records marked as "deleted",
     * via option $GLOBALS[TCA][$tableName][ctrl][delete]
     * This also takes deleted versioned records into account.
     *
     * @param int $pageId the uid of the pages record (can also be 0)
     * @param int $depth The current depth of levels to go down
     * @param array $allRecords the records that are already marked as deleted (used when going recursive)
     *
     * @return array the modified $deletedRecords array
     */
    protected function findAllConnectedRecordsInPage(int $pageId, int $depth, array $allRecords = []): array
    {
        // Register page
        if ($pageId > 0) {
            $allRecords['pages'][$pageId] = $pageId;
        }
        // Traverse tables of records that belongs to page
        foreach (array_keys($GLOBALS['TCA']) as $tableName) {
            /** @var string $tableName */
            if ($tableName !== 'pages') {
                // Select all records belonging to page:
                $queryBuilder = $this->connectionPool
                    ->getQueryBuilderForTable($tableName);

                $queryBuilder->getRestrictions()->removeAll();

                $result = $queryBuilder
                    ->select('uid')
                    ->from($tableName)
                    ->where(
                        $queryBuilder->expr()->eq(
                            'pid',
                            $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT)
                        )
                    )
                    ->executeQuery();

                while ($rowSub = $result->fetchAssociative()) {
                    $allRecords[$tableName][$rowSub['uid']] = $rowSub['uid'];
                    // Add any versions of those records:
                    $versions = BackendUtility::selectVersionsOfRecord($tableName, $rowSub['uid'], 'uid,t3ver_wsid', null, true);
                    if (is_array($versions)) {
                        foreach ($versions as $verRec) {
                            if (!$verRec['_CURRENT_VERSION']) {
                                $allRecords[$tableName][$verRec['uid']] = $verRec['uid'];
                            }
                        }
                    }
                }
            }
        }
        // Find subpages to root ID and traverse (only when rootID is not a version or is a branch-version):
        if ($depth > 0) {
            $depth--;
            $queryBuilder = $this->connectionPool
                ->getQueryBuilderForTable('pages');

            $queryBuilder->getRestrictions()->removeAll();

            $result = $queryBuilder
                ->select('uid')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq(
                        'pid',
                        $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT)
                    )
                )
                ->orderBy('sorting')
                ->executeQuery();

            while ($row = $result->fetchAssociative()) {
                $allRecords = $this->findAllConnectedRecordsInPage((int)$row['uid'], $depth, $allRecords);
            }
        }

        // Add any versions of pages
        if ($pageId > 0) {
            $versions = BackendUtility::selectVersionsOfRecord('pages', $pageId, 'uid,t3ver_oid,t3ver_wsid', null, true);
            if (is_array($versions)) {
                foreach ($versions as $verRec) {
                    if (!$verRec['_CURRENT_VERSION']) {
                        $allRecords = $this->findAllConnectedRecordsInPage((int)$verRec['uid'], $depth, $allRecords);
                    }
                }
            }
        }
        return $allRecords;
    }

    /**
     * Deletes records via DataHandler
     *
     * @param array $orphanedRecords two level array with tables and uids
     * @param bool $dryRun check if the records should NOT be deleted (use --dry-run to avoid)
     * @param SymfonyStyle $io
     */
    protected function deleteRecords(array $orphanedRecords, bool $dryRun, SymfonyStyle $io)
    {
        // Putting "pages" table in the bottom
        if (isset($orphanedRecords['pages'])) {
            $_pages = $orphanedRecords['pages'];
            unset($orphanedRecords['pages']);
            // To delete sub pages first assuming they are accumulated from top of page tree.
            $orphanedRecords['pages'] = array_reverse($_pages);
        }

        // set up the data handler instance
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], []);

        // Loop through all tables and their records
        foreach ($orphanedRecords as $table => $list) {
            if ($io->isVerbose()) {
                $io->writeln('Flushing ' . count($list) . ' orphaned records from table "' . $table . '"');
            }
            foreach ($list as $uid) {
                if ($io->isVeryVerbose()) {
                    $io->writeln('Flushing record "' . $table . ':' . $uid . '"');
                }
                if (!$dryRun) {
                    // Notice, we are deleting pages with no regard to subpages/subrecords - we do this since they
                    // should also be included in the set of deleted pages of course (no un-deleted record can exist
                    // under a deleted page...)
                    $dataHandler->deleteRecord($table, $uid, true, true);
                    // Return errors if any:
                    if (!empty($dataHandler->errorLog)) {
                        $errorMessage = array_merge(['DataHandler reported an error'], $dataHandler->errorLog);
                        $io->error($errorMessage);
                    } elseif (!$io->isQuiet()) {
                        $io->writeln('Permanently deleted orphaned record "' . $table . ':' . $uid . '".');
                    }
                }
            }
        }
    }
}
