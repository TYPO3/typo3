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
 * Renderer for unordered lists
 */
class UnorderedListTreeRenderer extends \TYPO3\CMS\Backend\Tree\Renderer\AbstractTreeRenderer
{
    /**
     * recursion level
     *
     * @var int
     */
    protected $recursionLevel = 0;

    /**
     * Renders a node recursive or just a single instance
     *
     * @param \TYPO3\CMS\Backend\Tree\TreeRepresentationNode $node
     * @param bool $recursive
     * @return string
     */
    public function renderNode(\TYPO3\CMS\Backend\Tree\TreeRepresentationNode $node, $recursive = true)
    {
        $code = '<li><span class="' . htmlspecialchars($node->getIcon()) . '">&nbsp;</span>' . htmlspecialchars($node->getLabel());
        if ($recursive && $node->getChildNodes() !== null) {
            $this->recursionLevel++;
            $code .= $this->renderNodeCollection($node->getChildNodes());
            $this->recursionLevel--;
        }
        $code .= '</li>';
        return $code;
    }

    /**
     * Renders a node collection recursive or just a single instance
     *
     * @param \TYPO3\CMS\Backend\Tree\AbstractTree $tree
     * @param bool $recursive
     * @return string
     */
    public function renderTree(\TYPO3\CMS\Backend\Tree\AbstractTree $tree, $recursive = true)
    {
        $this->recursionLevel = 0;
        $code = '<ul class="level' . $this->recursionLevel . '" style="margin-left:10px">';
        $code .= $this->renderNode($tree->getRoot(), $recursive);
        $code .= '</ul>';
        return $code;
    }

    /**
     * Renders an tree recursive or just a single instance
     *
     * @param \TYPO3\CMS\Backend\Tree\TreeNodeCollection $collection
     * @param bool $recursive
     * @return string
     */
    public function renderNodeCollection(\TYPO3\CMS\Backend\Tree\TreeNodeCollection $collection, $recursive = true)
    {
        $code = '<ul class="level' . $this->recursionLevel . '" style="margin-left:10px">';
        foreach ($collection as $node) {
            $code .= $this->renderNode($node, $recursive);
        }
        $code .= '</ul>';
        return $code;
    }
}
