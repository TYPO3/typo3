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

namespace TYPO3\CMS\Workspaces\Service;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\RootLevelRestriction;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Schema\Capability\LabelCapability;
use TYPO3\CMS\Core\Schema\Capability\RootLevelCapability;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * @internal
 *
 * @todo: This is public:true only because testing-framework uses GU::makeInstance() on it. Get rid of this.
 */
#[Autoconfigure(public: true)]
readonly class WorkspaceService
{
    public const TABLE_WORKSPACE = 'sys_workspace';
    public const LIVE_WORKSPACE_ID = 0;

    public const PUBLISH_ACCESS_ONLY_IN_PUBLISH_STAGE = 1;
    public const PUBLISH_ACCESS_ONLY_WORKSPACE_OWNERS = 2;
    public const PUBLISH_ACCESS_HIDE_ENTIRE_WORKSPACE_ACTION_DROPDOWN = 4;

    public function __construct(
        private TcaSchemaFactory $tcaSchemaFactory,
        private ConnectionPool $connectionPool,
        private ResourceFactory $resourceFactory,
    ) {}

    /**
     * Retrieves the available workspaces from the database and checks whether
     * they're available to the current BE user
     *
     * @return array array of workspaces available to the current user
     */
    public function getAvailableWorkspaces(): array
    {
        $backendUser = $this->getBackendUser();
        $availableWorkspaces = [];
        // add default workspaces
        if ($backendUser->checkWorkspace(self::LIVE_WORKSPACE_ID)) {
            $availableWorkspaces[self::LIVE_WORKSPACE_ID] = $this->getWorkspaceTitle(self::LIVE_WORKSPACE_ID);
        }
        // add custom workspaces (selecting all, filtering by BE_USER check):
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_WORKSPACE);
        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(RootLevelRestriction::class));
        $result = $queryBuilder
            ->select('uid', 'title', 'adminusers', 'members')
            ->from(self::TABLE_WORKSPACE)
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
     * Find the title for the requested workspace.
     */
    public function getWorkspaceTitle(int $wsId): string
    {
        $title = false;
        switch ($wsId) {
            case self::LIVE_WORKSPACE_ID:
                $title = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:shortcut_onlineWS');
                break;
            default:
                $schema = $this->tcaSchemaFactory->get(self::TABLE_WORKSPACE);
                /** @var LabelCapability $labelCapability */
                $labelCapability = $schema->getCapability(TcaSchemaCapability::Label);
                $labelField = $labelCapability->getPrimaryFieldName();
                $wsRecord = BackendUtility::getRecord(self::TABLE_WORKSPACE, $wsId, 'uid,' . $labelField);
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
     * @param bool $_ Dummy parameter, only there because testing framework is calling it with 2 parameters
     * @param int|null $language Select specific language only
     * @return array Command array for DataHandler
     */
    public function getCmdArrayForPublishWS(int $wsid, bool $_ = false, ?int $language = null): array
    {
        $cmd = [];
        if ($wsid > 0) {
            // Define stage to select:
            $stage = -99;
            $workspaceRec = BackendUtility::getRecord(self::TABLE_WORKSPACE, $wsid);
            if ($workspaceRec['publish_access'] & self::PUBLISH_ACCESS_ONLY_IN_PUBLISH_STAGE) {
                $stage = StagesService::STAGE_PUBLISH_ID;
            }
            // Select all versions to publishing
            $versions = $this->selectVersionsInWorkspace(
                $wsid,
                $stage,
                -1,
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
     * @param int|null $language Select specific language only
     * @return array Command array for DataHandler
     */
    public function getCmdArrayForFlushWS(int $wsid, ?int $language = null)
    {
        $cmd = [];
        if ($wsid > 0) {
            // Define stage to select:
            $stage = -99;
            // Select all versions to publish
            $versions = $this->selectVersionsInWorkspace(
                $wsid,
                $stage,
                -1,
                999,
                'tables_modify',
                $language
            );
            // Traverse the selection to build CMD array:
            foreach ($versions as $table => $records) {
                foreach ($records as $rec) {
                    $cmd[$table][$rec['uid']]['version'] = ['action' => 'flush'];
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
     * @param int|null $language Select specific language only
     * @return array Array of all records uids etc. First key is table name, second key incremental integer. Records are associative arrays with uid and t3ver_oidfields. The pid of the online record is found as "livepid" the pid of the offline record is found in "wspid
     */
    public function selectVersionsInWorkspace(int $wsid, int $stage = -99, int $pageId = -1, int $recursionLevel = 0, string $selectionType = 'tables_select', ?int $language = null): array
    {
        $backendUser = $this->getBackendUser();
        $output = [];
        // Contains either nothing or a list with live-uids
        if ($pageId != -1 && $recursionLevel > 0) {
            $pageList = $this->getTreeUids($pageId, $wsid, $recursionLevel);
        } elseif ($pageId != -1) {
            $pageList = (string)$pageId;
        } else {
            $pageList = '';
            // check if person may only see a "virtual" page-root
            $mountPoints = $backendUser->getWebmounts();
            if (!in_array(0, $mountPoints)) {
                $tempPageIds = [];
                foreach ($mountPoints as $mountPoint) {
                    $tempPageIds[] = $this->getTreeUids($mountPoint, $wsid, $recursionLevel);
                }
                $pageList = implode(',', $tempPageIds);
                $pageList = implode(',', array_unique(explode(',', $pageList)));
            }
        }
        // Traversing all tables supporting versioning
        foreach ($this->tcaSchemaFactory->all() as $schema) {
            if (!$schema->isWorkspaceAware()) {
                continue;
            }
            $table = $schema->getName();
            // we do not collect records from tables without permissions on them.
            if (!$backendUser->check($selectionType, $table)) {
                continue;
            }
            $recs = $this->selectAllVersionsFromPages($schema, $pageList, $wsid, $stage, $language);
            $newRecords = $this->getNewVersionsForPages($schema, $pageList, $wsid, $stage, $language);
            foreach ($newRecords as &$newRecord) {
                // If we're dealing with a 'new' record, this one has no t3ver_oid. On publish, there is no
                // live counterpart, but the publish methods later need a live uid to publish to. We thus
                // use the uid as t3ver_oid here to be transparent on javascript side.
                $newRecord['t3ver_oid'] = $newRecord['uid'];
            }
            unset($newRecord);
            $moveRecs = $this->getMovedRecordsFromPages($schema, $pageList, $wsid, $stage);
            $recs = array_merge($recs, $newRecords, $moveRecs);
            $recs = $this->filterPermittedElements($recs, $table);
            if (!empty($recs)) {
                $output[$table] = $recs;
            }
        }
        return $output;
    }

    /**
     * Find all versionized elements except moved and new records.
     */
    protected function selectAllVersionsFromPages(TcaSchema $schema, string $pageList, int $wsid, int $stage, ?int $language = null): array
    {
        // Include root level page as there might be some records with where root level
        // restriction is ignored (e.g. FAL records)
        /** @var RootLevelCapability $capability */
        $capability = $schema->getCapability(TcaSchemaCapability::RestrictionRootLevel);
        if ($pageList !== '' && $capability->shallIgnoreRootLevelRestriction()) {
            $pageList .= ',0';
        }
        // If table is not localizable, but localized records shall
        // be collected, an empty result array needs to be returned:
        if (!$schema->isLanguageAware() && $language > 0) {
            return [];
        }
        $table = $schema->getName();
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $fields = ['A.uid', 'A.pid', 'A.t3ver_oid', 'A.t3ver_stage', 'B.pid', 'B.pid AS wspid', 'B.pid AS livepid'];
        if ($schema->isLanguageAware()) {
            $fields[] = 'A.' . $schema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName();
            $fields[] = 'A.' . $schema->getCapability(TcaSchemaCapability::Language)->getTranslationOriginPointerField()->getName();
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
                    VersionState::MOVE_POINTER->value,
                    Connection::PARAM_INT
                )
            ),
        ];

        if ($pageList) {
            $pageIdRestriction = GeneralUtility::intExplode(',', $pageList, true);
            if ($table === 'pages' && $schema->isLanguageAware()) {
                $translationParentField = $schema->getCapability(TcaSchemaCapability::Language)->getTranslationOriginPointerField()->getName();
                $constraints[] = $queryBuilder->expr()->or(
                    $queryBuilder->expr()->in(
                        'B.uid',
                        $queryBuilder->createNamedParameter(
                            $pageIdRestriction,
                            Connection::PARAM_INT_ARRAY
                        )
                    ),
                    $queryBuilder->expr()->in(
                        'B.' . $translationParentField,
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

        if ($schema->isLanguageAware() && MathUtility::canBeInterpretedAsInteger($language)) {
            $constraints[] = $queryBuilder->expr()->eq(
                'A.' . $schema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName(),
                $queryBuilder->createNamedParameter($language, Connection::PARAM_INT)
            );
        }

        if ($wsid >= 0) {
            $constraints[] = $queryBuilder->expr()->eq(
                'A.t3ver_wsid',
                $queryBuilder->createNamedParameter($wsid, Connection::PARAM_INT)
            );
        }

        if ($stage !== -99) {
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
        TcaSchema $schema,
        string $pageList,
        int $wsid,
        int $stage,
        ?int $language
    ): array {
        // Include root level page as there might be some records with where root level
        // restriction is ignored (e.g. FAL records)
        /** @var RootLevelCapability $capability */
        $capability = $schema->getCapability(TcaSchemaCapability::RestrictionRootLevel);
        if ($pageList !== '' && $capability->shallIgnoreRootLevelRestriction()) {
            $pageList .= ',0';
        }
        // If table is not localizable, but localized records shall
        // be collected, an empty result array needs to be returned:
        if (!$schema->isLanguageAware() && $language > 0) {
            return [];
        }

        $languageField = $schema->isLanguageAware() ? $schema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName() : '';
        $transOrigPointerField = $schema->isLanguageAware() ? $schema->getCapability(TcaSchemaCapability::Language)->getTranslationOriginPointerField()->getName() : '';

        $table = $schema->getName();
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $fields = ['uid', 'pid', 't3ver_oid', 't3ver_state', 't3ver_stage', 'pid AS wspid', 'pid AS livepid'];

        // If the table is localizable, $languageField and $transOrigPointerField
        // are set and should be added to the query
        if ($schema->isLanguageAware()) {
            $fields[] = $languageField;
            $fields[] = $transOrigPointerField;
        }

        $constraints = [
            $queryBuilder->expr()->eq(
                't3ver_state',
                $queryBuilder->createNamedParameter(
                    VersionState::NEW_PLACEHOLDER->value,
                    Connection::PARAM_INT
                )
            ),
        ];

        if ($pageList) {
            $pageIdRestriction = GeneralUtility::intExplode(',', $pageList, true);
            if ($table === 'pages' && $schema->isLanguageAware()) {
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

        if ($schema->isLanguageAware() && MathUtility::canBeInterpretedAsInteger($language)) {
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
     */
    protected function getMovedRecordsFromPages(TcaSchema $schema, string $pageList, int $wsid, int $stage): array
    {
        $table = $schema->getName();
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        // Aliases:
        // B - online record
        // C - move pointer (t3ver_state = 4)
        $constraints = [
            $queryBuilder->expr()->eq(
                'B.t3ver_state',
                $queryBuilder->createNamedParameter(
                    VersionState::DEFAULT_STATE->value,
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
                    VersionState::MOVE_POINTER->value,
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

        if ($stage !== -99) {
            $constraints[] = $queryBuilder->expr()->eq(
                'C.t3ver_stage',
                $queryBuilder->createNamedParameter($stage, Connection::PARAM_INT)
            );
        }

        if ($pageList) {
            $pageIdRestriction = GeneralUtility::intExplode(',', $pageList, true);
            if ($table === 'pages' && $schema->isLanguageAware()) {
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
                        'B.' . $schema->getCapability(TcaSchemaCapability::Language)->getTranslationOriginPointerField()->getName(),
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
     * @return string Comma sep. uid list
     */
    protected function getTreeUids(int $pageId, int $wsid, int $recursionLevel): string
    {
        $backendUser = $this->getBackendUser();
        // Reusing existing functionality with the drawback that
        // mount points are not covered yet
        $permsClause = QueryHelper::stripLogicalOperatorPrefix(
            $backendUser->getPagePermsClause(Permission::PAGE_SHOW)
        );
        if ($pageId > 0) {
            $pageList = array_merge(
                [$pageId],
                $this->getPageChildrenRecursive($pageId, $recursionLevel, 0, $permsClause)
            );
        } else {
            $mountPoints = $backendUser->uc['pageTree_temporaryMountPoint'];
            if (!is_array($mountPoints) || empty($mountPoints)) {
                $mountPoints = $backendUser->getWebmounts();
            }
            $pageList = [];
            foreach ($mountPoints as $mountPoint) {
                $pageList = array_merge(
                    $pageList,
                    [ (int)$mountPoint ],
                    $this->getPageChildrenRecursive((int)$mountPoint, $recursionLevel, 0, $permsClause)
                );
            }
        }
        $pageList = array_unique($pageList);

        if ($this->tcaSchemaFactory->get('pages')->isWorkspaceAware() && !empty($pageList)) {
            // Remove the "subbranch" if a page was moved away
            $pageIds = $pageList;
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
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
                        $queryBuilder->createNamedParameter(VersionState::MOVE_POINTER->value, Connection::PARAM_INT)
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
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
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
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
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
     */
    protected function filterPermittedElements(array $recs, string $table): array
    {
        $permittedElements = [];
        foreach ($recs as $rec) {
            if ($this->isPageAccessibleForCurrentUser($table, $rec)
                && $this->isLanguageAccessibleForCurrentUser($table, $rec)
                && $this->isFileAccessibleForCurrentUser($table, $rec)
            ) {
                $permittedElements[] = $rec;
            }
        }
        return $permittedElements;
    }

    /**
     * Check if the current backend user has access to the file record.
     */
    protected function isFileAccessibleForCurrentUser(string $table, array $record): bool
    {
        if ($table !== 'sys_file_reference' && $table !== 'sys_file_metadata' && $table !== 'sys_file') {
            return true;
        }

        $dbRecord = BackendUtility::getRecord($table, $record['uid']);

        $fileId = 0;

        if ($table === 'sys_file_reference') {
            $fileId = (int)$dbRecord['uid_local'];
        } elseif ($table === 'sys_file_metadata') {
            $fileId = (int)$dbRecord['file'];
        } elseif ($table === 'sys_file') {
            $fileId = (int)$dbRecord['uid'];
        }

        if ($fileId === 0) {
            return true;
        }

        try {
            $fileObject = $this->resourceFactory->getFileObject($fileId);
        } catch (FileDoesNotExistException) {
            return false;
        }

        return $fileObject->getStorage()->checkFileActionPermission('write', $fileObject);
    }

    /**
     * Checking access to the page the record is on, respecting ignored root level restrictions
     *
     * @param string $table Name of the table
     * @param array $record Record row to be checked
     */
    protected function isPageAccessibleForCurrentUser(string $table, array $record): bool
    {
        $pageIdField = $table === 'pages' ? 'uid' : 'wspid';
        $pageId = isset($record[$pageIdField]) ? (int)$record[$pageIdField] : null;
        if ($pageId === null) {
            return false;
        }
        $schema = $this->tcaSchemaFactory->get($table);
        /** @var RootLevelCapability $capability */
        $capability = $schema->getCapability(TcaSchemaCapability::RestrictionRootLevel);
        if ($pageId === 0 && $capability->shallIgnoreRootLevelRestriction()) {
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
     */
    protected function isLanguageAccessibleForCurrentUser(string $table, array $record): bool
    {
        $schema = $this->tcaSchemaFactory->get($table);
        if ($schema->isLanguageAware()) {
            $languageField = $schema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName();
            $languageUid = $record[$languageField] ?? 0;
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
    public function isNewPage(int $id, int $language = 0): bool
    {
        $isNewPage = false;
        // If the language is not default, check state of overlay
        if ($language > 0) {
            $schema = $this->tcaSchemaFactory->get('pages');
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $row = $queryBuilder->select('t3ver_state')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq(
                        $schema->getCapability(TcaSchemaCapability::Language)->getTranslationOriginPointerField()->getName(),
                        $queryBuilder->createNamedParameter($id, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        $schema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName(),
                        $queryBuilder->createNamedParameter($language, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        't3ver_wsid',
                        $queryBuilder->createNamedParameter($this->getBackendUser()->workspace, Connection::PARAM_INT)
                    )
                )
                ->setMaxResults(1)
                ->executeQuery()
                ->fetchAssociative();

            if ($row !== false) {
                $isNewPage = VersionState::tryFrom($row['t3ver_state'] ?? 0) === VersionState::NEW_PLACEHOLDER;
            }
        } else {
            $rec = BackendUtility::getRecord('pages', $id, 't3ver_state');
            if (is_array($rec)) {
                $isNewPage = VersionState::tryFrom($rec['t3ver_state'] ?? 0) === VersionState::NEW_PLACEHOLDER;
            }
        }
        return $isNewPage;
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
