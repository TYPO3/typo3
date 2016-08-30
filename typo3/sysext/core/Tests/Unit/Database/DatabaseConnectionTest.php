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

/**
 * Test case
 *
 */
class DatabaseConnectionTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
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

        /** @var \TYPO3\CMS\Core\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, ['fullQuoteStr'], [], '', false);
        $subject->_set('isConnected', true);
        $subject
            ->expects($this->any())
            ->method('fullQuoteStr')
            ->will($this->returnCallback(function ($data) {
                return $data;
            }));
        $mysqliProphecy = $this->prophesize(\mysqli::class);
        $mysqliProphecy->query('INSERT INTO aTable (fieldblob) VALUES (' . $binaryString . ')')
            ->shouldBeCalled();
        $subject->_set('link', $mysqliProphecy->reveal());

        $subject->exec_INSERTquery('aTable', ['fieldblob' => $binaryString]);
    }

    /**
     * @test
     */
    public function storedGzipCompressedDataReturnsSameData()
    {
        $testStringWithBinary = @gzcompress('sdfkljer4587');

        /** @var \TYPO3\CMS\Core\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, ['fullQuoteStr'], [], '', false);
        $subject->_set('isConnected', true);
        $subject
            ->expects($this->any())
            ->method('fullQuoteStr')
            ->will($this->returnCallback(function ($data) {
                return $data;
            }));
        $mysqliProphecy = $this->prophesize(\mysqli::class);
        $mysqliProphecy->query('INSERT INTO aTable (fieldblob) VALUES (' . $testStringWithBinary . ')')
            ->shouldBeCalled();
        $subject->_set('link', $mysqliProphecy->reveal());

        $subject->exec_INSERTquery('aTable', ['fieldblob' => $testStringWithBinary]);
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
        /** @var \TYPO3\CMS\Core\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, ['quoteStr'], [], '', false);
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
     * @expectedException \InvalidArgumentException
     */
    public function listQueryThrowsExceptionIfValueContainsComma()
    {
        /** @var \TYPO3\CMS\Core\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, ['quoteStr'], [], '', false);
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
                'AND'
            ],

            'One search word with special chars (for like)' => [
                '(pages.title LIKE \'%TYPO3\\_100\\%%\')',
                ['TYPO3_100%'],
                ['title'],
                'pages',
                'AND'
            ],

            'One search word in multiple fields' => [
                '(pages.title LIKE \'%TYPO3%\' OR pages.keyword LIKE \'%TYPO3%\' OR pages.description LIKE \'%TYPO3%\')',
                ['TYPO3'],
                ['title', 'keyword', 'description'],
                'pages',
                'AND'
            ],

            'Multiple search words in one field with AND constraint' => [
                '(pages.title LIKE \'%TYPO3%\') AND (pages.title LIKE \'%is%\') AND (pages.title LIKE \'%great%\')',
                ['TYPO3', 'is', 'great'],
                ['title'],
                'pages',
                'AND'
            ],

            'Multiple search words in one field with OR constraint' => [
                '(pages.title LIKE \'%TYPO3%\') OR (pages.title LIKE \'%is%\') OR (pages.title LIKE \'%great%\')',
                ['TYPO3', 'is', 'great'],
                ['title'],
                'pages',
                'OR'
            ],

            'Multiple search words in multiple fields with AND constraint' => [
                '(pages.title LIKE \'%TYPO3%\' OR pages.keywords LIKE \'%TYPO3%\' OR pages.description LIKE \'%TYPO3%\') AND ' .
                    '(pages.title LIKE \'%is%\' OR pages.keywords LIKE \'%is%\' OR pages.description LIKE \'%is%\') AND ' .
                    '(pages.title LIKE \'%great%\' OR pages.keywords LIKE \'%great%\' OR pages.description LIKE \'%great%\')',
                ['TYPO3', 'is', 'great'],
                ['title', 'keywords', 'description'],
                'pages',
                'AND'
            ],

            'Multiple search words in multiple fields with OR constraint' => [
                '(pages.title LIKE \'%TYPO3%\' OR pages.keywords LIKE \'%TYPO3%\' OR pages.description LIKE \'%TYPO3%\') OR ' .
                    '(pages.title LIKE \'%is%\' OR pages.keywords LIKE \'%is%\' OR pages.description LIKE \'%is%\') OR ' .
                    '(pages.title LIKE \'%great%\' OR pages.keywords LIKE \'%great%\' OR pages.description LIKE \'%great%\')',
                ['TYPO3', 'is', 'great'],
                ['title', 'keywords', 'description'],
                'pages',
                'OR'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider searchQueryDataProvider
     */
    public function searchQueryCreatesQuery($expectedResult, $searchWords, $fields, $table, $constraint)
    {
        /** @var \TYPO3\CMS\Core\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, ['quoteStr'], [], '', false);
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
        /** @var \TYPO3\CMS\Core\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, ['dummy'], [], '', false);
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
            'single ORDER BY' => ['ORDER BY name, tstamp', 'name, tstamp'],
            'single ORDER BY in lower case' => ['order by name, tstamp', 'name, tstamp'],
            'ORDER BY with additional space behind' => ['ORDER BY  name, tstamp', 'name, tstamp'],
            'ORDER BY without space between the words' => ['ORDERBY name, tstamp', 'name, tstamp'],
            'ORDER BY added twice' => ['ORDER BY ORDER BY name, tstamp', 'name, tstamp'],
            'ORDER BY added twice without spaces in the first occurrence' => ['ORDERBY ORDER BY  name, tstamp', 'name, tstamp'],
            'ORDER BY added twice without spaces in the second occurrence' => ['ORDER BYORDERBY name, tstamp', 'name, tstamp'],
            'ORDER BY added twice without spaces' => ['ORDERBYORDERBY name, tstamp', 'name, tstamp'],
            'ORDER BY added twice without spaces afterwards' => ['ORDERBYORDERBYname, tstamp', 'name, tstamp'],
        ];
    }

    /**
     * @test
     * @dataProvider stripOrderByForOrderByKeywordDataProvider
     * @param string $orderByClause The clause to test
     * @param string $expectedResult The expected result
     * @return void
     */
    public function stripOrderByForOrderByKeyword($orderByClause, $expectedResult)
    {
        /** @var \TYPO3\CMS\Core\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, ['dummy'], [], '', false);
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
            'single GROUP BY' => ['GROUP BY name, tstamp', 'name, tstamp'],
            'single GROUP BY in lower case' => ['group by name, tstamp', 'name, tstamp'],
            'GROUP BY with additional space behind' => ['GROUP BY  name, tstamp', 'name, tstamp'],
            'GROUP BY without space between the words' => ['GROUPBY name, tstamp', 'name, tstamp'],
            'GROUP BY added twice' => ['GROUP BY GROUP BY name, tstamp', 'name, tstamp'],
            'GROUP BY added twice without spaces in the first occurrence' => ['GROUPBY GROUP BY  name, tstamp', 'name, tstamp'],
            'GROUP BY added twice without spaces in the second occurrence' => ['GROUP BYGROUPBY name, tstamp', 'name, tstamp'],
            'GROUP BY added twice without spaces' => ['GROUPBYGROUPBY name, tstamp', 'name, tstamp'],
            'GROUP BY added twice without spaces afterwards' => ['GROUPBYGROUPBYname, tstamp', 'name, tstamp'],
        ];
    }

    /**
     * @test
     * @dataProvider stripGroupByForGroupByKeywordDataProvider
     * @param string $groupByClause The clause to test
     * @param string $expectedResult The expected result
     * @return void
     */
    public function stripGroupByForGroupByKeyword($groupByClause, $expectedResult)
    {
        /** @var \TYPO3\CMS\Core\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, ['dummy'], [], '', false);
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
                [1, 2, 3]
            ],
            'string array' => [
                ['2', '4', '8'],
                [2, 4, 8]
            ],
            'string array with letters #1' => [
                ['3', '6letters', '12'],
                [3, 6, 12]
            ],
            'string array with letters #2' => [
                ['3', 'letters6', '12'],
                [3, 0, 12]
            ],
            'string array with letters #3' => [
                ['3', '6letters4', '12'],
                [3, 6, 12]
            ],
            'associative array' => [
                ['apples' => 3, 'bananas' => 4, 'kiwis' => 9],
                ['apples' => 3, 'bananas' => 4, 'kiwis' => 9]
            ],
            'associative string array' => [
                ['apples' => '1', 'bananas' => '5', 'kiwis' => '7'],
                ['apples' => 1, 'bananas' => 5, 'kiwis' => 7]
            ],
            'associative string array with letters #1' => [
                ['apples' => '1', 'bananas' => 'no5', 'kiwis' => '7'],
                ['apples' => 1, 'bananas' => 0, 'kiwis' => 7]
            ],
            'associative string array with letters #2' => [
                ['apples' => '1', 'bananas' => '5yes', 'kiwis' => '7'],
                ['apples' => 1, 'bananas' => 5, 'kiwis' => 7]
            ],
            'associative string array with letters #3' => [
                ['apples' => '1', 'bananas' => '5yes9', 'kiwis' => '7'],
                ['apples' => 1, 'bananas' => 5, 'kiwis' => 7]
            ],
            'multidimensional associative array' => [
                ['apples' => '1', 'bananas' => [3, 4], 'kiwis' => '7'],
                // intval(array(...)) is 1
                // But by specification "cleanIntArray" should only get used on one-dimensional arrays
                ['apples' => 1, 'bananas' => 1, 'kiwis' => 7]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider cleanIntArrayDataProvider
     * @param array $exampleData The array to sanitize
     * @param array $expectedResult The expected result
     * @return void
     */
    public function cleanIntArray($exampleData, $expectedResult)
    {
        /** @var \TYPO3\CMS\Core\Database\DatabaseConnection $subject */
        $subject = new \TYPO3\CMS\Core\Database\DatabaseConnection();
        $sanitizedArray = $subject->cleanIntArray($exampleData);
        $this->assertEquals($expectedResult, $sanitizedArray);
    }

    /**
     * @test
     */
    public function sqlForSelectMmQuery()
    {
        $subject = new \TYPO3\CMS\Core\Database\DatabaseConnection();
        $result = $subject->SELECT_mm_query('*', 'sys_category', 'sys_category_record_mm', 'tt_content', 'AND sys_category.uid = 1', '', 'sys_category.title DESC');
        $expected = 'SELECT * FROM sys_category,sys_category_record_mm,tt_content WHERE sys_category.uid=sys_category_record_mm.uid_local AND tt_content.uid=sys_category_record_mm.uid_foreign AND sys_category.uid = 1 ORDER BY sys_category.title DESC';
        $this->assertEquals($expected, $result);
    }
}
