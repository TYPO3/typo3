<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Xavier Perseguers <typo3@perseguers.ch>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/


require_once('BaseTestCase.php');

/**
 * Testcase for class ux_t3lib_sqlparser
 * 
 * $Id$
 *
 * @author Xavier Perseguers <typo3@perseguers.ch>
 *
 * @package TYPO3
 * @subpackage dbal
 */
class sqlparser_general_testcase extends BaseTestCase {

	/**
	 * @var ux_t3lib_sqlparser (extended to make protected methods public)
	 */
	protected $fixture;

	/**
	 * Prepares the environment before running a test.
	 */
	public function setUp() {
		$className = self::buildAccessibleProxy('ux_t3lib_sqlparser');
		$this->fixture = new $className;
	}

	/**
	 * Cleans up the environment after running a test.
	 */
	public function tearDown() {
		unset($this->fixture);
	}

	/**
	 * Cleans a SQL query.
	 *  
	 * @param mixed $sql
	 * @return mixed (string or array)
	 */
	private function cleanSql($sql) {
		if (!is_string($sql)) {
			return $sql;
		}

		$sql = str_replace("\n", ' ', $sql);
		$sql = preg_replace('/\s+/', ' ', $sql);
		return $sql;
	}

	/**
	 * @test
	 */
	public function canExtractPartsOfAQuery() {
		$parseString = "SELECT   *\nFROM pages WHERE pid IN (1,2,3,4)";
		$regex = '^SELECT[[:space:]]+(.*)[[:space:]]+';
		$trimAll = TRUE;
		$fields = $this->fixture->_callRef('nextPart', $parseString, $regex, $trimAll);

		$this->assertEquals(
			'*',
			$fields
		);
		$this->assertEquals(
			'FROM pages WHERE pid IN (1,2,3,4)',
			$parseString
		);

		$regex = '^FROM ([^)]+) WHERE';
		$table = $this->fixture->_callRef('nextPart', $parseString, $regex);

		$this->assertEquals(
			'pages',
			$table
		);
		$this->assertEquals(
			'pages WHERE pid IN (1,2,3,4)',
			$parseString
		);
	}

	/**
	 * @test
	 */
	public function canGetIntegerValue() {
		$parseString = '1024';
		$value = $this->fixture->_callRef('getValue', $parseString);
		$expected = array(1024);

		$this->assertEquals($expected, $value);
	}

	/**
	 * @test
	 */
	public function canGetStringValue() {
		$parseString = '"some owner\\\' string"';
		$value = $this->fixture->_callRef('getValue', $parseString);
		$expected = array('some owner\' string', '"');

		$this->assertEquals($expected, $value);
	}

	/**
	 * @test
	 */
	public function canGetListOfValues() {
		$parseString = '( 1,   2, 3  ,4)';
		$operator = 'IN';
		$values = $this->fixture->_callRef('getValue', $parseString, $operator);
		$expected = array(
			array(1),
			array(2),
			array(3),
			array(4)
		);

		$this->assertEquals($expected, $values);
	}

	/**
	 * @test
	 */
	public function parseFromTablesWithInnerJoinReturnsArray() {
		$parseString = 'be_users INNER JOIN pages ON pages.cruser_id = be_users.uid';
		$tables = $this->fixture->parseFromTables($parseString);

		$this->assertTrue(is_array($tables), $tables);
		$this->assertTrue(empty($parseString), 'parseString is not empty');
	}

	/**
	 * @test
	 */
	public function parseFromTablesWithLeftOuterJoinReturnsArray() {
		$parseString = 'be_users LEFT OUTER JOIN pages ON be_users.uid = pages.cruser_id';
		$tables = $this->fixture->parseFromTables($parseString);

		$this->assertTrue(is_array($tables), $tables);
		$this->assertTrue(empty($parseString), 'parseString is not empty');
	}

	/**
	 * @test
	 */
	public function parseFromTablesWithMultipleJoinsReturnsArray() {
		$parseString = 'be_users LEFT OUTER JOIN pages ON be_users.uid = pages.cruser_id INNER JOIN cache_pages cp ON cp.page_id = pages.uid';
		$tables = $this->fixture->parseFromTables($parseString);

		$this->assertTrue(is_array($tables), $tables);
		$this->assertTrue(empty($parseString), 'parseString is not empty');
	}

	/**
	 * @test
	 */
	public function parseWhereClauseReturnsArray() {
		$parseString = 'uid IN (1,2) AND (starttime < ' . time() . ' OR cruser_id + 10 < 20)';
		$where = $this->fixture->parseWhereClause($parseString);

		$this->assertTrue(is_array($where), $where);
		$this->assertTrue(empty($parseString), 'parseString is not empty');
	}

	/**
	 * @test
	 */
	public function canSelectAllFieldsFromPages() {
		$sql = 'SELECT * FROM pages';
		$expected = $sql;
		$actual = $this->cleanSql($this->fixture->debug_testSQL($sql)); 

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function canUseInnerJoinInSelect() {
		$sql = 'SELECT pages.uid, be_users.username FROM be_users INNER JOIN pages ON pages.cruser_id = be_users.uid';
		$expected = 'SELECT pages.uid, be_users.username FROM be_users INNER JOIN pages ON pages.cruser_id=be_users.uid';
		$actual = $this->cleanSql($this->fixture->debug_testSQL($sql)); 

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function canUseMultipleInnerJoinsInSelect() {
		$sql = 'SELECT * FROM tt_news_cat INNER JOIN tt_news_cat_mm ON tt_news_cat.uid = tt_news_cat_mm.uid_foreign INNER JOIN tt_news ON tt_news.uid = tt_news_cat_mm.uid_local';
		$expected = 'SELECT * FROM tt_news_cat INNER JOIN tt_news_cat_mm ON tt_news_cat.uid=tt_news_cat_mm.uid_foreign INNER JOIN tt_news ON tt_news.uid=tt_news_cat_mm.uid_local';
		$actual = $this->cleanSql($this->fixture->debug_testSQL($sql)); 

		$this->assertEquals($expected, $actual);
	}

}
?>