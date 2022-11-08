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

namespace TYPO3\CMS\Workspaces\Service;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\RootLevelRestriction;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Workspace service
 */
class WorkspaceService implements SingletonInterface
{
    /**
     * @var array
     */
    protected $versionsOnPageCache = [];

    /**
     * @var array
     */
    protected $pagesWithVersionsInTable = [];

    public const TABLE_WORKSPACE = 'sys_workspace';
    public const LIVE_WORKSPACE_ID = 0;

    /**
     * retrieves the available workspaces from the database and checks whether
     * they're available to the current BE user
     *
     * @return array array of workspaces available to the current user
     */
    public function getAvailableWorkspaces()
    {
        $backendUser = $this->getBackendUser();
        $availableWorkspaces = [];
        // add default workspaces
        if ($backendUser->checkWorkspace(self::LIVE_WORKSPACE_ID)) {
            $availableWorkspaces[self::LIVE_WORKSPACE_ID] = $this->getWorkspaceTitle(self::LIVE_WORKSPACE_ID);
        }
        // add custom workspaces (selecting all, filtering by BE_USER check):
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_workspace');
        $queryBuilder->getRestrictions()
            ->add(GeneralUtility::makeInstance(RootLevelRestriction::class));

        $result = $queryBuilder
            ->select('uid', 'title', 'adminusers', 'members')
            ->from('sys_workspace')
            ->orderBy('title')
            ->executeQuery();

        while ($workspace = $result->fetchAssociative()) {
            if ($backendUser->checkWorkspace($workspace)) {
                $availableWorkspaces[$workspace['uid']] = $workspace['title'];
            }
        }
        return $availableWorkspaces;
    }

    /**
     * Gets the current workspace ID.
     */
    public function getCurrentWorkspace(): int
    {
        return $this->getBackendUser()->workspace;
    }

    /**
     * easy function to just return the number of hours.
     *
     * a preview link is valid, based on the workspaces' custom value (default to 48 hours)
     * or falls back to the users' TSconfig value "options.workspaces.previewLinkTTLHours".
     *
     * by default, it's 48hs.
     *
     * @return int The hours as a number
     */
    public function getPreviewLinkLifetime(): int
    {
        $workspaceId = $this->getCurrentWorkspace();
        if ($workspaceId > 0) {
            $wsRecord = BackendUtility::getRecord('sys_workspace', $workspaceId, '*');
            if (($wsRecord['previewlink_lifetime'] ?? 0) > 0) {
                return (int)$wsRecord['previewlink_lifetime'];
            }
        }
        $ttlHours = (int)($this->getBackendUser()->getTSConfig()['options.']['workspaces.']['previewLinkTTLHours'] ?? 0);
        return $ttlHours ?: 24 * 2;
    }

    /**
     * Find the title for the requested workspace.
     *
     * @throws \InvalidArgumentException
     */
    public function getWorkspaceTitle(int $wsId): string
    {
        $title = false;
        switch ($wsId) {
            case self::LIVE_WORKSPACE_ID:
                $title = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:shortcut_onlineWS');
                break;
            default:
                $labelField = $GLOBALS['TCA']['sys_workspace']['ctrl']['label'];
                $wsRecord = BackendUtility::getRecord('sys_workspace', $wsId, 'uid,' . $labelField);
                if (is_array($wsRecord)) {
                    $title = (string)$wsRecord[$labelField];
                }
        }
        if ($title === false) {
            throw new \InvalidArgumentException('No such workspace defined', 1476045469);
        }
        return $title;
    }

