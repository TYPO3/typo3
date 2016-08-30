<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Tree;

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
 * Testcase for class \TYPO3\CMS\Backend\Tree\TreeNodeCollection.
 */
class TreeNodeCollectionTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function sortNodes()
    {
        $nodeCollection = new \TYPO3\CMS\Backend\Tree\TreeNodeCollection([
            ['serializeClassName' => \TYPO3\CMS\Backend\Tree\TreeNode::class, 'id' => 15],
            ['serializeClassName' => \TYPO3\CMS\Backend\Tree\TreeNode::class, 'id' => 25],
            ['serializeClassName' => \TYPO3\CMS\Backend\Tree\TreeNode::class, 'id' => 5],
            ['serializeClassName' => \TYPO3\CMS\Backend\Tree\TreeNode::class, 'id' => 2],
            ['serializeClassName' => \TYPO3\CMS\Backend\Tree\TreeNode::class, 'id' => 150],
            ['serializeClassName' => \TYPO3\CMS\Backend\Tree\TreeNode::class, 'id' => 67]
        ]);
        $nodeCollection->asort();
        $expected = [2, 5, 15, 25, 67, 150];
        $ids = [];
        foreach ($nodeCollection as $node) {
            $ids[] = $node->getId();
        }
        $this->assertSame($expected, $ids);
    }
}
