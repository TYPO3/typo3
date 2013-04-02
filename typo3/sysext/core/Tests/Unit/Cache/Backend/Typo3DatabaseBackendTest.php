<?php
namespace TYPO3\CMS\Core\Tests\Unit\Cache\Backend;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Ingo Renner <ingo@typo3.org>
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
 * Test case
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class Typo3DatabaseBackendTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var string Name of the testing data table
	 */
	protected $testingCacheTable;

	/**
	 * @var string Name of the testing tags table
	 */
	protected $testingTagsTable;

	/**
	 * Set up testcases
	 */
	public function setUp() {
		$tablePrefix = 'cf_';
		$this->testingCacheTable = $tablePrefix . 'Testing';
		$this->testingTagsTable = $tablePrefix . 'Testing_tags';
	}

	/**
	 * Sets up the backend used for testing
	 *
	 * @return void
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
		$backend = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend', 'Testing', $backendOptions);
		return $backend;
	}

	/**
	 * Helper method to inject a mock frontend to backend instance
	 *
	 * @param \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend $backend Current backend instance
	 * @return \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface Mock frontend
	 */
	protected function setUpMockFrontendOfBackend(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend $backend) {
		$mockCache = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('Testing'));
		$backend->setCache($mockCache);
		return $mockCache;
	}

	/**

	 */
	public function tearDown() {
		$GLOBALS['TYPO3_DB']->sql_query('DROP TABLE IF EXISTS ' . $this->testingCacheTable . ';');
		$GLOBALS['TYPO3_DB']->sql_query('DROP TABLE IF EXISTS ' . $this->testingTagsTable . ';');
	}

	/**
	 * @test
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
	 * @expectedException \TYPO3\CMS\Core\Cache\Exception
	 */
	public function setThrowsExceptionIfFrontendWasNotSet() {
		$backend = $this->setUpBackend();
		$backend->set('identifier', 'data');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Core\Cache\Exception\InvalidDataException
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
	 */
	public function setInsertsEntryInTable() {
		$backend = $this->setUpBackend();
		$this->setUpMockFrontendOfBackend($backend);
		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendDbTest';
		$backend->set($entryIdentifier, $data);
		$entryFound = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', $this->testingCacheTable, 'identifier = \'' . $entryIdentifier . '\'');
		$this->assertEquals($data, $entryFound['content']);
	}

	/**
	 * @test
	 */
	public function setRemovesAnAlreadyExistingCacheEntryForTheSameIdentifier() {
		$backend = $this->setUpBackend();
		$this->setUpMockFrontendOfBackend($backend);
		$data1 = 'some data' . microtime();
		$data2 = $data1 . '_different';
		$entryIdentifier = 'BackendDbRemoveBeforeSetTest';
		$backend->set($entryIdentifier, $data1, array(), 500);
		$backend->set($entryIdentifier, $data2, array(), 200);
		$entriesFound = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', $this->testingCacheTable, 'identifier = \'' . $entryIdentifier . '\'');
		$this->assertEquals(1, count($entriesFound));
	}

	/**
	 * @test
	 */
	public function setReallySavesSpecifiedTags() {
		$backend = $this->setUpBackend();
		$this->setUpMockFrontendOfBackend($backend);
		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendDbTest';
		$backend->set($entryIdentifier, $data, array('UnitTestTag%tag1', 'UnitTestTag%tag2'));
		$entriesFound = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', $this->testingTagsTable, 'identifier = \'' . $entryIdentifier . '\'');
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
	 */
	public function setSavesCompressedDataWithEnabledCompression() {
		$backend = $this->setUpBackend(array(
			'compression' => TRUE
		));
		$this->setUpMockFrontendOfBackend($backend);
		$data = 'some data ' . microtime();
		$entryIdentifier = 'BackendDbTest';
		$backend->set($entryIdentifier, $data);
		$entry = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('content', $this->testingCacheTable, 'identifier = \'' . $entryIdentifier . '\'');
		$this->assertEquals($data, @gzuncompress($entry['content']));
	}

	/**
	 * @test
	 */
	public function setSavesPlaintextDataWithEnabledCompressionAndCompressionLevel0() {
		$backend = $this->setUpBackend(array(
			'compression' => TRUE,
			'compressionLevel' => 0
		));
		$this->setUpMockFrontendOfBackend($backend);
		$data = 'some data ' . microtime();
		$entryIdentifier = 'BackendDbTest';
		$backend->set($entryIdentifier, $data);
		$entry = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('content', $this->testingCacheTable, 'identifier = \'' . $entryIdentifier . '\'');
		$this->assertGreaterThan(0, substr_count($entry['content'], $data));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Core\Cache\Exception
	 */
	public function getThrowsExceptionIfFrontendWasNotSet() {
		$backend = $this->setUpBackend();
		$backend->get('identifier');
	}

	/**
	 * @test
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
	 * @expectedException \TYPO3\CMS\Core\Cache\Exception
	 */
	public function hasThrowsExceptionIfFrontendWasNotSet() {
		$backend = $this->setUpBackend();
		$backend->has('identifier');
	}

	/**
	 * @test
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
	 * @expectedException \TYPO3\CMS\Core\Cache\Exception
	 */
	public function removeThrowsExceptionIfFrontendWasNotSet() {
		$backend = $this->setUpBackend();
		$backend->remove('identifier');
	}

	/**
	 * @test
	 */
	public function removeReallyRemovesACacheEntry() {
		$backend = $this->setUpBackend();
		$this->setUpMockFrontendOfBackend($backend);
		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendDbRemovalTest';
		$backend->set($entryIdentifier, $data);
		$backend->remove($entryIdentifier);
		$entriesFound = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', $this->testingCacheTable, 'identifier = \'' . $entryIdentifier . '\'');
		$this->assertTrue(count($entriesFound) == 0);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Core\Cache\Exception
	 */
	public function collectGarbageThrowsExceptionIfFrontendWasNotSet() {
		$backend = $this->setUpBackend();
		$backend->collectGarbage();
	}

	/**
	 * @test
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
		$entriesFound = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', $this->testingCacheTable, 'identifier = \'' . $entryIdentifier . '\'');
		$this->assertTrue(count($entriesFound) == 0);
	}

	/**
	 * @test
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
		$entriesFound = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', $this->testingCacheTable, '');
		$this->assertTrue(count($entriesFound) == 0);
	}

	/**
	 * @test
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
	 * @expectedException \TYPO3\CMS\Core\Cache\Exception
	 */
	public function flushThrowsExceptionIfFrontendWasNotSet() {
		$backend = $this->setUpBackend();
		$backend->flush();
	}

	/**
	 * @test
	 */
	public function flushRemovesAllCacheEntries() {
		$backend = $this->setUpBackend();
		$this->setUpMockFrontendOfBackend($backend);
		$data = 'some data' . microtime();
		$backend->set('BackendDbTest1', $data, array('UnitTestTag%test'));
		$backend->set('BackendDbTest2', $data, array('UnitTestTag%test', 'UnitTestTag%special'));
		$backend->set('BackendDbTest3', $data, array('UnitTestTag%test'));
		$backend->flush();
		$entriesFound = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', $this->testingCacheTable, '');
		$this->assertTrue(count($entriesFound) == 0);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Core\Cache\Exception
	 */
	public function flushByTagThrowsExceptionIfFrontendWasNotSet() {
		$backend = $this->setUpBackend();
		$backend->flushByTag(array());
	}

	/**
	 * @test
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
		$tagEntriesFound = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', $this->testingTagsTable, 'tag = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('UnitTestTag%special', $this->testingTagsTable));
		$this->assertEquals(0, count($tagEntriesFound));
	}

	/**
	 * @test
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
	 * @expectedException \TYPO3\CMS\Core\Cache\Exception
	 */
	public function findIdentifiersByTagThrowsExceptionIfFrontendWasNotSet() {
		$backend = $this->setUpBackend();
		$backend->findIdentifiersByTag('identifier');
	}

	/**
	 * @test
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
	 */
	public function setWithUnlimitedLifetimeWritesCorrectEntry() {
		$backend = $this->setUpBackend();
		$this->setUpMockFrontendOfBackend($backend);
		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendFileTest';
		$backend->set($entryIdentifier, $data, array(), 0);
		$entryFound = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', $this->testingCacheTable, '');
		$this->assertTrue(is_array($entryFound));
		$retrievedData = $entryFound['content'];
		$this->assertEquals($data, $retrievedData);
	}

}

?>