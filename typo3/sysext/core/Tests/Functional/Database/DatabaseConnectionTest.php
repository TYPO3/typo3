<?php
namespace TYPO3\CMS\Core\Tests\Functional\Database;

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
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Tests\AccessibleObjectInterface;
use TYPO3\CMS\Core\Tests\FunctionalTestCase;

/**
 * Test case for \TYPO3\CMS\Core\Database\DatabaseConnection
 */
class DatabaseConnectionTest extends FunctionalTestCase
{
    /**
     * @var DatabaseConnection
     */
    protected $subject = null;

    /**
     * @var string
     */
    protected $testTable = 'test_database_connection';

    /**
     * @var string
     */
    protected $testField = 'test_field';

    /**
     * @var string
     */
    protected $anotherTestField = 'another_test_field';

    /**
     * Set the test up
     *
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        $this->subject = $GLOBALS['TYPO3_DB'];
        $this->subject->sql_query(
            "CREATE TABLE {$this->testTable} (" .
            '   id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,' .
            "   {$this->testField} MEDIUMBLOB," .
            "   {$this->anotherTestField} MEDIUMBLOB," .
            '   PRIMARY KEY (id)' .
            ') ENGINE=MyISAM DEFAULT CHARSET=utf8;'
        );
    }

    /**
     * Tear the test down
     *
     * @return void
     */
    protected function tearDown()
    {
        $this->subject->sql_query("DROP TABLE {$this->testTable};");
        unset($this->subject);
    }

    /**
     * @test
     *
     * @return void
     */
    public function selectDbReturnsTrue()
    {
        $this->assertTrue($this->subject->sql_select_db());
    }