    /**
     * Building DataHandler CMD-array for publishing all versions in a workspace.
     *
     * @param int $wsid Real workspace ID, cannot be ONLINE (zero).
     * @param bool $_ Unused, previously used to choose between swapping and publishing
     * @param int $pageId The page id
     * @param int|null $language Select specific language only
     * @return array Command array for DataHandler
     */
    public function getCmdArrayForPublishWS($wsid, $_ = false, $pageId = 0, $language = null)
    {
        $wsid = (int)$wsid;
        $cmd = [];
        if ($wsid > 0) {
            // Define stage to select:
            $stage = -99;
            $workspaceRec = BackendUtility::getRecord('sys_workspace', $wsid);
            if ($workspaceRec['publish_access'] & 1) {
                $stage = StagesService::STAGE_PUBLISH_ID;
            }
            // Select all versions to publishing
            $versions = $this->selectVersionsInWorkspace(
                $wsid,
                $stage,
                $pageId ?: -1,
                999,
                'tables_modify',
                $language
            );
            // Traverse the selection to build CMD array:
            foreach ($versions as $table => $records) {
                foreach ($records as $rec) {
                    // For new records, the live ID is the same as the version ID
                    $liveId = $rec['t3ver_oid'] ?: $rec['uid'];
                    $cmd[$table][$liveId]['version'] = ['action' => 'swap', 'swapWith' => $rec['uid']];
                }
            }
        }
        return $cmd;
    }

    /**
     * Building DataHandler CMD-array for releasing all versions in a workspace.
     *
     * @param int $wsid Real workspace ID, cannot be ONLINE (zero).
     * @param bool $flush Run Flush (TRUE) or ClearWSID (FALSE) command
     * @param int $pageId The page id
     * @param int $language Select specific language only
     * @return array Command array for DataHandler
     */
    public function getCmdArrayForFlushWS($wsid, $flush = true, $pageId = 0, $language = null)
    {
        $wsid = (int)$wsid;
        $cmd = [];
        if ($wsid > 0) {
            // Define stage to select:
            $stage = -99;
            // Select all versions to publish
            $versions = $this->selectVersionsInWorkspace(
                $wsid,
                $stage,
                $pageId ?: -1,
                999,
                'tables_modify',
                $language
            );
            // Traverse the selection to build CMD array:
            foreach ($versions as $table => $records) {
                foreach ($records as $rec) {
                    // Build the cmd Array:
                    $cmd[$table][$rec['uid']]['version'] = ['action' => $flush ? 'flush' : 'clearWSID'];
                }
            }
        }
        return $cmd;
    }

    /**
     * Select all records from workspace pending for publishing
     * Used from backend to display workspace overview
     * User for auto-publishing for selecting versions for publication
     *
     * @param int $wsid Workspace ID. If -99, will select ALL versions from ANY workspace. If -98 will select all but ONLINE. >=-1 will select from the actual workspace
     * @param int $stage Stage filter: -99 means no filtering, otherwise it will be used to select only elements with that stage. For publishing, that would be "10
     * @param int $pageId Page id: Live page for which to find versions in workspace!
     * @param int $recursionLevel Recursion Level - select versions recursive - parameter is only relevant if $pageId != -1
     * @param string $selectionType How to collect records for "listing" or "modify" these tables. Support the permissions of each type of record, see \TYPO3\CMS\Core\Authentication\BackendUserAuthentication::check.
     * @param int $language Select specific language only
     * @return array Array of all records uids etc. First key is table name, second key incremental integer. Records are associative arrays with uid and t3ver_oidfields. The pid of the online record is found as "livepid" the pid of the offline record is found in "wspid
     */
    public function selectVersionsInWorkspace($wsid, $stage = -99, $pageId = -1, $recursionLevel = 0, $selectionType = 'tables_select', $language = null)
    {
        $backendUser = $this->getBackendUser();
        $wsid = (int)$wsid;
        $output = [];
        // Contains either nothing or a list with live-uids
        if ($pageId != -1 && $recursionLevel > 0) {
            $pageList = $this->getTreeUids($pageId, $wsid, $recursionLevel);
        } elseif ($pageId != -1) {
            $pageList = (string)$pageId;
        } else {
            $pageList = '';
            // check if person may only see a "virtual" page-root
            $mountPoints = array_map('intval', $backendUser->returnWebmounts());
            $mountPoints = array_unique($mountPoints);
            if (!in_array(0, $mountPoints)) {
                $tempPageIds = [];
                foreach ($mountPoints as $mountPoint) {
                    $tempPageIds[] = $this->getTreeUids($mountPoint, $wsid, $recursionLevel);
                }
                $pageList = implode(',', $tempPageIds);
                $pageList = implode(',', array_unique(explode(',', $pageList)));
            }
        }
        // Traversing all tables supporting versioning:
        foreach ($GLOBALS['TCA'] as $table => $cfg) {
            // we do not collect records from tables without permissions on them.
            if (!$backendUser->check($selectionType, $table)) {
                continue;
            }
            if (BackendUtility::isTableWorkspaceEnabled($table)) {
                $recs = $this->selectAllVersionsFromPages($table, $pageList, $wsid, $stage, $language);
                $newRecords = $this->getNewVersionsForPages($table, $pageList, $wsid, (int)$stage, $language);
                foreach ($newRecords as &$newRecord) {
                    // If we're dealing with a 'new' record, this one has no t3ver_oid. On publish, there is no
                    // live counterpart, but the publish methods later need a live uid to publish to. We thus
                    // use the uid as t3ver_oid here to be transparent on javascript side.
                    $newRecord['t3ver_oid'] = $newRecord['uid'];
                }
                unset($newRecord);
                $moveRecs = $this->getMovedRecordsFromPages($table, $pageList, $wsid, $stage);
                $recs = array_merge($recs, $newRecords, $moveRecs);
                $recs = $this->filterPermittedElements($recs, $table);
                if (!empty($recs)) {
                    $output[$table] = $recs;
                }
            }
        }
        return $output;
    }

