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
     *
     * @todo Use return type "true" instead of bool if PHP8.3+ is minimum supported and remove #[\ReturnTypeWillChange].
     */
    #[\ReturnTypeWillChange]
    public function asort(int $flags = SORT_REGULAR): bool
    {
        $this->uasort($this->nodeCompare(...));
        return true;
    }

    /**
     * Compares a node with another one
     *
     * @see \TYPO3\CMS\Backend\Tree\TreeNode::compareTo
     * @internal
     */
    public function nodeCompare(TreeNode $node, TreeNode $otherNode): int
    {
        return $node->compareTo($otherNode);
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
    public function __unserialize($data): void
    {
        if ($data['serializeClassName'] !== static::class) {
            throw new Exception('Deserialized object type is not identical!', 1294586647);
        }
        $this->dataFromArray($data);
    }

    /**
     * Returns the collection in an array representation for e.g. serialization
     */
    public function toArray(): array
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
     */
    public function dataFromArray(array $data): void
    {
        unset($data['serializeClassName']);
        foreach ($data as $index => $nodeArray) {
            $node = GeneralUtility::makeInstance($nodeArray['serializeClassName'], $nodeArray);
            $this->offsetSet($index, $node);
        }
    }
}
