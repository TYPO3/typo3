<?php
namespace TYPO3\CMS\Dbal\Tests\Unit\Database;

/**
 * Test Oracle database handling.
 *
 * @author Xavier Perseguers <xavier@typo3.org>
 */
class DatabaseConnectionOracleTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $db;

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
		// Backup database connection
		$this->db = $GLOBALS['TYPO3_DB'];
		// Reconfigure DBAL to use Oracle
		require 'Fixtures/oci8.config.php';
		$className = self::buildAccessibleProxy('TYPO3\\CMS\\Dbal\\Database\\DatabaseConnection');
		$GLOBALS['TYPO3_DB'] = new $className();
		$parserClassName = self::buildAccessibleProxy('TYPO3\\CMS\\Dbal\\Database\\SqlParser');
		$GLOBALS['TYPO3_DB']->SQLparser = new $parserClassName();
		$this->assertFalse($GLOBALS['TYPO3_DB']->isConnected());
		// Initialize a fake Oracle connection
		\TYPO3\CMS\Dbal\Tests\Unit\Database\FakeDatabaseConnection::connect($GLOBALS['TYPO3_DB'], 'oci8');
		$this->assertTrue($GLOBALS['TYPO3_DB']->isConnected());
	}

	/**
	 * Cleans up the environment after running a test.
	 */
	public function tearDown() {
		// Clear DBAL-generated cache files
		$GLOBALS['TYPO3_DB']->clearCachedFieldInfo();
		// Restore DBAL configuration
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dbal'] = $this->dbalConfig;
		// Restore DB connection
		$GLOBALS['TYPO3_DB'] = $this->db;
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
		$sql = str_replace('
', ' ', $sql);
		$sql = preg_replace('/\\s+/', ' ', $sql);
		return trim($sql);
	}

	/**
	 * @test
	 */
	public function configurationIsUsingAdodbAndDriverOci8() {
		$configuration = $GLOBALS['TYPO3_DB']->conf['handlerCfg'];
		$this->assertTrue(is_array($configuration) && count($configuration) > 0, 'No configuration found');
		$this->assertEquals('adodb', $configuration['_DEFAULT']['type']);
		$this->assertTrue($GLOBALS['TYPO3_DB']->runningADOdbDriver('oci8') !== FALSE, 'Not using oci8 driver');
	}

	/**
	 * @test
	 */
	public function tablesWithMappingAreDetected() {
		$tablesWithMapping = array_keys($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dbal']['mapping']);
		foreach ($GLOBALS['TYPO3_DB']->cache_fieldType as $table => $fieldTypes) {
			$tableDef = $GLOBALS['TYPO3_DB']->_call('map_needMapping', $table);
			if (in_array($table, $tablesWithMapping)) {
				self::assertTrue(is_array($tableDef), 'Table ' . $table . ' was expected to need mapping');
			} else {
				self::assertFalse($tableDef, 'Table ' . $table . ' was not expected to need mapping');
			}
		}
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=12897
	 */
	public function sqlHintIsRemoved() {
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('/*! SQL_NO_CACHE */ content', 'tx_realurl_urlencodecache', '1=1'));
		$expected = 'SELECT "content" FROM "tx_realurl_urlencodecache" WHERE 1 = 1';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 */
	public function canCompileInsertWithFields() {
		$parseString = 'INSERT INTO static_territories (uid, pid, tr_iso_nr, tr_parent_iso_nr, tr_name_en) ';
		$parseString .= 'VALUES (\'1\', \'0\', \'2\', \'0\', \'Africa\');';
		$components = $GLOBALS['TYPO3_DB']->SQLparser->_callRef('parseINSERT', $parseString);
		$this->assertTrue(is_array($components), $components);
		$insert = $GLOBALS['TYPO3_DB']->SQLparser->_callRef('compileINSERT', $components);
		$expected = array(
			'uid' => '1',
			'pid' => '0',
			'tr_iso_nr' => '2',
			'tr_parent_iso_nr' => '0',
			'tr_name_en' => 'Africa'
		);
		$this->assertEquals($expected, $insert);
	}

	/**
	 * @test
	 */
	public function canCompileExtendedInsert() {
		$parseString = 'INSERT INTO static_territories VALUES (\'1\', \'0\', \'2\', \'0\', \'Africa\'),(\'2\', \'0\', \'9\', \'0\', \'Oceania\'),' . '(\'3\', \'0\', \'19\', \'0\', \'Americas\'),(\'4\', \'0\', \'142\', \'0\', \'Asia\');';
		$components = $GLOBALS['TYPO3_DB']->SQLparser->_callRef('parseINSERT', $parseString);
		$this->assertTrue(is_array($components), $components);
		$insert = $GLOBALS['TYPO3_DB']->SQLparser->_callRef('compileINSERT', $components);
		$this->assertEquals(4, count($insert));
		for ($i = 0; $i < count($insert); $i++) {
			foreach (\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', 'uid,pid,tr_iso_nr,tr_parent_iso_nr,tr_name_en') as $field) {
				$this->assertTrue(isset($insert[$i][$field]), 'Could not find ' . $field . ' column');
			}
		}
	}

	/**
	 * @test
	 */
	public function sqlForInsertWithMultipleRowsIsValid() {
		$fields = array('uid', 'pid', 'title', 'body');
		$rows = array(
			array('1', '2', 'Title #1', 'Content #1'),
			array('3', '4', 'Title #2', 'Content #2'),
			array('5', '6', 'Title #3', 'Content #3')
		);
		$query = $GLOBALS['TYPO3_DB']->INSERTmultipleRows('tt_content', $fields, $rows);
		$expected[0] = 'INSERT INTO "tt_content" ( "uid", "pid", "title", "body" ) VALUES ( \'1\', \'2\', \'Title #1\', \'Content #1\' )';
		$expected[1] = 'INSERT INTO "tt_content" ( "uid", "pid", "title", "body" ) VALUES ( \'3\', \'4\', \'Title #2\', \'Content #2\' )';
		$expected[2] = 'INSERT INTO "tt_content" ( "uid", "pid", "title", "body" ) VALUES ( \'5\', \'6\', \'Title #3\', \'Content #3\' )';
		$this->assertEquals(count($expected), count($query));
		for ($i = 0; $i < count($query); $i++) {
			$this->assertTrue(is_array($query[$i]), 'Expected array: ' . $query[$i]);
			$this->assertEquals(1, count($query[$i]));
			$this->assertEquals($expected[$i], $this->cleanSql($query[$i][0]));
		}
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=15535
	 */
	public function groupConditionsAreProperlyTransformed() {
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('*', 'pages', 'pid=0 AND pages.deleted=0 AND pages.hidden=0 AND pages.starttime<=1281620460 ' . 'AND (pages.endtime=0 OR pages.endtime>1281620460) AND NOT pages.t3ver_state>0 ' . 'AND pages.doktype<200 AND (pages.fe_group=\'\' OR pages.fe_group IS NULL OR ' . 'pages.fe_group=\'0\' OR FIND_IN_SET(\'0\',pages.fe_group) OR FIND_IN_SET(\'-1\',pages.fe_group))'));
		$expected = 'SELECT * FROM "pages" WHERE "pid" = 0 AND "pages"."deleted" = 0 AND "pages"."hidden" = 0 ' . 'AND "pages"."starttime" <= 1281620460 AND ("pages"."endtime" = 0 OR "pages"."endtime" > 1281620460) ' . 'AND NOT "pages"."t3ver_state" > 0 AND "pages"."doktype" < 200 AND ("pages"."fe_group" = \'\' ' . 'OR "pages"."fe_group" IS NULL OR "pages"."fe_group" = \'0\' OR \',\'||"pages"."fe_group"||\',\' LIKE \'%,0,%\' ' . 'OR \',\'||"pages"."fe_group"||\',\' LIKE \'%,-1,%\')';
		$this->assertEquals($expected, $query);
	}

	///////////////////////////////////////
	// Tests concerning quoting
	///////////////////////////////////////
	/**
	 * @test
	 */
	public function selectQueryIsProperlyQuoted() {
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('uid', 'tt_content', 'pid=1', 'cruser_id', 'tstamp'));
		$expected = 'SELECT "uid" FROM "tt_content" WHERE "pid" = 1 GROUP BY "cruser_id" ORDER BY "tstamp"';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 */
	public function truncateQueryIsProperlyQuoted() {
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->TRUNCATEquery('be_users'));
		$expected = 'TRUNCATE TABLE "be_users"';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=2438
	 */
	public function distinctFieldIsProperlyQuoted() {
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('COUNT(DISTINCT pid)', 'tt_content', '1=1'));
		$expected = 'SELECT COUNT(DISTINCT "pid") FROM "tt_content" WHERE 1 = 1';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=10411
	 * @remark Remapping is not expected here
	 */
	public function multipleInnerJoinsAreProperlyQuoted() {
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('*', 'tt_news_cat INNER JOIN tt_news_cat_mm ON tt_news_cat.uid = tt_news_cat_mm.uid_foreign INNER JOIN tt_news ON tt_news.uid = tt_news_cat_mm.uid_local', '1=1'));
		$expected = 'SELECT * FROM "tt_news_cat"';
		$expected .= ' INNER JOIN "tt_news_cat_mm" ON "tt_news_cat"."uid"="tt_news_cat_mm"."uid_foreign"';
		$expected .= ' INNER JOIN "tt_news" ON "tt_news"."uid"="tt_news_cat_mm"."uid_local"';
		$expected .= ' WHERE 1 = 1';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=6198
	 */
	public function stringsWithinInClauseAreProperlyQuoted() {
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('COUNT(DISTINCT tx_dam.uid) AS count', 'tx_dam', 'tx_dam.pid IN (1) AND tx_dam.file_type IN (\'gif\',\'png\',\'jpg\',\'jpeg\') AND tx_dam.deleted = 0'));
		$expected = 'SELECT COUNT(DISTINCT "tx_dam"."uid") AS "count" FROM "tx_dam"';
		$expected .= ' WHERE "tx_dam"."pid" IN (1) AND "tx_dam"."file_type" IN (\'gif\',\'png\',\'jpg\',\'jpeg\') AND "tx_dam"."deleted" = 0';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=12515
	 * @remark Remapping is not expected here
	 */
	public function concatAfterLikeOperatorIsProperlyQuoted() {
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('*', 'sys_refindex, tx_dam_file_tracking', 'sys_refindex.tablename = \'tx_dam_file_tracking\'' . ' AND sys_refindex.ref_string LIKE CONCAT(tx_dam_file_tracking.file_path, tx_dam_file_tracking.file_name)'));
		$expected = 'SELECT * FROM "sys_refindex", "tx_dam_file_tracking" WHERE "sys_refindex"."tablename" = \'tx_dam_file_tracking\'';
		$expected .= ' AND (instr(LOWER("sys_refindex"."ref_string"), concat("tx_dam_file_tracking"."file_path","tx_dam_file_tracking"."file_name"),1,1) > 0)';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=12231
	 */
	public function cachingFrameworkQueryIsProperlyQuoted() {
		$currentTime = time();
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('content', 'cache_hash', 'identifier = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('abbbabaf2d4b3f9a63e8dde781f1c106', 'cache_hash') . ' AND (crdate + lifetime >= ' . $currentTime . ' OR lifetime = 0)'));
		$expected = 'SELECT "content" FROM "cache_hash" WHERE "identifier" = \'abbbabaf2d4b3f9a63e8dde781f1c106\' AND ("crdate"+"lifetime" >= ' . $currentTime . ' OR "lifetime" = 0)';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=12231
	 */
	public function calculatedFieldsAreProperlyQuoted() {
		$currentTime = time();
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('identifier', 'cachingframework_cache_pages', 'crdate + lifetime < ' . $currentTime . ' AND lifetime > 0'));
		$expected = 'SELECT "identifier" FROM "cachingframework_cache_pages" WHERE "crdate"+"lifetime" < ' . $currentTime . ' AND "lifetime" > 0';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 */
	public function numericColumnsAreNotQuoted() {
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('1', 'be_users', 'username = \'_cli_scheduler\' AND admin = 0 AND be_users.deleted = 0'));
		$expected = 'SELECT 1 FROM "be_users" WHERE "username" = \'_cli_scheduler\' AND "admin" = 0 AND "be_users"."deleted" = 0';
		$this->assertEquals($expected, $query);
	}

	///////////////////////////////////////
	// Tests concerning remapping
	///////////////////////////////////////
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
		$remappedParameters = $GLOBALS['TYPO3_DB']->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->_call('SELECTqueryFromArray', $remappedParameters));
		$expected = 'SELECT * FROM "ext_tt_news_cat"';
		$expected .= ' INNER JOIN "ext_tt_news_cat_mm" ON "ext_tt_news_cat"."cat_uid"="ext_tt_news_cat_mm"."uid_foreign"';
		$expected .= ' INNER JOIN "ext_tt_news" ON "ext_tt_news"."news_uid"="ext_tt_news_cat_mm"."local_uid"';
		$expected .= ' WHERE 1 = 1';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=6953
	 */
	public function fieldWithinSqlFunctionIsRemapped() {
		$selectFields = 'tstamp, script, SUM(exec_time) AS calc_sum, COUNT(*) AS qrycount, MAX(errorFlag) AS error';
		$fromTables = 'tx_dbal_debuglog';
		$whereClause = '1=1';
		$groupBy = '';
		$orderBy = '';
		$remappedParameters = $GLOBALS['TYPO3_DB']->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->_call('SELECTqueryFromArray', $remappedParameters));
		$expected = 'SELECT "tstamp", "script", SUM("exec_time") AS "calc_sum", COUNT(*) AS "qrycount", MAX("errorflag") AS "error" FROM "tx_dbal_debuglog" WHERE 1 = 1';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=6953
	 */
	public function tableAndFieldWithinSqlFunctionIsRemapped() {
		$selectFields = 'MAX(tt_news_cat.uid) AS biggest_id';
		$fromTables = 'tt_news_cat INNER JOIN tt_news_cat_mm ON tt_news_cat.uid = tt_news_cat_mm.uid_foreign';
		$whereClause = 'tt_news_cat_mm.uid_local > 50';
		$groupBy = '';
		$orderBy = '';
		$remappedParameters = $GLOBALS['TYPO3_DB']->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->_call('SELECTqueryFromArray', $remappedParameters));
		$expected = 'SELECT MAX("ext_tt_news_cat"."cat_uid") AS "biggest_id" FROM "ext_tt_news_cat"';
		$expected .= ' INNER JOIN "ext_tt_news_cat_mm" ON "ext_tt_news_cat"."cat_uid"="ext_tt_news_cat_mm"."uid_foreign"';
		$expected .= ' WHERE "ext_tt_news_cat_mm"."local_uid" > 50';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=12515
	 * @remark Remapping is expected here
	 */
	public function concatAfterLikeOperatorIsRemapped() {
		$selectFields = '*';
		$fromTables = 'sys_refindex, tx_dam_file_tracking';
		$whereClause = 'sys_refindex.tablename = \'tx_dam_file_tracking\'' . ' AND sys_refindex.ref_string LIKE CONCAT(tx_dam_file_tracking.file_path, tx_dam_file_tracking.file_name)';
		$groupBy = '';
		$orderBy = '';
		$remappedParameters = $GLOBALS['TYPO3_DB']->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->_call('SELECTqueryFromArray', $remappedParameters));
		$expected = 'SELECT * FROM "sys_refindex", "tx_dam_file_tracking" WHERE "sys_refindex"."tablename" = \'tx_dam_file_tracking\'';
		$expected .= ' AND (instr(LOWER("sys_refindex"."ref_string"), concat("tx_dam_file_tracking"."path","tx_dam_file_tracking"."filename"),1,1) > 0)';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=5708
	 */
	public function fieldIsMappedOnRightSideOfAJoinCondition() {
		$selectFields = 'cpg_categories.uid, cpg_categories.name';
		$fromTables = 'cpg_categories, pages';
		$whereClause = 'pages.uid = cpg_categories.pid AND pages.deleted = 0 AND 1 = 1';
		$groupBy = '';
		$orderBy = 'cpg_categories.pos';
		$remappedParameters = $GLOBALS['TYPO3_DB']->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->_call('SELECTqueryFromArray', $remappedParameters));
		$expected = 'SELECT "cpg_categories"."uid", "cpg_categories"."name" FROM "cpg_categories", "my_pages" WHERE "my_pages"."page_uid" = "cpg_categories"."page_id"';
		$expected .= ' AND "my_pages"."deleted" = 0 AND 1 = 1 ORDER BY "cpg_categories"."pos"';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=14372
	 */
	public function fieldFromAliasIsRemapped() {
		$selectFields = 'news.uid';
		$fromTables = 'tt_news AS news';
		$whereClause = 'news.uid = 1';
		$groupBy = '';
		$orderBy = '';
		$remappedParameters = $GLOBALS['TYPO3_DB']->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->_call('SELECTqueryFromArray', $remappedParameters));
		$expected = 'SELECT "news"."news_uid" FROM "ext_tt_news" AS "news" WHERE "news"."news_uid" = 1';
		$this->assertEquals($expected, $query);
	}

	/**
	 * Trick here is that we already have a mapping for both table tt_news and table tt_news_cat
	 * (see tests/fixtures/oci8.config.php) which is used as alias name.
	 *
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=14372
	 */
	public function fieldFromAliasIsRemappedWithoutBeingTricked() {
		$selectFields = 'tt_news_cat.uid';
		$fromTables = 'tt_news AS tt_news_cat';
		$whereClause = 'tt_news_cat.uid = 1';
		$groupBy = '';
		$orderBy = '';
		$remappedParameters = $GLOBALS['TYPO3_DB']->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->_call('SELECTqueryFromArray', $remappedParameters));
		$expected = 'SELECT "tt_news_cat"."news_uid" FROM "ext_tt_news" AS "tt_news_cat" WHERE "tt_news_cat"."news_uid" = 1';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=14372
	 */
	public function aliasRemappingDoesNotAlterFurtherQueries() {
		$selectFields = 'foo.uid';
		$fromTables = 'tt_news AS foo';
		$whereClause = 'foo.uid = 1';
		$groupBy = '';
		$orderBy = '';
		// First call to possibly alter (in memory) the mapping from localconf.php
		$remappedParameters = $GLOBALS['TYPO3_DB']->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);
		$selectFields = 'uid';
		$fromTables = 'foo';
		$whereClause = 'uid = 1';
		$groupBy = '';
		$orderBy = '';
		$remappedParameters = $GLOBALS['TYPO3_DB']->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->_call('SELECTqueryFromArray', $remappedParameters));
		$expected = 'SELECT "uid" FROM "foo" WHERE "uid" = 1';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=14372
	 */
	public function fieldFromAliasInJoinIsRemapped() {
		$selectFields = 'cat.uid, cat_mm.uid_local, news.uid';
		$fromTables = 'tt_news_cat AS cat' . ' INNER JOIN tt_news_cat_mm AS cat_mm ON cat.uid = cat_mm.uid_foreign' . ' INNER JOIN tt_news AS news ON news.uid = cat_mm.uid_local';
		$whereClause = '1=1';
		$groupBy = '';
		$orderBy = '';
		$remappedParameters = $GLOBALS['TYPO3_DB']->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->_call('SELECTqueryFromArray', $remappedParameters));
		$expected = 'SELECT "cat"."cat_uid", "cat_mm"."local_uid", "news"."news_uid"';
		$expected .= ' FROM "ext_tt_news_cat" AS "cat"';
		$expected .= ' INNER JOIN "ext_tt_news_cat_mm" AS "cat_mm" ON "cat"."cat_uid"="cat_mm"."uid_foreign"';
		$expected .= ' INNER JOIN "ext_tt_news" AS "news" ON "news"."news_uid"="cat_mm"."local_uid"';
		$expected .= ' WHERE 1 = 1';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=14372
	 */
	public function aliasRemappingWithInSubqueryDoesNotAffectMainQuery() {
		$selectFields = 'foo.uid';
		$fromTables = 'tt_news AS foo INNER JOIN tt_news_cat_mm ON tt_news_cat_mm.uid_local = foo.uid';
		$whereClause = 'tt_news_cat_mm.uid_foreign IN (SELECT foo.uid FROM tt_news_cat AS foo WHERE foo.hidden = 0)';
		$groupBy = '';
		$orderBy = 'foo.uid';
		$remappedParameters = $GLOBALS['TYPO3_DB']->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->_call('SELECTqueryFromArray', $remappedParameters));
		$expected = 'SELECT "foo"."news_uid" FROM "ext_tt_news" AS "foo"';
		$expected .= ' INNER JOIN "ext_tt_news_cat_mm" ON "ext_tt_news_cat_mm"."local_uid"="foo"."news_uid"';
		$expected .= ' WHERE "ext_tt_news_cat_mm"."uid_foreign" IN (';
		$expected .= 'SELECT "foo"."cat_uid" FROM "ext_tt_news_cat" AS "foo" WHERE "foo"."hidden" = 0';
		$expected .= ')';
		$expected .= ' ORDER BY "foo"."news_uid"';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=14372
	 */
	public function aliasRemappingWithExistsSubqueryDoesNotAffectMainQuery() {
		$selectFields = 'foo.uid';
		$fromTables = 'tt_news AS foo INNER JOIN tt_news_cat_mm ON tt_news_cat_mm.uid_local = foo.uid';
		$whereClause = 'EXISTS (SELECT foo.uid FROM tt_news_cat AS foo WHERE foo.hidden = 0)';
		$groupBy = '';
		$orderBy = 'foo.uid';
		$remappedParameters = $GLOBALS['TYPO3_DB']->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->_call('SELECTqueryFromArray', $remappedParameters));
		$expected = 'SELECT "foo"."news_uid" FROM "ext_tt_news" AS "foo"';
		$expected .= ' INNER JOIN "ext_tt_news_cat_mm" ON "ext_tt_news_cat_mm"."local_uid"="foo"."news_uid"';
		$expected .= ' WHERE EXISTS (';
		$expected .= 'SELECT "foo"."cat_uid" FROM "ext_tt_news_cat" AS "foo" WHERE "foo"."hidden" = 0';
		$expected .= ')';
		$expected .= ' ORDER BY "foo"."news_uid"';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=14372
	 */
	public function aliasRemappingSupportsNestedSubqueries() {
		$selectFields = 'foo.uid';
		$fromTables = 'tt_news AS foo';
		$whereClause = 'uid IN (' . 'SELECT foobar.uid_local FROM tt_news_cat_mm AS foobar WHERE uid_foreign IN (' . 'SELECT uid FROM tt_news_cat WHERE deleted = 0' . '))';
		$groupBy = '';
		$orderBy = '';
		$remappedParameters = $GLOBALS['TYPO3_DB']->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->_call('SELECTqueryFromArray', $remappedParameters));
		$expected = 'SELECT "foo"."news_uid" FROM "ext_tt_news" AS "foo"';
		$expected .= ' WHERE "news_uid" IN (';
		$expected .= 'SELECT "foobar"."local_uid" FROM "ext_tt_news_cat_mm" AS "foobar" WHERE "uid_foreign" IN (';
		$expected .= 'SELECT "cat_uid" FROM "ext_tt_news_cat" WHERE "deleted" = 0';
		$expected .= ')';
		$expected .= ')';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=14372
	 */
	public function remappingDoesNotMixUpAliasesInSubquery() {
		$selectFields = 'pages.uid';
		$fromTables = 'tt_news AS pages INNER JOIN tt_news_cat_mm AS cat_mm ON cat_mm.uid_local = pages.uid';
		$whereClause = 'pages.pid IN (SELECT uid FROM pages WHERE deleted = 0 AND cat_mm.uid_local != 100)';
		$groupBy = '';
		$orderBy = 'pages.uid';
		$remappedParameters = $GLOBALS['TYPO3_DB']->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->_call('SELECTqueryFromArray', $remappedParameters));
		$expected = 'SELECT "pages"."news_uid" FROM "ext_tt_news" AS "pages"';
		$expected .= ' INNER JOIN "ext_tt_news_cat_mm" AS "cat_mm" ON "cat_mm"."local_uid"="pages"."news_uid"';
		$expected .= ' WHERE "pages"."pid" IN (';
		$expected .= 'SELECT "page_uid" FROM "my_pages" WHERE "deleted" = 0 AND "cat_mm"."local_uid" != 100';
		$expected .= ')';
		$expected .= ' ORDER BY "pages"."news_uid"';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=14479
	 */
	public function likeIsRemappedAccordingToFieldType() {
		$select = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('*', 'tt_content', 'tt_content.bodytext LIKE \'foo%\''));
		$expected = 'SELECT * FROM "tt_content" WHERE (dbms_lob.instr(LOWER("tt_content"."bodytext"), \'foo\',1,1) > 0)';
		$this->assertEquals($expected, $select);
		$select = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('*', 'fe_users', 'fe_users.usergroup LIKE \'2\''));
		$expected = 'SELECT * FROM "fe_users" WHERE (instr(LOWER("fe_users"."usergroup"), \'2\',1,1) > 0)';
		$this->assertEquals($expected, $select);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=15253
	 */
	public function notLikeIsRemappedAccordingToFieldType() {
		$select = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('*', 'tt_content', 'tt_content.bodytext NOT LIKE \'foo%\''));
		$expected = 'SELECT * FROM "tt_content" WHERE NOT (dbms_lob.instr(LOWER("tt_content"."bodytext"), \'foo\',1,1) > 0)';
		$this->assertEquals($expected, $select);
		$select = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('*', 'fe_users', 'fe_users.usergroup NOT LIKE \'2\''));
		$expected = 'SELECT * FROM "fe_users" WHERE NOT (instr(LOWER("fe_users"."usergroup"), \'2\',1,1) > 0)';
		$this->assertEquals($expected, $select);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=14479
	 */
	public function instrIsUsedForCEOnPages() {
		$select = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('*', 'tt_content', 'uid IN (62) AND tt_content.deleted=0 AND tt_content.t3ver_state<=0' . ' AND tt_content.hidden=0 AND (tt_content.starttime<=1264487640)' . ' AND (tt_content.endtime=0 OR tt_content.endtime>1264487640)' . ' AND (tt_content.fe_group=\'\' OR tt_content.fe_group IS NULL OR tt_content.fe_group=\'0\'' . ' OR (tt_content.fe_group LIKE \'%,0,%\' OR tt_content.fe_group LIKE \'0,%\' OR tt_content.fe_group LIKE \'%,0\'' . ' OR tt_content.fe_group=\'0\')' . ' OR (tt_content.fe_group LIKE\'%,-1,%\' OR tt_content.fe_group LIKE \'-1,%\' OR tt_content.fe_group LIKE \'%,-1\'' . ' OR tt_content.fe_group=\'-1\'))'));
		$expected = 'SELECT * FROM "tt_content"';
		$expected .= ' WHERE "uid" IN (62) AND "tt_content"."deleted" = 0 AND "tt_content"."t3ver_state" <= 0';
		$expected .= ' AND "tt_content"."hidden" = 0 AND ("tt_content"."starttime" <= 1264487640)';
		$expected .= ' AND ("tt_content"."endtime" = 0 OR "tt_content"."endtime" > 1264487640)';
		$expected .= ' AND ("tt_content"."fe_group" = \'\' OR "tt_content"."fe_group" IS NULL OR "tt_content"."fe_group" = \'0\'';
		$expected .= ' OR ((instr(LOWER("tt_content"."fe_group"), \',0,\',1,1) > 0)';
		$expected .= ' OR (instr(LOWER("tt_content"."fe_group"), \'0,\',1,1) > 0)';
		$expected .= ' OR (instr(LOWER("tt_content"."fe_group"), \',0\',1,1) > 0)';
		$expected .= ' OR "tt_content"."fe_group" = \'0\')';
		$expected .= ' OR ((instr(LOWER("tt_content"."fe_group"), \',-1,\',1,1) > 0)';
		$expected .= ' OR (instr(LOWER("tt_content"."fe_group"), \'-1,\',1,1) > 0)';
		$expected .= ' OR (instr(LOWER("tt_content"."fe_group"), \',-1\',1,1) > 0)';
		$expected .= ' OR "tt_content"."fe_group" = \'-1\'))';
		$this->assertEquals($expected, $select);
	}

	///////////////////////////////////////
	// Tests concerning DB management
	///////////////////////////////////////
	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=12670
	 */
	public function notNullableColumnsWithDefaultEmptyStringAreCreatedAsNullable() {
		$parseString = '
			CREATE TABLE tx_realurl_uniqalias (
				uid int(11) NOT NULL auto_increment,
				tstamp int(11) DEFAULT \'0\' NOT NULL,
				tablename varchar(60) DEFAULT \'\' NOT NULL,
				field_alias varchar(255) DEFAULT \'\' NOT NULL,
				field_id varchar(60) DEFAULT \'\' NOT NULL,
				value_alias varchar(255) DEFAULT \'\' NOT NULL,
				value_id int(11) DEFAULT \'0\' NOT NULL,
				lang int(11) DEFAULT \'0\' NOT NULL,
				expire int(11) DEFAULT \'0\' NOT NULL,

				PRIMARY KEY (uid),
				KEY tablename (tablename),
				KEY bk_realurl01 (field_alias,field_id,value_id,lang,expire),
				KEY bk_realurl02 (tablename,field_alias,field_id,value_alias(220),expire)
			);
		';
		$components = $GLOBALS['TYPO3_DB']->SQLparser->_callRef('parseCREATETABLE', $parseString);
		$this->assertTrue(is_array($components), 'Not an array: ' . $components);
		$sqlCommands = $GLOBALS['TYPO3_DB']->SQLparser->_call('compileCREATETABLE', $components);
		$this->assertTrue(is_array($sqlCommands), 'Not an array: ' . $sqlCommands);
		$this->assertEquals(4, count($sqlCommands));
		$expected = $this->cleanSql('
			CREATE TABLE "tx_realurl_uniqalias" (
				"uid" NUMBER(20) NOT NULL,
				"tstamp" NUMBER(20) DEFAULT 0,
				"tablename" VARCHAR(60) DEFAULT \'\',
				"field_alias" VARCHAR(255) DEFAULT \'\',
				"field_id" VARCHAR(60) DEFAULT \'\',
				"value_alias" VARCHAR(255) DEFAULT \'\',
				"value_id" NUMBER(20) DEFAULT 0,
				"lang" NUMBER(20) DEFAULT 0,
				"expire" NUMBER(20) DEFAULT 0,
				PRIMARY KEY ("uid")
			)
		');
		$this->assertEquals($expected, $this->cleanSql($sqlCommands[0]));
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=11142
	 * @see http://bugs.typo3.org/view.php?id=12670
	 */
	public function defaultValueIsProperlyQuotedInCreateTable() {
		$parseString = '
			CREATE TABLE tx_test (
				uid int(11) NOT NULL auto_increment,
				lastname varchar(60) DEFAULT \'unknown\' NOT NULL,
				firstname varchar(60) DEFAULT \'\' NOT NULL,
				language varchar(2) NOT NULL,
				tstamp int(11) DEFAULT \'0\' NOT NULL,

				PRIMARY KEY (uid),
				KEY name (name)
			);
		';
		$components = $GLOBALS['TYPO3_DB']->SQLparser->_callRef('parseCREATETABLE', $parseString);
		$this->assertTrue(is_array($components), 'Not an array: ' . $components);
		$sqlCommands = $GLOBALS['TYPO3_DB']->SQLparser->_call('compileCREATETABLE', $components);
		$this->assertTrue(is_array($sqlCommands), 'Not an array: ' . $sqlCommands);
		$this->assertEquals(2, count($sqlCommands));
		$expected = $this->cleanSql('
			CREATE TABLE "tx_test" (
				"uid" NUMBER(20) NOT NULL,
				"lastname" VARCHAR(60) DEFAULT \'unknown\',
				"firstname" VARCHAR(60) DEFAULT \'\',
				"language" VARCHAR(2) DEFAULT \'\',
				"tstamp" NUMBER(20) DEFAULT 0,
				PRIMARY KEY ("uid")
			)
		');
		$this->assertEquals($expected, $this->cleanSql($sqlCommands[0]));
	}

	///////////////////////////////////////
	// Tests concerning subqueries
	///////////////////////////////////////
	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=12758
	 */
	public function inWhereClauseWithSubqueryIsProperlyQuoted() {
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('*', 'tx_crawler_queue', 'process_id IN (SELECT process_id FROM tx_crawler_process WHERE active=0 AND deleted=0)'));
		$expected = 'SELECT * FROM "tx_crawler_queue" WHERE "process_id" IN (SELECT "process_id" FROM "tx_crawler_process" WHERE "active" = 0 AND "deleted" = 0)';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=12758
	 */
	public function subqueryIsRemappedForInWhereClause() {
		$selectFields = '*';
		$fromTables = 'tx_crawler_queue';
		$whereClause = 'process_id IN (SELECT process_id FROM tx_crawler_process WHERE active=0 AND deleted=0)';
		$groupBy = '';
		$orderBy = '';
		$remappedParameters = $GLOBALS['TYPO3_DB']->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->_call('SELECTqueryFromArray', $remappedParameters));
		$expected = 'SELECT * FROM "tx_crawler_queue" WHERE "process_id" IN (SELECT "ps_id" FROM "tx_crawler_ps" WHERE "is_active" = 0 AND "deleted" = 0)';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=12800
	 */
	public function cachingFrameworkQueryIsSupported() {
		$currentTime = time();
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->DELETEquery('cachingframework_cache_hash_tags', 'identifier IN (' . $GLOBALS['TYPO3_DB']->SELECTsubquery('identifier', 'cachingframework_cache_pages', ('crdate + lifetime < ' . $currentTime . ' AND lifetime > 0')) . ')'));
		$expected = 'DELETE FROM "cachingframework_cache_hash_tags" WHERE "identifier" IN (';
		$expected .= 'SELECT "identifier" FROM "cachingframework_cache_pages" WHERE "crdate"+"lifetime" < ' . $currentTime . ' AND "lifetime" > 0';
		$expected .= ')';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=12800
	 */
	public function cachingFrameworkQueryIsRemapped() {
		$currentTime = time();
		$table = 'cachingframework_cache_hash_tags';
		$where = 'identifier IN (' . $GLOBALS['TYPO3_DB']->SELECTsubquery('identifier', 'cachingframework_cache_pages', ('crdate + lifetime < ' . $currentTime . ' AND lifetime > 0')) . ')';
		// Perform remapping (as in method exec_DELETEquery)
		if ($tableArray = $GLOBALS['TYPO3_DB']->_call('map_needMapping', $table)) {
			// Where clause:
			$whereParts = $GLOBALS['TYPO3_DB']->SQLparser->parseWhereClause($where);
			$GLOBALS['TYPO3_DB']->_callRef('map_sqlParts', $whereParts, $tableArray[0]['table']);
			$where = $GLOBALS['TYPO3_DB']->SQLparser->compileWhereClause($whereParts, FALSE);
			// Table name:
			if ($GLOBALS['TYPO3_DB']->mapping[$table]['mapTableName']) {
				$table = $GLOBALS['TYPO3_DB']->mapping[$table]['mapTableName'];
			}
		}
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->DELETEquery($table, $where));
		$expected = 'DELETE FROM "cf_cache_hash_tags" WHERE "identifier" IN (';
		$expected .= 'SELECT "identifier" FROM "cf_cache_pages" WHERE "crdate"+"lifetime" < ' . $currentTime . ' AND "lifetime" > 0';
		$expected .= ')';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=12758
	 */
	public function existsWhereClauseIsProperlyQuoted() {
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('*', 'tx_crawler_process', 'active = 0 AND NOT EXISTS (' . $GLOBALS['TYPO3_DB']->SELECTsubquery('*', 'tx_crawler_queue', 'tx_crawler_queue.process_id = tx_crawler_process.process_id AND tx_crawler_queue.exec_time = 0)') . ')'));
		$expected = 'SELECT * FROM "tx_crawler_process" WHERE "active" = 0 AND NOT EXISTS (';
		$expected .= 'SELECT * FROM "tx_crawler_queue" WHERE "tx_crawler_queue"."process_id" = "tx_crawler_process"."process_id" AND "tx_crawler_queue"."exec_time" = 0';
		$expected .= ')';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=12758
	 */
	public function subqueryIsRemappedForExistsWhereClause() {
		$selectFields = '*';
		$fromTables = 'tx_crawler_process';
		$whereClause = 'active = 0 AND NOT EXISTS (' . $GLOBALS['TYPO3_DB']->SELECTsubquery('*', 'tx_crawler_queue', 'tx_crawler_queue.process_id = tx_crawler_process.process_id AND tx_crawler_queue.exec_time = 0') . ')';
		$groupBy = '';
		$orderBy = '';
		$remappedParameters = $GLOBALS['TYPO3_DB']->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->_call('SELECTqueryFromArray', $remappedParameters));
		$expected = 'SELECT * FROM "tx_crawler_ps" WHERE "is_active" = 0 AND NOT EXISTS (';
		$expected .= 'SELECT * FROM "tx_crawler_queue" WHERE "tx_crawler_queue"."process_id" = "tx_crawler_ps"."ps_id" AND "tx_crawler_queue"."exec_time" = 0';
		$expected .= ')';
		$this->assertEquals($expected, $query);
	}

	///////////////////////////////////////
	// Tests concerning advanced operators
	///////////////////////////////////////
	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=13135
	 */
	public function caseStatementIsProperlyQuoted() {
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('process_id, CASE active' . ' WHEN 1 THEN ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('one', 'tx_crawler_process') . ' WHEN 2 THEN ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('two', 'tx_crawler_process') . ' ELSE ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('out of range', 'tx_crawler_process') . ' END AS number', 'tx_crawler_process', '1=1'));
		$expected = 'SELECT "process_id", CASE "active" WHEN 1 THEN \'one\' WHEN 2 THEN \'two\' ELSE \'out of range\' END AS "number" FROM "tx_crawler_process" WHERE 1 = 1';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=13135
	 */
	public function caseStatementIsProperlyRemapped() {
		$selectFields = 'process_id, CASE active' . ' WHEN 1 THEN ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('one', 'tx_crawler_process') . ' WHEN 2 THEN ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('two', 'tx_crawler_process') . ' ELSE ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('out of range', 'tx_crawler_process') . ' END AS number';
		$fromTables = 'tx_crawler_process';
		$whereClause = '1=1';
		$groupBy = '';
		$orderBy = '';
		$remappedParameters = $GLOBALS['TYPO3_DB']->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->_call('SELECTqueryFromArray', $remappedParameters));
		$expected = 'SELECT "ps_id", CASE "is_active" WHEN 1 THEN \'one\' WHEN 2 THEN \'two\' ELSE \'out of range\' END AS "number" ';
		$expected .= 'FROM "tx_crawler_ps" WHERE 1 = 1';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=13135
	 */
	public function caseStatementWithExternalTableIsProperlyRemapped() {
		$selectFields = 'process_id, CASE tt_news.uid' . ' WHEN 1 THEN ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('one', 'tt_news') . ' WHEN 2 THEN ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('two', 'tt_news') . ' ELSE ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('out of range', 'tt_news') . ' END AS number';
		$fromTables = 'tx_crawler_process, tt_news';
		$whereClause = '1=1';
		$groupBy = '';
		$orderBy = '';
		$remappedParameters = $GLOBALS['TYPO3_DB']->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->_call('SELECTqueryFromArray', $remappedParameters));
		$expected = 'SELECT "ps_id", CASE "ext_tt_news"."news_uid" WHEN 1 THEN \'one\' WHEN 2 THEN \'two\' ELSE \'out of range\' END AS "number" ';
		$expected .= 'FROM "tx_crawler_ps", "ext_tt_news" WHERE 1 = 1';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=13134
	 */
	public function locateStatementIsProperlyQuoted() {
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('*, CASE WHEN' . ' LOCATE(' . $GLOBALS['TYPO3_DB']->fullQuoteStr('(fce)', 'tx_templavoila_tmplobj') . ', datastructure)>0 THEN 2' . ' ELSE 1' . ' END AS scope', 'tx_templavoila_tmplobj', '1=1'));
		$expected = 'SELECT *, CASE WHEN INSTR("datastructure", \'(fce)\') > 0 THEN 2 ELSE 1 END AS "scope" FROM "tx_templavoila_tmplobj" WHERE 1 = 1';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=13134
	 */
	public function locateStatementWithPositionIsProperlyQuoted() {
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('*, CASE WHEN' . ' LOCATE(' . $GLOBALS['TYPO3_DB']->fullQuoteStr('(fce)', 'tx_templavoila_tmplobj') . ', datastructure, 4)>0 THEN 2' . ' ELSE 1' . ' END AS scope', 'tx_templavoila_tmplobj', '1=1'));
		$expected = 'SELECT *, CASE WHEN INSTR("datastructure", \'(fce)\', 4) > 0 THEN 2 ELSE 1 END AS "scope" FROM "tx_templavoila_tmplobj" WHERE 1 = 1';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=6196
	 */
	public function IfNullIsProperlyRemapped() {
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('*', 'tt_news_cat_mm', 'IFNULL(tt_news_cat_mm.uid_foreign,0) IN (21,22)'));
		$expected = 'SELECT * FROM "tt_news_cat_mm" WHERE NVL("tt_news_cat_mm"."uid_foreign", 0) IN (21,22)';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=14985
	 */
	public function findInSetIsProperlyRemapped() {
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('*', 'fe_users', 'FIND_IN_SET(10, usergroup)'));
		$expected = 'SELECT * FROM "fe_users" WHERE \',\'||"usergroup"||\',\' LIKE \'%,10,%\'';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=14985
	 */
	public function findInSetFieldIsProperlyRemapped() {
		$selectFields = 'fe_group';
		$fromTables = 'tt_news';
		$whereClause = 'FIND_IN_SET(10, fe_group)';
		$groupBy = '';
		$orderBy = '';
		$remappedParameters = $GLOBALS['TYPO3_DB']->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->_call('SELECTqueryFromArray', $remappedParameters));
		$expected = 'SELECT "usergroup" FROM "ext_tt_news" WHERE \',\'||"ext_tt_news"."usergroup"||\',\' LIKE \'%,10,%\'';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=14818
	 */
	public function listQueryIsProperlyRemapped() {
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('*', 'fe_users', $GLOBALS['TYPO3_DB']->listQuery('usergroup', 10, 'fe_users')));
		$expected = 'SELECT * FROM "fe_users" WHERE \',\'||"usergroup"||\',\' LIKE \'%,10,%\'';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=12535
	 */
	public function likeBinaryOperatorIsRemoved() {
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('*', 'tt_content', 'bodytext LIKE BINARY \'test\''));
		$expected = 'SELECT * FROM "tt_content" WHERE (dbms_lob.instr("bodytext", \'test\',1,1) > 0)';
		$this->assertEquals($expected, $query);
	}

}


?>