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

namespace TYPO3\CMS\Core\Tests\Unit\Database\Query;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQL94Platform as PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\AbstractRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\DefaultRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionInterface;
use TYPO3\CMS\Core\Tests\Unit\Database\Mocks\MockPlatform;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class QueryBuilderTest extends UnitTestCase
{
    protected Connection&MockObject $connection;
    protected ?AbstractPlatform $platform;
    protected ?QueryBuilder $subject;
    protected DoctrineQueryBuilder&MockObject $concreteQueryBuilder;

    /**
     * Create a new database connection mock object for every test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->concreteQueryBuilder = $this->createMock(DoctrineQueryBuilder::class);
        $this->connection = $this->createMock(Connection::class);
        $this->subject = new QueryBuilder(
            $this->connection,
            null,
            $this->concreteQueryBuilder
        );
    }

    /**
     * @test
     */
    public function exprReturnsExpressionBuilderForConnection(): void
    {
        $this->connection->expects(self::atLeastOnce())->method('getExpressionBuilder')
            ->willReturn(GeneralUtility::makeInstance(ExpressionBuilder::class, $this->connection));
        $this->subject->expr();
    }

    /**
     * @test
     */
    public function getTypeDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('getType')
            ->willReturn(DoctrineQueryBuilder::INSERT);
        $this->subject->getType();
    }

    /**
     * @test
     */
    public function getStateDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('getState')
            ->willReturn(DoctrineQueryBuilder::STATE_CLEAN);
        $this->subject->getState();
    }

    /**
     * @test
     */
    public function getSQLDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('getSQL')
            ->willReturn('UPDATE aTable SET pid = 7');
        $this->concreteQueryBuilder->method('getType')
            ->willReturn(2); // Update Type
        $this->subject->getSQL();
    }

    /**
     * @test
     */
    public function setParameterDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('setParameter')->with('aField', 5, self::anything())
            ->willReturn($this->subject);
        $this->subject->setParameter('aField', 5);
    }

    /**
     * @test
     */
    public function setParametersDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('setParameters')->with(['aField' => 'aValue'], [])
            ->willReturn($this->subject);
        $this->subject->setParameters(['aField' => 'aValue']);
    }

    /**
     * @test
     */
    public function getParametersDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('getParameters')
            ->willReturn(['aField' => 'aValue']);
        $this->subject->getParameters();
    }

    /**
     * @test
     */
    public function getParameterDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('getParameter')->with('aField')
            ->willReturn('aValue');
        $this->subject->getParameter('aField');
    }

    /**
     * @test
     */
    public function getParameterTypesDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('getParameterTypes')->willReturn([]);
        $this->subject->getParameterTypes();
    }

    /**
     * @test
     */
    public function getParameterTypeDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('getParameterType')->with('aField')
            ->willReturn(Connection::PARAM_STR);
        $this->subject->getParameterType('aField');
    }

    /**
     * @test
     */
    public function setFirstResultDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('setFirstResult')->with(self::anything())
            ->willReturn($this->subject);
        $this->subject->setFirstResult(1);
    }

    /**
     * @test
     */
    public function getFirstResultDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('getFirstResult')->willReturn(1);
        $this->subject->getFirstResult();
    }

    /**
     * @test
     */
    public function setMaxResultsDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('setMaxResults')->with(self::anything())
            ->willReturn($this->subject);
        $this->subject->setMaxResults(1);
    }

    /**
     * @test
     */
    public function getMaxResultsDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('getMaxResults')->willReturn(1);
        $this->subject->getMaxResults();
    }

    /**
     * @test
     */
    public function addDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('add')->with('select', 'aField', self::anything())
            ->willReturn($this->subject);
        $this->subject->add('select', 'aField');
    }

    /**
     * @test
     */
    public function countBuildsExpressionAndCallsSelect(): void
    {
        $this->connection->method('getDatabasePlatform')->willReturn(new MockPlatform());
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('select')->with('COUNT(*)')
            ->willReturn($this->subject);
        $this->subject->count('*');
    }

    /**
     * @test
     */
    public function selectQuotesIdentifiersAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->expects(self::atLeastOnce())->method('quoteIdentifier')->withConsecutive(['aField'], ['anotherField'])
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('select')->with('aField', 'anotherField')
            ->willReturn($this->subject);
        $this->subject->select('aField', 'anotherField');
    }

    public function quoteIdentifiersForSelectDataProvider(): array
    {
        return [
            'fieldName' => [
                'fieldName',
                '"fieldName"',
            ],
            'tableName.fieldName' => [
                'tableName.fieldName',
                '"tableName"."fieldName"',
            ],
            'tableName.*' => [
                'tableName.*',
                '"tableName".*',
            ],
            '*' => [
                '*',
                '*',
            ],
            'fieldName AS anotherFieldName' => [
                'fieldName AS anotherFieldName',
                '"fieldName" AS "anotherFieldName"',
            ],
            'tableName.fieldName AS anotherFieldName' => [
                'tableName.fieldName AS anotherFieldName',
                '"tableName"."fieldName" AS "anotherFieldName"',
            ],
            'tableName.fieldName AS anotherTable.anotherFieldName' => [
                'tableName.fieldName AS anotherTable.anotherFieldName',
                '"tableName"."fieldName" AS "anotherTable"."anotherFieldName"',
            ],
            'fieldName as anotherFieldName' => [
                'fieldName as anotherFieldName',
                '"fieldName" AS "anotherFieldName"',
            ],
            'tableName.fieldName as anotherFieldName' => [
                'tableName.fieldName as anotherFieldName',
                '"tableName"."fieldName" AS "anotherFieldName"',
            ],
            'tableName.fieldName as anotherTable.anotherFieldName' => [
                'tableName.fieldName as anotherTable.anotherFieldName',
                '"tableName"."fieldName" AS "anotherTable"."anotherFieldName"',
            ],
            'fieldName aS anotherFieldName' => [
                'fieldName aS anotherFieldName',
                '"fieldName" AS "anotherFieldName"',
            ],
            'tableName.fieldName aS anotherFieldName' => [
                'tableName.fieldName aS anotherFieldName',
                '"tableName"."fieldName" AS "anotherFieldName"',
            ],
            'tableName.fieldName aS anotherTable.anotherFieldName' => [
                'tableName.fieldName aS anotherTable.anotherFieldName',
                '"tableName"."fieldName" AS "anotherTable"."anotherFieldName"',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider quoteIdentifiersForSelectDataProvider
     */
    public function quoteIdentifiersForSelect(string $identifier, string $expectedResult): void
    {
        $this->connection->method('quoteIdentifier')->with(self::anything())->willReturnCallback(
            static function ($args) {
                return (new MockPlatform())->quoteIdentifier($args);
            }
        );
        self::assertSame([$expectedResult], $this->subject->quoteIdentifiersForSelect([$identifier]));
    }

    /**
     * @test
     */
    public function quoteIdentifiersForSelectWithInvalidAlias(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1461170686);
        $this->connection->method('quoteIdentifier')->with(self::anything())->willReturnCallback(
            static function ($args) {
                return (new MockPlatform())->quoteIdentifier($args);
            }
        );
        $this->subject->quoteIdentifiersForSelect(['aField AS anotherField,someField AS someThing']);
    }

    /**
     * @test
     */
    public function selectDoesNotQuoteStarPlaceholder(): void
    {
        $this->connection->expects(self::never())->method('quoteIdentifier')->with('*');
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('select')->with('*')
            ->willReturn($this->subject);
        $this->subject->select('*');
    }

    /**
     * @test
     */
    public function addSelectQuotesIdentifiersAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->expects(self::atLeastOnce())->method('quoteIdentifier')
            ->withConsecutive(['aField'], ['anotherField'])->willReturnArgument(0);
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('addSelect')->with('aField', 'anotherField')
            ->willReturn($this->subject);
        $this->subject->addSelect('aField', 'anotherField');
    }

    /**
     * @test
     */
    public function addSelectDoesNotQuoteStarPlaceholder(): void
    {
        $this->connection->expects(self::never())->method('quoteIdentifier')->with('*');
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('addSelect')->with('*')
            ->willReturn($this->subject);
        $this->subject->addSelect('*');
    }

    /**
     * @test
     */
    public function selectLiteralDirectlyDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->expects(self::never())->method('quoteIdentifier')->with(self::anything());
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('select')->with('MAX(aField) AS anAlias')
            ->willReturn($this->subject);
        $this->subject->selectLiteral('MAX(aField) AS anAlias');
    }

    /**
     * @test
     */
    public function addSelectLiteralDirectlyDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->expects(self::never())->method('quoteIdentifier')->with(self::anything());
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('addSelect')->with('MAX(aField) AS anAlias')
            ->willReturn($this->subject);
        $this->subject->addSelectLiteral('MAX(aField) AS anAlias');
    }

    /**
     * @test
     * @todo: Test with alias
     */
    public function deleteQuotesIdentifierAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->expects(self::atLeastOnce())->method('quoteIdentifier')->with('aTable')
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->method('delete')->with('aTable', self::anything())->willReturn($this->subject);
        $this->subject->delete('aTable');
    }

    /**
     * @test
     * @todo: Test with alias
     */
    public function updateQuotesIdentifierAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->expects(self::atLeastOnce())->method('quoteIdentifier')->with('aTable')
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->method('update')->with('aTable', self::anything())->willReturn($this->subject);
        $this->subject->update('aTable');
    }

    /**
     * @test
     */
    public function insertQuotesIdentifierAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->expects(self::atLeastOnce())->method('quoteIdentifier')->with('aTable')
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->method('insert')->with('aTable')->willReturn($this->subject);
        $this->subject->insert('aTable');
    }

    /**
     * @test
     * @todo: Test with alias
     */
    public function fromQuotesIdentifierAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->expects(self::atLeastOnce())->method('quoteIdentifier')->with('aTable')
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->method('from')->with('aTable', self::anything())->willReturn($this->subject);
        $this->subject->from('aTable');
    }

    /**
     * @test
     */
    public function joinQuotesIdentifiersAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->expects(self::atLeastOnce())->method('quoteIdentifier')->withConsecutive(
            ['fromAlias'],
            ['join'],
            ['alias'],
        )->willReturnArgument(0);
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('innerJoin')
            ->with('fromAlias', 'join', 'alias', null)->willReturn($this->subject);
        $this->subject->join('fromAlias', 'join', 'alias');
    }

    /**
     * @test
     */
    public function innerJoinQuotesIdentifiersAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->expects(self::atLeastOnce())->method('quoteIdentifier')->withConsecutive(
            ['fromAlias'],
            ['join'],
            ['alias'],
        )->willReturnArgument(0);
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('innerJoin')
            ->with('fromAlias', 'join', 'alias', null)->willReturn($this->subject);
        $this->subject->innerJoin('fromAlias', 'join', 'alias');
    }

    /**
     * @test
     */
    public function leftJoinQuotesIdentifiersAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->expects(self::atLeastOnce())->method('quoteIdentifier')->withConsecutive(
            ['fromAlias'],
            ['join'],
            ['alias'],
        )->willReturnArgument(0);
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('leftJoin')
            ->with('fromAlias', 'join', 'alias', self::anything())->willReturn($this->subject);
        $expressionBuilder = GeneralUtility::makeInstance(ExpressionBuilder::class, $this->connection);
        $this->connection->method('getExpressionBuilder')->willReturn($expressionBuilder);
        $this->subject->leftJoin('fromAlias', 'join', 'alias');
    }

    /**
     * @test
     */
    public function rightJoinQuotesIdentifiersAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->expects(self::atLeastOnce())->method('quoteIdentifier')->withConsecutive(
            ['fromAlias'],
            ['join'],
            ['alias'],
        )->willReturnArgument(0);
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('rightJoin')
            ->with('fromAlias', 'join', 'alias', self::anything())->willReturn($this->subject);
        $this->concreteQueryBuilder->method('getQueryPart')->with('from')->willReturn([]);
        $expressionBuilder = GeneralUtility::makeInstance(ExpressionBuilder::class, $this->connection);
        $this->connection->method('getExpressionBuilder')->willReturn($expressionBuilder);
        $this->subject->rightJoin('fromAlias', 'join', 'alias');
    }

    /**
     * @test
     */
    public function setQuotesIdentifierAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->expects(self::atLeastOnce())->method('quoteIdentifier')->with('aField')
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('createNamedParameter')
            ->with('aValue', self::anything())->willReturn(':dcValue1');
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('set')->with('aField', ':dcValue1')
            ->willReturn($this->subject);
        $this->subject->set('aField', 'aValue');
    }

    /**
     * @test
     */
    public function setWithoutNamedParameterQuotesIdentifierAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->expects(self::atLeastOnce())->method('quoteIdentifier')->with('aField')
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->expects(self::never())->method('createNamedParameter')->with(self::anything());
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('set')->with('aField', 'aValue')
            ->willReturn($this->subject);
        $this->subject->set('aField', 'aValue', false);
    }

    /**
     * @test
     */
    public function whereDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('where')->with('uid=1', 'type=9')
            ->willReturn($this->subject);
        $this->subject->where('uid=1', 'type=9');
    }

    /**
     * @test
     */
    public function andWhereDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('andWhere')->with('uid=1', 'type=9')
            ->willReturn($this->subject);
        $this->subject->andWhere('uid=1', 'type=9');
    }

    /**
     * @test
     */
    public function orWhereDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('orWhere')->with('uid=1', 'type=9')
            ->willReturn($this->subject);
        $this->subject->orWhere('uid=1', 'type=9');
    }

    /**
     * @test
     */
    public function groupByQuotesIdentifierAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->expects(self::atLeastOnce())->method('quoteIdentifiers')->with(['aField', 'anotherField'])
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('groupBy')->with('aField', 'anotherField')
            ->willReturn($this->subject);
        $this->subject->groupBy('aField', 'anotherField');
    }

    /**
     * @test
     */
    public function addGroupByQuotesIdentifierAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->expects(self::atLeastOnce())->method('quoteIdentifiers')->with(['aField', 'anotherField'])
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('addGroupBy')->with('aField', 'anotherField')
            ->willReturn($this->subject);
        $this->subject->addGroupBy('aField', 'anotherField');
    }

    /**
     * @test
     */
    public function setValueQuotesIdentifierAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->expects(self::atLeastOnce())->method('quoteIdentifier')->with('aField')
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('createNamedParameter')
            ->with('aValue', self::anything())->willReturn(':dcValue1');
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('setValue')->with('aField', ':dcValue1')
            ->willReturn($this->subject);
        $this->subject->setValue('aField', 'aValue');
    }

    /**
     * @test
     */
    public function setValueWithoutNamedParameterQuotesIdentifierAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->expects(self::atLeastOnce())->method('quoteIdentifier')->with('aField')
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('setValue')->with('aField', 'aValue')
            ->willReturn($this->subject);
        $this->subject->setValue('aField', 'aValue', false);
    }

    /**
     * @test
     */
    public function valuesQuotesIdentifiersAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->expects(self::atLeastOnce())->method('quoteColumnValuePairs')
            ->with(['aField' => ':dcValue1', 'aValue' => ':dcValue2'])->willReturnArgument(0);
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('createNamedParameter')
            ->withConsecutive([1, self::anything()], [2, self::anything()])
            ->willReturnOnConsecutiveCalls(':dcValue1', ':dcValue2');
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('values')
            ->with(['aField' => ':dcValue1', 'aValue' => ':dcValue2'])->willReturn($this->subject);
        $this->subject->values(['aField' => 1, 'aValue' => 2]);
    }

    /**
     * @test
     */
    public function valuesWithoutNamedParametersQuotesIdentifiersAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->expects(self::atLeastOnce())->method('quoteColumnValuePairs')
            ->with(['aField' => 1, 'aValue' => 2])->willReturnArgument(0);
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('values')
            ->with(['aField' => 1, 'aValue' => 2])->willReturn($this->subject);
        $this->subject->values(['aField' => 1, 'aValue' => 2], false);
    }

    /**
     * @test
     */
    public function havingDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('having')->with('uid=1', 'type=9')
            ->willReturn($this->subject);
        $this->subject->having('uid=1', 'type=9');
    }

    /**
     * @test
     */
    public function andHavingDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('andHaving')->with('uid=1', 'type=9')
            ->willReturn($this->subject);
        $this->subject->andHaving('uid=1', 'type=9');
    }

    /**
     * @test
     */
    public function orHavingDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('orHaving')->with('uid=1', 'type=9')
            ->willReturn($this->subject);
        $this->subject->orHaving('uid=1', 'type=9');
    }

    /**
     * @test
     */
    public function orderByQuotesIdentifierAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->expects(self::atLeastOnce())->method('quoteIdentifier')->with('aField')
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('orderBy')->with('aField', null)
            ->willReturn($this->subject);
        $this->subject->orderBy('aField');
    }

    /**
     * @test
     */
    public function addOrderByQuotesIdentifierAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->expects(self::atLeastOnce())->method('quoteIdentifier')->with('aField')
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('addOrderBy')->with('aField', 'DESC')
            ->willReturn($this->subject);
        $this->subject->addOrderBy('aField', 'DESC');
    }

    /**
     * @test
     */
    public function getQueryPartDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('getQueryPart')->with('from')
            ->willReturn('aTable');
        $this->subject->getQueryPart('from');
    }

    /**
     * @test
     */
    public function getQueryPartsDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('getQueryParts')
            ->willReturn([]);
        $this->subject->getQueryParts();
    }

    /**
     * @test
     */
    public function resetQueryPartsDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('resetQueryParts')->with(['select', 'from'])
            ->willReturn($this->subject);
        $this->subject->resetQueryParts(['select', 'from']);
    }

    /**
     * @test
     */
    public function resetQueryPartDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('resetQueryPart')->with('select')
            ->willReturn($this->subject);
        $this->subject->resetQueryPart('select');
    }

    /**
     * @test
     */
    public function createNamedParameterDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('createNamedParameter')
            ->with(5, self::anything())->willReturn(':dcValue1');
        $this->subject->createNamedParameter(5);
    }

    /**
     * @test
     */
    public function createPositionalParameterDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('createPositionalParameter')
            ->with(5, self::anything())->willReturn('?');
        $this->subject->createPositionalParameter(5);
    }

    /**
     * @test
     */
    public function queryRestrictionsAreAddedForSelectOnExecuteQuery(): void
    {
        $GLOBALS['TCA']['pages']['ctrl'] = [
            'tstamp' => 'tstamp',
            'versioningWS' => true,
            'delete' => 'deleted',
            'crdate' => 'crdate',
            'enablecolumns' => [
                'disabled' => 'hidden',
            ],
        ];

        $this->connection->method('getDatabasePlatform')->willReturn(new MockPlatform());
        $this->connection->method('quoteIdentifier')->with(self::anything())->willReturnArgument(0);
        $this->connection->method('quoteIdentifiers')->with(self::anything())->willReturnArgument(0);

        $connectionBuilder = GeneralUtility::makeInstance(
            DoctrineQueryBuilder::class,
            $this->connection
        );

        $expressionBuilder = GeneralUtility::makeInstance(ExpressionBuilder::class, $this->connection);
        $this->connection->method('getExpressionBuilder')->willReturn($expressionBuilder);

        $subject = GeneralUtility::makeInstance(
            QueryBuilder::class,
            $this->connection,
            null,
            $connectionBuilder
        );

        $subject->select('*')
            ->from('pages')
            ->where('uid=1');

        $expectedSQL = 'SELECT * FROM pages WHERE (uid=1) AND (((pages.deleted = 0) AND (pages.hidden = 0)))';
        $this->connection->method('executeQuery')->with($expectedSQL, self::anything())
            ->willReturn($this->createMock(Result::class));

        $subject->executeQuery();
    }

    /**
     * @test
     */
    public function queryRestrictionsAreAddedForCountOnExecuteQuery(): void
    {
        $GLOBALS['TCA']['pages']['ctrl'] = [
            'tstamp' => 'tstamp',
            'versioningWS' => true,
            'delete' => 'deleted',
            'crdate' => 'crdate',
            'enablecolumns' => [
                'disabled' => 'hidden',
            ],
        ];

        $this->connection->method('getDatabasePlatform')->willReturn(new MockPlatform());
        $this->connection->method('quoteIdentifier')->with(self::anything())->willReturnArgument(0);
        $this->connection->method('quoteIdentifiers')->with(self::anything())->willReturnArgument(0);

        $connectionBuilder = GeneralUtility::makeInstance(
            DoctrineQueryBuilder::class,
            $this->connection
        );

        $expressionBuilder = GeneralUtility::makeInstance(ExpressionBuilder::class, $this->connection);
        $this->connection->method('getExpressionBuilder')->willReturn($expressionBuilder);

        $subject = GeneralUtility::makeInstance(
            QueryBuilder::class,
            $this->connection,
            null,
            $connectionBuilder
        );

        $subject->count('uid')
            ->from('pages')
            ->where('uid=1');

        $expectedSQL = 'SELECT COUNT(uid) FROM pages WHERE (uid=1) AND (((pages.deleted = 0) AND (pages.hidden = 0)))';
        $this->connection->method('executeQuery')->with($expectedSQL, self::anything())
            ->willReturn($this->createMock(Result::class));

        $subject->executeQuery();
    }

    /**
     * @test
     */
    public function queryRestrictionsAreReevaluatedOnSettingsChangeForGetSQL(): void
    {
        $GLOBALS['TCA']['pages']['ctrl'] = [
            'tstamp' => 'tstamp',
            'versioningWS' => true,
            'delete' => 'deleted',
            'crdate' => 'crdate',
            'enablecolumns' => [
                'disabled' => 'hidden',
            ],
        ];

        $this->connection->method('getDatabasePlatform')->willReturn(new MockPlatform());
        $this->connection->method('quoteIdentifier')->with(self::anything())->willReturnArgument(0);
        $this->connection->method('quoteIdentifiers')->with(self::anything())->willReturnArgument(0);
        $this->connection->method('getExpressionBuilder')
            ->willReturn(GeneralUtility::makeInstance(ExpressionBuilder::class, $this->connection));

        $concreteQueryBuilder = GeneralUtility::makeInstance(
            DoctrineQueryBuilder::class,
            $this->connection
        );

        $subject = GeneralUtility::makeInstance(
            QueryBuilder::class,
            $this->connection,
            null,
            $concreteQueryBuilder
        );

        $subject->select('*')
            ->from('pages')
            ->where('uid=1');

        $expectedSQL = 'SELECT * FROM pages WHERE (uid=1) AND (((pages.deleted = 0) AND (pages.hidden = 0)))';
        self::assertSame($expectedSQL, $subject->getSQL());

        $subject->getRestrictions()->removeAll()->add(new DeletedRestriction());

        $expectedSQL = 'SELECT * FROM pages WHERE (uid=1) AND (pages.deleted = 0)';
        self::assertSame($expectedSQL, $subject->getSQL());
    }

    /**
     * @test
     */
    public function queryRestrictionsAreReevaluatedOnSettingsChangeForExecuteQuery(): void
    {
        $GLOBALS['TCA']['pages']['ctrl'] = [
            'tstamp' => 'tstamp',
            'versioningWS' => true,
            'delete' => 'deleted',
            'crdate' => 'crdate',
            'enablecolumns' => [
                'disabled' => 'hidden',
            ],
        ];

        $this->connection->method('getDatabasePlatform')->willReturn(new MockPlatform());
        $this->connection->method('quoteIdentifier')->with(self::anything())->willReturnArgument(0);
        $this->connection->method('quoteIdentifiers')->with(self::anything())->willReturnArgument(0);
        $this->connection->method('getExpressionBuilder')
            ->willReturn(GeneralUtility::makeInstance(ExpressionBuilder::class, $this->connection));

        $concreteQueryBuilder = GeneralUtility::makeInstance(
            DoctrineQueryBuilder::class,
            $this->connection
        );

        $subject = GeneralUtility::makeInstance(
            QueryBuilder::class,
            $this->connection,
            null,
            $concreteQueryBuilder
        );

        $subject->select('*')
            ->from('pages')
            ->where('uid=1');

        $subject->getRestrictions()->removeAll()->add(new DeletedRestriction());

        $expectedSQLForQuery = 'SELECT * FROM pages WHERE (uid=1) AND (pages.deleted = 0)';
        $expectedSQLForResetRestrictions = 'SELECT * FROM pages WHERE (uid=1) AND (((pages.deleted = 0) AND (pages.hidden = 0)))';

        $this->connection->method('executeQuery')->withConsecutive(
            [$expectedSQLForQuery, self::anything()],
            [$expectedSQLForResetRestrictions, self::anything()],
        )->willReturn($this->createMock(Result::class));

        $subject->executeQuery();
        $subject->resetRestrictions();

        $subject->executeQuery();
    }

    /**
     * @test
     */
    public function getQueriedTablesReturnsSameTableTwiceForInnerJoin(): void
    {
        $this->connection->method('getDatabasePlatform')->willReturn(new MockPlatform());
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('getQueryPart')
            ->withConsecutive(['from'], ['join'])
            ->willReturnOnConsecutiveCalls(
                [
                    [
                        'table' => 'aTable',
                    ],
                ],
                [
                    'aTable' => [
                        [
                            'joinType' => 'inner',
                            'joinTable' => 'aTable',
                            'joinAlias' => 'aTable_alias',
                        ],
                    ],
                ]
            );

        // Call a protected method
        $result = \Closure::bind(function () {
            return $this->getQueriedTables();
        }, $this->subject, QueryBuilder::class)();

        $expected = [
            'aTable' => 'aTable',
            'aTable_alias' => 'aTable',
        ];
        self::assertEquals($expected, $result);
    }

    public function unquoteSingleIdentifierUnquotesCorrectlyOnDifferentPlatformsDataProvider(): array
    {
        return [
            'mysql' => [
                'platform' => MySQLPlatform::class,
                'quoteChar' => '`',
                'input' => '`anIdentifier`',
                'expected' => 'anIdentifier',
            ],
            'mysql with spaces' => [
                'platform' => MySQLPlatform::class,
                'quoteChar' => '`',
                'input' => ' `anIdentifier` ',
                'expected' => 'anIdentifier',
            ],
            'postgres' => [
                'platform' => PostgreSqlPlatform::class,
                'quoteChar' => '"',
                'input' => '"anIdentifier"',
                'expected' => 'anIdentifier',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider unquoteSingleIdentifierUnquotesCorrectlyOnDifferentPlatformsDataProvider
     */
    public function unquoteSingleIdentifierUnquotesCorrectlyOnDifferentPlatforms(string $platform, string $quoteChar, string $input, string $expected): void
    {
        $connectionMock = $this->createMock(Connection::class);
        $databasePlatformMock = $this->createMock($platform);
        $databasePlatformMock->method('getIdentifierQuoteCharacter')->willReturn($quoteChar);
        $connectionMock->method('getDatabasePlatform')->willReturn($databasePlatformMock);
        $subject = $this->getAccessibleMock(QueryBuilder::class, ['dummy'], [$connectionMock]);
        $result = $subject->_call('unquoteSingleIdentifier', $input);
        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function cloningQueryBuilderClonesConcreteQueryBuilder(): void
    {
        $clonedQueryBuilder = clone $this->subject;
        self::assertNotSame($this->subject->getConcreteQueryBuilder(), $clonedQueryBuilder->getConcreteQueryBuilder());
    }

    /**
     * @test
     */
    public function changingClonedQueryBuilderDoesNotInfluenceSourceOne(): void
    {
        $GLOBALS['TCA']['pages']['ctrl'] = [
            'tstamp' => 'tstamp',
            'versioningWS' => true,
            'delete' => 'deleted',
            'crdate' => 'crdate',
            'enablecolumns' => [
                'disabled' => 'hidden',
            ],
        ];

        $this->connection->method('getDatabasePlatform')->willReturn(new MockPlatform());
        $this->connection->method('quoteIdentifier')->with(self::anything())->willReturnArgument(0);
        $this->connection->method('quoteIdentifiers')->with(self::anything())->willReturnArgument(0);
        $this->connection->method('getExpressionBuilder')
            ->willReturn(GeneralUtility::makeInstance(ExpressionBuilder::class, $this->connection));

        $concreteQueryBuilder = GeneralUtility::makeInstance(
            DoctrineQueryBuilder::class,
            $this->connection
        );

        $subject = GeneralUtility::makeInstance(
            QueryBuilder::class,
            $this->connection,
            null,
            $concreteQueryBuilder
        );

        $subject->select('*')
            ->from('pages')
            ->where('uid=1');

        $expectedSQL = 'SELECT * FROM pages WHERE (uid=1) AND (((pages.deleted = 0) AND (pages.hidden = 0)))';
        self::assertSame($expectedSQL, $subject->getSQL());

        $clonedQueryBuilder = clone $subject;
        //just after cloning both query builders should return the same sql
        self::assertSame($expectedSQL, $clonedQueryBuilder->getSQL());

        //change cloned QueryBuilder
        $clonedQueryBuilder->count('*');
        $expectedCountSQL = 'SELECT COUNT(*) FROM pages WHERE (uid=1) AND (((pages.deleted = 0) AND (pages.hidden = 0)))';
        self::assertSame($expectedCountSQL, $clonedQueryBuilder->getSQL());

        //check if the original QueryBuilder has not changed
        self::assertSame($expectedSQL, $subject->getSQL());

        //change restrictions in the original QueryBuilder and check if cloned has changed
        $subject->getRestrictions()->removeAll()->add(new DeletedRestriction());
        $expectedSQL = 'SELECT * FROM pages WHERE (uid=1) AND (pages.deleted = 0)';
        self::assertSame($expectedSQL, $subject->getSQL());

        self::assertSame($expectedCountSQL, $clonedQueryBuilder->getSQL());
    }

    /**
     * @test
     */
    public function settingRestrictionContainerWillAddAdditionalRestrictionsFromConstructor(): void
    {
        $restrictionClass = get_class($this->createMock(QueryRestrictionInterface::class));
        $queryBuilder = new QueryBuilder(
            $this->connection,
            null,
            $this->concreteQueryBuilder,
            [
                $restrictionClass => [],
            ]
        );

        $container = $this->createMock(AbstractRestrictionContainer::class);
        $container->expects(self::atLeastOnce())->method('add')->with(new $restrictionClass());

        $queryBuilder->setRestrictions($container);
    }

    /**
     * @test
     */
    public function settingRestrictionContainerWillAddAdditionalRestrictionsFromConfiguration(): void
    {
        $restrictionClass = get_class($this->createMock(QueryRestrictionInterface::class));
        $GLOBALS['TYPO3_CONF_VARS']['DB']['additionalQueryRestrictions'][$restrictionClass] = [];
        $queryBuilder = new QueryBuilder(
            $this->connection,
            null,
            $this->concreteQueryBuilder
        );

        $container = $this->createMock(AbstractRestrictionContainer::class);
        $container->expects(self::atLeastOnce())->method('add')->with(new $restrictionClass());

        $queryBuilder->setRestrictions($container);
    }

    /**
     * @test
     */
    public function settingRestrictionContainerWillNotAddAdditionalRestrictionsFromConfigurationIfNotDisabled(): void
    {
        $restrictionClass = get_class($this->createMock(QueryRestrictionInterface::class));
        $GLOBALS['TYPO3_CONF_VARS']['DB']['additionalQueryRestrictions'][$restrictionClass] = ['disabled' => true];
        $queryBuilder = new QueryBuilder(
            $this->connection,
            null,
            $this->concreteQueryBuilder
        );

        $container = $this->createMock(AbstractRestrictionContainer::class);
        $container->expects(self::never())->method('add')->with(new $restrictionClass());

        $queryBuilder->setRestrictions($container);
    }

    /**
     * @test
     */
    public function resettingToDefaultRestrictionContainerWillAddAdditionalRestrictionsFromConfiguration(): void
    {
        $restrictionClass = get_class($this->createMock(QueryRestrictionInterface::class));
        $queryBuilder = new QueryBuilder(
            $this->connection,
            null,
            $this->concreteQueryBuilder,
            [
                $restrictionClass => [],
            ]
        );

        $container = $this->createMock(DefaultRestrictionContainer::class);
        $container->expects(self::atLeastOnce())->method('add')->with(new $restrictionClass());
        GeneralUtility::addInstance(DefaultRestrictionContainer::class, $container);

        $queryBuilder->resetRestrictions();
    }

    /**
     * @test
     * @dataProvider createNamedParameterInput
     * @param mixed $input
     */
    public function setWithNamedParameterPassesGivenTypeToCreateNamedParameter($input, int $type): void
    {
        $this->connection->method('quoteIdentifier')->with('aField')
            ->willReturnArgument(0);
        $concreteQueryBuilder = new DoctrineQueryBuilder($this->connection);

        $subject = new QueryBuilder($this->connection, null, $concreteQueryBuilder);
        $subject->set('aField', $input, true, $type);
        self::assertSame($type, $concreteQueryBuilder->getParameterType('dcValue1'));
    }

    public function createNamedParameterInput(): array
    {
        return [
            'string input and output' => [
                'aValue',
                Connection::PARAM_STR,
            ],
            'int input and string output' => [
                17,
                Connection::PARAM_STR,
            ],
            'int input and int output' => [
                17,
                Connection::PARAM_INT,
            ],
            'string input and array output' => [
                'aValue',
                Connection::PARAM_STR_ARRAY,
            ],
        ];
    }

    public function castFieldToTextTypeDataProvider(): array
    {
        return [
            'Test cast for MySQLPlatform' => [
                new MySQLPlatform(),
                'CONVERT(aField, CHAR)',
            ],
            'Test cast for PostgreSqlPlatform' => [
                new PostgreSqlPlatform(),
                'aField::text',
            ],
            'Test cast for SqlitePlatform' => [
                new SqlitePlatform(),
                'CAST(aField as TEXT)',
            ],
            'Test cast for OraclePlatform' => [
                new OraclePlatform(),
                'CAST(aField as VARCHAR)',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider castFieldToTextTypeDataProvider
     */
    public function castFieldToTextType(AbstractPlatform $platform, string $expectation): void
    {
        $this->connection->expects(self::atLeastOnce())->method('quoteIdentifier')->with('aField')
            ->willReturnArgument(0);

        $this->connection->method('getDatabasePlatform')->willReturn($platform);

        $concreteQueryBuilder = new DoctrineQueryBuilder($this->connection);

        $subject = new QueryBuilder($this->connection, null, $concreteQueryBuilder);
        $result = $subject->castFieldToTextType('aField');

        self::assertSame($expectation, $result);
    }

    /**
     * @test
     */
    public function limitRestrictionsToTablesLimitsRestrictionsInTheContainerToTheGivenTables(): void
    {
        $GLOBALS['TCA']['tt_content']['ctrl'] = $GLOBALS['TCA']['pages']['ctrl'] = [
            'delete' => 'deleted',
            'enablecolumns' => [
                'disabled' => 'hidden',
            ],
        ];

        $this->connection->method('getDatabasePlatform')->willReturn(new MockPlatform());
        $this->connection->method('quoteIdentifier')->with(self::anything())->willReturnArgument(0);
        $this->connection->method('quoteIdentifiers')->with(self::anything())->willReturnArgument(0);

        $connectionBuilder = GeneralUtility::makeInstance(
            DoctrineQueryBuilder::class,
            $this->connection
        );

        $expressionBuilder = GeneralUtility::makeInstance(ExpressionBuilder::class, $this->connection);
        $this->connection->method('getExpressionBuilder')->willReturn($expressionBuilder);

        $subject = new QueryBuilder(
            $this->connection,
            null,
            $connectionBuilder
        );
        $subject->limitRestrictionsToTables(['pages']);

        $subject->select('*')
            ->from('pages')
            ->leftJoin(
                'pages',
                'tt_content',
                'content',
                'pages.uid = content.pid'
            )
            ->where($expressionBuilder->eq('uid', 1));

        $this->connection->method('executeQuery')->with(
            'SELECT * FROM pages LEFT JOIN tt_content content ON pages.uid = content.pid WHERE (uid = 1) AND (((pages.deleted = 0) AND (pages.hidden = 0)))',
            self::anything()
        )->willReturn($this->createMock(Result::class));

        $subject->executeQuery();
    }

    /**
     * @test
     */
    public function restrictionsCanStillBeRemovedAfterTheyHaveBeenLimitedToTables(): void
    {
        $GLOBALS['TCA']['tt_content']['ctrl'] = $GLOBALS['TCA']['pages']['ctrl'] = [
            'delete' => 'deleted',
            'enablecolumns' => [
                'disabled' => 'hidden',
            ],
        ];

        $this->connection->method('getDatabasePlatform')->willReturn(new MockPlatform());
        $this->connection->method('quoteIdentifier')->with(self::anything())->willReturnArgument(0);
        $this->connection->method('quoteIdentifiers')->with(self::anything())->willReturnArgument(0);

        $connectionBuilder = GeneralUtility::makeInstance(
            DoctrineQueryBuilder::class,
            $this->connection
        );

        $expressionBuilder = GeneralUtility::makeInstance(ExpressionBuilder::class, $this->connection);
        $this->connection->method('getExpressionBuilder')->willReturn($expressionBuilder);

        $subject = new QueryBuilder(
            $this->connection,
            null,
            $connectionBuilder
        );
        $subject->limitRestrictionsToTables(['pages']);
        $subject->getRestrictions()->removeByType(DeletedRestriction::class);

        $subject->select('*')
            ->from('pages')
            ->leftJoin(
                'pages',
                'tt_content',
                'content',
                'pages.uid = content.pid'
            )
            ->where($expressionBuilder->eq('uid', 1));

        $this->connection->method('executeQuery')->with(
            'SELECT * FROM pages LEFT JOIN tt_content content ON pages.uid = content.pid WHERE (uid = 1) AND (pages.hidden = 0)',
            self::anything()
        )->willReturn($this->createMock(Result::class));

        $subject->executeQuery();
    }

    /**
     * @test
     */
    public function restrictionsAreAppliedInJoinConditionForLeftJoins(): void
    {
        $GLOBALS['TCA']['tt_content']['ctrl'] = $GLOBALS['TCA']['pages']['ctrl'] = [
            'delete' => 'deleted',
            'enablecolumns' => [
                'disabled' => 'hidden',
            ],
        ];

        $this->connection->method('getDatabasePlatform')->willReturn(new MockPlatform());
        $this->connection->method('quoteIdentifier')->with(self::anything())->willReturnArgument(0);
        $this->connection->method('quoteIdentifiers')->with(self::anything())->willReturnArgument(0);

        $connectionBuilder = GeneralUtility::makeInstance(
            DoctrineQueryBuilder::class,
            $this->connection
        );

        $expressionBuilder = GeneralUtility::makeInstance(ExpressionBuilder::class, $this->connection);
        $this->connection->method('getExpressionBuilder')->willReturn($expressionBuilder);

        $subject = new QueryBuilder(
            $this->connection,
            null,
            $connectionBuilder
        );

        $subject->select('*')
                ->from('pages')
                ->leftJoin(
                    'pages',
                    'tt_content',
                    'content',
                    'pages.uid = content.pid'
                )
                ->where($expressionBuilder->eq('uid', 1));

        $this->connection->method('executeQuery')->with(
            'SELECT * FROM pages LEFT JOIN tt_content content ON ((pages.uid = content.pid) AND (((content.deleted = 0) AND (content.hidden = 0)))) WHERE (uid = 1) AND (((pages.deleted = 0) AND (pages.hidden = 0)))',
            self::anything()
        )->willReturn($this->createMock(Result::class));

        $subject->executeQuery();
    }

    /**
     * @test
     */
    public function restrictionsAreAppliedInJoinConditionForRightJoins(): void
    {
        $GLOBALS['TCA']['tt_content']['ctrl'] = $GLOBALS['TCA']['pages']['ctrl'] = [
            'delete' => 'deleted',
            'enablecolumns' => [
                'disabled' => 'hidden',
            ],
        ];

        $this->connection->method('getDatabasePlatform')->willReturn(new MockPlatform());
        $this->connection->method('quoteIdentifier')->with(self::anything())->willReturnArgument(0);
        $this->connection->method('quoteIdentifiers')->with(self::anything())->willReturnArgument(0);

        $connectionBuilder = GeneralUtility::makeInstance(
            DoctrineQueryBuilder::class,
            $this->connection
        );

        $expressionBuilder = GeneralUtility::makeInstance(ExpressionBuilder::class, $this->connection);
        $this->connection->method('getExpressionBuilder')->willReturn($expressionBuilder);

        $subject = new QueryBuilder(
            $this->connection,
            null,
            $connectionBuilder
        );

        $subject->select('*')
                ->from('tt_content')
                ->rightJoin(
                    'tt_content',
                    'pages',
                    'pages',
                    'pages.uid = tt_content.pid'
                )
                ->where($expressionBuilder->eq('uid', 1));

        $this->connection->method('executeQuery')->with(
            'SELECT * FROM tt_content RIGHT JOIN pages pages ON ((pages.uid = tt_content.pid) AND (((tt_content.deleted = 0) AND (tt_content.hidden = 0)))) WHERE (uid = 1) AND (((pages.deleted = 0) AND (pages.hidden = 0)))',
            self::anything()
        )->willReturn($this->createMock(Result::class));

        $subject->executeQuery();
    }
}
