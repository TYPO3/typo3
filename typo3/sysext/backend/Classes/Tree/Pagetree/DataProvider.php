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
        if ($nodeLimit === null) {
            $nodeLimit = $GLOBALS['TYPO3_CONF_VARS']['BE']['pageTree']['preloadLimit'];
        }
        $this->nodeLimit = abs((int)$nodeLimit);

        $this->showRootlineAboveMounts = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.showPathAboveMounts');

        $this->hiddenRecords = GeneralUtility::trimExplode(',', $GLOBALS['BE_USER']->getTSConfigVal('options.hideRecords.pages'));
        $hookElements = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/tree/pagetree/class.t3lib_tree_pagetree_dataprovider.php']['postProcessCollections'];
        if (is_array($hookElements)) {
            foreach ($hookElements as $classRef) {
                /** @var $hookObject \TYPO3\CMS\Backend\Tree\Pagetree\CollectionProcessorInterface */
                $hookObject = GeneralUtility::getUserObj($classRef);
                if ($hookObject instanceof \TYPO3\CMS\Backend\Tree\Pagetree\CollectionProcessorInterface) {
                    $this->processCollectionHookObjects[] = $hookObject;
                }
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
        if ($level >= 99 || $node->getStopPageTree()) {
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
                if (!in_array(0, $mountPoints)) {
                    // using a virtual root node
                    // so then return the mount points here as "subpages" of the first node
                    $isVirtualRootNode = true;
                    $subpages = [];
                    foreach ($mountPoints as $webMountPoint) {
                        $subpages[] = [
                            'uid' => $webMountPoint,
                            'isMountPoint' => true
                        ];
                    }
                }
            }
        }
        if (is_array($subpages) && !empty($subpages)) {
            $lastRootline = [];
            foreach ($subpages as $subpage) {
                if (in_array($subpage['uid'], $this->hiddenRecords)) {
                    continue;
                }
                // must be calculated above getRecordWithWorkspaceOverlay,
                // because the information is lost otherwise
                $isMountPoint = $subpage['isMountPoint'] === true;
                if ($isVirtualRootNode) {
                    $mountPoint = (int)$subpage['uid'];
                }
                $subpage = $this->getRecordWithWorkspaceOverlay($subpage['uid'], true);
                if (!$subpage) {
                    continue;
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
                    $subNode->setLeaf(!$this->hasNodeSubPages($subNode->getId()));
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
     * Wrapper method for \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL
     *
     * @param int $uid The page id
     * @param bool $unsetMovePointers Whether to unset move pointers
     * @return array
     */
    protected function getRecordWithWorkspaceOverlay($uid, $unsetMovePointers = false)
    {
        return BackendUtility::getRecordWSOL('pages', $uid, '*', '', true, $unsetMovePointers);
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
        $records = $this->getSubpages(-1, $searchFilter);
        if (!is_array($records) || empty($records)) {
            return $nodeCollection;
        } elseif (count($records) > 500) {
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
            if ((int)$record['t3ver_wsid'] !== (int)$GLOBALS['BE_USER']->workspace && (int)$record['t3ver_wsid'] !== 0) {
                continue;
            }
            $liveVersion = BackendUtility::getLiveVersionOfRecord('pages', $record['uid'], 'uid');
            if ($liveVersion !== null) {
                $record = $liveVersion;
            }

            $record = Commands::getNodeRecord($record['uid'], false);
            if ((int)$record['pid'] === -1
                || in_array($record['uid'], $this->hiddenRecords)
                || in_array($record['uid'], $processedRecordIds)
            ) {
                continue;
            }
            $processedRecordIds[] = $record['uid'];

            $rootline = BackendUtility::BEgetRootLine($record['uid'], '', $GLOBALS['BE_USER']->workspace != 0);
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
                $rootlineElement = Commands::getNodeRecord($rootlineElement['uid'], false);
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
            if (!in_array(0, $mountPoints)) {
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
                $sitename = 'TYPO3';
                if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] !== '') {
                    $sitename = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
                }
                $record = [
                    'uid' => 0,
                    'title' => $sitename
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
                $record = $this->getRecordWithWorkspaceOverlay($mountPoint);
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
     * Returns the where clause for fetching pages
     *
     * @param int $id
     * @param string $searchFilter
     * @return string
     */
    protected function getWhereClause($id, $searchFilter = '')
    {
        $where = $GLOBALS['BE_USER']->getPagePermsClause(1) . BackendUtility::deleteClause('pages') . BackendUtility::versioningPlaceholderClause('pages');
        if (is_numeric($id) && $id >= 0) {
            $where .= ' AND pid= ' . $GLOBALS['TYPO3_DB']->fullQuoteStr((int)$id, 'pages');
        }

        $excludedDoktypes = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.excludeDoktypes');
        if (!empty($excludedDoktypes)) {
            $excludedDoktypes = $GLOBALS['TYPO3_DB']->fullQuoteArray(GeneralUtility::intExplode(',', $excludedDoktypes), 'pages');
            $where .= ' AND doktype NOT IN (' . implode(',', $excludedDoktypes) . ')';
        }

        if ($searchFilter !== '') {
            if (is_numeric($searchFilter) && $searchFilter > 0) {
                $searchWhere .= 'uid = ' . (int)$searchFilter . ' OR ';
            }
            $searchFilter = $GLOBALS['TYPO3_DB']->fullQuoteStr('%' . $searchFilter . '%', 'pages');
            $useNavTitle = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.showNavTitle');
            $useAlias = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.searchInAlias');

            $searchWhereAlias = '';
            if ($useAlias) {
                $searchWhereAlias = ' OR alias LIKE ' . $searchFilter;
            }

            if ($useNavTitle) {
                $searchWhere .= '(nav_title LIKE ' . $searchFilter .
                ' OR (nav_title = "" AND title LIKE ' . $searchFilter . ')' . $searchWhereAlias . ')';
            } else {
                $searchWhere .= 'title LIKE ' . $searchFilter . $searchWhereAlias;
            }

            $where .= ' AND (' . $searchWhere . ')';
        }
        return $where;
    }

    /**
     * Returns all sub-pages of a given id
     *
     * @param int $id
     * @param string $searchFilter
     * @return array
     */
    protected function getSubpages($id, $searchFilter = '')
    {
        $where = $this->getWhereClause($id, $searchFilter);
        return $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,t3ver_wsid', 'pages', $where, '', 'sorting', '', 'uid');
    }

    /**
     * Returns TRUE if the node has child's
     *
     * @param int $id
     * @return bool
     */
    protected function hasNodeSubPages($id)
    {
        $where = $this->getWhereClause($id);
        $subpage = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('uid', 'pages', $where, '', 'sorting', '', 'uid');
        $returnValue = true;
        if (!$subpage['uid']) {
            $returnValue = false;
        }
        return $returnValue;
    }
}
