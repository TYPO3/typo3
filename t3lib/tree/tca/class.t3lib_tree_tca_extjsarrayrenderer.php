<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Steffen Ritter <info@steffen-ritter.net>
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
 * Renders a tca tree array for ExtJS
 *
 * @author	Steffen Ritter <info@steffen-ritter.net>
 * @package TYPO3
 * @subpackage t3lib_tree
 */

class t3lib_tree_Tca_ExtJsArrayRenderer extends t3lib_tree_Renderer_ExtJsJson {

	/**
	 * Gets the node array. If the TCA configuration has defined items,
	 * they are added to rootlevel on top of the tree
	 *
	 * @param t3lib_tree_Tca_DatabaseNode $node
	 * @return array
	 */
	protected function getNodeArray(t3lib_tree_Tca_DatabaseNode $node) {
		$nodeArray = parent::getNodeArray($node);
		$nodeArray = array_merge(
			$nodeArray,
			array(
				 'expanded' => $node->getExpanded(),
				 'expandable' => $node->hasChildNodes(),
				 'checked' => $node->getSelected(),
			)
		);
		if (!$node->getSelectable()) {
			unset ($nodeArray['checked']);
		}

		return $nodeArray;
	}

	/**
	 * Renders a node collection recursive or just a single instance
	 *
	 * @param t3lib_tree_NodeCollection $node
	 * @param bool $recursive
	 * @return array
	 */
	public function renderTree(t3lib_tree_AbstractTree $tree, $recursive = TRUE) {
		$this->recursionLevel = 0;
		$children = $this->renderNode($tree->getRoot(), $recursive);

		return $children;
	}
}

?>