<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 TYPO3 Tree Team <http://forge.typo3.org/projects/typo3v4-extjstrees>
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
 * @author Steffen Ritter <info@steffen-ritter.net>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_tree_Renderer_UnorderedList extends t3lib_tree_Renderer_Abstract {
	/**
	 * recursion level
	 *
	 * @var int
	 */
	protected $recursionLevel = 0;

	/**
	 * Renders a node recursive or just a single instance
	 *
	 * @param t3lib_tree_RepresentationNode $node
	 * @param bool $recursive
	 * @return mixed
	 */
	public function renderNode(t3lib_tree_RepresentationNode $node, $recursive = TRUE) {
		$code = '<li><span class="' . $node->getIcon() . '">&nbsp;</span>' . $node->getLabel();
		if ($recursive && $node->getChildNodes() !== NULL) {
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
	 * @param t3lib_tree_NodeCollection $node
	 * @param bool $recursive
	 * @return mixed
	 */
	public function renderTree(t3lib_tree_AbstractTree $tree, $recursive = TRUE) {
		$this->recursionLevel = 0;
		$code = '<ul class="level' . $this->recursionLevel . '" style="margin-left:10px">';
		$code .= $this->renderNode($tree->getRoot(), $recursive);
		$code .= '</ul>';

		return $code;
	}

	/**
	 * Renders an tree recursive or just a single instance
	 *
	 * @param t3lib_tree_AbstractTree $node
	 * @param bool $recursive
	 * @return mixed
	 */
	public function renderNodeCollection(t3lib_tree_NodeCollection $collection, $recursive = TRUE) {
		$code = '<ul class="level' . $this->recursionLevel . '" style="margin-left:10px">';
		foreach ($collection as $node) {
			$code .= $this->renderNode($node, $recursive);
		}
		$code .= '</ul>';

		return $code;
	}
}

?>