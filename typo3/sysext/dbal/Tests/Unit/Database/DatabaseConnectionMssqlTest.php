<?php
namespace TYPO3\CMS\Dbal\Tests\Unit\Database;

/**
 * Test MS SQL database handling.
 *
 * @author Xavier Perseguers <xavier@typo3.org>
 */
class DatabaseConnectionMssqlTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

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
		// Reconfigure DBAL to use MS SQL
		require 'Fixtures/mssql.config.php';
		$className = self::buildAccessibleProxy('TYPO3\\CMS\\Dbal\\Database\\DatabaseConnection');
		$GLOBALS['TYPO3_DB'] = new $className();
		$parserClassName = self::buildAccessibleProxy('TYPO3\\CMS\\Dbal\\Database\\SqlParser');
		$GLOBALS['TYPO3_DB']->SQLparser = new $parserClassName();
		$this->assertFalse($GLOBALS['TYPO3_DB']->isConnected());
		// Initialize a fake MS SQL connection
		\TYPO3\CMS\Dbal\Tests\Unit\Database\FakeDatabaseConnection::connect($GLOBALS['TYPO3_DB'], 'mssql');
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
	public function configurationIsUsingAdodbAndDriverMssql() {
		$configuration = $GLOBALS['TYPO3_DB']->conf['handlerCfg'];
		$this->assertTrue(is_array($configuration) && count($configuration) > 0, 'No configuration found');
		$this->assertEquals('adodb', $configuration['_DEFAULT']['type']);
		$this->assertTrue($GLOBALS['TYPO3_DB']->runningADOdbDriver('mssql') !== FALSE, 'Not using mssql driver');
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
	 * @see http://bugs.typo3.org/view.php?id=14985
	 */
	public function findInSetIsProperlyRemapped() {
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('*', 'fe_users', 'FIND_IN_SET(10, usergroup)'));
		$expected = 'SELECT * FROM "fe_users" WHERE \',\'+"usergroup"+\',\' LIKE \'%,10,%\'';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/27858
	 */
	public function canParseSingleQuote() {
		$parseString = 'SELECT * FROM pages WHERE title=\'1\'\'\' AND deleted=0';
		$components = $GLOBALS['TYPO3_DB']->SQLparser->_callRef('parseSELECT', $parseString);
		$this->assertTrue(is_array($components), $components);
		$this->assertTrue(empty($components['parseString']), 'parseString is not empty');
	}

	///////////////////////////////////////
	// Tests concerning remapping with
	// external (non-TYPO3) databases
	///////////////////////////////////////
	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=13490
	 */
	public function canRemapPidToZero() {
		$selectFields = 'uid, FirstName, LastName';
		$fromTables = 'Members';
		$whereClause = 'pid=0 AND cruser_id=1';
		$groupBy = '';
		$orderBy = '';
		$remappedParameters = $GLOBALS['TYPO3_DB']->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->_call('SELECTqueryFromArray', $remappedParameters));
		$expected = 'SELECT "MemberID", "FirstName", "LastName" FROM "Members" WHERE 0 = 0 AND 1 = 1';
		$this->assertEquals($expected, $query);
	}

	///////////////////////////////////////
	// Tests concerning advanced operators
	///////////////////////////////////////
	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=13134
	 */
	public function locateStatementIsProperlyQuoted() {
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('*, CASE WHEN' . ' LOCATE(' . $GLOBALS['TYPO3_DB']->fullQuoteStr('(fce)', 'tx_templavoila_tmplobj') . ', datastructure)>0 THEN 2' . ' ELSE 1' . ' END AS scope', 'tx_templavoila_tmplobj', '1=1'));
		$expected = 'SELECT *, CASE WHEN CHARINDEX(\'(fce)\', "datastructure") > 0 THEN 2 ELSE 1 END AS "scope" FROM "tx_templavoila_tmplobj" WHERE 1 = 1';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=13134
	 */
	public function locateStatementWithPositionIsProperlyQuoted() {
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('*, CASE WHEN' . ' LOCATE(' . $GLOBALS['TYPO3_DB']->fullQuoteStr('(fce)', 'tx_templavoila_tmplobj') . ', datastructure, 4)>0 THEN 2' . ' ELSE 1' . ' END AS scope', 'tx_templavoila_tmplobj', '1=1'));
		$expected = 'SELECT *, CASE WHEN CHARINDEX(\'(fce)\', "datastructure", 4) > 0 THEN 2 ELSE 1 END AS "scope" FROM "tx_templavoila_tmplobj" WHERE 1 = 1';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=13134
	 */
	public function locateStatementIsProperlyRemapped() {
		$selectFields = '*, CASE WHEN' . ' LOCATE(' . $GLOBALS['TYPO3_DB']->fullQuoteStr('(fce)', 'tx_templavoila_tmplobj') . ', datastructure, 4)>0 THEN 2' . ' ELSE 1' . ' END AS scope';
		$fromTables = 'tx_templavoila_tmplobj';
		$whereClause = '1=1';
		$groupBy = '';
		$orderBy = '';
		$remappedParameters = $GLOBALS['TYPO3_DB']->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->_call('SELECTqueryFromArray', $remappedParameters));
		$expected = 'SELECT *, CASE WHEN CHARINDEX(\'(fce)\', "ds", 4) > 0 THEN 2 ELSE 1 END AS "scope" FROM "tx_templavoila_tmplobj" WHERE 1 = 1';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=13134
	 */
	public function locateStatementWithExternalTableIsProperlyRemapped() {
		$selectFields = '*, CASE WHEN' . ' LOCATE(' . $GLOBALS['TYPO3_DB']->fullQuoteStr('(fce)', 'tx_templavoila_tmplobj') . ', tx_templavoila_tmplobj.datastructure, 4)>0 THEN 2' . ' ELSE 1' . ' END AS scope';
		$fromTables = 'tx_templavoila_tmplobj';
		$whereClause = '1=1';
		$groupBy = '';
		$orderBy = '';
		$remappedParameters = $GLOBALS['TYPO3_DB']->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->_call('SELECTqueryFromArray', $remappedParameters));
		$expected = 'SELECT *, CASE WHEN CHARINDEX(\'(fce)\', "tx_templavoila_tmplobj"."ds", 4) > 0 THEN 2 ELSE 1 END AS "scope" FROM "tx_templavoila_tmplobj" WHERE 1 = 1';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=6196
	 */
	public function IfNullIsProperlyRemapped() {
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('*', 'tt_news_cat_mm', 'IFNULL(tt_news_cat_mm.uid_foreign,0) IN (21,22)'));
		$expected = 'SELECT * FROM "tt_news_cat_mm" WHERE ISNULL("tt_news_cat_mm"."uid_foreign", 0) IN (21,22)';
		$this->assertEquals($expected, $query);
	}

}


?>