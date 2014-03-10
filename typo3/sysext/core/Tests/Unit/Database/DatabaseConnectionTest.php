<?php
namespace TYPO3\CMS\Core\Tests\Unit\Database;

/***************************************************************
 * Copyright notice
 *
 * (c) 2010-2013 Ernesto Baschny (ernst@cron-it.de)
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Test case
 *
 */
class DatabaseConnectionTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	//////////////////////////////////////////////////
	// Write/Read tests for charsets and binaries
	//////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function storedFullAsciiRangeCallsLinkObjectWithGivenData() {
		$binaryString = '';
		for ($i = 0; $i < 256; $i++) {
			$binaryString .= chr($i);
		}

		/** @var \TYPO3\CMS\Core\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
		$subject = $this->getAccessibleMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array('fullQuoteStr'), array(), '', FALSE);
		$subject->_set('isConnected', TRUE);
		$subject
			->expects($this->any())
			->method('fullQuoteStr')
			->will($this->returnCallback(function ($data) {
				return $data;
			}));
		$mysqliMock = $this->getMock('mysqli');
		$mysqliMock
			->expects($this->once())
			->method('query')
			->with('INSERT INTO aTable (fieldblob) VALUES (' . $binaryString . ')');
		$subject->_set('link', $mysqliMock);

		$subject->exec_INSERTquery('aTable', array('fieldblob' => $binaryString));
	}

	/**
	 * @test
	 */
	public function storedGzipCompressedDataReturnsSameData() {
		$testStringWithBinary = @gzcompress('sdfkljer4587');

		/** @var \TYPO3\CMS\Core\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
		$subject = $this->getAccessibleMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array('fullQuoteStr'), array(), '', FALSE);
		$subject->_set('isConnected', TRUE);
		$subject
			->expects($this->any())
			->method('fullQuoteStr')
			->will($this->returnCallback(function ($data) {
				return $data;
			}));
		$mysqliMock = $this->getMock('mysqli');
		$mysqliMock
			->expects($this->once())
			->method('query')
			->with('INSERT INTO aTable (fieldblob) VALUES (' . $testStringWithBinary . ')');
		$subject->_set('link', $mysqliMock);

		$subject->exec_INSERTquery('aTable', array('fieldblob' => $testStringWithBinary));
	}


	////////////////////////////////
	// Tests concerning listQuery
	////////////////////////////////

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/23253
	 */
	public function listQueryWithIntegerCommaAsValue() {
		/** @var \TYPO3\CMS\Core\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
		$subject = $this->getAccessibleMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array('quoteStr'), array(), '', FALSE);
		$subject->_set('isConnected', TRUE);
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
	public function listQueryThrowsExceptionIfValueContainsComma() {
		/** @var \TYPO3\CMS\Core\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
		$subject = $this->getAccessibleMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array('quoteStr'), array(), '', FALSE);
		$subject->_set('isConnected', TRUE);
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
	public function searchQueryDataProvider() {
		return array(
			'One search word in one field' => array(
				'(pages.title LIKE \'%TYPO3%\')',
				array('TYPO3'),
				array('title'),
				'pages',
				'AND'
			),

			'One search word in multiple fields' => array(
				'(pages.title LIKE \'%TYPO3%\' OR pages.keyword LIKE \'%TYPO3%\' OR pages.description LIKE \'%TYPO3%\')',
				array('TYPO3'),
				array('title', 'keyword', 'description'),
				'pages',
				'AND'
			),

			'Multiple search words in one field with AND constraint' => array(
				'(pages.title LIKE \'%TYPO3%\') AND (pages.title LIKE \'%is%\') AND (pages.title LIKE \'%great%\')',
				array('TYPO3', 'is', 'great'),
				array('title'),
				'pages',
				'AND'
			),

			'Multiple search words in one field with OR constraint' => array(
				'(pages.title LIKE \'%TYPO3%\') OR (pages.title LIKE \'%is%\') OR (pages.title LIKE \'%great%\')',
				array('TYPO3', 'is', 'great'),
				array('title'),
				'pages',
				'OR'
			),

			'Multiple search words in multiple fields with AND constraint' => array(
				'(pages.title LIKE \'%TYPO3%\' OR pages.keywords LIKE \'%TYPO3%\' OR pages.description LIKE \'%TYPO3%\') AND ' .
					'(pages.title LIKE \'%is%\' OR pages.keywords LIKE \'%is%\' OR pages.description LIKE \'%is%\') AND ' .
					'(pages.title LIKE \'%great%\' OR pages.keywords LIKE \'%great%\' OR pages.description LIKE \'%great%\')',
				array('TYPO3', 'is', 'great'),
				array('title', 'keywords', 'description'),
				'pages',
				'AND'
			),

			'Multiple search words in multiple fields with OR constraint' => array(
				'(pages.title LIKE \'%TYPO3%\' OR pages.keywords LIKE \'%TYPO3%\' OR pages.description LIKE \'%TYPO3%\') OR ' .
					'(pages.title LIKE \'%is%\' OR pages.keywords LIKE \'%is%\' OR pages.description LIKE \'%is%\') OR ' .
					'(pages.title LIKE \'%great%\' OR pages.keywords LIKE \'%great%\' OR pages.description LIKE \'%great%\')',
				array('TYPO3', 'is', 'great'),
				array('title', 'keywords', 'description'),
				'pages',
				'OR'
			),
		);
	}

	/**
	 * @test
	 * @dataProvider searchQueryDataProvider
	 */
	public function searchQueryCreatesQuery($expectedResult, $searchWords, $fields, $table, $constraint) {
		/** @var \TYPO3\CMS\Core\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $subject */
		$subject = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array('quoteStr'), array(), '', FALSE);
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
	public function escapeStringForLikeComparison() {
		/** @var \TYPO3\CMS\Core\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $subject */
		$subject = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array('dummy'), array(), '', FALSE);
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
	public function stripOrderByForOrderByKeywordDataProvider() {
		return array(
			'single ORDER BY' => array('ORDER BY name, tstamp', 'name, tstamp'),
			'single ORDER BY in lower case' => array('order by name, tstamp', 'name, tstamp'),
			'ORDER BY with additional space behind' => array('ORDER BY  name, tstamp', 'name, tstamp'),
			'ORDER BY without space between the words' => array('ORDERBY name, tstamp', 'name, tstamp'),
			'ORDER BY added twice' => array('ORDER BY ORDER BY name, tstamp', 'name, tstamp'),
			'ORDER BY added twice without spaces in the first occurrence' => array('ORDERBY ORDER BY  name, tstamp', 'name, tstamp'),
			'ORDER BY added twice without spaces in the second occurrence' => array('ORDER BYORDERBY name, tstamp', 'name, tstamp'),
			'ORDER BY added twice without spaces' => array('ORDERBYORDERBY name, tstamp', 'name, tstamp'),
			'ORDER BY added twice without spaces afterwards' => array('ORDERBYORDERBYname, tstamp', 'name, tstamp'),
		);
	}

	/**
	 * @test
	 * @dataProvider stripOrderByForOrderByKeywordDataProvider
	 * @param string $orderByClause The clause to test
	 * @param string $expectedResult The expected result
	 * @return void
	 */
	public function stripOrderByForOrderByKeyword($orderByClause, $expectedResult) {
		/** @var \TYPO3\CMS\Core\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $subject */
		$subject = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array('dummy'), array(), '', FALSE);
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
	public function stripGroupByForGroupByKeywordDataProvider() {
		return array(
			'single GROUP BY' => array('GROUP BY name, tstamp', 'name, tstamp'),
			'single GROUP BY in lower case' => array('group by name, tstamp', 'name, tstamp'),
			'GROUP BY with additional space behind' => array('GROUP BY  name, tstamp', 'name, tstamp'),
			'GROUP BY without space between the words' => array('GROUPBY name, tstamp', 'name, tstamp'),
			'GROUP BY added twice' => array('GROUP BY GROUP BY name, tstamp', 'name, tstamp'),
			'GROUP BY added twice without spaces in the first occurrence' => array('GROUPBY GROUP BY  name, tstamp', 'name, tstamp'),
			'GROUP BY added twice without spaces in the second occurrence' => array('GROUP BYGROUPBY name, tstamp', 'name, tstamp'),
			'GROUP BY added twice without spaces' => array('GROUPBYGROUPBY name, tstamp', 'name, tstamp'),
			'GROUP BY added twice without spaces afterwards' => array('GROUPBYGROUPBYname, tstamp', 'name, tstamp'),
		);
	}

	/**
	 * @test
	 * @dataProvider stripGroupByForGroupByKeywordDataProvider
	 * @param string $groupByClause The clause to test
	 * @param string $expectedResult The expected result
	 * @return void
	 */
	public function stripGroupByForGroupByKeyword($groupByClause, $expectedResult) {
		/** @var \TYPO3\CMS\Core\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $subject */
		$subject = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array('dummy'), array(), '', FALSE);
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
	public function cleanIntArrayDataProvider() {
		return array(
			'simple array' => array(
				array(1, 2, 3),
				array(1, 2, 3)
			),
			'string array' => array(
				array('2', '4', '8'),
				array(2, 4, 8)
			),
			'string array with letters #1' => array(
				array('3', '6letters', '12'),
				array(3, 6, 12)
			),
			'string array with letters #2' => array(
				array('3', 'letters6', '12'),
				array(3, 0, 12)
			),
			'string array with letters #3' => array(
				array('3', '6letters4', '12'),
				array(3, 6, 12)
			),
			'associative array' => array(
				array('apples' => 3, 'bananas' => 4, 'kiwis' => 9),
				array('apples' => 3, 'bananas' => 4, 'kiwis' => 9)
			),
			'associative string array' => array(
				array('apples' => '1', 'bananas' => '5', 'kiwis' => '7'),
				array('apples' => 1, 'bananas' => 5, 'kiwis' => 7)
			),
			'associative string array with letters #1' => array(
				array('apples' => '1', 'bananas' => 'no5', 'kiwis' => '7'),
				array('apples' => 1, 'bananas' => 0, 'kiwis' => 7)
			),
			'associative string array with letters #2' => array(
				array('apples' => '1', 'bananas' => '5yes', 'kiwis' => '7'),
				array('apples' => 1, 'bananas' => 5, 'kiwis' => 7)
			),
			'associative string array with letters #3' => array(
				array('apples' => '1', 'bananas' => '5yes9', 'kiwis' => '7'),
				array('apples' => 1, 'bananas' => 5, 'kiwis' => 7)
			),
			'multidimensional associative array' => array(
				array('apples' => '1', 'bananas' => array(3, 4), 'kiwis' => '7'),
				// intval(array(...)) is 1
				// But by specification "cleanIntArray" should only get used on one-dimensional arrays
				array('apples' => 1, 'bananas' => 1, 'kiwis' => 7)
			),
		);
	}

	/**
	 * @test
	 * @dataProvider cleanIntArrayDataProvider
	 * @param array $exampleData The array to sanitize
	 * @param array $expectedResult The expected result
	 * @return void
	 */
	public function cleanIntArray($exampleData, $expectedResult) {
		/** @var \TYPO3\CMS\Core\Database\DatabaseConnection $subject */
		$subject = new \TYPO3\CMS\Core\Database\DatabaseConnection();
		$sanitizedArray = $subject->cleanIntArray($exampleData);
		$this->assertEquals($expectedResult, $sanitizedArray);
	}

}