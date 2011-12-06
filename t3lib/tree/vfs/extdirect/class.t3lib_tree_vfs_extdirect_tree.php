<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Andreas Wolf <andreas.wolf@ikt-werk.de>
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
 * Data Provider for the VFS
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_tree_vfs_extdirect_Tree extends t3lib_tree_ExtJs_AbstractStatefulExtJsTree {

	/**
	 * @var t3lib_tree_vfs_TreeDataProvider
	 */
	protected $dataProvider;

	public function getTreeData($parameters) {
		/** @var t3lib_tree_Renderer_ExtJsJson $renderer s*/
		$renderer = t3lib_div::makeInstance('t3lib_tree_Renderer_ExtJsJson');

		/** @var t3lib_tree_vfs_TreeDataProvider $dataProvider */
		$this->dataProvider = t3lib_div::makeInstance('t3lib_tree_vfs_TreeDataProvider');

		$node = $this->createNodeFromParameters($parameters);
		$nodeCollection = $this->dataProvider->getNodes($node);

		$renderedNodes = $renderer->renderNodeCollection($nodeCollection);

		return $renderedNodes;
	}

	protected function createNodeFromParameters($parameters) {
		if ($parameters->node == 0) {
			$node = $this->getRoot();
		} else {
			// TODO implement node creation from identifier string
			/*$nodeData = array(
				'id' => ,
				'label' =>
			);
			$node = t3lib_div::makeInstance('t3lib_tree_RepresentationNode', $nodeData);*/
		}

		return $node;
	}

	/**
	 * Returns the root node
	 *
	 * @return t3lib_tree_Node
	 */
	public function getRoot() {
		return $this->dataProvider->getRoot();
	}

	/**
	 * Fetches the next tree level
	 *
	 * @param int $nodeId
	 * @param stdClass $nodeData
	 * @return array
	 */
	public function getNextTreeLevel($nodeId, $nodeData) {
		//return $this->dataProvider->
	}
}