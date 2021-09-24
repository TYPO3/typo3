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
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\BulkInsertQuery;
use TYPO3\CMS\Core\Tests\Unit\Database\Mocks\MockPlatform;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class BulkInsertTest extends UnitTestCase
{
    /**
     * @var Connection
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

        $this->connection = $this->createMock(Connection::class);

        $this->connection->expects(self::any())
            ->method('quoteIdentifier')
            ->willReturnArgument(0);
        $this->connection->expects(self::any())
            ->method('getDatabasePlatform')
            ->willReturn(new MockPlatform());
    }

    /**
     * @test
     */
    public function getSQLWithoutSpecifiedValuesThrowsException(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('You need to add at least one set of values before generating the SQL.');

        $query = new BulkInsertQuery($this->connection, $this->testTable);

        $query->getSQL();
    }

    /**
     * @test
     */
    public function insertWithoutColumnAndTypeSpecification(): void
    {
        $query = new BulkInsertQuery($this->connection, $this->testTable);

        $query->addValues([]);

        self::assertSame("INSERT INTO {$this->testTable} VALUES ()", (string)$query);
        self::assertSame([], $query->getParameters());
        self::assertSame([], $query->getParameterTypes());
    }

    public function insertWithoutColumnSpecification(): void
    {
        $query = new BulkInsertQuery($this->connection, $this->testTable);

        $query->addValues([], [Connection::PARAM_BOOL]);

        self::assertSame("INSERT INTO {$this->testTable} VALUES ()", (string)$query);
        self::assertSame([], $query->getParameters());
        self::assertSame([], $query->getParameterTypes());
    }

    /**
     * @test
     */
    public function singleInsertWithoutColumnSpecification(): void
    {
        $query = new BulkInsertQuery($this->connection, $this->testTable);

        $query->addValues(['bar', 'baz', 'named' => 'bloo']);

        self::assertSame("INSERT INTO {$this->testTable} VALUES (?, ?, ?)", (string)$query);
        self::assertSame(['bar', 'baz', 'bloo'], $query->getParameters());
        self::assertSame([null, null, null], $query->getParameterTypes());

        $query = new BulkInsertQuery($this->connection, $this->testTable);

        $query->addValues(
            ['bar', 'baz', 'named' => 'bloo'],
            ['named' => Connection::PARAM_BOOL, null, Connection::PARAM_INT]
        );

        self::assertSame("INSERT INTO {$this->testTable} VALUES (?, ?, ?)", (string)$query);
        self::assertSame(['bar', 'baz', 'bloo'], $query->getParameters());
        self::assertSame([null, Connection::PARAM_INT, Connection::PARAM_BOOL], $query->getParameterTypes());
    }

    /**
     * @test
     */
    public function multiInsertWithoutColumnSpecification(): void
    {
        $query = new BulkInsertQuery($this->connection, $this->testTable);

        $query->addValues([]);
        $query->addValues(['bar', 'baz']);
        $query->addValues(['bar', 'baz', 'bloo']);
        $query->addValues(['bar', 'baz', 'named' => 'bloo']);

        self::assertSame("INSERT INTO {$this->testTable} VALUES (), (?, ?), (?, ?, ?), (?, ?, ?)", (string)$query);
        self::assertSame(['bar', 'baz', 'bar', 'baz', 'bloo', 'bar', 'baz', 'bloo'], $query->getParameters());
        self::assertSame([null, null, null, null, null, null, null, null], $query->getParameterTypes());

        $query = new BulkInsertQuery($this->connection, $this->testTable);

        $query->addValues([], [Connection::PARAM_INT]);
        $query->addValues(['bar', 'baz'], [1 => Connection::PARAM_BOOL]);
        $query->addValues(['bar', 'baz', 'bloo'], [Connection::PARAM_INT, null, Connection::PARAM_BOOL]);
        $query->addValues(
            ['bar', 'baz', 'named' => 'bloo'],
            ['named' => Connection::PARAM_INT, null, Connection::PARAM_BOOL]
        );

        self::assertSame("INSERT INTO {$this->testTable} VALUES (), (?, ?), (?, ?, ?), (?, ?, ?)", (string)$query);
        self::assertSame(['bar', 'baz', 'bar', 'baz', 'bloo', 'bar', 'baz', 'bloo'], $query->getParameters());
        self::assertSame(
            [
                null,
                Connection::PARAM_BOOL,
                Connection::PARAM_INT,
                null,
                Connection::PARAM_BOOL,
                null,
                Connection::PARAM_BOOL,
                Connection::PARAM_INT,
            ],
            $query->getParameterTypes()
        );
    }

    /**
     * @test
     */
    public function singleInsertWithColumnSpecificationAndPositionalTypeValues(): void
    {
        $query = new BulkInsertQuery($this->connection, $this->testTable, ['bar', 'baz']);

        $query->addValues(['bar', 'baz']);

        self::assertSame("INSERT INTO {$this->testTable} (bar, baz) VALUES (?, ?)", (string)$query);
        self::assertSame(['bar', 'baz'], $query->getParameters());
        self::assertSame([null, null], $query->getParameterTypes());

        $query = new BulkInsertQuery($this->connection, $this->testTable, ['bar', 'baz']);

        $query->addValues(['bar', 'baz'], [1 => Connection::PARAM_BOOL]);

        self::assertSame("INSERT INTO {$this->testTable} (bar, baz) VALUES (?, ?)", (string)$query);
        self::assertSame(['bar', 'baz'], $query->getParameters());
        self::assertSame([null, Connection::PARAM_BOOL], $query->getParameterTypes());
    }

    /**
     * @test
     */
    public function singleInsertWithColumnSpecificationAndNamedTypeValues(): void
    {
        $query = new BulkInsertQuery($this->connection, $this->testTable, ['bar', 'baz']);

        $query->addValues(['baz' => 'baz', 'bar' => 'bar']);

        self::assertSame("INSERT INTO {$this->testTable} (bar, baz) VALUES (?, ?)", (string)$query);
        self::assertSame(['bar', 'baz'], $query->getParameters());
        self::assertSame([null, null], $query->getParameterTypes());

        $query = new BulkInsertQuery($this->connection, $this->testTable, ['bar', 'baz']);

        $query->addValues(['baz' => 'baz', 'bar' => 'bar'], [null, Connection::PARAM_INT]);

        self::assertSame("INSERT INTO {$this->testTable} (bar, baz) VALUES (?, ?)", (string)$query);
        self::assertSame(['bar', 'baz'], $query->getParameters());
        self::assertSame([null, Connection::PARAM_INT], $query->getParameterTypes());
    }

    /**
     * @test
     */
    public function singleInsertWithColumnSpecificationAndMixedTypeValues(): void
    {
        $query = new BulkInsertQuery($this->connection, $this->testTable, ['bar', 'baz']);

        $query->addValues([1 => 'baz', 'bar' => 'bar']);

        self::assertSame("INSERT INTO {$this->testTable} (bar, baz) VALUES (?, ?)", (string)$query);
        self::assertSame(['bar', 'baz'], $query->getParameters());
        self::assertSame([null, null], $query->getParameterTypes());

        $query = new BulkInsertQuery($this->connection, $this->testTable, ['bar', 'baz']);

        $query->addValues([1 => 'baz', 'bar' => 'bar'], [Connection::PARAM_INT, Connection::PARAM_BOOL]);

        self::assertSame("INSERT INTO {$this->testTable} (bar, baz) VALUES (?, ?)", (string)$query);
        self::assertSame(['bar', 'baz'], $query->getParameters());
        self::assertSame([Connection::PARAM_INT, Connection::PARAM_BOOL], $query->getParameterTypes());
    }

    /**
     * @test
     */
    public function multiInsertWithColumnSpecification(): void
    {
        $query = new BulkInsertQuery($this->connection, $this->testTable, ['bar', 'baz']);

        $query->addValues(['bar', 'baz']);
        $query->addValues([1 => 'baz', 'bar' => 'bar']);
        $query->addValues(['bar', 'baz' => 'baz']);
        $query->addValues(['bar' => 'bar', 'baz' => 'baz']);

        self::assertSame(
            "INSERT INTO {$this->testTable} (bar, baz) VALUES (?, ?), (?, ?), (?, ?), (?, ?)",
            (string)$query
        );
        self::assertSame(['bar', 'baz', 'bar', 'baz', 'bar', 'baz', 'bar', 'baz'], $query->getParameters());
        self::assertSame([null, null, null, null, null, null, null, null], $query->getParameterTypes());

        $query = new BulkInsertQuery($this->connection, $this->testTable, ['bar', 'baz']);

        $query->addValues(['bar', 'baz'], ['baz' => Connection::PARAM_BOOL, 'bar' => Connection::PARAM_INT]);
        $query->addValues([1 => 'baz', 'bar' => 'bar'], [1 => Connection::PARAM_BOOL, 'bar' => Connection::PARAM_INT]);
        $query->addValues(['bar', 'baz' => 'baz'], [null, null]);
        $query->addValues(
            ['bar' => 'bar', 'baz' => 'baz'],
            ['bar' => Connection::PARAM_INT, 'baz' => Connection::PARAM_BOOL]
        );

        self::assertSame(
            "INSERT INTO {$this->testTable} (bar, baz) VALUES (?, ?), (?, ?), (?, ?), (?, ?)",
            (string)$query
        );
        self::assertSame(['bar', 'baz', 'bar', 'baz', 'bar', 'baz', 'bar', 'baz'], $query->getParameters());
        self::assertSame(
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
    public function emptyInsertWithColumnSpecificationThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No value specified for column bar (index 0).');

        $query = new BulkInsertQuery($this->connection, $this->testTable, ['bar', 'baz']);
        $query->addValues([]);
    }

    /**
     * @test
     */
    public function insertWithColumnSpecificationAndMultipleValuesForColumnThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Multiple values specified for column baz (index 1).');

        $query = new BulkInsertQuery($this->connection, $this->testTable, ['bar', 'baz']);
        $query->addValues(['bar', 'baz', 'baz' => 666]);
    }

    /**
     * @test
     */
    public function insertWithColumnSpecificationAndMultipleTypesForColumnThrowsException(): void
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
    public function executeWithMaxInsertRowsPerStatementExceededThrowsException(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('You can only insert 10 rows in a single INSERT statement with platform "mock".');

        /** @var \PHPUnit\Framework\MockObject\MockObject|BulkInsertQuery $subject */
        $subject = $this->getAccessibleMock(
            BulkInsertQuery::class,
            ['getInsertMaxRows'],
            [$this->connection, $this->testTable],
            ''
        );

        $subject->expects(self::any())
            ->method('getInsertMaxRows')
            ->willReturn(10);

        for ($i = 0; $i <= 10; $i++) {
            $subject->addValues([]);
        }

        $subject->execute();
    }
}
