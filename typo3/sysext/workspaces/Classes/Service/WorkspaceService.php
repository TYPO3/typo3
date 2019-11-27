<?php
namespace TYPO3\CMS\Workspaces\Service;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\RootLevelRestriction;
use TYPO3\CMS\Core\Database\QueryView;
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

    const TABLE_WORKSPACE = 'sys_workspace';
    const SELECT_ALL_WORKSPACES = -98;
    const LIVE_WORKSPACE_ID = 0;

    /**
     * retrieves the available workspaces from the database and checks whether
     * they're available to the current BE user
     *
     * @return array array of workspaces available to the current user
     */
    public function getAvailableWorkspaces()
    {
        $availableWorkspaces = [];
        // add default workspaces
        if ($GLOBALS['BE_USER']->checkWorkspace(['uid' => (string)self::LIVE_WORKSPACE_ID])) {
            $availableWorkspaces[self::LIVE_WORKSPACE_ID] = self::getWorkspaceTitle(self::LIVE_WORKSPACE_ID);
        }
        // add custom workspaces (selecting all, filtering by BE_USER check):
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_workspace');
        $queryBuilder->getRestrictions()
            ->add(GeneralUtility::makeInstance(RootLevelRestriction::class));

        $result = $queryBuilder
            ->select('uid', 'title', 'adminusers', 'members')
            ->from('sys_workspace')
            ->orderBy('title')
            ->execute();

        while ($workspace = $result->fetch()) {
            if ($GLOBALS['BE_USER']->checkWorkspace($workspace)) {
                $availableWorkspaces[$workspace['uid']] = $workspace['title'];
            }
        }
        return $availableWorkspaces;
    }

    /**
     * Gets the current workspace ID.
     *
     * @return int The current workspace ID
     */
    public function getCurrentWorkspace()
    {
        $workspaceId = $GLOBALS['BE_USER']->workspace;
        $activeId = $GLOBALS['BE_USER']->getSessionData('tx_workspace_activeWorkspace');

        // Avoid invalid workspace settings
        if ($activeId !== null && $activeId !== self::SELECT_ALL_WORKSPACES) {
            $availableWorkspaces = $this->getAvailableWorkspaces();
            if (isset($availableWorkspaces[$activeId])) {
                $workspaceId = $activeId;
            }
        }

        return $workspaceId;
    }

    /**
     * Find the title for the requested workspace.
     *
     * @param int $wsId
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function getWorkspaceTitle($wsId)
    {
        $title = false;
        switch ($wsId) {
            case self::LIVE_WORKSPACE_ID:
                $title = static::getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:shortcut_onlineWS');
                break;
            default:
                $labelField = $GLOBALS['TCA']['sys_workspace']['ctrl']['label'];
                $wsRecord = BackendUtility::getRecord('sys_workspace', $wsId, 'uid,' . $labelField);
                if (is_array($wsRecord)) {
                    $title = $wsRecord[$labelField];
                }
        }
        if ($title === false) {
            throw new \InvalidArgumentException('No such workspace defined', 1476045469);
        }
        return $title;
    }

    /**
     * Building DataHandler CMD-array for swapping all versions in a workspace.
     *
     * @param int $wsid Real workspace ID, cannot be ONLINE (zero).
     * @param bool $doSwap If set, then the currently online versions are swapped into the workspace in exchange for the offline versions. Otherwise the workspace is emptied.
     * @param int $pageId The page id
     * @param int $language Select specific language only
     * @return array Command array for DataHandler
     */
    public function getCmdArrayForPublishWS($wsid, $doSwap, $pageId = 0, $language = null)
    {
        $wsid = (int)$wsid;
        $cmd = [];
        if ($wsid > 0) {
            // Define stage to select:
            $stage = -99;
            if ($wsid > 0) {
                $workspaceRec = BackendUtility::getRecord('sys_workspace', $wsid);
                if ($workspaceRec['publish_access'] & 1) {
                    $stage = StagesService::STAGE_PUBLISH_ID;
                }
            }
            // Select all versions to swap:
            $versions = $this->selectVersionsInWorkspace($wsid, 0, $stage, $pageId ?: -1, 999, 'tables_modify', $language);
            // Traverse the selection to build CMD array:
            foreach ($versions as $table => $records) {
                foreach ($records as $rec) {
                    // Build the cmd Array:
                    $cmd[$table][$rec['t3ver_oid']]['version'] = ['action' => 'swap', 'swapWith' => $rec['uid'], 'swapIntoWS' => $doSwap ? 1 : 0];
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
            // Select all versions to swap:
            $versions = $this->selectVersionsInWorkspace($wsid, 0, $stage, $pageId ?: -1, 999, 'tables_modify', $language);
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
     * @param int $filter Lifecycle filter: 1 = select all drafts (never-published), 2 = select all published one or more times (archive/multiple), anything else selects all.
     * @param int $stage Stage filter: -99 means no filtering, otherwise it will be used to select only elements with that stage. For publishing, that would be "10
     * @param int $pageId Page id: Live page for which to find versions in workspace!
     * @param int $recursionLevel Recursion Level - select versions recursive - parameter is only relevant if $pageId != -1
     * @param string $selectionType How to collect records for "listing" or "modify" these tables. Support the permissions of each type of record, see \TYPO3\CMS\Core\Authentication\BackendUserAuthentication::check.
     * @param int $language Select specific language only
     * @return array Array of all records uids etc. First key is table name, second key incremental integer. Records are associative arrays with uid and t3ver_oidfields. The pid of the online record is found as "livepid" the pid of the offline record is found in "wspid
     */
    public function selectVersionsInWorkspace($wsid, $filter = 0, $stage = -99, $pageId = -1, $recursionLevel = 0, $selectionType = 'tables_select', $language = null)
    {
        $wsid = (int)$wsid;
        $filter = (int)$filter;
        $output = [];
        // Contains either nothing or a list with live-uids
        if ($pageId != -1 && $recursionLevel > 0) {
            $pageList = $this->getTreeUids($pageId, $wsid, $recursionLevel);
        } elseif ($pageId != -1) {
            $pageList = $pageId;
        } else {
            $pageList = '';
            // check if person may only see a "virtual" page-root
            $mountPoints = array_map('intval', $GLOBALS['BE_USER']->returnWebmounts());
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
            if (!$GLOBALS['BE_USER']->check($selectionType, $table)) {
                continue;
            }
            if (BackendUtility::isTableWorkspaceEnabled($table)) {
                $recs = $this->selectAllVersionsFromPages($table, $pageList, $wsid, $filter, $stage, $language);
                $moveRecs = $this->getMoveToPlaceHolderFromPages($table, $pageList, $wsid, $filter, $stage);
                $recs = array_merge($recs, $moveRecs);
                $recs = $this->filterPermittedElements($recs, $table);
                if (!empty($recs)) {
                    $output[$table] = $recs;
                }
            }
        }
        return $output;
    }

    /**
     * Find all versionized elements except moved records.
     *
     * @param string $table
     * @param string $pageList
     * @param int $wsid
     * @param int $filter
     * @param int $stage
     * @param int $language
     * @return array
     */
    protected function selectAllVersionsFromPages($table, $pageList, $wsid, $filter, $stage, $language = null)
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
                $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
            ),
            $queryBuilder->expr()->eq(
                'B.t3ver_oid',
                $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
            ),
            $queryBuilder->expr()->neq(
                'A.t3ver_state',
                $queryBuilder->createNamedParameter(
                    (string)new VersionState(VersionState::MOVE_POINTER),
                    \PDO::PARAM_INT
                )
            )
        ];

        if ($pageList) {
            $pageIdRestriction = GeneralUtility::intExplode(',', $pageList, true);
            if ($table === 'pages') {
                $constraints[] = $queryBuilder->expr()->orX(
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
                $queryBuilder->createNamedParameter($language, \PDO::PARAM_INT)
            );
        }

        // For "real" workspace numbers, select by that.
        // If = -98, select all that are NOT online (zero).
        // Anything else below -1 will not select on the wsid and therefore select all!
        if ($wsid > self::SELECT_ALL_WORKSPACES) {
            $constraints[] = $queryBuilder->expr()->eq(
                'A.t3ver_wsid',
                $queryBuilder->createNamedParameter($wsid, \PDO::PARAM_INT)
            );
        } elseif ($wsid === self::SELECT_ALL_WORKSPACES) {
            $constraints[] = $queryBuilder->expr()->neq(
                'A.t3ver_wsid',
                $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
            );
        }

        // lifecycle filter:
        // 1 = select all drafts (never-published),
        // 2 = select all published one or more times (archive/multiple)
        if ($filter === 1) {
            $constraints[] = $queryBuilder->expr()->eq(
                'A.t3ver_count',
                $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
            );
        } elseif ($filter === 2) {
            $constraints[] = $queryBuilder->expr()->gt(
                'A.t3ver_count',
                $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
            );
        }

        if ((int)$stage !== -99) {
            $constraints[] = $queryBuilder->expr()->eq(
                'A.t3ver_stage',
                $queryBuilder->createNamedParameter($stage, \PDO::PARAM_INT)
            );
        }

        // ... and finally the join between the two tables.
        $constraints[] = $queryBuilder->expr()->eq('A.t3ver_oid', $queryBuilder->quoteIdentifier('B.uid'));

        // Select all records from this table in the database from the workspace
        // This joins the online version with the offline version as tables A and B
        // Order by UID, mostly to have a sorting in the backend overview module which
        // doesn't "jump around" when swapping.
        $rows = $queryBuilder->select(...$fields)
            ->from($table, 'A')
            ->from($table, 'B')
            ->where(...$constraints)
            ->orderBy('B.uid')
            ->execute()
            ->fetchAll();

        return $rows;
    }

    /**
     * Find all moved records at their new position.
     *
     * @param string $table
     * @param string $pageList
     * @param int $wsid
     * @param int $filter
     * @param int $stage
     * @return array
     */
    protected function getMoveToPlaceHolderFromPages($table, $pageList, $wsid, $filter, $stage)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        // Aliases:
        // A - moveTo placeholder
        // B - online record
        // C - moveFrom placeholder
        $constraints = [
            $queryBuilder->expr()->eq(
                'A.t3ver_state',
                $queryBuilder->createNamedParameter(
                    (string)new VersionState(VersionState::MOVE_PLACEHOLDER),
                    \PDO::PARAM_INT
                )
            ),
            $queryBuilder->expr()->gt(
                'B.pid',
                $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
            ),
            $queryBuilder->expr()->eq(
                'B.t3ver_state',
                $queryBuilder->createNamedParameter(
                    (string)new VersionState(VersionState::DEFAULT_STATE),
                    \PDO::PARAM_INT
                )
            ),
            $queryBuilder->expr()->eq(
                'B.t3ver_wsid',
                $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
            ),
            $queryBuilder->expr()->eq(
                'C.pid',
                $queryBuilder->createNamedParameter(-1, \PDO::PARAM_INT)
            ),
            $queryBuilder->expr()->eq(
                'C.t3ver_state',
                $queryBuilder->createNamedParameter(
                    (string)new VersionState(VersionState::MOVE_POINTER),
                    \PDO::PARAM_INT
                )
            ),
            $queryBuilder->expr()->eq('A.t3ver_move_id', $queryBuilder->quoteIdentifier('B.uid')),
            $queryBuilder->expr()->eq('B.uid', $queryBuilder->quoteIdentifier('C.t3ver_oid'))
        ];

        if ($wsid > self::SELECT_ALL_WORKSPACES) {
            $constraints[] = $queryBuilder->expr()->eq(
                'A.t3ver_wsid',
                $queryBuilder->createNamedParameter($wsid, \PDO::PARAM_INT)
            );
            $constraints[] = $queryBuilder->expr()->eq(
                'C.t3ver_wsid',
                $queryBuilder->createNamedParameter($wsid, \PDO::PARAM_INT)
            );
        } elseif ($wsid === self::SELECT_ALL_WORKSPACES) {
            $constraints[] = $queryBuilder->expr()->neq(
                'A.t3ver_wsid',
                $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
            );
            $constraints[] = $queryBuilder->expr()->neq(
                'C.t3ver_wsid',
                $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
            );
        }

        // lifecycle filter:
        // 1 = select all drafts (never-published),
        // 2 = select all published one or more times (archive/multiple)
        if ($filter === 1) {
            $constraints[] = $queryBuilder->expr()->eq(
                'C.t3ver_count',
                $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
            );
        } elseif ($filter === 2) {
            $constraints[] = $queryBuilder->expr()->gt(
                'C.t3ver_count',
                $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
            );
        }

        if ((int)$stage != -99) {
            $constraints[] = $queryBuilder->expr()->eq(
                'C.t3ver_stage',
                $queryBuilder->createNamedParameter($stage, \PDO::PARAM_INT)
            );
        }

        if ($pageList) {
            $pageIdRestriction = GeneralUtility::intExplode(',', $pageList, true);
            if ($table === 'pages') {
                $constraints[] = $queryBuilder->expr()->orX(
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
                    'A.pid',
                    $queryBuilder->createNamedParameter(
                        $pageIdRestriction,
                        Connection::PARAM_INT_ARRAY
                    )
                );
            }
        }

        $rows = $queryBuilder
            ->select('A.pid AS wspid', 'B.uid AS t3ver_oid', 'C.uid AS uid', 'B.pid AS livepid')
            ->from($table, 'A')
            ->from($table, 'B')
            ->from($table, 'C')
            ->where(...$constraints)
            ->orderBy('A.uid')
            ->execute()
            ->fetchAll();

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
        // Reusing existing functionality with the drawback that
        // mount points are not covered yet
        $perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(Permission::PAGE_SHOW);
        $searchObj = GeneralUtility::makeInstance(QueryView::class);
        if ($pageId > 0) {
            $pageList = $searchObj->getTreeList($pageId, $recursionLevel, 0, $perms_clause);
        } else {
            $mountPoints = $GLOBALS['BE_USER']->uc['pageTree_temporaryMountPoint'];
            if (!is_array($mountPoints) || empty($mountPoints)) {
                $mountPoints = array_map('intval', $GLOBALS['BE_USER']->returnWebmounts());
                $mountPoints = array_unique($mountPoints);
            }
            $newList = [];
            foreach ($mountPoints as $mountPoint) {
                $newList[] = $searchObj->getTreeList($mountPoint, $recursionLevel, 0, $perms_clause);
            }
            $pageList = implode(',', $newList);
        }
        unset($searchObj);

        if (BackendUtility::isTableWorkspaceEnabled('pages') && $pageList) {
            // Remove the "subbranch" if a page was moved away
            $pageIds = GeneralUtility::intExplode(',', $pageList, true);
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $result = $queryBuilder
                ->select('uid', 'pid', 't3ver_move_id')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->in(
                        't3ver_move_id',
                        $queryBuilder->createNamedParameter($pageIds, Connection::PARAM_INT_ARRAY)
                    ),
                    $queryBuilder->expr()->eq(
                        't3ver_wsid',
                        $queryBuilder->createNamedParameter($wsid, \PDO::PARAM_INT)
                    )
                )
                ->orderBy('uid')
                ->execute();

            $movedAwayPages = [];
            while ($row = $result->fetch()) {
                $movedAwayPages[$row['t3ver_move_id']] = $row;
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
            $result = $queryBuilder->select('uid', 't3ver_move_id')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->in(
                        'uid',
                        $queryBuilder->createNamedParameter($newList, Connection::PARAM_INT_ARRAY)
                    )
                )
                ->orderBy('uid')
                ->execute();

            $pages = [];
            while ($row = $result->fetch()) {
                $pages[$row['uid']] = $row;
            }

            $pageIds = $newList;
            if (!in_array($pageId, $pageIds)) {
                $pageIds[] = $pageId;
            }

            $newList = [];
            foreach ($pageIds as $pageId) {
                if ((int)$pages[$pageId]['t3ver_move_id'] > 0) {
                    $newList[] = (int)$pages[$pageId]['t3ver_move_id'];
                } else {
                    $newList[] = $pageId;
                }
            }
            $pageList = implode(',', $newList);
        }

        return $pageList;
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

        return $GLOBALS['BE_USER']->doesUserHaveAccess($page, 1);
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
            $languageUid = $record[$GLOBALS['TCA'][$table]['ctrl']['languageField']];
        } else {
            return true;
        }
        return $GLOBALS['BE_USER']->checkLanguageAccess($languageUid);
    }

    /**
     * Determine whether a specific page is new and not yet available in the LIVE workspace
     *
     * @param int $id Primary key of the page to check
     * @param int $language Language for which to check the page
     * @return bool
     */
    public static function isNewPage($id, $language = 0)
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
                        $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        $GLOBALS['TCA']['pages']['ctrl']['languageField'],
                        $queryBuilder->createNamedParameter($language, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        't3ver_wsid',
                        $queryBuilder->createNamedParameter($GLOBALS['BE_USER']->workspace, \PDO::PARAM_INT)
                    )
                )
                ->setMaxResults(1)
                ->execute()
                ->fetch();

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

        $parameters = [
            'workspaceId' => $workspaceId,
            'pageId' => $pageId,
            'versionsOnPageCache' => &$this->versionsOnPageCache,
        ];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Workspaces\Service\WorkspaceService::class]['hasPageRecordVersions'] ?? [] as $hookFunction) {
            GeneralUtility::callUserFunction($hookFunction, $parameters, $this);
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

            $movePointerParameter = $queryBuilder->createNamedParameter(
                VersionState::MOVE_POINTER,
                \PDO::PARAM_INT
            );
            $workspaceIdParameter = $queryBuilder->createNamedParameter(
                $workspaceId,
                \PDO::PARAM_INT
            );
            $onlineVersionParameter = $queryBuilder->createNamedParameter(
                0,
                \PDO::PARAM_INT
            );
            // create sub-queries, parameters are available for main query
            $versionQueryBuilder = $this->createQueryBuilderForTable($tableName)
                ->select('A.t3ver_oid')
                ->from($tableName, 'A')
                ->where(
                    $queryBuilder->expr()->gt('A.t3ver_oid', $onlineVersionParameter),
                    $queryBuilder->expr()->eq('A.t3ver_wsid', $workspaceIdParameter),
                    $queryBuilder->expr()->neq('A.t3ver_state', $movePointerParameter)
                );
            $movePointerQueryBuilder = $this->createQueryBuilderForTable($tableName)
                ->select('A.t3ver_oid')
                ->from($tableName, 'A')
                ->where(
                    $queryBuilder->expr()->gt('A.t3ver_oid', $onlineVersionParameter),
                    $queryBuilder->expr()->eq('A.t3ver_wsid', $workspaceIdParameter),
                    $queryBuilder->expr()->eq('A.t3ver_state', $movePointerParameter)
                );
            $subQuery = '%s IN (%s)';
            // execute main query
            $result = $queryBuilder
                ->select('B.pid AS pageId')
                ->from($tableName, 'B')
                ->orWhere(
                    sprintf(
                        $subQuery,
                        $queryBuilder->quoteIdentifier('B.uid'),
                        $versionQueryBuilder->getSQL()
                    ),
                    sprintf(
                        $subQuery,
                        $queryBuilder->quoteIdentifier('B.t3ver_move_id'),
                        $movePointerQueryBuilder->getSQL()
                    )
                )
                ->groupBy('B.pid')
                ->execute();

            $pageIds = [];
            while ($row = $result->fetch()) {
                $pageIds[$row['pageId']] = true;
            }

            $this->pagesWithVersionsInTable[$workspaceId][$tableName] = $pageIds;

            $parameters = [
                'workspaceId' => $workspaceId,
                'tableName' => $tableName,
                'pagesWithVersionsInTable' => &$this->pagesWithVersionsInTable,
            ];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Workspaces\Service\WorkspaceService::class]['fetchPagesWithVersionsInTable'] ?? [] as $hookFunction) {
                GeneralUtility::callUserFunction($hookFunction, $parameters, $this);
            }
        }

        return $this->pagesWithVersionsInTable[$workspaceId][$tableName];
    }

    /**
     * @param string $tableName
     * @return QueryBuilder
     */
    protected function createQueryBuilderForTable(string $tableName)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        return $queryBuilder;
    }

    /**
     * @return LanguageService|null
     */
    protected static function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'] ?? null;
    }
}
