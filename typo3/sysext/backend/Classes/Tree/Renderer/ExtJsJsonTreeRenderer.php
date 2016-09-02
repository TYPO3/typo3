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
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

/**
 * Renderer for unordered lists
 */
class ExtJsJsonTreeRenderer extends \TYPO3\CMS\Backend\Tree\Renderer\AbstractTreeRenderer
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
     * @return array
     */
    public function renderNode(\TYPO3\CMS\Backend\Tree\TreeRepresentationNode $node, $recursive = true)
    {
        $nodeArray = $this->getNodeArray($node);
        if ($recursive && $node->hasChildNodes()) {
            $this->recursionLevel++;
            $children = $this->renderNodeCollection($node->getChildNodes());
            $nodeArray['children'] = $children;
            $this->recursionLevel--;
        }
        return $nodeArray;
    }

    /**
     * Get node array
     *
     * @param \TYPO3\CMS\Backend\Tree\TreeRepresentationNode $node
     * @return array
     */
    protected function getNodeArray(\TYPO3\CMS\Backend\Tree\TreeRepresentationNode $node)
    {
        $overlayIconMarkup  = '';
        if (is_object($node->getIcon())) {
            $iconMarkup = $node->getIcon()->getMarkup(SvgIconProvider::MARKUP_IDENTIFIER_INLINE);
            if (is_object($node->getIcon()->getOverlayIcon())) {
                $overlayIconMarkup = $node->getIcon()->getOverlayIcon()->getMarkup(SvgIconProvider::MARKUP_IDENTIFIER_INLINE);
            }
        } else {
            $iconMarkup = $node->getIcon();
        }
        $nodeArray = [
            'iconTag' => $iconMarkup,
            'text' => htmlspecialchars($node->getLabel()),
            'leaf' => !$node->hasChildNodes(),
            'id' => htmlspecialchars($node->getId()),
            'uid' => htmlspecialchars($node->getId()),

            //svgtree
            'icon' => $iconMarkup,
            'overlayIcon' => $overlayIconMarkup,
            'identifier' => htmlspecialchars($node->getId()),
            //no need for htmlspecialhars here as d3 is using 'textContent' property of the HTML DOM node
            'name' => $node->getLabel(),
        ];

        return $nodeArray;
    }

    /**
     * Renders a node collection recursive or just a single instance
     *
     * @param \TYPO3\CMS\Backend\Tree\TreeNodeCollection $node
     * @param bool $recursive
     * @return string
     */
    public function renderTree(\TYPO3\CMS\Backend\Tree\AbstractTree $tree, $recursive = true)
    {
        $this->recursionLevel = 0;
        $children = $this->renderNode($tree->getRoot(), $recursive);
        return json_encode($children);
    }

    /**
     * Renders an tree recursive or just a single instance
     *
     * @param \TYPO3\CMS\Backend\Tree\AbstractTree $node
     * @param bool $recursive
     * @return array
     */
    public function renderNodeCollection(\TYPO3\CMS\Backend\Tree\TreeNodeCollection $collection, $recursive = true)
    {
        foreach ($collection as $node) {
            $treeItems[] = $this->renderNode($node, $recursive);
        }
        return $treeItems;
    }
}
