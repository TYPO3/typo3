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
 * Testcase for class \TYPO3\CMS\Backend\Tree\TreeNode.
 */
class TreeNodeTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    //////////////////////
    // Utility functions
    //////////////////////
    /**
     * Returns the absolute fixtures path for this testcase.
     *
     * @return string the absolute fixtures path for this testcase, will not be empty
     */
    private function determineFixturesPath()
    {
        // We have to take the whole relative path as otherwise this test fails on Windows systems
        return PATH_site . 'typo3/sysext/backend/Tests/Unit/Tree/Fixtures/';
    }

    protected function setUpNodeTestData()
    {
        $fixture = new \TYPO3\CMS\Backend\Tree\TreeNode();
        $fixture->setId('Root');
        $nodeCollection = new \TYPO3\CMS\Backend\Tree\TreeNodeCollection();
        for ($i = 0; $i < 10; ++$i) {
            $node = new \TYPO3\CMS\Backend\Tree\TreeNode();
            $node->setId($i);
            $node->setParentNode($fixture);
            $subNodeCollection = new \TYPO3\CMS\Backend\Tree\TreeNodeCollection();
            for ($j = 0; $j < 5; ++$j) {
                $subNode = new \TYPO3\CMS\Backend\Tree\TreeRepresentationNode();
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
        return $fixture;
    }

    ///////////////
    // Test cases
    ///////////////
    /**
     * @test
     */
    public function serializeFixture()
    {
        $expected = trim(file_get_contents($this->determineFixturesPath() . 'serialized.txt'));
        $fixture = $this->setUpNodeTestData();
        $serializedString = trim($fixture->serialize());
        $this->assertSame($expected, $serializedString);
    }

    /**
     * @test
     */
    public function deserializeFixture()
    {
        $source = trim(file_get_contents($this->determineFixturesPath() . 'serialized.txt'));
        $node = new \TYPO3\CMS\Backend\Tree\TreeNode();
        $node->unserialize($source);
        $serializedString = $node->serialize();
        $this->assertSame($source, $serializedString);
    }

    /**
     * @test
     */
    public function compareNodes()
    {
        $node = new \TYPO3\CMS\Backend\Tree\TreeNode(['id' => '15']);
        $otherNode = new \TYPO3\CMS\Backend\Tree\TreeNode(['id' => '5']);
        $compareResult = $node->compareTo($otherNode);
        $otherNode->setId('25');
        $compareResult = $node->compareTo($otherNode);
        $this->assertSame(-1, $compareResult);
        $otherNode->setId('15');
        $compareResult = $node->compareTo($otherNode);
        $this->assertSame(0, $compareResult);
    }
}
