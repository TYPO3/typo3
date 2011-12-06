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
 * Tree data provider vor the virtual file system (VFS)
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_tree_vfs_TreeDataProvider extends t3lib_tree_AbstractDataProvider {

	/**
	 * @var t3lib_vfs_Domain_Repository_MountRepository
	 */
	protected $mountRepository;

	public function __construct() {
		$this->mountRepository = t3lib_div::makeInstance('t3lib_vfs_Domain_Repository_MountRepository');
	}

	/**
	 * Returns the root node
	 *
	 * @return t3lib_tree_Node
	 */
	public function getRoot() {
		return t3lib_div::makeInstance('t3lib_tree_RepresentationNode', array('id' => 0));
	}

	/**
	 * Fetches the subnodes of the given node
	 *
	 * @param t3lib_tree_Node $node
	 * @return t3lib_tree_NodeCollection
	 */
	public function getNodes(t3lib_tree_Node $node) {
		if ($node->getId() == 0) {
			$nodes = $this->getMountNodes();
		} else {
			//$mount =

			//$nodes = $this->getCollectionsInCollection($node, '/');
		}
		return $nodes;
	}

	/**
	 * @return array
	 */
	protected function getMountNodes() {
		$mounts = $this->mountRepository->findAll();

		/** @var $nodeCollection t3lib_tree_NodeCollection */
		$nodeCollection = t3lib_div::makeInstance('t3lib_tree_NodeCollection');
		foreach ($mounts as $mount) {
			/** @var $mount t3lib_vfs_Domain_Model_Mount */
			$nodeCollection->append($this->createRepresentationNodeForMount($mount));
		}
		return $nodeCollection;
	}

	protected function createRepresentationNodeForMount(t3lib_vfs_Domain_Model_Mount $mount) {
		$nodeData = array(
			'label' => $mount->getName(),
			'id' => $mount->getUid(),
			'icon' => t3lib_iconWorks::getSpriteIconClasses('apps-filetree-mount')
		);

		/** @var $node t3lib_tree_RepresentationNode */
		$node = t3lib_div::makeInstance('t3lib_tree_RepresentationNode', $nodeData);
		$node->setChildNodes($this->getSubcollectionNodeCollection($mount->getRootLevelStorageCollection()));

		return $node;
	}

	protected function createRepresentationNodeForCollection(t3lib_vfs_Domain_Model_StorageCollection $collection) {
		$nodeData = array(
			'label' => $collection->getName(),
			'id' => $collection->getMount()->getUid() . ':' . $collection->getIdentifier(),
			'icon' => t3lib_iconWorks::getSpriteIconClasses('apps-filetree-folder-default')
		);

		/** @var $node t3lib_tree_RepresentationNode */
		$node = t3lib_div::makeInstance('t3lib_tree_RepresentationNode', $nodeData);
		$node->setChildNodes($this->getSubcollectionNodeCollection($collection));

		return $node;
	}

	protected function getSubcollectionNodeCollection(t3lib_vfs_Domain_Model_StorageCollection $collection) {
		$children = $collection->getSubcollections();
		/** @var $nodeCollection t3lib_tree_NodeCollection */
		$nodeCollection = t3lib_div::makeInstance('t3lib_tree_NodeCollection');
		foreach ($children as $child) {
			$nodeCollection->append($this->createRepresentationNodeForCollection($child));
		}
		return $nodeCollection;
	}

	protected function getCollectionsInCollection(t3lib_tree_RepresentationNode $mount, $identifier) {
		//
	}
}
?>