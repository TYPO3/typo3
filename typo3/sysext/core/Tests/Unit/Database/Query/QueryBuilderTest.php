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
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Prophecy\Argument;
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

/**
 * Test case
 */
class QueryBuilderTest extends UnitTestCase
{
    /**
     * @var Connection|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $connection;

    /**
     * @var AbstractPlatform
     */
    protected $platform;

    /**
     * @var QueryBuilder
     */
    protected $subject;

    /**
     * @var \Doctrine\DBAL\Query\QueryBuilder|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $concreteQueryBuilder;

    /**
     * Create a new database connection mock object for every test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->concreteQueryBuilder = $this->prophesize(\Doctrine\DBAL\Query\QueryBuilder::class);

        $this->connection = $this->prophesize(Connection::class);
        $this->connection->getDatabasePlatform()->willReturn(new MockPlatform());

        $this->subject = new QueryBuilder(
            $this->connection->reveal(),
            null,
            $this->concreteQueryBuilder->reveal()
        );
    }

    /**
     * @test
     */
    public function exprReturnsExpressionBuilderForConnection(): void
    {
        $this->connection->getExpressionBuilder()
            ->shouldBeCalled()
            ->willReturn(GeneralUtility::makeInstance(ExpressionBuilder::class, $this->connection->reveal()));

        $this->subject->expr();
    }

    /**
     * @test
     */
    public function getTypeDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->getType()
            ->shouldBeCalled()
            ->willReturn(\Doctrine\DBAL\Query\QueryBuilder::INSERT);

