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

namespace TYPO3\CMS\Core\Tests\Unit\Tree\TableConfiguration;

use Doctrine\DBAL\Result;
use PHPUnit\Framework\MockObject\MockObject;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Backend\Tree\TreeNode;
use TYPO3\CMS\Backend\Tree\TreeNodeCollection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionContainerInterface;
use TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for \TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider
 */
class DatabaseTreeDataProviderTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @var MockObject|DatabaseTreeDataProvider|AccessibleObjectInterface
     */
    protected $subject;

    protected ?TreeNode $treeData;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->treeData = new TreeNode();
    }

    /**
     * Setup prophecies for database stack
     *
     * @param int $instanceCount Number of instances of ConnectionPool::class to register
     * @return ObjectProphecy<QueryBuilder>
     */
    protected function setupDatabaseMock(int $instanceCount = 1): ObjectProphecy
    {
        // Prophecies and revelations for a lot of the database stack classes
        $connectionPoolProphecy = $this->prophesize(ConnectionPool::class);
        $queryRestrictionProphecy = $this->prophesize(QueryRestrictionContainerInterface::class);
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $expressionBuilderProphecy = $this->prophesize(ExpressionBuilder::class);
        $statementProphecy = $this->prophesize(Result::class);

        $expressionBuilderProphecy->eq(Argument::cetera())->willReturn('1=1');

        // Simulate method call flow on database objects and verify correct query is built
        $connectionPoolProphecy->getQueryBuilderForTable(Argument::cetera())
            ->shouldBeCalled()
            ->willReturn($queryBuilderProphecy->reveal());
        $queryRestrictionProphecy->removeAll()
            ->shouldBeCalled()
            ->willReturn($queryRestrictionProphecy->reveal());
        $queryBuilderProphecy->getRestrictions()
            ->shouldBeCalled()
            ->willReturn($queryRestrictionProphecy->reveal());
        $queryBuilderProphecy->expr()
            ->shouldBeCalled()
            ->willReturn($expressionBuilderProphecy->reveal());
        $queryBuilderProphecy->executeQuery()
            ->shouldBeCalled()
            ->willReturn($statementProphecy->reveal());

        $queryBuilderProphecy->select(Argument::cetera())
            ->shouldBeCalled()
            ->willReturn($queryBuilderProphecy->reveal());
        $queryBuilderProphecy->from(Argument::cetera())
            ->shouldBeCalled()
            ->willReturn($queryBuilderProphecy->reveal());
        $queryBuilderProphecy->createNamedParameter(Argument::cetera())
            ->shouldBeCalled()
            ->willReturnArgument(0);
        $queryBuilderProphecy->where(Argument::cetera())
            ->shouldBeCalled()
            ->willReturn($queryBuilderProphecy->reveal());
        $queryBuilderProphecy->setMaxResults(Argument::cetera())
            ->shouldBeCalled()
            ->willReturn($queryBuilderProphecy->reveal());

        $statementProphecy->fetchAssociative()
            ->shouldBeCalled()
            ->willReturn(['uid' => 0, 'parent' => '']);

        // Register connection pool revelation in framework, this is the entry point used by system under test
        for ($i = 1; $i <= $instanceCount; $i++) {
            GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphecy->reveal());
        }

        return $queryBuilderProphecy;
    }

    /**
     * @param array $mockMethods
     */
    protected function initializeSubjectMock(array $mockMethods): void
    {
        $this->subject = $this->getAccessibleMock(DatabaseTreeDataProvider::class, $mockMethods, [], '', false);
        $this->subject->method('getStartingPoints')->willReturn([0]);
        $this->subject->_set('treeData', $this->treeData);
    }

    /**
     * @test
     */
    public function loadTreeDataLevelMaximumSetToZeroWorks(): void
    {
        $this->initializeSubjectMock(['getRelatedRecords', 'getStartingPoints', 'getChildrenOf']);
        $this->subject->_set('levelMaximum', 0);
        $this->subject->expects(self::never())->method('getChildrenOf');
        $this->subject->_call('loadTreeData');
    }

    /**
     * @test
     */
    public function loadTreeDataLevelMaximumSetToOneWorks(): void
    {
        $this->initializeSubjectMock(['getRelatedRecords', 'getStartingPoints', 'getChildrenOf']);
        $this->subject->_set('levelMaximum', 1);
        $this->subject->expects(self::once())->method('getChildrenOf')->with($this->treeData, 1);
        $this->subject->_call('loadTreeData');
    }

    /**
     * @test
     */
    public function getChildrenOfLevelMaximumSetToOneWorks(): void
    {
        $this->setupDatabaseMock();

        $expectedTreeNode = new TreeNode();
        $expectedTreeNode->setId(1);
        $expectedStorage = new TreeNodeCollection();
        $expectedStorage->append($expectedTreeNode);

        $this->initializeSubjectMock(['getRelatedRecords', 'getStartingPoints']);
        $this->subject->_set('levelMaximum', 1);
        $this->subject->expects(self::once())->method('getRelatedRecords')->willReturn([1]);
        $storage = $this->subject->_call('getChildrenOf', $this->treeData, 1);

        self::assertEquals($expectedStorage, $storage);
    }

    /**
     * @test
     */
    public function getChildrenOfLevelMaximumSetToTwoWorks(): void
    {
        $this->setupDatabaseMock(2);

        $expectedStorage = new TreeNodeCollection();

        $expectedFirstLevelTreeNode = new TreeNode();
        $expectedFirstLevelTreeNode->setId(1);

        $expectedSecondLevelTreeNode = new TreeNode();
        $expectedSecondLevelTreeNode->setId(2);

        $expectedStorageOfSecondLevelChildren = new TreeNodeCollection();
        $expectedStorageOfSecondLevelChildren->append($expectedSecondLevelTreeNode);

        $expectedFirstLevelTreeNode->setChildNodes($expectedStorageOfSecondLevelChildren);
        $expectedStorage->append($expectedFirstLevelTreeNode);

        $this->initializeSubjectMock(['getRelatedRecords', 'getStartingPoints']);
        $this->subject->_set('levelMaximum', 2);
        $this->subject->expects(self::exactly(2))->method('getRelatedRecords')->willReturnOnConsecutiveCalls([1], [2]);
        $storage = $this->subject->_call('getChildrenOf', $this->treeData, 1);

        self::assertEquals($expectedStorage, $storage);
    }
}
