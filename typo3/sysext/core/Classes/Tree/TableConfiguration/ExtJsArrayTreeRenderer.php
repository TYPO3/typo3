<?php
namespace TYPO3\CMS\Core\Tree\TableConfiguration;

/**
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
 * Renders a tca tree array for ExtJS
 *
 * @author Steffen Ritter <info@steffen-ritter.net>
 */
class ExtJsArrayTreeRenderer extends \TYPO3\CMS\Backend\Tree\Renderer\ExtJsJsonTreeRenderer {

	/**
	 * Gets the node array. If the TCA configuration has defined items,
	 * they are added to rootlevel on top of the tree
	 *
	 * @param \TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeNode $node
	 * @return array
	 */
	protected function getNodeArray(\TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeNode $node) {
		$nodeArray = parent::getNodeArray($node);
		$nodeArray = array_merge($nodeArray, array(
			'expanded' => $node->getExpanded(),
			'expandable' => $node->hasChildNodes(),
			'checked' => $node->getSelected()
		));
		if (!$node->getSelectable()) {
			unset($nodeArray['checked']);
		}
		return $nodeArray;
	}

	/**
	 * Renders a node collection recursive or just a single instance
	 *
	 * @param \TYPO3\CMS\Backend\Tree\TreeNodeCollection $node
	 * @param boolean $recursive
	 * @return array
	 */
	public function renderTree(\TYPO3\CMS\Backend\Tree\AbstractTree $tree, $recursive = TRUE) {
		$this->recursionLevel = 0;
		$children = $this->renderNode($tree->getRoot(), $recursive);
		return $children;
	}

}
