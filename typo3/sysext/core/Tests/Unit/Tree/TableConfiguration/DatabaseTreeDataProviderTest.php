<?php
namespace TYPO3\CMS\Core\Tests\Unit\Tree\TableConfiguration;

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

use TYPO3\CMS\Backend\Tree\TreeNode;
use TYPO3\CMS\Backend\Tree\TreeNodeCollection;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider;

/**
 * Testcase for \TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider
 */
class DatabaseTreeDataProviderTest extends UnitTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DatabaseTreeDataProvider|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $subject;

    /**
     * @var TreeNode
     */
    protected $treeData;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DatabaseConnection|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $database;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->database = $this->getMock(DatabaseConnection::class, ['exec_SELECTgetSingleRow']);
        $this->database->expects($this->any())->method('exec_SELECTgetSingleRow')->will($this->returnValue(['uid' => 0, 'parent' => '']));
        $this->treeData = new TreeNode();
        $GLOBALS['TYPO3_DB'] = $this->database;
    }

    /**
     * @param array $mockMethods
     */
    protected function initializeSubjectMock(array $mockMethods)
    {
        $this->subject = $this->getAccessibleMock(DatabaseTreeDataProvider::class, $mockMethods, [], '', false);
        $this->subject->expects($this->any())->method('getRootUid')->will($this->returnValue(0));
        $this->subject->_set('treeData', $this->treeData);
    }

    /**
     * @test
     */
    public function loadTreeDataLevelMaximumSetToZeroWorks()
    {
        $this->initializeSubjectMock(['getRelatedRecords', 'getRootUid', 'getChildrenOf']);
        $this->subject->_set('levelMaximum', 0);
        $this->subject->expects($this->never())->method('getChildrenOf');
        $this->subject->_call('loadTreeData');
    }

    /**
     * @test
     */
    public function loadTreeDataLevelMaximumSetToOneWorks()
    {
        $this->initializeSubjectMock(['getRelatedRecords', 'getRootUid', 'getChildrenOf']);
        $this->subject->_set('levelMaximum', 1);
        $this->subject->expects($this->once())->method('getChildrenOf')->with($this->treeData, 1);
        $this->subject->_call('loadTreeData');
    }

    /**
     * @test
     */
    public function getChildrenOfLevelMaximumSetToOneWorks()
    {
        $expectedTreeNode = new TreeNode();
        $expectedTreeNode->setId(1);
        $expectedStorage = new TreeNodeCollection();
        $expectedStorage->append($expectedTreeNode);

        $this->initializeSubjectMock(['getRelatedRecords', 'getRootUid']);
        $this->subject->_set('levelMaximum', 1);
        $this->subject->expects($this->once())->method('getRelatedRecords')->will($this->returnValue([1]));
        $storage = $this->subject->_call('getChildrenOf', $this->treeData, 1);

        $this->assertEquals($expectedStorage, $storage);
    }

    /**
     * @test
     */
    public function getChildrenOfLevelMaximumSetToTwoWorks()
    {
        $expectedStorage = new TreeNodeCollection();

        $expectedFirstLevelTreeNode = new TreeNode();
        $expectedFirstLevelTreeNode->setId(1);

        $expectedSecondLevelTreeNode = new TreeNode();
        $expectedSecondLevelTreeNode->setId(2);

        $expectedStorageOfSecondLevelChildren = new TreeNodeCollection();
        $expectedStorageOfSecondLevelChildren->append($expectedSecondLevelTreeNode);

        $expectedFirstLevelTreeNode->setChildNodes($expectedStorageOfSecondLevelChildren);
        $expectedStorage->append($expectedFirstLevelTreeNode);

        $this->initializeSubjectMock(['getRelatedRecords', 'getRootUid']);
        $this->subject->_set('levelMaximum', 2);
        $this->subject->expects($this->at(0))->method('getRelatedRecords')->will($this->returnValue([1]));
        $this->subject->expects($this->at(1))->method('getRelatedRecords')->will($this->returnValue([2]));
        $storage = $this->subject->_call('getChildrenOf', $this->treeData, 1);

        $this->assertEquals($expectedStorage, $storage);
    }
}
