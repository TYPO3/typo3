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

use TYPO3\CMS\Backend\Tree\TreeNode;
use TYPO3\CMS\Backend\Tree\TreeNodeCollection;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for class \TYPO3\CMS\Backend\Tree\TreeNodeCollection.
 */
class TreeNodeCollectionTest extends UnitTestCase
{
    /**
     * @test
     */
    public function sortNodes()
    {
        $nodeCollection = new TreeNodeCollection([
            ['serializeClassName' => TreeNode::class, 'id' => 15],
            ['serializeClassName' => TreeNode::class, 'id' => 25],
            ['serializeClassName' => TreeNode::class, 'id' => 5],
            ['serializeClassName' => TreeNode::class, 'id' => 2],
            ['serializeClassName' => TreeNode::class, 'id' => 150],
            ['serializeClassName' => TreeNode::class, 'id' => 67]
        ]);
        $nodeCollection->asort();
        $expected = [2, 5, 15, 25, 67, 150];
        $ids = [];
        foreach ($nodeCollection as $node) {
            $ids[] = $node->getId();
        }
        self::assertSame($expected, $ids);
    }
}
