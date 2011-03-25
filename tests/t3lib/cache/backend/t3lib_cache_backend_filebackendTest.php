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
 * Testcase for the File cache backend
 *
 * This file is a backport from FLOW3
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 * @package TYPO3
 * @subpackage tests
 */
class t3lib_cache_backend_FileBackendTest extends tx_phpunit_testcase {
	/**
	 * Backup of global variable EXEC_TIME
	 *
	 * @var array
	 */
	protected $backupGlobalVariables;

	/**
	 * If set, the tearDown() method will clean up the cache subdirectory used by this unit test.
	 *
	 * @var t3lib_cache_backend_FileBackend
	 */
	protected $backend;

	/**
	 * @var string Directory for testing data, relative to PATH_site
	 */
	protected $testingCacheDirectory;

	/**
	 * Sets up this testcase
	 *
	 * @return void
	 */
	public function setUp() {
		$this->backupGlobalVariables = array(
			'EXEC_TIME' => $GLOBALS['EXEC_TIME'],
		);

		$this->testingCacheDirectory = 'typo3temp/cache/testing/';

		$this->backend = t3lib_div::makeInstance(
			't3lib_cache_backend_FileBackend',
			array('cacheDirectory' => $this->testingCacheDirectory)
		);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function defaultCacheDirectoryIsWritable() {
		$cacheDirectory = $this->backend->getCacheDirectory();

		$this->assertTrue(is_writable($cacheDirectory), 'The default cache directory "' . $cacheDirectory . '" is not writable.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setCacheDirectoryThrowsExceptionOnNonWritableDirectory() {
		if (TYPO3_OS == 'WIN') {
			$this->markTestSkipped('test not reliable in Windows environment');
		}

			// Create test directory and remove write permissions
		$directoryName = PATH_site . 'typo3temp/' . uniqid('test_');
		t3lib_div::mkdir($directoryName);
		chmod($directoryName, 1551);

		try {
			$this->backend->setCacheDirectory($directoryName);
			$this->fail('setCacheDirectory did not throw an exception on a non writable directory');
		} catch (t3lib_cache_Exception $e) {
				// Remove created test directory
			t3lib_div::rmdir($directoryName);
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function getCacheDirectoryReturnsTheCurrentCacheDirectory() {
		$directory = $this->testingCacheDirectory;
		$fullPathToDirectory = PATH_site . $directory;

		$this->backend->setCacheDirectory($directory);
		$this->assertEquals($fullPathToDirectory, $this->backend->getCacheDirectory(), 'getCacheDirectory() did not return the expected value.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 * @expectedException t3lib_cache_exception_InvalidData
	 */
	public function setThrowsExceptionIfDataIsNotAString() {
		$mockCache = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$this->backend->setCache($mockCache);
		$data = array('Some data');
		$entryIdentifier = 'BackendFileTest';

		$this->backend->set($entryIdentifier, $data);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function setReallySavesToTheSpecifiedDirectory() {
		$mockCache = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendFileTest';
		$this->backend->setCache($mockCache);
		$pathAndFilename = $this->backend->getCacheDirectory() . $entryIdentifier;

		$this->backend->set($entryIdentifier, $data, array(), 10);

		$this->assertFileExists($pathAndFilename);
		$retrievedData = file_get_contents($pathAndFilename, NULL, NULL, 0, strlen($data));
		$this->assertEquals($data, $retrievedData);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function setOverwritesAnAlreadyExistingCacheEntryForTheSameIdentifier() {
		$mockCache = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$data1 = 'some data' . microtime();
		$data2 = 'some data' . microtime();
		$entryIdentifier = 'BackendFileRemoveBeforeSetTest';

		$this->backend->setCache($mockCache);
		$this->backend->set($entryIdentifier, $data1, array(), 500);
			// Setting a second entry with the same identifier, but different
			// data, this should _replace_ the existing one we set before
		$this->backend->set($entryIdentifier, $data2, array(), 200);

		$pathAndFilename = $this->backend->getCacheDirectory() . $entryIdentifier;

		$this->assertFileExists($pathAndFilename);
		$retrievedData = file_get_contents($pathAndFilename, NULL, NULL, 0, strlen($data2));
		$this->assertEquals($data2, $retrievedData);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function setAlsoSavesSpecifiedTags() {
		$mockCache = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendFileTest';

		$this->backend->setCache($mockCache);
		$this->backend->set($entryIdentifier, $data, array('Tag1', 'Tag2'));

		$pathAndFilename = $this->backend->getCacheDirectory() . $entryIdentifier;
		$this->assertFileExists($pathAndFilename);
		$retrievedData = file_get_contents($pathAndFilename, NULL, NULL, (strlen($data) + t3lib_cache_backend_FileBackend::EXPIRYTIME_LENGTH), 9);
		$this->assertEquals('Tag1 Tag2', $retrievedData);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function setWithUnlimitedLifetimeWritesCorrectEntry() {
		$mockCache = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendFileTest';

		$this->backend->setCache($mockCache);
		$pathAndFilename = $this->backend->getCacheDirectory() . $entryIdentifier;

		$this->backend->set($entryIdentifier, $data, array(), 0);

		$this->assertFileExists($pathAndFilename);

		$dataSize = (integer)file_get_contents($pathAndFilename, NULL, NULL, filesize($pathAndFilename) - t3lib_cache_backend_FileBackend::DATASIZE_DIGITS, t3lib_cache_backend_FileBackend::DATASIZE_DIGITS);
		$retrievedData = file_get_contents($pathAndFilename, NULL, NULL, 0, $dataSize);

		$this->assertEquals($data, $retrievedData, 'The original and the retrieved data don\'t match.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getReturnsFalseForExpiredEntries() {
		$mockCache = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$backend = $this->getMock('t3lib_cache_backend_FileBackend', array('isCacheFileExpired'), array(), '', FALSE);
		$fullPathToCacheFile = PATH_site . 'typo3temp/cache/UnitTestCache/ExpiredEntry';
		$backend->expects($this->once())->method('isCacheFileExpired')->with($fullPathToCacheFile)->will($this->returnValue(TRUE));
		$backend->setCache($mockCache);

		$this->assertFalse($backend->get('ExpiredEntry'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function hasReturnsTrueIfAnEntryExists() {
		$mockCache = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$this->backend->setCache($mockCache);

		$entryIdentifier = 'BackendFileTest';
		$data = 'some data' . microtime();
		$this->backend->set($entryIdentifier, $data);

		$this->assertTrue($this->backend->has($entryIdentifier), 'has() did not return TRUE.');
		$this->assertFalse($this->backend->has($entryIdentifier . 'Not'), 'has() did not return FALSE.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function hasReturnsFalseForExpiredEntries() {
		$backend = $this->getMock('t3lib_cache_backend_FileBackend', array('isCacheFileExpired'), array(), '', FALSE);
		$backend->expects($this->exactly(2))->method('isCacheFileExpired')->will($this->onConsecutiveCalls(TRUE, FALSE));

		$this->assertFalse($backend->has('foo'));
		$this->assertTrue($backend->has('bar'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function removeReallyRemovesACacheEntry() {
		$mockCache = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendFileTest';

		$this->backend->setCache($mockCache);
		$pathAndFilename = $this->backend->getCacheDirectory() . $entryIdentifier;
		$this->backend->set($entryIdentifier, $data);

		$this->assertFileExists($pathAndFilename);
		$this->backend->remove($entryIdentifier);
		$this->assertFileNotExists($pathAndFilename);
	}

	/**
	 * @author Christian Kuhn <lolli@schwarzbu.ch>
	 */
	public function invalidEntryIdentifiers() {
		return array(
			'trailing slash' => array('/myIdentifer'),
			'trailing dot and slash' => array('./myIdentifer'),
			'trailing two dots and slash' => array('../myIdentifier'),
			'trailing with multiple dots and slashes' => array('.././../myIdentifier'),
			'slash in middle part' => array('my/Identifier'),
			'dot and slash in middle part' => array('my./Identifier'),
			'two dots and slash in middle part' => array('my../Identifier'),
			'multiple dots and slashes in middle part' => array('my.././../Identifier'),
			'pending slash' => array('myIdentifier/'),
			'pending dot and slash' => array('myIdentifier./'),
			'pending dots and slash' => array('myIdentifier../'),
			'pending multiple dots and slashes' => array('myIdentifier.././../'),
		);
	}

	/**
	 * @test
	 * @dataProvider invalidEntryIdentifiers
	 * @expectedException InvalidArgumentException
	 * @author Christian Kuhn <lolli@schwarzbu.ch>
	 */
	public function setThrowsExceptionForInvalidIdentifier($identifier) {
		$mockCache = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$backend = $this->getMock('t3lib_cache_backend_FileBackend', array('dummy'), array(), '', TRUE);
		$backend->setCache($mockCache);

		$backend->set($identifier, 'cache data', array());
	}

	/**
	 * @test
	 * @dataProvider invalidEntryIdentifiers
	 * @expectedException InvalidArgumentException
	 * @author Christian Kuhn <lolli@schwarzbu.ch>
	 */
	public function getThrowsExceptionForInvalidIdentifier($identifier) {
		$mockCache = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$backend = $this->getMock('t3lib_cache_backend_FileBackend', array('dummy'), array(), '', FALSE);
		$backend->setCache($mockCache);

		$backend->get($identifier);
	}

	/**
	 * @test
	 * @dataProvider invalidEntryIdentifiers
	 * @expectedException InvalidArgumentException
	 * @author Christian Kuhn <lolli@schwarzbu.ch>
	 */
	public function hasThrowsExceptionForInvalidIdentifier($identifier) {
		$backend = $this->getMock('t3lib_cache_backend_FileBackend', array('dummy'), array(), '', FALSE);

		$backend->has($identifier);
	}

	/**
	 * @test
	 * @dataProvider invalidEntryIdentifiers
	 * @expectedException InvalidArgumentException
	 * @author Christian Kuhn <lolli@schwarzbu.ch>
	 */
	public function removeThrowsExceptionForInvalidIdentifier($identifier) {
		$mockCache = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$backend = $this->getMock('t3lib_cache_backend_FileBackend', array('dummy'), array(), '', FALSE);
		$backend->setCache($mockCache);

		$backend->remove($identifier);
	}

	/**
	 * @test
	 * @dataProvider invalidEntryIdentifiers
	 * @expectedException InvalidArgumentException
	 * @author Christian Kuhn <lolli@schwarzbu.ch>
	 */
	public function requireOnceThrowsExceptionForInvalidIdentifier($identifier) {
		$mockCache = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$backend = $this->getMock('t3lib_cache_backend_FileBackend', array('dummy'), array(), '', FALSE);
		$backend->setCache($mockCache);

		$backend->requireOnce($identifier);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function collectGarbageReallyRemovesAnExpiredCacheEntry() {
		$mockCache = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendFileRemovalTest';

		$this->backend->setCache($mockCache);
		$pathAndFilename = $this->backend->getCacheDirectory() . $entryIdentifier;
		$this->backend->set($entryIdentifier, $data, array(), 1);

		$this->assertFileExists($pathAndFilename);

		$GLOBALS['EXEC_TIME'] += 2;
		$this->backend->collectGarbage();

		$this->assertFileNotExists($pathAndFilename);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function collectGarbageReallyRemovesAllExpiredCacheEntries() {
		$mockCache = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendFileRemovalTest';

		$this->backend->setCache($mockCache);
		$pathAndFilename = $this->backend->getCacheDirectory() . $entryIdentifier;

		$this->backend->set($entryIdentifier . 'A', $data, array(), NULL);
		$this->backend->set($entryIdentifier . 'B', $data, array(), 10);
		$this->backend->set($entryIdentifier . 'C', $data, array(), 1);
		$this->backend->set($entryIdentifier . 'D', $data, array(), 1);

		$this->assertFileExists($pathAndFilename . 'A');
		$this->assertFileExists($pathAndFilename . 'B');
		$this->assertFileExists($pathAndFilename . 'C');
		$this->assertFileExists($pathAndFilename . 'D');

		$GLOBALS['EXEC_TIME'] += 2;
		$this->backend->collectGarbage();

		$this->assertFileExists($pathAndFilename . 'A');
		$this->assertFileExists($pathAndFilename . 'B');
		$this->assertFileNotExists($pathAndFilename . 'C');
		$this->assertFileNotExists($pathAndFilename . 'D');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function findIdentifiersByTagFindsCacheEntriesWithSpecifiedTag() {
		$mockCache = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$this->backend->setCache($mockCache);

		$data = 'some data' . microtime();
		$this->backend->set('BackendFileTest1', $data, array('UnitTestTag%test', 'UnitTestTag%boring'));
		$this->backend->set('BackendFileTest2', $data, array('UnitTestTag%test', 'UnitTestTag%special'));
		$this->backend->set('BackendFileTest3', $data, array('UnitTestTag%test'));

		$expectedEntry = 'BackendFileTest2';

		$actualEntries = $this->backend->findIdentifiersByTag('UnitTestTag%special');

		$this->assertTrue(is_array($actualEntries), 'actualEntries is not an array.');
		$this->assertEquals($expectedEntry, array_pop($actualEntries));
	}

	/**
	 * @test
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function findIdentifiersByTagDoesNotReturnExpiredEntries() {
		$mockCache = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$this->backend->setCache($mockCache);

		$data = 'some data';
		$this->backend->set('BackendFileTest1', $data, array('UnitTestTag%test', 'UnitTestTag%boring'));
		$this->backend->set('BackendFileTest2', $data, array('UnitTestTag%test', 'UnitTestTag%special'), -100);
		$this->backend->set('BackendFileTest3', $data, array('UnitTestTag%test'));

		$this->assertSame(array(), $this->backend->findIdentifiersByTag('UnitTestTag%special'));
		$foundIdentifiers = $this->backend->findIdentifiersByTag('UnitTestTag%test');
		sort($foundIdentifiers);
		$this->assertSame(array('BackendFileTest1', 'BackendFileTest3'), $foundIdentifiers);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function flushRemovesAllCacheEntries() {
		$mockCache = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$this->backend->setCache($mockCache);

		$data = 'some data' . microtime();
		$this->backend->set('BackendFileTest1', $data, array('UnitTestTag%test'));
		$this->backend->set('BackendFileTest2', $data, array('UnitTestTag%test', 'UnitTestTag%special'));
		$this->backend->set('BackendFileTest3', $data, array('UnitTestTag%test'));

		$this->backend->flush();

		$pattern = $this->backend->getCacheDirectory() . '*';
		$filesFound = is_array(glob($pattern)) ? glob($pattern) : array();
		$this->assertTrue(count($filesFound) === 0, 'Still files in the cache directory');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function flushByTagRemovesCacheEntriesWithSpecifiedTag() {
		$mockCache = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$this->backend->setCache($mockCache);

		$data = 'some data' . microtime();
		$this->backend->set('BackendFileTest1', $data, array('UnitTestTag%test', 'UnitTestTag%boring'));
		$this->backend->set('BackendFileTest2', $data, array('UnitTestTag%test', 'UnitTestTag%special'));
		$this->backend->set('BackendFileTest3', $data, array('UnitTestTag%test'));

		$this->backend->flushByTag('UnitTestTag%special');

		$this->assertTrue($this->backend->has('BackendFileTest1'), 'BackendFileTest1');
		$this->assertFalse($this->backend->has('BackendFileTest2'), 'BackendFileTest2');
		$this->assertTrue($this->backend->has('BackendFileTest3'), 'BackendFileTest3');
	}

	/**
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function tearDown() {
		if (is_object($this->backend)) {
			$directory = $this->backend->getCacheDirectory();
			if (is_dir($directory)) {
				t3lib_div::rmdir($directory, TRUE);
			}
		}
		foreach ($this->backupGlobalVariables as $key => $data) {
			$GLOBALS[$key] = $data;
		}
	}
}

?>