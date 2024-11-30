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

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform as DoctrineAbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDBPlatform as DoctrineMariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform as DoctrineMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform as DoctrinePostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform as DoctrineSQLitePlatform;
use Doctrine\DBAL\Query\From;
use Doctrine\DBAL\Query\Join;
use Doctrine\DBAL\Query\QueryException;
use Doctrine\DBAL\Query\QueryType;
use Doctrine\DBAL\Query\UnionType;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\ConcreteQueryBuilder;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\AbstractRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\DefaultRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionInterface;
use TYPO3\CMS\Core\Tests\Unit\Database\Mocks\MockPlatform\MockPlatform;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class QueryBuilderTest extends UnitTestCase
{
    private Connection&MockObject $connection;
    private QueryBuilder $subject;
    private ConcreteQueryBuilder&MockObject $concreteQueryBuilder;

    /**
     * Create a new database connection mock object for every test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->concreteQueryBuilder = $this->createMock(ConcreteQueryBuilder::class);
        $this->connection = $this->createMock(Connection::class);
        $this->subject = new QueryBuilder(
            $this->connection,
            null,
            $this->concreteQueryBuilder
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->concreteQueryBuilder,
            $this->connection,
            $this->subject,
        );
        parent::tearDown();
    }

    #[Test]
    public function exprReturnsExpressionBuilderForConnection(): void
    {
        $this->connection->expects(self::atLeastOnce())->method('getExpressionBuilder')
            ->willReturn(GeneralUtility::makeInstance(ExpressionBuilder::class, $this->connection));
        $this->subject->expr();
    }

    #[Test]
    public function getSQLDelegatesToConcreteQueryBuilder(): void
    {
        // Set protected type of the concrete QueryBuilder
        $setQueryType = \Closure::bind(function (string $property, QueryType $value) {
            $this->{$property} = $value;
        }, $this->concreteQueryBuilder, ConcreteQueryBuilder::class);
        $setQueryType->call($this->concreteQueryBuilder, 'type', QueryType::UPDATE);
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('getSQL')
            ->willReturn('UPDATE aTable SET pid = 7');
        $this->subject->getSQL();
    }

    #[Test]
    public function setParameterDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('setParameter')->with('aField', 5, self::anything())
            ->willReturn($this->subject);
        $this->subject->setParameter('aField', 5);
    }

    #[Test]
    public function setParametersDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('setParameters')->with(['aField' => 'aValue'], [])
            ->willReturn($this->subject);
        $this->subject->setParameters(['aField' => 'aValue']);
    }

    #[Test]
    public function getParametersDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('getParameters')
            ->willReturn(['aField' => 'aValue']);
        $this->subject->getParameters();
    }

    #[Test]
    public function getParameterDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('getParameter')->with('aField')
            ->willReturn('aValue');
        $this->subject->getParameter('aField');
    }

    #[Test]
    public function getParameterTypesDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('getParameterTypes')->willReturn([]);
        $this->subject->getParameterTypes();
    }

    #[Test]
    public function getParameterTypeDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('getParameterType')->with('aField')
            ->willReturn(Connection::PARAM_STR);
        $this->subject->getParameterType('aField');
    }

    #[Test]
    public function setFirstResultDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('setFirstResult')->with(self::anything())
            ->willReturn($this->subject);
        $this->subject->setFirstResult(1);
    }

    #[Test]
    public function getFirstResultDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('getFirstResult')->willReturn(1);
        $this->subject->getFirstResult();
    }

    #[Test]
    public function setMaxResultsDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('setMaxResults')->with(self::anything())
            ->willReturn($this->subject);
        $this->subject->setMaxResults(1);
    }

    #[Test]
    public function getMaxResultsDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('getMaxResults')->willReturn(1);
        $this->subject->getMaxResults();
    }

    #[Test]
    public function countBuildsExpressionAndCallsSelect(): void
    {
        $this->connection->method('getDatabasePlatform')->willReturn(new MockPlatform());
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('select')->with('COUNT(*)')
            ->willReturn($this->subject);
        $this->subject->count('*');
    }

    #[Test]
    public function selectQuotesIdentifiersAndDelegatesToConcreteQueryBuilder(): void
    {
        $series = [
            ['aField'],
            ['anotherField'],
        ];
        $this->connection->expects(self::exactly(2))->method('quoteIdentifier')
            ->willReturnCallback(function (string $field) use (&$series): string {
                $arguments = array_shift($series);
                self::assertSame($arguments[0], $field);
                return $field;
            });
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('select')->with('aField', 'anotherField')
            ->willReturn($this->subject);
        $this->subject->select('aField', 'anotherField');
    }

    public static function quoteIdentifiersForSelectDataProvider(): array
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

    #[DataProvider('quoteIdentifiersForSelectDataProvider')]
    #[Test]
    public function quoteIdentifiersForSelect(string $identifier, string $expectedResult): void
    {
        $this->connection->method('quoteIdentifier')->willReturnCallback(
            static function (string $identifier): string {
                return (new MockPlatform())->quoteIdentifier($identifier);
            }
        );
        self::assertSame([$expectedResult], $this->subject->quoteIdentifiersForSelect([$identifier]));
    }

    #[Test]
    public function quoteIdentifiersForSelectWithInvalidAlias(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1461170686);
        $this->connection->method('quoteIdentifier')->willReturnCallback(
            static function (string $identifier): string {
                return (new MockPlatform())->quoteIdentifier($identifier);
            }
        );
        $this->subject->quoteIdentifiersForSelect(['aField AS anotherField,someField AS someThing']);
    }

    #[Test]
    public function selectDoesNotQuoteStarPlaceholder(): void
    {
        $this->connection->expects(self::never())->method('quoteIdentifier')->with('*');
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('select')->with('*')
            ->willReturn($this->subject);
        $this->subject->select('*');
    }

    #[Test]
    public function addSelectQuotesIdentifiersAndDelegatesToConcreteQueryBuilder(): void
    {
        $series = [
            ['aField'],
            ['anotherField'],
        ];
        $this->connection->expects(self::exactly(2))->method('quoteIdentifier')
            ->willReturnCallback(function (string $field) use (&$series): string {
                $arguments = array_shift($series);
                self::assertSame($arguments[0], $field);
                return $field;
            });
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('addSelect')->with('aField', 'anotherField')
            ->willReturn($this->subject);
        $this->subject->addSelect('aField', 'anotherField');
    }

    #[Test]
    public function addSelectDoesNotQuoteStarPlaceholder(): void
    {
        $this->connection->expects(self::never())->method('quoteIdentifier')->with('*');
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('addSelect')->with('*')
            ->willReturn($this->subject);
        $this->subject->addSelect('*');
    }

    #[Test]
    public function selectLiteralDirectlyDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->expects(self::never())->method('quoteIdentifier')->with(self::anything());
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('select')->with('MAX(aField) AS anAlias')
            ->willReturn($this->subject);
        $this->subject->selectLiteral('MAX(aField) AS anAlias');
    }

    #[Test]
    public function addSelectLiteralDirectlyDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->expects(self::never())->method('quoteIdentifier')->with(self::anything());
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('addSelect')->with('MAX(aField) AS anAlias')
            ->willReturn($this->subject);
        $this->subject->addSelectLiteral('MAX(aField) AS anAlias');
    }

    #[Test]
    public function deleteQuotesIdentifierAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->expects(self::atLeastOnce())->method('quoteIdentifier')->with('aTable')
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->method('delete')->with('aTable')->willReturn($this->subject);
        $this->subject->delete('aTable');
    }

    #[Test]
    public function updateQuotesIdentifierAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->expects(self::atLeastOnce())->method('quoteIdentifier')->with('aTable')
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->method('update')->with('aTable')->willReturn($this->subject);
        $this->subject->update('aTable');
    }

    #[Test]
    public function insertQuotesIdentifierAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->expects(self::atLeastOnce())->method('quoteIdentifier')->with('aTable')
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->method('insert')->with('aTable')->willReturn($this->subject);
        $this->subject->insert('aTable');
    }

    /**
     * @todo: Test with alias
     */
    #[Test]
    public function fromQuotesIdentifierAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->expects(self::atLeastOnce())->method('quoteIdentifier')->with('aTable')
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->method('from')->with('aTable', self::anything())->willReturn($this->subject);
        $this->subject->from('aTable');
    }

    #[Test]
    public function joinQuotesIdentifiersAndDelegatesToConcreteQueryBuilder(): void
    {
        $series = [
            ['fromAlias'],
            ['join'],
            ['alias'],
        ];
        $this->connection->expects(self::exactly(3))->method('quoteIdentifier')
            ->willReturnCallback(function (string $field) use (&$series): string {
                $arguments = array_shift($series);
                self::assertSame($arguments[0], $field);
                return $field;
            });
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('innerJoin')
            ->with('fromAlias', 'join', 'alias', null)->willReturn($this->subject);
        $this->subject->join('fromAlias', 'join', 'alias');
    }

    #[Test]
    public function innerJoinQuotesIdentifiersAndDelegatesToConcreteQueryBuilder(): void
    {
        $series = [
            ['fromAlias'],
            ['join'],
            ['alias'],
        ];
        $this->connection->expects(self::exactly(3))->method('quoteIdentifier')
            ->willReturnCallback(function (string $field) use (&$series): string {
                $arguments = array_shift($series);
                self::assertSame($arguments[0], $field);
                return $field;
            });
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('innerJoin')
            ->with('fromAlias', 'join', 'alias', null)->willReturn($this->subject);
        $this->subject->innerJoin('fromAlias', 'join', 'alias');
    }

    #[Test]
    public function leftJoinQuotesIdentifiersAndDelegatesToConcreteQueryBuilder(): void
    {
        $series = [
            ['fromAlias'],
            ['join'],
            ['alias'],
        ];
        $this->connection->expects(self::exactly(3))->method('quoteIdentifier')
            ->willReturnCallback(function (string $field) use (&$series): string {
                $arguments = array_shift($series);
                self::assertSame($arguments[0], $field);
                return $field;
            });
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('leftJoin')
            ->with('fromAlias', 'join', 'alias', self::anything())->willReturn($this->subject);
        $expressionBuilder = GeneralUtility::makeInstance(ExpressionBuilder::class, $this->connection);
        $this->connection->method('getExpressionBuilder')->willReturn($expressionBuilder);
        $this->subject->leftJoin('fromAlias', 'join', 'alias');
    }

    #[Test]
    public function rightJoinQuotesIdentifiersAndDelegatesToConcreteQueryBuilder(): void
    {
        $series = [
            ['fromAlias'],
            ['join'],
            ['alias'],
        ];
        $this->connection->expects(self::exactly(3))->method('quoteIdentifier')
            ->willReturnCallback(function (string $field) use (&$series): string {
                $arguments = array_shift($series);
                self::assertSame($arguments[0], $field);
                return $field;
            });
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('rightJoin')
            ->with('fromAlias', 'join', 'alias', self::anything())->willReturn($this->subject);
        // Set protected properties of the concrete QueryBuilder
        $setParts = \Closure::bind(function (string $property, array $value) {
            $this->{$property} = $value;
        }, $this->concreteQueryBuilder, ConcreteQueryBuilder::class);
        $setParts->call($this->concreteQueryBuilder, 'from', []);
        $expressionBuilder = GeneralUtility::makeInstance(ExpressionBuilder::class, $this->connection);
        $this->connection->method('getExpressionBuilder')->willReturn($expressionBuilder);
        $this->subject->rightJoin('fromAlias', 'join', 'alias');
    }

    #[Test]
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

    #[Test]
    public function setWithoutNamedParameterQuotesIdentifierAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->expects(self::atLeastOnce())->method('quoteIdentifier')->with('aField')
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->expects(self::never())->method('createNamedParameter')->with(self::anything());
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('set')->with('aField', 'aValue')
            ->willReturn($this->subject);
        $this->subject->set('aField', 'aValue', false);
    }

    #[Test]
    public function whereDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('where')->with('uid=1', 'type=9')
            ->willReturn($this->subject);
        $this->subject->where('uid=1', 'type=9');
    }

    #[Test]
    public function andWhereDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('andWhere')->with('uid=1', 'type=9')
            ->willReturn($this->subject);
        $this->subject->andWhere('uid=1', 'type=9');
    }

    #[Test]
    public function orWhereDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('orWhere')->with('uid=1', 'type=9')
            ->willReturn($this->subject);
        $this->subject->orWhere('uid=1', 'type=9');
    }

    #[Test]
    public function groupByQuotesIdentifierAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->expects(self::atLeastOnce())->method('quoteIdentifiers')->with(['aField', 'anotherField'])
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('groupBy')->with('aField', 'anotherField')
            ->willReturn($this->subject);
        $this->subject->groupBy('aField', 'anotherField');
    }

    #[Test]
    public function addGroupByQuotesIdentifierAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->expects(self::atLeastOnce())->method('quoteIdentifiers')->with(['aField', 'anotherField'])
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('addGroupBy')->with('aField', 'anotherField')
            ->willReturn($this->subject);
        $this->subject->addGroupBy('aField', 'anotherField');
    }

    #[Test]
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

    #[Test]
    public function setValueWithoutNamedParameterQuotesIdentifierAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->expects(self::atLeastOnce())->method('quoteIdentifier')->with('aField')
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('setValue')->with('aField', 'aValue')
            ->willReturn($this->subject);
        $this->subject->setValue('aField', 'aValue', false);
    }

    #[Test]
    public function valuesQuotesIdentifiersAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->expects(self::atLeastOnce())->method('quoteColumnValuePairs')
            ->with(['aField' => ':dcValue1', 'aValue' => ':dcValue2'])->willReturnArgument(0);
        $series = [
            [1, ':dcValue1'],
            [2, ':dcValue2'],
        ];
        $this->concreteQueryBuilder->expects(self::exactly(2))->method('createNamedParameter')
            ->willReturnCallback(function (int $value) use (&$series): string {
                $arguments = array_shift($series);
                self::assertSame($arguments[0], $value);
                return $arguments[1];
            });
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('values')
            ->with(['aField' => ':dcValue1', 'aValue' => ':dcValue2'])->willReturn($this->subject);
        $this->subject->values(['aField' => 1, 'aValue' => 2]);
    }

    #[Test]
    public function valuesWithoutNamedParametersQuotesIdentifiersAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->expects(self::atLeastOnce())->method('quoteColumnValuePairs')
            ->with(['aField' => 1, 'aValue' => 2])->willReturnArgument(0);
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('values')
            ->with(['aField' => 1, 'aValue' => 2])->willReturn($this->subject);
        $this->subject->values(['aField' => 1, 'aValue' => 2], false);
    }

    #[Test]
    public function havingDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('having')->with('uid=1', 'type=9')
            ->willReturn($this->subject);
        $this->subject->having('uid=1', 'type=9');
    }

    #[Test]
    public function andHavingDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('andHaving')->with('uid=1', 'type=9')
            ->willReturn($this->subject);
        $this->subject->andHaving('uid=1', 'type=9');
    }

    #[Test]
    public function orHavingDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('orHaving')->with('uid=1', 'type=9')
            ->willReturn($this->subject);
        $this->subject->orHaving('uid=1', 'type=9');
    }

    #[Test]
    public function orderByQuotesIdentifierAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->expects(self::atLeastOnce())->method('quoteIdentifier')->with('aField')
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('orderBy')->with('aField', null)
            ->willReturn($this->subject);
        $this->subject->orderBy('aField');
    }

    #[Test]
    public function addOrderByQuotesIdentifierAndDelegatesToConcreteQueryBuilder(): void
    {
        $this->connection->expects(self::atLeastOnce())->method('quoteIdentifier')->with('aField')
            ->willReturnArgument(0);
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('addOrderBy')->with('aField', 'DESC')
            ->willReturn($this->subject);
        $this->subject->addOrderBy('aField', 'DESC');
    }

    #[Test]
    public function createNamedParameterDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('createNamedParameter')
            ->with(5, self::anything())->willReturn(':dcValue1');
        $this->subject->createNamedParameter(5);
    }

    #[Test]
    public function createPositionalParameterDelegatesToConcreteQueryBuilder(): void
    {
        $this->concreteQueryBuilder->expects(self::atLeastOnce())->method('createPositionalParameter')
            ->with(5, self::anything())->willReturn('?');
        $this->subject->createPositionalParameter(5);
    }

    #[Test]
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

        $connectionBuilder = GeneralUtility::makeInstance(ConcreteQueryBuilder::class, $this->connection);

        $expressionBuilder = GeneralUtility::makeInstance(ExpressionBuilder::class, $this->connection);
        $this->connection->method('getExpressionBuilder')->willReturn($expressionBuilder);

        /** @var QueryBuilder $subject */
        $subject = GeneralUtility::makeInstance(QueryBuilder::class, $this->connection, null, $connectionBuilder, null);

        $subject->select('*')
            ->from('pages')
            ->where('uid=1');

        $expectedSQL = 'SELECT * FROM pages WHERE (uid=1) AND (((pages.deleted = 0) AND (pages.hidden = 0)))';
        $this->connection->expects(self::atLeastOnce())->method('executeQuery')->with($expectedSQL, self::anything(), self::anything(), self::anything())
            ->willReturn($this->createMock(Result::class));

        $subject->executeQuery();
    }

    #[Test]
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

        $connectionBuilder = GeneralUtility::makeInstance(ConcreteQueryBuilder::class, $this->connection);

        $expressionBuilder = GeneralUtility::makeInstance(ExpressionBuilder::class, $this->connection);
        $this->connection->method('getExpressionBuilder')->willReturn($expressionBuilder);

        $subject = GeneralUtility::makeInstance(QueryBuilder::class, $this->connection, null, $connectionBuilder);

        $subject->count('uid')->from('pages')->where('uid=1');

        $expectedSQL = 'SELECT COUNT(uid) FROM pages WHERE (uid=1) AND (((pages.deleted = 0) AND (pages.hidden = 0)))';
        $this->connection->expects(self::atLeastOnce())->method('executeQuery')->with($expectedSQL, self::anything())
            ->willReturn($this->createMock(Result::class));

        $subject->executeQuery();
    }

    #[Test]
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
            ConcreteQueryBuilder::class,
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

    #[Test]
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
            ConcreteQueryBuilder::class,
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

        $series = [
            [$expectedSQLForQuery, $this->createMock(Result::class)],
            [$expectedSQLForResetRestrictions, $this->createMock(Result::class)],
        ];
        $this->connection->expects(self::exactly(2))->method('executeQuery')
            ->willReturnCallback(function (string $sql) use (&$series): Result&MockObject {
                $arguments = array_shift($series);
                self::assertSame($arguments[0], $sql);
                return $arguments[1];
            });

        $subject->executeQuery();
        $subject->resetRestrictions();

        $subject->executeQuery();
    }

    #[Test]
    public function getQueriedTablesReturnsSameTableTwiceForInnerJoin(): void
    {
        $this->connection->method('getDatabasePlatform')->willReturn(new MockPlatform());
        $from = [
            new From(table: 'aTable'),
        ];
        $joins = [
            'aTable' => [
                Join::inner(
                    table: 'aTable',
                    alias: 'aTable_alias',
                    condition: null,
                ),
            ],
        ];

        // Set protected properties of the concrete QueryBuilder
        $setParts = \Closure::bind(function (string $property, array $value) {
            $this->{$property} = $value;
        }, $this->concreteQueryBuilder, ConcreteQueryBuilder::class);
        $setParts->call($this->concreteQueryBuilder, 'from', $from);
        $setParts->call($this->concreteQueryBuilder, 'join', $joins);

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

    public static function unquoteSingleIdentifierUnquotesCorrectlyOnDifferentPlatformsDataProvider(): array
    {
        return [
            'mysql' => [
                'platform' => DoctrineMySQLPlatform::class,
                'quoteChar' => '`',
                'input' => '`anIdentifier`',
                'expected' => 'anIdentifier',
            ],
            'mysql with spaces' => [
                'platform' => DoctrineMySQLPlatform::class,
                'quoteChar' => '`',
                'input' => ' `anIdentifier` ',
                'expected' => 'anIdentifier',
            ],
            'mariadb' => [
                'platform' => DoctrineMariaDBPlatform::class,
                'quoteChar' => '`',
                'input' => '`anIdentifier`',
                'expected' => 'anIdentifier',
            ],
            'mariadb with spaces' => [
                'platform' => DoctrineMariaDBPlatform::class,
                'quoteChar' => '`',
                'input' => ' `anIdentifier` ',
                'expected' => 'anIdentifier',
            ],
            'postgres' => [
                'platform' => DoctrinePostgreSQLPlatform::class,
                'quoteChar' => '"',
                'input' => '"anIdentifier"',
                'expected' => 'anIdentifier',
            ],
            'postgres with spaces ' => [
                'platform' => DoctrinePostgreSQLPlatform::class,
                'quoteChar' => '"',
                'input' => ' "anIdentifier" ',
                'expected' => 'anIdentifier',
            ],
            'sqlite' => [
                'platform' => DoctrineSQLitePlatform::class,
                'quoteChar' => '"',
                'input' => '"anIdentifier"',
                'expected' => 'anIdentifier',
            ],
            'sqlite with spaces' => [
                'platform' => DoctrineSQLitePlatform::class,
                'quoteChar' => '"',
                'input' => ' "anIdentifier" ',
                'expected' => 'anIdentifier',
            ],
        ];
    }

    #[DataProvider('unquoteSingleIdentifierUnquotesCorrectlyOnDifferentPlatformsDataProvider')]
    #[Test]
    public function unquoteSingleIdentifierUnquotesCorrectlyOnDifferentPlatforms(string $platform, string $quoteChar, string $input, string $expected): void
    {
        $connectionMock = $this->createMock(Connection::class);
        $databasePlatformMock = $this->createMock($platform);
        $databasePlatformMock->method('quoteSingleIdentifier')->willReturnCallback(static function (string $str) use ($quoteChar): string {
            return $quoteChar . str_replace($quoteChar, $quoteChar . $quoteChar, $str) . $quoteChar;
        });
        $connectionMock->method('getDatabasePlatform')->willReturn($databasePlatformMock);
        $subject = $this->getAccessibleMock(QueryBuilder::class, null, [$connectionMock]);
        $result = $subject->_call('unquoteSingleIdentifier', $input);
        self::assertEquals($expected, $result);
    }

    #[Test]
    public function cloningQueryBuilderClonesConcreteQueryBuilder(): void
    {
        $clonedQueryBuilder = clone $this->subject;
        self::assertNotSame($this->subject->getConcreteQueryBuilder(), $clonedQueryBuilder->getConcreteQueryBuilder());
    }

    #[Test]
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
            ConcreteQueryBuilder::class,
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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
     * @param mixed $input
     */
    #[DataProvider('createNamedParameterInput')]
    #[Test]
    public function setWithNamedParameterPassesGivenTypeToCreateNamedParameter($input, string|ParameterType|Type|ArrayParameterType $type): void
    {
        $this->connection->method('quoteIdentifier')->with('aField')->willReturnArgument(0);
        $concreteQueryBuilder = new ConcreteQueryBuilder($this->connection);
        $subject = new QueryBuilder($this->connection, null, $concreteQueryBuilder);
        $subject->set('aField', $input, true, $type);
        self::assertSame($type, $concreteQueryBuilder->getParameterType('dcValue1'));
    }

    public static function createNamedParameterInput(): array
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

    public static function castFieldToTextTypeDataProvider(): array
    {
        return [
            'Test cast for MySQLPlatform' => [
                'platform' => new DoctrineMySQLPlatform(),
                'expectation' => 'CONVERT(aField, CHAR)',
            ],
            'Test cast for MariaDBPlatform' => [
                'platform' => new DoctrineMariaDBPlatform(),
                'expectation' => 'CONVERT(aField, CHAR)',
            ],
            'Test cast for PostgreSqlPlatform' => [
                'platform' => new DoctrinePostgreSQLPlatform(),
                'expectation' => 'aField::text',
            ],
            'Test cast for SqlitePlatform' => [
                'platform' => new DoctrineSQLitePlatform(),
                'expectation' => 'CAST(aField as TEXT)',
            ],
        ];
    }

    #[DataProvider('castFieldToTextTypeDataProvider')]
    #[Test]
    public function castFieldToTextType(DoctrineAbstractPlatform $platform, string $expectation): void
    {
        $this->connection->expects(self::atLeastOnce())->method('quoteIdentifier')->with('aField')->willReturnArgument(0);
        $this->connection->method('getDatabasePlatform')->willReturn($platform);
        $concreteQueryBuilder = new ConcreteQueryBuilder($this->connection);
        $subject = new QueryBuilder($this->connection, null, $concreteQueryBuilder);
        $result = $subject->castFieldToTextType('aField');
        self::assertSame($expectation, $result);
    }

    #[Test]
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

        $connectionBuilder = GeneralUtility::makeInstance(ConcreteQueryBuilder::class, $this->connection);

        $expressionBuilder = GeneralUtility::makeInstance(ExpressionBuilder::class, $this->connection);
        $this->connection->method('getExpressionBuilder')->willReturn($expressionBuilder);

        $subject = new QueryBuilder($this->connection, null, $connectionBuilder);
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

        $this->connection->expects(self::atLeastOnce())->method('executeQuery')->with(
            'SELECT * FROM pages LEFT JOIN tt_content content ON pages.uid = content.pid WHERE (uid = 1) AND (((pages.deleted = 0) AND (pages.hidden = 0)))',
            self::anything()
        )->willReturn($this->createMock(Result::class));

        $subject->executeQuery();
    }

    #[Test]
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

        $connectionBuilder = GeneralUtility::makeInstance(ConcreteQueryBuilder::class, $this->connection);

        $expressionBuilder = GeneralUtility::makeInstance(ExpressionBuilder::class, $this->connection);
        $this->connection->method('getExpressionBuilder')->willReturn($expressionBuilder);

        $subject = new QueryBuilder($this->connection, null, $connectionBuilder);
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

        $this->connection->expects(self::atLeastOnce())->method('executeQuery')->with(
            'SELECT * FROM pages LEFT JOIN tt_content content ON pages.uid = content.pid WHERE (uid = 1) AND (pages.hidden = 0)',
            self::anything()
        )->willReturn($this->createMock(Result::class));

        $subject->executeQuery();
    }

    #[Test]
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

        $connectionBuilder = GeneralUtility::makeInstance(ConcreteQueryBuilder::class, $this->connection);

        $expressionBuilder = GeneralUtility::makeInstance(ExpressionBuilder::class, $this->connection);
        $this->connection->method('getExpressionBuilder')->willReturn($expressionBuilder);

        $subject = new QueryBuilder($this->connection, null, $connectionBuilder);
        $subject->select('*')
                ->from('pages')
                ->leftJoin(
                    'pages',
                    'tt_content',
                    'content',
                    'pages.uid = content.pid'
                )
                ->where($expressionBuilder->eq('uid', 1));

        $this->connection->expects(self::atLeastOnce())->method('executeQuery')->with(
            'SELECT * FROM pages LEFT JOIN tt_content content ON ((pages.uid = content.pid) AND (((content.deleted = 0) AND (content.hidden = 0)))) WHERE (uid = 1) AND (((pages.deleted = 0) AND (pages.hidden = 0)))',
            self::anything()
        )->willReturn($this->createMock(Result::class));

        $subject->executeQuery();
    }

    #[Test]
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

        $connectionBuilder = GeneralUtility::makeInstance(ConcreteQueryBuilder::class, $this->connection);

        $expressionBuilder = GeneralUtility::makeInstance(ExpressionBuilder::class, $this->connection);
        $this->connection->method('getExpressionBuilder')->willReturn($expressionBuilder);

        $subject = new QueryBuilder($this->connection, null, $connectionBuilder);
        $subject->select('*')
                ->from('tt_content')
                ->rightJoin(
                    'tt_content',
                    'pages',
                    'pages',
                    'pages.uid = tt_content.pid'
                )
                ->where($expressionBuilder->eq('uid', 1));

        $this->connection->expects(self::atLeastOnce())->method('executeQuery')->with(
            'SELECT * FROM tt_content RIGHT JOIN pages pages ON ((pages.uid = tt_content.pid) AND (((tt_content.deleted = 0) AND (tt_content.hidden = 0)))) WHERE (uid = 1) AND (((pages.deleted = 0) AND (pages.hidden = 0)))',
            self::anything()
        )->willReturn($this->createMock(Result::class));

        $subject->executeQuery();
    }

    #[Test]
    public function unionWithOneUnionPartThrowException(): void
    {
        $this->connection->method('getDatabasePlatform')->willReturn(new MockPlatform());
        $this->connection->method('quoteIdentifier')->willReturnCallback(fn($value) => '`' . $value . '`');
        $queryBuilder = new QueryBuilder($this->connection, null, new ConcreteQueryBuilder($this->connection));
        $queryBuilder->union('SELECT 1 AS field_one');

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage(
            'Insufficient UNION parts give, need at least 2. '
            . 'Please use union() and addUnion() to set enough UNION parts.',
        );

        $queryBuilder->getSQL();
    }

    #[Test]
    public function unionAllReturnsUnionAllQuery(): void
    {
        $this->connection->method('getDatabasePlatform')->willReturn(new MockPlatform());
        $this->connection->method('quoteIdentifier')->willReturnCallback(fn($value) => '`' . $value . '`');
        $queryBuilder = new QueryBuilder($this->connection, null, new ConcreteQueryBuilder($this->connection));
        $queryBuilder
            ->union('SELECT 1 AS field_one')
            ->addUnion('SELECT 2 as field_one', UnionType::ALL);

        self::assertSame('SELECT 1 AS field_one UNION ALL SELECT 2 as field_one', $queryBuilder->getSQL());
    }

    #[Test]
    public function unionAllAndLimitClauseReturnsUnionAllQuery(): void
    {
        $this->connection->method('getDatabasePlatform')->willReturn(new MockPlatform());
        $this->connection->method('quoteIdentifier')->willReturnCallback(fn($value) => '`' . $value . '`');
        $queryBuilder = new QueryBuilder($this->connection, null, new ConcreteQueryBuilder($this->connection));
        $queryBuilder
            ->union('SELECT 1 AS field_one')
            ->addUnion('SELECT 2 as field_one', UnionType::ALL)
            ->setMaxResults(10)
            ->setFirstResult(10);

        self::assertSame('SELECT 1 AS field_one UNION ALL SELECT 2 as field_one LIMIT 10 OFFSET 10', $queryBuilder->getSQL());
    }

    #[Test]
    public function unionDistinctQueryReturnsUnionDistinctQuery(): void
    {
        $this->connection->method('getDatabasePlatform')->willReturn(new MockPlatform());
        $this->connection->method('quoteIdentifier')->willReturnCallback(fn($value) => '`' . $value . '`');
        $qb = new QueryBuilder($this->connection, null, new ConcreteQueryBuilder($this->connection));
        $qb
            ->union('SELECT 1 AS field_one')
            ->addUnion('SELECT 2 as field_one', UnionType::DISTINCT);

        self::assertSame('SELECT 1 AS field_one UNION SELECT 2 as field_one', $qb->getSQL());
    }

    #[Test]
    public function unionAllQueryWithOrderByReturnsUnionAllQueryWithOrderBy(): void
    {
        $this->connection->method('getDatabasePlatform')->willReturn(new MockPlatform());
        $this->connection->method('quoteIdentifier')->willReturnCallback(fn($value) => '`' . $value . '`');
        $queryBuilder = new QueryBuilder($this->connection, null, new ConcreteQueryBuilder($this->connection));
        $queryBuilder
            ->union('SELECT 1 AS field_one')
            ->addUnion('SELECT 2 as field_one', UnionType::ALL)
            ->orderBy('field_one', 'ASC');

        self::assertSame('SELECT 1 AS field_one UNION ALL SELECT 2 as field_one ORDER BY `field_one` ASC', $queryBuilder->getSQL());
    }

    #[Test]
    public function unionDistinctQueryAndOrderByReturnsUnionQueryWithOrderBy(): void
    {
        $this->connection->method('getDatabasePlatform')->willReturn(new MockPlatform());
        $this->connection->method('quoteIdentifier')->willReturnCallback(fn($value) => '`' . $value . '`');
        $queryBuilder = new QueryBuilder($this->connection, null, new ConcreteQueryBuilder($this->connection));
        $queryBuilder
            ->union('SELECT 1 AS field_one')
            ->addUnion('SELECT 2 as field_one', UnionType::DISTINCT)
            ->orderBy('field_one', 'ASC');

        self::assertSame('SELECT 1 AS field_one UNION SELECT 2 as field_one ORDER BY `field_one` ASC', $queryBuilder->getSQL());
    }
}
