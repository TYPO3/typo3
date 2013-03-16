<?php
namespace TYPO3\CMS\Backend\Tree\Renderer;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Steffen Kamper <steffen@typo3.org>
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
 * Renderer for unordered lists
 *
 * @author Steffen Kamper <steffen@typo3.org>
 * @author Steffen Ritter <info@steffen-ritter.net>
 */
class ExtJsJsonTreeRenderer extends \TYPO3\CMS\Backend\Tree\Renderer\AbstractTreeRenderer {

	/**
	 * recursion level
	 *
	 * @var integer
	 */
	protected $recursionLevel = 0;

	/**
	 * Renders a node recursive or just a single instance
	 *
	 * @param \TYPO3\CMS\Backend\Tree\TreeRepresentationNode $node
	 * @param boolean $recursive
	 * @return array
	 */
	public function renderNode(\TYPO3\CMS\Backend\Tree\TreeRepresentationNode $node, $recursive = TRUE) {
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
	protected function getNodeArray(\TYPO3\CMS\Backend\Tree\TreeRepresentationNode $node) {
		$nodeArray = array(
			'iconCls' => $node->getIcon(),
			'text' => $node->getLabel(),
			'leaf' => !$node->hasChildNodes(),
			'id' => $node->getId(),
			'uid' => $node->getId()
		);

		foreach ($nodeArray as &$nodeItem) {
			if (is_string($nodeItem)) {
				$nodeItem = htmlspecialchars($nodeItem);
			}
		}

		return $nodeArray;
	}

	/**
	 * Renders a node collection recursive or just a single instance
	 *
	 * @param \TYPO3\CMS\Backend\Tree\TreeNodeCollection $node
	 * @param boolean $recursive
	 * @return string
	 */
	public function renderTree(\TYPO3\CMS\Backend\Tree\AbstractTree $tree, $recursive = TRUE) {
		$this->recursionLevel = 0;
		$children = $this->renderNode($tree->getRoot(), $recursive);
		return json_encode($children);
	}

	/**
	 * Renders an tree recursive or just a single instance
	 *
	 * @param \TYPO3\CMS\Backend\Tree\AbstractTree $node
	 * @param boolean $recursive
	 * @return array
	 */
	public function renderNodeCollection(\TYPO3\CMS\Backend\Tree\TreeNodeCollection $collection, $recursive = TRUE) {
		foreach ($collection as $node) {
			$treeItems[] = $this->renderNode($node, $recursive);
		}
		return $treeItems;
	}

}


?>