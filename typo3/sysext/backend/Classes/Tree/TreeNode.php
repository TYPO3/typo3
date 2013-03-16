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
 * Tree Node
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 * @author Steffen Ritter <info@steffen-ritter.net>
 */
class TreeNode implements \TYPO3\CMS\Backend\Tree\ComparableNodeInterface, \Serializable {

	/**
	 * Node Identifier
	 *
	 * @var string
	 */
	protected $id = '';

	/**
	 * Parent Node
	 *
	 * @var \TYPO3\CMS\Backend\Tree\TreeNode
	 */
	protected $parentNode = NULL;

	/**
	 * Child Nodes
	 *
	 * @var \TYPO3\CMS\Backend\Tree\TreeNodeCollection
	 */
	protected $childNodes = NULL;

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
	 * Sets the child nodes collection
	 *
	 * @param \TYPO3\CMS\Backend\Tree\TreeNodeCollection $childNodes
	 * @return void
	 */
	public function setChildNodes(\TYPO3\CMS\Backend\Tree\TreeNodeCollection $childNodes) {
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
	 * @return \TYPO3\CMS\Backend\Tree\TreeNodeCollection
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
	 * @param NULL|\TYPO3\CMS\Backend\Tree\TreeNode $parentNode
	 * @return void
	 */
	public function setParentNode(\TYPO3\CMS\Backend\Tree\TreeNode $parentNode = NULL) {
		$this->parentNode = $parentNode;
	}

	/**
	 * Returns the parent node
	 *
	 * @return \TYPO3\CMS\Backend\Tree\TreeNode
	 */
	public function getParentNode() {
		return $this->parentNode;
	}

	/**
	 * Compares a node if it's identical to another node by the id property.
	 *
	 * @param \TYPO3\CMS\Backend\Tree\TreeNode $other
	 * @return boolean
	 */
	public function equals(\TYPO3\CMS\Backend\Tree\TreeNode $other) {
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
	 * @param \TYPO3\CMS\Backend\Tree\TreeNode $other
	 * @return integer See description above
	 */
	public function compareTo($other) {
		if ($this->equals($other)) {
			return 0;
		}
		return $this->id > $other->getId() ? 1 : -1;
	}

	/**
	 * Returns the node in an array representation that can be used for serialization
	 *
	 * @param boolean $addChildNodes
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
			$this->setParentNode(\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($data['parentNode']['serializeClassName'], $data['parentNode']));
		}
		if (isset($data['childNodes']) && $data['childNodes'] !== '') {
			$this->setChildNodes(\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($data['childNodes']['serializeClassName'], $data['childNodes']));
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
	 * @throws \TYPO3\CMS\Core\Exception if the deserialized object type is not identical to the current one
	 * @param string $serializedString
	 * @return void
	 */
	public function unserialize($serializedString) {
		$arrayRepresentation = unserialize($serializedString);
		if ($arrayRepresentation['serializeClassName'] !== get_class($this)) {
			throw new \TYPO3\CMS\Core\Exception('Deserialized object type is not identical!', 1294586646);
		}
		$this->dataFromArray($arrayRepresentation);
	}

}


?>