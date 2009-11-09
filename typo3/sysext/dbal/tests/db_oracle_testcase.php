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
		$fromTables   = 'tt_news_cat INNER JOIN tt_news_cat_mm ON tt_news_cat.uid = tt_news_cat_mm.uid_foreign INNER JOIN tt_news ON tt_news.uid = tt_news_cat_mm.uid_local';
		$whereClause  = '1=1';
		$groupBy      = '';
		$orderBy      = '';

		$this->fixture->_callRef('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);
		$query = $this->cleanSql($this->fixture->SELECTquery($selectFields, $fromTables, $whereClause, $groupBy, $orderBy));

		$expected = 'SELECT * FROM "ext_tt_news_cat"';
		$expected .= ' INNER JOIN "ext_tt_news_cat_mm" ON "ext_tt_news_cat"."cat_uid"="ext_tt_news_cat_mm"."uid_foreign"';
		$expected .= ' INNER JOIN "ext_tt_news" ON "ext_tt_news"."news_uid"="ext_tt_news_cat_mm"."local_uid"';
		$expected .= ' WHERE 1 = 1';
		$this->assertEquals($expected, $query);
	}

	/** 
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=6198
	 */
	public function stringsWithinInClauseAreProperlyQuoted() {
		$query = $this->cleanSql($this->fixture->SELECTquery(
			'COUNT(DISTINCT tx_dam.uid) AS count',
			'tx_dam',
			'tx_dam.pid IN (1) AND tx_dam.file_type IN (\'gif\',\'png\',\'jpg\',\'jpeg\') AND tx_dam.deleted = 0'
		));
		$expected = 'SELECT COUNT(DISTINCT "tx_dam"."uid") AS "count" FROM "tx_dam"';
		$expected .= ' WHERE "tx_dam"."pid" IN (1) AND "tx_dam"."file_type" IN (\'gif\',\'png\',\'jpg\',\'jpeg\') AND "tx_dam"."deleted" = 0';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=6953
	 */
	public function fieldWithinSqlFunctionIsRemapped() {
		$selectFields = 'tstamp, script, SUM(exec_time) AS calc_sum, COUNT(*) AS qrycount, MAX(errorFlag) AS error';
		$fromTables   = 'tx_dbal_debuglog';
		$whereClause  = '1=1';
		$groupBy      = '';
		$orderBy      = '';

		$this->fixture->_callRef('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);
		$query = $this->cleanSql($this->fixture->SELECTquery($selectFields, $fromTables, $whereClause, $groupBy, $orderBy));

		$expected = 'SELECT "tstamp", "script", SUM("exec_time") AS "calc_sum", COUNT(*) AS "qrycount", MAX("errorflag") AS "error" FROM "tx_dbal_debuglog" WHERE 1 = 1';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=6953
	 */
	public function tableAndFieldWithinSqlFunctionIsRemapped() {
		$selectFields = 'MAX(tt_news_cat.uid) AS biggest_id';
		$fromTables   = 'tt_news_cat INNER JOIN tt_news_cat_mm ON tt_news_cat.uid = tt_news_cat_mm.uid_foreign';
		$whereClause  = 'tt_news_cat_mm.uid_local > 50';
		$groupBy      = '';
		$orderBy      = '';

		$this->fixture->_callRef('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);
		$query = $this->cleanSql($this->fixture->SELECTquery($selectFields, $fromTables, $whereClause, $groupBy, $orderBy));

		$expected = 'SELECT MAX("ext_tt_news_cat"."cat_uid") AS "biggest_id" FROM "ext_tt_news_cat"';
		$expected .= ' INNER JOIN "ext_tt_news_cat_mm" ON "ext_tt_news_cat"."cat_uid"="ext_tt_news_cat_mm"."uid_foreign"';
		$expected .= ' WHERE "ext_tt_news_cat_mm"."local_uid" > 50';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=12515
	 * @remark Remapping is not expected here
	 */
	public function concatAfterLikeOperatorIsProperlyQuoted() {
		$query = $this->cleanSql($this->fixture->SELECTquery(
			'*',
			'sys_refindex, tx_dam_file_tracking',
			'sys_refindex.tablename = \'tx_dam_file_tracking\''
			. ' AND sys_refindex.ref_string LIKE CONCAT(tx_dam_file_tracking.file_path, tx_dam_file_tracking.file_name)'
		));
		$expected = 'SELECT * FROM "sys_refindex", "tx_dam_file_tracking" WHERE "sys_refindex"."tablename" = \'tx_dam_file_tracking\'';
		$expected .= ' AND "sys_refindex"."ref_string" LIKE CONCAT("tx_dam_file_tracking"."file_path","tx_dam_file_tracking"."file_name")';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=12515
	 * @remark Remapping is expected here
	 */
	public function concatAfterLikeOperatorIsRemapped() {
		$selectFields = '*';
		$fromTables   = 'sys_refindex, tx_dam_file_tracking';
		$whereClause  = 'sys_refindex.tablename = \'tx_dam_file_tracking\''
							. ' AND sys_refindex.ref_string LIKE CONCAT(tx_dam_file_tracking.file_path, tx_dam_file_tracking.file_name)';
		$groupBy      = '';
		$orderBy      = '';

		$this->fixture->_callRef('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);
		$query = $this->cleanSql($this->fixture->SELECTquery($selectFields, $fromTables, $whereClause, $groupBy, $orderBy));

		$expected = 'SELECT * FROM "sys_refindex", "tx_dam_file_tracking" WHERE "sys_refindex"."tablename" = \'tx_dam_file_tracking\'';
		$expected .= ' AND "sys_refindex"."ref_string" LIKE CONCAT("tx_dam_file_tracking"."path","tx_dam_file_tracking"."filename")';
		$this->assertEquals($expected, $query);
	}
}