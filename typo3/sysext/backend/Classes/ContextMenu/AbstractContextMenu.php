<?php
namespace TYPO3\CMS\Backend\ContextMenu;

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
 * Abstract Context Menu
 */
abstract class AbstractContextMenu
{
    /**
     * Data Provider
     *
     * @var \TYPO3\CMS\Backend\ContextMenu\AbstractContextMenuDataProvider
     */
    protected $dataProvider = null;

    /**
     * @param \TYPO3\CMS\Backend\ContextMenu\AbstractContextMenuDataProvider $dataProvider
     * @return void
     */
    public function setDataProvider(\TYPO3\CMS\Backend\ContextMenu\AbstractContextMenuDataProvider $dataProvider)
    {
        $this->dataProvider = $dataProvider;
    }

    /**
     * @return \TYPO3\CMS\Backend\ContextMenu\AbstractContextMenuDataProvider
     */
    public function getDataProvider()
    {
        return $this->dataProvider;
    }

    /**
     * Returns the actions for the given node information
     *
     * @param \TYPO3\CMS\Backend\Tree\TreeNode $node
     * @return array
     */
    abstract public function getActionsForNode(\TYPO3\CMS\Backend\Tree\TreeNode $node);
}
