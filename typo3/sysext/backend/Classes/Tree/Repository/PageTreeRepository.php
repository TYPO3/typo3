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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\DataHandling\PlainDataResolver;
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
     * @param int $workspaceId the workspace ID to be checked for.
     * @param array $additionalFieldsToQuery an array with more fields that should be accessed.
     */
    public function __construct(int $workspaceId = 0, array $additionalFieldsToQuery = [])
    {
        $this->currentWorkspace = $workspaceId;
        if (!empty($additionalFieldsToQuery)) {
            $this->fields = array_merge($this->fields, $additionalFieldsToQuery);
        }
    }

    /**
     * Main entry point for this repository, to fetch the tree data for a page.
     * Basically the page record, plus all child pages and their child pages recursively, stored within "_children" item.
     *
     * @param int $entryPoint the page ID to fetch the tree for
     * @param callable $callback a callback to be used to check for permissions and filter out pages not to be included.
     * @return array
     */
    public function getTree(int $entryPoint, callable $callback = null): array
    {
        $this->fetchAllPages();
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
        foreach ($tree['_children'] as $k => $childPage) {
            if (!call_user_func_array($callback, [$childPage])) {
                unset($tree['_children'][$k]);
                continue;
            }
            $this->applyCallbackToChildren($childPage, $callback);
        }
    }

    /**
     * Fetch all non-deleted pages, regardless of permissions. That's why it's internal.
     *
     * @return array the full page tree of the whole installation
     */
    protected function fetchAllPages(): array
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

        $pageRecords = $queryBuilder
            ->select(...$this->fields)
            ->from('pages')
            ->where(
                // Only show records in default language
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
            )
            ->execute()
            ->fetchAll();

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
}
