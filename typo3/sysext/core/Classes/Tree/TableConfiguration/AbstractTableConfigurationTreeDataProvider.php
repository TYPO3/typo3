<?php
namespace TYPO3\CMS\Core\Tree\TableConfiguration;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Steffen Ritter <info@steffen-ritter.net>
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
 */
abstract class AbstractTableConfigurationTreeDataProvider extends \TYPO3\CMS\Backend\Tree\AbstractTreeDataProvider {

	/**
	 * @var boolean
	 */
	protected $expandAll = FALSE;

	/**
	 * @var integer
	 */
	protected $levelMaximum = 2;

	/**
	 * @var \TYPO3\CMS\Backend\Tree\TreeNode
	 */
	protected $treeData = NULL;

	/**
	 * @var string
	 */
	protected $treeId;

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
	 *
	 * @return string
	 */
	public function getTreeId() {
		return $this->treeId;
	}

	/**
	 * Sets the expandAll
	 *
	 * @param boolean $expandAll
	 * @return void
	 */
	public function setExpandAll($expandAll) {
		$this->expandAll = $expandAll;
	}

	/**
	 * Gets the expamdAll
	 *
	 * @return boolean
	 */
	public function getExpandAll() {
		return $this->expandAll;
	}

	/**
	 * Sets the levelMaximum
	 *
	 * @param integer $levelMaximum
	 * @return void
	 */
	public function setLevelMaximum($levelMaximum) {
		$this->levelMaximum = $levelMaximum;
	}

	/**
	 * Gets the levelMaximum
	 *
	 * @return integer
	 */
	public function getLevelMaximum() {
		return $this->levelMaximum;
	}

	/**
	 * Gets the expanded state of a given node
	 *
	 * @param \TYPO3\CMS\Backend\Tree\TreeNode $node
	 * @return boolean
	 */
	protected function isExpanded(\TYPO3\CMS\Backend\Tree\TreeNode $node) {
		return $this->getExpandAll() || \TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->expandedList, $node->getId());
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
	 * @param string $selectedList
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
	 * Sets the list for non selectable tree levels
	 *
	 * @param string $nonSelectableLevelList
	 * @return void
	 */
	public function setNonSelectableLevelList($nonSelectableLevelList) {
		$this->nonSelectableLevelList = $nonSelectableLevelList;
	}

	/**
	 * Gets the list for non selectable tree levels
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