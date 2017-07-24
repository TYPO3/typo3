<?php
declare(strict_types=1);
namespace TYPO3\CMS\Core\Tests\Unit\Database\Query;

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

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Prophecy\Argument;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Tests\Unit\Database\Mocks\MockPlatform;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

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
    protected function setUp()
    {
        parent::setUp();

        $this->concreteQueryBuilder = $this->prophesize(\Doctrine\DBAL\Query\QueryBuilder::class);

        $this->connection = $this->prophesize(Connection::class);
        $this->connection->getDatabasePlatform()->willReturn(new MockPlatform());

        $this->subject = GeneralUtility::makeInstance(
            QueryBuilder::class,
            $this->connection->reveal(),
            null,
            $this->concreteQueryBuilder->reveal()
        );
    }

    /**
     * @test
     */
    public function exprReturnsExpressionBuilderForConnection()
    {
        $this->connection->getExpressionBuilder()
            ->shouldBeCalled()
            ->willReturn(GeneralUtility::makeInstance(ExpressionBuilder::class, $this->connection->reveal()));

        $this->subject->expr();
    }

    /**
     * @test
     */
    public function getTypeDelegatesToConcreteQueryBuilder()
    {
        $this->concreteQueryBuilder->getType()
            ->shouldBeCalled()
            ->willReturn(\Doctrine\DBAL\Query\QueryBuilder::INSERT);

        $this->subject->getType();
    }

    /**
     * @test
     */
    public function getStateDelegatesToConcreteQueryBuilder()
    {
        $this->concreteQueryBuilder->getState()
            ->shouldBeCalled()
            ->willReturn(\Doctrine\DBAL\Query\QueryBuilder::STATE_CLEAN);

        $this->subject->getState();
    }

    /**
     * @test
     */
    public function getSQLDelegatesToConcreteQueryBuilder()
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
    public function setParameterDelegatesToConcreteQueryBuilder()
    {
        $this->concreteQueryBuilder->setParameter(Argument::exact('aField'), Argument::exact(5), Argument::cetera())
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->setParameter('aField', 5);
    }

    /**
     * @test
     */
    public function setParametersDelegatesToConcreteQueryBuilder()
    {
        $this->concreteQueryBuilder->setParameters(Argument::exact(['aField' => 'aValue']), Argument::exact([]))
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->setParameters(['aField' => 'aValue']);
    }

    /**
     * @test
     */
    public function getParametersDelegatesToConcreteQueryBuilder()
    {
        $this->concreteQueryBuilder->getParameters()
            ->shouldBeCalled()
            ->willReturn(['aField' => 'aValue']);

        $this->subject->getParameters();
    }

    /**
     * @test
     */
    public function getParameterDelegatesToConcreteQueryBuilder()
    {
        $this->concreteQueryBuilder->getParameter(Argument::exact('aField'))
            ->shouldBeCalled()
            ->willReturn('aValue');

        $this->subject->getParameter('aField');
    }

    /**
     * @test
     */
    public function getParameterTypesDelegatesToConcreteQueryBuilder()
    {
        $this->concreteQueryBuilder->getParameterTypes()
            ->shouldBeCalled()
            ->willReturn([]);

        $this->subject->getParameterTypes();
    }

    /**
     * @test
     */
    public function getParameterTypeDelegatesToConcreteQueryBuilder()
    {
        $this->concreteQueryBuilder->getParameterType(Argument::exact('aField'))
            ->shouldBeCalled()
            ->willReturn(Connection::PARAM_STR);

        $this->subject->getParameterType('aField');
    }

    /**
     * @test
     */
    public function setFirstResultDelegatesToConcreteQueryBuilder()
    {
        $this->concreteQueryBuilder->setFirstResult(Argument::cetera())
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->setFirstResult(1);
    }

    /**
     * @test
     */
    public function getFirstResultDelegatesToConcreteQueryBuilder()
    {
        $this->concreteQueryBuilder->getFirstResult()
            ->shouldBeCalled()
            ->willReturn(1);

        $this->subject->getFirstResult();
    }

    /**
     * @test
     */
    public function setMaxResultsDelegatesToConcreteQueryBuilder()
    {
        $this->concreteQueryBuilder->setMaxResults(Argument::cetera())
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->setMaxResults(1);
    }

    /**
     * @test
     */
    public function getMaxResultsDelegatesToConcreteQueryBuilder()
    {
        $this->concreteQueryBuilder->getMaxResults()
            ->shouldBeCalled()
            ->willReturn(1);

        $this->subject->getMaxResults();
    }

    /**
     * @test
     */
    public function addDelegatesToConcreteQueryBuilder()
    {
        $this->concreteQueryBuilder->add(Argument::exact('select'), Argument::exact('aField'), Argument::cetera())
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->add('select', 'aField');
    }

    /**
     * @test
     */
    public function countBuildsExpressionAndCallsSelect()
    {
        $this->concreteQueryBuilder->select(Argument::exact('COUNT(*)'))
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->count('*');
    }

    /**
     * @test
     */
    public function selectQuotesIdentifiersAndDelegatesToConcreteQueryBuilder()
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
    public function quoteIdentifiersForSelectDataProvider()
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
    public function quoteIdentifiersForSelect($identifier, $expectedResult)
    {
        $this->connection->quoteIdentifier(Argument::cetera())->will(
            function ($args) {
                $platform = new MockPlatform();

                return $platform->quoteIdentifier($args[0]);
            }
        );

        $this->assertSame([$expectedResult], $this->subject->quoteIdentifiersForSelect([$identifier]));
    }

    /**
     * @test
     */
    public function quoteIdentifiersForSelectWithInvalidAlias()
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
    public function selectDoesNotQuoteStarPlaceholder()
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
    public function addSelectQuotesIdentifiersAndDelegatesToConcreteQueryBuilder()
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
    public function addSelectDoesNotQuoteStarPlaceholder()
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
    public function selectLiteralDirectlyDelegatesToConcreteQueryBuilder()
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
    public function addSelectLiteralDirectlyDelegatesToConcreteQueryBuilder()
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
    public function deleteQuotesIdentifierAndDelegatesToConcreteQueryBuilder()
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
    public function updateQuotesIdentifierAndDelegatesToConcreteQueryBuilder()
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
    public function insertQuotesIdentifierAndDelegatesToConcreteQueryBuilder()
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
    public function fromQuotesIdentifierAndDelegatesToConcreteQueryBuilder()
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
    public function joinQuotesIdentifiersAndDelegatesToConcreteQueryBuilder()
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
    public function innerJoinQuotesIdentifiersAndDelegatesToConcreteQueryBuilder()
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
    public function leftJoinQuotesIdentifiersAndDelegatesToConcreteQueryBuilder()
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
    public function rightJoinQuotesIdentifiersAndDelegatesToConcreteQueryBuilder()
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
    public function setQuotesIdentifierAndDelegatesToConcreteQueryBuilder()
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
    public function setWithoutNamedParameterQuotesIdentifierAndDelegatesToConcreteQueryBuilder()
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
    public function whereDelegatesToConcreteQueryBuilder()
    {
        $this->concreteQueryBuilder->where('uid=1', 'type=9')
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->where('uid=1', 'type=9');
    }

    /**
     * @test
     */
    public function andWhereDelegatesToConcreteQueryBuilder()
    {
        $this->concreteQueryBuilder->andWhere('uid=1', 'type=9')
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->andWhere('uid=1', 'type=9');
    }

    /**
     * @test
     */
    public function orWhereDelegatesToConcreteQueryBuilder()
    {
        $this->concreteQueryBuilder->orWhere('uid=1', 'type=9')
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->orWhere('uid=1', 'type=9');
    }

    /**
     * @test
     */
    public function groupByQuotesIdentifierAndDelegatesToConcreteQueryBuilder()
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
    public function addGroupByQuotesIdentifierAndDelegatesToConcreteQueryBuilder()
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
    public function setValueQuotesIdentifierAndDelegatesToConcreteQueryBuilder()
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
    public function setValueWithoudNamedParameterQuotesIdentifierAndDelegatesToConcreteQueryBuilder()
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
    public function valuesQuotesIdentifiersAndDelegatesToConcreteQueryBuilder()
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
    public function valuesWithoutNamedParametersQuotesIdentifiersAndDelegatesToConcreteQueryBuilder()
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
    public function havingDelegatesToConcreteQueryBuilder()
    {
        $this->concreteQueryBuilder->having('uid=1', 'type=9')
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->having('uid=1', 'type=9');
    }

    /**
     * @test
     */
    public function andHavingDelegatesToConcreteQueryBuilder()
    {
        $this->concreteQueryBuilder->andHaving('uid=1', 'type=9')
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->andHaving('uid=1', 'type=9');
    }

    /**
     * @test
     */
    public function orHavingDelegatesToConcreteQueryBuilder()
    {
        $this->concreteQueryBuilder->orHaving('uid=1', 'type=9')
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->orHaving('uid=1', 'type=9');
    }

    /**
     * @test
     */
    public function orderByQuotesIdentifierAndDelegatesToConcreteQueryBuilder()
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
    public function addOrderByQuotesIdentifierAndDelegatesToConcreteQueryBuilder()
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
    public function getQueryPartDelegatesToConcreteQueryBuilder()
    {
        $this->concreteQueryBuilder->getQueryPart('from')
            ->shouldBeCalled()
            ->willReturn('aTable');

        $this->subject->getQueryPart('from');
    }

    /**
     * @test
     */
    public function getQueryPartsDelegatesToConcreteQueryBuilder()
    {
        $this->concreteQueryBuilder->getQueryParts()
            ->shouldBeCalled()
            ->willReturn([]);

        $this->subject->getQueryParts();
    }

    /**
     * @test
     */
    public function resetQueryPartsDelegatesToConcreteQueryBuilder()
    {
        $this->concreteQueryBuilder->resetQueryParts(['select', 'from'])
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->resetQueryParts(['select', 'from']);
    }

    /**
     * @test
     */
    public function resetQueryPartDelegatesToConcreteQueryBuilder()
    {
        $this->concreteQueryBuilder->resetQueryPart('select')
            ->shouldBeCalled()
            ->willReturn($this->subject);

        $this->subject->resetQueryPart('select');
    }

    /**
     * @test
     */
    public function createNamedParameterDelegatesToConcreteQueryBuilder()
    {
        $this->concreteQueryBuilder->createNamedParameter(5, Argument::cetera())
            ->shouldBeCalled()
            ->willReturn(':dcValue1');

        $this->subject->createNamedParameter(5);
    }

    /**
     * @test
     */
    public function createPositionalParameterDelegatesToConcreteQueryBuilder()
    {
        $this->concreteQueryBuilder->createPositionalParameter(5, Argument::cetera())
            ->shouldBeCalled()
            ->willReturn('?');

        $this->subject->createPositionalParameter(5);
    }

    /**
     * @test
     */
    public function queryRestrictionsAreAddedForSelectOnExecute()
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
    public function queryRestrictionsAreAddedForCountOnExecute()
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
    public function queryRestrictionsAreReevaluatedOnSettingsChangeForGetSQL()
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
        $this->assertSame($expectedSQL, $subject->getSQL());

        $subject->getRestrictions()->removeAll()->add(new DeletedRestriction());

        $expectedSQL = 'SELECT * FROM pages WHERE (uid=1) AND (pages.deleted = 0)';
        $this->assertSame($expectedSQL, $subject->getSQL());
    }

    /**
     * @test
     */
    public function queryRestrictionsAreReevaluatedOnSettingsChangeForExecute()
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
    public function getQueriedTablesReturnsSameTableTwiceForInnerJoin()
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
        $result = $this->callInaccessibleMethod($this->subject, 'getQueriedTables');

        $expected = [
            'aTable' => 'aTable',
            'aTable_alias' => 'aTable'
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function unquoteSingleIdentifierUnquotesCorrectlyOnDifferentPlatformsDataProvider()
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
     */
    public function unquoteSingleIdentifierUnquotesCorrectlyOnDifferentPlatforms($platform, $quoteChar, $input, $expected)
    {
        $connectionProphecy = $this->prophesize(Connection::class);
        $databasePlatformProphecy = $this->prophesize($platform);
        $databasePlatformProphecy->getIdentifierQuoteCharacter()->willReturn($quoteChar);
        $connectionProphecy->getDatabasePlatform()->willReturn($databasePlatformProphecy);
        $subject = GeneralUtility::makeInstance(QueryBuilder::class, $connectionProphecy->reveal());
        $result = $this->callInaccessibleMethod($subject, 'unquoteSingleIdentifier', $input);
        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function cloningQueryBuilderClonesConcreteQueryBuilder()
    {
        $clonedQueryBuilder = clone $this->subject;
        self::assertNotSame($this->subject->getConcreteQueryBuilder(), $clonedQueryBuilder->getConcreteQueryBuilder());
    }

    /**
     * @test
     */
    public function changingClonedQueryBuilderDoesNotInfluenceSourceOne()
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
        $this->assertSame($expectedSQL, $subject->getSQL());

        $clonedQueryBuilder = clone $subject;
        //just after cloning both query builders should return the same sql
        $this->assertSame($expectedSQL, $clonedQueryBuilder->getSQL());

        //change cloned QueryBuilder
        $clonedQueryBuilder->count('*');
        $expectedCountSQL = 'SELECT COUNT(*) FROM pages WHERE (uid=1) AND ((pages.deleted = 0) AND (pages.hidden = 0))';
        $this->assertSame($expectedCountSQL, $clonedQueryBuilder->getSQL());

        //check if the original QueryBuilder has not changed
        $this->assertSame($expectedSQL, $subject->getSQL());

        //change restrictions in the original QueryBuilder and check if cloned has changed
        $subject->getRestrictions()->removeAll()->add(new DeletedRestriction());
        $expectedSQL = 'SELECT * FROM pages WHERE (uid=1) AND (pages.deleted = 0)';
        $this->assertSame($expectedSQL, $subject->getSQL());

        $this->assertSame($expectedCountSQL, $clonedQueryBuilder->getSQL());
    }
}
