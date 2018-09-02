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
     */
    public function asort()
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
    public function nodeCompare(\TYPO3\CMS\Backend\Tree\TreeNode $node, \TYPO3\CMS\Backend\Tree\TreeNode $otherNode)
    {
        return $node->compareTo($otherNode);
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
     * Initializes the current object with a serialized instance
     *
     * @param string $serializedString
     * @throws \TYPO3\CMS\Core\Exception if the deserialized is not identical to the current class
     */
    public function unserialize($serializedString)
    {
        $arrayRepresentation = unserialize($serializedString);
        if ($arrayRepresentation['serializeClassName'] !== static::class) {
            throw new \TYPO3\CMS\Core\Exception('Deserialized object type is not identical!', 1294586647);
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
            'serializeClassName' => static::class
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
            $node = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($nodeArray['serializeClassName'], $nodeArray);
            $this->offsetSet($index, $node);
        }
    }
}
