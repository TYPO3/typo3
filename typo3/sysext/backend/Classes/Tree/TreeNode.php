<?php

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

namespace TYPO3\CMS\Backend\Tree;

use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Tree Node
 */
class TreeNode implements ComparableNodeInterface, \Serializable
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
     * @var \TYPO3\CMS\Backend\Tree\TreeNode|null
     */
    protected $parentNode;

    /**
     * Child Nodes
     *
     * @var \TYPO3\CMS\Backend\Tree\TreeNodeCollection|null
     */
    protected $childNodes;

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
     */
    public function setChildNodes(TreeNodeCollection $childNodes)
    {
        $this->childNodes = $childNodes;
    }

    /**
     * Removes child nodes collection
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
     * @param \TYPO3\CMS\Backend\Tree\TreeNode|null $parentNode
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
            'serializeClassName' => static::class,
            'id' => $this->id,
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
     */
    public function dataFromArray($data)
    {
        $this->setId($data['id']);
        if (isset($data['parentNode']) && $data['parentNode'] !== '') {
            /** @var TreeNode $parentNode */
            $parentNode = GeneralUtility::makeInstance($data['parentNode']['serializeClassName'], $data['parentNode']);
            $this->setParentNode($parentNode);
        }
        if (isset($data['childNodes']) && $data['childNodes'] !== '') {
            /** @var TreeNodeCollection $childNodes */
            $childNodes = GeneralUtility::makeInstance($data['childNodes']['serializeClassName'], $data['childNodes']);
            $this->setChildNodes($childNodes);
        }
    }

    /**
     * Returns the serialized instance
     *
     * @return string
     * @todo: Drop method and \Serializable class interface in v12.
     */
    public function serialize()
    {
        return serialize($this->__serialize());
    }

    /**
     * Returns class state to be serialized.
     */
    public function __serialize(): array
    {
        return $this->toArray();
    }

    /**
     * Create class state from serialized array.
     *
     * @param string $serializedString
     * @throws Exception if the deserialized object type is not identical to the current one
     * @todo: Drop method and \Serializable class interface in v12.
     */
    public function unserialize($serializedString)
    {
        $this->__unserialize(unserialize($serializedString));
    }

    /**
     * Fills the current node with the given serialized information
     *
     * @throws Exception if the deserialized object type is not identical to the current one
     */
    public function __unserialize(array $arrayRepresentation): void
    {
        if ($arrayRepresentation['serializeClassName'] !== static::class) {
            throw new Exception('Deserialized object type is not identical!', 1294586646);
        }
        $this->dataFromArray($arrayRepresentation);
    }
}