    /**
     * Find all versionized elements except moved and new records.
     *
     * @param string $table
     * @param string $pageList
     * @param int $wsid
     * @param int $stage
     * @param int $language
     * @return array
     */
    protected function selectAllVersionsFromPages($table, $pageList, $wsid, $stage, $language = null)
    {
        // Include root level page as there might be some records with where root level
        // restriction is ignored (e.g. FAL records)
        if ($pageList !== '' && BackendUtility::isRootLevelRestrictionIgnored($table)) {
            $pageList .= ',0';
        }
        $isTableLocalizable = BackendUtility::isTableLocalizable($table);
        $languageParentField = '';
        // If table is not localizable, but localized records shall
        // be collected, an empty result array needs to be returned:
        if ($isTableLocalizable === false && $language > 0) {
            return [];
        }
        if ($isTableLocalizable) {
            $languageParentField = 'A.' . $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $fields = ['A.uid', 'A.pid', 'A.t3ver_oid', 'A.t3ver_stage', 'B.pid', 'B.pid AS wspid', 'B.pid AS livepid'];
        if ($isTableLocalizable) {
            $fields[] = $languageParentField;
            $fields[] = 'A.' . $GLOBALS['TCA'][$table]['ctrl']['languageField'];
        }
        // Table A is the offline version and t3ver_oid>0 defines offline
        // Table B (online) must have t3ver_oid=0 to signify being online.
        $constraints = [
            $queryBuilder->expr()->gt(
                'A.t3ver_oid',
                $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
            ),
            $queryBuilder->expr()->eq(
                'B.t3ver_oid',
                $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
            ),
            $queryBuilder->expr()->neq(
                'A.t3ver_state',
                $queryBuilder->createNamedParameter(
                    (string)new VersionState(VersionState::MOVE_POINTER),
                    Connection::PARAM_INT
                )
            ),
        ];

        if ($pageList) {
            $pageIdRestriction = GeneralUtility::intExplode(',', $pageList, true);
            if ($table === 'pages') {
                $constraints[] = $queryBuilder->expr()->or(
                    $queryBuilder->expr()->in(
                        'B.uid',
                        $queryBuilder->createNamedParameter(
                            $pageIdRestriction,
                            Connection::PARAM_INT_ARRAY
                        )
                    ),
                    $queryBuilder->expr()->in(
                        'B.' . $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'],
                        $queryBuilder->createNamedParameter(
                            $pageIdRestriction,
                            Connection::PARAM_INT_ARRAY
                        )
                    )
                );
            } else {
                $constraints[] = $queryBuilder->expr()->in(
                    'B.pid',
                    $queryBuilder->createNamedParameter(
                        $pageIdRestriction,
                        Connection::PARAM_INT_ARRAY
                    )
                );
            }
        }

        if ($isTableLocalizable && MathUtility::canBeInterpretedAsInteger($language)) {
            $constraints[] = $queryBuilder->expr()->eq(
                'A.' . $GLOBALS['TCA'][$table]['ctrl']['languageField'],
                $queryBuilder->createNamedParameter($language, Connection::PARAM_INT)
            );
        }

        if ($wsid >= 0) {
            $constraints[] = $queryBuilder->expr()->eq(
                'A.t3ver_wsid',
                $queryBuilder->createNamedParameter($wsid, Connection::PARAM_INT)
            );
        }

        if ((int)$stage !== -99) {
            $constraints[] = $queryBuilder->expr()->eq(
                'A.t3ver_stage',
                $queryBuilder->createNamedParameter($stage, Connection::PARAM_INT)
            );
        }

        // ... and finally the join between the two tables.
        $constraints[] = $queryBuilder->expr()->eq('A.t3ver_oid', $queryBuilder->quoteIdentifier('B.uid'));

        // Select all records from this table in the database from the workspace
        // This joins the online version with the offline version as tables A and B
        // Order by UID, mostly to have a sorting in the backend overview module which
        // doesn't "jump around" when publishing.
        $rows = $queryBuilder->select(...$fields)
            ->from($table, 'A')
            ->from($table, 'B')
            ->where(...$constraints)
            ->orderBy('B.uid')
            ->executeQuery()
            ->fetchAllAssociative();

        return $rows;
    }

    /**
     * Find all versionized elements which are new (= do not have a live counterpart),
     * so this method does not need to have a JOIN SQL statement.
     */
    protected function getNewVersionsForPages(
        string $table,
        string $pageList,
        int $wsid,
        int $stage,
        ?int $language
    ): array {
        // Include root level page as there might be some records with where root level
        // restriction is ignored (e.g. FAL records)
        if ($pageList !== '' && BackendUtility::isRootLevelRestrictionIgnored($table)) {
            $pageList .= ',0';
        }
        $isTableLocalizable = BackendUtility::isTableLocalizable($table);
        // If table is not localizable, but localized records shall
        // be collected, an empty result array needs to be returned:
        if ($isTableLocalizable === false && $language > 0) {
            return [];
        }

        $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'] ?? '';
        $transOrigPointerField = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] ?? '';

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $fields = ['uid', 'pid', 't3ver_oid', 't3ver_state', 't3ver_stage', 'pid AS wspid', 'pid AS livepid'];

        // If the table is localizable, $languageField and $transOrigPointerField
        // are set and should be added to the query
        if ($isTableLocalizable) {
            $fields[] = $languageField;
            $fields[] = $transOrigPointerField;
        }

        $constraints = [
            $queryBuilder->expr()->eq(
                't3ver_state',
                $queryBuilder->createNamedParameter(
                    VersionState::NEW_PLACEHOLDER,
                    Connection::PARAM_INT
                )
            ),
        ];

        if ($pageList) {
            $pageIdRestriction = GeneralUtility::intExplode(',', $pageList, true);
            if ($table === 'pages' && $transOrigPointerField !== '') {
                $constraints[] = $queryBuilder->expr()->or(
                    $queryBuilder->expr()->in(
                        'uid',
                        $queryBuilder->createNamedParameter(
                            $pageIdRestriction,
                            Connection::PARAM_INT_ARRAY
                        )
                    ),
                    $queryBuilder->expr()->in(
                        $transOrigPointerField,
                        $queryBuilder->createNamedParameter(
                            $pageIdRestriction,
                            Connection::PARAM_INT_ARRAY
                        )
                    )
                );
            } else {
                $constraints[] = $queryBuilder->expr()->in(
                    'pid',
                    $queryBuilder->createNamedParameter(
                        $pageIdRestriction,
                        Connection::PARAM_INT_ARRAY
                    )
                );
            }
        }

        if ($isTableLocalizable && MathUtility::canBeInterpretedAsInteger($language)) {
            $constraints[] = $queryBuilder->expr()->eq(
                $languageField,
                $queryBuilder->createNamedParameter((int)$language, Connection::PARAM_INT)
            );
        }

        if ($wsid >= 0) {
            $constraints[] = $queryBuilder->expr()->eq(
                't3ver_wsid',
                $queryBuilder->createNamedParameter($wsid, Connection::PARAM_INT)
            );
        }

        if ($stage !== -99) {
            $constraints[] = $queryBuilder->expr()->eq(
                't3ver_stage',
                $queryBuilder->createNamedParameter($stage, Connection::PARAM_INT)
            );
        }

        // Select all records from this table in the database from the workspace
        // Order by UID, mostly to have a sorting in the backend overview module which
        // doesn't "jump around" when publishing.
        return $queryBuilder
            ->select(...$fields)
            ->from($table)
            ->where(...$constraints)
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * Find all moved records at their new position.
     *
     * @param string $table
     * @param string $pageList
     * @param int $wsid
     * @param int $stage
     * @return array
     */
    protected function getMovedRecordsFromPages($table, $pageList, $wsid, $stage)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        // Aliases:
        // B - online record
        // C - move pointer (t3ver_state = 4)
        $constraints = [
            $queryBuilder->expr()->eq(
                'B.t3ver_state',
                $queryBuilder->createNamedParameter(
                    (string)new VersionState(VersionState::DEFAULT_STATE),
                    Connection::PARAM_INT
                )
            ),
            $queryBuilder->expr()->eq(
                'B.t3ver_wsid',
                $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
            ),
            $queryBuilder->expr()->eq(
                'C.t3ver_state',
                $queryBuilder->createNamedParameter(
                    (string)new VersionState(VersionState::MOVE_POINTER),
                    Connection::PARAM_INT
                )
            ),
            $queryBuilder->expr()->eq('B.uid', $queryBuilder->quoteIdentifier('C.t3ver_oid')),
        ];

        if ($wsid >= 0) {
            $constraints[] = $queryBuilder->expr()->eq(
                'C.t3ver_wsid',
                $queryBuilder->createNamedParameter($wsid, Connection::PARAM_INT)
            );
        }

        if ((int)$stage !== -99) {
            $constraints[] = $queryBuilder->expr()->eq(
                'C.t3ver_stage',
                $queryBuilder->createNamedParameter($stage, Connection::PARAM_INT)
            );
        }

        if ($pageList) {
            $pageIdRestriction = GeneralUtility::intExplode(',', $pageList, true);
            if ($table === 'pages') {
                $constraints[] = $queryBuilder->expr()->or(
                    $queryBuilder->expr()->in(
                        'B.uid',
                        $queryBuilder->createNamedParameter(
                            $pageIdRestriction,
                            Connection::PARAM_INT_ARRAY
                        )
                    ),
                    $queryBuilder->expr()->in(
                        'C.pid',
                        $queryBuilder->createNamedParameter(
                            $pageIdRestriction,
                            Connection::PARAM_INT_ARRAY
                        )
                    ),
                    $queryBuilder->expr()->in(
                        'B.' . $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'],
                        $queryBuilder->createNamedParameter(
                            $pageIdRestriction,
                            Connection::PARAM_INT_ARRAY
                        )
                    )
                );
            } else {
                $constraints[] = $queryBuilder->expr()->in(
                    'C.pid',
                    $queryBuilder->createNamedParameter(
                        $pageIdRestriction,
                        Connection::PARAM_INT_ARRAY
                    )
                );
            }
        }

        $rows = $queryBuilder
            ->select('C.pid AS wspid', 'B.uid AS t3ver_oid', 'C.uid AS uid', 'B.pid AS livepid', 'C.t3ver_stage as t3ver_stage')
            ->from($table, 'B')
            ->from($table, 'C')
            ->where(...$constraints)
            ->orderBy('C.uid')
            ->executeQuery()
            ->fetchAllAssociative();

        return $rows;
    }

