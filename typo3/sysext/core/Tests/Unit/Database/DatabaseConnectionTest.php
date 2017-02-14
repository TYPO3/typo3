<?php
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

use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;

/**
 * Test case
 */
class DatabaseConnectionTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @var DatabaseConnection
     */
    protected $subject;
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
     */
    protected function setUp()
    {
        parent::setUp();
        $this->subject = $this->getAccessibleMock(DatabaseConnection::class, ['dummy'], [], '', false);
        $this->subject->_set('databaseName', 'typo3_test');
    }

    //////////////////////////////////////////////////
    // Write/Read tests for charsets and binaries
    //////////////////////////////////////////////////

    /**
     * @test
     */
    public function storedFullAsciiRangeCallsLinkObjectWithGivenData()
    {
        $binaryString = '';
        for ($i = 0; $i < 256; $i++) {
            $binaryString .= chr($i);
        }

        /** @var DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject|AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(DatabaseConnection::class, ['fullQuoteStr'], [], '', false);
        $subject->_set('isConnected', true);
        $subject
            ->expects($this->any())
            ->method('fullQuoteStr')
            ->will($this->returnCallback(function ($data) {
                return $data;
            }));
        $mysqliProphecy = $this->prophesize(\mysqli::class);
        $mysqliProphecy->query("INSERT INTO {$this->testTable} ({$this->testField}) VALUES ({$binaryString})")
            ->shouldBeCalled();
        $subject->_set('link', $mysqliProphecy->reveal());

        $subject->exec_INSERTquery($this->testTable, [$this->testField => $binaryString]);
    }

    /**
     * @test
     * @requires function gzcompress
     */
    public function storedGzipCompressedDataReturnsSameData()
    {
        $testStringWithBinary = gzcompress('sdfkljer4587');

        /** @var DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject|AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(DatabaseConnection::class, ['fullQuoteStr'], [], '', false);
        $subject->_set('isConnected', true);
        $subject
            ->expects($this->any())
            ->method('fullQuoteStr')
            ->will($this->returnCallback(function ($data) {
                return $data;
            }));
        $mysqliProphecy = $this->prophesize(\mysqli::class);
        $mysqliProphecy->query("INSERT INTO {$this->testTable} ({$this->testField}) VALUES ({$testStringWithBinary})")
            ->shouldBeCalled();
        $subject->_set('link', $mysqliProphecy->reveal());

        $subject->exec_INSERTquery($this->testTable, [$this->testField => $testStringWithBinary]);
    }

    ////////////////////////////////
    // Tests concerning listQuery
    ////////////////////////////////

    /**
     * @test
     * @see http://forge.typo3.org/issues/23253
     */
    public function listQueryWithIntegerCommaAsValue()
    {
        /** @var DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject|AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(DatabaseConnection::class, ['quoteStr'], [], '', false);
        $subject->_set('isConnected', true);
        $subject
            ->expects($this->any())
            ->method('quoteStr')
            ->will($this->returnCallback(function ($data) {
                return $data;
            }));
        // Note: 44 = ord(',')
        $this->assertEquals($subject->listQuery('dummy', 44, 'table'), $subject->listQuery('dummy', '44', 'table'));
    }

    /**
     * @test
     */
    public function listQueryThrowsExceptionIfValueContainsComma()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1294585862);

        /** @var DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject|AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(DatabaseConnection::class, ['quoteStr'], [], '', false);
        $subject->_set('isConnected', true);
        $subject->listQuery('aField', 'foo,bar', 'aTable');
    }

    ////////////////////////////////
    // Tests concerning searchQuery
    ////////////////////////////////

    /**
     * Data provider for searchQueryCreatesQuery
     *
     * @return array
     */
    public function searchQueryDataProvider()
    {
        return [
            'One search word in one field' => [
                '(pages.title LIKE \'%TYPO3%\')',
                ['TYPO3'],
                ['title'],
                'pages',
                'AND',
            ],
            'One search word with special chars (for like)' => [
                '(pages.title LIKE \'%TYPO3\\_100\\%%\')',
                ['TYPO3_100%'],
                ['title'],
                'pages',
                'AND',
            ],
            'One search word in multiple fields' => [
                "(pages.title LIKE '%TYPO3%' OR pages.keyword LIKE '%TYPO3%' OR pages.description LIKE '%TYPO3%')",
                ['TYPO3'],
                ['title', 'keyword', 'description'],
                'pages',
                'AND',
            ],
            'Multiple search words in one field with AND constraint' => [
                "(pages.title LIKE '%TYPO3%') AND (pages.title LIKE '%is%') AND (pages.title LIKE '%great%')",
                ['TYPO3', 'is', 'great'],
                ['title'],
                'pages',
                'AND',
            ],
            'Multiple search words in one field with OR constraint' => [
                "(pages.title LIKE '%TYPO3%') OR (pages.title LIKE '%is%') OR (pages.title LIKE '%great%')",
                ['TYPO3', 'is', 'great'],
                ['title'],
                'pages',
                'OR',
            ],
            'Multiple search words in multiple fields with AND constraint' => [
                "(pages.title LIKE '%TYPO3%' OR pages.keywords LIKE '%TYPO3%' OR pages.description LIKE '%TYPO3%') " .
                "AND (pages.title LIKE '%is%' OR pages.keywords LIKE '%is%' OR pages.description LIKE '%is%') " .
                "AND (pages.title LIKE '%great%' OR pages.keywords LIKE '%great%' OR pages.description LIKE '%great%')",
                ['TYPO3', 'is', 'great'],
                ['title', 'keywords', 'description'],
                'pages',
                'AND',
            ],
            'Multiple search words in multiple fields with OR constraint' => [
                "(pages.title LIKE '%TYPO3%' OR pages.keywords LIKE '%TYPO3%' OR pages.description LIKE '%TYPO3%') " .
                "OR (pages.title LIKE '%is%' OR pages.keywords LIKE '%is%' OR pages.description LIKE '%is%') " .
                "OR (pages.title LIKE '%great%' OR pages.keywords LIKE '%great%' OR pages.description LIKE '%great%')",
                ['TYPO3', 'is', 'great'],
                ['title', 'keywords', 'description'],
                'pages',
                'OR',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider searchQueryDataProvider
     * @param string $expectedResult
     * @param array $searchWords
     * @param array $fields
     * @param string $table
     * @param string $constraint
     */
    public function searchQueryCreatesQuery($expectedResult, array $searchWords, array $fields, $table, $constraint)
    {
        /** @var DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMockBuilder(DatabaseConnection::class)
            ->setMethods(['quoteStr'])
            ->disableOriginalConstructor()
            ->getMock();
        $subject
            ->expects($this->any())
            ->method('quoteStr')
            ->will($this->returnCallback(function ($data) {
                return $data;
            }));

        $this->assertSame($expectedResult, $subject->searchQuery($searchWords, $fields, $table, $constraint));
    }

    /////////////////////////////////////////////////
    // Tests concerning escapeStringForLikeComparison
    /////////////////////////////////////////////////

    /**
     * @test
     */
    public function escapeStringForLikeComparison()
    {
        /** @var DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMockBuilder(DatabaseConnection::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertEquals('foo\\_bar\\%', $subject->escapeStrForLike('foo_bar%', 'table'));
    }

    /////////////////////////////////////////////////
    // Tests concerning stripOrderByForOrderByKeyword
    /////////////////////////////////////////////////

    /**
     * Data Provider for stripGroupByForGroupByKeyword()
     *
     * @see stripOrderByForOrderByKeyword()
     * @return array
     */
    public function stripOrderByForOrderByKeywordDataProvider()
    {
        return [
            'single ORDER BY' => [
                'ORDER BY name, tstamp',
                'name, tstamp'
            ],
            'single ORDER BY in lower case' => [
                'order by name, tstamp',
                'name, tstamp'
            ],
            'ORDER BY with additional space behind' => [
                'ORDER BY  name, tstamp',
                'name, tstamp'
            ],
            'ORDER BY without space between the words' => [
                'ORDERBY name, tstamp',
                'name, tstamp'
            ],
            'ORDER BY added twice' => [
                'ORDER BY ORDER BY name, tstamp',
                'name, tstamp'
            ],
            'ORDER BY added twice without spaces in the first occurrence' => [
                'ORDERBY ORDER BY  name, tstamp',
                'name, tstamp',
            ],
            'ORDER BY added twice without spaces in the second occurrence' => [
                'ORDER BYORDERBY name, tstamp',
                'name, tstamp',
            ],
            'ORDER BY added twice without spaces' => [
                'ORDERBYORDERBY name, tstamp',
                'name, tstamp'
            ],
            'ORDER BY added twice without spaces afterwards' => [
                'ORDERBYORDERBYname, tstamp',
                'name, tstamp'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider stripOrderByForOrderByKeywordDataProvider
     * @param string $orderByClause The clause to test
     * @param string $expectedResult The expected result
     */
    public function stripOrderByForOrderByKeyword($orderByClause, $expectedResult)
    {
        /** @var DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMockBuilder(DatabaseConnection::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $strippedQuery = $subject->stripOrderBy($orderByClause);
        $this->assertEquals($expectedResult, $strippedQuery);
    }

    /////////////////////////////////////////////////
    // Tests concerning stripGroupByForGroupByKeyword
    /////////////////////////////////////////////////

    /**
     * Data Provider for stripGroupByForGroupByKeyword()
     *
     * @see stripGroupByForGroupByKeyword()
     * @return array
     */
    public function stripGroupByForGroupByKeywordDataProvider()
    {
        return [
            'single GROUP BY' => [
                'GROUP BY name, tstamp',
                'name, tstamp'
            ],
            'single GROUP BY in lower case' => [
                'group by name, tstamp',
                'name, tstamp'
            ],
            'GROUP BY with additional space behind' => [
                'GROUP BY  name, tstamp',
                'name, tstamp'
            ],
            'GROUP BY without space between the words' => [
                'GROUPBY name, tstamp',
                'name, tstamp'
            ],
            'GROUP BY added twice' => [
                'GROUP BY GROUP BY name, tstamp',
                'name, tstamp'
            ],
            'GROUP BY added twice without spaces in the first occurrence' => [
                'GROUPBY GROUP BY  name, tstamp',
                'name, tstamp',
            ],
            'GROUP BY added twice without spaces in the second occurrence' => [
                'GROUP BYGROUPBY name, tstamp',
                'name, tstamp',
            ],
            'GROUP BY added twice without spaces' => [
                'GROUPBYGROUPBY name, tstamp',
                'name, tstamp'
            ],
            'GROUP BY added twice without spaces afterwards' => [
                'GROUPBYGROUPBYname, tstamp',
                'name, tstamp'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider stripGroupByForGroupByKeywordDataProvider
     * @param string $groupByClause The clause to test
     * @param string $expectedResult The expected result
     */
    public function stripGroupByForGroupByKeyword($groupByClause, $expectedResult)
    {
        /** @var DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMockBuilder(DatabaseConnection::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $strippedQuery = $subject->stripGroupBy($groupByClause);
        $this->assertEquals($expectedResult, $strippedQuery);
    }

    /////////////////////////////////////////////////
    // Tests concerning stripOrderByForOrderByKeyword
    /////////////////////////////////////////////////

    /**
     * Data Provider for stripGroupByForGroupByKeyword()
     *
     * @see stripOrderByForOrderByKeyword()
     * @return array
     */
    public function cleanIntArrayDataProvider()
    {
        return [
            'simple array' => [
                [1, 2, 3],
                [1, 2, 3],
            ],
            'string array' => [
                ['2', '4', '8'],
                [2, 4, 8],
            ],
            'string array with letters #1' => [
                ['3', '6letters', '12'],
                [3, 6, 12],
            ],
            'string array with letters #2' => [
                ['3', 'letters6', '12'],
                [3, 0, 12],
            ],
            'string array with letters #3' => [
                ['3', '6letters4', '12'],
                [3, 6, 12],
            ],
            'associative array' => [
                ['apples' => 3, 'bananas' => 4, 'kiwis' => 9],
                ['apples' => 3, 'bananas' => 4, 'kiwis' => 9],
            ],
            'associative string array' => [
                ['apples' => '1', 'bananas' => '5', 'kiwis' => '7'],
                ['apples' => 1, 'bananas' => 5, 'kiwis' => 7],
            ],
            'associative string array with letters #1' => [
                ['apples' => '1', 'bananas' => 'no5', 'kiwis' => '7'],
                ['apples' => 1, 'bananas' => 0, 'kiwis' => 7],
            ],
            'associative string array with letters #2' => [
                ['apples' => '1', 'bananas' => '5yes', 'kiwis' => '7'],
                ['apples' => 1, 'bananas' => 5, 'kiwis' => 7],
            ],
            'associative string array with letters #3' => [
                ['apples' => '1', 'bananas' => '5yes9', 'kiwis' => '7'],
                ['apples' => 1, 'bananas' => 5, 'kiwis' => 7],
            ],
            'multidimensional associative array' => [
                ['apples' => '1', 'bananas' => [3, 4], 'kiwis' => '7'],
                // intval(array(...)) is 1
                // But by specification "cleanIntArray" should only get used on one-dimensional arrays
                ['apples' => 1, 'bananas' => 1, 'kiwis' => 7],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider cleanIntArrayDataProvider
     * @param array $exampleData The array to sanitize
     * @param array $expectedResult The expected result
     */
    public function cleanIntArray($exampleData, $expectedResult)
    {
        $sanitizedArray = $this->subject->cleanIntArray($exampleData);
        $this->assertEquals($expectedResult, $sanitizedArray);
    }

    /**
     * @test
     */
    public function cleanIntListReturnsCleanedString()
    {
        $str = '234,-434,4.3,0, 1';
        $result = $this->subject->cleanIntList($str);
        $this->assertSame('234,-434,4,0,1', $result);
    }

    /**
     * @test
     */
    public function sqlForSelectMmQuery()
    {
        $result = $this->subject->SELECT_mm_query(
            '*',
            'sys_category',
            'sys_category_record_mm',
            'tt_content',
            'AND sys_category.uid = 1',
            '',
            'sys_category.title DESC'
        );
        $expected = 'SELECT * FROM sys_category,sys_category_record_mm,tt_content ' .
            'WHERE sys_category.uid=sys_category_record_mm.uid_local ' .
            'AND tt_content.uid=sys_category_record_mm.uid_foreign ' .
            'AND sys_category.uid = 1 ORDER BY sys_category.title DESC';
        $this->assertEquals($expected, $result);
    }

    /**
     * Data provider for searchQueryCreatesQuery
     *
     * @return array
     */
    public function noQuoteForFullQuoteArrayDataProvider()
    {
        return [
            'noQuote boolean false' => [
                ['aField' => 'aValue', 'anotherField' => 'anotherValue'],
                ['aField' => "'aValue'", 'anotherField' => "'anotherValue'"],
                false,
            ],
            'noQuote boolean true' => [
                ['aField' => 'aValue', 'anotherField' => 'anotherValue'],
                ['aField' => 'aValue', 'anotherField' => 'anotherValue'],
                true,
            ],
            'noQuote list of fields' => [
                ['aField' => 'aValue', 'anotherField' => 'anotherValue'],
                ['aField' => "'aValue'", 'anotherField' => 'anotherValue'],
                'anotherField',
            ],
            'noQuote array of fields' => [
                ['aField' => 'aValue', 'anotherField' => 'anotherValue'],
                ['aField' => 'aValue', 'anotherField' => "'anotherValue'"],
                ['aField'],
            ],
        ];
    }

    /**
     * @test
     * @param array $input
     * @param array $expected
     * @param bool|array|string $noQuote
     * @dataProvider noQuoteForFullQuoteArrayDataProvider
     */
    public function noQuoteForFullQuoteArray(array $input, array $expected, $noQuote)
    {
        /** @var DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject|AccessibleObjectInterface $subject */
        $subject = $this->getMockBuilder(DatabaseConnection::class)
            ->setMethods(['fullQuoteStr'])
            ->disableOriginalConstructor()
            ->getMock();

        $subject
            ->expects($this->any())
            ->method('fullQuoteStr')
            ->will($this->returnCallback(function ($data) {
                return '\'' . (string)$data . '\'';
            }));
        $this->assertSame($expected, $subject->fullQuoteArray($input, 'aTable', $noQuote));
    }

    /**
     * @test
     */
    public function sqlSelectDbReturnsTrue()
    {
        /** @var DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject|AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(DatabaseConnection::class, ['dummy'], [], '', false);
        $subject->_set('isConnected', true);
        $subject->_set('databaseName', $this->testTable);

        $mysqliProphecy = $this->prophesize(\mysqli::class);
        $mysqliProphecy->select_db($this->testTable)->shouldBeCalled()->willReturn(true);
        $subject->_set('link', $mysqliProphecy->reveal());

        $this->assertTrue($subject->sql_select_db());
    }

    /**
     * @test
     */
    public function sqlSelectDbReturnsFalse()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLogLevel'] = GeneralUtility::SYSLOG_SEVERITY_WARNING;

        /** @var DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject|AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(DatabaseConnection::class, ['sql_error'], [], '', false);
        $subject->_set('isConnected', true);
        $subject->_set('databaseName', $this->testTable);
        $subject->expects($this->any())->method('sql_error')->will($this->returnValue(''));

        $mysqliProphecy = $this->prophesize(\mysqli::class);
        $mysqliProphecy->select_db($this->testTable)->shouldBeCalled()->willReturn(false);
        $subject->_set('link', $mysqliProphecy->reveal());

        $this->assertFalse($subject->sql_select_db());
    }

    /**
     * @test
     */
    public function insertQueryCreateValidQuery()
    {
        $this->subject = $this->getAccessibleMock(DatabaseConnection::class, ['fullQuoteStr'], [], '', false);
        $this->subject->expects($this->any())
            ->method('fullQuoteStr')
            ->will($this->returnCallback(function ($data) {
                return '\'' . (string)$data . '\'';
            }));

        $fieldValues = [$this->testField => 'Foo'];
        $queryExpected = "INSERT INTO {$this->testTable} ({$this->testField}) VALUES ('Foo')";
        $queryGenerated = $this->subject->INSERTquery($this->testTable, $fieldValues);
        $this->assertSame($queryExpected, $queryGenerated);
    }

    /**
     * @test
     */
    public function insertQueryCreateValidQueryFromMultipleValues()
    {
        $this->subject = $this->getAccessibleMock(DatabaseConnection::class, ['fullQuoteStr'], [], '', false);
        $this->subject->expects($this->any())
            ->method('fullQuoteStr')
            ->will($this->returnCallback(function ($data) {
                return '\'' . (string)$data . '\'';
            }));
        $fieldValues = [
            $this->testField => 'Foo',
            $this->anotherTestField => 'Bar',
        ];
        $queryExpected = "INSERT INTO {$this->testTable} ({$this->testField},{$this->anotherTestField}) " .
            "VALUES ('Foo','Bar')";
        $queryGenerated = $this->subject->INSERTquery($this->testTable, $fieldValues);
        $this->assertSame($queryExpected, $queryGenerated);
    }

    /**
     * @test
     */
    public function insertMultipleRowsCreateValidQuery()
    {
        $this->subject = $this->getAccessibleMock(DatabaseConnection::class, ['fullQuoteStr'], [], '', false);
        $this->subject->expects($this->any())
            ->method('fullQuoteStr')
            ->will($this->returnCallback(function ($data) {
                return '\'' . (string)$data . '\'';
            }));
        $fields = [$this->testField, $this->anotherTestField];
        $values = [
            ['Foo', 100],
            ['Bar', 200],
            ['Baz', 300],
        ];
        $queryExpected = "INSERT INTO {$this->testTable} ({$this->testField}, {$this->anotherTestField}) " .
            "VALUES ('Foo', '100'), ('Bar', '200'), ('Baz', '300')";
        $queryGenerated = $this->subject->INSERTmultipleRows($this->testTable, $fields, $values);
        $this->assertSame($queryExpected, $queryGenerated);
    }

    /**
     * @test
     */
    public function updateQueryCreateValidQuery()
    {
        $this->subject = $this->getAccessibleMock(DatabaseConnection::class, ['fullQuoteStr'], [], '', false);
        $this->subject->expects($this->any())
            ->method('fullQuoteStr')
            ->will($this->returnCallback(function ($data) {
                return '\'' . (string)$data . '\'';
            }));

        $fieldsValues = [$this->testField => 'aTestValue'];
        $queryExpected = "UPDATE {$this->testTable} SET {$this->testField}='aTestValue' WHERE id=1";
        $queryGenerated = $this->subject->UPDATEquery($this->testTable, 'id=1', $fieldsValues);
        $this->assertSame($queryExpected, $queryGenerated);
    }

    /**
     * @test
     */
    public function deleteQueryCreateValidQuery()
    {
        $queryExpected = "DELETE FROM {$this->testTable} WHERE id=1";
        $queryGenerated = $this->subject->DELETEquery($this->testTable, 'id=1');
        $this->assertSame($queryExpected, $queryGenerated);
    }

    /**
     * @test
     */
    public function selectQueryCreateValidQuery()
    {
        $queryExpected = "SELECT {$this->testField} FROM {$this->testTable} WHERE id=1";
        $queryGenerated = $this->subject->SELECTquery($this->testField, $this->testTable, 'id=1');
        $this->assertSame($queryExpected, $queryGenerated);
    }

    /**
     * @test
     */
    public function selectQueryCreateValidQueryWithEmptyWhereClause()
    {
        $queryExpected = "SELECT {$this->testField} FROM {$this->testTable}";
        $queryGenerated = $this->subject->SELECTquery($this->testField, $this->testTable, '');
        $this->assertSame($queryExpected, $queryGenerated);
    }

    /**
     * @test
     */
    public function selectQueryCreateValidQueryWithGroupByClause()
    {
        $queryExpected = "SELECT {$this->testField} FROM {$this->testTable} WHERE id=1 GROUP BY id";
        $queryGenerated = $this->subject->SELECTquery($this->testField, $this->testTable, 'id=1', 'id');
        $this->assertSame($queryExpected, $queryGenerated);
    }

    /**
     * @test
     */
    public function selectQueryCreateValidQueryWithOrderByClause()
    {
        $queryExpected = "SELECT {$this->testField} FROM {$this->testTable} WHERE id=1 ORDER BY id";
        $queryGenerated = $this->subject->SELECTquery($this->testField, $this->testTable, 'id=1', '', 'id');
        $this->assertSame($queryExpected, $queryGenerated);
    }

    /**
     * @test
     */
    public function selectQueryCreateValidQueryWithLimitClause()
    {
        $queryGenerated = $this->subject->SELECTquery($this->testField, $this->testTable, 'id=1', '', '', '1,2');
        $queryExpected = "SELECT {$this->testField} FROM {$this->testTable} WHERE id=1 LIMIT 1,2";
        $this->assertSame($queryExpected, $queryGenerated);
    }

    /**
     * @test
     */
    public function selectSubQueryCreateValidQuery()
    {
        $queryExpected = "SELECT {$this->testField} FROM {$this->testTable} WHERE id=1";
        $queryGenerated = $this->subject->SELECTsubquery($this->testField, $this->testTable, 'id=1');
        $this->assertSame($queryExpected, $queryGenerated);
    }

    /**
     * @test
     */
    public function truncateQueryCreateValidQuery()
    {
        $queryExpected = "TRUNCATE TABLE {$this->testTable}";
        $queryGenerated = $this->subject->TRUNCATEquery($this->testTable);
        $this->assertSame($queryExpected, $queryGenerated);
    }
}