        $this->subject->getType();
    }

    /**
     * @test
     */
    public function getStateDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->getState()
            ->shouldBeCalled()
            ->willReturn(\Doctrine\DBAL\Query\QueryBuilder::STATE_CLEAN);

        $this->subject->getState();
    }

    /**
     * @test
     */
    public function getSQLDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->getSQL()
            ->shouldBeCalled()
            ->willReturn('UPDATE aTable SET pid = 7');
        $this->concreteQueryBuilder->getType()
            ->willReturn(2); // Update Type

        $this->subject->getSQL();
    }

    /**
     * @test
     */
    public function setParameterDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->setParameter(Argument::exact('aField'), Argument::exact(5), Argument::cetera())
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->setParameter('aField', 5);
    }

    /**
     * @test
     */
    public function setParametersDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->setParameters(Argument::exact(['aField' => 'aValue']), Argument::exact([]))
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->setParameters(['aField' => 'aValue']);
    }

    /**
     * @test
     */
    public function getParametersDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->getParameters()
            ->shouldBeCalled()
            ->willReturn(['aField' => 'aValue']);

        $this->subject->getParameters();
    }

    /**
     * @test
     */
    public function getParameterDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->getParameter(Argument::exact('aField'))
            ->shouldBeCalled()
            ->willReturn('aValue');

        $this->subject->getParameter('aField');
    }

    /**
     * @test
     */
    public function getParameterTypesDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->getParameterTypes()
            ->shouldBeCalled()
            ->willReturn([]);

        $this->subject->getParameterTypes();
    }

    /**
     * @test
     */
    public function getParameterTypeDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->getParameterType(Argument::exact('aField'))
            ->shouldBeCalled()
            ->willReturn(Connection::PARAM_STR);

        $this->subject->getParameterType('aField');
    }

    /**
     * @test
     */
    public function setFirstResultDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->setFirstResult(Argument::cetera())
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->setFirstResult(1);
    }

    /**
     * @test
     */
    public function getFirstResultDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->getFirstResult()
            ->shouldBeCalled()
            ->willReturn(1);

        $this->subject->getFirstResult();
    }

    /**
     * @test
     */
    public function setMaxResultsDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->setMaxResults(Argument::cetera())
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->setMaxResults(1);
    }

    /**
     * @test
     */
    public function getMaxResultsDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->getMaxResults()
            ->shouldBeCalled()
            ->willReturn(1);

        $this->subject->getMaxResults();
    }

    /**
     * @test
     */
    public function addDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->add(Argument::exact('select'), Argument::exact('aField'), Argument::cetera())
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->add('select', 'aField');
    }

    /**
     * @test
     */
    public function countBuildsExpressionAndCallsSelect(): void
    {
        $this->concreteQueryBuilder->select(Argument::exact('COUNT(*)'))
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->count('*');
    }

    /**
     * @test
     */
    public function selectQuotesIdentifiersAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->quoteIdentifier('aField')
            ->shouldBeCalled()
            ->willReturnArgument(0);
        $this->connection->quoteIdentifier('anotherField')
            ->shouldBeCalled()
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->select(Argument::exact('aField'), Argument::exact('anotherField'))
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->select('aField', 'anotherField');
    }

    /**
     * @return array
     */
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
     * @param string $identifier
     * @param string $expectedResult
     */
    public function quoteIdentifiersForSelect($identifier, $expectedResult): void
    {
        $this->connection->quoteIdentifier(Argument::cetera())->will(
            function ($args) {
                $platform = new MockPlatform();

                return $platform->quoteIdentifier($args[0]);
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

        $this->connection->quoteIdentifier(Argument::cetera())->will(
            function ($args) {
                $platform = new MockPlatform();

                return $platform->quoteIdentifier($args[0]);
            }
        );
        $this->subject->quoteIdentifiersForSelect(['aField AS anotherField,someField AS someThing']);
    }

    /**
     * @test
     */
    public function selectDoesNotQuoteStarPlaceholder(): void
    {
        $this->connection->quoteIdentifier('aField')
            ->shouldBeCalled()
            ->willReturnArgument(0);
        $this->connection->quoteIdentifier('*')
            ->shouldNotBeCalled();
        $this->concreteQueryBuilder->select(Argument::exact('aField'), Argument::exact('*'))
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->select('aField', '*');
    }

    /**
     * @test
     */
    public function addSelectQuotesIdentifiersAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->quoteIdentifier('aField')
            ->shouldBeCalled()
            ->willReturnArgument(0);
        $this->connection->quoteIdentifier('anotherField')
            ->shouldBeCalled()
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->addSelect(Argument::exact('aField'), Argument::exact('anotherField'))
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->addSelect('aField', 'anotherField');
    }

    /**
     * @test
     */
    public function addSelectDoesNotQuoteStarPlaceholder(): void
    {
        $this->connection->quoteIdentifier('aField')
            ->shouldBeCalled()
            ->willReturnArgument(0);
        $this->connection->quoteIdentifier('*')
            ->shouldNotBeCalled();
        $this->concreteQueryBuilder->addSelect(Argument::exact('aField'), Argument::exact('*'))
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->addSelect('aField', '*');
    }

    /**
     * @test
     */
    public function selectLiteralDirectlyDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->quoteIdentifier(Argument::cetera())
            ->shouldNotBeCalled();
        $this->concreteQueryBuilder->select(Argument::exact('MAX(aField) AS anAlias'))
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->selectLiteral('MAX(aField) AS anAlias');
    }

    /**
     * @test
     */
    public function addSelectLiteralDirectlyDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->quoteIdentifier(Argument::cetera())
            ->shouldNotBeCalled();
        $this->concreteQueryBuilder->addSelect(Argument::exact('MAX(aField) AS anAlias'))
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->addSelectLiteral('MAX(aField) AS anAlias');
    }

    /**
     * @test
     * @todo: Test with alias
     */
    public function deleteQuotesIdentifierAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->quoteIdentifier('aTable')
            ->shouldBeCalled()
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->delete(Argument::exact('aTable'), Argument::cetera())
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->delete('aTable');
    }

    /**
     * @test
     * @todo: Test with alias
     */
    public function updateQuotesIdentifierAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->quoteIdentifier('aTable')
            ->shouldBeCalled()
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->update(Argument::exact('aTable'), Argument::cetera())
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->update('aTable');
    }

    /**
     * @test
     */
    public function insertQuotesIdentifierAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->quoteIdentifier('aTable')
            ->shouldBeCalled()
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->insert(Argument::exact('aTable'))
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->insert('aTable');
    }

    /**
     * @test
     * @todo: Test with alias
     */
    public function fromQuotesIdentifierAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->quoteIdentifier('aTable')
            ->shouldBeCalled()
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->from(Argument::exact('aTable'), Argument::cetera())
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->from('aTable');
    }

    /**
     * @test
     */
    public function joinQuotesIdentifiersAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->quoteIdentifier('fromAlias')
            ->shouldBeCalled()
            ->willReturnArgument(0);
        $this->connection->quoteIdentifier('join')
            ->shouldBeCalled()
            ->willReturnArgument(0);
        $this->connection->quoteIdentifier('alias')
            ->shouldBeCalled()
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->innerJoin('fromAlias', 'join', 'alias', null)
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->join('fromAlias', 'join', 'alias');
    }

    /**
     * @test
     */
    public function innerJoinQuotesIdentifiersAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->quoteIdentifier('fromAlias')
            ->shouldBeCalled()
            ->willReturnArgument(0);
        $this->connection->quoteIdentifier('join')
            ->shouldBeCalled()
            ->willReturnArgument(0);
        $this->connection->quoteIdentifier('alias')
            ->shouldBeCalled()
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->innerJoin('fromAlias', 'join', 'alias', null)
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->innerJoin('fromAlias', 'join', 'alias');
    }

    /**
     * @test
     */
    public function leftJoinQuotesIdentifiersAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->quoteIdentifier('fromAlias')
            ->shouldBeCalled()
            ->willReturnArgument(0);
        $this->connection->quoteIdentifier('join')
            ->shouldBeCalled()
            ->willReturnArgument(0);
        $this->connection->quoteIdentifier('alias')
            ->shouldBeCalled()
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->leftJoin('fromAlias', 'join', 'alias', null)
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->leftJoin('fromAlias', 'join', 'alias');
    }

    /**
     * @test
     */
    public function rightJoinQuotesIdentifiersAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->quoteIdentifier('fromAlias')
            ->shouldBeCalled()
            ->willReturnArgument(0);
        $this->connection->quoteIdentifier('join')
            ->shouldBeCalled()
            ->willReturnArgument(0);
        $this->connection->quoteIdentifier('alias')
            ->shouldBeCalled()
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->rightJoin('fromAlias', 'join', 'alias', null)
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->rightJoin('fromAlias', 'join', 'alias');
    }

    /**
     * @test
     */
    public function setQuotesIdentifierAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->quoteIdentifier('aField')
            ->shouldBeCalled()
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->createNamedParameter('aValue', Argument::cetera())
            ->shouldBeCalled()
            ->willReturn(':dcValue1');
        $this->concreteQueryBuilder->set('aField', ':dcValue1')
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->set('aField', 'aValue');
    }

    /**
     * @test
     */
    public function setWithoutNamedParameterQuotesIdentifierAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->quoteIdentifier('aField')
            ->shouldBeCalled()
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->createNamedParameter(Argument::cetera())->shouldNotBeCalled();
        $this->concreteQueryBuilder->set('aField', 'aValue')
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->set('aField', 'aValue', false);
    }

    /**
     * @test
     */
    public function whereDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->where('uid=1', 'type=9')
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->where('uid=1', 'type=9');
    }

    /**
     * @test
     */
    public function andWhereDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->andWhere('uid=1', 'type=9')
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->andWhere('uid=1', 'type=9');
    }

    /**
     * @test
     */
    public function orWhereDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->orWhere('uid=1', 'type=9')
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->orWhere('uid=1', 'type=9');
    }

    /**
     * @test
     */
    public function groupByQuotesIdentifierAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->quoteIdentifiers(['aField', 'anotherField'])
            ->shouldBeCalled()
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->groupBy('aField', 'anotherField')
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->groupBy('aField', 'anotherField');
    }

    /**
     * @test
     */
    public function addGroupByQuotesIdentifierAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->quoteIdentifiers(['aField', 'anotherField'])
            ->shouldBeCalled()
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->addGroupBy('aField', 'anotherField')
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->addGroupBy('aField', 'anotherField');
    }

    /**
     * @test
     */
    public function setValueQuotesIdentifierAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->quoteIdentifier('aField')
            ->shouldBeCalled()
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->createNamedParameter('aValue', Argument::cetera())
            ->shouldBeCalled()
            ->willReturn(':dcValue1');
        $this->concreteQueryBuilder->setValue('aField', ':dcValue1')
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->setValue('aField', 'aValue');
    }

    /**
     * @test
     */
    public function setValueWithoutNamedParameterQuotesIdentifierAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->quoteIdentifier('aField')
            ->shouldBeCalled()
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->setValue('aField', 'aValue')
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->setValue('aField', 'aValue', false);
    }

    /**
     * @test
     */
    public function valuesQuotesIdentifiersAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->quoteColumnValuePairs(['aField' => ':dcValue1', 'aValue' => ':dcValue2'])
            ->shouldBeCalled()
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->createNamedParameter(1, Argument::cetera())
            ->shouldBeCalled()
            ->willReturn(':dcValue1');
        $this->concreteQueryBuilder->createNamedParameter(2, Argument::cetera())
            ->shouldBeCalled()
            ->willReturn(':dcValue2');
        $this->concreteQueryBuilder->values(['aField' => ':dcValue1', 'aValue' => ':dcValue2'])
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->values(['aField' => 1, 'aValue' => 2]);
    }

    /**
     * @test
     */
    public function valuesWithoutNamedParametersQuotesIdentifiersAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->quoteColumnValuePairs(['aField' => 1, 'aValue' => 2])
            ->shouldBeCalled()
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->values(['aField' => 1, 'aValue' => 2])
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->values(['aField' => 1, 'aValue' => 2], false);
    }

    /**
     * @test
     */
    public function havingDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->having('uid=1', 'type=9')
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->having('uid=1', 'type=9');
    }

    /**
     * @test
     */
    public function andHavingDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->andHaving('uid=1', 'type=9')
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->andHaving('uid=1', 'type=9');
    }

    /**
     * @test
     */
    public function orHavingDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->orHaving('uid=1', 'type=9')
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->orHaving('uid=1', 'type=9');
    }

    /**
     * @test
     */
    public function orderByQuotesIdentifierAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->quoteIdentifier('aField')
            ->shouldBeCalled()
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->orderBy('aField', null)
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->orderBy('aField');
    }

    /**
     * @test
     */
    public function addOrderByQuotesIdentifierAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->quoteIdentifier('aField')
            ->shouldBeCalled()
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->addOrderBy('aField', 'DESC')
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->addOrderBy('aField', 'DESC');
    }

    /**
     * @test
     */
    public function getQueryPartDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->getQueryPart('from')
            ->shouldBeCalled()
            ->willReturn('aTable');

        $this->subject->getQueryPart('from');
    }

    /**
     * @test
     */
    public function getQueryPartsDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->getQueryParts()
            ->shouldBeCalled()
            ->willReturn([]);

        $this->subject->getQueryParts();
    }

    /**
     * @test
     */
    public function resetQueryPartsDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->resetQueryParts(['select', 'from'])
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->resetQueryParts(['select', 'from']);
    }

    /**
     * @test
     */
    public function resetQueryPartDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->resetQueryPart('select')
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->resetQueryPart('select');
    }

    /**
     * @test
     */
    public function createNamedParameterDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->createNamedParameter(5, Argument::cetera())
            ->shouldBeCalled()
            ->willReturn(':dcValue1');

        $this->subject->createNamedParameter(5);
    }

    /**
     * @test
     */
    public function createPositionalParameterDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->createPositionalParameter(5, Argument::cetera())
            ->shouldBeCalled()
            ->willReturn('?');

        $this->subject->createPositionalParameter(5);
    }

    /**
     * @test
     */
    public function queryRestrictionsAreAddedForSelectOnExecute(): void
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

        $this->connection->quoteIdentifier(Argument::cetera())
            ->willReturnArgument(0);
        $this->connection->quoteIdentifiers(Argument::cetera())
            ->willReturnArgument(0);

        $connectionBuilder = GeneralUtility::makeInstance(
            \Doctrine\DBAL\Query\QueryBuilder::class,
            $this->connection->reveal()
        );

        $expressionBuilder = GeneralUtility::makeInstance(ExpressionBuilder::class, $this->connection->reveal());
        $this->connection->getExpressionBuilder()
            ->willReturn($expressionBuilder);

        $subject = GeneralUtility::makeInstance(
            QueryBuilder::class,
            $this->connection->reveal(),
            null,
            $connectionBuilder
        );

        $subject->select('*')
            ->from('pages')
            ->where('uid=1');

        $expectedSQL = 'SELECT * FROM pages WHERE (uid=1) AND ((pages.deleted = 0) AND (pages.hidden = 0))';
        $this->connection->executeQuery($expectedSQL, Argument::cetera())
            ->shouldBeCalled();

        $subject->execute();
    }

    /**
     * @test
     */
    public function queryRestrictionsAreAddedForCountOnExecute(): void
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

        $this->connection->quoteIdentifier(Argument::cetera())
            ->willReturnArgument(0);
        $this->connection->quoteIdentifiers(Argument::cetera())
            ->willReturnArgument(0);

        $connectionBuilder = GeneralUtility::makeInstance(
            \Doctrine\DBAL\Query\QueryBuilder::class,
            $this->connection->reveal()
        );

        $expressionBuilder = GeneralUtility::makeInstance(ExpressionBuilder::class, $this->connection->reveal());
        $this->connection->getExpressionBuilder()
            ->willReturn($expressionBuilder);

        $subject = GeneralUtility::makeInstance(
            QueryBuilder::class,
            $this->connection->reveal(),
            null,
            $connectionBuilder
        );

        $subject->count('uid')
            ->from('pages')
            ->where('uid=1');

        $expectedSQL = 'SELECT COUNT(uid) FROM pages WHERE (uid=1) AND ((pages.deleted = 0) AND (pages.hidden = 0))';
        $this->connection->executeQuery($expectedSQL, Argument::cetera())
            ->shouldBeCalled();

        $subject->execute();
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

        $this->connection->quoteIdentifier(Argument::cetera())
            ->willReturnArgument(0);
        $this->connection->quoteIdentifiers(Argument::cetera())
            ->willReturnArgument(0);
        $this->connection->getExpressionBuilder()
            ->willReturn(GeneralUtility::makeInstance(ExpressionBuilder::class, $this->connection->reveal()));

        $concreteQueryBuilder = GeneralUtility::makeInstance(
            \Doctrine\DBAL\Query\QueryBuilder::class,
            $this->connection->reveal()
        );

        $subject = GeneralUtility::makeInstance(
            QueryBuilder::class,
            $this->connection->reveal(),
            null,
            $concreteQueryBuilder
        );

        $subject->select('*')
            ->from('pages')
            ->where('uid=1');

        $expectedSQL = 'SELECT * FROM pages WHERE (uid=1) AND ((pages.deleted = 0) AND (pages.hidden = 0))';
        self::assertSame($expectedSQL, $subject->getSQL());

        $subject->getRestrictions()->removeAll()->add(new DeletedRestriction());

        $expectedSQL = 'SELECT * FROM pages WHERE (uid=1) AND (pages.deleted = 0)';
        self::assertSame($expectedSQL, $subject->getSQL());
    }

    /**
     * @test
     */
    public function queryRestrictionsAreReevaluatedOnSettingsChangeForExecute(): void
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

        $this->connection->quoteIdentifier(Argument::cetera())
            ->willReturnArgument(0);
        $this->connection->quoteIdentifiers(Argument::cetera())
            ->willReturnArgument(0);
        $this->connection->getExpressionBuilder()
            ->willReturn(GeneralUtility::makeInstance(ExpressionBuilder::class, $this->connection->reveal()));

        $concreteQueryBuilder = GeneralUtility::makeInstance(
            \Doctrine\DBAL\Query\QueryBuilder::class,
            $this->connection->reveal()
        );

        $subject = GeneralUtility::makeInstance(
            QueryBuilder::class,
            $this->connection->reveal(),
            null,
            $concreteQueryBuilder
        );

        $subject->select('*')
            ->from('pages')
            ->where('uid=1');

        $subject->getRestrictions()->removeAll()->add(new DeletedRestriction());

        $expectedSQL = 'SELECT * FROM pages WHERE (uid=1) AND (pages.deleted = 0)';
        $this->connection->executeQuery($expectedSQL, Argument::cetera())
            ->shouldBeCalled();

        $subject->execute();

        $subject->resetRestrictions();

        $expectedSQL = 'SELECT * FROM pages WHERE (uid=1) AND ((pages.deleted = 0) AND (pages.hidden = 0))';
        $this->connection->executeQuery($expectedSQL, Argument::cetera())
            ->shouldBeCalled();

        $subject->execute();
    }

    /**
     * @test
     */
    public function getQueriedTablesReturnsSameTableTwiceForInnerJoin(): void
    {
        $this->concreteQueryBuilder->getQueryPart('from')
            ->shouldBeCalled()
            ->willReturn([
                [
                    'table' => 'aTable',
                ],
            ]);
        $this->concreteQueryBuilder->getQueryPart('join')
            ->shouldBeCalled()
            ->willReturn([
                'aTable' => [
                    [
                        'joinType' => 'inner',
                        'joinTable' => 'aTable',
                        'joinAlias' => 'aTable_alias'
                    ]
                ]
            ]);

        // Call a protected method
        $result = \Closure::bind(function () {
            return $this->getQueriedTables();
        }, $this->subject, QueryBuilder::class)();

        $expected = [
            'aTable' => 'aTable',
            'aTable_alias' => 'aTable'
        ];
        self::assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function unquoteSingleIdentifierUnquotesCorrectlyOnDifferentPlatformsDataProvider(): array
    {
        return [
            'mysql' => [
                'platform' => MySqlPlatform::class,
                'quoteChar' => '`',
                'input' => '`anIdentifier`',
                'expected' => 'anIdentifier',
            ],
            'mysql with spaces' => [
                'platform' => MySqlPlatform::class,
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
            'mssql' => [
                'platform' => SQLServerPlatform::class,
                'quoteChar' => '', // no single quote character, but [ and ]
                'input' => '[anIdentifier]',
                'expected' => 'anIdentifier',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider unquoteSingleIdentifierUnquotesCorrectlyOnDifferentPlatformsDataProvider
     * @param string $platform
     * @param string $quoteChar
     * @param string $input
     * @param string $expected
     */
    public function unquoteSingleIdentifierUnquotesCorrectlyOnDifferentPlatforms(string $platform, string $quoteChar, string $input, string $expected): void
    {
        $connectionProphecy = $this->prophesize(Connection::class);
        $databasePlatformProphecy = $this->prophesize($platform);
        $databasePlatformProphecy->getIdentifierQuoteCharacter()->willReturn($quoteChar);
        $connectionProphecy->getDatabasePlatform()->willReturn($databasePlatformProphecy);
        $subject = $this->getAccessibleMock(QueryBuilder::class, ['dummy'], [$connectionProphecy->reveal()]);
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

        $this->connection->quoteIdentifier(Argument::cetera())
            ->willReturnArgument(0);
        $this->connection->quoteIdentifiers(Argument::cetera())
            ->willReturnArgument(0);
        $this->connection->getExpressionBuilder()
            ->willReturn(GeneralUtility::makeInstance(ExpressionBuilder::class, $this->connection->reveal()));

        $concreteQueryBuilder = GeneralUtility::makeInstance(
            \Doctrine\DBAL\Query\QueryBuilder::class,
            $this->connection->reveal()
        );

        $subject = GeneralUtility::makeInstance(
            QueryBuilder::class,
            $this->connection->reveal(),
            null,
            $concreteQueryBuilder
        );

        $subject->select('*')
            ->from('pages')
            ->where('uid=1');

        $expectedSQL = 'SELECT * FROM pages WHERE (uid=1) AND ((pages.deleted = 0) AND (pages.hidden = 0))';
        self::assertSame($expectedSQL, $subject->getSQL());

        $clonedQueryBuilder = clone $subject;
        //just after cloning both query builders should return the same sql
        self::assertSame($expectedSQL, $clonedQueryBuilder->getSQL());

        //change cloned QueryBuilder
        $clonedQueryBuilder->count('*');
        $expectedCountSQL = 'SELECT COUNT(*) FROM pages WHERE (uid=1) AND ((pages.deleted = 0) AND (pages.hidden = 0))';
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
    public function settingRestrictionContainerWillAddAdditionalRestrictionsFromConstructor()
    {
        $restrictionClass = get_class($this->prophesize(QueryRestrictionInterface::class)->reveal());
        $queryBuilder = new QueryBuilder(
            $this->connection->reveal(),
            null,
            $this->concreteQueryBuilder->reveal(),
            [
                $restrictionClass => [],
            ]
        );

        $container = $this->prophesize(AbstractRestrictionContainer::class);
        $container->add(new $restrictionClass())->shouldBeCalled();

        $queryBuilder->setRestrictions($container->reveal());
    }

    /**
     * @test
     */
    public function settingRestrictionContainerWillAddAdditionalRestrictionsFromConfiguration()
    {
        $restrictionClass = get_class($this->prophesize(QueryRestrictionInterface::class)->reveal());
        $GLOBALS['TYPO3_CONF_VARS']['DB']['additionalQueryRestrictions'][$restrictionClass] = [];
        $queryBuilder = new QueryBuilder(
            $this->connection->reveal(),
            null,
            $this->concreteQueryBuilder->reveal()
        );

        $container = $this->prophesize(AbstractRestrictionContainer::class);
        $container->add(new $restrictionClass())->shouldBeCalled();

        $queryBuilder->setRestrictions($container->reveal());
    }

    /**
     * @test
     */
    public function settingRestrictionContainerWillNotAddAdditionalRestrictionsFromConfigurationIfNotDisabled()
    {
        $restrictionClass = get_class($this->prophesize(QueryRestrictionInterface::class)->reveal());
        $GLOBALS['TYPO3_CONF_VARS']['DB']['additionalQueryRestrictions'][$restrictionClass] = ['disabled' => true];
        $queryBuilder = new QueryBuilder(
            $this->connection->reveal(),
            null,
            $this->concreteQueryBuilder->reveal()
        );

        $container = $this->prophesize(AbstractRestrictionContainer::class);
        $container->add(new $restrictionClass())->shouldNotBeCalled();

        $queryBuilder->setRestrictions($container->reveal());
    }

    /**
     * @test
     */
    public function resettingToDefaultRestrictionContainerWillAddAdditionalRestrictionsFromConfiguration()
    {
        $restrictionClass = get_class($this->prophesize(QueryRestrictionInterface::class)->reveal());
        $queryBuilder = new QueryBuilder(
            $this->connection->reveal(),
            null,
            $this->concreteQueryBuilder->reveal(),
            [
                $restrictionClass => [],
            ]
        );

        $container = $this->prophesize(DefaultRestrictionContainer::class);
        $container->add(new $restrictionClass())->shouldBeCalled();
        GeneralUtility::addInstance(DefaultRestrictionContainer::class, $container->reveal());

        $queryBuilder->resetRestrictions();
    }

    /**
     * @test
     * @dataProvider createNamedParameterInput
     * @param mixed $input
     * @param int $type
     */
    public function setWithNamedParameterPassesGivenTypeToCreateNamedParameter($input, int $type): void
    {
        $this->connection->quoteIdentifier('aField')
            ->willReturnArgument(0);
        $concreteQueryBuilder = new \Doctrine\DBAL\Query\QueryBuilder($this->connection->reveal());

        $subject = new QueryBuilder($this->connection->reveal(), null, $concreteQueryBuilder);
        $subject->set('aField', $input, true, $type);
        self::assertSame($type, $concreteQueryBuilder->getParameterType('dcValue1'));
    }

    public function createNamedParameterInput(): array
    {
        return [
            'string input and output' => [
                'aValue',
                \PDO::PARAM_STR,
            ],
            'int input and string output' => [
                17,
                \PDO::PARAM_STR,
            ],
            'int input and int output' => [
                17,
                \PDO::PARAM_INT,
            ],
            'string input and array output' => [
                'aValue',
                Connection::PARAM_STR_ARRAY
            ],
        ];
    }

    public function castFieldToTextTypeDataProvider(): array
    {
        return [
            'Test cast for MySqlPlatform' => [
                new MySqlPlatform(),
                'CONVERT(aField, CHAR)'
            ],
            'Test cast for PostgreSqlPlatform' => [
                new PostgreSqlPlatform(),
                'aField::text'
            ],
            'Test cast for SqlitePlatform' => [
                new SqlitePlatform(),
                'CAST(aField as TEXT)'
            ],
            'Test cast for SQLServerPlatform' => [
                new SQLServerPlatform(),
                'CAST(aField as VARCHAR)'
            ],
            'Test cast for OraclePlatform' => [
                new OraclePlatform(),
                'CAST(aField as VARCHAR)'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider castFieldToTextTypeDataProvider
     *
     * @param AbstractPlatform $platform
     * @param string $expectation
     */
    public function castFieldToTextType(AbstractPlatform $platform, string $expectation): void
    {
        $this->connection->quoteIdentifier('aField')
            ->shouldBeCalled()
            ->willReturnArgument(0);

        $this->connection->getDatabasePlatform()->willReturn($platform);

        $concreteQueryBuilder = new \Doctrine\DBAL\Query\QueryBuilder($this->connection->reveal());

        $subject = new QueryBuilder($this->connection->reveal(), null, $concreteQueryBuilder);
        $result = $subject->castFieldToTextType('aField');

        $this->connection->quoteIdentifier('aField')->shouldHaveBeenCalled();
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

        $this->connection->quoteIdentifier(Argument::cetera())
            ->willReturnArgument(0);
        $this->connection->quoteIdentifiers(Argument::cetera())
            ->willReturnArgument(0);

        $connectionBuilder = GeneralUtility::makeInstance(
            \Doctrine\DBAL\Query\QueryBuilder::class,
            $this->connection->reveal()
        );

        $expressionBuilder = GeneralUtility::makeInstance(ExpressionBuilder::class, $this->connection->reveal());
        $this->connection->getExpressionBuilder()->willReturn($expressionBuilder);

        $subject = new QueryBuilder(
            $this->connection->reveal(),
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

        $this->connection->executeQuery(
            'SELECT * FROM pages LEFT JOIN tt_content content ON pages.uid = content.pid WHERE (uid = 1) AND ((pages.deleted = 0) AND (pages.hidden = 0))',
            Argument::cetera()
        )->shouldBeCalled();

        $subject->execute();
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

        $this->connection->quoteIdentifier(Argument::cetera())
            ->willReturnArgument(0);
        $this->connection->quoteIdentifiers(Argument::cetera())
            ->willReturnArgument(0);

        $connectionBuilder = GeneralUtility::makeInstance(
            \Doctrine\DBAL\Query\QueryBuilder::class,
            $this->connection->reveal()
        );

        $expressionBuilder = GeneralUtility::makeInstance(ExpressionBuilder::class, $this->connection->reveal());
        $this->connection->getExpressionBuilder()->willReturn($expressionBuilder);

        $subject = new QueryBuilder(
            $this->connection->reveal(),
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

        $this->connection->executeQuery(
            'SELECT * FROM pages LEFT JOIN tt_content content ON pages.uid = content.pid WHERE (uid = 1) AND (pages.hidden = 0)',
            Argument::cetera()
        )->shouldBeCalled();

        $subject->execute();
    }
}
