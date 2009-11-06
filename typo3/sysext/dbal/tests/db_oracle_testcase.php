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
require_once('FakeDbConnection.php');

/**
 * Testcase for class ux_t3lib_db. Testing Oracle database handling.
 * 
 * $Id$
 *
 * @author Xavier Perseguers <typo3@perseguers.ch>
 *
 * @package TYPO3
 * @subpackage dbal
 */
class db_oracle_testcase extends BaseTestCase {

	/**
	 * @var ux_t3lib_db (extended to make protected methods public)
	 */
	protected $fixture;

	/**
	 * @var array
	 */
	protected $dbalConfig;

	/**
	 * Prepares the environment before running a test.
	 */
	public function setUp() {
			// Backup DBAL configuration
		$this->dbalConfig = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dbal'];
			// Reconfigure DBAL to use Oracle
		require('fixtures/oci8.config.php');

		$className =  self::buildAccessibleProxy('ux_t3lib_db');
		$this->fixture = new $className;

			// Initialize a fake Oracle connection
		FakeDbConnection::connect($this->fixture, 'oci8');
		$this->assertTrue($this->fixture->handlerInstance['_DEFAULT']->isConnected());
	}

	/**
	 * Cleans up the environment after running a test.
	 */
	public function tearDown() {
			// Clear DBAL-generated cache files
		$this->fixture->clearCachedFieldInfo();
		unset($this->fixture);
			// Restore DBAL configuration
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dbal'] = $this->dbalConfig;
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
	public function configurationIsUsingAdodb() {
		$configuration = $this->fixture->conf['handlerCfg'];
		self::assertTrue(is_array($configuration) && count($configuration) > 0, 'No configuration found');
		self::assertEquals('adodb', $configuration['_DEFAULT']['type']);
	}

	/** 
	 * @test
	 */
	public function tablesWithMappingAreDetected() {
		$tablesWithMapping = array_keys($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dbal']['mapping']);

		foreach ($this->fixture->cache_fieldType as $table => $fieldTypes) {
			$tableDef = $this->fixture->_call('map_needMapping', $table);

			if (in_array($table, $tablesWithMapping)) {
				self::assertTrue(is_array($tableDef), 'Table ' . $table . ' was expected to need mapping');
			} else {
				self::assertFalse($tableDef, 'Table ' . $table . ' was not expected to need mapping');
			}
		}
	}

	/**
	 * @test
	 */
	public function selectQueryIsProperlyQuoted() {
		$query = $this->cleanSql($this->fixture->SELECTquery(
			'uid',					// select fields
			'tt_content',			// from table
			'pid=1',				// where clause
			'cruser_id',			// group by
			'tstamp'				// order by
		));
		$expected = 'SELECT "uid" FROM "tt_content" WHERE "pid" = 1 GROUP BY "cruser_id" ORDER BY "tstamp"';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=2438
	 */
	public function distinctFieldIsProperlyQuoted() {
		$query = $this->cleanSql($this->fixture->SELECTquery(
			'COUNT(DISTINCT pid)',	// select fields
			'tt_content',			// from table
			'1=1'					// where clause
		));
		$expected = 'SELECT COUNT(DISTINCT "pid") FROM "tt_content" WHERE 1 = 1';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=10411
	 * @remark Remapping is not expected here
	 */
	public function multipleInnerJoinsAreProperlyQuoted() {
		$query = $this->cleanSql($this->fixture->SELECTquery(
			'*',
			'tt_news_cat INNER JOIN tt_news_cat_mm ON tt_news_cat.uid = tt_news_cat_mm.uid_foreign INNER JOIN tt_news ON tt_news.uid = tt_news_cat_mm.uid_local',
			'1=1'
		));
		$expected = 'SELECT * FROM "tt_news_cat"';
		$expected .= ' INNER JOIN "tt_news_cat_mm" ON "tt_news_cat"."uid"="tt_news_cat_mm"."uid_foreign"';
		$expected .= ' INNER JOIN "tt_news" ON "tt_news"."uid"="tt_news_cat_mm"."uid_local"';
		$expected .= ' WHERE 1 = 1';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=10411
	 * @remark Remapping is expected here
	 */
	public function tablesAndFieldsAreRemappedInMultipleJoins() {
		$selectFields = '*';
		$fromTables = 'tt_news_cat INNER JOIN tt_news_cat_mm ON tt_news_cat.uid = tt_news_cat_mm.uid_foreign INNER JOIN tt_news ON tt_news.uid = tt_news_cat_mm.uid_local';
		$whereClause = '1=1';
		$groupBy = '';
		$orderBy = '';

		$this->fixture->_callRef('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);
		$query = $this->cleanSql($this->fixture->SELECTquery($selectFields, $fromTables, $whereClause, $groupBy, $orderBy));

		$expected = 'SELECT * FROM "XP_tt_news_cat"';
		$expected .= ' INNER JOIN "XP_tt_news_cat_mm" ON "XP_tt_news_cat"."XP_uid"="XP_tt_news_cat_mm"."XP_uid_foreign"';
		$expected .= ' INNER JOIN "XP_tt_news" ON "XP_tt_news"."XP_uid"="XP_tt_news_cat_mm"."XP_uid_local"';
		$expected .= ' WHERE 1 = 1';
		$this->assertEquals($expected, $query);
	}
}