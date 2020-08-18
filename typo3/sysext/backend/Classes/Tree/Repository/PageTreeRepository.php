<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Tree\Repository;

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

use Doctrine\DBAL\Connection;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\DataHandling\PlainDataResolver;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Fetches ALL pages in the page tree, possibly overlaid with the workspace
 * in a sorted way.
 *
 * This works agnostic of the Backend User, allows to be used in FE as well in the future.
 *
 * @internal this class is not public API yet, as it needs to be proven stable enough first.
 */
class PageTreeRepository
{
    /**
     * Fields to be queried from the database
     *
     * @var string[]
     */
    protected $fields = [
        'uid',
        'pid',
        'sorting',
        'starttime',
        'endtime',
        'hidden',
        'fe_group',
        'title',
        'nav_title',
        'nav_hide',
        'alias',
        'php_tree_stop',
        'doktype',
        'is_siteroot',
        'module',
        'extendToSubpages',
        'content_from_pid',
        't3ver_oid',
        't3ver_id',
        't3ver_wsid',
        't3ver_label',
        't3ver_state',
        't3ver_stage',
        't3ver_tstamp',
        't3ver_move_id',
        'perms_userid',
        'perms_user',
        'perms_groupid',
        'perms_group',
        'perms_everybody',
        'mount_pid',
        'shortcut',
        'shortcut_mode',
        'mount_pid_ol',
        'url',
        'sys_language_uid',
        'l10n_parent',
    ];

    /**
     * The workspace ID to operate on
     *
     * @var int
     */
    protected $currentWorkspace = 0;

    /**
     * Full page tree when selected without permissions applied.
     *
     * @var array
     */
    protected $fullPageTree = [];

    /**
     * @var array
     */
    protected $additionalQueryRestrictions = [];

    /**
     * @param int $workspaceId the workspace ID to be checked for.
     * @param array $additionalFieldsToQuery an array with more fields that should be accessed.
     * @param array $additionalQueryRestrictions an array with more restrictions to add
     */
    public function __construct(int $workspaceId = 0, array $additionalFieldsToQuery = [], array $additionalQueryRestrictions = [])
    {
        $this->currentWorkspace = $workspaceId;
        if (!empty($additionalFieldsToQuery)) {
            $this->fields = array_merge($this->fields, $additionalFieldsToQuery);
        }

        if (!empty($additionalQueryRestrictions)) {
            $this->additionalQueryRestrictions = $additionalQueryRestrictions;
        }
    }

    /**
     * Main entry point for this repository, to fetch the tree data for a page.
     * Basically the page record, plus all child pages and their child pages recursively, stored within "_children" item.
     *
     * @param int $entryPoint the page ID to fetch the tree for
     * @param callable $callback a callback to be used to check for permissions and filter out pages not to be included.
     * @param array $dbMounts
     * @param bool $resolveUserPermissions
     * @return array
     */
    public function getTree(
        int $entryPoint,
        callable $callback = null,
        array $dbMounts = [],
        $resolveUserPermissions = false
    ): array {
        $this->fetchAllPages($dbMounts, $resolveUserPermissions);
        if ($entryPoint === 0) {
            $tree = $this->fullPageTree;
        } else {
            $tree = $this->findInPageTree($entryPoint, $this->fullPageTree);
        }
        if (!empty($tree) && $callback !== null) {
            $this->applyCallbackToChildren($tree, $callback);
        }
        return $tree;
    }

    /**
     * Removes items from a tree based on a callback, usually used for permission checks
     *
     * @param array $tree
     * @param callable $callback
     */
    protected function applyCallbackToChildren(array &$tree, callable $callback)
    {
        if (!isset($tree['_children'])) {
            return;
        }
        foreach ($tree['_children'] as $k => &$childPage) {
            if (!call_user_func_array($callback, [$childPage])) {
                unset($tree['_children'][$k]);
                continue;
            }
            $this->applyCallbackToChildren($childPage, $callback);
        }
    }

