<?php

declare(strict_types=1);

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
use TYPO3\CMS\Backend\Tree\TreeRepresentationNode;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class TreeNodeTest extends UnitTestCase
{
    /**
     * @test
     */
    public function serializeFixture()
    {
        $expected = trim(file_get_contents(__DIR__ . '/Fixtures/serialized.txt'));
        $fixture = new TreeNode();
        $fixture->setId('Root');
        $nodeCollection = new TreeNodeCollection();
        for ($i = 0; $i < 10; ++$i) {
            $node = new TreeNode();
            $node->setId($i);
            $node->setParentNode($fixture);
            $subNodeCollection = new TreeNodeCollection();
            for ($j = 0; $j < 5; ++$j) {
                $subNode = new TreeRepresentationNode();
                $subNode->setId($j);
                $subNode->setLabel('SubTest');
                $subNode->setType('Type');
                $subNode->setClass('Class');
                $subNode->setIcon('Icon');
                $subNode->setCallbackAction('Callback Action');
                $subNode->setParentNode($node);
                $subNodeCollection->append($subNode);
            }
            $node->setChildNodes($subNodeCollection);
            $nodeCollection->append($node);
        }
        $fixture->setChildNodes($nodeCollection);
        $serializedString = trim($fixture->serialize());
        self::assertSame($expected, $serializedString);
    }

    /**
     * @test
     */
    public function deserializeFixture()
    {
        $source = trim(file_get_contents(__DIR__ . '/Fixtures/serialized.txt'));
        $node = new TreeNode();
        $node->unserialize($source);
        $serializedString = $node->serialize();
        self::assertSame($source, $serializedString);
    }

    /**
     * @test
     */
    public function compareNodes()
    {
        $node = new TreeNode(['id' => '15']);
        $otherNode = new TreeNode(['id' => '5']);
        $otherNode->setId('25');
        $compareResult = $node->compareTo($otherNode);
        self::assertSame(-1, $compareResult);
        $otherNode->setId('15');
        $compareResult = $node->compareTo($otherNode);
        self::assertSame(0, $compareResult);
    }
}
