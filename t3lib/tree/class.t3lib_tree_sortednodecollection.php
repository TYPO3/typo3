<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 TYPO3 Tree Team <http://forge.typo3.org/projects/typo3v4-extjstrees>
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
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_tree_SortedNodeCollection extends t3lib_tree_NodeCollection {
	/**
	 * Checks if a specific node is inside the collection
	 *
	 * @param t3lib_tree_Node $node
	 * @return boolean
	 */
	public function contains(t3lib_tree_Node $node) {
		return $this->offsetOf($node) !== -1;
	}

	/**
	 * Returns the offset key of given node
	 *
	 * @param t3lib_tree_Node $node
	 * @return int
	 */
	protected function offsetOf(t3lib_tree_Node $node) {
		return $this->binarySearch($node, 0, $this->count() - 1);
	}

	/**
	 * Binary search that returns the offset of a given node
	 *
	 * @param t3lib_tree_Node $node
	 * @param int $start
	 * @param int $end
	 * @return int
	 */
	protected function binarySearch(t3lib_tree_Node $node, $start, $end) {
		if ((!$start && ($end - $start) >= 2) || ($end - $start) > 2) {
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
	 * @param t3lib_tree_Node $node
	 * @return void
	 */
	public function addNode(t3lib_tree_Node $node) {
		$this->append($node);
		$this->asort();
		$this->normalize();
	}

	/**
	 * Removes a specific node from the internal array
	 *
	 * @param t3lib_tree_Node $node
	 * @return void
	 */
	public function removeNode(t3lib_tree_Node $node) {
		$offset = $this->offsetOf($node);
		if ($offset !== -1) {
			$this->offsetUnset($offset);
			$this->normalize();
		}
	}
}

?>