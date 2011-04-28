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
 * Tree Node
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 * @author Steffen Ritter <info@steffen-ritter.net>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_tree_Node implements t3lib_tree_ComparableNode, Serializable {
	/**
	 * Node Identifier
	 *
	 * @var string
	 */
	protected $id = '';

	/**
	 * Parent Node
	 *
	 * @var t3lib_tree_Node
	 */
	protected $parentNode = NULL;

	/**
	 * Child Nodes
	 *
	 * @var t3lib_tree_NodeCollection
	 */
	protected $childNodes = NULL;

	/**
	 * Constructor
	 *
	 * You can move an initial data array to initialize the instance and further objects.
	 * This is useful for the deserialization.
	 *
	 * @param array $data
	 * @return void
	 */
	public function __construct(array $data = array()) {
		if (count($data)) {
			$this->dataFromArray($data);
		}
	}

	/**
	 * Sets the child nodes collection
	 *
	 * @param t3lib_tree_NodeCollection $childNodes
	 * @return void
	 */
	public function setChildNodes(t3lib_tree_NodeCollection $childNodes) {
		$this->childNodes = $childNodes;
	}

	/**
	 * Removes child nodes collection
	 *
	 * @return void
	 */
	public function removeChildNodes() {
		if ($this->childNodes !== NULL) {
			unset($this->childNodes);
			$this->childNodes = NULL;
		}
	}

	/**
	 * Returns child nodes collection
	 *
	 * @return t3lib_tree_NodeCollection
	 */
	public function getChildNodes() {
		return $this->childNodes;
	}

	/**
	 * Returns TRUE if the node has child nodes attached
	 *
	 * @return boolean
	 */
	public function hasChildNodes() {
		if ($this->childNodes !== NULL) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Sets the identifier
	 *
	 * @param string $id
	 * @return void
	 */
	public function setId($id) {
		$this->id = $id;
	}

	/**
	 * Returns the identifier
	 *
	 * @return string
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Sets the parent node
	 *
	 * @param t3lib_tree_Node $parentNode
	 * @return void
	 */
	public function setParentNode(t3lib_tree_Node $parentNode = NULL) {
		$this->parentNode = $parentNode;
	}

	/**
	 * Returns the parent node
	 *
	 * @return t3lib_tree_Node
	 */
	public function getParentNode() {
		return $this->parentNode;
	}

	/**
	 * Compares a node if it's identical to another node by the id property.
	 *
	 * @param t3lib_tree_Node $other
	 * @return boolean
	 */
	public function equals(t3lib_tree_Node $other) {
		return $this->id == $other->getId();
	}

	/**
	 * Compares a node to another one.
	 *
	 * Returns:
	 * 1 if its greater than the other one
	 * -1 if its smaller than the other one
	 * 0 if its equal
	 *
	 * @param t3lib_tree_Node $other
	 * @return int see description above
	 */
	public function compareTo($other) {
		if ($this->equals($other)) {
			return 0;
		}

		return ($this->id > $other->getId()) ? 1 : -1;
	}

	/**
	 * Returns the node in an array representation that can be used for serialization
	 *
	 * @param bool $addChildNodes
	 * @return array
	 */
	public function toArray($addChildNodes = TRUE) {
		$arrayRepresentation = array(
			'serializeClassName' => get_class($this),
			'id' => $this->id
		);

		if ($this->parentNode !== NULL) {
			$arrayRepresentation['parentNode'] = $this->parentNode->toArray(FALSE);
		} else {
			$arrayRepresentation['parentNode'] = '';
		}

		if ($this->hasChildNodes() && $addChildNodes) {
			$arrayRepresentation['childNodes'] = $this->childNodes->toArray();
		} else {
			$arrayRepresentation['childNodes'] = '';
		}

		return $arrayRepresentation;
	}

	/**
	 * Sets data of the node by a given data array
	 *
	 * @param array $data
	 * @return void
	 */
	public function dataFromArray($data) {
		$this->setId($data['id']);

		if (isset($data['parentNode']) && $data['parentNode'] !== '') {
			$this->setParentNode(t3lib_div::makeInstance(
				$data['parentNode']['serializeClassName'],
				$data['parentNode']
			));
		}

		if (isset($data['childNodes']) && $data['childNodes'] !== '') {
			$this->setChildNodes(t3lib_div::makeInstance(
				$data['childNodes']['serializeClassName'],
				$data['childNodes']
			));
		}
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
	 * Fills the current node with the given serialized informations
	 *
	 * @throws t3lib_exception if the deserialized object type is not identical to the current one
	 * @param string $serializedString
	 * @return void
	 */
	public function unserialize($serializedString) {
		$arrayRepresentation = unserialize($serializedString);
		if ($arrayRepresentation['serializeClassName'] !== get_class($this)) {
			throw new t3lib_exception('Deserialized object type is not identical!', 1294586646);
		}
		$this->dataFromArray($arrayRepresentation);
	}
}

?>