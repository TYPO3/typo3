<?php
namespace TYPO3\CMS\Dbal\Tests\Unit\Database;

/***************************************************************
 * Copyright notice
 *
 * (c) 2010-2014 Xavier Perseguers <xavier@typo3.org>
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
 * A copy is found in the text file GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case
 */
class DatabaseConnectionTest extends AbstractTestCase {

	/**
	 * @var \TYPO3\CMS\Dbal\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $subject;

	/**
	 * @var array
	 */
	protected $temporaryFiles = array();

	/**
	 * Set up
	 */
	public function setUp() {
		$GLOBALS['TYPO3_LOADED_EXT'] = array();

		/** @var \TYPO3\CMS\Dbal\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
		$subject = $this->getAccessibleMock('TYPO3\\CMS\\Dbal\\Database\\DatabaseConnection', array('getFieldInfoCache'), array(), '', FALSE);

		// Disable caching
		$mockCacheFrontend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\PhpFrontend', array(), array(), '', FALSE);
		$subject->expects($this->any())->method('getFieldInfoCache')->will($this->returnValue($mockCacheFrontend));

		// Inject SqlParser - Its logic is tested with the tests, too.
		$sqlParser = $this->getAccessibleMock('TYPO3\\CMS\\Dbal\\Database\\SqlParser', array('dummy'), array(), '', FALSE);
		$sqlParser->_set('databaseConnection', $subject);
		$subject->SQLparser = $sqlParser;

		// Mock away schema migration service from install tool
		$installerSqlMock = $this->getMock('TYPO3\\CMS\\Install\\Service\\SqlSchemaMigrationService', array('getFieldDefinitions_fileContent'), array(), '', FALSE);
		$installerSqlMock->expects($this->any())->method('getFieldDefinitions_fileContent')->will($this->returnValue(array()));
		$subject->_set('installerSql', $installerSqlMock);

		$subject->initialize();
		$subject->lastHandlerKey = '_DEFAULT';

		$this->subject = $subject;
	}

	/**
	 * Tear down.
	 */
	public function tearDown() {
		// Delete temporary files
		foreach ($this->temporaryFiles as $filename) {
			unlink($filename);
		}
		parent::tearDown();
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
		$ext_tables = GeneralUtility::tempnam('ext_tables');
		if (!GeneralUtility::writeFile($ext_tables, $tableDefinition)) {
			throw new \RuntimeException('Can\'t write temporary ext_tables file.');
		}
		$this->temporaryFiles[] = $ext_tables;
		$GLOBALS['TYPO3_LOADED_EXT'] = array(
			'test_dbal' => array(
				'ext_tables.sql' => $ext_tables
			)
		);
		// Append our test table to the list of existing tables
		$this->subject->initialize();
	}

	/**
	 * @test
	 */
	public function tableWithMappingIsDetected() {
		$dbalConfiguration = array(
			'mapping' => array(
				'cf_cache_hash' => array(),
			),
		);

		/** @var \TYPO3\CMS\Dbal\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
		$subject = $this->getAccessibleMock('TYPO3\\CMS\\Dbal\\Database\\DatabaseConnection', array('getFieldInfoCache'), array(), '', FALSE);

		$mockCacheFrontend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\PhpFrontend', array(), array(), '', FALSE);
		$subject->expects($this->any())->method('getFieldInfoCache')->will($this->returnValue($mockCacheFrontend));

		$sqlParser = $this->getAccessibleMock('TYPO3\\CMS\\Dbal\\Database\\SqlParser', array('dummy'), array(), '', FALSE);
		$sqlParser->_set('databaseConnection', $subject);
		$subject->SQLparser = $sqlParser;

		$installerSqlMock = $this->getMock('TYPO3\\CMS\\Install\\Service\\SqlSchemaMigrationService', array(), array(), '', FALSE);
		$subject->_set('installerSql', $installerSqlMock);
		$schemaMigrationResult = array(
			'cf_cache_pages' => array(),
		);
		$installerSqlMock->expects($this->once())->method('getFieldDefinitions_fileContent')->will($this->returnValue($schemaMigrationResult));

		$subject->conf = $dbalConfiguration;
		$subject->initialize();
		$subject->lastHandlerKey = '_DEFAULT';

		$this->assertFalse($subject->_call('map_needMapping', 'cf_cache_pages'));
		$cfCacheHashNeedsMapping = $subject->_call('map_needMapping', 'cf_cache_hash');
		$this->assertEquals('cf_cache_hash', $cfCacheHashNeedsMapping[0]['table']);
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/21502
	 */
	public function concatCanBeParsedAfterLikeOperator() {
		$result = $this->subject->SELECTquery('*', 'sys_refindex, tx_dam_file_tracking', 'sys_refindex.tablename = \'tx_dam_file_tracking\'' . ' AND sys_refindex.ref_string LIKE CONCAT(tx_dam_file_tracking.file_path, tx_dam_file_tracking.file_name)');
		$expected = 'SELECT * FROM sys_refindex, tx_dam_file_tracking WHERE sys_refindex.tablename = \'tx_dam_file_tracking\'';
		$expected .= ' AND sys_refindex.ref_string LIKE CONCAT(tx_dam_file_tracking.file_path, tx_dam_file_tracking.file_name)';
		$this->assertEquals($expected, $this->cleanSql($result));
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
		$result = $this->subject->INSERTquery('tx_test_dbal', $data);
		$expected = 'INSERT INTO tx_test_dbal ( foo, foobar ) VALUES ( \'99.12\', \'-120\' )';
		$this->assertEquals($expected, $this->cleanSql($result));
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
		$result = $this->subject->INSERTquery('tx_test_dbal', $data);
		$expected = 'INSERT INTO tx_test_dbal ( foo, foobar ) VALUES ( \'9223372036854775807\', \'9223372036854775807\' )';
		$this->assertEquals($expected, $this->cleanSql($result));
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
		$result = $this->subject->INSERTmultipleRows('tt_content', $fields, $rows);
		$expected = 'INSERT INTO tt_content (uid, pid, title, body) VALUES ';
		$expected .= '(\'1\', \'2\', \'Title #1\', \'Content #1\'), ';
		$expected .= '(\'3\', \'4\', \'Title #2\', \'Content #2\'), ';
		$expected .= '(\'5\', \'6\', \'Title #3\', \'Content #3\')';
		$this->assertEquals($expected, $this->cleanSql($result));
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/16708
	 */
	public function minFunctionAndInOperatorCanBeParsed() {
		$result = $this->subject->SELECTquery('*', 'pages', 'MIN(uid) IN (1,2,3,4)');
		$expected = 'SELECT * FROM pages WHERE MIN(uid) IN (1,2,3,4)';
		$this->assertEquals($expected, $this->cleanSql($result));
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/16708
	 */
	public function maxFunctionAndInOperatorCanBeParsed() {
		$result = $this->subject->SELECTquery('*', 'pages', 'MAX(uid) IN (1,2,3,4)');
		$expected = 'SELECT * FROM pages WHERE MAX(uid) IN (1,2,3,4)';
		$this->assertEquals($expected, $this->cleanSql($result));
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/21514
	 */
	public function likeBinaryOperatorIsKept() {
		$result = $this->cleanSql($this->subject->SELECTquery('*', 'tt_content', 'bodytext LIKE BINARY \'test\''));
		$expected = 'SELECT * FROM tt_content WHERE bodytext LIKE BINARY \'test\'';
		$this->assertEquals($expected, $this->cleanSql($result));
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/21514
	 */
	public function notLikeBinaryOperatorIsKept() {
		$result = $this->cleanSql($this->subject->SELECTquery('*', 'tt_content', 'bodytext NOT LIKE BINARY \'test\''));
		$expected = 'SELECT * FROM tt_content WHERE bodytext NOT LIKE BINARY \'test\'';
		$this->assertEquals($expected, $this->cleanSql($result));
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
