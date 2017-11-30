<?php
namespace TYPO3\CMS\Backend\Tree\Pagetree;

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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Page tree data provider.
 */
class DataProvider extends \TYPO3\CMS\Backend\Tree\AbstractTreeDataProvider
{
    /**
     * Node limit that should be loaded for this request per mount
     *
     * @var int
     */
    protected $nodeLimit = 0;

    /**
     * Current amount of nodes
     *
     * @var int
     */
    protected $nodeCounter = 0;

    /**
     * TRUE to show the path of each mountpoint in the tree
     *
     * @var bool
     */
    protected $showRootlineAboveMounts = false;

    /**
     * Hidden Records
     *
     * @var array<string>
     */
    protected $hiddenRecords = [];

    /**
     * Process collection hook objects
     *
     * @var array<\TYPO3\CMS\Backend\Tree\Pagetree\CollectionProcessorInterface>
     */
    protected $processCollectionHookObjects = [];

    /**
     * Constructor
     *
     * @param int $nodeLimit (optional)
     */
    public function __construct($nodeLimit = null)
    {
        $nodeLimit = $nodeLimit ?? $GLOBALS['TYPO3_CONF_VARS']['BE']['pageTree']['preloadLimit'];
        $this->nodeLimit = abs((int)$nodeLimit);

        $this->showRootlineAboveMounts = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.showPathAboveMounts');

        $this->hiddenRecords = GeneralUtility::trimExplode(',', $GLOBALS['BE_USER']->getTSConfigVal('options.hideRecords.pages'));
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/tree/pagetree/class.t3lib_tree_pagetree_dataprovider.php']['postProcessCollections'] ?? [] as $className) {
            /** @var $hookObject \TYPO3\CMS\Backend\Tree\Pagetree\CollectionProcessorInterface */
            $hookObject = GeneralUtility::makeInstance($className);
            if ($hookObject instanceof \TYPO3\CMS\Backend\Tree\Pagetree\CollectionProcessorInterface) {
                $this->processCollectionHookObjects[] = $hookObject;
            }
        }
    }

    /**
     * Returns the root node.
     *
     * @return \TYPO3\CMS\Backend\Tree\TreeNode the root node
     */
    public function getRoot()
    {
        /** @var $node \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
        $node = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode::class);
        $node->setId('root');
        $node->setExpanded(true);
        return $node;
    }

    /**
     * Fetches the sub-nodes of the given node
     *
     * @param \TYPO3\CMS\Backend\Tree\TreeNode $node
     * @param int $mountPoint
     * @param int $level internally used variable as a recursion limiter
     * @return \TYPO3\CMS\Backend\Tree\TreeNodeCollection
     */
    public function getNodes(\TYPO3\CMS\Backend\Tree\TreeNode $node, $mountPoint = 0, $level = 0)
    {
        /** @var $nodeCollection \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNodeCollection */
        $nodeCollection = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNodeCollection::class);
        if ($level >= 99 || ($node->getStopPageTree() && $node->getId() !== $mountPoint)) {
            return $nodeCollection;
        }
        $isVirtualRootNode = false;
        $subpages = $this->getSubpages($node->getId());
        // check if fetching subpages the "root"-page
        // and in case of a virtual root return the mountpoints as virtual "subpages"
        if ((int)$node->getId() === 0) {
            // check no temporary mountpoint is used
            if (!(int)$GLOBALS['BE_USER']->uc['pageTree_temporaryMountPoint']) {
                $mountPoints = array_map('intval', $GLOBALS['BE_USER']->returnWebmounts());
                $mountPoints = array_unique($mountPoints);
                if (!in_array(0, $mountPoints, true)) {
                    // using a virtual root node
                    // so then return the mount points here as "subpages" of the first node
                    $isVirtualRootNode = true;
                    $subpages = [];
                    foreach ($mountPoints as $webMountPoint) {
                        $subpage = BackendUtility::getRecordWSOL('pages', $webMountPoint, '*', '', true, true);
                        $subpage['isMountPoint'] = true;
                        $subpages[] = $subpage;
                    }
                }
            }
        }
        if (is_array($subpages) && !empty($subpages)) {
            $lastRootline = [];
            foreach ($subpages as $subpage) {
                if (in_array($subpage['t3ver_oid'] ?: $subpage['uid'], $this->hiddenRecords)) {
                    continue;
                }
                // must be calculated before getRecordWSOL(),
                // because the information is lost otherwise
                $isMountPoint = $subpage['isMountPoint'] === true;
                if ($isVirtualRootNode) {
                    $mountPoint = (int)$subpage['t3ver_oid'] ?: $subpage['uid'];
                }
                $subNode = Commands::getNewNode($subpage, $mountPoint);
                $subNode->setIsMountPoint($isMountPoint);
                if ($isMountPoint && $this->showRootlineAboveMounts) {
                    if ($subpage['pid'] > 0) {
                        $rootline = Commands::getMountPointPath($subpage['pid']);
                    } else {
                        $rootline = Commands::getMountPointPath($subpage['uid']);
                    }
                    if ($lastRootline !== $rootline) {
                        $subNode->setReadableRootline($rootline);
                    }
                    $lastRootline = $rootline;
                }
                if ($this->nodeCounter < $this->nodeLimit) {
                    $childNodes = $this->getNodes($subNode, $mountPoint, $level + 1);
                    $subNode->setChildNodes($childNodes);
                    $this->nodeCounter += $childNodes->count();
                } else {
                    $subNode->setLeaf(!$this->hasNodeSubPages((int)$subNode->getId()));
                }
                if (!$GLOBALS['BE_USER']->isAdmin() && (int)$subpage['editlock'] === 1) {
                    $subNode->setLabelIsEditable(false);
                }
                $nodeCollection->append($subNode);
            }
        }
        foreach ($this->processCollectionHookObjects as $hookObject) {
            /** @var $hookObject \TYPO3\CMS\Backend\Tree\Pagetree\CollectionProcessorInterface */
            $hookObject->postProcessGetNodes($node, $mountPoint, $level, $nodeCollection);
        }
        return $nodeCollection;
    }

    /**
     * Returns a node collection of filtered nodes
     *
     * @param \TYPO3\CMS\Backend\Tree\TreeNode $node
     * @param string $searchFilter
     * @param int $mountPoint
     * @return \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNodeCollection the filtered nodes
     */
    public function getFilteredNodes(\TYPO3\CMS\Backend\Tree\TreeNode $node, $searchFilter, $mountPoint = 0)
    {
        /** @var $nodeCollection \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNodeCollection */
        $nodeCollection = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNodeCollection::class);
        $records = $this->getPagesByQuery($searchFilter);
        if (!is_array($records) || empty($records)) {
            return $nodeCollection;
        }
        if (count($records) > 500) {
            return $nodeCollection;
        }
        // check no temporary mountpoint is used
        $mountPoints = (int)$GLOBALS['BE_USER']->uc['pageTree_temporaryMountPoint'];
        if (!$mountPoints) {
            $mountPoints = array_map('intval', $GLOBALS['BE_USER']->returnWebmounts());
            $mountPoints = array_unique($mountPoints);
        } else {
            $mountPoints = [$mountPoints];
        }
        $isNumericSearchFilter = is_numeric($searchFilter) && $searchFilter > 0;
        $searchFilterQuoted = preg_quote($searchFilter, '/');
        $nodeId = (int)$node->getId();
        $processedRecordIds = [];
        foreach ($records as $record) {
            $uid = (int)$record['t3ver_oid'] ?: $record['uid'];
            if (in_array($uid, $this->hiddenRecords) || in_array($uid, $processedRecordIds, true)
                || (
                    (int)$record['pid'] === -1 && (
                        (int)$record['t3ver_wsid'] === 0
                        || (int)$record['t3ver_wsid'] !== (int)$GLOBALS['BE_USER']->workspace
                    )
                )
            ) {
                continue;
            }
            $processedRecordIds[] = $uid;

            $rootline = BackendUtility::BEgetRootLine(
                $uid,
                '',
                $GLOBALS['BE_USER']->workspace != 0,
                [
                    'hidden',
                    'starttime',
                    'endtime',
                ]
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
                    continue;
                }
            }
            $reference = $nodeCollection;
            $inFilteredRootline = false;
            $amountOfRootlineElements = count($rootline);
            // render the root line elements up to the search result
            for ($i = 0; $i < $amountOfRootlineElements; ++$i) {
                $rootlineElement = $rootline[$i];
                $rootlineElement['uid'] = (int)$rootlineElement['uid'];
                $isInWebMount = (int)$GLOBALS['BE_USER']->isInWebMount($rootlineElement['uid']);
                if (!$isInWebMount
                    || ($rootlineElement['uid'] === (int)$mountPoints[0]
                        && $rootlineElement['uid'] !== $isInWebMount)
                ) {
                    continue;
                }
                if ((int)$rootlineElement['pid'] === $nodeId
                    || $rootlineElement['uid'] === $nodeId
                    || ($rootlineElement['uid'] === $isInWebMount
                        && in_array($rootlineElement['uid'], $mountPoints, true))
                ) {
                    $inFilteredRootline = true;
                }
                if (!$inFilteredRootline || $rootlineElement['uid'] === $mountPoint) {
                    continue;
                }
                $ident = (int)$rootlineElement['sorting'] . (int)$rootlineElement['uid'];
                if ($reference && $reference->offsetExists($ident)) {
                    /** @var $refNode \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
                    $refNode = $reference->offsetGet($ident);
                    $refNode->setExpanded(true);
                    $refNode->setLeaf(false);
                    $reference = $refNode->getChildNodes();
                    if ($reference == null) {
                        $reference = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNodeCollection::class);
                        $refNode->setChildNodes($reference);
                    }
                } else {
                    $refNode = Commands::getNewNode($rootlineElement, $mountPoint);
                    $replacement = '<span class="typo3-pagetree-filteringTree-highlight">$1</span>';
                    if ($isNumericSearchFilter && (int)$rootlineElement['uid'] === (int)$searchFilter) {
                        $text = str_replace('$1', $refNode->getText(), $replacement);
                    } else {
                        $text = preg_replace('/(' . $searchFilterQuoted . ')/iu', $replacement, $refNode->getText());
                    }
                    $refNode->setText($text, $refNode->getTextSourceField(), $refNode->getPrefix(), $refNode->getSuffix());
                    /** @var $childCollection \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNodeCollection */
                    $childCollection = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNodeCollection::class);
                    if ($i + 1 >= $amountOfRootlineElements) {
                        $childNodes = $this->getNodes($refNode, $mountPoint);
                        foreach ($childNodes as $childNode) {
                            /** @var $childNode \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
                            $childRecord = $childNode->getRecord();
                            $childIdent = (int)$childRecord['sorting'] . (int)$childRecord['uid'];
                            $childCollection->offsetSet($childIdent, $childNode);
                        }
                        $refNode->setChildNodes($childNodes);
                    }
                    $refNode->setChildNodes($childCollection);
                    $reference->offsetSet($ident, $refNode);
                    $reference->ksort();
                    $reference = $childCollection;
                }
            }
        }
        foreach ($this->processCollectionHookObjects as $hookObject) {
            /** @var $hookObject \TYPO3\CMS\Backend\Tree\Pagetree\CollectionProcessorInterface */
            $hookObject->postProcessFilteredNodes($node, $searchFilter, $mountPoint, $nodeCollection);
        }
        return $nodeCollection;
    }

    /**
     * Returns the page tree mounts for the current user
     *
     * Note: If you add the search filter parameter, the nodes will be filtered by this string.
     *
     * @param string $searchFilter
     * @return \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNodeCollection
     */
    public function getTreeMounts($searchFilter = '')
    {
        /** @var $nodeCollection \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNodeCollection */
        $nodeCollection = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNodeCollection::class);
        $isTemporaryMountPoint = false;
        $rootNodeIsVirtual = false;
        $mountPoints = (int)$GLOBALS['BE_USER']->uc['pageTree_temporaryMountPoint'];
        if (!$mountPoints) {
            $mountPoints = array_map('intval', $GLOBALS['BE_USER']->returnWebmounts());
            $mountPoints = array_unique($mountPoints);
            if (!in_array(0, $mountPoints, true)) {
                $rootNodeIsVirtual = true;
                // use a virtual root
                // the real mountpoints will be fetched in getNodes() then
                // since those will be the "subpages" of the virtual root
                $mountPoints = [0];
            }
        } else {
            $isTemporaryMountPoint = true;
            $mountPoints = [$mountPoints];
        }
        if (empty($mountPoints)) {
            return $nodeCollection;
        }

        foreach ($mountPoints as $mountPoint) {
            if ($mountPoint === 0) {
                $record = [
                    'uid' => 0,
                    'title' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] ?: 'TYPO3'
                ];
                $subNode = Commands::getNewNode($record);
                $subNode->setLabelIsEditable(false);
                if ($rootNodeIsVirtual) {
                    $subNode->setType('virtual_root');
                    $subNode->setIsDropTarget(false);
                } else {
                    $subNode->setType('pages_root');
                    $subNode->setIsDropTarget(true);
                }
            } else {
                if (in_array($mountPoint, $this->hiddenRecords)) {
                    continue;
                }
                $record = BackendUtility::getRecordWSOL('pages', $mountPoint);
                if (!$record) {
                    continue;
                }
                $subNode = Commands::getNewNode($record, $mountPoint);
                if ($this->showRootlineAboveMounts && !$isTemporaryMountPoint) {
                    $rootline = Commands::getMountPointPath($record['uid']);
                    $subNode->setReadableRootline($rootline);
                }
            }
            if (count($mountPoints) <= 1) {
                $subNode->setExpanded(true);
                $subNode->setCls('typo3-pagetree-node-notExpandable');
            }
            $subNode->setIsMountPoint(true);
            $subNode->setDraggable(false);
            if ($searchFilter === '') {
                $childNodes = $this->getNodes($subNode, $mountPoint);
            } else {
                $childNodes = $this->getFilteredNodes($subNode, $searchFilter, $mountPoint);
                $subNode->setExpanded(true);
            }
            $subNode->setChildNodes($childNodes);
            $nodeCollection->append($subNode);
        }
        foreach ($this->processCollectionHookObjects as $hookObject) {
            /** @var $hookObject \TYPO3\CMS\Backend\Tree\Pagetree\CollectionProcessorInterface */
            $hookObject->postProcessGetTreeMounts($searchFilter, $nodeCollection);
        }
        return $nodeCollection;
    }

    /**
     * Sets the Doctrine where clause for fetching pages
     *
     * @param QueryBuilder $queryBuilder
     * @param string $searchFilter
     * @return QueryBuilder
     */
    protected function setWhereClause(QueryBuilder $queryBuilder, $searchFilter = ''): QueryBuilder
    {
        $expressionBuilder = $queryBuilder->expr();
        $queryBuilder->where(
            QueryHelper::stripLogicalOperatorPrefix($GLOBALS['BE_USER']->getPagePermsClause(1)),
            // Only show records in default language
            $expressionBuilder->eq('sys_language_uid', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
        );

        if ($searchFilter !== '') {
            $searchParts = $expressionBuilder->orX();
            if (is_numeric($searchFilter) && $searchFilter > 0) {
                $searchParts->add(
                    $expressionBuilder->eq('uid', $queryBuilder->createNamedParameter($searchFilter, \PDO::PARAM_INT))
                );
            }
            $searchFilter = '%' . $queryBuilder->escapeLikeWildcards($searchFilter) . '%';
            $useNavTitle = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.showNavTitle');

            if ($useNavTitle) {
                $searchWhereAlias = $expressionBuilder->orX(
                    $expressionBuilder->like(
                        'nav_title',
                        $queryBuilder->createNamedParameter($searchFilter, \PDO::PARAM_STR)
                    ),
                    $expressionBuilder->andX(
                        $expressionBuilder->eq(
                            'nav_title',
                            $queryBuilder->createNamedParameter('', \PDO::PARAM_STR)
                        ),
                        $expressionBuilder->like(
                            'title',
                            $queryBuilder->createNamedParameter($searchFilter, \PDO::PARAM_STR)
                        )
                    )
                );
                $searchParts->add($searchWhereAlias);
            } else {
                $searchParts->add(
                    $expressionBuilder->like(
                        'title',
                        $queryBuilder->createNamedParameter($searchFilter, \PDO::PARAM_STR)
                    )
                );
            }

            // Also search for the alias
            $searchParts->add(
                $expressionBuilder->like(
                    'alias',
                    $queryBuilder->createNamedParameter($searchFilter, \PDO::PARAM_STR)
                )
            );
            $queryBuilder->andWhere($searchParts);
        }
        return $queryBuilder;
    }

    /**
     * Returns all sub-pages of a given ID
     *
     * @param int $id
     * @return array
     */
    protected function getSubpages(int $id): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));
        $result = [];
        $queryBuilder->select('*')
            ->from('pages')
            ->where(
                QueryHelper::stripLogicalOperatorPrefix($GLOBALS['BE_USER']->getPagePermsClause(1)),
                // Only show records in default language
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
            )
            ->orderBy('sorting');
        if ((int)$id >= 0) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT))
            );
        }
        $queryResult = $queryBuilder->execute();
        while ($row = $queryResult->fetch()) {
            BackendUtility::workspaceOL('pages', $row, -99, true);
            if ($row) {
                $result[] = $row;
            }
        }
        return $result;
    }

    /**
     * Returns all pages with a query.
     *
     * @param string $searchFilter
     * @return array
     */
    protected function getPagesByQuery(string $searchFilter = ''): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));
        $result = [];
        $queryBuilder = $this->setWhereClause($queryBuilder, $searchFilter);
        $queryResult = $queryBuilder->select('*')
            ->from('pages')
            ->orderBy('sorting')
            ->execute();
        while ($row = $queryResult->fetch()) {
            BackendUtility::workspaceOL('pages', $row, -99, true);
            if ($row) {
                $result[] = $row;
            }
        }
        return $result;
    }

    /**
     * Returns true if the node has children.
     *
     * @param int $id
     * @return bool
     */
    protected function hasNodeSubPages(int $id): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));
        $queryBuilder->count('uid')
            ->from('pages')
            ->where(
                QueryHelper::stripLogicalOperatorPrefix($GLOBALS['BE_USER']->getPagePermsClause(1)),
                // Only show records in default language
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
            );
        if ((int)$id >= 0) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT))
            );
        }
        $count = $queryBuilder->execute()
            ->fetchColumn(0);
        return (bool)$count;
    }
}
