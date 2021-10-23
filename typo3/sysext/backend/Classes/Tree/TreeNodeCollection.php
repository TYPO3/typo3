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
 * Tree Node Collection
 */
class TreeNodeCollection extends \ArrayObject
{
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
        parent::__construct();
        if (!empty($data)) {
            $this->dataFromArray($data);
        }
    }

    /**
     * Sorts the internal nodes array
     *
     * @param int $flags Optional parameter, ignored. Added to be compatible with asort method signature in PHP 8.
     * @todo: Set return type to void in v12 as breaking patch and drop #[\ReturnTypeWillChange]
     */
    #[\ReturnTypeWillChange]
    public function asort($flags = SORT_REGULAR)
    {
        $this->uasort([$this, 'nodeCompare']);
    }

    /**
     * Compares a node with another one
     *
     * @see \TYPO3\CMS\Backend\Tree\TreeNode::compareTo
     * @internal
     * @param TreeNode $node
     * @param TreeNode $otherNode
     * @return int
     */
    public function nodeCompare(TreeNode $node, TreeNode $otherNode)
    {
        return $node->compareTo($otherNode);
    }

    /**
     * Returns the serialized instance
     *
     * @return string
     * @todo: Drop method and \Serializable class interface in v12.
     */
    #[\ReturnTypeWillChange]
    public function serialize()
    {
        return serialize($this->__serialize());
    }

    /**
     * Returns class state to be serialized.
     *
     * @todo: Change signature to __serialize(): array in v12
     */
    #[\ReturnTypeWillChange]
    public function __serialize()
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
    #[\ReturnTypeWillChange]
    public function unserialize($serializedString)
    {
        $this->__unserialize(unserialize($serializedString));
    }

    /**
     * Fills the current node with the given serialized information
     *
     * @throws Exception if the deserialized object type is not identical to the current one
     * @todo: Change signature to __unserialize(array $arrayRepresentation): void in v12
     */
    #[\ReturnTypeWillChange]
    public function __unserialize($arrayRepresentation)
    {
        if ($arrayRepresentation['serializeClassName'] !== static::class) {
            throw new Exception('Deserialized object type is not identical!', 1294586647);
        }
        $this->dataFromArray($arrayRepresentation);
    }

    /**
     * Returns the collection in an array representation for e.g. serialization
     *
     * @return array
     */
    public function toArray()
    {
        $arrayRepresentation = [
            'serializeClassName' => static::class,
        ];
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
     */
    public function dataFromArray($data)
    {
        unset($data['serializeClassName']);
        foreach ($data as $index => $nodeArray) {
            $node = GeneralUtility::makeInstance($nodeArray['serializeClassName'], $nodeArray);
            $this->offsetSet($index, $node);
        }
    }
}
