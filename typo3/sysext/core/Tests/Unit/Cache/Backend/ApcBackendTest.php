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
 * Testcase for the APC cache backend.
 *
 * NOTE: If you want to execute these tests you need to enable apc in
 * cli context (apc.enable_cli = 1)
 *
 * This file is a backport from FLOW3
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class ApcBackendTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * Sets up this testcase
	 *
	 * @return void
	 */
	public function setUp() {
		// Currently APCu identifies itself both as "apcu" and "apc" (for compatibility) although it doesn't provide the APC-opcache functionality
		if (!extension_loaded('apc')) {
			$this->markTestSkipped('APC/APCu extension was not available');
		}
		if (ini_get('apc.slam_defense') == 1) {
			$this->markTestSkipped('This testcase can only be executed with apc.slam_defense = Off');
		}
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Core\Cache\Exception
	 */
	public function setThrowsExceptionIfNoFrontEndHasBeenSet() {
		$backend = new \TYPO3\CMS\Core\Cache\Backend\ApcBackend('Testing');
		$data = 'Some data';
		$identifier = 'MyIdentifier' . md5(uniqid(mt_rand(), TRUE));
		$backend->set($identifier, $data);
	}

	/**
	 * @test
	 */
	public function itIsPossibleToSetAndCheckExistenceInCache() {
		// APC has some slam protection that tries to prevent hammering of cache
		// entries. This can be disabled, but the option does not work at least
		// in native PHP 5.3.3 on debian squeeze. While it is no problem with
		// higher PHP version like the current one on travis-ci.org,
		// the test is now just skipped on PHP environments that are knows for issues.
		if (version_compare(phpversion(), '5.3.4', '<')) {
			$this->markTestSkipped('This test is not reliable with PHP version below 5.3.3');
		}
		$backend = $this->setUpBackend();
		$data = 'Some data';
		$identifier = 'MyIdentifier' . md5(uniqid(mt_rand(), TRUE));
		$backend->set($identifier, $data);
		$inCache = $backend->has($identifier);
		$this->assertTrue($inCache, 'APC backend failed to set and check entry');
	}

	/**
	 * @test
	 */
	public function itIsPossibleToSetAndGetEntry() {
		// APC has some slam protection that tries to prevent hammering of cache
		// entries. This can be disabled, but the option does not work at least
		// in native PHP 5.3.3 on debian squeeze. While it is no problem with
		// higher PHP version like the current one on travis-ci.org,
		// the test is now just skipped on PHP environments that are knows for issues.
		if (version_compare(phpversion(), '5.3.4', '<')) {
			$this->markTestSkipped('This test is not reliable with PHP version below 5.3.3');
		}
		$backend = $this->setUpBackend();
		$data = 'Some data';
		$identifier = 'MyIdentifier' . md5(uniqid(mt_rand(), TRUE));
		$backend->set($identifier, $data);
		$fetchedData = $backend->get($identifier);
		$this->assertEquals($data, $fetchedData, 'APC backend failed to set and retrieve data');
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
		$this->assertFalse($inCache, 'Failed to set and remove data from APC backend');
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
		$this->assertEquals($otherData, $fetchedData, 'APC backend failed to overwrite and retrieve data');
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
	public function setCacheIsSettingIdentifierPrefixWithCacheIdentifier() {
		$cacheMock = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\FrontendInterface', array(), array(), '', FALSE);
		$cacheMock->expects($this->any())->method('getIdentifier')->will($this->returnValue(
			'testidentifier'
		));

		/** @var $backendMock \TYPO3\CMS\Core\Cache\Backend\ApcBackend */
		$backendMock = $this->getMock(
			'TYPO3\\CMS\\Core\\Cache\\Backend\\ApcBackend',
			array('setIdentifierPrefix','getCurrentUserData','getPathSite'),
			array('testcontext')
		);

		$backendMock->expects($this->once())->method('getCurrentUserData')->will(
			$this->returnValue(array('name' => 'testname'))
		);

		$backendMock->expects($this->once())->method('getPathSite')->will(
			$this->returnValue('testpath')
		);

		$expectedIdentifier = 'TYPO3_' . \TYPO3\CMS\Core\Utility\GeneralUtility::shortMD5('testpath' . 'testname' . 'testcontext' . 'testidentifier', 12);
		$backendMock->expects($this->once())->method('setIdentifierPrefix')->with($expectedIdentifier);
		$backendMock->setCache($cacheMock);
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
	 */
	public function flushRemovesAllCacheEntries() {
		// APC has some slam protection that tries to prevent hammering of cache
		// entries. This can be disabled, but the option does not work at least
		// in native PHP 5.3.3 on debian squeeze. While it is no problem with
		// higher PHP version like the current one on travis-ci.org,
		// the test is now just skipped on PHP environments that are knows for issues.
		if (version_compare(phpversion(), '5.3.4', '<')) {
			$this->markTestSkipped('This test is not reliable with PHP version below 5.3.3');
		}
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
	 */
	public function flushRemovesOnlyOwnEntries() {
		$thisCache = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\FrontendInterface', array(), array(), '', FALSE);
		$thisCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('thisCache'));
		$thisBackend = new \TYPO3\CMS\Core\Cache\Backend\ApcBackend('Testing');
		$thisBackend->setCache($thisCache);
		$thatCache = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\FrontendInterface', array(), array(), '', FALSE);
		$thatCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('thatCache'));
		$thatBackend = new \TYPO3\CMS\Core\Cache\Backend\ApcBackend('Testing');
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
	 * Sets up the APC backend used for testing
	 *
	 * @return \TYPO3\CMS\Core\Cache\Backend\ApcBackend
	 */
	protected function setUpBackend() {
		$cache = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\FrontendInterface', array(), array(), '', FALSE);
		$backend = new \TYPO3\CMS\Core\Cache\Backend\ApcBackend('Testing');
		$backend->setCache($cache);
		return $backend;
	}

}

?>