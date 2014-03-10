<?php
namespace TYPO3\CMS\Dbal\Tests\Unit\Database;

/**
 * Testcase for class DatabaseConnection.
 *
 * @author Xavier Perseguers <xavier@typo3.org>
 */
class DatabaseConnectionTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var array
	 */
	protected $temporaryFiles;

	/**
	 * Prepares the environment before running a test.
	 */
	public function setUp() {
		$this->temporaryFiles = array();
		$GLOBALS['TYPO3_LOADED_EXT'] = array();
		$GLOBALS['TYPO3_DB'] = $this->getAccessibleMock('TYPO3\\CMS\\Dbal\\Database\\DatabaseConnection', array('admin_get_charsets'));
		$GLOBALS['TYPO3_DB']->expects($this->any())->method('admin_get_charsets')->will($this->returnValue(array('utf8' => array())));
		$GLOBALS['TYPO3_DB']->initialize();
		$GLOBALS['TYPO3_DB']->lastHandlerKey = '_DEFAULT';
	}

	/**
	 * Cleans up the environment after running a test.
	 */
	public function tearDown() {
		// Clear DBAL-generated cache files
		$GLOBALS['TYPO3_DB']->clearCachedFieldInfo();
		// Delete temporary files
		foreach ($this->temporaryFiles as $filename) {
			unlink($filename);
		}
		parent::tearDown();
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
	 * Creates a fake extension with a given table definition.
	 *
	 * @param string $tableDefinition SQL script to create the extension's tables
	 * @throws \RuntimeException
	 * @return void
	 */
	protected function createFakeExtension($tableDefinition) {
		// Prepare a fake extension configuration
		$ext_tables = \TYPO3\CMS\Core\Utility\GeneralUtility::tempnam('ext_tables');
		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::writeFile($ext_tables, $tableDefinition)) {
			throw new \RuntimeException('Can\'t write temporary ext_tables file.');
		}
		$this->temporaryFiles[] = $ext_tables;
		$GLOBALS['TYPO3_LOADED_EXT'] = array('test_dbal' => array(
			'ext_tables.sql' => $ext_tables
		));
		// Append our test table to the list of existing tables
		$GLOBALS['TYPO3_DB']->clearCachedFieldInfo();
		$GLOBALS['TYPO3_DB']->initialize();
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/21502
	 */
	public function concatCanBeParsedAfterLikeOperator() {
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('*', 'sys_refindex, tx_dam_file_tracking', 'sys_refindex.tablename = \'tx_dam_file_tracking\'' . ' AND sys_refindex.ref_string LIKE CONCAT(tx_dam_file_tracking.file_path, tx_dam_file_tracking.file_name)'));
		$expected = 'SELECT * FROM sys_refindex, tx_dam_file_tracking WHERE sys_refindex.tablename = \'tx_dam_file_tracking\'';
		$expected .= ' AND sys_refindex.ref_string LIKE CONCAT(tx_dam_file_tracking.file_path, tx_dam_file_tracking.file_name)';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/20346
	 */
	public function floatNumberCanBeStoredInDatabase() {
		$this->createFakeExtension('
			CREATE TABLE tx_test_dbal (
				foo double default \'0\',
				foobar int default \'0\'
			);
		');
		$data = array(
			'foo' => 99.12,
			'foobar' => -120
		);
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->INSERTquery('tx_test_dbal', $data));
		$expected = 'INSERT INTO tx_test_dbal ( foo, foobar ) VALUES ( \'99.12\', \'-120\' )';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/20427
	 */
	public function positive64BitIntegerIsSupported() {
		$this->createFakeExtension('
			CREATE TABLE tx_test_dbal (
				foo int default \'0\',
				foobar bigint default \'0\'
			);
		');
		$data = array(
			'foo' => 9223372036854775807,
			'foobar' => 9223372036854775807
		);
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->INSERTquery('tx_test_dbal', $data));
		$expected = 'INSERT INTO tx_test_dbal ( foo, foobar ) VALUES ( \'9223372036854775807\', \'9223372036854775807\' )';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/20427
	 */
	public function negative64BitIntegerIsSupported() {
		$this->markTestSkipped('This test causes problems on some systems.');
		$this->createFakeExtension('
			CREATE TABLE tx_test_dbal (
				foo int default \'0\',
				foobar bigint default \'0\'
			);
		');
		$data = array(
			'foo' => -9.2233720368548E+18,
			'foobar' => -9.2233720368548E+18
		);
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->INSERTquery('tx_test_dbal', $data));
		$expected = 'INSERT INTO tx_test_dbal ( foo, foobar ) VALUES ( \'-9223372036854775808\', \'-9223372036854775808\' )';
		$this->assertEquals($expected, $query);
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
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->INSERTmultipleRows('tt_content', $fields, $rows));
		$expected = 'INSERT INTO tt_content (uid, pid, title, body) VALUES ';
		$expected .= '(\'1\', \'2\', \'Title #1\', \'Content #1\'), ';
		$expected .= '(\'3\', \'4\', \'Title #2\', \'Content #2\'), ';
		$expected .= '(\'5\', \'6\', \'Title #3\', \'Content #3\')';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/16708
	 */
	public function minFunctionAndInOperatorCanBeParsed() {
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('*', 'pages', 'MIN(uid) IN (1,2,3,4)'));
		$expected = 'SELECT * FROM pages WHERE MIN(uid) IN (1,2,3,4)';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/16708
	 */
	public function maxFunctionAndInOperatorCanBeParsed() {
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('*', 'pages', 'MAX(uid) IN (1,2,3,4)'));
		$expected = 'SELECT * FROM pages WHERE MAX(uid) IN (1,2,3,4)';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/21514
	 */
	public function likeBinaryOperatorIsKept() {
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('*', 'tt_content', 'bodytext LIKE BINARY \'test\''));
		$expected = 'SELECT * FROM tt_content WHERE bodytext LIKE BINARY \'test\'';
		$this->assertEquals($expected, $query);
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/21514
	 */
	public function notLikeBinaryOperatorIsKept() {
		$query = $this->cleanSql($GLOBALS['TYPO3_DB']->SELECTquery('*', 'tt_content', 'bodytext NOT LIKE BINARY \'test\''));
		$expected = 'SELECT * FROM tt_content WHERE bodytext NOT LIKE BINARY \'test\'';
		$this->assertEquals($expected, $query);
	}

	///////////////////////////////////////
	// Tests concerning prepared queries
	///////////////////////////////////////
	/**
	 * @test
	 * @see http://forge.typo3.org/issues/23374
	 */
	public function similarNamedParametersAreProperlyReplaced() {
		$sql = 'SELECT * FROM cache WHERE tag = :tag1 OR tag = :tag10 OR tag = :tag100';
		$parameterValues = array(
			':tag1' => 'tag-one',
			':tag10' => 'tag-two',
			':tag100' => 'tag-three'
		);
		$className = self::buildAccessibleProxy('TYPO3\\CMS\\Core\\Database\\PreparedStatement');
		$query = $sql;
		$precompiledQueryParts = array();
		$statement = new $className($sql, 'cache');
		$statement->bindValues($parameterValues);
		$parameters = $statement->_get('parameters');
		$statement->_callRef('convertNamedPlaceholdersToQuestionMarks', $query, $parameters, $precompiledQueryParts);
		$expectedQuery = 'SELECT * FROM cache WHERE tag = ? OR tag = ? OR tag = ?';
		$expectedParameterValues = array(
			0 => array(
				'type' => \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_STR,
				'value' => 'tag-one',
			),
			1 => array(
				'type' => \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_STR,
				'value' => 'tag-two',
			),
			2 => array(
				'type' => \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_STR,
				'value' => 'tag-three',
			),
		);
		$this->assertEquals($expectedQuery, $query);
		$this->assertEquals($expectedParameterValues, $parameters);
	}

}
