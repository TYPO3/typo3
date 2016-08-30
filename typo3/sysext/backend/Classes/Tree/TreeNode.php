<?php
namespace TYPO3\CMS\Backend\Tree;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Tree Node
 */
class TreeNode implements \TYPO3\CMS\Backend\Tree\ComparableNodeInterface, \Serializable
{
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
    protected $parentNode = null;

    /**
     * Child Nodes
     *
     * @var \TYPO3\CMS\Backend\Tree\TreeNodeCollection
     */
    protected $childNodes = null;

    /**
     * Constructor
     *
     * You can move an initial data array to initialize the instance and further objects.
     * This is useful for the deserialization.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->dataFromArray($data);
        }
    }

    /**
     * Sets the child nodes collection
     *
     * @param \TYPO3\CMS\Backend\Tree\TreeNodeCollection $childNodes
     * @return void
     */
    public function setChildNodes(\TYPO3\CMS\Backend\Tree\TreeNodeCollection $childNodes)
    {
        $this->childNodes = $childNodes;
    }

    /**
     * Removes child nodes collection
     *
     * @return void
     */
    public function removeChildNodes()
    {
        if ($this->childNodes !== null) {
            unset($this->childNodes);
            $this->childNodes = null;
        }
    }

    /**
     * Returns child nodes collection
     *
     * @return \TYPO3\CMS\Backend\Tree\TreeNodeCollection
     */
    public function getChildNodes()
    {
        return $this->childNodes;
    }

    /**
     * Returns TRUE if the node has child nodes attached
     *
     * @return bool
     */
    public function hasChildNodes()
    {
        if ($this->childNodes !== null) {
            return true;
        }
        return false;
    }

    /**
     * Sets the identifier
     *
     * @param string $id
     * @return void
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Returns the identifier
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets the parent node
     *
     * @param NULL|\TYPO3\CMS\Backend\Tree\TreeNode $parentNode
     * @return void
     */
    public function setParentNode(\TYPO3\CMS\Backend\Tree\TreeNode $parentNode = null)
    {
        $this->parentNode = $parentNode;
    }

    /**
     * Returns the parent node
     *
     * @return \TYPO3\CMS\Backend\Tree\TreeNode
     */
    public function getParentNode()
    {
        return $this->parentNode;
    }

    /**
     * Compares a node if it's identical to another node by the id property.
     *
     * @param \TYPO3\CMS\Backend\Tree\TreeNode $other
     * @return bool
     */
    public function equals(\TYPO3\CMS\Backend\Tree\TreeNode $other)
    {
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
     * @return int See description above
     */
    public function compareTo($other)
    {
        if ($this->equals($other)) {
            return 0;
        }
        return $this->id > $other->getId() ? 1 : -1;
    }

    /**
     * Returns the node in an array representation that can be used for serialization
     *
     * @param bool $addChildNodes
     * @return array
     */
    public function toArray($addChildNodes = true)
    {
        $arrayRepresentation = [
            'serializeClassName' => get_class($this),
            'id' => $this->id
        ];
        if ($this->parentNode !== null) {
            $arrayRepresentation['parentNode'] = $this->parentNode->toArray(false);
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
    public function dataFromArray($data)
    {
        $this->setId($data['id']);
        if (isset($data['parentNode']) && $data['parentNode'] !== '') {
            $this->setParentNode(GeneralUtility::makeInstance($data['parentNode']['serializeClassName'], $data['parentNode']));
        }
        if (isset($data['childNodes']) && $data['childNodes'] !== '') {
            $this->setChildNodes(GeneralUtility::makeInstance($data['childNodes']['serializeClassName'], $data['childNodes']));
        }
    }

    /**
     * Returns the serialized instance
     *
     * @return string
     */
    public function serialize()
    {
        return serialize($this->toArray());
    }

    /**
     * Fills the current node with the given serialized informations
     *
     * @throws \TYPO3\CMS\Core\Exception if the deserialized object type is not identical to the current one
     * @param string $serializedString
     * @return void
     */
    public function unserialize($serializedString)
    {
        $arrayRepresentation = unserialize($serializedString);
        if ($arrayRepresentation['serializeClassName'] !== get_class($this)) {
            throw new \TYPO3\CMS\Core\Exception('Deserialized object type is not identical!', 1294586646);
        }
        $this->dataFromArray($arrayRepresentation);
    }
}
