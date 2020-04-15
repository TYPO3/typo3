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

namespace TYPO3\CMS\Backend\Tests\Unit\Tree;

use TYPO3\CMS\Backend\Tree\SortedTreeNodeCollection;
use TYPO3\CMS\Backend\Tree\TreeNode;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for class \TYPO3\CMS\Backend\Tree\SortedTreeNodeCollection.
 */
class SortedTreeNodeCollectionTest extends UnitTestCase
{
    protected function createTestCollection()
    {
        $nodeCollection = new SortedTreeNodeCollection();
        $node = new TreeNode(['id' => 5]);
        $nodeCollection->append($node);
        $node = new TreeNode(['id' => 15]);
        $nodeCollection->append($node);
        $node = new TreeNode(['id' => 3]);
        $nodeCollection->append($node);
        return $nodeCollection;
    }

    protected function createTestCollectionWithTwoNodes()
    {
        $nodeCollection = new SortedTreeNodeCollection();
        $node = new TreeNode(['id' => 5]);
        $nodeCollection->append($node);
        $node = new TreeNode(['id' => 3]);
        $nodeCollection->append($node);
        return $nodeCollection;
    }

    /**
     * @test
     */
    public function appendsSorted()
    {
        $nodeCollection = $this->createTestCollection();
        $expected = [3, 5, 15];
        $ids = [];
        foreach ($nodeCollection as $node) {
            $ids[] = $node->getId();
        }
        self::assertSame($expected, $ids);
    }

    /**
     * @test
     */
    public function collectionContainsNode()
    {
        $nodeCollection = $this->createTestCollection();
        $node = new TreeNode(['id' => 5]);
        self::assertTrue($nodeCollection->contains($node));
    }

    /**
     * @test
     */
    public function searchDataWithBinarySearch()
    {
        $nodeCollection = $this->createTestCollection();
        $node = new TreeNode(['id' => 15]);
        self::assertTrue($nodeCollection->contains($node));
        $node = new TreeNode(['id' => 99]);
        self::assertFalse($nodeCollection->contains($node));
        $nodeCollection = $this->createTestCollectionWithTwoNodes();
        $node = new TreeNode(['id' => 3]);
        self::assertTrue($nodeCollection->contains($node));
        $node = new TreeNode(['id' => 99]);
        self::assertFalse($nodeCollection->contains($node));
    }
}
