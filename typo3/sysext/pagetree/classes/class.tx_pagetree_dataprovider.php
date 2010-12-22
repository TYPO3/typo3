<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 TYPO3 Tree Team <http://forge.typo3.org/projects/typo3v4-extjstrees>
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
 * Abstract Tree Data Provider
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 * @package TYPO3
 * @subpackage tx_pagetree
 */
class tx_pagetree_DataProvider extends t3lib_tree_AbstractDataProvider {
	/**
	 * Node limit that should be loaded for this request per mount
	 *
	 * @var int
	 */
	protected $nodeLimit = 500;

	/**
	 * Current amount of nodes
	 *
	 * @var int
	 */
	protected $nodeCounter = 0;

	/**
	 * Returns the root node
	 *
	 * @return t3lib_tree_Node
	 */
	public function getRoot() {
		/** @var $node tx_pagetree_Node */
		$node = t3lib_div::makeInstance('tx_pagetree_Node');
		$node->setId('root');
		$node->setExpanded(true);

		return $node;
	}

	/**
	 * Fetches the sub-nodes of the given node
	 *
	 * @param t3lib_tree_Node $node
	 * @param int $mountPoint
	 * @param int $level internally used variable as a recursion limiter
	 * @return t3lib_tree_NodeCollection
	 */
	 public function getNodes(t3lib_tree_Node $node, $mountPoint = 0, $level = 0) {
		/** @var $nodeCollection tx_pagetree_NodeCollection */
		$nodeCollection = t3lib_div::makeInstance('tx_pagetree_NodeCollection');
		if ($level >= 99) {
			return $nodeCollection;
		}

		$subpages = $this->getSubpages($node->getId());
		if (!is_array($subpages) || !count($subpages)) {
			return $nodeCollection;
		}

		foreach ($subpages as $subpage) {
			$subpage = t3lib_befunc::getRecordWSOL('pages', $subpage['uid'], '*', '', TRUE, TRUE);
			if (!$subpage) {
				continue;
			}

			$subNode = tx_pagetree_Commands::getNewNode($subpage, $mountPoint);
			if ($this->nodeCounter < $this->nodeLimit) {
				$childNodes = $this->getNodes($subNode, $mountPoint, $level + 1);
				$subNode->setChildNodes($childNodes);
				$this->nodeCounter += $childNodes->count();
			} else {
				$subNode->setLeaf(!$this->hasNodeSubPages($subNode->getId()));
			}

			$nodeCollection->append($subNode);
		}

		return $nodeCollection;
	}

	/**
	 * Returns a node collection of filtered nodes
	 *
	 * @param int $nodeId
	 * @param string $searchFilter
	 * @param int $mountPoint
	 * @return void
	 */
	public function getFilteredNodes($nodeId, $searchFilter, $mountPoint = 0) {
		$nodeId = intval($nodeId);
		
		/** @var $nodeCollection tx_pagetree_NodeCollection */
		$nodeCollection = t3lib_div::makeInstance('tx_pagetree_NodeCollection');

		$subpages = $this->getSubpages(-1, $searchFilter);
		if (!is_array($subpages) || !count($subpages)) {
			return $nodeCollection;
		}

		foreach ($subpages as $subpage) {
			$rootline = array_reverse(t3lib_BEfunc::BEgetRootLine($subpage['uid']));
			array_shift($rootline);
			$reference = $nodeCollection;

			$inFilteredRootline = FALSE;
			foreach ($rootline as $rootlineElement) {
				if (intval($rootlineElement['pid']) === $nodeId) {
					$inFilteredRootline = TRUE;
				}

				if (!$inFilteredRootline) {
					continue;
				}

				$rootlineElement = tx_pagetree_Commands::getNodeRecord($rootlineElement['uid']);
				if ($reference->offsetExists($rootlineElement['uid'])) {
					/** @var $node tx_pagetree_Node */
					$node = $reference->offsetGet($rootlineElement['uid']);
					$node->setExpanded(TRUE);
					$node->setLeaf(FALSE);

					$reference = $node->getChildNodes();
					continue;
				}

				/** @var $childCollection tx_pagetree_NodeCollection */
				$childCollection = t3lib_div::makeInstance('tx_pagetree_NodeCollection');

				$node = tx_pagetree_Commands::getNewNode($rootlineElement, $mountPoint);
				$node->setChildNodes($childCollection);

				$text = preg_replace(
					'/(' . $searchFilter . ')/i',
					'<span class="typo3-pagetree-filteringTree-highlight">$1</span>',
					$node->getText()
				);
				$node->setText($text, $node->getTextSourceField(), $node->getPrefix());

				$reference->offsetSet($rootlineElement['uid'], $node);
				$reference = $childCollection;
			}
		}

		return $nodeCollection;
	}

