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
 * Testcase for the cache to memcached backend
 *
 * This file is a backport from FLOW3
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage tests
 */
class t3lib_cache_backend_MemcachedBackendTest extends tx_phpunit_testcase {

	/**
	 * Sets up this testcase
	 *
	 * @return void
	 * @author Christian Jul Jensen <julle@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function setUp() {
		if (!extension_loaded('memcache')) {
			$this->markTestSkipped('memcache extension was not available');
		}

		try {
			if (!fsockopen('localhost', 11211)) {
				$this->markTestSkipped('memcached not reachable');
			}
		} catch (Exception $e) {
			$this->markTestSkipped('memcached not reachable');
		}
	}

	/**
	 * @test
	 * @author Christian Jul Jensen <julle@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 * @expectedException t3lib_cache_Exception
	 */
	public function setThrowsExceptionIfNoFrontEndHasBeenSet() {
		$backendOptions = array('servers' => array('localhost:11211'));
		$backend = new t3lib_cache_backend_MemcachedBackend($backendOptions);

		$data = 'Some data';
		$identifier = uniqid('MyIdentifier');
		$backend->set($identifier, $data);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 * @expectedException t3lib_cache_Exception
	 */
	public function constructorThrowsExceptionIfNoMemcacheServerIsConfigured() {
		$backend = new t3lib_cache_backend_MemcachedBackend();
	}

	/**
	 * @test
	 * @author Christian Jul Jensen <julle@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 * @expectedException t3lib_cache_Exception
	 */
	public function setThrowsExceptionIfConfiguredServersAreUnreachable() {
		$backend = $this->setUpBackend(array('servers' => array('julle.did.this:1234')));
		$data = 'Somedata';
		$identifier = uniqid('MyIdentifier');
		$backend->set($identifier, $data);
	}

	/**
	 * @test
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	public function itIsPossibleToSetAndCheckExistenceInCache() {
		$backend = $this->setUpBackend();
		$data = 'Some data';
		$identifier = uniqid('MyIdentifier');
		$backend->set($identifier, $data);
		$inCache = $backend->has($identifier);
		$this->assertTrue($inCache, 'Memcache failed to set and check entry');
	}

	/**
	 * @test
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	public function itIsPossibleToSetAndGetEntry() {
		$backend = $this->setUpBackend();
		$data = 'Some data';
		$identifier = uniqid('MyIdentifier');
		$backend->set($identifier, $data);
		$fetchedData = $backend->get($identifier);
		$this->assertEquals($data, $fetchedData, 'Memcache failed to set and retrieve data');
	}

	/**
	 * @test
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	public function itIsPossibleToRemoveEntryFromCache() {
		$backend = $this->setUpBackend();
		$data = 'Some data';
		$identifier = uniqid('MyIdentifier');
		$backend->set($identifier, $data);
		$backend->remove($identifier);
		$inCache = $backend->has($identifier);
		$this->assertFalse($inCache, 'Failed to set and remove data from Memcache');
	}

	/**
	 * @test
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	public function itIsPossibleToOverwriteAnEntryInTheCache() {
		$backend = $this->setUpBackend();
		$data = 'Some data';
		$identifier = uniqid('MyIdentifier');
		$backend->set($identifier, $data);
		$otherData = 'some other data';
		$backend->set($identifier, $otherData);
		$fetchedData = $backend->get($identifier);
		$this->assertEquals($otherData, $fetchedData, 'Memcache failed to overwrite and retrieve data');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function findIdentifiersByTagFindsCacheEntriesWithSpecifiedTag() {
		$backend = $this->setUpBackend();

		$data = 'Some data';
		$identifier = uniqid('MyIdentifier');
		$backend->set($identifier, $data, array('UnitTestTag%tag1', 'UnitTestTag%tag2'));

		$retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag1');
		$this->assertEquals($identifier, $retrieved[0], 'Could not retrieve expected entry by tag.');

		$retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag2');
		$this->assertEquals($identifier, $retrieved[0], 'Could not retrieve expected entry by tag.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setRemovesTagsFromPreviousSet() {
		$backend = $this->setUpBackend();

		$data = 'Some data';
		$identifier = uniqid('MyIdentifier');
		$backend->set($identifier, $data, array('UnitTestTag%tag1', 'UnitTestTag%tag2'));
		$backend->set($identifier, $data, array('UnitTestTag%tag3'));

		$retrieved = $backend->findIdentifiersByTag('UnitTestTag%tagX');
		$this->assertEquals(array(), $retrieved, 'Found entry which should no longer exist.');
	}

	/**
	 * @test
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	public function hasReturnsFalseIfTheEntryDoesntExist() {
		$backend = $this->setUpBackend();
		$identifier = uniqid('NonExistingIdentifier');
		$inCache = $backend->has($identifier);
		$this->assertFalse($inCache,'"has" did not return FALSE when checking on non existing identifier');
	}

	/**
	 * @test
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	public function removeReturnsFalseIfTheEntryDoesntExist() {
		$backend = $this->setUpBackend();
		$identifier = uniqid('NonExistingIdentifier');
		$inCache = $backend->remove($identifier);
		$this->assertFalse($inCache,'"remove" did not return FALSE when checking on non existing identifier');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function flushByTagRemovesCacheEntriesWithSpecifiedTag() {
		$backend = $this->setUpBackend();

		$data = 'some data' . microtime();
		$backend->set('BackendMemcacheTest1', $data, array('UnitTestTag%test', 'UnitTestTag%boring'));
		$backend->set('BackendMemcacheTest2', $data, array('UnitTestTag%test', 'UnitTestTag%special'));
		$backend->set('BackendMemcacheTest3', $data, array('UnitTestTag%test'));

		$backend->flushByTag('UnitTestTag%special');

		$this->assertTrue($backend->has('BackendMemcacheTest1'), 'BackendMemcacheTest1');
		$this->assertFalse($backend->has('BackendMemcacheTest2'), 'BackendMemcacheTest2');
		$this->assertTrue($backend->has('BackendMemcacheTest3'), 'BackendMemcacheTest3');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function flushRemovesAllCacheEntries() {
		$backend = $this->setUpBackend();

		$data = 'some data' . microtime();
		$backend->set('BackendMemcacheTest1', $data);
		$backend->set('BackendMemcacheTest2', $data);
		$backend->set('BackendMemcacheTest3', $data);

		$backend->flush();

		$this->assertFalse($backend->has('BackendMemcacheTest1'), 'BackendMemcacheTest1');
		$this->assertFalse($backend->has('BackendMemcacheTest2'), 'BackendMemcacheTest2');
		$this->assertFalse($backend->has('BackendMemcacheTest3'), 'BackendMemcacheTest3');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function flushRemovesOnlyOwnEntries() {
		$backendOptions = array('servers' => array('localhost:11211'));

		$thisCache = $this->getMock(
			't3lib_cache_frontend_AbstractFrontend',
			array(),
			array(),
			'',
			FALSE
		);
		$thisCache->expects($this->any())
			->method('getIdentifier')
			->will($this->returnValue('thisCache'));
		$thisBackend = new t3lib_cache_backend_MemcachedBackend($backendOptions);
		$thisBackend->setCache($thisCache);

		$thatCache = $this->getMock(
			't3lib_cache_frontend_AbstractFrontend',
			array(),
			array(),
			'',
			FALSE
		);
		$thatCache->expects($this->any())
			->method('getIdentifier')
			->will($this->returnValue('thatCache'));
		$thatBackend = new t3lib_cache_backend_MemcachedBackend($backendOptions);
		$thatBackend->setCache($thatCache);

		$thisBackend->set('thisEntry', 'Hello');
		$thatBackend->set('thatEntry', 'World!');
		$thatBackend->flush();

		$this->assertEquals('Hello', $thisBackend->get('thisEntry'));
		$this->assertFalse($thatBackend->has('thatEntry'));
	}

	/**
	 * Check if we can store ~5 MB of data, this gives some headroom for the
	 * reflection data.
	 *
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function largeDataIsStored() {
		$backend = $this->setUpBackend();

		$data = str_repeat('abcde', 1024 * 1024);
		$backend->set('tooLargeData', $data);

		$this->assertTrue($backend->has('tooLargeData'));
		$this->assertEquals($backend->get('tooLargeData'), $data);
	}

	/**
	 * Sets up the memcached backend used for testing
	 *
	 * @param	array	$backendOptions Options for the memcache backend
	 * @return t3lib_cache_backend_MemcachedBackend
	 * @author Christian Jul Jensen <julle@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	protected function setUpBackend(array $backendOptions = array()) {
		$cache = $this->getMock('t3lib_cache_frontend_Frontend', array(), array(), '', FALSE);
		if (empty($backendOptions)) {
			$backendOptions = array('servers' => array('localhost:11211'));
		}

		$backend = new t3lib_cache_backend_MemcachedBackend($backendOptions);
		$backend->setCache($cache);

		return $backend;
	}
}

?>