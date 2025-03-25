<?php

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

namespace TYPO3\CMS\Core\Tree\TableConfiguration;

use TYPO3\CMS\Backend\Tree\AbstractTreeDataProvider;
use TYPO3\CMS\Backend\Tree\TreeNode;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * An abstract TCA tree data provider
 */
abstract class AbstractTableConfigurationTreeDataProvider extends AbstractTreeDataProvider
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
     * @var TreeNode
     */
    protected $treeData;

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
     * @var mixed[]
     */
    protected $itemUnselectableList = [];

    /**
     * @todo: This is a hack to speed up category tree calculation. See the comments
     *        in TcaCategory and AbstractItemProvider FormEngine classes.
     * @internal
     */
    protected array $availableItems = [];

    /**
     * @var int[]
     */
    protected array $startingPoints = [0];

    /**
     * Sets the id of the tree
     *
     * @param string $treeId
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
     */
    public function setExpandAll($expandAll)
    {
        $this->expandAll = $expandAll;
    }

    /**
     * Gets the expandAll
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
     * @return bool
     */
    protected function isExpanded(TreeNode $node)
    {
        return $this->getExpandAll() || GeneralUtility::inList($this->expandedList, $node->getId());
    }

    /**
     * Init the tree data
     */
    public function initializeTreeData() {}

    /**
     * Sets the list for selected nodes
     *
     * @param string $selectedList
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

    /**
     * @internal See property comment
     */
    public function setAvailableItems(array $availableItems)
    {
        $this->availableItems = $availableItems;
    }

    /**
     * @param int[] $startingPoints
     */
    public function setStartingPoints(array $startingPoints): void
    {
        $this->startingPoints = $startingPoints;
    }

    /**
     * @return int[]
     */
    public function getStartingPoints(): array
    {
        return $this->startingPoints;
    }
}
