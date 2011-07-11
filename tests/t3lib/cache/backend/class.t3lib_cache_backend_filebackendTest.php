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
	 * Enable backup of global and system variables
	 *
	 * @var boolean
	 */
	protected $backupGlobals = TRUE;

	/**
	 * Exclude TYPO3_DB from backup/ restore of $GLOBALS
	 * because resource types cannot be handled during serialization
	 *
	 * @var array
	 */
	protected $backupGlobalsBlacklist = array('TYPO3_DB');

	/**
	 * Sets up this testcase
	 *
	 * @return void
	 */
	public function setUp() {
		if (!class_exists('\vfsStreamWrapper')) {
			$this->markTestSkipped('File backend tests are not available with this phpunit version.');
		}

		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('Foo'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException \t3lib_cache_Exception
	 */
	public function setCacheDirectoryThrowsExceptionOnNonWritableDirectory() {
		$mockCache = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array(), array(), '', FALSE);

		$backend = $this->getMock('t3lib_cache_backend_FileBackend', array('dummy'), array(), '', FALSE);
		$backend->setCacheDirectory('http://localhost/');

		$backend->setCache($mockCache);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function getCacheDirectoryReturnsTheCurrentCacheDirectory() {
		$mockCache = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('SomeCache'));

		$backend = $this->getMock('t3lib_cache_backend_FileBackend', array('dummy'), array(), '', FALSE);
		$backend->setCacheDirectory('vfs://Foo/');
		$backend->setCache($mockCache);

		$this->assertEquals('vfs://Foo/Cache/Data/SomeCache/', $backend->getCacheDirectory());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aDedicatedCacheDirectoryIsUsedForCodeCaches() {
		$mockCache = $this->getMock('t3lib_cache_frontend_PhpFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('SomeCache'));

		$backend = $this->getMock('t3lib_cache_backend_FileBackend', array('dummy'), array(), '', FALSE);
		$backend->setCacheDirectory('vfs://Foo/');
		$backend->setCache($mockCache);

		$this->assertEquals('vfs://Foo/Cache/Code/SomeCache/', $backend->getCacheDirectory());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 * @expectedException t3lib_cache_exception_InvalidData
	 */
	public function setThrowsExceptionIfDataIsNotAString() {
		$mockCache = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array(), array(), '', FALSE);

		$backend = $this->getMock('t3lib_cache_backend_FileBackend', array('dummy'), array(), '', FALSE);
		$backend->setCacheDirectory('vfs://Foo/');
		$backend->setCache($mockCache);

		$backend->set('some identifier', array('not a string'));
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
		$pathAndFilename = 'vfs://Foo/Cache/Data/UnitTestCache/' . $entryIdentifier;

		$backend = $this->getMock('t3lib_cache_backend_FileBackend', array('dummy'), array(), '', FALSE);
		$backend->setCacheDirectory('vfs://Foo/');
		$backend->setCache($mockCache);

		$backend->set($entryIdentifier, $data);

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

		$backend = $this->getMock('t3lib_cache_backend_FileBackend', array('dummy'), array(), '', FALSE);
		$backend->setCacheDirectory('vfs://Foo/');
		$backend->setCache($mockCache);

		$backend->set($entryIdentifier, $data1, array(), 500);
		$backend->set($entryIdentifier, $data2, array(), 200);

		$pathAndFilename = 'vfs://Foo/Cache/Data/UnitTestCache/' . $entryIdentifier;
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
		$entryIdentifier = 'BackendFileRemoveBeforeSetTest';

		$backend = $this->getMock('t3lib_cache_backend_FileBackend', array('dummy'), array(), '', FALSE);
		$backend->setCacheDirectory('vfs://Foo/');
		$backend->setCache($mockCache);

		$backend->set($entryIdentifier, $data, array('Tag1', 'Tag2'));

		$pathAndFilename = 'vfs://Foo/Cache/Data/UnitTestCache/' . $entryIdentifier;
		$this->assertFileExists($pathAndFilename);
		$retrievedData = file_get_contents($pathAndFilename, NULL, NULL, (strlen($data) + t3lib_cache_backend_FileBackend::EXPIRYTIME_LENGTH), 9);
		$this->assertEquals('Tag1 Tag2', $retrievedData);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getReturnsContentOfTheCorrectCacheFile() {
		$mockCache = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$backend = $this->getMock('t3lib_cache_backend_FileBackend', array('setTag'), array(), '', FALSE);
		$backend->setCacheDirectory('vfs://Foo/');
		$backend->setCache($mockCache);

		$entryIdentifier = 'BackendFileTest';

		$data = 'some data' . microtime();
		$backend->set($entryIdentifier, $data, array(), 500);

		$data = 'some other data' . microtime();
		$backend->set($entryIdentifier, $data, array(), 100);

		$loadedData = $backend->get($entryIdentifier);
		$this->assertEquals($data, $loadedData);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getReturnsFalseForExpiredEntries() {
		$mockCache = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$backend = $this->getMock('t3lib_cache_backend_FileBackend', array('isCacheFileExpired'), array(), '', FALSE);
		$backend->expects($this->once())->method('isCacheFileExpired')->with('vfs://Foo/Cache/Data/UnitTestCache/ExpiredEntry')->will($this->returnValue(TRUE));
		$backend->setCacheDirectory('vfs://Foo/');
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

		$backend = $this->getMock('t3lib_cache_backend_FileBackend', array('dummy'), array(), '', FALSE);
		$backend->setCacheDirectory('vfs://Foo/');
		$backend->setCache($mockCache);

		$entryIdentifier = 'BackendFileTest';

		$data = 'some data' . microtime();
		$backend->set($entryIdentifier, $data);

		$this->assertTrue($backend->has($entryIdentifier), 'has() did not return TRUE.');
		$this->assertFalse($backend->has($entryIdentifier . 'Not'), 'has() did not return FALSE.');
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
		$pathAndFilename = 'vfs://Foo/Cache/Data/UnitTestCache/' . $entryIdentifier;

		$backend = $this->getMock('t3lib_cache_backend_FileBackend', array('dummy'), array(), '', FALSE);
		$backend->setCacheDirectory('vfs://Foo/');
		$backend->setCache($mockCache);

		$backend->set($entryIdentifier, $data);
		$this->assertFileExists($pathAndFilename);

		$backend->remove($entryIdentifier);
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

		$backend = $this->getMock('t3lib_cache_backend_FileBackend', array('dummy'), array('test'), '', TRUE);
		$backend->setCacheDirectory('vfs://Foo/');
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
		$backend->setCacheDirectory('vfs://Foo/');
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
		$backend->setCacheDirectory('vfs://Foo/');
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
		$backend->setCacheDirectory('vfs://Foo/');
		$backend->setCache($mockCache);

		$backend->requireOnce($identifier);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function findIdentifiersByTagFindsCacheEntriesWithSpecifiedTag() {
		$mockCache = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$backend = $this->getMock('t3lib_cache_backend_FileBackend', array('dummy'), array(), '', FALSE);
		$backend->setCacheDirectory('vfs://Foo/');
		$backend->setCache($mockCache);

		$data = 'some data' . microtime();
		$backend->set('BackendFileTest1', $data, array('UnitTestTag%test', 'UnitTestTag%boring'));
		$backend->set('BackendFileTest2', $data, array('UnitTestTag%test', 'UnitTestTag%special'));
		$backend->set('BackendFileTest3', $data, array('UnitTestTag%test'));

		$expectedEntry = 'BackendFileTest2';

		$actualEntries = $backend->findIdentifiersByTag('UnitTestTag%special');
		$this->assertInternalType('array', $actualEntries);
		$this->assertEquals($expectedEntry, array_pop($actualEntries));
	}

	/**
	 * @test
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function findIdentifiersByTagDoesNotReturnExpiredEntries() {
		$mockCache = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$backend = $this->getMock('t3lib_cache_backend_FileBackend', array('dummy'), array(), '', FALSE);
		$backend->setCacheDirectory('vfs://Foo/');
		$backend->setCache($mockCache);

		$data = 'some data';
		$backend->set('BackendFileTest1', $data, array('UnitTestTag%test', 'UnitTestTag%boring'));
		$backend->set('BackendFileTest2', $data, array('UnitTestTag%test', 'UnitTestTag%special'), -100);
		$backend->set('BackendFileTest3', $data, array('UnitTestTag%test'));

		$this->assertSame(array(), $backend->findIdentifiersByTag('UnitTestTag%special'));
		$this->assertSame(array('BackendFileTest1', 'BackendFileTest3'), $backend->findIdentifiersByTag('UnitTestTag%test'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function flushRemovesAllCacheEntries() {
		$mockCache = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$backend = $this->getMock('t3lib_cache_backend_FileBackend', array('dummy'), array(), '', FALSE);
		$backend->setCacheDirectory('vfs://Foo/');
		$backend->setCache($mockCache);

		$data = 'some data';
		$backend->set('BackendFileTest1', $data);
		$backend->set('BackendFileTest2', $data);

		$this->assertFileExists('vfs://Foo/Cache/Data/UnitTestCache/BackendFileTest1');
		$this->assertFileExists('vfs://Foo/Cache/Data/UnitTestCache/BackendFileTest2');

		$backend->flush();

		$this->assertFileNotExists('vfs://Foo/Cache/Data/UnitTestCache/BackendFileTest1');
		$this->assertFileNotExists('vfs://Foo/Cache/Data/UnitTestCache/BackendFileTest2');
	}

	/**
	 * @test
	 * @author Christian Kuhn <lolli@schwarzbu.ch>
	 */
	public function flushCreatesCacheDirectoryAgain() {
		$mockCache = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$backend = $this->getMock('t3lib_cache_backend_FileBackend', array('dummy'), array(), '', FALSE);
		$backend->setCacheDirectory('vfs://Foo/');
		$backend->setCache($mockCache);

		$backend->flush();
		$this->assertFileExists('vfs://Foo/Cache/Data/UnitTestCache/');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function flushByTagRemovesCacheEntriesWithSpecifiedTag() {
		$backend = $this->getMock('t3lib_cache_backend_FileBackend', array('findIdentifiersByTag', 'remove'), array(), '', FALSE);

		$backend->expects($this->once())->method('findIdentifiersByTag')->with('UnitTestTag%special')->will($this->returnValue(array('foo', 'bar', 'baz')));
		$backend->expects($this->at(1))->method('remove')->with('foo');
		$backend->expects($this->at(2))->method('remove')->with('bar');
		$backend->expects($this->at(3))->method('remove')->with('baz');

		$backend->flushByTag('UnitTestTag%special');
	}

	/**
	 * @test
	 * @author Christian Kuhn <lolli@schwarzbu.ch>
	 */
	public function collectGarbageRemovesExpiredCacheEntries() {
		$mockCache = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$backend = $this->getMock('t3lib_cache_backend_FileBackend', array('isCacheFileExpired'), array(), '', FALSE);
		$backend->expects($this->exactly(2))->method('isCacheFileExpired')->will($this->onConsecutiveCalls(TRUE, FALSE));
		$backend->setCacheDirectory('vfs://Foo/');
		$backend->setCache($mockCache);

		$data = 'some data';
		$backend->set('BackendFileTest1', $data);
		$backend->set('BackendFileTest2', $data);

		$this->assertFileExists('vfs://Foo/Cache/Data/UnitTestCache/BackendFileTest1');
		$this->assertFileExists('vfs://Foo/Cache/Data/UnitTestCache/BackendFileTest2');

		$backend->collectGarbage();
		$this->assertFileNotExists('vfs://Foo/Cache/Data/UnitTestCache/BackendFileTest1');
		$this->assertFileExists('vfs://Foo/Cache/Data/UnitTestCache/BackendFileTest2');
	}
}
?>