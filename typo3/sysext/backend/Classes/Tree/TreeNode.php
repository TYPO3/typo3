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
class TreeNode implements ComparableNodeInterface
{
    /**
     * Node Identifier
     *
     * @var string|int
     */
    protected $id = '';

    /**
     * Parent Node
     *
     * @var TreeNode|null
     */
    protected $parentNode;

    /**
     * Child Nodes
     *
     * @var TreeNodeCollection|null
     */
    protected $childNodes;

    /**
     * @internal This is part of the category tree performance hack.
     */
    protected array $additionalData = [];

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
        if ($data !== []) {
            $this->dataFromArray($data);
        }
    }

    /**
     * Sets the child nodes collection
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
     * @return TreeNodeCollection
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
     * @param string|int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Returns the identifier
     *
     * @return string|int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets the parent node
     *
     * @param TreeNode|null $parentNode
     */
    public function setParentNode(TreeNode $parentNode = null)
    {
        $this->parentNode = $parentNode;
    }

    /**
     * Returns the parent node
     *
     * @return TreeNode
     */
    public function getParentNode()
    {
        return $this->parentNode;
    }

    /**
     * Compares a node if it's identical to another node by the id property.
     *
     * @return bool
     */
    public function equals(TreeNode $other)
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
     * @param TreeNode $other
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
     * @internal This is part of the category tree performance hack
     */
    public function getAdditionalData(): array
    {
        return $this->additionalData;
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
        $this->setId($data['id'] ?? $data['uid']);
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
        // @todo: This is part of the category tree performance hack
        $this->additionalData = $data;
    }

    /**
     * Returns class state to be serialized.
     */
    public function __serialize(): array
    {
        return $this->toArray();
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
