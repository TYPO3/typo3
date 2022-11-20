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

namespace TYPO3\CMS\Workspaces\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Fetches all versions in the database, and checks for integrity
 */
class WorkspaceVersionRecordsCommand extends Command
{
    /**
     * List of all workspaces
     * @var array
     */
    protected $allWorkspaces = [0 => 'Live Workspace'];

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
     * Array with all records found when traversing the database
     * @var array
     */
    protected $foundRecords = [
        // All versions of records found
        // Subset of "all" which are offline versions (t3ver_oid > 0) [Informational]
        'all_versioned_records' => [],
        // All records that has been published and can therefore be removed permanently
        // Subset of "versions" that is a count of 1 or more (has been published) [Informational]
        'published_versions' => [],
        // All versions that are offline versions in the Live workspace. You may wish to flush these if you only use
        // workspaces for versioning since then you might find lots of versions piling up in the live workspace which
        // have simply been disconnected from the workspace before they were published.
        'versions_in_live' => [],
        // Versions that has lost their connection to a workspace in TYPO3.
        // Subset of "versions" that doesn't belong to an existing workspace [Warning: Fix by move to live workspace]
        'invalid_workspace' => [],
    ];

    /**
     * Configuring the command options
     */
    public function configure()
    {
        $this
            ->setHelp('Traverse page tree and find versioned records. Also list all versioned records, additionally with some inconsistencies in the database, which can cleaned up with the "action" option. If you want to get more detailed information, use the --verbose option.')
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
                'If this option is set, the records will not actually be deleted/modified, but just the output which records would be touched are shown'
            )
            ->addOption(
                'action',
                null,
                InputOption::VALUE_OPTIONAL,
                'Specify which action should be taken. Set it to "versions_in_live", "published_versions" or "invalid_workspace"'
            );
    }

    /**
     * Executes the command to find versioned records
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
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

        $action = '';
        if ($input->hasOption('action') && !empty($input->getOption('action'))) {
            $action = $input->getOption('action');
        }

        // type unsafe comparison and explicit boolean setting on purpose
        $dryRun = $input->hasOption('dry-run') && $input->getOption('dry-run') != false ? true : false;

        if ($io->isVerbose()) {
            $io->section('Searching the database now for versioned records.');
        }

        $this->loadAllWorkspaceRecords();

        // Find all records that are versioned
        $this->traversePageTreeForVersionedRecords($startingPoint, $depth);
        // Sort recStats (for diff'able displays)
        foreach ($this->foundRecords as $kk => $vv) {
            foreach ($this->foundRecords[$kk] as $tables => $recArrays) {
                ksort($this->foundRecords[$kk][$tables]);
            }
            ksort($this->foundRecords[$kk]);
        }

        if (!$io->isQuiet()) {
            $numberOfVersionedRecords = 0;
            foreach ($this->foundRecords['all_versioned_records'] as $records) {
                $numberOfVersionedRecords += count($records);
            }

            $io->section('Found ' . $numberOfVersionedRecords . ' versioned records in the database.');
            if ($io->isVeryVerbose()) {
                foreach ($this->foundRecords['all_versioned_records'] as $table => $records) {
                    $io->writeln('Table "' . $table . '"');
                    $io->listing($records);
                }
            }

            $numberOfPublishedVersions = 0;
            foreach ($this->foundRecords['published_versions'] as $records) {
                $numberOfPublishedVersions += count($records);
            }
            $io->section('Found ' . $numberOfPublishedVersions . ' versioned records that have been published.');
            if ($io->isVeryVerbose()) {
                foreach ($this->foundRecords['published_versions'] as $table => $records) {
                    $io->writeln('Table "' . $table . '"');
                    $io->listing($records);
                }
            }

            $numberOfVersionsInLiveWorkspace = 0;
            foreach ($this->foundRecords['versions_in_live'] as $records) {
                $numberOfVersionsInLiveWorkspace += count($records);
            }
            $io->section('Found ' . $numberOfVersionsInLiveWorkspace . ' versioned records that are in the live workspace.');
            if ($io->isVeryVerbose()) {
                foreach ($this->foundRecords['versions_in_live'] as $table => $records) {
                    $io->writeln('Table "' . $table . '"');
                    $io->listing($records);
                }
            }

            $numberOfVersionsWithInvalidWorkspace = 0;
            foreach ($this->foundRecords['invalid_workspace'] as $records) {
                $numberOfVersionsWithInvalidWorkspace += count($records);
            }
            $io->section('Found ' . $numberOfVersionsWithInvalidWorkspace . ' versioned records with an invalid workspace.');
            if ($io->isVeryVerbose()) {
                foreach ($this->foundRecords['invalid_workspace'] as $table => $records) {
                    $io->writeln('Table "' . $table . '"');
                    $io->listing($records);
                }
            }
        }

        // Actually permanently delete / update records
        switch ($action) {
            // All versions that are offline versions in the Live workspace. You may wish to flush these if you only use
            // workspaces for versioning since then you might find lots of versions piling up in the live workspace which
            // have simply been disconnected from the workspace before they were published.
            case 'versions_in_live':
                $io->section('Deleting versioned records in live workspace now. ' . ($dryRun ? ' (Not deleting now, just a dry run)' : ''));
                $this->deleteRecords($this->foundRecords['versions_in_live'], $dryRun, $io);
                break;

                // All records that has been published and can therefore be removed permanently
                // Subset of "versions" that is a count of 1 or more (has been published)
            case 'published_versions':
                $io->section('Deleting published records in live workspace now. ' . ($dryRun ? ' (Not deleting now, just a dry run)' : ''));
                $this->deleteRecords($this->foundRecords['published_versions'], $dryRun, $io);
                break;

                // Versions that has lost their connection to a workspace in TYPO3.
                // Subset of "versions" that doesn't belong to an existing workspace [Warning: Fix by move to live workspace]
            case 'invalid_workspace':
                $io->section('Moving versions in invalid workspaces to live workspace now. ' . ($dryRun ? ' (Not deleting now, just a dry run)' : ''));
                $this->resetRecordsWithoutValidWorkspace($this->foundRecords['invalid_workspace'], $dryRun, $io);
                break;

            default:
                $io->note('No action specified, just displaying statistics. See --action option for details.');
                break;
        }
        $io->success('All done!');
        return Command::SUCCESS;
    }

    /**
     * Recursive traversal of page tree, fetching ALL versioned records found in the database
     *
     * @param int $rootID Page root id (must be online, valid page record - or zero for page tree root)
     * @param int $depth Depth
     * @param bool $isInsideVersionedPage DON'T set from outside, internal. (indicates we are inside a version of a page)
     * @param bool $rootIsVersion DON'T set from outside, internal. Indicates that rootID is a version of a page
     */
    protected function traversePageTreeForVersionedRecords(int $rootID, int $depth, bool $isInsideVersionedPage = false, bool $rootIsVersion = false)
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();

        $pageRecord = $queryBuilder
            ->select(
                'deleted',
                'title',
                't3ver_wsid'
            )
            ->from('pages')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($rootID, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchAssociative();

        // If rootIsVersion is set it means that the input rootID is that of a version of a page. See below where the recursive call is made.
        if ($rootIsVersion) {
            $workspaceId = (int)$pageRecord['t3ver_wsid'];
            $this->foundRecords['all_versioned_records']['pages'][$rootID] = $rootID;
            // If it has been published and is in archive now...
            if ($workspaceId === 0) {
                $this->foundRecords['versions_in_live']['pages'][$rootID] = $rootID;
            }
            // If it doesn't belong to a workspace...
            if (!isset($this->allWorkspaces[$workspaceId])) {
                $this->foundRecords['invalid_workspace']['pages'][$rootID] = $rootID;
            }
        }
        // Only check for records if not inside a version
        if (!$isInsideVersionedPage) {
            // Traverse tables of records that belongs to page
            $tableNames = $this->getAllVersionableTables();
            foreach ($tableNames as $tableName) {
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
                                $queryBuilder->createNamedParameter($rootID, Connection::PARAM_INT)
                            )
                        )
                        ->executeQuery();
                    while ($rowSub = $result->fetchAssociative()) {
                        // Add any versions of those records
                        $versions = BackendUtility::selectVersionsOfRecord($tableName, $rowSub['uid'], 'uid,t3ver_wsid' . (($GLOBALS['TCA'][$tableName]['ctrl']['delete'] ?? false) ? ',' . $GLOBALS['TCA'][$tableName]['ctrl']['delete'] : ''), null, true);
                        if (is_array($versions)) {
                            foreach ($versions as $verRec) {
                                if (!($verRec['_CURRENT_VERSION'] ?? false)) {
                                    // Register version
                                    $this->foundRecords['all_versioned_records'][$tableName][$verRec['uid']] = $verRec['uid'];
                                    $workspaceId = (int)$verRec['t3ver_wsid'];
                                    if ($workspaceId === 0) {
                                        $this->foundRecords['versions_in_live'][$tableName][$verRec['uid']] = $verRec['uid'];
                                    }
                                    if (!isset($this->allWorkspaces[$workspaceId])) {
                                        $this->foundRecords['invalid_workspace'][$tableName][$verRec['uid']] = $verRec['uid'];
                                    }
                                }
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
            $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

            $queryBuilder
                ->select('uid')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq(
                        'pid',
                        $queryBuilder->createNamedParameter($rootID, Connection::PARAM_INT)
                    )
                )
                ->orderBy('sorting');

            $result = $queryBuilder->executeQuery();
            while ($row = $result->fetchAssociative()) {
                $this->traversePageTreeForVersionedRecords((int)$row['uid'], $depth, $isInsideVersionedPage, false);
            }
        }
        // Add any versions of pages
        if ($rootID > 0) {
            $versions = BackendUtility::selectVersionsOfRecord('pages', $rootID, 'uid,t3ver_oid,t3ver_wsid', null, true);
            if (is_array($versions)) {
                foreach ($versions as $verRec) {
                    if (!($verRec['_CURRENT_VERSION'] ?? false)) {
                        $this->traversePageTreeForVersionedRecords((int)$verRec['uid'], $depth, true, true);
                    }
                }
            }
        }
    }

    /**************************
     * actions / delete methods
     **************************/
    /**
     * Deletes records via DataHandler
     *
     * @param array $records two level array with tables and uids
     * @param bool $dryRun check if the records should NOT be deleted (use --dry-run to avoid)
     */
    protected function deleteRecords(array $records, bool $dryRun, SymfonyStyle $io)
    {
        // Putting "pages" table in the bottom
        if (isset($records['pages'])) {
            $_pages = $records['pages'];
            unset($records['pages']);
            // To delete sub pages first assuming they are accumulated from top of page tree.
            $records['pages'] = array_reverse($_pages);
        }

        // Set up the data handler instance
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], []);

        // Traversing records
        foreach ($records as $table => $uidsInTable) {
            if ($io->isVerbose()) {
                $io->writeln('Flushing published records from table "' . $table . '"');
            }
            foreach ($uidsInTable as $uid) {
                if ($io->isVeryVerbose()) {
                    $io->writeln('Flushing record "' . $table . ':' . $uid . '"');
                }
                if (!$dryRun) {
                    $dataHandler->deleteEl($table, $uid, true, true);
                    if (!empty($dataHandler->errorLog)) {
                        $errorMessage = array_merge(['DataHandler reported an error'], $dataHandler->errorLog);
                        $io->error($errorMessage);
                    } elseif (!$io->isQuiet()) {
                        $io->writeln('Flushed published record "' . $table . ':' . $uid . '".');
                    }
                }
            }
        }
    }

    /**
     * Set the workspace ID to "0" (= live) for records that have a workspace not found
     * in the system (e.g. hard deleted in the database)
     *
     * @param array $records array with array of table and uid of each record
     * @param bool $dryRun check if the records should NOT be deleted (use --dry-run to avoid)
     */
    protected function resetRecordsWithoutValidWorkspace(array $records, bool $dryRun, SymfonyStyle $io)
    {
        foreach ($records as $table => $uidsInTable) {
            if ($io->isVerbose()) {
                $io->writeln('Resetting workspace to zero for records from table "' . $table . '"');
            }
            foreach ($uidsInTable as $uid) {
                if ($io->isVeryVerbose()) {
                    $io->writeln('Flushing record "' . $table . ':' . $uid . '"');
                }
                if (!$dryRun) {
                    $queryBuilder = $this->connectionPool
                        ->getQueryBuilderForTable($table);

                    $queryBuilder
                        ->update($table)
                        ->where(
                            $queryBuilder->expr()->eq(
                                'uid',
                                $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                            )
                        )
                        ->set('t3ver_wsid', 0)
                        ->executeStatement();
                    if (!$io->isQuiet()) {
                        $io->writeln('Flushed record "' . $table . ':' . $uid . '".');
                    }
                }
            }
        }
    }

    /**
     * HELPER FUNCTIONS
     */

    /**
     * Fetches all sys_workspace records from the database
     *
     * @return array all workspaces with UID as key, and the title as value
     */
    protected function loadAllWorkspaceRecords(): array
    {
        $queryBuilder = $this->connectionPool
            ->getQueryBuilderForTable('sys_workspace');

        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $result = $queryBuilder
            ->select('uid', 'title')
            ->from('sys_workspace')
            ->executeQuery();

        while ($workspaceRecord = $result->fetchAssociative()) {
            $this->allWorkspaces[(int)$workspaceRecord['uid']] = $workspaceRecord['title'];
        }
        return $this->allWorkspaces;
    }

    /**
     * Returns all TCA tables where workspaces is enabled
     */
    protected function getAllVersionableTables(): array
    {
        $tables = [];
        foreach ($GLOBALS['TCA'] as $tableName => $config) {
            if (BackendUtility::isTableWorkspaceEnabled($tableName)) {
                $tables[] = $tableName;
            }
        }
        return $tables;
    }
}
