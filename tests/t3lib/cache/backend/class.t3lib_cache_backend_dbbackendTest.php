<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2011 Ingo Renner <ingo@typo3.org>
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

/**
 * Testcase for the DB cache backend
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage tests
 */
class t3lib_cache_backend_DbBackendTest extends tx_phpunit_testcase {

	/**
	 * Enable backup of global and system variables
	 *
	 * @var boolean
	 */
	protected $backupGlobals = TRUE;

	/**
	 * Exclude TYPO3_DB from backup/ restore of $GLOBALS
	 * because resource types cannot be handled during serializing
	 *
	 * @var array
	 */
	protected $backupGlobalsBlacklist = array('TYPO3_DB');

	/**
	 *  @var t3lib_DB Backup of original TYPO3_DB instance
	 */
	protected $typo3DbBackup;

	/**
	 * @var string Name of the testing data table
	 */
	protected $testingCacheTable = 'cachingframework_Testing';

	/**
	 * @var string Name of the testing tags table
	 */
	protected $testingTagsTable = 'cachingframework_Testing_tags';

	/**
	 * Set up testcases
	 */
	public function setUp() {
		$this->typo3DbBackup = $GLOBALS['TYPO3_DB'];
	}

	/**
	 * Sets up the backend used for testing
	 *
	 * @return void
	 * @author Ingo Renner <ingo@typo3.org>
	 * @author Christian Kuhn <lolli@schwarzbu.ch>
	 */
	protected function setUpBackend(array $backendOptions = array()) {
		$GLOBALS['TYPO3_DB']->sql_query('CREATE TABLE ' . $this->testingCacheTable . ' (
			id int(11) unsigned NOT NULL auto_increment,
			identifier varchar(250) DEFAULT \'\' NOT NULL,
			expires int(11) unsigned DEFAULT \'0\' NOT NULL,
			content mediumblob,
			PRIMARY KEY (id),
			KEY cache_id (identifier, expires)
		) ENGINE=InnoDB;
		');

		$GLOBALS['TYPO3_DB']->sql_query('CREATE TABLE ' . $this->testingTagsTable . ' (
			id int(11) unsigned NOT NULL auto_increment,
			identifier varchar(250) DEFAULT \'\' NOT NULL,
			tag varchar(250) DEFAULT \'\' NOT NULL,
			PRIMARY KEY (id),
			KEY cache_id (identifier),
			KEY cache_tag (tag)
		) ENGINE=InnoDB;
		');

		$backend = t3lib_div::makeInstance(
			't3lib_cache_backend_DbBackend',
			'Testing',
			$backendOptions
		);

		return $backend;
	}

	/**
	 * Helper method to inject a mock frontend to backend instance
	 *
	 * @param t3lib_cache_backend_DbBackend $backend Current backend instance
	 * @return t3lib_cache_frontend_Frontend Mock frontend
	 */
	protected function setUpMockFrontendOfBackend(t3lib_cache_backend_DbBackend $backend) {
		$mockCache = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('Testing'));
		$backend->setCache($mockCache);

