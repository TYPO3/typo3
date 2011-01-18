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
 * Abstract ExtJS tree based on ExtDirect
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 * @package TYPO3
 * @subpackage t3lib
 */
abstract class t3lib_tree_ExtDirect_AbstractExtJsTree extends t3lib_tree_AbstractTree {

	/**
	 * State Provider
	 *
	 * @var t3lib_tree_AbstractStateProvider
	 */
	protected $stateProvider = NULL;

	/**
	 * @param t3lib_tree_AbstractStateProvider $stateProvider
	 * @return void
	 */
	public function setStateProvider(t3lib_tree_AbstractStateProvider $stateProvider) {
		$this->stateProvider = $stateProvider;
	}

	/**
	 * @return t3lib_tree_AbstractStateProvider
	 */
	public function getStateProvider() {
		return $this->stateProvider;
	}

	/**
	 * Fetches the next tree level
	 *
	 * @abstract
	 * @param int $nodeId
	 * @param stdClass $nodeData
	 * @return array
	 */
	abstract public function getNextTreeLevel($nodeId, $nodeData);
}

?>