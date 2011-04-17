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
 * Testcase for the TransientMemory cache backend
 *
 * This file is a backport from FLOW3
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage tests
 */
class t3lib_cache_backend_TransientMemoryBackendTest extends tx_phpunit_testcase {

	/**
	 * @expectedException t3lib_cache_Exception
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setThrowsExceptionIfNoFrontEndHasBeenSet() {
		$backend = new t3lib_cache_backend_TransientMemoryBackend();

		$data = 'Some data';
		$identifier = 'MyIdentifier';
		$backend->set($identifier, $data);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function itIsPossibleToSetAndCheckExistenceInCache() {
		$cache = $this->getMock('t3lib_cache_frontend_Frontend', array(), array(), '', FALSE);
		$backend = new t3lib_cache_backend_TransientMemoryBackend();
		$backend->setCache($cache);

		$data = 'Some data';
		$identifier = 'MyIdentifier';
		$backend->set($identifier, $data);
		$inCache = $backend->has($identifier);
		$this->assertTrue($inCache);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function itIsPossibleToSetAndGetEntry() {
		$cache = $this->getMock('t3lib_cache_frontend_Frontend', array(), array(), '', FALSE);
		$backend = new t3lib_cache_backend_TransientMemoryBackend();
		$backend->setCache($cache);

		$data = 'Some data';
		$identifier = 'MyIdentifier';
		$backend->set($identifier, $data);
		$fetchedData = $backend->get($identifier);
		$this->assertEquals($data, $fetchedData);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function itIsPossibleToRemoveEntryFromCache() {
		$cache = $this->getMock('t3lib_cache_frontend_Frontend', array(), array(), '', FALSE);
		$backend = new t3lib_cache_backend_TransientMemoryBackend();
		$backend->setCache($cache);

		$data = 'Some data';
		$identifier = 'MyIdentifier';
		$backend->set($identifier, $data);
		$backend->remove($identifier);
		$inCache = $backend->has($identifier);
		$this->assertFalse($inCache);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function itIsPossibleToOverwriteAnEntryInTheCache() {
		$cache = $this->getMock('t3lib_cache_frontend_Frontend', array(), array(), '', FALSE);
		$backend = new t3lib_cache_backend_TransientMemoryBackend();
		$backend->setCache($cache);

		$data = 'Some data';
		$identifier = 'MyIdentifier';
		$backend->set($identifier, $data);
		$otherData = 'some other data';
		$backend->set($identifier, $otherData);
		$fetchedData = $backend->get($identifier);
		$this->assertEquals($otherData, $fetchedData);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function findIdentifiersByTagFindsCacheEntriesWithSpecifiedTag() {
		$cache = $this->getMock('t3lib_cache_frontend_Frontend', array(), array(), '', FALSE);
		$backend = new t3lib_cache_backend_TransientMemoryBackend();
		$backend->setCache($cache);

		$data = 'Some data';
		$entryIdentifier = 'MyIdentifier';
		$backend->set($entryIdentifier, $data, array('UnitTestTag%tag1', 'UnitTestTag%tag2'));

		$retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag1');
		$this->assertEquals($entryIdentifier, $retrieved[0]);

		$retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag2');
		$this->assertEquals($entryIdentifier, $retrieved[0]);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function hasReturnsFalseIfTheEntryDoesntExist() {
		$cache = $this->getMock('t3lib_cache_frontend_Frontend', array(), array(), '', FALSE);
		$backend = new t3lib_cache_backend_TransientMemoryBackend();
		$backend->setCache($cache);

		$identifier = 'NonExistingIdentifier';
		$inCache = $backend->has($identifier);
		$this->assertFalse($inCache);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function removeReturnsFalseIfTheEntryDoesntExist() {
		$cache = $this->getMock('t3lib_cache_frontend_Frontend', array(), array(), '', FALSE);
		$backend = new t3lib_cache_backend_TransientMemoryBackend();
		$backend->setCache($cache);

		$identifier = 'NonExistingIdentifier';
		$inCache = $backend->remove($identifier);
		$this->assertFalse($inCache);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function flushByTagRemovesCacheEntriesWithSpecifiedTag() {
		$cache = $this->getMock('t3lib_cache_frontend_Frontend', array(), array(), '', FALSE);
		$backend = new t3lib_cache_backend_TransientMemoryBackend();
		$backend->setCache($cache);

		$data = 'some data' . microtime();
		$backend->set('TransientMemoryBackendTest1', $data, array('UnitTestTag%test', 'UnitTestTag%boring'));
		$backend->set('TransientMemoryBackendTest2', $data, array('UnitTestTag%test', 'UnitTestTag%special'));
		$backend->set('TransientMemoryBackendTest3', $data, array('UnitTestTag%test'));

		$backend->flushByTag('UnitTestTag%special');

		$this->assertTrue($backend->has('TransientMemoryBackendTest1'), 'TransientMemoryBackendTest1');
		$this->assertFalse($backend->has('TransientMemoryBackendTest2'), 'TransientMemoryBackendTest2');
		$this->assertTrue($backend->has('TransientMemoryBackendTest3'), 'TransientMemoryBackendTest3');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function flushRemovesAllCacheEntries() {
		$cache = $this->getMock('t3lib_cache_frontend_Frontend', array(), array(), '', FALSE);
		$backend = new t3lib_cache_backend_TransientMemoryBackend();
		$backend->setCache($cache);

		$data = 'some data' . microtime();
		$backend->set('TransientMemoryBackendTest1', $data);
		$backend->set('TransientMemoryBackendTest2', $data);
		$backend->set('TransientMemoryBackendTest3', $data);

		$backend->flush();

		$this->assertFalse($backend->has('TransientMemoryBackendTest1'), 'TransientMemoryBackendTest1');
		$this->assertFalse($backend->has('TransientMemoryBackendTest2'), 'TransientMemoryBackendTest2');
		$this->assertFalse($backend->has('TransientMemoryBackendTest3'), 'TransientMemoryBackendTest3');
	}
}

?>