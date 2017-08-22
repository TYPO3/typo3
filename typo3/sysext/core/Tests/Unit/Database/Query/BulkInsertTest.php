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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\BulkInsertQuery;
use TYPO3\CMS\Core\Tests\Unit\Database\Mocks\MockPlatform;

class BulkInsertTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @var Connection
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

        $this->connection = $this->createMock(Connection::class);

        $this->connection->expects($this->any())
            ->method('quoteIdentifier')
            ->will($this->returnArgument(0));
        $this->connection->expects($this->any())
            ->method('getDatabasePlatform')
            ->will($this->returnValue(new MockPlatform()));
    }

    /**
     * @test
     */
    public function getSQLWithoutSpecifiedValuesThrowsException()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('You need to add at least one set of values before generating the SQL.');

        $query = new BulkInsertQuery($this->connection, $this->testTable);

        $query->getSQL();
    }

    /**
     * @test
     */
    public function insertWithoutColumnAndTypeSpecification()
    {
        $query = new BulkInsertQuery($this->connection, $this->testTable);

        $query->addValues([]);

        $this->assertSame("INSERT INTO {$this->testTable} VALUES ()", (string)$query);
        $this->assertSame([], $query->getParameters());
        $this->assertSame([], $query->getParameterTypes());
    }

    public function insertWithoutColumnSpecification()
    {
        $query = new BulkInsertQuery($this->connection, $this->testTable);

        $query->addValues([], [Connection::PARAM_BOOL]);

        $this->assertSame("INSERT INTO {$this->testTable} VALUES ()", (string)$query);
        $this->assertSame([], $query->getParameters());
        $this->assertSame([], $query->getParameterTypes());
    }

    /**
     * @test
     */
    public function singleInsertWithoutColumnSpecification()
    {
        $query = new BulkInsertQuery($this->connection, $this->testTable);

        $query->addValues(['bar', 'baz', 'named' => 'bloo']);

        $this->assertSame("INSERT INTO {$this->testTable} VALUES (?, ?, ?)", (string)$query);
        $this->assertSame(['bar', 'baz', 'bloo'], $query->getParameters());
        $this->assertSame([null, null, null], $query->getParameterTypes());

        $query = new BulkInsertQuery($this->connection, $this->testTable);

        $query->addValues(
            ['bar', 'baz', 'named' => 'bloo'],
            ['named' => Connection::PARAM_BOOL, null, Connection::PARAM_INT]
        );

        $this->assertSame("INSERT INTO {$this->testTable} VALUES (?, ?, ?)", (string)$query);
        $this->assertSame(['bar', 'baz', 'bloo'], $query->getParameters());
        $this->assertSame([null, Connection::PARAM_INT, Connection::PARAM_BOOL], $query->getParameterTypes());
    }

    /**
     * @test
     */
    public function multiInsertWithoutColumnSpecification()
    {
        $query = new BulkInsertQuery($this->connection, $this->testTable);

        $query->addValues([]);
        $query->addValues(['bar', 'baz']);
        $query->addValues(['bar', 'baz', 'bloo']);
        $query->addValues(['bar', 'baz', 'named' => 'bloo']);

        $this->assertSame("INSERT INTO {$this->testTable} VALUES (), (?, ?), (?, ?, ?), (?, ?, ?)", (string)$query);
        $this->assertSame(['bar', 'baz', 'bar', 'baz', 'bloo', 'bar', 'baz', 'bloo'], $query->getParameters());
        $this->assertSame([null, null, null, null, null, null, null, null], $query->getParameterTypes());

        $query = new BulkInsertQuery($this->connection, $this->testTable);

        $query->addValues([], [Connection::PARAM_INT]);
        $query->addValues(['bar', 'baz'], [1 => Connection::PARAM_BOOL]);
        $query->addValues(['bar', 'baz', 'bloo'], [Connection::PARAM_INT, null, Connection::PARAM_BOOL]);
        $query->addValues(
            ['bar', 'baz', 'named' => 'bloo'],
            ['named' => Connection::PARAM_INT, null, Connection::PARAM_BOOL]
        );

        $this->assertSame("INSERT INTO {$this->testTable} VALUES (), (?, ?), (?, ?, ?), (?, ?, ?)", (string)$query);
        $this->assertSame(['bar', 'baz', 'bar', 'baz', 'bloo', 'bar', 'baz', 'bloo'], $query->getParameters());
        $this->assertSame(
            [
                null,
                Connection::PARAM_BOOL,
                Connection::PARAM_INT,
                null,
                Connection::PARAM_BOOL,
                null,
                Connection::PARAM_BOOL,
                Connection::PARAM_INT
            ],
            $query->getParameterTypes()
        );
    }

    /**
     * @test
     */
    public function singleInsertWithColumnSpecificationAndPositionalTypeValues()
    {
        $query = new BulkInsertQuery($this->connection, $this->testTable, ['bar', 'baz']);

        $query->addValues(['bar', 'baz']);

        $this->assertSame("INSERT INTO {$this->testTable} (bar, baz) VALUES (?, ?)", (string)$query);
        $this->assertSame(['bar', 'baz'], $query->getParameters());
        $this->assertSame([null, null], $query->getParameterTypes());

        $query = new BulkInsertQuery($this->connection, $this->testTable, ['bar', 'baz']);

        $query->addValues(['bar', 'baz'], [1 => Connection::PARAM_BOOL]);

        $this->assertSame("INSERT INTO {$this->testTable} (bar, baz) VALUES (?, ?)", (string)$query);
        $this->assertSame(['bar', 'baz'], $query->getParameters());
        $this->assertSame([null, Connection::PARAM_BOOL], $query->getParameterTypes());
    }

    /**
     * @test
     */
    public function singleInsertWithColumnSpecificationAndNamedTypeValues()
    {
        $query = new BulkInsertQuery($this->connection, $this->testTable, ['bar', 'baz']);

        $query->addValues(['baz' => 'baz', 'bar' => 'bar']);

        $this->assertSame("INSERT INTO {$this->testTable} (bar, baz) VALUES (?, ?)", (string)$query);
        $this->assertSame(['bar', 'baz'], $query->getParameters());
        $this->assertSame([null, null], $query->getParameterTypes());

        $query = new BulkInsertQuery($this->connection, $this->testTable, ['bar', 'baz']);

        $query->addValues(['baz' => 'baz', 'bar' => 'bar'], [null, Connection::PARAM_INT]);

        $this->assertSame("INSERT INTO {$this->testTable} (bar, baz) VALUES (?, ?)", (string)$query);
        $this->assertSame(['bar', 'baz'], $query->getParameters());
        $this->assertSame([null, Connection::PARAM_INT], $query->getParameterTypes());
    }

    /**
     * @test
     */
    public function singleInsertWithColumnSpecificationAndMixedTypeValues()
    {
        $query = new BulkInsertQuery($this->connection, $this->testTable, ['bar', 'baz']);

        $query->addValues([1 => 'baz', 'bar' => 'bar']);

        $this->assertSame("INSERT INTO {$this->testTable} (bar, baz) VALUES (?, ?)", (string)$query);
        $this->assertSame(['bar', 'baz'], $query->getParameters());
        $this->assertSame([null, null], $query->getParameterTypes());

        $query = new BulkInsertQuery($this->connection, $this->testTable, ['bar', 'baz']);

        $query->addValues([1 => 'baz', 'bar' => 'bar'], [Connection::PARAM_INT, Connection::PARAM_BOOL]);

        $this->assertSame("INSERT INTO {$this->testTable} (bar, baz) VALUES (?, ?)", (string)$query);
        $this->assertSame(['bar', 'baz'], $query->getParameters());
        $this->assertSame([Connection::PARAM_INT, Connection::PARAM_BOOL], $query->getParameterTypes());
    }

    /**
     * @test
     */
    public function multiInsertWithColumnSpecification()
    {
        $query = new BulkInsertQuery($this->connection, $this->testTable, ['bar', 'baz']);

        $query->addValues(['bar', 'baz']);
        $query->addValues([1 => 'baz', 'bar' => 'bar']);
        $query->addValues(['bar', 'baz' => 'baz']);
        $query->addValues(['bar' => 'bar', 'baz' => 'baz']);

        $this->assertSame(
            "INSERT INTO {$this->testTable} (bar, baz) VALUES (?, ?), (?, ?), (?, ?), (?, ?)",
            (string)$query
        );
        $this->assertSame(['bar', 'baz', 'bar', 'baz', 'bar', 'baz', 'bar', 'baz'], $query->getParameters());
        $this->assertSame([null, null, null, null, null, null, null, null], $query->getParameterTypes());

        $query = new BulkInsertQuery($this->connection, $this->testTable, ['bar', 'baz']);

        $query->addValues(['bar', 'baz'], ['baz' => Connection::PARAM_BOOL, 'bar' => Connection::PARAM_INT]);
        $query->addValues([1 => 'baz', 'bar' => 'bar'], [1 => Connection::PARAM_BOOL, 'bar' => Connection::PARAM_INT]);
        $query->addValues(['bar', 'baz' => 'baz'], [null, null]);
        $query->addValues(
            ['bar' => 'bar', 'baz' => 'baz'],
            ['bar' => Connection::PARAM_INT, 'baz' => Connection::PARAM_BOOL]
        );

        $this->assertSame(
            "INSERT INTO {$this->testTable} (bar, baz) VALUES (?, ?), (?, ?), (?, ?), (?, ?)",
            (string)$query
        );
        $this->assertSame(['bar', 'baz', 'bar', 'baz', 'bar', 'baz', 'bar', 'baz'], $query->getParameters());
        $this->assertSame(
            [
                Connection::PARAM_INT,
                Connection::PARAM_BOOL,
                Connection::PARAM_INT,
                Connection::PARAM_BOOL,
                null,
                null,
                Connection::PARAM_INT,
                Connection::PARAM_BOOL,
            ],
            $query->getParameterTypes()
        );
    }

    /**
     * @test
     */
    public function emptyInsertWithColumnSpecificationThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No value specified for column bar (index 0).');

        $query = new BulkInsertQuery($this->connection, $this->testTable, ['bar', 'baz']);
        $query->addValues([]);
    }

    /**
     * @test
     */
    public function insertWithColumnSpecificationAndMultipleValuesForColumnThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Multiple values specified for column baz (index 1).');

        $query = new BulkInsertQuery($this->connection, $this->testTable, ['bar', 'baz']);
        $query->addValues(['bar', 'baz', 'baz' => 666]);
    }

    /**
     * @test
     */
    public function insertWithColumnSpecificationAndMultipleTypesForColumnThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Multiple types specified for column baz (index 1).');

        $query = new BulkInsertQuery($this->connection, $this->testTable, ['bar', 'baz']);
        $query->addValues(
            ['bar', 'baz'],
            [Connection::PARAM_INT, Connection::PARAM_INT, 'baz' => Connection::PARAM_STR]
        );
    }

    /**
     * @test
     */
    public function executeWithMaxInsertRowsPerStatementExceededThrowsException()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('You can only insert 10 rows in a single INSERT statement with platform "mock".');

        /** @var \PHPUnit_Framework_MockObject_MockObject|BulkInsertQuery $subject */
        $subject = $this->getAccessibleMock(
            BulkInsertQuery::class,
            ['getInsertMaxRows'],
            [$this->connection, $this->testTable],
            ''
        );

        $subject->expects($this->any())
            ->method('getInsertMaxRows')
            ->will($this->returnValue(10));

        for ($i = 0; $i <= 10; $i++) {
            $subject->addValues([]);
        }

        $subject->execute();
    }
}
