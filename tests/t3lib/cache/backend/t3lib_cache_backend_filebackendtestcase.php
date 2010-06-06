<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Ingo Renner <ingo@typo3.org>
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


	// TODO implement autoloading so that we only require stuff we really need
require_once(PATH_t3lib . 'class.t3lib_cache.php');

require_once(PATH_t3lib . 'cache/backend/interfaces/interface.t3lib_cache_backend_backend.php');
require_once(PATH_t3lib . 'cache/frontend/interfaces/interface.t3lib_cache_frontend_frontend.php');

require_once(PATH_t3lib . 'cache/backend/class.t3lib_cache_backend_abstractbackend.php');
require_once(PATH_t3lib . 'cache/frontend/class.t3lib_cache_frontend_abstractfrontend.php');
require_once(PATH_t3lib . 'cache/class.t3lib_cache_exception.php');
require_once(PATH_t3lib . 'cache/class.t3lib_cache_factory.php');
require_once(PATH_t3lib . 'cache/class.t3lib_cache_manager.php');
require_once(PATH_t3lib . 'cache/frontend/class.t3lib_cache_frontend_variablefrontend.php');

require_once(PATH_t3lib . 'cache/exception/class.t3lib_cache_exception_classalreadyloaded.php');
require_once(PATH_t3lib . 'cache/exception/class.t3lib_cache_exception_duplicateidentifier.php');
require_once(PATH_t3lib . 'cache/exception/class.t3lib_cache_exception_invalidbackend.php');
require_once(PATH_t3lib . 'cache/exception/class.t3lib_cache_exception_invalidcache.php');
require_once(PATH_t3lib . 'cache/exception/class.t3lib_cache_exception_invaliddata.php');
require_once(PATH_t3lib . 'cache/exception/class.t3lib_cache_exception_nosuchcache.php');

require_once(PATH_t3lib . 'cache/backend/class.t3lib_cache_backend_filebackend.php');

/**
 * Testcase for the File cache backend
 *
 * This file is a backport from FLOW3
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage tests
 * @version $Id$
 */
class t3lib_cache_backend_FileBackendTestCase extends tx_phpunit_testcase {

	/**
	 * If set, the tearDown() method will clean up the cache subdirectory used by this unit test.
	 *
	 * @var t3lib_cache_backend_FileBackend
	 */
	protected $backend;

	protected $testingCacheDirectory;

