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
 * Tree Node Collection
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 * @author Steffen Ritter <info@steffen-ritter.net>
 */
class TreeNodeCollection extends \ArrayObject {

	/**
	 * Constructor
	 *
	 * You can move an initial data array to initialize the instance and further objects.
	 * This is useful for the deserialization.
	 *
	 * @param array $data
	 */
	public function __construct(array $data = array()) {
		if (count($data)) {
			$this->dataFromArray($data);
		}
	}

	/**
	 * Sorts the internal nodes array
	 *
	 * @return void
	 */
	public function asort() {
		$this->uasort(array($this, 'nodeCompare'));
	}

	/**
	 * Compares a node with another one
	 *
	 * @see \TYPO3\CMS\Backend\Tree\TreeNode::compareTo
	 * @return void
	 * @noapi
	 */
	public function nodeCompare(\TYPO3\CMS\Backend\Tree\TreeNode $node, \TYPO3\CMS\Backend\Tree\TreeNode $otherNode) {
		return $node->compareTo($otherNode);
	}

	/**
	 * Returns the serialized instance
	 *
	 * @return string
	 */
	public function serialize() {
		return serialize($this->toArray());
	}

	/**
	 * Initializes the current object with a serialized instance
	 *
	 * @param string $serializedString
	 * @return void
	 * @throws \TYPO3\CMS\Core\Exception if the deserialized is not identical to the current class
	 */
	public function unserialize($serializedString) {
		$arrayRepresentation = unserialize($serializedString);
		if ($arrayRepresentation['serializeClassName'] !== get_class($this)) {
			throw new \TYPO3\CMS\Core\Exception('Deserialized object type is not identical!', 1294586647);
		}
		$this->dataFromArray($arrayRepresentation);
	}

	/**
	 * Returns the collection in an array representation for e.g. serialization
	 *
	 * @return array
	 */
	public function toArray() {
		$arrayRepresentation = array(
			'serializeClassName' => get_class($this)
		);
		$iterator = $this->getIterator();
		while ($iterator->valid()) {
			$arrayRepresentation[] = $iterator->current()->toArray();
			$iterator->next();
		}
		return $arrayRepresentation;
	}

	/**
	 * Sets the data of the node collection by a given array
	 *
	 * @param array $data
	 * @return void
	 */
	public function dataFromArray($data) {
		unset($data['serializeClassName']);
		foreach ($data as $index => $nodeArray) {
			$node = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($nodeArray['serializeClassName'], $nodeArray);
			$this->offsetSet($index, $node);
		}
	}

}


?>