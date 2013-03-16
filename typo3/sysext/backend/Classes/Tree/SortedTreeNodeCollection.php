<?php
namespace TYPO3\CMS\Backend\Tree;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 TYPO3 Tree Team <http://forge.typo3.org/projects/typo3v4-extjstrees>
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
 * Sorted Tree Node Collection
 *
 * Note: This collection works only with integers as offset keys and not
 * with much datasets. You have been warned!
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 * @author Steffen Ritter <info@steffen-ritter.net>
 */
class SortedTreeNodeCollection extends \TYPO3\CMS\Backend\Tree\TreeNodeCollection {

	/**
	 * Checks if a specific node is inside the collection
	 *
	 * @param \TYPO3\CMS\Backend\Tree\TreeNode $node
	 * @return boolean
	 */
	public function contains(\TYPO3\CMS\Backend\Tree\TreeNode $node) {
		return $this->offsetOf($node) !== -1;
	}

	/**
	 * Returns the offset key of given node
	 *
	 * @param \TYPO3\CMS\Backend\Tree\TreeNode $node
	 * @return int
	 */
	protected function offsetOf(\TYPO3\CMS\Backend\Tree\TreeNode $node) {
		return $this->binarySearch($node, 0, $this->count() - 1);
	}

	/**
	 * Binary search that returns the offset of a given node
	 *
	 * @param \TYPO3\CMS\Backend\Tree\TreeNode $node
	 * @param integer $start
	 * @param integer $end
	 * @return integer
	 */
	protected function binarySearch(\TYPO3\CMS\Backend\Tree\TreeNode $node, $start, $end) {
		if (!$start && $end - $start >= 2 || $end - $start > 2) {
			$divider = ceil(($end - $start) / 2);
			if ($this->offsetGet($divider)->equals($node)) {
				return $divider;
			} elseif ($this->offsetGet($divider)->compareTo($node) > 0) {
				return $this->binarySearch($node, $start, $divider - 1);
			} else {
				return $this->binarySearch($node, $divider + 1, $end);
			}
		} else {
			if ($this->offsetGet($start)->equals($node)) {
				return $start;
			} elseif ($this->offsetGet($end)->equals($node)) {
				return $end;
			} else {
				return -1;
			}
		}
	}

	/**
	 * Normalizes the array by reordering the keys
	 *
	 * @return void
	 */
	protected function normalize() {
		$nodes = array();
		foreach ($this as $node) {
			$nodes[] = $node;
		}
		$this->exchangeArray($nodes);
	}

	/**
	 * Adds a node to the internal list in a sorted approach
	 *
	 * @param \TYPO3\CMS\Backend\Tree\TreeNode $node
	 * @return void
	 */
	public function append(\TYPO3\CMS\Backend\Tree\TreeNode $node) {
		parent::append($node);
		$this->asort();
		$this->normalize();
	}

}


?>