	/**
	 * Sets up this testcase
	 *
	 * @return void
	 */
	public function setUp() {
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
	 * @expectedException t3lib_cache_Exception
	 */
	public function setCacheDirectoryThrowsExceptionOnNonWritableDirectory() {
		if (DIRECTORY_SEPARATOR == '\\') {
			$this->markTestSkipped('test not reliable in Windows environment');
		}
		$directoryName = '/sbin';

		$this->backend->setCacheDirectory($directoryName);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function getCacheDirectoryReturnsThePreviouslySetDirectory() {
		$directory = $this->testingCacheDirectory;
		$fullPathToDirectory = t3lib_div::getIndpEnv('TYPO3_DOCUMENT_ROOT') . '/' . $directory;

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
		$cache = $this->getMock('t3lib_cache_frontend_AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'),
			array(),
			'',
			FALSE
		);

		$data = array('Some data');
		$entryIdentifier = 'BackendFileTest';

		$this->backend->setCache($cache);

		$this->backend->set($entryIdentifier, $data);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function setReallySavesToTheSpecifiedDirectory() {
		$cacheIdentifier = 'UnitTestCache';
		$cache = $this->getMock('t3lib_cache_frontend_AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'),
			array(),
			'',
			FALSE
		);

		$cache->expects($this->atLeastOnce())
			->method('getIdentifier')
			->will($this->returnValue($cacheIdentifier));

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendFileTest';
		$entryIdentifierHash = sha1($entryIdentifier);

		$this->backend->setCache($cache);
		$this->backend->set($entryIdentifier, $data);

		$cacheDirectory = $this->backend->getCacheDirectory();
		$pathAndFilename = $cacheDirectory
			. 'data/'
			. $cacheIdentifier . '/'
			. $entryIdentifierHash[0] . '/'
			. $entryIdentifierHash[1] . '/'
			. $entryIdentifier;
		$this->assertTrue(file_exists($pathAndFilename), 'File does not exist.');
		$retrievedData = file_get_contents($pathAndFilename, NULL, NULL, t3lib_cache_backend_FileBackend::EXPIRYTIME_LENGTH);
		$this->assertEquals(
			$data,
			$retrievedData,
			'The original and the retrieved data don\'t match.'
		);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function setOverwritesAnAlreadyExistingCacheEntryForTheSameIdentifier() {
		$cacheIdentifier = 'UnitTestCache';
		$cache = $this->getMock('t3lib_cache_frontend_AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'),
			array(),
			'',
			FALSE
		);
		$cache->expects($this->atLeastOnce())
			->method('getIdentifier')
			->will($this->returnValue($cacheIdentifier));

		$data1 = 'some data' . microtime();
		$data2 = 'some data' . microtime();
		$entryIdentifier = 'BackendFileRemoveBeforeSetTest';
		$entryIdentifierHash = sha1($entryIdentifier);

		$this->backend->setCache($cache);
		$this->backend->set($entryIdentifier, $data1, array(), 500);
			// setting a second entry with the same identifier, but different
			// data, this should _replace_ the existing one we set before
		$this->backend->set($entryIdentifier, $data2, array(), 200);

		$cacheDirectory = $this->backend->getCacheDirectory();
		$pathAndFilename = $cacheDirectory
			. 'data/'
			. $cacheIdentifier . '/'
			. $entryIdentifierHash[0] . '/'
			. $entryIdentifierHash[1] . '/'
			. $entryIdentifier;
		$this->assertTrue(file_exists($pathAndFilename), 'File does not exist.');
		$retrievedData = file_get_contents($pathAndFilename, NULL, NULL, t3lib_cache_backend_FileBackend::EXPIRYTIME_LENGTH);
		$this->assertEquals($data2, $retrievedData);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function setReallySavesSpecifiedTags() {
		$cacheIdentifier = 'UnitTestCache';
		$cache = $this->getMock('t3lib_cache_frontend_AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'),
			array(),
			'',
			FALSE
		);
		$cache->expects($this->atLeastOnce())
			->method('getIdentifier')
			->will($this->returnValue($cacheIdentifier));

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendFileTest';
		$entryIdentifierHash = sha1($entryIdentifier);

		$this->backend->setCache($cache);
		$tagsDirectory = $this->backend->getCacheDirectory() . 'tags/';

		$this->backend->set($entryIdentifier, $data, array('UnitTestTag%tag1', 'UnitTestTag%tag2'));

		$this->assertTrue(is_dir($tagsDirectory . 'UnitTestTag%tag1'), 'Tag directory UnitTestTag%tag1 does not exist.');
		$this->assertTrue(is_dir($tagsDirectory . 'UnitTestTag%tag2'), 'Tag directory UnitTestTag%tag2 does not exist.');

		$filename = $tagsDirectory . 'UnitTestTag%tag1/' . $cacheIdentifier . t3lib_cache_backend_FileBackend::SEPARATOR . $entryIdentifier;
		$this->assertTrue(file_exists($filename), 'File "' . $filename . '" does not exist.');

		$filename = $tagsDirectory . 'UnitTestTag%tag2/' . $cacheIdentifier . t3lib_cache_backend_FileBackend::SEPARATOR . $entryIdentifier;
		$this->assertTrue(file_exists($filename), 'File "' . $filename . '" does not exist.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function getReturnsContentOfTheCorrectCacheFile() {
		$cacheIdentifier = 'UnitTestCache';
		$cache = $this->getMock('t3lib_cache_frontend_AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'),
			array(),
			'',
			FALSE
		);
		$cache->expects($this->atLeastOnce())
			->method('getIdentifier')
			->will($this->returnValue($cacheIdentifier));

		$this->backend->setCache($cache);

		$entryIdentifier = 'BackendFileTest';

		$data = 'some data' . microtime();
		$this->backend->set($entryIdentifier, $data, array(), 500);

		$data = 'some other data' . microtime();
		$this->backend->set($entryIdentifier, $data, array(), 100);

		$loadedData = $this->backend->get($entryIdentifier);

		$this->assertEquals($data, $loadedData, 'The original and the retrieved data don\'t match.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function hasReturnsTheCorrectResult() {
		$cacheIdentifier = 'UnitTestCache';
		$cache = $this->getMock('t3lib_cache_frontend_AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'),
			array(),
			'',
			FALSE
		);
		$cache->expects($this->atLeastOnce())
			->method('getIdentifier')
			->will($this->returnValue($cacheIdentifier));

		$this->backend->setCache($cache);

		$entryIdentifier = 'BackendFileTest';

		$data = 'some data' . microtime();
		$this->backend->set($entryIdentifier, $data);

		$this->assertTrue($this->backend->has($entryIdentifier), 'has() did not return TRUE.');
		$this->assertFalse($this->backend->has($entryIdentifier . 'Not'), 'has() did not return FALSE.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function removeReallyRemovesACacheEntry() {
		$cacheIdentifier = 'UnitTestCache';
		$cache = $this->getMock('t3lib_cache_frontend_AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'),
			array(),
			'',
			FALSE
		);
		$cache->expects($this->atLeastOnce())
			->method('getIdentifier')
			->will($this->returnValue($cacheIdentifier));

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendFileRemovalTest';
		$entryIdentifierHash = sha1($entryIdentifier);

		$cacheDirectory = $this->backend->getCacheDirectory();
		$this->backend->setCache($cache);

		$pathAndFilename = $cacheDirectory
			. 'data/'
			. $cacheIdentifier . '/'
			. $entryIdentifierHash[0] . '/'
			. $entryIdentifierHash[1] . '/'
			. $entryIdentifier;

		$this->backend->set($entryIdentifier, $data);
		$this->assertTrue(file_exists($pathAndFilename), 'The cache entry does not exist.');

		$this->backend->remove($entryIdentifier);
		$this->assertFalse(file_exists($pathAndFilename), 'The cache entry still exists.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function collectGarbageReallyRemovesAnExpiredCacheEntry() {
		$cacheIdentifier = 'UnitTestCache';
		$cache = $this->getMock('t3lib_cache_frontend_AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'),
			array(),
			'',
			FALSE
		);
		$cache->expects($this->atLeastOnce())
			->method('getIdentifier')
			->will($this->returnValue($cacheIdentifier));

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendFileRemovalTest';
		$entryIdentifierHash = sha1($entryIdentifier);

		$cacheDirectory = $this->backend->getCacheDirectory();
		$this->backend->setCache($cache);

		$pathAndFilename = $cacheDirectory
			. 'data/'
			. $cacheIdentifier . '/'
			. $entryIdentifierHash[0] . '/'
			. $entryIdentifierHash[1] . '/'
			. $entryIdentifier;

		$this->backend->set($entryIdentifier, $data, array(), 1);
		$this->assertTrue(file_exists($pathAndFilename), 'The cache entry does not exist.');

		sleep(2);

		$this->backend->collectGarbage();
		$this->assertFalse(file_exists($pathAndFilename), 'The cache entry still exists.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function collectGarbageReallyRemovesAllExpiredCacheEntries() {
		$cacheIdentifier = 'UnitTestCache';
		$cache = $this->getMock('t3lib_cache_frontend_AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'),
			array(),
			'',
			FALSE
		);
		$cache->expects($this->atLeastOnce())
			->method('getIdentifier')
			->will($this->returnValue($cacheIdentifier));

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendFileRemovalTest';

		$cacheDirectory = $this->backend->getCacheDirectory();
		$this->backend->setCache($cache);

		$pattern = $cacheDirectory
			. 'data/'
			. $cacheIdentifier . '/*/*/'
			. $entryIdentifier
			. '?';

		$this->backend->set($entryIdentifier . 'A', $data, array(), 1);
		$this->backend->set($entryIdentifier . 'B', $data, array(), 1);
		$this->backend->set($entryIdentifier . 'C', $data, array(), 1);
		$filesFound = glob($pattern);
		$this->assertTrue(is_array($filesFound) && count($filesFound) > 0, 'The cache entries do not exist.');

		sleep(2);

		$this->backend->collectGarbage();
		$filesFound = is_array(glob($pattern)) ? glob($pattern) : array();
		$this->assertTrue(count($filesFound) === 0, 'The cache entries still exist.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function removeReallyRemovesTagsOfRemovedEntry() {
		$cacheIdentifier = 'UnitTestCache';
		$cache = $this->getMock('t3lib_cache_frontend_AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'),
			array(),
			'',
			FALSE
		);
		$cache->expects($this->atLeastOnce())
			->method('getIdentifier')
			->will($this->returnValue($cacheIdentifier));

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendFileTest';

		$this->backend->setCache($cache);

		$tagsDirectory = $this->backend->getCacheDirectory() . 'tags/';

		$this->backend->set($entryIdentifier, $data, array('UnitTestTag%tag1', 'UnitTestTag%tag2'));
		$this->backend->remove($entryIdentifier);

		$this->assertTrue(!file_exists($tagsDirectory . 'UnitTestTag%tag1/' . $entryIdentifier), 'File "' . $tagsDirectory . 'UnitTestTag%tag1/' . $entryIdentifier . '" still exists.');
		$this->assertTrue(!file_exists($tagsDirectory . 'UnitTestTag%tag2/' . $entryIdentifier), 'File "' . $tagsDirectory . 'UnitTestTag%tag2/' . $entryIdentifier . '" still exists.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function findIdentifiersByTagFindsCacheEntriesWithSpecifiedTag() {
		$cacheIdentifier = 'UnitTestCache';
		$cache = $this->getMock('t3lib_cache_frontend_AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'),
			array(),
			'',
			FALSE
		);
		$cache->expects($this->atLeastOnce())
			->method('getIdentifier')
			->will($this->returnValue($cacheIdentifier));

		$this->backend->setCache($cache);

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
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function flushRemovesAllCacheEntriesAndRelatedTags() {
		$cacheIdentifier = 'UnitTestCache';
		$cache = $this->getMock('t3lib_cache_frontend_AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'),
			array(),
			'',
			FALSE
		);
		$cache->expects($this->atLeastOnce())
			->method('getIdentifier')
			->will($this->returnValue($cacheIdentifier));

		$this->backend->setCache($cache);

		$tagsDirectory = $this->backend->getCacheDirectory() . 'tags/';
		$cacheDirectory = $this->backend->getCacheDirectory() . 'data/' . $cacheIdentifier . '/';

		$data = 'some data' . microtime();
		$this->backend->set('BackendFileTest1', $data, array('UnitTestTag%test'));
		$this->backend->set('BackendFileTest2', $data, array('UnitTestTag%test', 'UnitTestTag%special'));
		$this->backend->set('BackendFileTest3', $data, array('UnitTestTag%test'));

		$this->backend->flush();

		$pattern = $cacheDirectory . '*/*/*';
		$filesFound = is_array(glob($pattern)) ? glob($pattern) : array();
		$this->assertTrue(count($filesFound) === 0, 'Still files in the cache directory');

		$tagPrefixTest = $tagsDirectory . 'UnitTestTag%test/' . $cacheIdentifier . '^';
		$tagPrefixSpecial = $tagsDirectory . 'UnitTestTag%special/' . $cacheIdentifier . '^';
		$entryIdentifier = 'BackendFileTest1';
		$this->assertTrue(!file_exists($tagPrefixTest . $entryIdentifier), 'File "' . $tagPrefixTest . $entryIdentifier . '" still exists.');
		$entryIdentifier = 'BackendFileTest2';
		$this->assertTrue(!file_exists($tagPrefixTest . $entryIdentifier), 'File "' . $tagPrefixTest . $entryIdentifier . '" still exists.');
		$this->assertTrue(!file_exists($tagPrefixSpecial . $entryIdentifier), 'File "' . $tagPrefixSpecial . $entryIdentifier . '" still exists.');
		$entryIdentifier = 'BackendFileTest3';
		$this->assertTrue(!file_exists($tagPrefixTest . $entryIdentifier), 'File "' . $tagPrefixTest . $entryIdentifier . '" still exists.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function flushByTagRemovesCacheEntriesWithSpecifiedTag() {
		$cacheIdentifier = 'UnitTestCache';
		$cache = $this->getMock('t3lib_cache_frontend_AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'),
			array(),
			'',
			FALSE
		);
		$cache->expects($this->atLeastOnce())
			->method('getIdentifier')
			->will($this->returnValue($cacheIdentifier));

		$this->backend->setCache($cache);

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
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function hasReturnsTheCorrectResultForEntryWithExceededLifetime() {
		$cacheIdentifier = 'UnitTestCache';
		$cache = $this->getMock('t3lib_cache_frontend_AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'),
			array(),
			'',
			FALSE
		);
		$cache->expects($this->atLeastOnce())
			->method('getIdentifier')
			->will($this->returnValue($cacheIdentifier));

		$this->backend->setCache($cache);

		$entryIdentifier = 'BackendFileTest';
		$data = 'some data' . microtime();
		$this->backend->set($entryIdentifier, $data);

		$expiredEntryIdentifier = 'ExpiredBackendFileTest';
		$expiredData = 'some old data' . microtime();
		$this->backend->set($expiredEntryIdentifier, $expiredData, array(), 1);

		sleep(2);

		$this->assertFalse($this->backend->has($expiredEntryIdentifier), 'has() did not return FALSE.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function getReturnsFalseForEntryWithExceededLifetime() {
		$cacheIdentifier = 'UnitTestCache';
		$cache = $this->getMock('t3lib_cache_frontend_AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'),
			array(),
			'',
			FALSE
		);
		$cache->expects($this->atLeastOnce())
			->method('getIdentifier')
			->will($this->returnValue($cacheIdentifier));

		$this->backend->setCache($cache);

		$entryIdentifier = 'BackendFileTest';
		$data = 'some data' . microtime();
		$this->backend->set($entryIdentifier, $data);

		$expiredEntryIdentifier = 'ExpiredBackendFileTest';
		$expiredData = 'some old data' . microtime();
		$this->backend->set($expiredEntryIdentifier, $expiredData, array(), 1);

		sleep(2);

		$this->assertEquals($data, $this->backend->get($entryIdentifier), 'The original and the retrieved data don\'t match.');
		$this->assertFalse($this->backend->get($expiredEntryIdentifier), 'The expired entry could be loaded.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function findIdentifiersByTagReturnsEmptyArrayForEntryWithExceededLifetime() {
		$cacheIdentifier = 'UnitTestCache';
		$cache = $this->getMock('t3lib_cache_frontend_AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'),
			array(),
			'',
			FALSE
		);
		$cache->expects($this->atLeastOnce())
			->method('getIdentifier')
			->will($this->returnValue($cacheIdentifier));

		$this->backend->setCache($cache);

		$this->backend->set('BackendFileTest', 'some data', array('UnitTestTag%special'), 1);

		sleep(2);

		$this->assertEquals(array(), $this->backend->findIdentifiersByTag('UnitTestTag%special'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function setWithUnlimitedLifetimeWritesCorrectEntry() {
		$cacheIdentifier = 'UnitTestCache';
		$cache = $this->getMock('t3lib_cache_frontend_AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'),
			array(),
			'',
			FALSE
		);
		$cache->expects($this->atLeastOnce())
			->method('getIdentifier')
			->will($this->returnValue($cacheIdentifier));

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendFileTest';
		$entryIdentifierHash = sha1($entryIdentifier);

		$this->backend->setCache($cache);
		$this->backend->set($entryIdentifier, $data, array(), 0);

		$cacheDirectory = $this->backend->getCacheDirectory();

		$pathAndFilename = $cacheDirectory
			. 'data/'
			. $cacheIdentifier . '/'
			. $entryIdentifierHash[0] . '/'
			. $entryIdentifierHash[1] . '/'
			. $entryIdentifier;
		$this->assertTrue(file_exists($pathAndFilename), 'File not found.');

		$retrievedData = file_get_contents($pathAndFilename, NULL, NULL, t3lib_cache_backend_FileBackend::EXPIRYTIME_LENGTH);
		$this->assertEquals($data, $retrievedData, 'The original and the retrieved data don\'t match.');
	}

	/**
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function tearDown() {
		if (is_object($this->backend)) {
			$directory = $this->backend->getCacheDirectory();
			if (is_dir($directory)) {
				t3lib_div::rmdir($directory, true);
			}
		}
	}
}

?>