    /**
     * @test
     * @return void
     */
    public function selectDbReturnsFalse()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1270853883);
        $this->expectExceptionMessage('TYPO3 Fatal Error: Cannot connect to the current database, "Foo"!');

        $this->subject->setDatabaseName('Foo');
        $this->assertFalse($this->subject->sql_select_db());
    }

    /**
     * @test
     *
     * @return void
     */
    public function sqlAffectedRowsReturnsCorrectAmountOfRows()
    {
        $this->subject->exec_INSERTquery($this->testTable, [$this->testField => 'test']);
        $this->assertEquals(1, $this->subject->sql_affected_rows());
    }

    /**
     * @test
     *
     * @return void
     */
    public function sqlInsertIdReturnsCorrectId()
    {
        $this->subject->exec_INSERTquery($this->testTable, [$this->testField => 'test']);
        $this->assertEquals(1, $this->subject->sql_insert_id());
    }

    /**
     * @test
     *
     * @return void
     */
    public function noSqlError()
    {
        $this->subject->exec_INSERTquery($this->testTable, [$this->testField => 'test']);
        $this->assertEquals('', $this->subject->sql_error());
    }

    /**
     * @test
     *
     * @return void
     */
    public function sqlErrorWhenInsertIntoInexistentField()
    {
        $this->subject->exec_INSERTquery($this->testTable, ['test' => 'test']);
        $this->assertEquals('Unknown column \'test\' in \'field list\'', $this->subject->sql_error());
    }

    /**
     * @test
     *
     * @return void
     */
    public function noSqlErrorCode()
    {
        $this->subject->exec_INSERTquery($this->testTable, [$this->testField => 'test']);
        $this->assertEquals(0, $this->subject->sql_errno());
    }

    /**
     * @test
     *
     * @return void
     */
    public function sqlErrorNoWhenInsertIntoInexistentField()
    {
        $this->subject->exec_INSERTquery($this->testTable, ['test' => 'test']);
        $this->assertEquals(1054, $this->subject->sql_errno());
    }

    /**
     * @test
     */
    public function sqlPconnectReturnsInstanceOfMySqli()
    {
        $this->assertInstanceOf('mysqli', $this->subject->sql_pconnect());
    }

    /**
     * @test
     */
    public function connectDbThrowsExeptionsWhenNoDatabaseIsGiven()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1270853882);

        /** @var DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject|AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(DatabaseConnection::class, ['dummy'], [], '', false);
        $subject->connectDB();
    }

    /**
     * @test
     *
     * @return void
     */
    public function connectDbConnectsToDatabaseWithoutErrors()
    {
        $this->subject->connectDB();
        $this->assertTrue($this->subject->isConnected());
    }

    /**
     * @test
     *
     * @return void
     */
    public function disconnectIfConnectedDisconnects()
    {
        $this->assertTrue($this->subject->isConnected());
        $this->subject->setDatabaseHost('127.0.0.1');
        $this->assertFalse($this->subject->isConnected());
    }

    /**
     * Data Provider for fullQuoteStrReturnsQuotedString()
     *
     * @see fullQuoteStrReturnsQuotedString()
     *
     * @return array
     */
    public function fullQuoteStrReturnsQuotedStringDataProvider()
    {
        return [
            'NULL string with ReturnNull is allowed' => [
                [null, true],
                'NULL',
            ],
            'NULL string with ReturnNull is false' => [
                [null, false],
                "''",
            ],
            'Normal string' => [
                ['Foo', false],
                "'Foo'",
            ],
            'Single quoted string' => [
                ["'Hello'", false],
                "'\\'Hello\\''",
            ],
            'Double quoted string' => [
                ['"Hello"', false],
                "'\\\"Hello\\\"'",
            ],
            'String with internal single tick' => [
                ['It\'s me', false],
                "'It\\'s me'",
            ],
            'Slashes' => [
                ['/var/log/syslog.log', false],
                "'/var/log/syslog.log'",
            ],
            'Backslashes' => [
                ['\\var\\log\\syslog.log', false],
                "'\\\\var\\\\log\\\\syslog.log'",
            ],
        ];
    }

    /**
     * @test
     * @dataProvider fullQuoteStrReturnsQuotedStringDataProvider
     *
     * @param string $values
     * @param string $expectedResult
     *
     * @return void
     */
    public function fullQuoteStrReturnsQuotedString($values, $expectedResult)
    {
        /** @var DatabaseConnection $subject */
        $quotedStr = $this->subject->fullQuoteStr($values[0], 'tt_content', $values[1]);
        $this->assertEquals($expectedResult, $quotedStr);
    }

    /**
     * Data Provider for fullQuoteArrayQuotesArray()
     *
     * @see fullQuoteArrayQuotesArray()
     *
     * @return array
     */
    public function fullQuoteArrayQuotesArrayDataProvider()
    {
        return [
            'NULL array with ReturnNull is allowed' => [
                [
                    [null, null],
                    false,
                    true,
                ],
                ['NULL', 'NULL'],
            ],

            'NULL array with ReturnNull is false' => [
                [
                    [null, null],
                    false,
                    false,
                ],
                ["''", "''"],
            ],

            'Strings in array' => [
                [
                    ['Foo', 'Bar'],
                    false,
                    false,
                ],
                ["'Foo'", "'Bar'"],
            ],

            'Single quotes in array' => [
                [
                    ["'Hello'"],
                    false,
                    false,
                ],
                ["'\\'Hello\\''"],
            ],

            'Double quotes in array' => [
                [
                    ['"Hello"'],
                    false,
                    false,
                ],
                ["'\\\"Hello\\\"'"],
            ],

            'Slashes in array' => [
                [
                    ['/var/log/syslog.log'],
                    false,
                    false,
                ],
                ["'/var/log/syslog.log'"],
            ],

            'Backslashes in array' => [
                [
                    ['\var\log\syslog.log'],
                    false,
                    false,
                ],
                ["'\\\\var\\\\log\\\\syslog.log'"],
            ],

            'Strings with internal single tick' => [
                [
                    ['Hey!', 'It\'s me'],
                    false,
                    false,
                ],
                ["'Hey!'", "'It\\'s me'"],
            ],

            'no quotes strings from array' => [
                [
                    [
                        'First' => 'Hey!',
                        'Second' => 'It\'s me',
                        'Third' => 'O\' Reily',
                    ],
                    ['First', 'Third'],
                    false,
                ],
                ['First' => 'Hey!', 'Second' => "'It\\'s me'", 'Third' => "O' Reily"],
            ],

            'no quotes strings from string' => [
                [
                    [
                        'First' => 'Hey!',
                        'Second' => 'It\'s me',
                        'Third' => 'O\' Reily',
                    ],
                    'First,Third',
                    false,
                ],
                ['First' => 'Hey!', 'Second' => "'It\\'s me'", 'Third' => "O' Reily"],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider fullQuoteArrayQuotesArrayDataProvider
     *
     * @param string $values
     * @param string $expectedResult
     *
     * @return void
     */
    public function fullQuoteArrayQuotesArray($values, $expectedResult)
    {
        $quotedResult = $this->subject->fullQuoteArray($values[0], $this->testTable, $values[1], $values[2]);
        $this->assertSame($expectedResult, $quotedResult);
    }

    /**
     * Data Provider for quoteStrQuotesDoubleQuotesCorrectly()
     *
     * @see quoteStrQuotesDoubleQuotesCorrectly()
     *
     * @return array
     */
    public function quoteStrQuotesCorrectlyDataProvider()
    {
        return [
            'Double Quotes' => [
                '"Hello"',
                '\\"Hello\\"'
            ],
            'Single Quotes' => [
                '\'Hello\'',
                "\\'Hello\\'"
            ],
            'Slashes' => [
                '/var/log/syslog.log',
                '/var/log/syslog.log'
            ],
            'Literal Backslashes' => [
                '\\var\\log\\syslog.log',
                '\\\\var\\\\log\\\\syslog.log'
            ],
            'Fallback Literal Backslashes' => [
                '\var\log\syslog.log',
                '\\\\var\\\\log\\\\syslog.log'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider quoteStrQuotesCorrectlyDataProvider
     *
     * @param string $string String to quote
     * @param string $expectedResult Quoted string we expect
     *
     * @return void
     */
    public function quoteStrQuotesDoubleQuotesCorrectly($string, $expectedResult)
    {
        $quotedString = $this->subject->quoteStr($string, $this->testTable);
        $this->assertSame($expectedResult, $quotedString);
    }

    /**
     * @test
     *
     * @return void
     */
    public function adminQueryReturnsTrueForInsertQuery()
    {
        $this->assertTrue(
            $this->subject->admin_query("INSERT INTO {$this->testTable} ({$this->testField}) VALUES ('foo')")
        );
    }

    /**
     * @test
     *
     * @return void
     */
    public function adminQueryReturnsTrueForUpdateQuery()
    {
        $this->assertTrue(
            $this->subject->admin_query("INSERT INTO {$this->testTable} ({$this->testField}) VALUES ('foo')")
        );
        $id = $this->subject->sql_insert_id();
        $this->assertTrue(
            $this->subject->admin_query("UPDATE {$this->testTable} SET {$this->testField}='bar' WHERE id={$id}")
        );
    }

    /**
     * @test
     *
     * @return void
     */
    public function adminQueryReturnsTrueForDeleteQuery()
    {
        $this->assertTrue(
            $this->subject->admin_query("INSERT INTO {$this->testTable} ({$this->testField}) VALUES ('foo')")
        );
        $id = $this->subject->sql_insert_id();
        $this->assertTrue($this->subject->admin_query("DELETE FROM {$this->testTable} WHERE id={$id}"));
    }

    /**
     * @test
     *
     * @return void
     */
    public function adminQueryReturnsResultForSelectQuery()
    {
        $this->assertTrue(
            $this->subject->admin_query("INSERT INTO {$this->testTable} ({$this->testField}) VALUES ('foo')")
        );
        $res = $this->subject->admin_query("SELECT {$this->testField} FROM {$this->testTable}");
        $this->assertInstanceOf('mysqli_result', $res);
        $result = $res->fetch_assoc();
        $this->assertEquals('foo', $result[$this->testField]);
    }

    /**
     * @test
     *
     * @return void
     */
    public function adminGetCharsetsReturnsArrayWithCharsets()
    {
        $columnsRes = $this->subject->admin_query('SHOW CHARACTER SET');
        $result = $this->subject->admin_get_charsets();
        $this->assertEquals(count($result), $columnsRes->num_rows);

        /** @var array $row */
        while (($row = $columnsRes->fetch_assoc())) {
            $this->assertArrayHasKey($row['Charset'], $result);
        }
    }

    /**
     * @test
     *
     * @return void
     */
    public function adminGetKeysReturnIndexKeysOfTable()
    {
        $result = $this->subject->admin_get_keys($this->testTable);
        $this->assertEquals('id', $result[0]['Column_name']);
    }

    /**
     * @test
     *
     * @return void
     */
    public function adminGetFieldsReturnFieldInformationsForTable()
    {
        $result = $this->subject->admin_get_fields($this->testTable);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey($this->testField, $result);
    }

    /**
     * @test
     *
     * @return void
     */
    public function adminGetTablesReturnAllTablesFromDatabase()
    {
        $result = $this->subject->admin_get_tables();
        $this->assertArrayHasKey('tt_content', $result);
        $this->assertArrayHasKey('pages', $result);
    }

    /**
     * @test
     *
     * @return void
     */
    public function adminGetDbsReturnsAllDatabases()
    {
        /** @noinspection SqlResolve */
        $databases = $this->subject->admin_query('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA');
        $result = $this->subject->admin_get_dbs();
        $this->assertSame(count($result), $databases->num_rows);

        $i = 0;
        while ($database = $databases->fetch_assoc()) {
            $this->assertSame($database['SCHEMA_NAME'], $result[$i]);
            $i++;
        }
    }

    /**
     * Data Provider for sqlNumRowsReturnsCorrectAmountOfRows()
     *
     * @see sqlNumRowsReturnsCorrectAmountOfRows()
     *
     * @return array
     */
    public function sqlNumRowsReturnsCorrectAmountOfRowsProvider()
    {
        $sql1 = "SELECT * FROM {$this->testTable} WHERE {$this->testField}='baz'";
        $sql2 = "SELECT * FROM {$this->testTable} WHERE {$this->testField}='baz' OR {$this->testField}='bar'";
        $sql3 = "SELECT * FROM {$this->testTable} WHERE {$this->testField} IN ('baz', 'bar', 'foo')";

        return [
            'One result' => [$sql1, 1],
            'Two results' => [$sql2, 2],
            'Three results' => [$sql3, 3],
        ];
    }

    /**
     * @test
     * @dataProvider sqlNumRowsReturnsCorrectAmountOfRowsProvider
     *
     * @param string $sql
     * @param string $expectedResult
     *
     * @return void
     */
    public function sqlNumRowsReturnsCorrectAmountOfRows($sql, $expectedResult)
    {
        $this->assertTrue(
            $this->subject->admin_query(
                "INSERT INTO {$this->testTable} ({$this->testField}) VALUES ('foo'), ('bar'), ('baz')"
            )
        );

        $res = $this->subject->admin_query($sql);
        $numRows = $this->subject->sql_num_rows($res);
        $this->assertSame($expectedResult, $numRows);
    }

    /**
     * @test
     *
     * @return void
     */
    public function sqlNumRowsReturnsFalse()
    {
        $res = $this->subject->admin_query("SELECT * FROM {$this->testTable} WHERE test='baz'");
        $numRows = $this->subject->sql_num_rows($res);
        $this->assertFalse($numRows);
    }

    /**
     * Prepares the test table for the fetch* Tests
     *
     * @return void
     */
    protected function prepareTableForFetchTests()
    {
        $this->assertTrue(
            $this->subject->sql_query(
                "ALTER TABLE {$this->testTable} " .
                'ADD name mediumblob, ' .
                'ADD deleted int, ' .
                'ADD street varchar(100), ' .
                'ADD city varchar(50), ' .
                'ADD country varchar(100)'
            )
        );

        $this->assertTrue(
            $this->subject->admin_query(
                "INSERT INTO {$this->testTable} (name,street,city,country,deleted) VALUES " .
                "('Mr. Smith','Oakland Road','Los Angeles','USA',0)," .
                "('Ms. Smith','Oakland Road','Los Angeles','USA',0)," .
                "('Alice im Wunderland','Große Straße','Königreich der Herzen','Wunderland',0)," .
                "('Agent Smith','Unbekannt','Unbekannt','Matrix',1)"
            )
        );
    }

    /**
     * @test
     *
     * @return void
     */
    public function sqlFetchAssocReturnsAssocArray()
    {
        $this->prepareTableForFetchTests();

        $res = $this->subject->admin_query("SELECT * FROM {$this->testTable} ORDER BY id");
        $expectedResult = [
            [
                'id' => '1',
                $this->testField => null,
                $this->anotherTestField => null,
                'name' => 'Mr. Smith',
                'deleted' => '0',
                'street' => 'Oakland Road',
                'city' => 'Los Angeles',
                'country' => 'USA',
            ],
            [
                'id' => '2',
                $this->testField => null,
                $this->anotherTestField => null,
                'name' => 'Ms. Smith',
                'deleted' => '0',
                'street' => 'Oakland Road',
                'city' => 'Los Angeles',
                'country' => 'USA',
            ],
            [
                'id' => '3',
                $this->testField => null,
                $this->anotherTestField => null,
                'name' => 'Alice im Wunderland',
                'deleted' => '0',
                'street' => 'Große Straße',
                'city' => 'Königreich der Herzen',
                'country' => 'Wunderland',
            ],
            [
                'id' => '4',
                $this->testField => null,
                $this->anotherTestField => null,
                'name' => 'Agent Smith',
                'deleted' => '1',
                'street' => 'Unbekannt',
                'city' => 'Unbekannt',
                'country' => 'Matrix',
            ],
        ];
        $i = 0;
        while ($row = $this->subject->sql_fetch_assoc($res)) {
            $this->assertSame($expectedResult[$i], $row);
            $i++;
        }
    }

    /**
     * @test
     *
     * @return void
     */
    public function sqlFetchRowReturnsNumericArray()
    {
        $this->prepareTableForFetchTests();
        $res = $this->subject->admin_query("SELECT * FROM {$this->testTable} ORDER BY id");
        $expectedResult = [
            ['1', null, null, 'Mr. Smith', '0', 'Oakland Road', 'Los Angeles', 'USA'],
            ['2', null, null, 'Ms. Smith', '0', 'Oakland Road', 'Los Angeles', 'USA'],
            ['3', null, null, 'Alice im Wunderland', '0', 'Große Straße', 'Königreich der Herzen', 'Wunderland'],
            ['4', null, null, 'Agent Smith', '1', 'Unbekannt', 'Unbekannt', 'Matrix'],
        ];
        $i = 0;
        while ($row = $this->subject->sql_fetch_row($res)) {
            $this->assertSame($expectedResult[$i], $row);
            $i++;
        }
    }

    /**
     * @test
     *
     * @return void
     */
    public function sqlFreeResultReturnsFalseOnFailure()
    {
        $this->assertTrue(
            $this->subject->admin_query("INSERT INTO {$this->testTable} ({$this->testField}) VALUES ('baz')")
        );
        $res = $this->subject->admin_query("SELECT * FROM {$this->testTable} WHERE {$this->testField}=baz");
        $this->assertFalse($this->subject->sql_free_result($res));
    }

    /**
     * @test
     *
     * @return void
     */
    public function sqlFreeResultReturnsTrueOnSuccess()
    {
        $this->assertTrue(
            $this->subject->admin_query("INSERT INTO {$this->testTable} ({$this->testField}) VALUES ('baz')")
        );
        $res = $this->subject->admin_query("SELECT * FROM {$this->testTable} WHERE {$this->testField}='baz'");
        $this->assertTrue($this->subject->sql_free_result($res));
    }
}
