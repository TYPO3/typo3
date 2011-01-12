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
	 * Hidden Records
	 *
	 * @var array
	 */
	protected $hiddenRecords = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->hiddenRecords = t3lib_div::trimExplode(
			',',
			$GLOBALS['BE_USER']->getTSConfigVal('options.hideRecords.pages')
		);
	}

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
			if (in_array($subpage['uid'], $this->hiddenRecords)) {
				continue;
			}

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
	 * @param t3lib_tree_Node $node
	 * @param string $searchFilter
	 * @param int $mountPoint
	 * @return void
	 */
	public function getFilteredNodes(t3lib_tree_Node $node, $searchFilter, $mountPoint = 0) {
		/** @var $nodeCollection tx_pagetree_NodeCollection */
		$nodeCollection = t3lib_div::makeInstance('tx_pagetree_NodeCollection');

		$records = $this->getSubpages(-1, $searchFilter);
		if (!is_array($records) || !count($records)) {
			return $nodeCollection;
		}

		$nodeId = intval($node->getId());
		foreach ($records as $record) {
			$record = tx_pagetree_Commands::getNodeRecord($record['uid']);
			if (intval($record['pid']) === -1 || in_array($record['uid'], $this->hiddenRecords)) {
				continue;
			}

			$rootline = t3lib_BEfunc::BEgetRootLine($record['uid'], ' AND uid != ' . intval($nodeId));
			$rootline = array_reverse($rootline);
			if ($nodeId === 0) {
				array_shift($rootline);
			}
			$reference = $nodeCollection;

			$inFilteredRootline = FALSE;
			$amountOfRootlineElements = count($rootline);
			for ($i = 0; $i < $amountOfRootlineElements; ++$i) {
				$rootlineElement = $rootline[$i];
				if (intval($rootlineElement['pid']) === $nodeId) {
					$inFilteredRootline = TRUE;
				}

				if (!$inFilteredRootline) {
					continue;
				}

				$rootlineElement = tx_pagetree_Commands::getNodeRecord($rootlineElement['uid']);
				$ident = intval($rootlineElement['sorting']) . intval($rootlineElement['uid']);
				if ($reference->offsetExists($ident)) {
					/** @var $refNode tx_pagetree_Node */
					$refNode = $reference->offsetGet($ident);
					$refNode->setExpanded(TRUE);
					$refNode->setLeaf(FALSE);

					$reference = $refNode->getChildNodes();
					continue;
				}

				$refNode = tx_pagetree_Commands::getNewNode($rootlineElement, $mountPoint);
				$replacement = '<span class="typo3-pagetree-filteringTree-highlight">$1</span>';
				$text = preg_replace('/(' . $searchFilter . ')/i', $replacement, $refNode->getText());
				$refNode->setText($text, $refNode->getTextSourceField(), $refNode->getPrefix(), $refNode->getSuffix());

				/** @var $childCollection tx_pagetree_NodeCollection */
				$childCollection = t3lib_div::makeInstance('tx_pagetree_NodeCollection');

				if (($i +1) >= $amountOfRootlineElements) {
					$childNodes = $this->getNodes($refNode, $mountPoint);
					foreach ($childNodes as $childNode) {
						/** @var $childNode tx_pagetree_Node */
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
		$mountPoints = intval($GLOBALS['BE_USER']->uc['pageTree_temporaryMountPoint']);
		if (!$mountPoints) {
			$mountPoints = array_map('intval', $GLOBALS['BE_USER']->returnWebmounts());
			$mountPoints = array_unique($mountPoints);
		} else {
			$isTemporaryMountPoint = TRUE;
			$mountPoints = array($mountPoints);
		}

		if (!count($mountPoints)) {
			return $nodeCollection;
		}

		$showRootlineAboveMounts = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.showPathAboveMounts');
		$class = (count($mountPoints) <= 1 ? 'typo3-pagetree-node-notExpandable' : '');
		foreach ($mountPoints as $mountPoint) {
			if ($mountPoint === 0) {
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
				$subNode->setType('pages_root');
			} else {
				$record = t3lib_BEfunc::getRecordWSOL('pages', $mountPoint, '*', '', TRUE);
				if (!$record) {
					continue;
				}

				$subNode = tx_pagetree_Commands::getNewNode($record, $mountPoint);
				if ($showRootlineAboveMounts && !$isTemporaryMountPoint) {
					$rootline = tx_pagetree_Commands::getMountPointPath($record['uid']);
					$subNode->setReadableRootline($rootline);
				}
			}

			$subNode->setIsMountPoint(TRUE);
			$subNode->setExpanded(TRUE);
			$subNode->setDraggable(FALSE);
			$subNode->setIsDropTarget(FALSE);
			$subNode->setCls($class);

			if ($searchFilter === '') {
				$childNodes = $this->getNodes($subNode, $mountPoint);
			} else {
				$childNodes = $this->getFilteredNodes($subNode, $searchFilter, $mountPoint);
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