<?php
namespace TYPO3\CMS\Backend\Tree\Pagetree;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 TYPO3 Tree Team <http://forge.typo3.org/projects/typo3v4-extjstrees>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Page tree data provider.
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
class DataProvider extends \TYPO3\CMS\Backend\Tree\AbstractTreeDataProvider {

	/**
	 * Node limit that should be loaded for this request per mount
	 *
	 * @var integer
	 */
	protected $nodeLimit = 0;

	/**
	 * Current amount of nodes
	 *
	 * @var integer
	 */
	protected $nodeCounter = 0;

	/**
	 * TRUE to show the path of each mountpoint in the tree
	 *
	 * @var bool
	 */
	protected $showRootlineAboveMounts = FALSE;

	/**
	 * Hidden Records
	 *
	 * @var array<string>
	 */
	protected $hiddenRecords = array();

	/**
	 * Process collection hook objects
	 *
	 * @var array<\TYPO3\CMS\Backend\Tree\Pagetree\CollectionProcessorInterface>
	 */
	protected $processCollectionHookObjects = array();

	/**
	 * Constructor
	 *
	 * @param integer $nodeLimit (optional)
	 */
	public function __construct($nodeLimit = NULL) {
		if ($nodeLimit === NULL) {
			$nodeLimit = $GLOBALS['TYPO3_CONF_VARS']['BE']['pageTree']['preloadLimit'];
		}
		$this->nodeLimit = abs(intval($nodeLimit));

		$this->showRootlineAboveMounts = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.showPathAboveMounts');

		$this->hiddenRecords = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['BE_USER']->getTSConfigVal('options.hideRecords.pages'));
		$hookElements = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/tree/pagetree/class.t3lib_tree_pagetree_dataprovider.php']['postProcessCollections'];
		if (is_array($hookElements)) {
			foreach ($hookElements as $classRef) {
				/** @var $hookObject \TYPO3\CMS\Backend\Tree\Pagetree\CollectionProcessorInterface */
				$hookObject = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
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
	public function getRoot() {
		/** @var $node \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
		$node = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Tree\\Pagetree\\PagetreeNode');
		$node->setId('root');
		$node->setExpanded(TRUE);
		return $node;
	}

	/**
	 * Fetches the sub-nodes of the given node
	 *
	 * @param \TYPO3\CMS\Backend\Tree\TreeNode $node
	 * @param integer $mountPoint
	 * @param integer $level internally used variable as a recursion limiter
	 * @return \TYPO3\CMS\Backend\Tree\TreeNodeCollection
	 */
	public function getNodes(\TYPO3\CMS\Backend\Tree\TreeNode $node, $mountPoint = 0, $level = 0) {
		/** @var $nodeCollection \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNodeCollection */
		$nodeCollection = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Tree\\Pagetree\\PagetreeNodeCollection');
		if ($level >= 99) {
			return $nodeCollection;
		}
		$subpages = $this->getSubpages($node->getId());
		// check if fetching subpages the "root"-page
		// and in case of a virtual root return the mountpoints as virtual "subpages"
		if (intval($node->getId()) === 0) {
			// check no temporary mountpoint is used
			if (!intval($GLOBALS['BE_USER']->uc['pageTree_temporaryMountPoint'])) {
				$mountPoints = array_map('intval', $GLOBALS['BE_USER']->returnWebmounts());
				$mountPoints = array_unique($mountPoints);
				if (!in_array(0, $mountPoints)) {
					// using a virtual root node
					// so then return the mount points here as "subpages" of the first node
					$subpages = array();
					foreach ($mountPoints as $webMountPoint) {
						$subpages[] = array(
							'uid' => $webMountPoint,
							'isMountPoint' => TRUE
						);
					}
				}
			}
		}
		if (is_array($subpages) && count($subpages) > 0) {
			foreach ($subpages as $subpage) {
				if (in_array($subpage['uid'], $this->hiddenRecords)) {
					continue;
				}
				// must be calculated above getRecordWithWorkspaceOverlay,
				// because the information is lost otherwise
				$isMountPoint = $subpage['isMountPoint'] === TRUE;
				$subpage = $this->getRecordWithWorkspaceOverlay($subpage['uid'], TRUE);
				if (!$subpage) {
					continue;
				}
				$subNode = \TYPO3\CMS\Backend\Tree\Pagetree\Commands::getNewNode($subpage, $mountPoint);
				$subNode->setIsMountPoint($isMountPoint);
				if ($isMountPoint && $this->showRootlineAboveMounts) {
					$rootline = \TYPO3\CMS\Backend\Tree\Pagetree\Commands::getMountPointPath($subpage['uid']);
					$subNode->setReadableRootline($rootline);
				}
				if ($this->nodeCounter < $this->nodeLimit) {
					$childNodes = $this->getNodes($subNode, $mountPoint, $level + 1);
					$subNode->setChildNodes($childNodes);
					$this->nodeCounter += $childNodes->count();
				} else {
					$subNode->setLeaf(!$this->hasNodeSubPages($subNode->getId()));
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
	 * @param integer $uid The page id
	 * @param boolean $unsetMovePointers Whether to unset move pointers
	 * @return array
	 */
	protected function getRecordWithWorkspaceOverlay($uid, $unsetMovePointers = FALSE) {
		return \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL('pages', $uid, '*', '', TRUE, $unsetMovePointers);
	}

	/**
	 * Returns a node collection of filtered nodes
	 *
	 * @param \TYPO3\CMS\Backend\Tree\TreeNode $node
	 * @param string $searchFilter
	 * @param integer $mountPoint
	 * @return \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNodeCollection the filtered nodes
	 */
	public function getFilteredNodes(\TYPO3\CMS\Backend\Tree\TreeNode $node, $searchFilter, $mountPoint = 0) {
		/** @var $nodeCollection \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNodeCollection */
		$nodeCollection = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Tree\\Pagetree\\PagetreeNodeCollection');
		$records = $this->getSubpages(-1, $searchFilter);
		if (!is_array($records) || !count($records)) {
			return $nodeCollection;
		} elseif (count($records) > 500) {
			return $nodeCollection;
		}
		// check no temporary mountpoint is used
		$mountPoints = intval($GLOBALS['BE_USER']->uc['pageTree_temporaryMountPoint']);
		if (!$mountPoints) {
			$mountPoints = array_map('intval', $GLOBALS['BE_USER']->returnWebmounts());
			$mountPoints = array_unique($mountPoints);
		} else {
			$mountPoints = array($mountPoints);
		}
		$isNumericSearchFilter = is_numeric($searchFilter) && $searchFilter > 0;
		$searchFilterQuoted = preg_quote($searchFilter, '/');
		$nodeId = intval($node->getId());
		foreach ($records as $record) {
			$record = \TYPO3\CMS\Backend\Tree\Pagetree\Commands::getNodeRecord($record['uid']);
			if (intval($record['pid']) === -1 || in_array($record['uid'], $this->hiddenRecords)) {
				continue;
			}
			$rootline = \TYPO3\CMS\Backend\Utility\BackendUtility::BEgetRootLine($record['uid'], '', $GLOBALS['BE_USER']->workspace != 0);
			$rootline = array_reverse($rootline);
			if ($nodeId === 0) {
				array_shift($rootline);
			}
			if ($mountPoints != array(0)) {
				$isInsideMountPoints = FALSE;
				foreach ($rootline as $rootlineElement) {
					if (in_array(intval($rootlineElement['uid']), $mountPoints, TRUE)) {
						$isInsideMountPoints = TRUE;
						break;
					}
				}
				if (!$isInsideMountPoints) {
					continue;
				}
			}
			$reference = $nodeCollection;
			$inFilteredRootline = FALSE;
			$amountOfRootlineElements = count($rootline);
			for ($i = 0; $i < $amountOfRootlineElements; ++$i) {
				$rootlineElement = $rootline[$i];
				$isInWebMount = $GLOBALS['BE_USER']->isInWebMount($rootlineElement['uid']);
				if (!$isInWebMount
					|| (intval($rootlineElement['uid']) === intval($mountPoints[0])
						&& intval($rootlineElement['uid']) !== intval($isInWebMount))
				) {
					continue;
				}
				if (intval($rootlineElement['pid']) === $nodeId
					|| intval($rootlineElement['uid']) === $nodeId
					|| (intval($rootlineElement['uid']) === intval($isInWebMount)
						&& in_array(intval($rootlineElement['uid']), $mountPoints, TRUE))
				) {
					$inFilteredRootline = TRUE;
				}
				if (!$inFilteredRootline || intval($rootlineElement['uid']) === intval($mountPoint)) {
					continue;
				}
				$rootlineElement = \TYPO3\CMS\Backend\Tree\Pagetree\Commands::getNodeRecord($rootlineElement['uid']);
				$ident = intval($rootlineElement['sorting']) . intval($rootlineElement['uid']);
				if ($reference && $reference->offsetExists($ident)) {
					/** @var $refNode \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
					$refNode = $reference->offsetGet($ident);
					$refNode->setExpanded(TRUE);
					$refNode->setLeaf(FALSE);
					$reference = $refNode->getChildNodes();
					if ($reference == NULL) {
						$reference = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Tree\\Pagetree\\PagetreeNodeCollection');
						$refNode->setChildNodes($reference);
					}
				} else {
					$refNode = \TYPO3\CMS\Backend\Tree\Pagetree\Commands::getNewNode($rootlineElement, $mountPoint);
					$replacement = '<span class="typo3-pagetree-filteringTree-highlight">$1</span>';
					if ($isNumericSearchFilter && intval($rootlineElement['uid']) === intval($searchFilter)) {
						$text = str_replace('$1', $refNode->getText(), $replacement);
					} else {
						$text = preg_replace('/(' . $searchFilterQuoted . ')/i', $replacement, $refNode->getText());
					}
					$refNode->setText($text, $refNode->getTextSourceField(), $refNode->getPrefix(), $refNode->getSuffix());
					/** @var $childCollection \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNodeCollection */
					$childCollection = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Tree\\Pagetree\\PagetreeNodeCollection');
					if ($i + 1 >= $amountOfRootlineElements) {
						$childNodes = $this->getNodes($refNode, $mountPoint);
						foreach ($childNodes as $childNode) {
							/** @var $childNode \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
							$childRecord = $childNode->getRecord();
							$childIdent = intval($childRecord['sorting']) . intval($childRecord['uid']);
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
	public function getTreeMounts($searchFilter = '') {
		/** @var $nodeCollection \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNodeCollection */
		$nodeCollection = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Tree\\Pagetree\\PagetreeNodeCollection');
		$isTemporaryMountPoint = FALSE;
		$rootNodeIsVirtual = FALSE;
		$mountPoints = intval($GLOBALS['BE_USER']->uc['pageTree_temporaryMountPoint']);
		if (!$mountPoints) {
			$mountPoints = array_map('intval', $GLOBALS['BE_USER']->returnWebmounts());
			$mountPoints = array_unique($mountPoints);
			if (!in_array(0, $mountPoints)) {
				$rootNodeIsVirtual = TRUE;
				// use a virtual root
				// the real mountpoints will be fetched in getNodes() then
				// since those will be the "subpages" of the virtual root
				$mountPoints = array(0);
			}
		} else {
			$isTemporaryMountPoint = TRUE;
			$mountPoints = array($mountPoints);
		}
		if (!count($mountPoints)) {
			return $nodeCollection;
		}

		foreach ($mountPoints as $mountPoint) {
			if ($mountPoint === 0) {
				$sitename = 'TYPO3';
				if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] !== '') {
					$sitename = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
				}
				$record = array(
					'uid' => 0,
					'title' => $sitename
				);
				$subNode = \TYPO3\CMS\Backend\Tree\Pagetree\Commands::getNewNode($record);
				$subNode->setLabelIsEditable(FALSE);
				if ($rootNodeIsVirtual) {
					$subNode->setType('virtual_root');
					$subNode->setIsDropTarget(FALSE);
				} else {
					$subNode->setType('pages_root');
					$subNode->setIsDropTarget(TRUE);
				}
			} else {
				if (in_array($mountPoint, $this->hiddenRecords)) {
					continue;
				}
				$record = $this->getRecordWithWorkspaceOverlay($mountPoint);
				if (!$record) {
					continue;
				}
				$subNode = \TYPO3\CMS\Backend\Tree\Pagetree\Commands::getNewNode($record, $mountPoint);
				if ($this->showRootlineAboveMounts && !$isTemporaryMountPoint) {
					$rootline = \TYPO3\CMS\Backend\Tree\Pagetree\Commands::getMountPointPath($record['uid']);
					$subNode->setReadableRootline($rootline);
				}
			}
			if (count($mountPoints) <= 1) {
				$subNode->setExpanded(TRUE);
				$subNode->setCls('typo3-pagetree-node-notExpandable');
			}
			$subNode->setIsMountPoint(TRUE);
			$subNode->setDraggable(FALSE);
			if ($searchFilter === '') {
				$childNodes = $this->getNodes($subNode, $mountPoint);
			} else {
				$childNodes = $this->getFilteredNodes($subNode, $searchFilter, $mountPoint);
				$subNode->setExpanded(TRUE);
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
	 * @param integer $id
	 * @param string $searchFilter
	 * @return string
	 */
	protected function getWhereClause($id, $searchFilter = '') {
		$where = $GLOBALS['BE_USER']->getPagePermsClause(1) . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('pages') . \TYPO3\CMS\Backend\Utility\BackendUtility::versioningPlaceholderClause('pages');
		if (is_numeric($id) && $id >= 0) {
			$where .= ' AND pid= ' . $GLOBALS['TYPO3_DB']->fullQuoteStr(intval($id), 'pages');
		}
		if ($searchFilter !== '') {
			if (is_numeric($searchFilter) && $searchFilter > 0) {
				$searchWhere .= 'uid = ' . intval($searchFilter) . ' OR ';
			}
			$searchFilter = $GLOBALS['TYPO3_DB']->fullQuoteStr('%' . $searchFilter . '%', 'pages');
			$useNavTitle = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.showNavTitle');
			if ($useNavTitle) {
				$searchWhere .= '(nav_title LIKE ' . $searchFilter . ' OR (nav_title = "" AND title LIKE ' . $searchFilter . '))';
			} else {
				$searchWhere .= 'title LIKE ' . $searchFilter;
			}
			$where .= ' AND (' . $searchWhere . ')';
		}
		return $where;
	}

	/**
	 * Returns all sub-pages of a given id
	 *
	 * @param integer $id
	 * @param string $searchFilter
	 * @return array
	 */
	protected function getSubpages($id, $searchFilter = '') {
		$where = $this->getWhereClause($id, $searchFilter);
		return $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid', 'pages', $where, '', 'sorting', '', 'uid');
	}

	/**
	 * Returns TRUE if the node has child's
	 *
	 * @param integer $id
	 * @return boolean
	 */
	protected function hasNodeSubPages($id) {
		$where = $this->getWhereClause($id);
		$subpage = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('uid', 'pages', $where, '', 'sorting', '', 'uid');
		$returnValue = TRUE;
		if (!$subpage['uid']) {
			$returnValue = FALSE;
		}
		return $returnValue;
	}

}


?>
