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
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Force-deletes all records in the database which have a deleted=1 flag
 */
class DeletedRecordsCommand extends Command
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
            ->setHelp('Traverse page tree and find and flush deleted records. If you want to get more detailed information, use the --verbose option.')
            ->addOption(
                'pid',
                'p',
                InputOption::VALUE_REQUIRED,
                'Setting start page in page tree. Default is the page tree root, 0 (zero)'
            )
            ->addOption(
                'depth',
                'd',
                InputOption::VALUE_REQUIRED,
                'Setting traversal depth. 0 (zero) will only analyze start page (see --pid), 1 will traverse one level of subpages etc.'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'If this option is set, the records will not actually be deleted, but just the output which records would be deleted are shown'
            );
    }

    /**
     * Executes the command to find and permanently delete records which are marked as deleted
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

        $startingPoint = 0;
        if ($input->hasOption('pid') && MathUtility::canBeInterpretedAsInteger($input->getOption('pid'))) {
            $startingPoint = MathUtility::forceIntegerInRange((int)$input->getOption('pid'), 0);
        }

        $depth = 1000;
        if ($input->hasOption('depth') && MathUtility::canBeInterpretedAsInteger($input->getOption('depth'))) {
            $depth = MathUtility::forceIntegerInRange((int)$input->getOption('depth'), 0);
        }

        if ($io->isVerbose()) {
            $io->section('Searching the database now for deleted records.');
        }

        // type unsafe comparison and explicit boolean setting on purpose
        $dryRun = $input->hasOption('dry-run') && $input->getOption('dry-run') != false ? true : false;

        // find all records that should be deleted
        $deletedRecords = $this->findAllFlaggedRecordsInPage($startingPoint, $depth);

        if (!$io->isQuiet()) {
            $totalAmountOfTables = count($deletedRecords);
            $totalAmountOfRecords = 0;
            foreach ($deletedRecords as $tableName => $itemsInTable) {
                $totalAmountOfRecords += count($itemsInTable);

                if ($io->isVeryVerbose()) {
                    $io->writeln('Found ' . count($itemsInTable) . ' deleted records in table "' . $tableName . '".');
                }
            }
            $io->note('Found ' . $totalAmountOfRecords . ' records in ' . $totalAmountOfTables . ' database tables ready to be deleted.');
        }

        $io->section('Deletion process starting now.' . ($dryRun ? ' (Not deleting now, just a dry run)' : ''));

        // actually permanently delete them
        $this->deleteRecords($deletedRecords, $dryRun, $io);

        $io->success('All done!');
        return 0;
    }

    /**
     * Recursive traversal of page tree to fetch all records marked as "deleted",
     * via option $GLOBALS[TCA][$tableName][ctrl][delete]
     * This also takes deleted versioned records into account.
     *
     * @param int $pageId the uid of the pages record (can also be 0)
     * @param int $depth The current depth of levels to go down
     * @param array $deletedRecords the records that are already marked as deleted (used when going recursive)
     *
     * @return array the modified $deletedRecords array
     */
    protected function findAllFlaggedRecordsInPage(int $pageId, int $depth, array $deletedRecords = []): array
    {
        $queryBuilderForPages = $this->connectionPool
            ->getQueryBuilderForTable('pages');
        $queryBuilderForPages->getRestrictions()->removeAll();

        $pageId = (int)$pageId;
        if ($pageId > 0) {
            $queryBuilderForPages
                ->select('uid', 'deleted')
                ->from('pages')
                ->where(
                    $queryBuilderForPages->expr()->andX(
                        $queryBuilderForPages->expr()->eq(
                            'uid',
                            $queryBuilderForPages->createNamedParameter($pageId, \PDO::PARAM_INT)
                        ),
                        $queryBuilderForPages->expr()->neq('deleted', 0)
                    )
                )
                // @todo Executing and not assigning and use the result looks weired, at least with the
                //       circumstance that the same QueryBuilder is reused as count query and executed
                //       directly afterwards - must be rechecked and either solved or proper commented
                //       why this mystery is needed here as this is not obvious and against general
                //       recommendation to not reuse the QueryBuilder.
                ->executeQuery();
            $rowCount = $queryBuilderForPages
                ->count('uid')
                ->executeQuery()
                ->fetchOne();
            // Register if page itself is deleted
            if ($rowCount > 0) {
                $deletedRecords['pages'][$pageId] = $pageId;
            }
        }

        $databaseTables = $this->getTablesWithDeletedFlags();
        // Traverse tables of records that belongs to page
        foreach ($databaseTables as $tableName => $deletedField) {
            // Select all records belonging to page
            $queryBuilder = $this->connectionPool
                ->getQueryBuilderForTable($tableName);

            $queryBuilder->getRestrictions()->removeAll();

            $result = $queryBuilder
                ->select('uid', $deletedField)
                ->from($tableName)
                ->where(
                    $queryBuilder->expr()->eq(
                        'pid',
                        $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT)
                    )
                )
                ->executeQuery();

            while ($recordOnPage = $result->fetchAssociative()) {
                // Register record as deleted
                if ($recordOnPage[$deletedField]) {
                    $deletedRecords[$tableName][$recordOnPage['uid']] = $recordOnPage['uid'];
                }
                // Add any versions of those records
                $versions = BackendUtility::selectVersionsOfRecord(
                    $tableName,
                    $recordOnPage['uid'],
                    'uid,t3ver_wsid,' . $deletedField,
                    null,
                    true
                ) ?: [];
                if (is_array($versions)) {
                    foreach ($versions as $verRec) {
                        // Mark as deleted
                        if (!$verRec['_CURRENT_VERSION'] && $verRec[$deletedField]) {
                            $deletedRecords[$tableName][$verRec['uid']] = $verRec['uid'];
                        }
                    }
                }
            }
        }

        // Find subpages to root ID and go recursive
        if ($depth > 0) {
            $depth--;
            $result = $queryBuilderForPages
                ->select('uid')
                ->from('pages')
                ->where(
                    $queryBuilderForPages->expr()->eq('pid', $pageId)
                )
                ->orderBy('sorting')
                ->executeQuery();

            while ($subPage = $result->fetchAssociative()) {
                $deletedRecords = $this->findAllFlaggedRecordsInPage($subPage['uid'], $depth, $deletedRecords);
            }
        }

        // Add any versions of the page
        if ($pageId > 0) {
            $versions = BackendUtility::selectVersionsOfRecord(
                'pages',
                $pageId,
                'uid,t3ver_oid,t3ver_wsid',
                null,
                true
            ) ?: [];
            if (is_array($versions)) {
                foreach ($versions as $verRec) {
                    if (!$verRec['_CURRENT_VERSION']) {
                        $deletedRecords = $this->findAllFlaggedRecordsInPage($verRec['uid'], $depth, $deletedRecords);
                    }
                }
            }
        }

        return $deletedRecords;
    }

    /**
     * Fetches all tables registered in the TCA with a deleted
     * and that are not pages (which are handled separately)
     *
     * @return array an associative array with the table as key and the
     */
    protected function getTablesWithDeletedFlags(): array
    {
        $tables = [];
        foreach ($GLOBALS['TCA'] as $tableName => $configuration) {
            if ($tableName !== 'pages' && isset($GLOBALS['TCA'][$tableName]['ctrl']['delete'])) {
                $tables[$tableName] = $GLOBALS['TCA'][$tableName]['ctrl']['delete'];
            }
        }
        ksort($tables);
        return $tables;
    }

    /**
     * Deletes records via DataHandler
     *
     * @param array $deletedRecords two level array with tables and uids
     * @param bool $dryRun check if the records should NOT be deleted (use --dry-run to avoid)
     * @param SymfonyStyle $io
     */
    protected function deleteRecords(array $deletedRecords, bool $dryRun, SymfonyStyle $io)
    {
        // Putting "pages" table in the bottom
        if (isset($deletedRecords['pages'])) {
            $_pages = $deletedRecords['pages'];
            unset($deletedRecords['pages']);
            // To delete sub pages first assuming they are accumulated from top of page tree.
            $deletedRecords['pages'] = array_reverse($_pages);
        }

        // set up the data handler instance
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], []);

        // Loop through all tables and their records
        foreach ($deletedRecords as $table => $list) {
            if ($io->isVerbose()) {
                $io->writeln('Flushing ' . count($list) . ' deleted records from table "' . $table . '"');
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
                        $io->writeln('Permanently deleted record "' . $table . ':' . $uid . '".');
                    }
                }
            }
        }
    }
}
