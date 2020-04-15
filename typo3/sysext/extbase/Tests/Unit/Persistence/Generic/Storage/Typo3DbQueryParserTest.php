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

namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence\Generic\Storage;

use Prophecy\Argument;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception\InconsistentQuerySettingsException;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnsupportedOrderException;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\AndInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\ComparisonInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\NotInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\OrInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\Selector;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\SourceInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbQueryParser;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Service\EnvironmentService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class Typo3DbQueryParserTest extends UnitTestCase
{
    /**
     * Clean up after tests
     */
    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function convertQueryToDoctrineQueryBuilderDoesNotAddAndWhereWithEmptyConstraint()
    {
        // Prepare subject, turn off initialize qb method and inject qb prophecy revelation
        $subject = $this->getAccessibleMock(
            Typo3DbQueryParser::class,
            // Shut down some methods not important for this test
            ['initializeQueryBuilder', 'parseOrderings', 'addTypo3Constraints']
        );
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $subject->_set('queryBuilder', $queryBuilderProphecy->reveal());

        $queryProphecy = $this->prophesize(QueryInterface::class);
        $sourceProphecy = $this->prophesize(SourceInterface::class);
        $queryProphecy->getSource()->willReturn($sourceProphecy->reveal());
        $queryProphecy->getOrderings()->willReturn([]);
        $queryProphecy->getStatement()->willReturn(null);

        // Test part: getConstraint returns no constraint object, andWhere() should not be called
        $queryProphecy->getConstraint()->willReturn(null);
        $queryBuilderProphecy->andWhere()->shouldNotBeCalled();

        $subject->convertQueryToDoctrineQueryBuilder($queryProphecy->reveal());
    }

    /**
     * @test
     */
    public function convertQueryToDoctrineQueryBuilderThrowsExceptionOnNotImplementedConstraint()
    {
        // Prepare subject, turn off initialize qb method and inject qb prophecy revelation
        $subject = $this->getAccessibleMock(
            Typo3DbQueryParser::class,
            // Shut down some methods not important for this test
            ['initializeQueryBuilder', 'parseOrderings', 'addTypo3Constraints', 'parseComparison']
        );
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $subject->_set('queryBuilder', $queryBuilderProphecy->reveal());

        $queryProphecy = $this->prophesize(QueryInterface::class);
        $sourceProphecy = $this->prophesize(SourceInterface::class);
        $queryProphecy->getSource()->willReturn($sourceProphecy->reveal());
        $queryProphecy->getOrderings()->willReturn([]);
        $queryProphecy->getStatement()->willReturn(null);

        // Test part: getConstraint returns not implemented object
        $constraintProphecy = $this->prophesize(ConstraintInterface::class);
        $queryProphecy->getConstraint()->willReturn($constraintProphecy->reveal());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1476199898);
        $subject->convertQueryToDoctrineQueryBuilder($queryProphecy->reveal());
    }

    /**
     * @test
     */
    public function convertQueryToDoctrineQueryBuilderAddsSimpleAndWhere()
    {
        // Prepare subject, turn off initialize qb method and inject qb prophecy revelation
        $subject = $this->getAccessibleMock(
            Typo3DbQueryParser::class,
            // Shut down some methods not important for this test
            ['initializeQueryBuilder', 'parseOrderings', 'addTypo3Constraints', 'parseComparison']
        );
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $subject->_set('queryBuilder', $queryBuilderProphecy->reveal());

        $queryProphecy = $this->prophesize(QueryInterface::class);
        $sourceProphecy = $this->prophesize(SourceInterface::class);
        $queryProphecy->getSource()->willReturn($sourceProphecy->reveal());
        $queryProphecy->getOrderings()->willReturn([]);
        $queryProphecy->getStatement()->willReturn(null);

        // Test part: getConstraint returns simple constraint, and should push to andWhere()
        $constraintProphecy = $this->prophesize(ComparisonInterface::class);
        $queryProphecy->getConstraint()->willReturn($constraintProphecy->reveal());
        $subject->expects(self::once())->method('parseComparison')->willReturn('heinz');
        $queryBuilderProphecy->andWhere('heinz')->shouldBeCalled();

        $subject->convertQueryToDoctrineQueryBuilder($queryProphecy->reveal());
    }

    /**
     * @test
     */
    public function convertQueryToDoctrineQueryBuilderAddsNotConstraint()
    {
        // Prepare subject, turn off initialize qb method and inject qb prophecy revelation
        $subject = $this->getAccessibleMock(
            Typo3DbQueryParser::class,
            // Shut down some methods not important for this test
            ['initializeQueryBuilder', 'parseOrderings', 'addTypo3Constraints', 'parseComparison']
        );
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $subject->_set('queryBuilder', $queryBuilderProphecy->reveal());

        $queryProphecy = $this->prophesize(QueryInterface::class);
        $sourceProphecy = $this->prophesize(SourceInterface::class);
        $queryProphecy->getSource()->willReturn($sourceProphecy->reveal());
        $queryProphecy->getOrderings()->willReturn([]);
        $queryProphecy->getStatement()->willReturn(null);

        $constraintProphecy = $this->prophesize(NotInterface::class);
        $subConstraintProphecy = $this->prophesize(ComparisonInterface::class);
        $constraintProphecy->getConstraint()->shouldBeCalled()->willReturn($subConstraintProphecy->reveal());
        $queryProphecy->getConstraint()->willReturn($constraintProphecy->reveal());
        $subject->expects(self::once())->method('parseComparison')->willReturn('heinz');
        $queryBuilderProphecy->andWhere(' NOT(heinz)')->shouldBeCalled();

        $subject->convertQueryToDoctrineQueryBuilder($queryProphecy->reveal());
    }

    /**
     * @test
     */
    public function convertQueryToDoctrineQueryBuilderAddsAndConstraint()
    {
        // Prepare subject, turn off initialize qb method and inject qb prophecy revelation
        $subject = $this->getAccessibleMock(
            Typo3DbQueryParser::class,
            // Shut down some methods not important for this test
            ['initializeQueryBuilder', 'parseOrderings', 'addTypo3Constraints', 'parseComparison']
        );
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $subject->_set('queryBuilder', $queryBuilderProphecy->reveal());

        $queryProphecy = $this->prophesize(QueryInterface::class);
        $sourceProphecy = $this->prophesize(SourceInterface::class);
        $queryProphecy->getSource()->willReturn($sourceProphecy->reveal());
        $queryProphecy->getOrderings()->willReturn([]);
        $queryProphecy->getStatement()->willReturn(null);

        $constraintProphecy = $this->prophesize(AndInterface::class);
        $queryProphecy->getConstraint()->willReturn($constraintProphecy->reveal());
        $constraint1Prophecy = $this->prophesize(ComparisonInterface::class);
        $constraintProphecy->getConstraint1()->willReturn($constraint1Prophecy->reveal());
        $constraint2Prophecy = $this->prophesize(ComparisonInterface::class);
        $constraintProphecy->getConstraint2()->willReturn($constraint2Prophecy->reveal());
        $subject->expects(self::any())->method('parseComparison')->willReturn('heinz');
        $expressionProphecy = $this->prophesize(ExpressionBuilder::class);
        $queryBuilderProphecy->expr()->shouldBeCalled()->willReturn($expressionProphecy->reveal());
        $compositeExpressionProphecy = $this->prophesize(CompositeExpression::class);
        $compositeExpressionProphecy->__toString()->willReturn('heinz AND heinz');
        $compositeExpressionRevelation = $compositeExpressionProphecy->reveal();
        $expressionProphecy->andX('heinz', 'heinz')->shouldBeCalled()->willReturn($compositeExpressionRevelation);
        $queryBuilderProphecy->andWhere($compositeExpressionRevelation)->shouldBeCalled();

        $subject->convertQueryToDoctrineQueryBuilder($queryProphecy->reveal());
    }

    /**
     * @test
     */
    public function convertQueryToDoctrineQueryBuilderNotAddsInvalidAndConstraint()
    {
        // Prepare subject, turn off initialize qb method and inject qb prophecy revelation
        $subject = $this->getAccessibleMock(
            Typo3DbQueryParser::class,
            // Shut down some methods not important for this test
            ['initializeQueryBuilder', 'parseOrderings', 'addTypo3Constraints', 'parseComparison']
        );
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $subject->_set('queryBuilder', $queryBuilderProphecy->reveal());

        $queryProphecy = $this->prophesize(QueryInterface::class);
        $sourceProphecy = $this->prophesize(SourceInterface::class);
        $queryProphecy->getSource()->willReturn($sourceProphecy->reveal());
        $queryProphecy->getOrderings()->willReturn([]);
        $queryProphecy->getStatement()->willReturn(null);

        $constraintProphecy = $this->prophesize(AndInterface::class);
        $queryProphecy->getConstraint()->willReturn($constraintProphecy->reveal());
        $constraint1Prophecy = $this->prophesize(ComparisonInterface::class);
        $constraintProphecy->getConstraint1()->willReturn($constraint1Prophecy->reveal());
        // no result for constraint2
        $constraintProphecy->getConstraint2()->willReturn(null);

        // not be called
        $queryBuilderProphecy->andWhere()->shouldNotBeCalled();

        $subject->convertQueryToDoctrineQueryBuilder($queryProphecy->reveal());
    }

    /**
     * @test
     */
    public function convertQueryToDoctrineQueryBuilderAddsOrConstraint()
    {
        // Prepare subject, turn off initialize qb method and inject qb prophecy revelation
        $subject = $this->getAccessibleMock(
            Typo3DbQueryParser::class,
            // Shut down some methods not important for this test
            ['initializeQueryBuilder', 'parseOrderings', 'addTypo3Constraints', 'parseComparison']
        );
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $subject->_set('queryBuilder', $queryBuilderProphecy->reveal());

        $queryProphecy = $this->prophesize(QueryInterface::class);
        $sourceProphecy = $this->prophesize(SourceInterface::class);
        $queryProphecy->getSource()->willReturn($sourceProphecy->reveal());
        $queryProphecy->getOrderings()->willReturn([]);
        $queryProphecy->getStatement()->willReturn(null);

        $constraintProphecy = $this->prophesize(OrInterface::class);
        $queryProphecy->getConstraint()->willReturn($constraintProphecy->reveal());
        $constraint1Prophecy = $this->prophesize(ComparisonInterface::class);
        $constraintProphecy->getConstraint1()->willReturn($constraint1Prophecy->reveal());
        $constraint2Prophecy = $this->prophesize(ComparisonInterface::class);
        $constraintProphecy->getConstraint2()->willReturn($constraint2Prophecy->reveal());
        $subject->expects(self::any())->method('parseComparison')->willReturn('heinz');
        $expressionProphecy = $this->prophesize(ExpressionBuilder::class);
        $queryBuilderProphecy->expr()->shouldBeCalled()->willReturn($expressionProphecy->reveal());
        $compositeExpressionProphecy = $this->prophesize(CompositeExpression::class);
        $compositeExpressionProphecy->__toString()->willReturn('heinz OR heinz');
        $compositeExpressionRevelation = $compositeExpressionProphecy->reveal();
        $expressionProphecy->orX('heinz', 'heinz')->shouldBeCalled()->willReturn($compositeExpressionRevelation);
        $queryBuilderProphecy->andWhere($compositeExpressionRevelation)->shouldBeCalled();

        $subject->convertQueryToDoctrineQueryBuilder($queryProphecy->reveal());
    }

    /**
     * @test
     */
    public function convertQueryToDoctrineQueryBuilderNotAddsInvalidOrConstraint()
    {
        // Prepare subject, turn off initialize qb method and inject qb prophecy revelation
        $subject = $this->getAccessibleMock(
            Typo3DbQueryParser::class,
            // Shut down some methods not important for this test
            ['initializeQueryBuilder', 'parseOrderings', 'addTypo3Constraints', 'parseComparison']
        );
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $subject->_set('queryBuilder', $queryBuilderProphecy->reveal());

        $queryProphecy = $this->prophesize(QueryInterface::class);
        $sourceProphecy = $this->prophesize(SourceInterface::class);
        $queryProphecy->getSource()->willReturn($sourceProphecy->reveal());
        $queryProphecy->getOrderings()->willReturn([]);
        $queryProphecy->getStatement()->willReturn(null);

        $constraintProphecy = $this->prophesize(OrInterface::class);
        $queryProphecy->getConstraint()->willReturn($constraintProphecy->reveal());
        $constraint1Prophecy = $this->prophesize(ComparisonInterface::class);
        $constraintProphecy->getConstraint1()->willReturn($constraint1Prophecy->reveal());
        // no result for constraint2
        $constraintProphecy->getConstraint2()->willReturn(null);

        // not be called
        $queryBuilderProphecy->andWhere()->shouldNotBeCalled();

        $subject->convertQueryToDoctrineQueryBuilder($queryProphecy->reveal());
    }

    /**
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    protected function getQueryBuilderWithExpressionBuilderProphet()
    {
        $connectionProphet = $this->prophesize(Connection::class);
        $connectionProphet->quoteIdentifier(Argument::cetera())->willReturnArgument(0);
        $queryBuilderProphet = $this->prophesize(QueryBuilder::class, $connectionProphet->reveal());
        $expr = GeneralUtility::makeInstance(ExpressionBuilder::class, $connectionProphet->reveal());
        $queryBuilderProphet->expr()->willReturn($expr);
        return $queryBuilderProphet;
    }

    /**
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    protected function getQueryBuilderProphetWithQueryBuilderForSubselect()
    {
        $connectionProphet = $this->prophesize(Connection::class);
        $connectionProphet->quoteIdentifier(Argument::cetera())->willReturnArgument(0);
        $queryBuilderProphet = $this->prophesize(QueryBuilder::class, $connectionProphet->reveal());
        $expr = GeneralUtility::makeInstance(ExpressionBuilder::class, $connectionProphet->reveal());
        $queryBuilderProphet->expr()->willReturn(
            $expr
        );
        $queryBuilderProphet->getConnection()->willReturn($connectionProphet->reveal());
        $queryBuilderForSubselectMock = $this->getMockBuilder(QueryBuilder::class)
            ->setMethods(['expr', 'unquoteSingleIdentifier'])
            ->setConstructorArgs([$connectionProphet->reveal()])
            ->getMock();
        $connectionProphet->createQueryBuilder()->willReturn($queryBuilderForSubselectMock);
        $queryBuilderForSubselectMock->expects(self::any())->method('expr')->willReturn($expr);
        $queryBuilderForSubselectMock->expects(self::any())->method('unquoteSingleIdentifier')->willReturnCallback(function ($identifier) {
            return $identifier;
        });
        return $queryBuilderProphet;
    }

    /**
     * @test
     */
    public function addGetLanguageStatementWorksForDefaultLanguage()
    {
        $table = StringUtility::getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'][$table]['ctrl'] = [
            'languageField' => 'sys_language_uid'
        ];
        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings|\PHPUnit\Framework\MockObject\MockObject $querySettings */
        $querySettings = $this->createMock(Typo3QuerySettings::class);
        $mockTypo3DbQueryParser = $this->getAccessibleMock(Typo3DbQueryParser::class, ['dummy'], [], '', false);
        $queryBuilderProphet = $this->getQueryBuilderWithExpressionBuilderProphet();
        $mockTypo3DbQueryParser->_set('queryBuilder', $queryBuilderProphet->reveal());
        $sql = $mockTypo3DbQueryParser->_call('getLanguageStatement', $table, $table, $querySettings);
        $expectedSql = $table . '.sys_language_uid IN (0, -1)';
        self::assertSame($expectedSql, $sql);
    }

    /**
     * @test
     */
    public function addGetLanguageStatementWorksForNonDefaultLanguage()
    {
        $table = StringUtility::getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'][$table]['ctrl'] = [
            'languageField' => 'sys_language_uid'
        ];
        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings|\PHPUnit\Framework\MockObject\MockObject $querySettings */
        $querySettings = $this->getMockBuilder(Typo3QuerySettings::class)
            ->setMethods(['dummy'])
            ->getMock();
        $querySettings->setLanguageUid('1');
        $mockTypo3DbQueryParser = $this->getAccessibleMock(Typo3DbQueryParser::class, ['dummy'], [], '', false);
        $queryBuilderProphet = $this->getQueryBuilderWithExpressionBuilderProphet();
        $mockTypo3DbQueryParser->_set('queryBuilder', $queryBuilderProphet->reveal());
        $sql = $mockTypo3DbQueryParser->_call('getLanguageStatement', $table, $table, $querySettings);
        $result = $table . '.sys_language_uid IN (1, -1)';
        self::assertSame($result, $sql);
    }

    /**
     * @test
     */
    public function addGetLanguageStatementWorksInBackendContextWithNoGlobalTypoScriptFrontendControllerAvailable()
    {
        $table = StringUtility::getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'][$table]['ctrl'] = [
            'languageField' => 'sys_language_uid'
        ];
        $querySettings = new Typo3QuerySettings();
        $mockTypo3DbQueryParser = $this->getAccessibleMock(Typo3DbQueryParser::class, ['dummy'], [], '', false);
        $queryBuilderProphet = $this->getQueryBuilderWithExpressionBuilderProphet();
        $mockTypo3DbQueryParser->_set('queryBuilder', $queryBuilderProphet->reveal());
        $sql = $mockTypo3DbQueryParser->_call('getLanguageStatement', $table, $table, $querySettings);
        $expectedSql = $table . '.sys_language_uid IN (0, -1)';
        self::assertSame($expectedSql, $sql);
    }

    /**
     * @test
     */
    public function addGetLanguageStatementWorksForDefaultLanguageWithoutDeleteStatementReturned()
    {
        $table = StringUtility::getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'][$table]['ctrl'] = [
            'languageField' => 'sys_language_uid',
            'delete' => 'deleted'
        ];
        $querySettings = new Typo3QuerySettings();
        $querySettings->setLanguageUid(0);
        $mockTypo3DbQueryParser = $this->getAccessibleMock(Typo3DbQueryParser::class, ['dummy'], [], '', false);
        $queryBuilderProphet = $this->getQueryBuilderWithExpressionBuilderProphet();
        $mockTypo3DbQueryParser->_set('queryBuilder', $queryBuilderProphet->reveal());
        $sql = $mockTypo3DbQueryParser->_call('getLanguageStatement', $table, $table, $querySettings);
        $expectedSql = $table . '.sys_language_uid IN (0, -1)';
        self::assertSame($expectedSql, $sql);
    }

    /**
     * @test
     */
    public function addGetLanguageStatementWorksForForeignLanguageWithoutSubselection()
    {
        $table = StringUtility::getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'][$table]['ctrl'] = [
            'languageField' => 'sys_language_uid'
        ];
        $querySettings = new Typo3QuerySettings();
        $querySettings->setLanguageUid(2);
        $mockTypo3DbQueryParser = $this->getAccessibleMock(Typo3DbQueryParser::class, ['dummy'], [], '', false);
        $queryBuilderProphet = $this->getQueryBuilderWithExpressionBuilderProphet();
        $mockTypo3DbQueryParser->_set('queryBuilder', $queryBuilderProphet->reveal());
        $sql = $mockTypo3DbQueryParser->_call('getLanguageStatement', $table, $table, $querySettings);
        $expectedSql = $table . '.sys_language_uid IN (2, -1)';
        self::assertSame($expectedSql, $sql);
    }

    /**
     * @test
     */
    public function addGetLanguageStatementWorksForForeignLanguageWithSubselectionWithoutDeleteStatementReturned()
    {
        $table = StringUtility::getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'][$table]['ctrl'] = [
            'languageField' => 'sys_language_uid',
            'transOrigPointerField' => 'l10n_parent'
        ];
        $querySettings = new Typo3QuerySettings();
        $querySettings->setLanguageUid(2);
        $mockTypo3DbQueryParser = $this->getAccessibleMock(Typo3DbQueryParser::class, ['dummy'], [], '', false);

        $queryBuilderProphet = $this->getQueryBuilderProphetWithQueryBuilderForSubselect();

        $mockTypo3DbQueryParser->_set('queryBuilder', $queryBuilderProphet->reveal());

        $compositeExpression = $mockTypo3DbQueryParser->_call('getLanguageStatement', $table, $table, $querySettings);
        $expectedSql = '(' . $table . '.sys_language_uid = -1) OR ((' . $table . '.sys_language_uid = 2) AND (' . $table . '.l10n_parent IN (SELECT ' . $table . '_dl.uid FROM ' . $table . ' ' . $table . '_dl WHERE (' . $table . '_dl.l10n_parent = 0) AND (' . $table . '_dl.sys_language_uid = 0)))) OR ((' . $table . '.sys_language_uid = 0) AND (' . $table . '.uid NOT IN (SELECT ' . $table . '_to.l10n_parent FROM ' . $table . ' ' . $table . '_dl, ' . $table . ' ' . $table . '_to WHERE (' . $table . '_to.l10n_parent > 0) AND (' . $table . '_to.sys_language_uid = 2))))';
        self::assertSame($expectedSql, $compositeExpression->__toString());
    }

    /**
     * @test
     */
    public function addGetLanguageStatementWorksForForeignLanguageWithSubselectionTakesDeleteStatementIntoAccountIfNecessary()
    {
        $table = StringUtility::getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'][$table]['ctrl'] = [
            'languageField' => 'sys_language_uid',
            'transOrigPointerField' => 'l10n_parent',
            'delete' => 'deleted'
        ];
        $querySettings = new Typo3QuerySettings();
        $querySettings->setLanguageUid(2);
        $mockTypo3DbQueryParser = $this->getAccessibleMock(Typo3DbQueryParser::class, ['dummy'], [], '', false);
        $queryBuilderProphet = $this->getQueryBuilderProphetWithQueryBuilderForSubselect();
        $mockTypo3DbQueryParser->_set('queryBuilder', $queryBuilderProphet->reveal());
        $compositeExpression= $mockTypo3DbQueryParser->_call('getLanguageStatement', $table, $table, $querySettings);
        $expectedSql = '(' . $table . '.sys_language_uid = -1) OR ((' . $table . '.sys_language_uid = 2) AND (' . $table . '.l10n_parent IN (SELECT ' . $table . '_dl.uid FROM ' . $table . ' ' . $table . '_dl WHERE (' . $table . '_dl.l10n_parent = 0) AND (' . $table . '_dl.sys_language_uid = 0) AND (' . $table . '_dl.deleted = 0)))) OR ((' . $table . '.sys_language_uid = 0) AND (' . $table . '.uid NOT IN (SELECT ' . $table . '_to.l10n_parent FROM ' . $table . ' ' . $table . '_dl, ' . $table . ' ' . $table . '_to WHERE (' . $table . '_to.l10n_parent > 0) AND (' . $table . '_to.sys_language_uid = 2) AND ((' . $table . '_dl.deleted = 0) AND (' . $table . '_to.deleted = 0)))))';
        self::assertSame($expectedSql, $compositeExpression->__toString());
    }

    /**
     * @test
     */
    public function addGetLanguageStatementWorksInBackendContextWithSubselectionTakesDeleteStatementIntoAccountIfNecessary()
    {
        $table = 'tt_content';
        $GLOBALS['TCA'][$table]['ctrl'] = [
            'languageField' => 'sys_language_uid',
            'transOrigPointerField' => 'l10n_parent',
            'delete' => 'deleted'
        ];
        $querySettings = new Typo3QuerySettings();
        $querySettings->setLanguageUid(2);
        $mockTypo3DbQueryParser = $this->getAccessibleMock(Typo3DbQueryParser::class, ['dummy'], [], '', false);

        $queryBuilderProphet = $this->getQueryBuilderProphetWithQueryBuilderForSubselect();

        $mockTypo3DbQueryParser->_set('queryBuilder', $queryBuilderProphet->reveal());
        $compositeExpression = $mockTypo3DbQueryParser->_call('getLanguageStatement', $table, $table, $querySettings);
        $expectedSql = '(' . $table . '.sys_language_uid = -1) OR ((' . $table . '.sys_language_uid = 2) AND (' . $table . '.l10n_parent IN (SELECT ' . $table . '_dl.uid FROM ' . $table . ' ' . $table . '_dl WHERE (' . $table . '_dl.l10n_parent = 0) AND (' . $table . '_dl.sys_language_uid = 0) AND (' . $table . '_dl.deleted = 0)))) OR ((' . $table . '.sys_language_uid = 0) AND (' . $table . '.uid NOT IN (SELECT ' . $table . '_to.l10n_parent FROM ' . $table . ' ' . $table . '_dl, ' . $table . ' ' . $table . '_to WHERE (' . $table . '_to.l10n_parent > 0) AND (' . $table . '_to.sys_language_uid = 2) AND ((' . $table . '_dl.deleted = 0) AND (' . $table . '_to.deleted = 0)))))';
        self::assertSame($expectedSql, $compositeExpression->__toString());
    }

    /**
     * @test
     */
    public function orderStatementGenerationWorks()
    {
        $mockSource = $this->getMockBuilder(Selector::class)
            ->setMethods(['getNodeTypeName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockSource->expects(self::any())->method('getNodeTypeName')->willReturn('foo');
        $mockDataMapper = $this->getMockBuilder(DataMapper::class)
            ->setMethods(['convertPropertyNameToColumnName', 'convertClassNameToTableName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockDataMapper->expects(self::once())->method('convertClassNameToTableName')->with('foo')->willReturn('tx_myext_tablename');
        $mockDataMapper->expects(self::once())->method('convertPropertyNameToColumnName')->with('fooProperty', 'foo')->willReturn('converted_fieldname');
        $queryBuilderProphet = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphet->addOrderBy('tx_myext_tablename.converted_fieldname', 'ASC')->shouldBeCalledTimes(1);

        $orderings = ['fooProperty' => QueryInterface::ORDER_ASCENDING];
        $mockTypo3DbQueryParser = $this->getAccessibleMock(Typo3DbQueryParser::class, ['dummy'], [], '', false);
        $mockTypo3DbQueryParser->_set('dataMapper', $mockDataMapper);
        $mockTypo3DbQueryParser->_set('queryBuilder', $queryBuilderProphet->reveal());
        $mockTypo3DbQueryParser->_call('parseOrderings', $orderings, $mockSource);
    }

    /**
     * @test
     */
    public function orderStatementGenerationThrowsExceptionOnUnsupportedOrder()
    {
        $this->expectException(UnsupportedOrderException::class);
        $this->expectExceptionCode(1242816074);
        $mockSource = $this->getMockBuilder(Selector::class)
            ->setMethods(['getNodeTypeName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockSource->expects(self::never())->method('getNodeTypeName');
        $mockDataMapper = $this->getMockBuilder(DataMapper::class)
            ->setMethods(['convertPropertyNameToColumnName', 'convertClassNameToTableName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockDataMapper->expects(self::never())->method('convertClassNameToTableName');
        $mockDataMapper->expects(self::never())->method('convertPropertyNameToColumnName');
        $orderings = ['fooProperty' => 'unsupported_order'];
        $mockTypo3DbQueryParser = $this->getAccessibleMock(Typo3DbQueryParser::class, ['dummy'], [], '', false);
        $mockTypo3DbQueryParser->_set('dataMapper', $mockDataMapper);

        $mockTypo3DbQueryParser->_call('parseOrderings', $orderings, $mockSource);
    }

    /**
     * @test
     */
    public function orderStatementGenerationWorksWithMultipleOrderings()
    {
        $mockSource = $this->getMockBuilder(Selector::class)
            ->setMethods(['getNodeTypeName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockSource->expects(self::any())->method('getNodeTypeName')->willReturn('Tx_MyExt_ClassName');
        $mockDataMapper = $this->getMockBuilder(DataMapper::class)
            ->setMethods(['convertPropertyNameToColumnName', 'convertClassNameToTableName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockDataMapper->expects(self::any())->method('convertClassNameToTableName')->with('Tx_MyExt_ClassName')->willReturn('tx_myext_tablename');
        $mockDataMapper->expects(self::any())->method('convertPropertyNameToColumnName')->willReturn('converted_fieldname');
        $orderings = [
            'fooProperty' => QueryInterface::ORDER_ASCENDING,
            'barProperty' => QueryInterface::ORDER_DESCENDING
        ];
        $mockTypo3DbQueryParser = $this->getAccessibleMock(Typo3DbQueryParser::class, ['dummy'], [], '', false);
        $mockTypo3DbQueryParser->_set('dataMapper', $mockDataMapper);

        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['addOrderBy'])
            ->getMock();
        $queryBuilder->expects(self::at(0))->method('addOrderBy')->with('tx_myext_tablename.converted_fieldname', 'ASC');
        $queryBuilder->expects(self::at(1))->method('addOrderBy')->with('tx_myext_tablename.converted_fieldname', 'DESC');

        $mockTypo3DbQueryParser->_set('queryBuilder', $queryBuilder);
        $mockTypo3DbQueryParser->_call('parseOrderings', $orderings, $mockSource);
    }

    public function providerForVisibilityConstraintStatement()
    {
        return [
            'in be: include all' => ['BE', true, [], true, ''],
            'in be: ignore enable fields but do not include deleted' => ['BE', true, [], false, 'tx_foo_table.deleted_column=0'],
            'in be: respect enable fields but include deleted' => ['BE', false, [], true, '(tx_foo_table.disabled_column = 0) AND (tx_foo_table.starttime_column <= 1451779200)'],
            'in be: respect enable fields and do not include deleted' => ['BE', false, [], false, '(tx_foo_table.disabled_column = 0) AND (tx_foo_table.starttime_column <= 1451779200) AND tx_foo_table.deleted_column=0'],
            'in fe: include all' => ['FE', true, [], true, ''],
            'in fe: ignore enable fields but do not include deleted' => ['FE', true, [], false, 'tx_foo_table.deleted_column=0'],
            'in fe: ignore only starttime and do not include deleted' => ['FE', true, ['starttime'], false, '(tx_foo_table.deleted_column = 0) AND (tx_foo_table.disabled_column = 0)'],
            'in fe: respect enable fields and do not include deleted' => ['FE', false, [], false, '(tx_foo_table.deleted_column = 0) AND (tx_foo_table.disabled_column = 0) AND (tx_foo_table.starttime_column <= 1451779200)']
        ];
    }

    /**
     * @test
     * @dataProvider providerForVisibilityConstraintStatement
     */
    public function visibilityConstraintStatementIsGeneratedAccordingToTheQuerySettings($mode, $ignoreEnableFields, $enableFieldsToBeIgnored, $deletedValue, $expectedSql)
    {
        $tableName = 'tx_foo_table';
        $GLOBALS['TCA'][$tableName]['ctrl'] = [
            'enablecolumns' => [
                'disabled' => 'disabled_column',
                'starttime' => 'starttime_column'
            ],
            'delete' => 'deleted_column'
        ];
        // simulate time for backend enable fields
        $GLOBALS['SIM_ACCESS_TIME'] = 1451779200;
        // simulate time for frontend (PageRepository) enable fields
        $dateAspect = new DateTimeAspect(new \DateTimeImmutable('3.1.2016'));
        $context = new Context(['date' => $dateAspect]);
        GeneralUtility::setSingletonInstance(Context::class, $context);

        $connectionProphet = $this->prophesize(Connection::class);
        $connectionProphet->quoteIdentifier(Argument::cetera())->willReturnArgument(0);
        $connectionProphet->getExpressionBuilder()->willReturn(
            GeneralUtility::makeInstance(ExpressionBuilder::class, $connectionProphet->reveal())
        );

        $queryBuilderProphet = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphet->expr()->willReturn(
            GeneralUtility::makeInstance(ExpressionBuilder::class, $connectionProphet->reveal())
        );
        $queryBuilderProphet->createNamedParameter(Argument::cetera())->willReturnArgument(0);

        $connectionPoolProphet = $this->prophesize(ConnectionPool::class);
        $connectionPoolProphet->getConnectionForTable(Argument::any($tableName, 'pages'))->willReturn($connectionProphet->reveal());
        $connectionPoolProphet->getQueryBuilderForTable(Argument::any($tableName, 'pages'))->willReturn($queryBuilderProphet->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphet->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphet->reveal());

        $mockQuerySettings = $this->getMockBuilder(Typo3QuerySettings::class)
            ->setMethods(['getIgnoreEnableFields', 'getEnableFieldsToBeIgnored', 'getIncludeDeleted'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockQuerySettings->expects(self::once())->method('getIgnoreEnableFields')->willReturn($ignoreEnableFields);
        $mockQuerySettings->expects(self::once())->method('getEnableFieldsToBeIgnored')->willReturn($enableFieldsToBeIgnored);
        $mockQuerySettings->expects(self::once())->method('getIncludeDeleted')->willReturn($deletedValue);

        /** @var $mockEnvironmentService \TYPO3\CMS\Extbase\Service\EnvironmentService | \PHPUnit\Framework\MockObject\MockObject */
        $mockEnvironmentService = $this->getMockBuilder(EnvironmentService::class)
            ->setMethods(['isEnvironmentInFrontendMode'])
            ->getMock();
        $mockEnvironmentService->expects(self::any())->method('isEnvironmentInFrontendMode')->willReturn($mode === 'FE');

        $mockTypo3DbQueryParser = $this->getAccessibleMock(Typo3DbQueryParser::class, ['dummy'], [], '', false);
        $mockTypo3DbQueryParser->_set('environmentService', $mockEnvironmentService);
        $resultSql = $mockTypo3DbQueryParser->_call('getVisibilityConstraintStatement', $mockQuerySettings, $tableName, $tableName);
        self::assertSame($expectedSql, $resultSql);
        unset($GLOBALS['TCA'][$tableName]);
    }

    public function providerForRespectEnableFields()
    {
        return [
            'in be: respectEnableFields=false' => ['BE', false, ''],
            'in be: respectEnableFields=true' => ['BE', true, '(tx_foo_table.disabled_column = 0) AND (tx_foo_table.starttime_column <= 1451779200) AND tx_foo_table.deleted_column=0'],
            'in FE: respectEnableFields=false' => ['FE', false, ''],
            'in FE: respectEnableFields=true' => ['FE', true, '(tx_foo_table.deleted_column = 0) AND (tx_foo_table.disabled_column = 0) AND (tx_foo_table.starttime_column <= 1451779200)']
        ];
    }

    /**
     * @test
     * @dataProvider providerForRespectEnableFields
     */
    public function respectEnableFieldsSettingGeneratesCorrectStatement($mode, $respectEnableFields, $expectedSql)
    {
        $tableName = 'tx_foo_table';
        $GLOBALS['TCA'][$tableName]['ctrl'] = [
            'enablecolumns' => [
                'disabled' => 'disabled_column',
                'starttime' => 'starttime_column'
            ],
            'delete' => 'deleted_column'
        ];
        // simulate time for backend enable fields
        $GLOBALS['SIM_ACCESS_TIME'] = 1451779200;
        // simulate time for frontend (PageRepository) enable fields
        $dateAspect = new DateTimeAspect(new \DateTimeImmutable('3.1.2016'));
        $context = new Context(['date' => $dateAspect]);
        GeneralUtility::setSingletonInstance(Context::class, $context);

        $connectionProphet = $this->prophesize(Connection::class);
        $connectionProphet->quoteIdentifier(Argument::cetera())->willReturnArgument(0);
        $connectionProphet->getExpressionBuilder(Argument::cetera())->willReturn(
            GeneralUtility::makeInstance(ExpressionBuilder::class, $connectionProphet->reveal())
        );
        $queryBuilderProphet = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphet->expr()->willReturn(
            GeneralUtility::makeInstance(ExpressionBuilder::class, $connectionProphet->reveal())
        );
        $queryBuilderProphet->createNamedParameter(Argument::cetera())->willReturnArgument(0);

        $connectionPoolProphet = $this->prophesize(ConnectionPool::class);
        $connectionPoolProphet->getQueryBuilderForTable(Argument::any($tableName, 'pages'))->willReturn($queryBuilderProphet->reveal());
        $connectionPoolProphet->getConnectionForTable(Argument::any($tableName, 'pages'))->willReturn($connectionProphet->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphet->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphet->reveal());

        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings $mockQuerySettings */
        $mockQuerySettings = $this->getMockBuilder(Typo3QuerySettings::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockQuerySettings->setIgnoreEnableFields(!$respectEnableFields);
        $mockQuerySettings->setIncludeDeleted(!$respectEnableFields);

        /** @var $mockEnvironmentService \TYPO3\CMS\Extbase\Service\EnvironmentService | \PHPUnit\Framework\MockObject\MockObject */
        $mockEnvironmentService = $this->getMockBuilder(EnvironmentService::class)
            ->setMethods(['isEnvironmentInFrontendMode'])
            ->getMock();
        $mockEnvironmentService->expects(self::any())->method('isEnvironmentInFrontendMode')->willReturn($mode === 'FE');

        $mockTypo3DbQueryParser = $this->getAccessibleMock(Typo3DbQueryParser::class, ['dummy'], [], '', false);
        $mockTypo3DbQueryParser->_set('environmentService', $mockEnvironmentService);
        $actualSql = $mockTypo3DbQueryParser->_call('getVisibilityConstraintStatement', $mockQuerySettings, $tableName, $tableName);
        self::assertSame($expectedSql, $actualSql);
        unset($GLOBALS['TCA'][$tableName]);
    }

    /**
     * @test
     */
    public function visibilityConstraintStatementGenerationThrowsExceptionIfTheQuerySettingsAreInconsistent()
    {
        $this->expectException(InconsistentQuerySettingsException::class);
        $this->expectExceptionCode(1460975922);
        $tableName = 'tx_foo_table';
        $GLOBALS['TCA'][$tableName]['ctrl'] = [
            'enablecolumns' => [
                'disabled' => 'disabled_column'
            ],
            'delete' => 'deleted_column'
        ];
        $mockQuerySettings = $this->getMockBuilder(Typo3QuerySettings::class)
            ->setMethods(['getIgnoreEnableFields', 'getEnableFieldsToBeIgnored', 'getIncludeDeleted'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockQuerySettings->expects(self::once())->method('getIgnoreEnableFields')->willReturn(false);
        $mockQuerySettings->expects(self::once())->method('getEnableFieldsToBeIgnored')->willReturn([]);
        $mockQuerySettings->expects(self::once())->method('getIncludeDeleted')->willReturn(true);

        /** @var $mockEnvironmentService \TYPO3\CMS\Extbase\Service\EnvironmentService | \PHPUnit\Framework\MockObject\MockObject */
        $mockEnvironmentService = $this->getMockBuilder(EnvironmentService::class)
            ->setMethods(['isEnvironmentInFrontendMode'])
            ->getMock();
        $mockEnvironmentService->expects(self::any())->method('isEnvironmentInFrontendMode')->willReturn(true);

        $mockTypo3DbQueryParser = $this->getAccessibleMock(Typo3DbQueryParser::class, ['dummy'], [], '', false);
        $mockTypo3DbQueryParser->_set('environmentService', $mockEnvironmentService);
        $mockTypo3DbQueryParser->_call('getVisibilityConstraintStatement', $mockQuerySettings, $tableName, $tableName);
        unset($GLOBALS['TCA'][$tableName]);
    }

    /**
     * DataProvider for addPageIdStatement Tests
     */
    public function providerForAddPageIdStatementData()
    {
        $table = StringUtility::getUniqueId('tx_coretest_table');
        return [
            'set Pid to zero if rootLevel = 1' => [
                '1',
                $table,
                $table . '.pid = 0'
            ],
            'set Pid to given Pids if rootLevel = 0' => [
                '0',
                $table,
                $table . '.pid IN (42, 27)'
            ],
            'add 0 to given Pids if rootLevel = -1' => [
                '-1',
                $table,
                $table . '.pid IN (42, 27, 0)'
            ],
            'set Pid to zero if rootLevel = -1 and no further pids given' => [
                '-1',
                $table,
                $table . '.pid = 0',
                []
            ],
            'set no statement for invalid configuration' => [
                '2',
                $table,
                ''
            ]
        ];
    }

    /**
     * @test
     * @dataProvider providerForAddPageIdStatementData
     */
    public function addPageIdStatementSetsPidToZeroIfTableDeclaresRootlevel($rootLevel, $table, $expectedSql, $storagePageIds = [42, 27])
    {
        $GLOBALS['TCA'][$table]['ctrl'] = [
            'rootLevel' => $rootLevel
        ];
        $mockTypo3DbQueryParser = $this->getAccessibleMock(Typo3DbQueryParser::class, ['dummy'], [], '', false);
        $queryBuilderProphet = $this->getQueryBuilderWithExpressionBuilderProphet();
        $mockTypo3DbQueryParser->_set('queryBuilder', $queryBuilderProphet->reveal());
        $sql = $mockTypo3DbQueryParser->_call('getPageIdStatement', $table, $table, $storagePageIds);

        self::assertSame($expectedSql, $sql);
    }
}
