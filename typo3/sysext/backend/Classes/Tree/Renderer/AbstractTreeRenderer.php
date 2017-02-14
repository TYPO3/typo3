<?php
namespace TYPO3\CMS\Backend\Tree\Renderer;

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
 * Abstract Renderer
 */
abstract class AbstractTreeRenderer
{
    /**
     * Renders a node recursive or just a single instance
     *
     * @param \TYPO3\CMS\Backend\Tree\TreeRepresentationNode $node
     * @param bool $recursive
     * @return mixed
     */
    abstract public function renderNode(\TYPO3\CMS\Backend\Tree\TreeRepresentationNode $node, $recursive = true);

    /**
     * Renders a node collection recursive or just a single instance
     *
     * @param \TYPO3\CMS\Backend\Tree\TreeNodeCollection $collection
     * @param bool $recursive
     * @return mixed
     */
    abstract public function renderNodeCollection(\TYPO3\CMS\Backend\Tree\TreeNodeCollection $collection, $recursive = true);

    /**
     * Renders an tree recursive or just a single instance
     *
     * @param \TYPO3\CMS\Backend\Tree\AbstractTree $tree
     * @param bool $recursive
     * @return mixed
     */
    abstract public function renderTree(\TYPO3\CMS\Backend\Tree\AbstractTree $tree, $recursive = true);
}
