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

namespace TYPO3\CMS\Core\Tests\Unit\Database;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Driver\AbstractMySQLDriver;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Result;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Tests\Unit\Database\Mocks\MockPlatform;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ConnectionTest extends UnitTestCase
{
    /**
     * @var Connection|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $connection;

    protected ?AbstractPlatform $platform;
    protected string $testTable = 'testTable';

    /**
     * Create a new database connection mock object for every test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->getMockBuilder(Connection::class)
            ->onlyMethods(
                [
                    'connect',
                    'executeQuery',
                    'executeUpdate',
                    'executeStatement',
                    'getDatabasePlatform',
                    'getDriver',
                    'getExpressionBuilder',
                    'getWrappedConnection',
                ]
            )
            ->setConstructorArgs([['platform' => $this->createMock(MySQLPlatform::class)], $this->createMock(AbstractMySQLDriver::class), new Configuration(), null])
            ->getMock();

        $this->connection
            ->method('getExpressionBuilder')
            ->willReturn(GeneralUtility::makeInstance(ExpressionBuilder::class, $this->connection));

        $this->connection
            ->method('connect');

        $this->connection
            ->method('getDatabasePlatform')
            ->willReturn(new MockPlatform());
    }

    /**
     * @test
     */
    public function createQueryBuilderReturnsInstanceOfTypo3QueryBuilder(): void
    {
        self::assertInstanceOf(QueryBuilder::class, $this->connection->createQueryBuilder());
    }

