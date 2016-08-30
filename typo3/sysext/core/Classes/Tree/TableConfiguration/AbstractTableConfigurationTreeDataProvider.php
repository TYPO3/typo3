<?php
namespace TYPO3\CMS\Core\Tree\TableConfiguration;

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

/**
 * An abstract TCA tree data provider
 */
abstract class AbstractTableConfigurationTreeDataProvider extends \TYPO3\CMS\Backend\Tree\AbstractTreeDataProvider
{
    /**
     * @var bool
     */
    protected $expandAll = false;

    /**
     * @var int
     */
    protected $levelMaximum = 4;

    /**
     * @var \TYPO3\CMS\Backend\Tree\TreeNode
     */
    protected $treeData = null;

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
    protected $itemWhiteList = [];

    /**
     * Contains all ids which are not allowed to be selected
     */
    protected $itemUnselectableList = [];

    /**
     * Sets the id of the tree
     *
     * @param string $treeId
     * @return void
     */
    public function setTreeId($treeId)
    {
        $this->treeId = $treeId;
    }

    /**
     * Gets the id of the tree
     *
     * @return string
     */
    public function getTreeId()
    {
        return $this->treeId;
    }

    /**
     * Sets the expandAll
     *
     * @param bool $expandAll
     * @return void
     */
    public function setExpandAll($expandAll)
    {
        $this->expandAll = $expandAll;
    }

    /**
     * Gets the expamdAll
     *
     * @return bool
     */
    public function getExpandAll()
    {
        return $this->expandAll;
    }

    /**
     * Sets the levelMaximum
     *
     * @param int $levelMaximum
     * @return void
     */
    public function setLevelMaximum($levelMaximum)
    {
        $this->levelMaximum = $levelMaximum;
    }

    /**
     * Gets the levelMaximum
     *
     * @return int
     */
    public function getLevelMaximum()
    {
        return $this->levelMaximum;
    }

    /**
     * Gets the expanded state of a given node
     *
     * @param \TYPO3\CMS\Backend\Tree\TreeNode $node
     * @return bool
     */
    protected function isExpanded(\TYPO3\CMS\Backend\Tree\TreeNode $node)
    {
        return $this->getExpandAll() || \TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->expandedList, $node->getId());
    }

    /**
     * Sets the list for expanded nodes
     *
     * @param string $expandedList
     * @return void
     */
    public function setExpandedList($expandedList)
    {
        $this->expandedList = $expandedList;
    }

    /**
     * Gets the list for expanded nodes
     *
     * @return string
     */
    public function getExpandedList()
    {
        return $this->expandedList;
    }

    /**
     * Read the list for expanded nodes from user settings
     *
     * @return void
     */
    public function initializeTreeData()
    {
        $this->expandedList = $GLOBALS['BE_USER']->uc['tcaTrees'][$this->treeId];
    }

    /**
     * Sets the list for selected nodes
     *
     * @param string $selectedList
     * @return void
     */
    public function setSelectedList($selectedList)
    {
        $this->selectedList = $selectedList;
    }

    /**
     * Gets the list for selected nodes
     *
     * @return string
     */
    public function getSelectedList()
    {
        return $this->selectedList;
    }

    /**
     * Sets the list for non selectable tree levels
     *
     * @param string $nonSelectableLevelList
     * @return void
     */
    public function setNonSelectableLevelList($nonSelectableLevelList)
    {
        $this->nonSelectableLevelList = $nonSelectableLevelList;
    }

    /**
     * Gets the list for non selectable tree levels
     *
     * @return string
     */
    public function getNonSelectableLevelList()
    {
        return $this->nonSelectableLevelList;
    }

    /**
     * Setter for the itemWhiteList
     *
     * @param array $itemWhiteList
     * @return void
     */
    public function setItemWhiteList(array $itemWhiteList)
    {
        $this->itemWhiteList = $itemWhiteList;
    }

    /**
     * Getter for the itemWhiteList
     *
     * @return array
     */
    public function getItemWhiteList()
    {
        return $this->itemWhiteList;
    }

    /**
     * Setter for $itemUnselectableList
     *
     * @param array $itemUnselectableList
     * @return void
     */
    public function setItemUnselectableList(array $itemUnselectableList)
    {
        $this->itemUnselectableList = $itemUnselectableList;
    }

    /**
     * Getter for $itemUnselectableList
     *
     * @return array
     */
    public function getItemUnselectableList()
    {
        return $this->itemUnselectableList;
    }
}
