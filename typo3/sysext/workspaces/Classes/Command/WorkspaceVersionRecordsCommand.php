<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Workspaces\Command;

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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

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
     * Array with all records found when traversing the database
     * @var array
     */
    protected $foundRecords = [
        // All versions of records found
        // Subset of "all" which are offline versions (pid=-1) [Informational]
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
        'invalid_workspace' => []
    ];

    /**
     * Configuring the command options
     */
    public function configure()
    {
        $this
            ->setDescription('Find all versioned records and possibly cleans up invalid records in the database.')
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
                'Specify which action should be taken. Set it to "versions_in_live", "published_versions", "invalid_workspace" or "unused_placeholders"'
            );
    }

    /**
     * Executes the command to find versioned records
     *
     * @param InputInterface $input
     * @param OutputInterface $output
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

        $unusedPlaceholders = $this->findUnusedPlaceholderRecords();

        // Finding all move placeholders with inconsistencies
        // Move-to placeholder records which have bad integrity
        $invalidMovePlaceholders = $this->findInvalidMovePlaceholderRecords();

        // Finding move_id_check inconsistencies
        // Checking if t3ver_move_id is correct. t3ver_move_id must only be set with online records having t3ver_state=3.
        $recordsWithInvalidMoveIds = $this->findInvalidMoveIdRecords();

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

            $io->section('Found ' . count($unusedPlaceholders) . ' unused placeholder records.');
            if ($io->isVeryVerbose()) {
                $io->listing(array_keys($unusedPlaceholders));
            }

            $io->section('Found ' . count($invalidMovePlaceholders) . ' invalid move placeholders.');
            if ($io->isVeryVerbose()) {
                $io->listing($invalidMovePlaceholders);
            }

            $io->section('Found ' . count($recordsWithInvalidMoveIds) . ' versions with an invalid move ID.');
            if ($io->isVeryVerbose()) {
                $io->listing($recordsWithInvalidMoveIds);
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

            // Finding all placeholders with no records attached
            // Placeholder records which are not used anymore by offline versions.
            case 'unused_placeholders':
                $io->section('Deleting unused placeholder records now. ' . ($dryRun ? ' (Not deleting now, just a dry run)' : ''));
                $this->deleteUnusedPlaceholders($unusedPlaceholders, $dryRun, $io);
                break;

            default:
                $io->note('No action specified, just displaying statistics. See --action option for details.');
                break;
        }
        $io->success('All done!');
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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();

        $pageRecord = $queryBuilder
            ->select(
                'deleted',
                'title',
                't3ver_count',
                't3ver_wsid'
            )
            ->from('pages')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($rootID, \PDO::PARAM_INT)))
            ->execute()
            ->fetch();

        // If rootIsVersion is set it means that the input rootID is that of a version of a page. See below where the recursive call is made.
        if ($rootIsVersion) {
            $workspaceId = (int)$pageRecord['t3ver_wsid'];
            $this->foundRecords['all_versioned_records']['pages'][$rootID] = $rootID;
            // If it has been published and is in archive now...
            if ($pageRecord['t3ver_count'] >= 1 && $workspaceId === 0) {
                $this->foundRecords['published_versions']['pages'][$rootID] = $rootID;
            }
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
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                        ->getQueryBuilderForTable($tableName);

                    $queryBuilder->getRestrictions()->removeAll();

                    $result = $queryBuilder
                        ->select('uid')
                        ->from($tableName)
                        ->where(
                            $queryBuilder->expr()->eq(
                                'pid',
                                $queryBuilder->createNamedParameter($rootID, \PDO::PARAM_INT)
                            )
                        )
                        ->execute();
                    while ($rowSub = $result->fetch()) {
                        // Add any versions of those records
                        $versions = BackendUtility::selectVersionsOfRecord($tableName, $rowSub['uid'], 'uid,t3ver_wsid,t3ver_count' . ($GLOBALS['TCA'][$tableName]['ctrl']['delete'] ? ',' . $GLOBALS['TCA'][$tableName]['ctrl']['delete'] : ''), null, true);
                        if (is_array($versions)) {
                            foreach ($versions as $verRec) {
                                if (!$verRec['_CURRENT_VERSION']) {
                                    // Register version
                                    $this->foundRecords['all_versioned_records'][$tableName][$verRec['uid']] = $verRec['uid'];
                                    $workspaceId = (int)$verRec['t3ver_wsid'];
                                    if ($verRec['t3ver_count'] >= 1 && $workspaceId === 0) {
                                        // Only register published versions in LIVE workspace
                                        // (published versions in draft workspaces are allowed)
                                        $this->foundRecords['published_versions'][$tableName][$verRec['uid']] = $verRec['uid'];
                                    }
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
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('pages');

            $queryBuilder->getRestrictions()->removeAll();
            $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

            $queryBuilder
                ->select('uid')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq(
                        'pid',
                        $queryBuilder->createNamedParameter($rootID, \PDO::PARAM_INT)
                    )
                )
                ->orderBy('sorting');

            $result = $queryBuilder->execute();
            while ($row = $result->fetch()) {
                $this->traversePageTreeForVersionedRecords((int)$row['uid'], $depth, $isInsideVersionedPage, false);
            }
        }
        // Add any versions of pages
        if ($rootID > 0) {
            $versions = BackendUtility::selectVersionsOfRecord('pages', $rootID, 'uid,t3ver_oid,t3ver_wsid,t3ver_count', null, true);
            if (is_array($versions)) {
                foreach ($versions as $verRec) {
                    if (!$verRec['_CURRENT_VERSION']) {
                        $this->traversePageTreeForVersionedRecords((int)$verRec['uid'], $depth, true, true);
                    }
                }
            }
        }
    }

    /**
     * Find all records where the field t3ver_state=1 (new placeholder)
     *
     * @return array the records (md5 as hash) with "table:uid" as value
     */
    protected function findUnusedPlaceholderRecords(): array
    {
        $unusedPlaceholders = [];
        $tableNames = $this->getAllVersionableTables();
        foreach ($tableNames as $table) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable($table);

            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

            $result = $queryBuilder
                ->select('uid', 'pid')
                ->from($table)
                ->where(
                    $queryBuilder->expr()->gte('pid', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->eq(
                        't3ver_state',
                        $queryBuilder->createNamedParameter(
                            (string)new VersionState(VersionState::NEW_PLACEHOLDER),
                            \PDO::PARAM_INT
                        )
                    )
                )
                ->execute();

            while ($placeholderRecord = $result->fetch()) {
                $versions = BackendUtility::selectVersionsOfRecord($table, $placeholderRecord['uid'], 'uid', '*', null);
                if (count($versions) <= 1) {
                    $unusedPlaceholders[$table . ':' . $placeholderRecord['uid']] = [
                        'table' => $table,
                        'uid'   => $placeholderRecord['uid']
                    ];
                }
            }
        }
        ksort($unusedPlaceholders);
        return $unusedPlaceholders;
    }

    /**
     * Find all records where the field t3ver_state=3 (move placeholder)
     * and checks against the ws_id etc.
     *
     * @return array the records (md5 as hash) with an array of data
     */
    protected function findInvalidMovePlaceholderRecords(): array
    {
        $invalidMovePlaceholders = [];
        $tableNames = $this->getAllVersionableTables();
        foreach ($tableNames as $table) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable($table);

            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

            $result = $queryBuilder
                ->select('uid', 'pid', 't3ver_move_id', 't3ver_wsid', 't3ver_state')
                ->from($table)
                ->where(
                    $queryBuilder->expr()->gte('pid', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->eq(
                        't3ver_state',
                        $queryBuilder->createNamedParameter(
                            (string)new VersionState(VersionState::MOVE_PLACEHOLDER),
                            \PDO::PARAM_INT
                        )
                    )
                )
                ->execute();
            while ($placeholderRecord = $result->fetch()) {
                $shortID = GeneralUtility::shortMD5($table . ':' . $placeholderRecord['uid']);
                if ((int)$placeholderRecord['t3ver_wsid'] !== 0) {
                    $phrecCopy = $placeholderRecord;
                    if (BackendUtility::movePlhOL($table, $placeholderRecord)) {
                        if ($wsAlt = BackendUtility::getWorkspaceVersionOfRecord($phrecCopy['t3ver_wsid'], $table, $placeholderRecord['uid'], 'uid,pid,t3ver_state')) {
                            if (!VersionState::cast($wsAlt['t3ver_state'])->equals(VersionState::MOVE_POINTER)) {
                                $invalidMovePlaceholders[$shortID] = $table . ':' . $placeholderRecord['uid'] . ' - State for version was not "4" as it should be!';
                            }
                        } else {
                            $invalidMovePlaceholders[$shortID] = $table . ':' . $placeholderRecord['uid'] . ' - No version was found for online record to be moved. A version must exist.';
                        }
                    } else {
                        $invalidMovePlaceholders[$shortID] = $table . ':' . $placeholderRecord['uid'] . ' - Did not find online record for "t3ver_move_id" value ' . $placeholderRecord['t3ver_move_id'];
                    }
                } else {
                    $invalidMovePlaceholders[$shortID] = $table . ':' . $placeholderRecord['uid'] . ' - Placeholder was not assigned a workspace value in t3ver_wsid.';
                }
            }
        }
        ksort($invalidMovePlaceholders);
        return $invalidMovePlaceholders;
    }

    /**
     * Find records with a t3ver_move_id field != 0 that are
     * neither a move placeholder or, if it is a move placeholder is offline
     *
     * @return array
     */
    protected function findInvalidMoveIdRecords(): array
    {
        $records = [];
        $tableNames = $this->getAllVersionableTables();
        foreach ($tableNames as $table) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable($table);

            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

            $result = $queryBuilder
                ->select('uid', 'pid', 't3ver_move_id', 't3ver_wsid', 't3ver_oid', 't3ver_state')
                ->from($table)
                ->where(
                    $queryBuilder->expr()->neq(
                        't3ver_move_id',
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    )
                )
                ->execute();

            while ($placeholderRecord = $result->fetch()) {
                if (VersionState::cast($placeholderRecord['t3ver_state'])->equals(VersionState::MOVE_PLACEHOLDER)) {
                    if ((int)$placeholderRecord['t3ver_oid'] > 0) {
                        $records[] = $table . ':' . $placeholderRecord['uid'] . ' - Record was offline, must not be!';
                    }
                } else {
                    $records[] = $table . ':' . $placeholderRecord['uid'] . ' - Record had t3ver_move_id set to "' . $placeholderRecord['t3ver_move_id'] . '" while having t3ver_state=' . $placeholderRecord['t3ver_state'];
                }
            }
        }
        return $records;
    }

    /**************************
     * actions / delete methods
     **************************/

    /**
     * Deletes records via DataHandler
     *
     * @param array $records two level array with tables and uids
     * @param bool $dryRun check if the records should NOT be deleted (use --dry-run to avoid)
     * @param SymfonyStyle $io
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
     * @param SymfonyStyle $io
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
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                        ->getQueryBuilderForTable($table);

                    $queryBuilder
                        ->update($table)
                        ->where(
                            $queryBuilder->expr()->eq(
                                'uid',
                                $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                            )
                        )
                        ->set('t3ver_wsid', 0)
                        ->execute();
                    if (!$io->isQuiet()) {
                        $io->writeln('Flushed record "' . $table . ':' . $uid . '".');
                    }
                }
            }
        }
    }

    /**
     * Delete unused placeholders
     *
     * @param array $records array with array of table and uid of each record
     * @param bool $dryRun check if the records should NOT be deleted (use --dry-run to avoid)
     * @param SymfonyStyle $io
     */
    protected function deleteUnusedPlaceholders(array $records, bool $dryRun, SymfonyStyle $io)
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], []);
        foreach ($records as $record) {
            $table = $record['table'];
            $uid = $record['uid'];
            if ($io->isVeryVerbose()) {
                $io->writeln('Deleting unused placeholder (soft) "' . $table . ':' . $uid . '"');
            }
            if (!$dryRun) {
                $dataHandler->deleteAction($table, $uid);
                // Return errors if any
                if (!empty($dataHandler->errorLog)) {
                    $errorMessage = array_merge(['DataHandler reported an error'], $dataHandler->errorLog);
                    $io->error($errorMessage);
                } elseif (!$io->isQuiet()) {
                    $io->writeln('Permanently deleted unused placeholder "' . $table . ':' . $uid . '".');
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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_workspace');

        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $result = $queryBuilder
            ->select('uid', 'title')
            ->from('sys_workspace')
            ->execute();

        while ($workspaceRecord = $result->fetch()) {
            $this->allWorkspaces[(int)$workspaceRecord['uid']] = $workspaceRecord['title'];
        }
        return $this->allWorkspaces;
    }

    /**
     * Returns all TCA tables where workspaces is enabled
     *
     * @return array
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
