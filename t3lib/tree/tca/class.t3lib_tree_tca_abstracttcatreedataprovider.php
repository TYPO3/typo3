<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 Steffen Ritter <info@steffen-ritter.net>
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
 * An abstract TCA tree data provider
 *
 * @author Steffen Ritter <info@steffen-ritter.net>
 * @package TYPO3
 * @subpackage t3lib_tree
 */
abstract class t3lib_tree_Tca_AbstractTcaTreeDataProvider extends t3lib_tree_AbstractDataProvider {
	/**
	 * @var boolean
	 */
	protected $expandAll = FALSE;

	/**
	 * @var int
	 */
	protected $levelMaximum = 2;

	/**
	 * @var t3lib_tree_AbstractNode
	 */
	protected $treeData = NULL;

	/**
	 * @var string
	 */
	protected $nonSelectableLevelList = '0';

	/**
	 * @var string
	 */
	protected $expandedList = '';

	/**
	 * @var string
	 */
	protected $selectedList = '';

	/**
	 * Contains all ids which may be allowed to display according to
	 * beUser Rights and foreign_table_where (if type db)
	 *
	 * @var array $itemWhiteList
	 */
	protected $itemWhiteList = array();

	/**
	 * Contains all ids which are not allowed to be selected
	 */
	protected $itemUnselectableList = array();

	/**
	 * Sets the id of the tree
	 *
	 * @param string $treeId
	 * @return void
	 */
	public function setTreeId($treeId) {
		$this->treeId = $treeId;
	}

	/**
	 * Gets the id of the tree
	 * @return string
	 */
	public function getTreeId() {
		return $this->treeId;
	}


	/**
	 * Sets the expandAll
	 *
	 * @param bool $expandAll
	 * @return void
	 */
	public function setExpandAll($expandAll) {
		$this->expandAll = $expandAll;
	}

	/**
	 * Gets the expamdAll
	 *
	 * @return bool
	 */
	public function getExpandAll() {
		return $this->expandAll;
	}

	/**
	 * Sets the levelMaximum
	 *
	 * @param int $levelMaximum
	 * @return void
	 */
	public function setLevelMaximum($levelMaximum) {
		$this->levelMaximum = $levelMaximum;
	}

	/**
	 * Gets the levelMaximum
	 *
	 * @return int
	 */
	public function getLevelMaximum() {
		return $this->levelMaximum;
	}

	/**
	 * Gets the expanded state of a given node
	 *
	 * @param t3lib_tree_AbstractNode $node
	 * @return bool
	 */
	protected function isExpanded(t3lib_tree_Node $node) {
		return $this->getExpandAll() || t3lib_div::inList($this->expandedList, $node->getId());
	}


	/**
	 * Sets the list for expanded nodes
	 *
	 * @param string $expandedList
	 * @return void
	 */
	public function setExpandedList($expandedList) {
		$this->expandedList = $expandedList;
	}

	/**
	 * Gets the list for expanded nodes
	 *
	 * @return string
	 */
	public function getExpandedList() {
		return $this->expandedList;
	}

	/**
	 * Read the list for expanded nodes from user settings
	 *
	 * @return void
	 */
	public function initializeTreeData() {
		$this->expandedList = $GLOBALS['BE_USER']->uc['tcaTrees'][$this->treeId];
	}

	/**
	 * Sets the list for selected nodes
	 *
	 * @param  $selectedList
	 * @return void
	 */
	public function setSelectedList($selectedList) {
		$this->selectedList = $selectedList;
	}

	/**
	 * Gets the list for selected nodes
	 *
	 * @return string
	 */
	public function getSelectedList() {
		return $this->selectedList;
	}

	/**
	 * Sets the list for non selectabal tree levels
	 *
	 * @param  $nonSelectableLevelList
	 * @return void
	 */
	public function setNonSelectableLevelList($nonSelectableLevelList) {
		$this->nonSelectableLevelList = $nonSelectableLevelList;
	}

	/**
	 * Gets the list for non selectabal tree levels
	 *
	 * @return string
	 */
	public function getNonSelectableLevelList() {
		return $this->nonSelectableLevelList;
	}

	/**
	 * Setter for the itemWhiteList
	 *
	 * @param array $itemWhiteList
	 * @return void
	 */
	public function setItemWhiteList(array $itemWhiteList) {
		$this->itemWhiteList = $itemWhiteList;
	}

	/**
	 * Getter for the itemWhiteList
	 *
	 * @return array
	 */
	public function getItemWhiteList() {
		return $this->itemWhiteList;
	}

	/**
	 * Setter for $itemUnselectableList
	 *
	 * @param array $itemUnselectableList
	 * @return void
	 */
	public function setItemUnselectableList(array $itemUnselectableList) {
		$this->itemUnselectableList = $itemUnselectableList;
	}

	/**
	 * Getter for $itemUnselectableList
	 *
	 * @return array
	 */
	public function getItemUnselectableList() {
		return $this->itemUnselectableList;
	}
}

?>