    /**
     * @return array
     */
    public function quoteIdentifierDataProvider(): array
    {
        return [
            'SQL star' => [
                '*',
                '*',
            ],
            'fieldname' => [
                'aField',
                '"aField"',
            ],
            'whitespace' => [
                'with blanks',
                '"with blanks"',
            ],
            'double quotes' => [
                '"double" quotes',
                '"""double"" quotes"',
            ],
            'single quotes' => [
                "'single'",
                '"\'single\'"',

            ],
            'multiple double quotes' => [
                '""multiple""',
                '"""""multiple"""""',
            ],
            'multiple single quotes' => [
                "''multiple''",
                '"\'\'multiple\'\'"',
            ],
            'backticks' => [
                '`backticks`',
                '"`backticks`"',
            ],
            'slashes' => [
                '/slashes/',
                '"/slashes/"',
            ],
            'backslashes' => [
                '\\backslashes\\',
                '"\\backslashes\\"',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider quoteIdentifierDataProvider
     */
    public function quoteIdentifier(string $input, string $expected): void
    {
        self::assertSame($expected, $this->connection->quoteIdentifier($input));
    }

    /**
     * @test
     */
    public function quoteIdentifiers(): void
    {
        $input = [
            'aField',
            'anotherField',
        ];

        $expected = [
            '"aField"',
            '"anotherField"',
        ];

        self::assertSame($expected, $this->connection->quoteIdentifiers($input));
    }

    /**
     * @return array
     */
    public function insertQueriesDataProvider(): array
    {
        return [
            'single value' => [
                ['aTestTable', ['aField' => 'aValue']],
                'INSERT INTO "aTestTable" ("aField") VALUES (?)',
                ['aValue'],
                [],
            ],
            'multiple values' => [
                ['aTestTable', ['aField' => 'aValue', 'bField' => 'bValue']],
                'INSERT INTO "aTestTable" ("aField", "bField") VALUES (?, ?)',
                ['aValue', 'bValue'],
                [],
            ],
            'with types' => [
                ['aTestTable', ['aField' => 'aValue', 'bField' => 'bValue'], [Connection::PARAM_STR, Connection::PARAM_STR]],
                'INSERT INTO "aTestTable" ("aField", "bField") VALUES (?, ?)',
                ['aValue', 'bValue'],
                [Connection::PARAM_STR, Connection::PARAM_STR],
            ],
            'with types for field' => [
                [
                    'aTestTable',
                    ['aField' => 123, 'bField' => 'bValue'],
                    ['aField' => Connection::PARAM_INT, 'bField' => Connection::PARAM_LOB],
                ],
                'INSERT INTO "aTestTable" ("aField", "bField") VALUES (?, ?)',
                [123, 'bValue'],
                [Connection::PARAM_INT, Connection::PARAM_LOB],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider insertQueriesDataProvider
     */
    public function insertQueries(array $args, string $expectedQuery, array $expectedValues, array $expectedTypes): void
    {
        // @todo drop else branch and condition once doctrine/dbal is requried in version 2.11.0 minimum
        if (method_exists(Connection::class, 'executeStatement')) {
            $this->connection->expects(self::once())
                ->method('executeStatement')
                ->with($expectedQuery, $expectedValues, $expectedTypes)
                ->willReturn(1);
        } else {
            $this->connection->expects(self::once())
                ->method('executeUpdate')
                ->with($expectedQuery, $expectedValues, $expectedTypes)
                ->willReturn(1);
        }

        $this->connection->insert(...$args);
    }

    /**
     * @test
     */
    public function bulkInsert(): void
    {
        $this->connection->expects(self::once())
            ->method('executeStatement')
            ->with('INSERT INTO "aTestTable" ("aField") VALUES (?), (?)', ['aValue', 'anotherValue'])
            ->willReturn(2);

        $this->connection->bulkInsert('aTestTable', [['aField' => 'aValue'], ['aField' => 'anotherValue']], ['aField']);
    }

    /**
     * @return array
     */
    public function updateQueriesDataProvider(): array
    {
        return [
            'single value' => [
                ['aTestTable', ['aField' => 'aValue'], ['uid' => 1]],
                'UPDATE "aTestTable" SET "aField" = ? WHERE "uid" = ?',
                ['aValue', 1],
                [],
            ],
            'multiple values' => [
                ['aTestTable', ['aField' => 'aValue', 'bField' => 'bValue'], ['uid' => 1]],
                'UPDATE "aTestTable" SET "aField" = ?, "bField" = ? WHERE "uid" = ?',
                ['aValue', 'bValue', 1],
                [],
            ],
            'with types' => [
                ['aTestTable', ['aField' => 'aValue'], ['uid' => 1], [Connection::PARAM_STR]],
                'UPDATE "aTestTable" SET "aField" = ? WHERE "uid" = ?',
                ['aValue', 1],
                [Connection::PARAM_STR],
            ],
            'with types for field' => [
                ['aTestTable', ['aField' => 'aValue'], ['uid' => 1], ['aField' => Connection::PARAM_LOB]],
                'UPDATE "aTestTable" SET "aField" = ? WHERE "uid" = ?',
                ['aValue', 1],
                [0 => Connection::PARAM_LOB, 1 => Connection::PARAM_STR],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider updateQueriesDataProvider
     */
    public function updateQueries(array $args, string $expectedQuery, array $expectedValues, array $expectedTypes): void
    {
        // @todo drop else branch and condition once doctrine/dbal is requried in version 2.11.0 minimum
        if (method_exists(Connection::class, 'executeStatement')) {
            $this->connection->expects(self::once())
                ->method('executeStatement')
                ->with($expectedQuery, $expectedValues, $expectedTypes)
                ->willReturn(1);
        } else {
            $this->connection->expects(self::once())
                ->method('executeUpdate')
                ->with($expectedQuery, $expectedValues, $expectedTypes)
                ->willReturn(1);
        }

        $this->connection->update(...$args);
    }

    /**
     * @return array
     */
    public function deleteQueriesDataProvider(): array
    {
        return [
            'single condition' => [
                ['aTestTable', ['aField' => 'aValue']],
                'DELETE FROM "aTestTable" WHERE "aField" = ?',
                ['aValue'],
                [],
            ],
            'multiple conditions' => [
                ['aTestTable', ['aField' => 'aValue', 'bField' => 'bValue']],
                'DELETE FROM "aTestTable" WHERE "aField" = ? AND "bField" = ?',
                ['aValue', 'bValue'],
                [],
            ],
            'with types' => [
                ['aTestTable', ['aField' => 'aValue'], [Connection::PARAM_STR]],
                'DELETE FROM "aTestTable" WHERE "aField" = ?',
                ['aValue'],
                [Connection::PARAM_STR],
            ],
            'with types for field' => [
                ['aTestTable', ['aField' => 'aValue'], ['aField' => Connection::PARAM_STR]],
                'DELETE FROM "aTestTable" WHERE "aField" = ?',
                ['aValue'],
                [Connection::PARAM_STR],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider deleteQueriesDataProvider
     */
    public function deleteQueries(array $args, string $expectedQuery, array $expectedValues, array $expectedTypes): void
    {
        // @todo drop else branch and condition once doctrine/dbal is requried in version 2.11.0 minimum
        if (method_exists(Connection::class, 'executeStatement')) {
            $this->connection->expects(self::once())
                ->method('executeStatement')
                ->with($expectedQuery, $expectedValues, $expectedTypes)
                ->willReturn(1);
        } else {
            $this->connection->expects(self::once())
                ->method('executeUpdate')
                ->with($expectedQuery, $expectedValues, $expectedTypes)
                ->willReturn(1);
        }

        $this->connection->delete(...$args);
    }

    /**
     * Data provider for select query tests
     *
     * Each array item consists of
     *  - array of parameters for select call
     *  - expected SQL string
     *  - expected named parameter values
     *
     * @return array
     */
    public function selectQueriesDataProvider(): array
    {
        return [
            'all columns' => [
                [['*'], 'aTable'],
                'SELECT * FROM "aTable"',
                [],
            ],
            'subset of columns' => [
                [['aField', 'anotherField'], 'aTable'],
                'SELECT "aField", "anotherField" FROM "aTable"',
                [],
            ],
            'conditions' => [
                [['*'], 'aTable', ['aField' => 'aValue']],
                'SELECT * FROM "aTable" WHERE "aField" = :dcValue1',
                ['dcValue1' => 'aValue'],
            ],
            'grouping' => [
                [['*'], 'aTable', [], ['aField']],
                'SELECT * FROM "aTable" GROUP BY "aField"',
                [],
            ],
            'ordering' => [
                [['*'], 'aTable', [], [], ['aField' => 'ASC']],
                'SELECT * FROM "aTable" ORDER BY "aField" ASC',
                [],
            ],
            'limit' => [
                [['*'], 'aTable', [], [], [], 1],
                'SELECT * FROM "aTable" LIMIT 1',
                [],
            ],
            'offset' => [
                [['*'], 'aTable', [], [], [], 1, 10],
                'SELECT * FROM "aTable" LIMIT 1 OFFSET 10',
                [],
            ],
            'everything' => [
                [
                    ['aField', 'anotherField'],
                    'aTable',
                    ['aField' => 'aValue'],
                    ['anotherField'],
                    ['aField' => 'ASC'],
                    1,
                    10,
                ],
                'SELECT "aField", "anotherField" FROM "aTable" WHERE "aField" = :dcValue1 ' .
                'GROUP BY "anotherField" ORDER BY "aField" ASC LIMIT 1 OFFSET 10',
                ['dcValue1' => 'aValue'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider selectQueriesDataProvider
     */
    public function selectQueries(array $args, string $expectedQuery, array $expectedParameters): void
    {
        $resultStatement = $this->createMock(Result::class);

        $this->connection->expects(self::once())
            ->method('executeQuery')
            ->with($expectedQuery, $expectedParameters)
            ->willReturn($resultStatement);

        $this->connection->select(...$args);
    }

    /**
     * Data provider for select query tests
     *
     * Each array item consists of
     *  - array of parameters for select call
     *  - expected SQL string
     *  - expected named parameter values
     *
     * @return array
     */
    public function countQueriesDataProvider(): array
    {
        return [
            'all columns' => [
                ['*', 'aTable', []],
                'SELECT COUNT(*) FROM "aTable"',
                [],
            ],
            'specified columns' => [
                ['aField', 'aTable', []],
                'SELECT COUNT("aField") FROM "aTable"',
                [],
            ],
            'conditions' => [
                ['aTable.aField', 'aTable', ['aField' => 'aValue']],
                'SELECT COUNT("aTable"."aField") FROM "aTable" WHERE "aField" = :dcValue1',
                ['dcValue1' => 'aValue'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider countQueriesDataProvider
     */
    public function countQueries(array $args, string $expectedQuery, array $expectedParameters): void
    {
        $resultStatement = $this->createMock(Result::class);

        $resultStatement->expects(self::once())
            ->method('fetchOne')
            ->willReturn(false);
        $this->connection->expects(self::once())
            ->method('executeQuery')
            ->with($expectedQuery, $expectedParameters)
            ->willReturn($resultStatement);
        $this->connection->count(...$args);
    }

    /**
     * @test
     */
    public function truncateQuery(): void
    {
        $this->connection->expects(self::once())
            ->method('executeStatement')
            ->with('TRUNCATE "aTestTable"')
            ->willReturn(0);

        $this->connection->truncate('aTestTable', false);
    }

    /**
     * @test
     */
    public function getServerVersionReportsPlatformVersion(): void
    {
        $wrappedConnectionMock = $this->createMock(Connection::class);
        $wrappedConnectionMock->method('getServerVersion')->willReturn('5.7.11');

        $this->connection
            ->method('getWrappedConnection')
            ->willReturn($wrappedConnectionMock);

        self::assertSame('mock 5.7.11', $this->connection->getServerVersion());
    }
}
