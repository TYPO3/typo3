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
	 * Returns the root node
	 *
	 * Not used for the page tree, because the multiple domains/roots feature
	 *
	 * @return t3lib_tree_Node
	 */
	public function getRoot() {
		return NULL;
	}

	/**
	 * Fetches the sub-nodes of the given node
	 *
	 * @param t3lib_tree_Node $node
	 * @return t3lib_tree_NodeCollection
	 */
	 public function getNodes(t3lib_tree_Node $node) {
		/** @var $nodeCollection tx_pagetree_NodeCollection */
		$nodeCollection = t3lib_div::makeInstance('tx_pagetree_NodeCollection');

		$subpages = $this->getSubpages($node->getId());
		if (!is_array($subpages) || !count($subpages)) {
			return $nodeCollection;
		}

		foreach ($subpages as $subpage) {
			$subpage = t3lib_befunc::getRecordWSOL('pages', $subpage['uid'], '*', '', TRUE);

			$subNode = tx_pagetree_Commands::getNewNode($subpage);
			$childNodes = $this->getNodes($subNode);
			$subNode->setChildNodes($childNodes);

			$nodeCollection->append($subNode);
		}

		return $nodeCollection;
	}

	/**
	 * Returns a node collection of filtered nodes
	 *
	 * @param string $searchFilter
	 * @return void
	 */
	public function getFilteredNodes($searchFilter) {
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

			foreach ($rootline as $rootlineElement) {
				if ($reference->offsetExists($rootlineElement['uid'])) {
					/** @var $node tx_pagetree_Node */
					$node = $reference->offsetGet($rootlineElement['uid']);
					$reference = $node->getChildNodes();
					continue;
				}

				/** @var $childCollection tx_pagetree_NodeCollection */
				$childCollection = t3lib_div::makeInstance('tx_pagetree_NodeCollection');

				$node = tx_pagetree_Commands::getNewNode($rootlineElement);
				$node->setExpanded(TRUE);
				$node->setChildNodes($childCollection);
				$node->setLeaf(FALSE);
				$node->setText(str_replace(
					$searchFilter,
					'<strong class="pagetree-highlight">' . $searchFilter . '</strong>',
					$node->getText()
				));

				$reference->offsetSet($rootlineElement['uid'], $node);
				$reference = $childCollection;
			}
		}

		return $nodeCollection;
	}

	/**
	 * Returns the page tree mounts for the current user
	 *
	 * @return array
	 */
	public function getTreeMounts() {
		/** @var $nodeCollection tx_pagetree_NodeCollection */
		$nodeCollection = t3lib_div::makeInstance('tx_pagetree_NodeCollection');

		$webmountIds = array_map('intval', $GLOBALS['BE_USER']->returnWebmounts());
		if (empty($webmountIds)) {
			return $nodeCollection;
		}

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
				$subNode->setEditableLable(FALSE);
				$subNode->setType('root');
			} else {
				$record = t3lib_BEfunc::getRecordWSOL('pages', $webmount, '*', '', TRUE);
				$subNode = tx_pagetree_Commands::getNewNode($record);
			}

			$childNodes = $this->getNodes($subNode);
			$subNode->setChildNodes($childNodes);

			$nodeCollection->append($subNode);
		}

		return $nodeCollection;
	}

	/**
	 * Returns all sub-pages of a given id
	 *
	 * @param int $id
	 * @param string $searchFilter
	 * @return array
	 */
	protected function getSubpages($id, $searchFilter = '') {
		$where = $GLOBALS['BE_USER']->getPagePermsClause(1)
			. t3lib_BEfunc::deleteClause('pages')
			. t3lib_BEfunc::versioningPlaceholderClause('pages');

		if (is_numeric($id) && $id >= 0) {
			$where .= ' AND pid= ' . $GLOBALS['TYPO3_DB']->fullQuoteStr(intval($id), 'pages');
		}

		if ($searchFilter !== '') {
			$where .= ' AND title LIKE ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('%' . $searchFilter . '%', 'pages');
		}

		$subpages = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'uid', 'pages', $where, '', 'sorting', '', 'uid'
		);

		return $subpages;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/sysext/pagetree/classes/class.tx_pagetree_dataprovider.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/sysext/pagetree/classes/class.tx_pagetree_dataprovider.php']);
}

?>