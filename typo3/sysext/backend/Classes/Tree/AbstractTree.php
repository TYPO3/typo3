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

namespace TYPO3\CMS\Backend\Tree;

use TYPO3\CMS\Backend\Tree\Renderer\AbstractTreeRenderer;

/**
 * Abstract Tree
 */
abstract class AbstractTree
{
    /**
     * Data Provider
     *
     * @var \TYPO3\CMS\Backend\Tree\AbstractTreeDataProvider
     */
    protected $dataProvider;

    /**
     * Tree Node Decorator
     *
     * @var \TYPO3\CMS\Backend\Tree\Renderer\AbstractTreeRenderer
     */
    protected $nodeRenderer;

    /**
     * @param \TYPO3\CMS\Backend\Tree\AbstractTreeDataProvider $dataProvider
     */
    public function setDataProvider(AbstractTreeDataProvider $dataProvider)
    {
        $this->dataProvider = $dataProvider;
    }

    /**
     * @return \TYPO3\CMS\Backend\Tree\AbstractTreeDataProvider
     */
    public function getDataProvider()
    {
        return $this->dataProvider;
    }

    /**
     * @param \TYPO3\CMS\Backend\Tree\Renderer\AbstractTreeRenderer $nodeRenderer
     */
    public function setNodeRenderer(AbstractTreeRenderer $nodeRenderer)
    {
        $this->nodeRenderer = $nodeRenderer;
    }

    /**
     * @return \TYPO3\CMS\Backend\Tree\Renderer\AbstractTreeRenderer
     */
    public function getNodeRenderer()
    {
        return $this->nodeRenderer;
    }

    /**
     * Returns the root node
     *
     * @return \TYPO3\CMS\Backend\Tree\TreeNode
     */
    abstract public function getRoot();
}