    /**
     * Get the page tree based on a given page record and a given depth
     *
     * @param array $pageTree The page record of the top level page you want to get the page tree of
     * @param int $depth Number of levels to fetch
     * @return array An array with page records and their children
     */
    public function getTreeLevels(array $pageTree, int $depth): array
    {
        $parentPageIds = [$pageTree['uid']];
        $groupedAndSortedPagesByPid = [];
        for ($i = 0; $i < $depth; $i++) {
            if (empty($parentPageIds)) {
                break;
            }
            $pageRecords = $this->getChildPageRecords($parentPageIds);

            $groupedAndSortedPagesByPid = $this->groupAndSortPages($pageRecords, $groupedAndSortedPagesByPid);

            $parentPageIds = array_column($pageRecords, 'uid');
        }
        $this->addChildrenToPage($pageTree, $groupedAndSortedPagesByPid);
        return $pageTree;
    }

    /**
     * Retrieve the page records based on the given parent page ids
     *
     * @param array $parentPageIds
     * @return array
     */
    protected function getChildPageRecords(array $parentPageIds): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->currentWorkspace));

        if (!empty($this->additionalQueryRestrictions)) {
            foreach ($this->additionalQueryRestrictions as $additionalQueryRestriction) {
                $queryBuilder->getRestrictions()->add($additionalQueryRestriction);
            }
        }

        $pageRecords = $queryBuilder
            ->select(...$this->fields)
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                $queryBuilder->expr()->in('pid', $queryBuilder->createNamedParameter($parentPageIds, Connection::PARAM_INT_ARRAY))
            )
            ->andWhere(
                QueryHelper::stripLogicalOperatorPrefix($GLOBALS['BE_USER']->getPagePermsClause(Permission::PAGE_SHOW))
            )
            ->execute()
            ->fetchAll();

        // This is necessary to resolve all IDs in a workspace
        if ($this->currentWorkspace !== 0 && !empty($pageRecords)) {
            $livePagePids = [];
            $livePageIds = [];
            $movePlaceholderData = [];
            foreach ($pageRecords as $pageRecord) {
                $livePageIds[] = (int)$pageRecord['uid'];
                $livePagePids[(int)$pageRecord['uid']] = (int)$pageRecord['pid'];
                if ((int)$pageRecord['t3ver_state'] === VersionState::MOVE_PLACEHOLDER) {
                    $movePlaceholderData[$pageRecord['t3ver_move_id']] = [
                        'pid' => (int)$pageRecord['pid'],
                        'sorting' => (int)$pageRecord['sorting']
                    ];
                }
            }

            // Resolve placeholders of workspace versions
            $resolver = GeneralUtility::makeInstance(
                PlainDataResolver::class,
                'pages',
                $livePageIds
            );
            $resolver->setWorkspaceId($this->currentWorkspace);
            $resolver->setKeepDeletePlaceholder(false);
            $resolver->setKeepMovePlaceholder(false);
            $resolver->setKeepLiveIds(false);
            $recordIds = $resolver->get();

            if (!empty($recordIds)) {
                $queryBuilder->getRestrictions()->removeAll();
                $pageRecords = $queryBuilder
                    ->select(...$this->fields)
                    ->from('pages')
                    ->where(
                        $queryBuilder->expr()->in('uid', $queryBuilder->createNamedParameter($recordIds, Connection::PARAM_INT_ARRAY))
                    )
                    ->execute()
                    ->fetchAll();

                foreach ($pageRecords as &$pageRecord) {
                    if ((int)$pageRecord['t3ver_state'] === VersionState::MOVE_PLACEHOLDER) {
                        $liveRecord = BackendUtility::getRecord('pages', $pageRecord['t3ver_move_id']);
                        $pageRecord['uid'] = (int)$liveRecord['uid'];
                        $pageRecord['t3ver_oid'] = (int)$pageRecord['t3ver_move_id'];
                        $pageRecord['title'] = $liveRecord['title'];
                    } elseif ((int)$pageRecord['t3ver_state'] === VersionState::MOVE_POINTER && !empty($movePlaceholderData[$pageRecord['t3ver_oid']])) {
                        $pageRecord['uid'] = $pageRecord['t3ver_oid'];
                        $pageRecord['sorting'] = (int)$movePlaceholderData[$pageRecord['t3ver_oid']]['sorting'];
                        $pageRecord['t3ver_state'] = VersionState::MOVE_PLACEHOLDER;
                        $pageRecord['pid'] = (int)$movePlaceholderData[$pageRecord['t3ver_oid']]['pid'];
                    } elseif ((int)$pageRecord['t3ver_oid'] > 0) {
                        $liveRecord = BackendUtility::getRecord('pages', $pageRecord['t3ver_oid']);
                        $pageRecord['sorting'] = (int)$liveRecord['sorting'];
                        $pageRecord['uid'] = (int)$liveRecord['uid'];
                        $pageRecord['pid'] = (int)$liveRecord['pid'];
                    }
                }
            } else {
                $pageRecords = [];
            }
        }
        foreach ($pageRecords as &$pageRecord) {
            $pageRecord['uid'] = (int)$pageRecord['uid'];
        }

        return $pageRecords;
    }

    public function hasChildren(int $pid): bool
    {
        $pageRecords = $this->getChildPageRecords([$pid]);
        return !empty($pageRecords);
    }

    /**
     * Fetch all non-deleted pages, regardless of permissions. That's why it's internal.
     *
     * @param array $dbMounts
     * @param bool $resolveUserPermissions
     * @return array the full page tree of the whole installation
     */
    protected function fetchAllPages(array $dbMounts, bool $resolveUserPermissions = false): array
    {
        if (!empty($this->fullPageTree)) {
            return $this->fullPageTree;
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->currentWorkspace));

        if (!empty($this->additionalQueryRestrictions)) {
            foreach ($this->additionalQueryRestrictions as $additionalQueryRestriction) {
                $queryBuilder->getRestrictions()->add($additionalQueryRestriction);
            }
        }

        $query = $queryBuilder
            ->select(...$this->fields)
            ->from('pages')
            ->where(
            // Only show records in default language
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
            );

        if ($resolveUserPermissions) {
            $query->andWhere(
                QueryHelper::stripLogicalOperatorPrefix($GLOBALS['BE_USER']->getPagePermsClause(Permission::PAGE_SHOW))
            );
        }

        $pageRecords = $query->execute()->fetchAll();

        $ids = array_column($pageRecords, 'uid');
        foreach ($dbMounts as $mount) {
            $entryPointRootLine = BackendUtility::BEgetRootLine($mount, '', false, $this->fields);
            foreach ($entryPointRootLine as $page) {
                $pageId = (int)$page['uid'];
                if (in_array($pageId, $ids) || $pageId === 0) {
                    continue;
                }
                $pageRecords[] = $page;
                $ids[] = $pageId;
            }
        }

        $livePagePids = [];
        $movePlaceholderData = [];
        // This is necessary to resolve all IDs in a workspace
        if ($this->currentWorkspace !== 0 && !empty($pageRecords)) {
            $livePageIds = [];
            foreach ($pageRecords as $pageRecord) {
                $livePageIds[] = (int)$pageRecord['uid'];
                $livePagePids[(int)$pageRecord['uid']] = (int)$pageRecord['pid'];
                if ((int)$pageRecord['t3ver_state'] === VersionState::MOVE_PLACEHOLDER) {
                    $movePlaceholderData[$pageRecord['t3ver_move_id']] = [
                        'pid' => (int)$pageRecord['pid'],
                        'sorting' => (int)$pageRecord['sorting']
                    ];
                }
            }
            // Resolve placeholders of workspace versions
            $resolver = GeneralUtility::makeInstance(
                PlainDataResolver::class,
                'pages',
                $livePageIds
            );
            $resolver->setWorkspaceId($this->currentWorkspace);
            $resolver->setKeepDeletePlaceholder(false);
            $resolver->setKeepMovePlaceholder(false);
            $resolver->setKeepLiveIds(false);
            $recordIds = $resolver->get();

            $queryBuilder->getRestrictions()->removeAll();
            $pageRecords = $queryBuilder
                ->select(...$this->fields)
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->in('uid', $recordIds)
                )
                ->execute()
                ->fetchAll();
        }

        // Now set up sorting, nesting (tree-structure) for all pages based on pid+sorting fields
        $groupedAndSortedPagesByPid = [];
        foreach ($pageRecords as $pageRecord) {
            $parentPageId = (int)$pageRecord['pid'];
            // In case this is a record from a workspace
            // The uid+pid of the live-version record is fetched
            // This is done in order to avoid fetching records again (e.g. via BackendUtility::workspaceOL()
            if ($parentPageId === -1) {
                // When a move pointer is found, the pid+sorting of the MOVE_PLACEHOLDER should be used (this is the
                // workspace record holding this information), also the t3ver_state is set to the MOVE_PLACEHOLDER
                // because the record is then added
                if ((int)$pageRecord['t3ver_state'] === VersionState::MOVE_POINTER && !empty($movePlaceholderData[$pageRecord['t3ver_oid']])) {
                    $parentPageId = (int)$movePlaceholderData[$pageRecord['t3ver_oid']]['pid'];
                    $pageRecord['sorting'] = (int)$movePlaceholderData[$pageRecord['t3ver_oid']]['sorting'];
                    $pageRecord['t3ver_state'] = VersionState::MOVE_PLACEHOLDER;
                } else {
                    // Just a record in a workspace (not moved etc)
                    $parentPageId = (int)$livePagePids[$pageRecord['t3ver_oid']];
                }
                // this is necessary so the links to the modules are still pointing to the live IDs
                $pageRecord['uid'] = (int)$pageRecord['t3ver_oid'];
                $pageRecord['pid'] = $parentPageId;
            }

            $sorting = (int)$pageRecord['sorting'];
            while (isset($groupedAndSortedPagesByPid[$parentPageId][$sorting])) {
                $sorting++;
            }
            $groupedAndSortedPagesByPid[$parentPageId][$sorting] = $pageRecord;
        }

        $this->fullPageTree = [
            'uid' => 0,
            'title' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] ?: 'TYPO3'
        ];
        $this->addChildrenToPage($this->fullPageTree, $groupedAndSortedPagesByPid);
        return $this->fullPageTree;
    }

    /**
     * Adds the property "_children" to a page record with the child pages
     *
     * @param array $page
     * @param array[] $groupedAndSortedPagesByPid
     */
    protected function addChildrenToPage(array &$page, array &$groupedAndSortedPagesByPid)
    {
        $page['_children'] = $groupedAndSortedPagesByPid[(int)$page['uid']] ?? [];
        ksort($page['_children']);
        foreach ($page['_children'] as &$child) {
            $this->addChildrenToPage($child, $groupedAndSortedPagesByPid);
        }
    }

    /**
     * Looking for a page by traversing the tree
     *
     * @param int $pageId the page ID to search for
     * @param array $pages the page tree to look for the page
     * @return array Array of the tree data, empty array if nothing was found
     */
    protected function findInPageTree(int $pageId, array $pages): array
    {
        foreach ($pages['_children'] as $childPage) {
            if ((int)$childPage['uid'] === $pageId) {
                return $childPage;
            }
            $result = $this->findInPageTree($pageId, $childPage);
            if (!empty($result)) {
                return $result;
            }
        }
        return [];
    }

    /**
     * Retrieve the page tree based on the given search filter
     *
     * @param string $searchFilter
     * @param array $allowedMountPointPageIds
     * @param string $additionalWhereClause
     * @return array
     */
    public function fetchFilteredTree(string $searchFilter, array $allowedMountPointPageIds, string $additionalWhereClause): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        if (!empty($this->additionalQueryRestrictions)) {
            foreach ($this->additionalQueryRestrictions as $additionalQueryRestriction) {
                $queryBuilder->getRestrictions()->add($additionalQueryRestriction);
            }
        }

        $expressionBuilder = $queryBuilder->expr();

        if ($this->currentWorkspace === 0) {
            // Only include ws_id=0
            $workspaceIdExpression = $expressionBuilder->eq('t3ver_wsid', 0);
        } else {
            // Include live records PLUS records from the given workspace
            $workspaceIdExpression = $expressionBuilder->in(
                't3ver_wsid',
                [0, $this->currentWorkspace]
            );
        }

        $queryBuilder = $queryBuilder
            ->select(...$this->fields)
            ->from('pages')
            ->where(
                // Only show records in default language
                $expressionBuilder->eq('sys_language_uid', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                $workspaceIdExpression,
                QueryHelper::stripLogicalOperatorPrefix($additionalWhereClause)
            );

        $searchParts = $expressionBuilder->orX();
        if (is_numeric($searchFilter) && $searchFilter > 0) {
            $searchParts->add(
                $expressionBuilder->eq('uid', $queryBuilder->createNamedParameter($searchFilter, \PDO::PARAM_INT))
            );
        }
        $searchFilter = '%' . $queryBuilder->escapeLikeWildcards($searchFilter) . '%';

        $searchWhereAlias = $expressionBuilder->orX(
            $expressionBuilder->like(
                'nav_title',
                $queryBuilder->createNamedParameter($searchFilter, \PDO::PARAM_STR)
            ),
            $expressionBuilder->like(
                'title',
                $queryBuilder->createNamedParameter($searchFilter, \PDO::PARAM_STR)
            )
        );
        $searchParts->add($searchWhereAlias);

        $queryBuilder->andWhere($searchParts);
        $pageRecords = $queryBuilder
            ->execute()
            ->fetchAll();

        $livePagePids = [];
        if ($this->currentWorkspace !== 0 && !empty($pageRecords)) {
            $livePageIds = [];
            foreach ($pageRecords as $pageRecord) {
                $livePageIds[] = (int)$pageRecord['uid'];
                $livePagePids[(int)$pageRecord['uid']] = (int)$pageRecord['pid'];
                if ((int)$pageRecord['t3ver_state'] === VersionState::MOVE_POINTER) {
                    $movePlaceholder = BackendUtility::getMovePlaceholder('pages', $pageRecord['t3ver_oid'], '*', $this->currentWorkspace);
                    $movePlaceholderData[$pageRecord['t3ver_oid']] = [
                        'pid' => (int)$movePlaceholder['pid'],
                        'sorting' => (int)$movePlaceholder['sorting']
                    ];
                }
                if ((int)$pageRecord['t3ver_state'] === VersionState::MOVE_PLACEHOLDER) {
                    $movePlaceholderData[$pageRecord['t3ver_move_id']] = [
                        'pid' => (int)$pageRecord['pid'],
                        'sorting' => (int)$pageRecord['sorting']
                    ];
                }
            }
            // Resolve placeholders of workspace versions
            $resolver = GeneralUtility::makeInstance(
                PlainDataResolver::class,
                'pages',
                $livePageIds
            );
            $resolver->setWorkspaceId($this->currentWorkspace);
            $resolver->setKeepDeletePlaceholder(false);
            $resolver->setKeepMovePlaceholder(false);
            $resolver->setKeepLiveIds(false);
            $recordIds = $resolver->get();

            $pageRecords = [];
            if (!empty($recordIds)) {
                $queryBuilder->getRestrictions()->removeAll();
                $queryBuilder
                    ->select(...$this->fields)
                    ->from('pages')
                    ->where(
                        $queryBuilder->expr()->in('uid', $queryBuilder->createNamedParameter($recordIds, Connection::PARAM_INT_ARRAY))
                    );
                $queryBuilder->andWhere($searchParts);
                $pageRecords = $queryBuilder
                    ->execute()
                    ->fetchAll();
            }
        }

        $pages = [];
        foreach ($pageRecords as $pageRecord) {
            $parentPageId = (int)$pageRecord['pid'];
            // In case this is a record from a workspace
            // The uid+pid of the live-version record is fetched
            // This is done in order to avoid fetching records again (e.g. via BackendUtility::workspaceOL()
            if ($parentPageId === -1) {
                // This probably should also remove the live version
                if ((int)$pageRecord['t3ver_state'] === VersionState::DELETE_PLACEHOLDER) {
                    continue;
                }
                // When a move pointer is found, the pid+sorting of the MOVE_PLACEHOLDER should be used (this is the
                // workspace record holding this information), also the t3ver_state is set to the MOVE_PLACEHOLDER
                // because the record is then added
                if ((int)$pageRecord['t3ver_state'] === VersionState::MOVE_POINTER && !empty($movePlaceholderData[$pageRecord['t3ver_oid']])) {
                    $parentPageId = (int)$movePlaceholderData[$pageRecord['t3ver_oid']]['pid'];
                    $pageRecord['sorting'] = (int)$movePlaceholderData[$pageRecord['t3ver_oid']]['sorting'];
                    $pageRecord['t3ver_state'] = VersionState::MOVE_PLACEHOLDER;
                } else {
                    // Just a record in a workspace (not moved etc)
                    if (isset($livePagePids[$pageRecord['t3ver_oid']])) {
                        $parentPageId = (int)$livePagePids[$pageRecord['t3ver_oid']];
                    } else {
                        $liveRecord = BackendUtility::getRecord('pages', $pageRecord['t3ver_oid'], 'uid,pid');
                        $parentPageId = (int)$liveRecord['pid'];
                    }
                }
                // this is necessary so the links to the modules are still pointing to the live IDs
                $pageRecord['uid'] = (int)$pageRecord['t3ver_oid'];
                $pageRecord['pid'] = $parentPageId;
            }
            $pages[(int)$pageRecord['uid']] = $pageRecord;
        }
        unset($pageRecords);

        $pages = $this->filterPagesOnMountPoints($pages, $allowedMountPointPageIds);

        $groupedAndSortedPagesByPid = $this->groupAndSortPages($pages);

        $this->fullPageTree = [
            'uid' => 0,
            'title' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] ?: 'TYPO3'
        ];
        $this->addChildrenToPage($this->fullPageTree, $groupedAndSortedPagesByPid);

        return $this->fullPageTree;
    }

    /**
     * Filter all records outside of the allowed mount points
     *
     * @param array $pages
     * @param array $mountPoints
     * @return array
     */
    protected function filterPagesOnMountPoints(array $pages, array $mountPoints): array
    {
        foreach ($pages as $key => $pageRecord) {
            $rootline = BackendUtility::BEgetRootLine(
                $pageRecord['uid'],
                '',
                $this->currentWorkspace !== 0,
                $this->fields
            );
            $rootline = array_reverse($rootline);
            if (!in_array(0, $mountPoints, true)) {
                $isInsideMountPoints = false;
                foreach ($rootline as $rootlineElement) {
                    if (in_array((int)$rootlineElement['uid'], $mountPoints, true)) {
                        $isInsideMountPoints = true;
                        break;
                    }
                }
                if (!$isInsideMountPoints) {
                    unset($pages[$key]);
                    //skip records outside of the allowed mount points
                    continue;
                }
            }

            $inFilteredRootline = false;
            $amountOfRootlineElements = count($rootline);
            for ($i = 0; $i < $amountOfRootlineElements; ++$i) {
                $rootlineElement = $rootline[$i];
                $rootlineElement['uid'] = (int)$rootlineElement['uid'];
                $isInWebMount = false;
                if ($rootlineElement['uid'] > 0) {
                    $isInWebMount = (int)$this->getBackendUser()->isInWebMount($rootlineElement);
                }

                if (!$isInWebMount
                    || ($rootlineElement['uid'] === (int)$mountPoints[0]
                        && $rootlineElement['uid'] !== $isInWebMount)
                ) {
                    continue;
                }
                if ($this->getBackendUser()->isAdmin() || ($rootlineElement['uid'] === $isInWebMount && in_array($rootlineElement['uid'], $mountPoints, true))) {
                    $inFilteredRootline = true;
                }
                if (!$inFilteredRootline) {
                    continue;
                }

                if (!isset($pages[$rootlineElement['uid']])) {
                    $pages[$rootlineElement['uid']] = $rootlineElement;
                }
            }
        }
        // Make sure the mountpoints show up in page tree even when parent pages are not accessible pages
        foreach ($mountPoints as $mountPoint) {
            if ($mountPoint !== 0) {
                if (!array_key_exists($mountPoint, $pages)) {
                    $pages[$mountPoint] = BackendUtility::getRecord('pages', $mountPoint);
                    $pages[$mountPoint]['uid'] = (int)$pages[$mountPoint]['uid'];
                }
                $pages[$mountPoint]['pid'] = 0;
            }
        }

        return $pages;
    }

    /**
     * Group pages by parent page and sort pages based on sorting property
     *
     * @param array $pages
     * @param array $groupedAndSortedPagesByPid
     * @return array
     */
    protected function groupAndSortPages(array $pages, $groupedAndSortedPagesByPid = []): array
    {
        foreach ($pages as $key => $pageRecord) {
            $parentPageId = (int)$pageRecord['pid'];
            $sorting = (int)$pageRecord['sorting'];
            while (isset($groupedAndSortedPagesByPid[$parentPageId][$sorting])) {
                $sorting++;
            }
            $groupedAndSortedPagesByPid[$parentPageId][$sorting] = $pageRecord;
        }

        return $groupedAndSortedPagesByPid;
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
