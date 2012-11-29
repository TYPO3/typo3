<?php
namespace TYPO3\CMS\Dbal\Tests\Unit\Database;

/**
 * Test PostgreSQL database handling.
 *
 * @author Xavier Perseguers <xavier@typo3.org>
 */
class DatabaseConnectionPostgresqlTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

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
		// Reconfigure DBAL to use PostgreSQL
		require 'Fixtures/postgresql.config.php';
		$className = self::buildAccessibleProxy('TYPO3\\CMS\\Dbal\\Database\\DatabaseConnection');
		$GLOBALS['TYPO3_DB'] = new $className();
		$parserClassName = self::buildAccessibleProxy('TYPO3\\CMS\\Dbal\\Database\\SqlParser');
		$GLOBALS['TYPO3_DB']->SQLparser = new $parserClassName();
		$this->assertFalse($GLOBALS['TYPO3_DB']->isConnected());
		// Initialize a fake PostgreSQL connection (using 'postgres7' as 'postgres' is remapped to it in AdoDB)
		\TYPO3\CMS\Dbal\Tests\Unit\Database\FakeDatabaseConnection::connect($GLOBALS['TYPO3_DB'], 'postgres7');
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
	public function configurationIsUsingAdodbAndDriverPostgres() {
		$configuration = $GLOBALS['TYPO3_DB']->conf['handlerCfg'];
		$this->assertTrue(is_array($configuration) && count($configuration) > 0, 'No configuration found');
		$this->assertEquals('adodb', $configuration['_DEFAULT']['type']);
		$this->assertTrue($GLOBALS['TYPO3_DB']->runningADOdbDriver('postgres') !== FALSE, 'Not using postgres driver');
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
	 * @see http://bugs.typo3.org/view.php?id=2367
	 */
	public function limitIsProperlyRemapped() {
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('*', 'be_users', '1=1', '', '', '20'));
		$expected = 'SELECT * FROM "be_users" WHERE 1 = 1 LIMIT 20';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=2367
	 */
	public function limitWithSkipIsProperlyRemapped() {
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('*', 'be_users', '1=1', '', '', '20,40'));
		$expected = 'SELECT * FROM "be_users" WHERE 1 = 1 LIMIT 40 OFFSET 20';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=14985
	 */
	public function findInSetIsProperlyRemapped() {
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('*', 'fe_users', 'FIND_IN_SET(10, usergroup)'));
		$expected = 'SELECT * FROM "fe_users" WHERE FIND_IN_SET(10, "usergroup") != 0';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=12535
	 */
	public function likeBinaryOperatorIsRemappedToLike() {
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('*', 'tt_content', 'bodytext LIKE BINARY \'test\''));
		$expected = 'SELECT * FROM "tt_content" WHERE "bodytext" LIKE \'test\'';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=12535
	 */
	public function notLikeBinaryOperatorIsRemappedToNotLike() {
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('*', 'tt_content', 'bodytext NOT LIKE BINARY \'test\''));
		$expected = 'SELECT * FROM "tt_content" WHERE "bodytext" NOT LIKE \'test\'';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=12535
	 */
	public function likeOperatorIsRemappedToIlike() {
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('*', 'tt_content', 'bodytext LIKE \'test\''));
		$expected = 'SELECT * FROM "tt_content" WHERE "bodytext" ILIKE \'test\'';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=12535
	 */
	public function notLikeOperatorIsRemappedToNotIlike() {
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('*', 'tt_content', 'bodytext NOT LIKE \'test\''));
		$expected = 'SELECT * FROM "tt_content" WHERE "bodytext" NOT ILIKE \'test\'';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/32626
	 */
	public function notEqualAnsiOperatorCanBeParsed() {
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('*', 'pages', 'pid<>3'));
		$expected = 'SELECT * FROM "pages" WHERE "pid" <> 3';
		$this->assertEquals($expected, $query);
	}

}


?>