    /**
     * Find all page uids recursive starting from a specific page
     *
     * @param int $pageId
     * @param int $wsid
     * @param int $recursionLevel
     * @return string Comma sep. uid list
     */
    protected function getTreeUids($pageId, $wsid, $recursionLevel)
    {
        $backendUser = $this->getBackendUser();
        // Reusing existing functionality with the drawback that
        // mount points are not covered yet
        $permsClause = QueryHelper::stripLogicalOperatorPrefix(
            $backendUser->getPagePermsClause(Permission::PAGE_SHOW)
        );
        if ($pageId > 0) {
            $pageList = array_merge(
                [ (int)$pageId ],
                $this->getPageChildrenRecursive((int)$pageId, (int)$recursionLevel, 0, $permsClause)
            );
        } else {
            $mountPoints = $backendUser->uc['pageTree_temporaryMountPoint'];
            if (!is_array($mountPoints) || empty($mountPoints)) {
                $mountPoints = array_map('intval', $backendUser->returnWebmounts());
                $mountPoints = array_unique($mountPoints);
            }
            $pageList = [];
            foreach ($mountPoints as $mountPoint) {
                $pageList = array_merge(
                    $pageList,
                    [ (int)$mountPoint ],
                    $this->getPageChildrenRecursive((int)$mountPoint, (int)$recursionLevel, 0, $permsClause)
                );
            }
        }
        $pageList = array_unique($pageList);

        if (BackendUtility::isTableWorkspaceEnabled('pages') && !empty($pageList)) {
            // Remove the "subbranch" if a page was moved away
            $pageIds = $pageList;
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $result = $queryBuilder
                ->select('uid', 'pid', 't3ver_oid')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->in(
                        't3ver_oid',
                        $queryBuilder->createNamedParameter($pageIds, Connection::PARAM_INT_ARRAY)
                    ),
                    $queryBuilder->expr()->eq(
                        't3ver_wsid',
                        $queryBuilder->createNamedParameter($wsid, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        't3ver_state',
                        $queryBuilder->createNamedParameter(VersionState::MOVE_POINTER, Connection::PARAM_INT)
                    )
                )
                ->orderBy('uid')
                ->executeQuery();

            $movedAwayPages = [];
            while ($row = $result->fetchAssociative()) {
                $movedAwayPages[$row['t3ver_oid']] = $row;
            }

            // move all pages away
            $newList = array_diff($pageIds, array_keys($movedAwayPages));
            // keep current page in the list
            $newList[] = $pageId;
            // move back in if still connected to the "remaining" pages
            do {
                $changed = false;
                foreach ($movedAwayPages as $uid => $rec) {
                    if (in_array($rec['pid'], $newList) && !in_array($uid, $newList)) {
                        $newList[] = $uid;
                        $changed = true;
                    }
                }
            } while ($changed);

            // In case moving pages is enabled we need to replace all move-to pointer with their origin
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $result = $queryBuilder->select('uid', 't3ver_oid')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->in(
                        'uid',
                        $queryBuilder->createNamedParameter($newList, Connection::PARAM_INT_ARRAY)
                    )
                )
                ->orderBy('uid')
                ->executeQuery();

            $pages = [];
            while ($row = $result->fetchAssociative()) {
                $pages[$row['uid']] = $row;
            }

            $pageIds = $newList;
            if (!in_array($pageId, $pageIds)) {
                $pageIds[] = $pageId;
            }

            $newList = [];
            foreach ($pageIds as $pageId) {
                if ((int)$pages[$pageId]['t3ver_oid'] > 0) {
                    $newList[] = (int)$pages[$pageId]['t3ver_oid'];
                } else {
                    $newList[] = $pageId;
                }
            }
            $pageList = $newList;
        }