	/**
	 * Returns the page tree mounts for the current user
	 *
	 * Note: If you add the search filter parameter, the nodes will be filtered by this string.
	 *
	 * @param string $searchFilter
	 * @return array
	 */
	public function getTreeMounts($searchFilter = '') {
		/** @var $nodeCollection tx_pagetree_NodeCollection */
		$nodeCollection = t3lib_div::makeInstance('tx_pagetree_NodeCollection');

		$isTemporaryMountPoint = FALSE;
		$webmountIds = intval($GLOBALS['BE_USER']->uc['pageTree_temporaryMountPoint']);
		if (!$webmountIds) {
			$webmountIds = array_map('intval', $GLOBALS['BE_USER']->returnWebmounts());
			$webmountIds = array_unique($webmountIds);
		} else {
			$isTemporaryMountPoint = TRUE;
			$webmountIds = array($webmountIds);
		}

		if (!count($webmountIds)) {
			return $nodeCollection;
		}

		$class = (count($webmountIds) <= 1 ? 'typo3-pagetree-node-notExpandable' : '');
		foreach ($webmountIds as $webmount) {
			if ($webmount === 0) {
				$sitename = 'TYPO3';
				if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] !== '') {
					$sitename = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
				}

				$record = array(
					'uid' => 0,
					'title' => $sitename,
				);
				$subNode = tx_pagetree_Commands::getNewNode($record);
				$subNode->setLabelIsEditable(FALSE);
			} else {
				$record = t3lib_BEfunc::getRecordWSOL('pages', $webmount, '*', '', TRUE);
				if (!$record) {
					continue;
				}

				$subNode = tx_pagetree_Commands::getNewNode($record, $webmount);
			}

			if ($webmount === 0 || $isTemporaryMountPoint) {
				$subNode->setType('pages_root');
			}

			$subNode->setExpanded(TRUE);
			$subNode->setDraggable(FALSE);
			$subNode->setIsDropTarget(FALSE);
			$subNode->setCls($class);

			if ($searchFilter === '') {
				$childNodes = $this->getNodes($subNode, $webmount);
			} else {
				$childNodes = $this->getFilteredNodes(intval($record['uid']), $searchFilter, $webmount);
				$subNode->setExpanded(TRUE);
			}

			$subNode->setChildNodes($childNodes);
			$nodeCollection->append($subNode);
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
	protected function getWhereClause($id, $searchFilter = '') {
		$where = $GLOBALS['BE_USER']->getPagePermsClause(1) .
			t3lib_BEfunc::deleteClause('pages') .
			t3lib_BEfunc::versioningPlaceholderClause('pages');

		if (is_numeric($id) && $id >= 0) {
			$where .= ' AND pid= ' . $GLOBALS['TYPO3_DB']->fullQuoteStr(intval($id), 'pages');
		}

		if ($searchFilter !== '') {
			$searchFilter = $GLOBALS['TYPO3_DB']->fullQuoteStr('%' . $searchFilter . '%', 'pages');
			$useNavTitle = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.showNavTitle');

			if ($useNavTitle) {
				$where .= ' AND (nav_title LIKE ' . $searchFilter .
					' OR (nav_title = "" && title LIKE ' . $searchFilter . '))';
			} else {
				$where .= ' AND title LIKE ' . $searchFilter;
			}
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
	protected function getSubpages($id, $searchFilter = '') {
		$where = $this->getWhereClause($id, $searchFilter);
		$subpages = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'uid', 'pages', $where, '', 'sorting', '', 'uid'
		);

		return $subpages;
	}

	/**
	 * Returns true if the node has child's
	 *
	 * @param int $id
	 * @return bool
	 */
	protected function hasNodeSubPages($id) {
		$where = $this->getWhereClause($id);
		$subpage = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
			'uid', 'pages', $where, '', 'sorting', '', 'uid'
		);

		$returnValue = TRUE;
		if (!$subpage['uid']) {
			$returnValue = FALSE;
		}

		return $returnValue;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/pagetree/classes/class.tx_pagetree_dataprovider.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/pagetree/classes/class.tx_pagetree_dataprovider.php']);
}

?>