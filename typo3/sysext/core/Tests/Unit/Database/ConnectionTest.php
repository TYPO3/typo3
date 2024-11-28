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
use Doctrine\DBAL\Result;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Tests\Unit\Database\Mocks\MockPlatform\MockPlatform;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ConnectionTest extends UnitTestCase
{
    protected Connection&MockObject $connection;

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
                    'ensureDatabaseValueTypes',
                    'executeQuery',
                    'executeStatement',
                    'getDatabasePlatform',
                    'getDriver',
                    'getExpressionBuilder',
                    'getNativeConnection',
                    'getServerVersion',
                ]
            )
            ->setConstructorArgs([[], $this->createMock(AbstractMySQLDriver::class), new Configuration(), null])
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

    #[Test]
    #[DoesNotPerformAssertions]
    public function createQueryBuilderWorks(): void
    {
        $this->connection->createQueryBuilder();
    }

    public static function quoteIdentifierDataProvider(): array
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

    #[DataProvider('quoteIdentifierDataProvider')]
    #[Test]
    public function quoteIdentifier(string $input, string $expected): void
    {
        self::assertSame($expected, $this->connection->quoteIdentifier($input));
    }

    #[Test]
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

    public static function insertQueriesDataProvider(): array
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

    #[DataProvider('insertQueriesDataProvider')]
    #[Test]
    public function insertQueries(array $args, string $expectedQuery, array $expectedValues, array $expectedTypes): void
    {
        $this->connection->expects(self::once())
            ->method('executeStatement')
            ->with($expectedQuery, $expectedValues, $expectedTypes)
            ->willReturn(1);

        $this->connection->insert(...$args);
    }

    #[Test]
    public function bulkInsert(): void
    {
        $this->connection->expects(self::once())
            ->method('executeStatement')
            ->with('INSERT INTO "aTestTable" ("aField") VALUES (?), (?)', ['aValue', 'anotherValue'])
            ->willReturn(2);

        $this->connection->bulkInsert('aTestTable', [['aField' => 'aValue'], ['aField' => 'anotherValue']], ['aField']);
    }

    public static function updateQueriesDataProvider(): array
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

    #[DataProvider('updateQueriesDataProvider')]
    #[Test]
    public function updateQueries(array $args, string $expectedQuery, array $expectedValues, array $expectedTypes): void
    {
        $this->connection->expects(self::once())
            ->method('executeStatement')
            ->with($expectedQuery, $expectedValues, $expectedTypes)
            ->willReturn(1);
        $this->connection->update(...$args);
    }

    public static function deleteQueriesDataProvider(): array
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

    #[DataProvider('deleteQueriesDataProvider')]
    #[Test]
    public function deleteQueries(array $args, string $expectedQuery, array $expectedValues, array $expectedTypes): void
    {
        $this->connection->expects(self::once())
            ->method('executeStatement')
            ->with($expectedQuery, $expectedValues, $expectedTypes)
            ->willReturn(1);
        $this->connection->delete(...$args);
    }

    /**
     * Data provider for select query tests
     *
     * Each array item consists of
     *  - array of parameters for select call
     *  - expected SQL string
     *  - expected named parameter values
     */
    public static function selectQueriesDataProvider(): array
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

    #[DataProvider('selectQueriesDataProvider')]
    #[Test]
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
     */
    public static function countQueriesDataProvider(): array
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

    #[DataProvider('countQueriesDataProvider')]
    #[Test]
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

    #[Test]
    public function truncateQuery(): void
    {
        $this->connection->expects(self::once())
            ->method('executeStatement')
            ->with('TRUNCATE "aTestTable"')
            ->willReturn(0);

        $this->connection->truncate('aTestTable', false);
    }

    #[Test]
    public function getServerVersionReportsServerVersionOnly(): void
    {
        $this->connection
            ->method('getServerVersion')
            ->willReturn('5.7.11');

        self::assertSame('5.7.11', $this->connection->getServerVersion());
    }

    #[Test]
    public function getPlatformServerVersionReportsPlatformVersion(): void
    {
        $this->connection
            ->method('getServerVersion')
            ->willReturn('5.7.11');

        self::assertSame('Mock 5.7.11', $this->connection->getPlatformServerVersion());
    }
}
