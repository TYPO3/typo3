<?php
declare(strict_types=1);
namespace TYPO3\CMS\Core\Tests\Unit\Database;

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

use Doctrine\DBAL\Driver\Mysqli\MysqliConnection;
use Doctrine\DBAL\Statement;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Tests\Unit\Database\Mocks\MockPlatform;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case
 */
class ConnectionTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @var Connection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $connection;

    /**
     * @var \Doctrine\DBAL\Platforms\AbstractPlatform
     */
    protected $platform;

    /**
     * @var string
     */
    protected $testTable = 'testTable';

    /**
     * Create a new database connection mock object for every test.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'connect',
                    'executeQuery',
                    'executeUpdate',
                    'getDatabasePlatform',
                    'getDriver',
                    'getExpressionBuilder',
                    'getWrappedConnection',
                ]
            )
            ->getMock();

        $this->connection->expects($this->any())
            ->method('getExpressionBuilder')
            ->will($this->returnValue(GeneralUtility::makeInstance(ExpressionBuilder::class, $this->connection)));

        $this->connection->expects($this->any())
            ->method('connect');

        $this->connection->expects($this->any())
            ->method('getDatabasePlatform')
            ->will($this->returnValue(new MockPlatform()));
    }

    /**
     * @test
     */
    public function createQueryBuilderReturnsInstanceOfTypo3QueryBuilder()
    {
        $this->assertInstanceOf(QueryBuilder::class, $this->connection->createQueryBuilder());
    }

    /**
     * @return array
     */
    public function quoteIdentifierDataProvider()
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
     * @param string $input
     * @param string $expected
     */
    public function quoteIdentifier(string $input, string $expected)
    {
        $this->assertSame($expected, $this->connection->quoteIdentifier($input));
    }

    /**
     * @test
     */
    public function quoteIdentifiers()
    {
        $input = [
            'aField',
            'anotherField',
        ];

        $expected = [
            '"aField"',
            '"anotherField"',
        ];

        $this->assertSame($expected, $this->connection->quoteIdentifiers($input));
    }

    /**
     * @return array
     */
    public function insertQueriesDataProvider()
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
                    ['aField' => Connection::PARAM_INT, 'bField' => Connection::PARAM_LOB]
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
     * @param array $args
     * @param string $expectedQuery
     * @param array $expectedValues
     * @param array $expectedTypes
     */
    public function insertQueries(array $args, string $expectedQuery, array $expectedValues, array $expectedTypes)
    {
        $this->connection->expects($this->once())
            ->method('executeUpdate')
            ->with($expectedQuery, $expectedValues, $expectedTypes)
            ->will($this->returnValue(1));

        $this->connection->insert(...$args);
    }

    /**
     * @test
     */
    public function bulkInsert()
    {
        $this->connection->expects($this->once())
            ->method('executeUpdate')
            ->with('INSERT INTO "aTestTable" ("aField") VALUES (?), (?)', ['aValue', 'anotherValue'])
            ->will($this->returnValue(2));

        $this->connection->bulkInsert('aTestTable', [['aField' => 'aValue'], ['aField' => 'anotherValue']], ['aField']);
    }

    /**
     * @return array
     */
    public function updateQueriesDataProvider()
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
     * @param array $args
     * @param string $expectedQuery
     * @param array $expectedValues
     * @param array $expectedTypes
     */
    public function updateQueries(array $args, string $expectedQuery, array $expectedValues, array $expectedTypes)
    {
        $this->connection->expects($this->once())
            ->method('executeUpdate')
            ->with($expectedQuery, $expectedValues, $expectedTypes)
            ->will($this->returnValue(1));

        $this->connection->update(...$args);
    }

    /**
     * @return array
     */
    public function deleteQueriesDataProvider()
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
     * @param array $args
     * @param string $expectedQuery
     * @param array $expectedValues
     * @param array $expectedTypes
     */
    public function deleteQueries(array $args, string $expectedQuery, array $expectedValues, array $expectedTypes)
    {
        $this->connection->expects($this->once())
            ->method('executeUpdate')
            ->with($expectedQuery, $expectedValues, $expectedTypes)
            ->will($this->returnValue(1));

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
    public function selectQueriesDataProvider()
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
                'SELECT * FROM "aTable" LIMIT 1 OFFSET 0',
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
     * @param array $args
     * @param string $expectedQuery
     * @param array $expectedParameters
     */
    public function selectQueries(array $args, string $expectedQuery, array $expectedParameters)
    {
        $resultStatement = $this->createMock(Statement::class);

        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with($expectedQuery, $expectedParameters)
            ->will($this->returnValue($resultStatement));

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
    public function countQueriesDataProvider()
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
     * @param array $args
     * @param string $expectedQuery
     * @param array $expectedParameters
     */
    public function countQueries(array $args, string $expectedQuery, array $expectedParameters)
    {
        $resultStatement = $this->createMock(Statement::class);

        $resultStatement->expects($this->once())
            ->method('fetchColumn')
            ->with(0)
            ->will($this->returnValue(0));

        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with($expectedQuery, $expectedParameters)
            ->will($this->returnValue($resultStatement));

        $this->connection->count(...$args);
    }

    /**
     * @test
     */
    public function truncateQuery()
    {
        $this->connection->expects($this->once())
            ->method('executeUpdate')
            ->with('TRUNCATE "aTestTable"')
            ->will($this->returnValue(0));

        $this->connection->truncate('aTestTable', false);
    }

    /**
     * @test
     */
    public function getServerVersionReportsPlatformVersion()
    {
        /** @var MysqliConnection|ObjectProphecy $driverProphet */
        $driverProphet = $this->prophesize(\Doctrine\DBAL\Driver\Mysqli\Driver::class);
        $driverProphet->willImplement(\Doctrine\DBAL\VersionAwarePlatformDriver::class);

        /** @var MysqliConnection|ObjectProphecy $wrappedConnectionProphet */
        $wrappedConnectionProphet = $this->prophesize(\Doctrine\DBAL\Driver\Mysqli\MysqliConnection::class);
        $wrappedConnectionProphet->willImplement(\Doctrine\DBAL\Driver\ServerInfoAwareConnection::class);
        $wrappedConnectionProphet->requiresQueryForServerVersion()->willReturn(false);
        $wrappedConnectionProphet->getServerVersion()->willReturn('5.7.11');

        $this->connection->expects($this->any())
            ->method('getDriver')
            ->willReturn($driverProphet->reveal());
        $this->connection->expects($this->any())
            ->method('getWrappedConnection')
            ->willReturn($wrappedConnectionProphet->reveal());

        $this->assertSame('mock 5.7.11', $this->connection->getServerVersion());
    }
}
