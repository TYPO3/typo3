<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2010 Ingo Renner <ingo@typo3.org>
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

require_once(PATH_t3lib . 'cache/backend/class.t3lib_cache_backend_apcbackend.php');

/**
 * Testcase for the APC cache backend
 *
 * This file is a backport from FLOW3
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage tests
 * @version $Id$
 */
class t3lib_cache_backend_ApcBackendTest extends tx_phpunit_testcase {

	/**
	 * Sets up this testcase
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function setUp() {
		if (!extension_loaded('apc')) {
			$this->markTestSkipped('APC extension was not available');
		}
	}

	/**
	 * @test
	 * @author Christian Jul Jensen <julle@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @expectedException t3lib_cache_Exception
	 */
	public function setThrowsExceptionIfNoFrontEndHasBeenSet() {
		$backend = new t3lib_cache_backend_ApcBackend();
		$data = 'Some data';
		$identifier = 'MyIdentifier';
		$backend->set($identifier, $data);
	}

	/**
	 * @test
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	public function itIsPossibleToSetAndCheckExistenceInCache() {
		$backend = $this->setUpBackend();
		$data = 'Some data';
		$identifier = 'MyIdentifier';
		$backend->set($identifier, $data);
		$inCache = $backend->has($identifier);
		$this->assertTrue($inCache, 'APC backend failed to set and check entry');
	}

	/**
	 * @test
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	public function itIsPossibleToSetAndGetEntry() {
		$backend = $this->setUpBackend();
		$data = 'Some data';
		$identifier = 'MyIdentifier';
		$backend->set($identifier, $data);
		$fetchedData = $backend->get($identifier);
		$this->assertEquals($data, $fetchedData, 'APC backend failed to set and retrieve data');
	}

	/**
	 * @test
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	public function itIsPossibleToRemoveEntryFromCache() {
		$backend = $this->setUpBackend();
		$data = 'Some data';
		$identifier = 'MyIdentifier';
		$backend->set($identifier, $data);
		$backend->remove($identifier);
		$inCache = $backend->has($identifier);
		$this->assertFalse($inCache, 'Failed to set and remove data from APC backend');
	}

	/**
	 * @test
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	public function itIsPossibleToOverwriteAnEntryInTheCache() {
		$backend = $this->setUpBackend();
		$data = 'Some data';
		$identifier = 'MyIdentifier';
		$backend->set($identifier, $data);
		$otherData = 'some other data';
		$backend->set($identifier, $otherData);
		$fetchedData = $backend->get($identifier);
		$this->assertEquals($otherData, $fetchedData, 'APC backend failed to overwrite and retrieve data');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function findIdentifiersByTagFindsSetEntries() {
		$backend = $this->setUpBackend();

		$data = 'Some data';
		$entryIdentifier = 'MyIdentifier';
		$backend->set($entryIdentifier, $data, array('UnitTestTag%tag1', 'UnitTestTag%tag2'));

		$retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag1');
		$this->assertEquals($entryIdentifier, $retrieved[0], 'Could not retrieve expected entry by tag.');

		$retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag2');
		$this->assertEquals($entryIdentifier, $retrieved[0], 'Could not retrieve expected entry by tag.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setRemovesTagsFromPreviousSet() {
		$backend = $this->setUpBackend();

		$data = 'Some data';
		$entryIdentifier = 'MyIdentifier';
		$backend->set($entryIdentifier, $data, array('UnitTestTag%tag1', 'UnitTestTag%tag2'));
		$backend->set($entryIdentifier, $data, array('UnitTestTag%tag3'));

		$retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag2');
		$this->assertEquals(array(), $retrieved, 'Found entry which should no longer exist.');
	}

	/**
	 * @test
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	public function hasReturnsFalseIfTheEntryDoesntExist() {
		$backend = $this->setUpBackend();
		$identifier = 'NonExistingIdentifier';
		$inCache = $backend->has($identifier);
		$this->assertFalse($inCache,'"has" did not return false when checking on non existing identifier');
	}

	/**
	 * @test
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	public function removeReturnsFalseIfTheEntryDoesntExist() {
		$backend = $this->setUpBackend();
		$identifier = 'NonExistingIdentifier';
		$inCache = $backend->remove($identifier);
		$this->assertFalse($inCache,'"remove" did not return false when checking on non existing identifier');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function flushByTagRemovesCacheEntriesWithSpecifiedTag() {
		$backend = $this->setUpBackend();

		$data = 'some data' . microtime();
		$backend->set('BackendAPCTest1', $data, array('UnitTestTag%test', 'UnitTestTag%boring'));
		$backend->set('BackendAPCTest2', $data, array('UnitTestTag%test', 'UnitTestTag%special'));
		$backend->set('BackendAPCTest3', $data, array('UnitTestTag%test'));

		$backend->flushByTag('UnitTestTag%special');

		$this->assertTrue($backend->has('BackendAPCTest1'), 'BackendAPCTest1');
		$this->assertFalse($backend->has('BackendAPCTest2'), 'BackendAPCTest2');
		$this->assertTrue($backend->has('BackendAPCTest3'), 'BackendAPCTest3');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function flushRemovesAllCacheEntries() {
		$backend = $this->setUpBackend();

		$data = 'some data' . microtime();
		$backend->set('BackendAPCTest1', $data);
		$backend->set('BackendAPCTest2', $data);
		$backend->set('BackendAPCTest3', $data);

		$backend->flush();

		$this->assertFalse($backend->has('BackendAPCTest1'), 'BackendAPCTest1');
		$this->assertFalse($backend->has('BackendAPCTest2'), 'BackendAPCTest2');
		$this->assertFalse($backend->has('BackendAPCTest3'), 'BackendAPCTest3');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function flushRemovesOnlyOwnEntries() {
		$thisCache = $this->getMock('t3lib_cache_frontend_Frontend', array(), array(), '', FALSE);
		$thisCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('thisCache'));
		$thisBackend = new t3lib_cache_backend_ApcBackend();
		$thisBackend->setCache($thisCache);

		$thatCache = $this->getMock('t3lib_cache_frontend_Frontend', array(), array(), '', FALSE);
		$thatCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('thatCache'));
		$thatBackend = new t3lib_cache_backend_ApcBackend();
		$thatBackend->setCache($thatCache);

		$thisBackend->set('thisEntry', 'Hello');
		$thatBackend->set('thatEntry', 'World!');
		$thatBackend->flush();

		$this->assertEquals('Hello', $thisBackend->get('thisEntry'));
		$this->assertFalse($thatBackend->has('thatEntry'));
	}

	/**
	 * Check if we can store ~5 MB of data
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
	 * Sets up the APC backend used for testing
	 *
	 * @param array $backendOptions Options for the APC backend
	 * @return t3lib_cache_backend_ApcBackend
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	protected function setUpBackend() {
		$cache = $this->getMock('t3lib_cache_frontend_Frontend', array(), array(), '', FALSE);
		$backend = new t3lib_cache_backend_ApcBackend();
		$backend->setCache($cache);

		return $backend;
	}
}

?>