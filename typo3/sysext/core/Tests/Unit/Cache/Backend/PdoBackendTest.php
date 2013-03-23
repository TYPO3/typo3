<?php
namespace TYPO3\CMS\Core\Tests\Unit\Cache\Backend;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 * Testcase for the PDO cache backend
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class PdoBackendTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * Sets up this testcase
	 */
	public function setUp() {
		if (!extension_loaded('pdo_sqlite')) {
			$this->markTestSkipped('pdo_sqlite extension was not available');
		}
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Core\Cache\Exception
	 */
	public function setThrowsExceptionIfNoFrontEndHasBeenSet() {
		$backend = new \TYPO3\CMS\Core\Cache\Backend\PdoBackend('Testing');
		$data = 'Some data';
		$identifier = 'MyIdentifier';
		$backend->set($identifier, $data);
	}

	/**
	 * @test
	 */
	public function itIsPossibleToSetAndCheckExistenceInCache() {
		$backend = $this->setUpBackend();
		$data = 'Some data';
		$identifier = 'MyIdentifier';
		$backend->set($identifier, $data);
		$this->assertTrue($backend->has($identifier));
	}

	/**
	 * @test
	 */
	public function itIsPossibleToSetAndGetEntry() {
		$backend = $this->setUpBackend();
		$data = 'Some data';
		$identifier = 'MyIdentifier';
		$backend->set($identifier, $data);
		$fetchedData = $backend->get($identifier);
		$this->assertEquals($data, $fetchedData);
	}

	/**
	 * @test
	 */
	public function itIsPossibleToRemoveEntryFromCache() {
		$backend = $this->setUpBackend();
		$data = 'Some data';
		$identifier = 'MyIdentifier';
		$backend->set($identifier, $data);
		$backend->remove($identifier);
		$this->assertFalse($backend->has($identifier));
	}

	/**
	 * @test
	 */
	public function itIsPossibleToOverwriteAnEntryInTheCache() {
		$backend = $this->setUpBackend();
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
	 */
	public function findIdentifiersByTagFindsSetEntries() {
		$backend = $this->setUpBackend();
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
	 */
	public function setRemovesTagsFromPreviousSet() {
		$backend = $this->setUpBackend();
		$data = 'Some data';
		$entryIdentifier = 'MyIdentifier';
		$backend->set($entryIdentifier, $data, array('UnitTestTag%tag1', 'UnitTestTag%tag2'));
		$backend->set($entryIdentifier, $data, array('UnitTestTag%tag3'));
		$retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag2');
		$this->assertEquals(array(), $retrieved);
	}

	/**
	 * @test
	 */
	public function setOverwritesExistingEntryThatExceededItsLifetimeWithNewData() {
		$backend = $this->setUpBackend();
		$data1 = 'data1';
		$entryIdentifier = uniqid('test');
		$backend->set($entryIdentifier, $data1, array(), 1);
		$data2 = 'data2';
		$GLOBALS['EXEC_TIME'] += 2;
		$backend->set($entryIdentifier, $data2, array(), 10);
		$this->assertEquals($data2, $backend->get($entryIdentifier));
	}

	/**
	 * @test
	 */
	public function hasReturnsFalseIfTheEntryDoesntExist() {
		$backend = $this->setUpBackend();
		$identifier = 'NonExistingIdentifier';
		$this->assertFalse($backend->has($identifier));
	}

	/**
	 * @test
	 */
	public function removeReturnsFalseIfTheEntryDoesntExist() {
		$backend = $this->setUpBackend();
		$identifier = 'NonExistingIdentifier';
		$this->assertFalse($backend->remove($identifier));
	}

	/**
	 * @test
	 */
	public function flushByTagRemovesCacheEntriesWithSpecifiedTag() {
		$backend = $this->setUpBackend();
		$data = 'some data' . microtime();
		$backend->set('PdoBackendTest1', $data, array('UnitTestTag%test', 'UnitTestTag%boring'));
		$backend->set('PdoBackendTest2', $data, array('UnitTestTag%test', 'UnitTestTag%special'));
		$backend->set('PdoBackendTest3', $data, array('UnitTestTag%test'));
		$backend->flushByTag('UnitTestTag%special');
		$this->assertTrue($backend->has('PdoBackendTest1'), 'PdoBackendTest1');
		$this->assertFalse($backend->has('PdoBackendTest2'), 'PdoBackendTest2');
		$this->assertTrue($backend->has('PdoBackendTest3'), 'PdoBackendTest3');
	}

	/**
	 * @test
	 */
	public function flushRemovesAllCacheEntries() {
		$backend = $this->setUpBackend();
		$data = 'some data' . microtime();
		$backend->set('PdoBackendTest1', $data);
		$backend->set('PdoBackendTest2', $data);
		$backend->set('PdoBackendTest3', $data);
		$backend->flush();
		$this->assertFalse($backend->has('PdoBackendTest1'), 'PdoBackendTest1');
		$this->assertFalse($backend->has('PdoBackendTest2'), 'PdoBackendTest2');
		$this->assertFalse($backend->has('PdoBackendTest3'), 'PdoBackendTest3');
	}

	/**
	 * @test
	 */
	public function flushRemovesOnlyOwnEntries() {
		$thisCache = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\FrontendInterface', array(), array(), '', FALSE);
		$thisCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('thisCache'));
		$thisBackend = $this->setUpBackend();
		$thisBackend->setCache($thisCache);
		$thatCache = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\FrontendInterface', array(), array(), '', FALSE);
		$thatCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('thatCache'));
		$thatBackend = $this->setUpBackend();
		$thatBackend->setCache($thatCache);
		$thisBackend->set('thisEntry', 'Hello');
		$thatBackend->set('thatEntry', 'World!');
		$thatBackend->flush();
		$this->assertEquals('Hello', $thisBackend->get('thisEntry'));
		$this->assertFalse($thatBackend->has('thatEntry'));
	}

	/**
	 * @test
	 */
	public function collectGarbageReallyRemovesAnExpiredCacheEntry() {
		$backend = $this->setUpBackend();
		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendPDORemovalTest';
		$backend->set($entryIdentifier, $data, array(), 1);
		$this->assertTrue($backend->has($entryIdentifier));
		$GLOBALS['EXEC_TIME'] += 2;
		$backend->collectGarbage();
		$this->assertFalse($backend->has($entryIdentifier));
	}

	/**
	 * @test
	 */
	public function collectGarbageReallyRemovesAllExpiredCacheEntries() {
		$backend = $this->setUpBackend();
		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendPDORemovalTest';
		$backend->set($entryIdentifier . 'A', $data, array(), NULL);
		$backend->set($entryIdentifier . 'B', $data, array(), 10);
		$backend->set($entryIdentifier . 'C', $data, array(), 1);
		$backend->set($entryIdentifier . 'D', $data, array(), 1);
		$this->assertTrue($backend->has($entryIdentifier . 'A'));
		$this->assertTrue($backend->has($entryIdentifier . 'B'));
		$this->assertTrue($backend->has($entryIdentifier . 'C'));
		$this->assertTrue($backend->has($entryIdentifier . 'D'));
		$GLOBALS['EXEC_TIME'] += 2;
		$backend->collectGarbage();
		$this->assertTrue($backend->has($entryIdentifier . 'A'));
		$this->assertTrue($backend->has($entryIdentifier . 'B'));
		$this->assertFalse($backend->has($entryIdentifier . 'C'));
		$this->assertFalse($backend->has($entryIdentifier . 'D'));
	}

	/**
	 * Sets up the PDO backend used for testing
	 *
	 * @return \TYPO3\CMS\Core\Cache\Backend\PdoBackend
	 */
	protected function setUpBackend() {
		$mockCache = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\FrontendInterface', array(), array(), '', FALSE);
		$mockCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('TestCache'));
		$backend = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Cache\\Backend\\PdoBackend', 'Testing');
		$backend->setCache($mockCache);
		$backend->setDataSourceName('sqlite::memory:');
		$backend->initializeObject();
		return $backend;
	}

}

?>