        return implode(',', $pageList);
    }

    /**
     * Recursively fetch all children of a given page
     *
     * @param int $pid uid of the page
     * @return int[] List of child row $uid's
     */
    protected function getPageChildrenRecursive(int $pid, int $depth, int $begin, string $permsClause): array
    {
        $children = [];
        if ($pid && $depth > 0) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $statement = $queryBuilder->select('uid')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('sys_language_uid', 0),
                    $permsClause
                )
                ->executeQuery();
            while ($row = $statement->fetchAssociative()) {
                if ($begin <= 0) {
                    $children[] = (int)$row['uid'];
                }
                if ($depth > 1) {
                    $theSubList = $this->getPageChildrenRecursive((int)$row['uid'], $depth - 1, $begin - 1, $permsClause);
                    $children = array_merge($children, $theSubList);
                }
            }
        }
        return $children;
    }

    /**
     * Remove all records which are not permitted for the user
     *
     * @param array $recs
     * @param string $table
     * @return array
     */
    protected function filterPermittedElements($recs, $table)
    {
        $permittedElements = [];
        if (is_array($recs)) {
            foreach ($recs as $rec) {
                if ($this->isPageAccessibleForCurrentUser($table, $rec) && $this->isLanguageAccessibleForCurrentUser($table, $rec)) {
                    $permittedElements[] = $rec;
                }
            }
        }
        return $permittedElements;
    }

    /**
     * Checking access to the page the record is on, respecting ignored root level restrictions
     *
     * @param string $table Name of the table
     * @param array $record Record row to be checked
     * @return bool
     */
    protected function isPageAccessibleForCurrentUser($table, array $record)
    {
        $pageIdField = $table === 'pages' ? 'uid' : 'wspid';
        $pageId = isset($record[$pageIdField]) ? (int)$record[$pageIdField] : null;
        if ($pageId === null) {
            return false;
        }
        if ($pageId === 0 && BackendUtility::isRootLevelRestrictionIgnored($table)) {
            return true;
        }
        $page = BackendUtility::getRecord('pages', $pageId, 'uid,pid,perms_userid,perms_user,perms_groupid,perms_group,perms_everybody');

        return $this->getBackendUser()->doesUserHaveAccess($page, Permission::PAGE_SHOW);
    }

    /**
     * Check current be users language access on given record.
     *
     * @param string $table Name of the table
     * @param array $record Record row to be checked
     * @return bool
     */
    protected function isLanguageAccessibleForCurrentUser($table, array $record)
    {
        if (BackendUtility::isTableLocalizable($table)) {
            $languageUid = $record[$GLOBALS['TCA'][$table]['ctrl']['languageField']] ?? 0;
        } else {
            return true;
        }
        return $this->getBackendUser()->checkLanguageAccess($languageUid);
    }

    /**
     * Determine whether a specific page is new and not yet available in the LIVE workspace
     *
     * @param int $id Primary key of the page to check
     * @param int $language Language for which to check the page
     */
    public function isNewPage($id, $language = 0): bool
    {
        $isNewPage = false;
        // If the language is not default, check state of overlay
        if ($language > 0) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $row = $queryBuilder->select('t3ver_state')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq(
                        $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'],
                        $queryBuilder->createNamedParameter($id, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        $GLOBALS['TCA']['pages']['ctrl']['languageField'],
                        $queryBuilder->createNamedParameter($language, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        't3ver_wsid',
                        $queryBuilder->createNamedParameter($this->getCurrentWorkspace(), Connection::PARAM_INT)
                    )
                )
                ->setMaxResults(1)
                ->executeQuery()
                ->fetchAssociative();

            if ($row !== false) {
                $isNewPage = VersionState::cast($row['t3ver_state'])->equals(VersionState::NEW_PLACEHOLDER);
            }
        } else {
            $rec = BackendUtility::getRecord('pages', $id, 't3ver_state');
            if (is_array($rec)) {
                $isNewPage = VersionState::cast($rec['t3ver_state'])->equals(VersionState::NEW_PLACEHOLDER);
            }
        }
        return $isNewPage;
    }

    /**
     * Determines whether a page has workspace versions.
     *
     * @param int $workspaceId
     * @param int $pageId
     * @return bool
     */
    public function hasPageRecordVersions($workspaceId, $pageId)
    {
        if ((int)$workspaceId === 0 || (int)$pageId === 0) {
            return false;
        }

        if (isset($this->versionsOnPageCache[$workspaceId][$pageId])) {
            return $this->versionsOnPageCache[$workspaceId][$pageId];
        }

        $this->versionsOnPageCache[$workspaceId][$pageId] = false;

        foreach ($GLOBALS['TCA'] as $tableName => $tableConfiguration) {
            if ($tableName === 'pages' || !BackendUtility::isTableWorkspaceEnabled($tableName)) {
                continue;
            }

            $pages = $this->fetchPagesWithVersionsInTable($workspaceId, $tableName);
            // Early break on first match
            if (!empty($pages[(string)$pageId])) {
                $this->versionsOnPageCache[$workspaceId][$pageId] = true;
                break;
            }
        }

        return $this->versionsOnPageCache[$workspaceId][$pageId];
    }

    /**
     * Gets all pages that have workspace versions per table.
     *
     * Result:
     * [
     *   'sys_template' => [],
     *   'tt_content' => [
     *     1 => true,
     *     11 => true,
     *     13 => true,
     *     15 => true
     *   ],
     *   'tx_something => [
     *     15 => true,
     *     11 => true,
     *     21 => true
     *   ],
     * ]
     *
     * @param int $workspaceId
     *
     * @return array
     */
    public function getPagesWithVersionsInTable($workspaceId)
    {
        foreach ($GLOBALS['TCA'] as $tableName => $tableConfiguration) {
            if ($tableName === 'pages' || !BackendUtility::isTableWorkspaceEnabled($tableName)) {
                continue;
            }

            $this->fetchPagesWithVersionsInTable($workspaceId, $tableName);
        }

        return $this->pagesWithVersionsInTable[$workspaceId];
    }

    /**
     * Gets all pages that have workspace versions in a particular table.
     *
     * Result:
     * [
     *   1 => true,
     *   11 => true,
     *   13 => true,
     *   15 => true
     * ],
     *
     * @param int $workspaceId
     * @param string $tableName
     * @return array
     */
    protected function fetchPagesWithVersionsInTable($workspaceId, $tableName)
    {
        if ((int)$workspaceId === 0) {
            return [];
        }

        if (!isset($this->pagesWithVersionsInTable[$workspaceId])) {
            $this->pagesWithVersionsInTable[$workspaceId] = [];
        }

        if (!isset($this->pagesWithVersionsInTable[$workspaceId][$tableName])) {
            $this->pagesWithVersionsInTable[$workspaceId][$tableName] = [];

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

            // Fetch all versioned record within a workspace
            $result = $queryBuilder
                ->select('pid')
                ->from($tableName)
                ->where(
                    $queryBuilder->expr()->or(
                        $queryBuilder->expr()->eq(
                            't3ver_state',
                            $queryBuilder->createNamedParameter(VersionState::NEW_PLACEHOLDER, Connection::PARAM_INT)
                        ),
                        $queryBuilder->expr()->gt(
                            't3ver_oid',
                            $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                        ),
                    ),
                    $queryBuilder->expr()->eq(
                        't3ver_wsid',
                        $queryBuilder->createNamedParameter(
                            $workspaceId,
                            Connection::PARAM_INT
                        )
                    )
                )
                ->groupBy('pid')
                ->executeQuery();

            $pageIds = [];
            while ($row = $result->fetchAssociative()) {
                $pageIds[$row['pid']] = true;
            }

            $this->pagesWithVersionsInTable[$workspaceId][$tableName] = $pageIds;
        }

        return $this->pagesWithVersionsInTable[$workspaceId][$tableName];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
