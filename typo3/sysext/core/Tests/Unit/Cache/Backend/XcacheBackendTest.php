<?php
namespace TYPO3\CMS\Core\Tests\Unit\Cache\Backend;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Philipp Gampe <philipp.gampe@typo3.org>
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
 * @author Philipp Gampe <philipp.gampe@typo3.org>
 */
class XcacheBackendTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * Sets up this testcase
	 *
	 * @return void
	 */
	public function setUp() {
		if (!extension_loaded('xcache')) {
			$this->markTestSkipped('xcache extension was not available');
		}
		if (php_sapi_name() === 'cli') {
			$this->markTestSkipped('XCache is not supported in CLI mode.');
		}
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Core\Cache\Exception
	 */
	public function setThrowsExceptionIfNoFrontEndHasBeenSet() {
		$backend = new \TYPO3\CMS\Core\Cache\Backend\XcacheBackend('Testing');
		$data = 'Some data';
		$identifier = 'MyIdentifier' . md5(uniqid(mt_rand(), TRUE));
		$backend->set($identifier, $data);
	}

	/**
	 * @test
	 */
	public function itIsPossibleToSetAndCheckExistenceInCache() {
		$backend = $this->setUpBackend();
		$data = 'Some data';
		$identifier = 'MyIdentifier' . md5(uniqid(mt_rand(), TRUE));
		$backend->set($identifier, $data);
		$inCache = $backend->has($identifier);
		$this->assertTrue($inCache, 'xcache backend failed to set and check entry');
	}

	/**
	 * @test
	 */
	public function itIsPossibleToSetAndGetEntry() {
		$backend = $this->setUpBackend();
		$data = 'Some data';
		$identifier = 'MyIdentifier' . md5(uniqid(mt_rand(), TRUE));
		$backend->set($identifier, $data);
		$fetchedData = $backend->get($identifier);
		$this->assertEquals($data, $fetchedData, 'xcache backend failed to set and retrieve data');
	}

	/**
	 * @test
	 */
	public function itIsPossibleToRemoveEntryFromCache() {
		$backend = $this->setUpBackend();
		$data = 'Some data';
		$identifier = 'MyIdentifier' . md5(uniqid(mt_rand(), TRUE));
		$backend->set($identifier, $data);
		$backend->remove($identifier);
		$inCache = $backend->has($identifier);
		$this->assertFalse($inCache, 'Failed to set and remove data from xcache backend');
	}

	/**
	 * @test
	 */
	public function itIsPossibleToOverwriteAnEntryInTheCache() {
		$backend = $this->setUpBackend();
		$data = 'Some data';
		$identifier = 'MyIdentifier' . md5(uniqid(mt_rand(), TRUE));
		$backend->set($identifier, $data);
		$otherData = 'some other data';
		$backend->set($identifier, $otherData);
		$fetchedData = $backend->get($identifier);
		$this->assertEquals($otherData, $fetchedData, 'xcache backend failed to overwrite and retrieve data');
	}

	/**
	 * @test
	 */
	public function findIdentifiersByTagFindsSetEntries() {
		$backend = $this->setUpBackend();
		$data = 'Some data';
		$identifier = 'MyIdentifier' . md5(uniqid(mt_rand(), TRUE));
		$backend->set($identifier, $data, array('UnitTestTag%tag1', 'UnitTestTag%tag2'));
		$retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag1');
		$this->assertEquals($identifier, $retrieved[0], 'Could not retrieve expected entry by tag.');
		$retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag2');
		$this->assertEquals($identifier, $retrieved[0], 'Could not retrieve expected entry by tag.');
	}

	/**
	 * @test
	 */
	public function setRemovesTagsFromPreviousSet() {
		$backend = $this->setUpBackend();
		$data = 'Some data';
		$identifier = 'MyIdentifier' . md5(uniqid(mt_rand(), TRUE));
		$backend->set($identifier, $data, array('UnitTestTag%tag1', 'UnitTestTag%tagX'));
		$backend->set($identifier, $data, array('UnitTestTag%tag3'));
		$retrieved = $backend->findIdentifiersByTag('UnitTestTag%tagX');
		$this->assertEquals(array(), $retrieved, 'Found entry which should no longer exist.');
	}

	/**
	 * @test
	 */
	public function hasReturnsFalseIfTheEntryDoesntExist() {
		$backend = $this->setUpBackend();
		$identifier = 'NonExistingIdentifier' . md5(uniqid(mt_rand(), TRUE));
		$inCache = $backend->has($identifier);
		$this->assertFalse($inCache, '"has" did not return FALSE when checking on non existing identifier');
	}

	/**
	 * @test
	 */
	public function removeReturnsFalseIfTheEntryDoesntExist() {
		$backend = $this->setUpBackend();
		$identifier = 'NonExistingIdentifier' . md5(uniqid(mt_rand(), TRUE));
		$inCache = $backend->remove($identifier);
		$this->assertFalse($inCache, '"remove" did not return FALSE when checking on non existing identifier');
	}

	/**
	 * @test
	 */
	public function flushByTagRemovesCacheEntriesWithSpecifiedTag() {
		$backend = $this->setUpBackend();
		$data = 'some data' . microtime();
		$backend->set('BackendXcacheTest1', $data, array('UnitTestTag%test', 'UnitTestTag%boring'));
		$backend->set('BackendXcacheTest2', $data, array('UnitTestTag%test', 'UnitTestTag%special'));
		$backend->set('BackendXcacheTest3', $data, array('UnitTestTag%test'));
		$backend->flushByTag('UnitTestTag%special');
		$this->assertTrue($backend->has('BackendXcacheTest1'), 'BackendXcacheTest1');
		$this->assertFalse($backend->has('BackendXcacheTest2'), 'BackendXcacheTest2');
		$this->assertTrue($backend->has('BackendXcacheTest3'), 'BackendXcacheTest3');
	}

	/**
	 * @test
	 */
	public function flushRemovesAllCacheEntries() {
		$backend = $this->setUpBackend();
		$data = 'some data' . microtime();
		$backend->set('BackendXcacheTest1', $data);
		$backend->set('BackendXcacheTest2', $data);
		$backend->set('BackendXcacheTest3', $data);
		$backend->flush();
		$this->assertFalse($backend->has('BackendXcacheTest1'), 'BackendXcacheTest1');
		$this->assertFalse($backend->has('BackendXcacheTest2'), 'BackendXcacheTest2');
		$this->assertFalse($backend->has('BackendXcacheTest3'), 'BackendXcacheTest3');
	}

	/**
	 * @test
	 */
	public function flushRemovesOnlyOwnEntries() {
		$thisCache = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\FrontendInterface', array(), array(), '', FALSE);
		$thisCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('thisCache'));
		$thisBackend = new \TYPO3\CMS\Core\Cache\Backend\XcacheBackend('Testing');
		$thisBackend->setCache($thisCache);
		$thatCache = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\FrontendInterface', array(), array(), '', FALSE);
		$thatCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('thatCache'));
		$thatBackend = new \TYPO3\CMS\Core\Cache\Backend\XcacheBackend('Testing');
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
	 */
	public function largeDataIsStored() {
		$backend = $this->setUpBackend();
		$data = str_repeat('abcde', 1024 * 1024);
		$identifier = 'tooLargeData' . md5(uniqid(mt_rand(), TRUE));
		$backend->set($identifier, $data);
		$this->assertTrue($backend->has($identifier));
		$this->assertEquals($backend->get($identifier), $data);
	}

	/**
	 * Sets up the xcache backend used for testing
	 *
	 * @return \TYPO3\CMS\Core\Cache\Backend\XcacheBackend
	 */
	protected function setUpBackend() {
		$cache = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\FrontendInterface', array(), array(), '', FALSE);
		$backend = new \TYPO3\CMS\Core\Cache\Backend\XcacheBackend('Testing');
		$backend->setCache($cache);
		return $backend;
	}

}

?>