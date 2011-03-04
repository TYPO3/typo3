<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Tolleiv Nietsch <typo3@tolleiv.de>
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
 * Interface for classes which perform pre or post processing
 *
 * @author Tolleiv Nietsch <typo3@tolleiv.de>
 * @package	TYPO3
 * @subpackage t3lib
 */
interface t3lib_tree_pagetree_interfaces_CollectionProcessor {

	/**
	 * @abstract
	 * @param t3lib_tree_pagetree_Node $node
	 * @param int $mountPoint
	 * @param int $level
	 * @param t3lib_tree_pagetree_NodeCollection $nodeCollection
	 * @return void
	 */
	public function postProcessGetNodes($node, $mountPoint, $level, $nodeCollection);

	/**
	 * @abstract
	 * @param t3lib_tree_pagetree_Node $node
	 * @param string $searchFilter
	 * @param int $mountPoint
	 * @param t3lib_tree_pagetree_NodeCollection $nodeCollection
	 * @return void
	 */
	public function postProcessFilteredNodes($node, $searchFilter, $mountPoint, $nodeCollection);

	/**
	 * @abstract
	 * @param string $searchFilter
	 * @param t3lib_tree_pagetree_NodeCollection $nodeCollection
	 * @return void
	 */
	public function postProcessGetTreeMounts($searchFilter, $nodeCollection);
}

?>