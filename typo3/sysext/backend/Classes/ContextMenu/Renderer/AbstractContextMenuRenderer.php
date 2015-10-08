<?php
namespace TYPO3\CMS\Backend\ContextMenu\Renderer;

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
 * Abstract Context Menu Renderer
 */
abstract class AbstractContextMenuRenderer
{
    /**
     * Renders an action recursive or just a single one
     *
     * @param \TYPO3\CMS\Backend\ContextMenu\ContextMenuAction $action
     * @param bool $recursive
     * @return mixed
     */
    abstract public function renderAction(\TYPO3\CMS\Backend\ContextMenu\ContextMenuAction $action, $recursive = false);

    /**
     * Renders an action collection recursive or just a single one
     *
     * @param \TYPO3\CMS\Backend\ContextMenu\ContextMenuActionCollection $actionCollection
     * @param bool $recursive
     * @return mixed
     */
    abstract public function renderActionCollection(\TYPO3\CMS\Backend\ContextMenu\ContextMenuActionCollection $actionCollection, $recursive = false);

    /**
     * Renders a context menu recursive or just a single one
     *
     * @param \TYPO3\CMS\Backend\ContextMenu\AbstractContextMenu $contextMenu
     * @param bool $recursive
     * @return mixed
     */
    abstract public function renderContextMenu(\TYPO3\CMS\Backend\ContextMenu\AbstractContextMenu $contextMenu, $recursive = false);
}