		return $mockCache;
	}

	/**
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function tearDown() {
		$GLOBALS['TYPO3_DB']->sql_query(
			'DROP TABLE IF EXISTS ' . $this->testingCacheTable . ';'
		);

		$GLOBALS['TYPO3_DB']->sql_query(
			'DROP TABLE IF EXISTS ' . $this->testingTagsTable . ';'
		);

		$GLOBALS['TYPO3_DB'] = $this->typo3DbBackup;
	}

	/**
	 * @test
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function setCacheCalculatesCacheTableName() {
		$backend = $this->setUpBackend();
		$this->setUpMockFrontendOfBackend($backend);
		$this->assertEquals($this->testingCacheTable, $backend->getCacheTable());
	}

	/**
	 * @test
	 */
	public function setCacheCalculatesTagsTableName() {
		$backend = $this->setUpBackend();
		$this->setUpMockFrontendOfBackend($backend);

		$this->assertEquals($this->testingTagsTable, $backend->getTagsTable());
	}

	/**
	 * @test
	 * @expectedException t3lib_cache_Exception
	 */
	public function setThrowsExceptionIfFrontendWasNotSet() {
		$backend = $this->setUpBackend();
		$backend->set('identifier', 'data');
	}

	/**
	 * @test
	 * @expectedException t3lib_cache_exception_InvalidData
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function setThrowsExceptionIfDataIsNotAString() {
		$backend = $this->setUpBackend();
		$this->setUpMockFrontendOfBackend($backend);

		$data = array('Some data');
		$entryIdentifier = 'BackendDbTest';

		$backend->set($entryIdentifier, $data);
	}

	/**
	 * @test
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function setInsertsEntryInTable() {
		$backend = $this->setUpBackend();
		$this->setUpMockFrontendOfBackend($backend);

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendDbTest';

		$backend->set($entryIdentifier, $data);

		$entryFound = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
			'*',
			$this->testingCacheTable,
			'identifier = \'' . $entryIdentifier . '\''
		);

		$this->assertEquals($data, $entryFound['content']);
	}

	/**
	 * @test
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function setRemovesAnAlreadyExistingCacheEntryForTheSameIdentifier() {
		$backend = $this->setUpBackend();
		$this->setUpMockFrontendOfBackend($backend);

		$data1 = 'some data' . microtime();
		$data2 = $data1 . '_different';
		$entryIdentifier = 'BackendDbRemoveBeforeSetTest';

		$backend->set($entryIdentifier, $data1, array(), 500);
		$backend->set($entryIdentifier, $data2, array(), 200);

		$entriesFound = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			$this->testingCacheTable,
			'identifier = \'' . $entryIdentifier . '\''
		);

		$this->assertEquals(1, count($entriesFound));
	}

	/**
	 * @test
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function setReallySavesSpecifiedTags() {
		$backend = $this->setUpBackend();
		$this->setUpMockFrontendOfBackend($backend);

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendDbTest';

		$backend->set($entryIdentifier, $data, array('UnitTestTag%tag1', 'UnitTestTag%tag2'));

		$entriesFound = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			$this->testingTagsTable,
			'identifier = \'' . $entryIdentifier . '\''
		);

		$tags = array();

		foreach ($entriesFound as $entry) {
			$tags[] = $entry['tag'];
		}

		$this->assertTrue(count($tags) > 0);
		$this->assertTrue(in_array('UnitTestTag%tag1', $tags));
		$this->assertTrue(in_array('UnitTestTag%tag2', $tags));
	}

	/**
	 * @test
	 * @author Christian Kuhn <lolli@schwarzbu.ch>
	 */
	public function setSavesCompressedDataWithEnabledCompression() {
		$backend = $this->setUpBackend(
			array(
				'compression' => TRUE,
			)
		);
		$this->setUpMockFrontendOfBackend($backend);

		$data = 'some data ' . microtime();
		$entryIdentifier = 'BackendDbTest';

		$backend->set($entryIdentifier, $data);

		$entry = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
			'content',
			$this->testingCacheTable,
			'identifier = \'' . $entryIdentifier . '\''
		);

		$this->assertEquals($data, @gzuncompress($entry['content']));
	}

	/**
	 * @test
	 * @author Christian Kuhn <lolli@schwarzbu.ch>
	 */
	public function setSavesPlaintextDataWithEnabledCompressionAndCompressionLevel0() {
		$backend = $this->setUpBackend(
			array(
				'compression' => TRUE,
				'compressionLevel' => 0,
			)
		);
		$this->setUpMockFrontendOfBackend($backend);

		$data = 'some data ' . microtime();
		$entryIdentifier = 'BackendDbTest';

		$backend->set($entryIdentifier, $data);

		$entry = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
			'content',
			$this->testingCacheTable,
			'identifier = \'' . $entryIdentifier . '\''
		);

		$this->assertGreaterThan(0, substr_count($entry['content'], $data));
	}

	/**
	 * @test
	 * @expectedException t3lib_cache_Exception
	 */
	public function getThrowsExceptionIfFrontendWasNotSet() {
		$backend = $this->setUpBackend();
		$backend->get('identifier');
	}

	/**
	 * @test
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function getReturnsContentOfTheCorrectCacheEntry() {
		$backend = $this->setUpBackend();
		$this->setUpMockFrontendOfBackend($backend);

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendDbTest';

		$backend->set($entryIdentifier, $data, array(), 500);

		$data = 'some other data' . microtime();
		$backend->set($entryIdentifier, $data, array(), 100);

		$loadedData = $backend->get($entryIdentifier);

		$this->assertEquals($data, $loadedData);
	}

	/**
	 * @test
	 * @expectedException t3lib_cache_Exception
	 */
	public function hasThrowsExceptionIfFrontendWasNotSet() {
		$backend = $this->setUpBackend();
		$backend->has('identifier');
	}

	/**
	 * @test
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function hasReturnsTheCorrectResult() {
		$backend = $this->setUpBackend();
		$this->setUpMockFrontendOfBackend($backend);

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendDbTest';

		$backend->set($entryIdentifier, $data);

		$this->assertTrue($backend->has($entryIdentifier));
		$this->assertFalse($backend->has($entryIdentifier . 'Not'));
	}

	/**
	 * @test
	 * @expectedException t3lib_cache_Exception
	 */
	public function removeThrowsExceptionIfFrontendWasNotSet() {
		$backend = $this->setUpBackend();
		$backend->remove('identifier');
	}

	/**
	 * @test
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function removeReallyRemovesACacheEntry() {
		$backend = $this->setUpBackend();
		$this->setUpMockFrontendOfBackend($backend);

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendDbRemovalTest';

		$backend->set($entryIdentifier, $data);

		$backend->remove($entryIdentifier);

		$entriesFound = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			$this->testingCacheTable,
			'identifier = \'' . $entryIdentifier . '\''
		);

		$this->assertTrue(count($entriesFound) == 0);
	}

	/**
	 * @test
	 * @expectedException t3lib_cache_Exception
	 */
	public function collectGarbageThrowsExceptionIfFrontendWasNotSet() {
		$backend = $this->setUpBackend();
		$backend->collectGarbage();
	}

	/**
	 * @test
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function collectGarbageReallyRemovesAnExpiredCacheEntry() {
		$backend = $this->setUpBackend();
		$mockCache = $this->setUpMockFrontendOfBackend($backend);

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendDbRemovalTest';

		$backend->set($entryIdentifier, $data, array(), 1);

		$GLOBALS['EXEC_TIME'] += 2;
			// setCache calls initializeCommonReferences which recalculate expire statement
			// needed after manual $GLOBALS['EXEC_TIME'] manipulation
		$backend->setCache($mockCache);

		$backend->collectGarbage();

		$entriesFound = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			$this->testingCacheTable,
			'identifier = \'' . $entryIdentifier . '\''
		);

		$this->assertTrue(count($entriesFound) == 0);
	}

	/**
	 * @test
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function collectGarbageReallyRemovesAllExpiredCacheEntries() {
		$backend = $this->setUpBackend();
		$mockCache = $this->setUpMockFrontendOfBackend($backend);

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendDbRemovalTest';

		$backend->set($entryIdentifier . 'A', $data, array(), 1);
		$backend->set($entryIdentifier . 'B', $data, array(), 1);
		$backend->set($entryIdentifier . 'C', $data, array(), 1);

		$GLOBALS['EXEC_TIME'] += 2;
			// setCache calls initializeCommonReferences which recalculate expire statement
			// needed after manual $GLOBALS['EXEC_TIME'] manipulation
		$backend->setCache($mockCache);

		$backend->collectGarbage();

		$entriesFound = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			$this->testingCacheTable,
			''
		);

		$this->assertTrue(count($entriesFound) == 0);
	}

	/**
	 * @test
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function findIdentifiersByTagFindsCacheEntriesWithSpecifiedTag() {
		$backend = $this->setUpBackend();
		$this->setUpMockFrontendOfBackend($backend);

		$data = 'some data' . microtime();
		$backend->set('BackendDbTest1', $data, array('UnitTestTag%test', 'UnitTestTag%boring'));
		$backend->set('BackendDbTest2', $data, array('UnitTestTag%test', 'UnitTestTag%special'));
		$backend->set('BackendDbTest3', $data, array('UnitTestTag%test'));

		$expectedEntry = 'BackendDbTest2';

		$actualEntries = $backend->findIdentifiersByTag('UnitTestTag%special');
		$this->assertTrue(is_array($actualEntries));
		$this->assertEquals($expectedEntry, array_pop($actualEntries));
	}

	/**
	 * @test
	 * @expectedException t3lib_cache_Exception
	 */
	public function flushThrowsExceptionIfFrontendWasNotSet() {
		$backend = $this->setUpBackend();
		$backend->flush();
	}

	/**
	 * @test
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function flushRemovesAllCacheEntries() {
		$backend = $this->setUpBackend();
		$this->setUpMockFrontendOfBackend($backend);

		$data = 'some data' . microtime();
		$backend->set('BackendDbTest1', $data, array('UnitTestTag%test'));
		$backend->set('BackendDbTest2', $data, array('UnitTestTag%test', 'UnitTestTag%special'));
		$backend->set('BackendDbTest3', $data, array('UnitTestTag%test'));

		$backend->flush();

		$entriesFound = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			$this->testingCacheTable,
			''
		);

		$this->assertTrue(count($entriesFound) == 0);
	}

	/**
	 * @test
	 */
	public function flushDropsDataTable() {
		$backend = $this->setUpBackend();
		$this->setUpMockFrontendOfBackend($backend);

		$GLOBALS['TYPO3_DB'] = $this->getMock('t3lib_DB', array('admin_query'));
		$GLOBALS['TYPO3_DB']->expects($this->at(0))
			->method('admin_query')
			->with('DROP TABLE IF EXISTS cachingframework_Testing');

		$backend->flush();
	}

	/**
	 * @test
	 */
	public function flushDropsTagsTable() {
		$backend = $this->setUpBackend();
		$this->setUpMockFrontendOfBackend($backend);

		$GLOBALS['TYPO3_DB'] = $this->getMock('t3lib_DB', array('admin_query'));
		$GLOBALS['TYPO3_DB']->expects($this->at(1))
			->method('admin_query')
			->with('DROP TABLE IF EXISTS cachingframework_Testing_tags');

		$backend->flush();
	}

	/**
	 * @test
	 */
	public function flushCreatesDataTable() {
		$backend = $this->setUpBackend();
		$this->setUpMockFrontendOfBackend($backend);

		$GLOBALS['TYPO3_DB'] = $this->getMock('t3lib_DB', array('admin_query'));
		$GLOBALS['TYPO3_DB']->expects($this->at(2))
			->method('admin_query')
			->will($this->returnCallback(array($this, flushCreatesDataTableCallback)));

		$backend->flush();
	}

	/**
	 * Callback of flushCreatesDataTable to check if data table is created
	 *
	 * @param string $sql SQL of admin_query
	 * @return void
	 */
	public function flushCreatesDataTableCallback($sql) {
		$startOfStatement = 'CREATE TABLE cachingframework_Testing (';
		$this->assertEquals($startOfStatement, substr($sql, 0, strlen($startOfStatement)));
	}

	/**
	 * @test
	 */
	public function flushCreatesTagsTable() {
		$backend = $this->setUpBackend();
		$this->setUpMockFrontendOfBackend($backend);

		$GLOBALS['TYPO3_DB'] = $this->getMock('t3lib_DB', array('admin_query'));
		$GLOBALS['TYPO3_DB']->expects($this->at(3))
			->method('admin_query')
			->will($this->returnCallback(array($this, flushCreatesTagsTableCallback)));

		$backend->flush();
	}

	/**
	 * Callback of flushCreatesTagsTable to check if tags table is created
	 *
	 * @param string $sql SQL of admin_query
	 * @return void
	 */
	public function flushCreatesTagsTableCallback($sql) {
		$startOfStatement = 'CREATE TABLE cachingframework_Testing_tags (';
		$this->assertEquals($startOfStatement, substr($sql, 0, strlen($startOfStatement)));
	}

	/**
	 * @test
	 * @expectedException t3lib_cache_Exception
	 */
	public function flushByTagThrowsExceptionIfFrontendWasNotSet() {
		$backend = $this->setUpBackend();
		$backend->flushByTag(array());
	}

	/**
	 * @test
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function flushByTagRemovesCacheEntriesWithSpecifiedTag() {
		$backend = $this->setUpBackend();
		$this->setUpMockFrontendOfBackend($backend);

		$data = 'some data' . microtime();
		$backend->set('BackendDbTest1', $data, array('UnitTestTag%test', 'UnitTestTag%boring'));
		$backend->set('BackendDbTest2', $data, array('UnitTestTag%test', 'UnitTestTag%special'));
		$backend->set('BackendDbTest3', $data, array('UnitTestTag%test'));

		$backend->flushByTag('UnitTestTag%special');

		$this->assertTrue($backend->has('BackendDbTest1'), 'BackendDbTest1 does not exist anymore.');
		$this->assertFalse($backend->has('BackendDbTest2'), 'BackendDbTest2 still exists.');
		$this->assertTrue($backend->has('BackendDbTest3'), 'BackendDbTest3 does not exist anymore.');

		$tagEntriesFound = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			$this->testingTagsTable,
			'tag = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('UnitTestTag%special', $this->testingTagsTable)
		);
		$this->assertEquals(0, count($tagEntriesFound));
	}


	/**
	 * @test
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function hasReturnsTheCorrectResultForEntryWithExceededLifetime() {
		$backend = $this->setUpBackend();
		$mockCache = $this->setUpMockFrontendOfBackend($backend);

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendDbTest';

		$backend->set($entryIdentifier, $data);

		$expiredEntryIdentifier = 'ExpiredBackendDbTest';
		$expiredData = 'some old data' . microtime();
		$backend->set($expiredEntryIdentifier, $expiredData, array(), 1);

		$GLOBALS['EXEC_TIME'] += 2;
			// setCache calls initializeCommonReferences which recalculate expire statement
			// needed after manual $GLOBALS['EXEC_TIME'] manipulation
		$backend->setCache($mockCache);

		$this->assertFalse($backend->has($expiredEntryIdentifier));
	}

	/**
	 * @test
	 * @author Christian Kuhn <lolli@schwarzbu.ch>
	 */
	public function hasReturnsTrueForEntryWithUnlimitedLifetime() {
		$backend = $this->setUpBackend();
		$mockCache = $this->setUpMockFrontendOfBackend($backend);

		$entryIdentifier = 'BackendDbTest';

		$backend->set($entryIdentifier, 'data', array(), 0);

		$GLOBALS['EXEC_TIME'] += 1;
			// setCache calls initializeCommonReferences which recalculate expire statement
			// needed after manual $GLOBALS['EXEC_TIME'] manipulation
		$backend->setCache($mockCache);

		$this->assertTrue($backend->has($entryIdentifier));
	}

	/**
	 * @test
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function getReturnsFalseForEntryWithExceededLifetime() {
		$backend = $this->setUpBackend();
		$mockCache = $this->setUpMockFrontendOfBackend($backend);

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendDbTest';

		$backend->set($entryIdentifier, $data);

		$expiredEntryIdentifier = 'ExpiredBackendDbTest';
		$expiredData = 'some old data' . microtime();
		$backend->set($expiredEntryIdentifier, $expiredData, array(), 1);

		$GLOBALS['EXEC_TIME'] += 2;
			// setCache calls initializeCommonReferences which recalculate expire statement
			// needed after manual $GLOBALS['EXEC_TIME'] manipulation
		$backend->setCache($mockCache);

		$this->assertEquals($data, $backend->get($entryIdentifier));
		$this->assertFalse($backend->get($expiredEntryIdentifier));
	}

	/**
	 * @test
	 * @expectedException t3lib_cache_Exception
	 */
	public function findIdentifiersByTagThrowsExceptionIfFrontendWasNotSet() {
		$backend = $this->setUpBackend();
		$backend->findIdentifiersByTag('identifier');
	}

	/**
	 * @test
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function findIdentifiersByTagReturnsEmptyArrayForEntryWithExceededLifetime() {
		$backend = $this->setUpBackend();
		$mockCache = $this->setUpMockFrontendOfBackend($backend);

		$backend->set('BackendDbTest', 'some data', array('UnitTestTag%special'), 1);

		$GLOBALS['EXEC_TIME'] += 2;
			// setCache calls initializeCommonReferences which recalculate expire statement
			// needed after manual $GLOBALS['EXEC_TIME'] manipulation
		$backend->setCache($mockCache);

		$this->assertEquals(array(), $backend->findIdentifiersByTag('UnitTestTag%special'));
	}

	/**
	 * @test
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function setWithUnlimitedLifetimeWritesCorrectEntry() {
		$backend = $this->setUpBackend();
		$this->setUpMockFrontendOfBackend($backend);

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendFileTest';

		$backend->set($entryIdentifier, $data, array(), 0);

		$entryFound = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
			'*',
			$this->testingCacheTable,
			''
		);

		$this->assertTrue(is_array($entryFound));

		$retrievedData = $entryFound['content'];
		$this->assertEquals($data, $retrievedData);
	}
}

?>