<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource;

/***************************************************************
 * Copyright notice
 *
 * (c) 2011-2013 Andreas Wolf <andreas.wolf@ikt-werk.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

require_once 'vfsStream/vfsStream.php';

/**
 * Testcase for the file class of the TYPO3 FAL
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @todo Many, many, many tests are skipped in this test case...
 */
class FileTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var array A backup of registered singleton instances
	 */
	protected $singletonInstances = array();

	public function setUp() {
		$this->singletonInstances = \TYPO3\CMS\Core\Utility\GeneralUtility::getSingletonInstances();
		\TYPO3\CMS\Core\Utility\GeneralUtility::purgeInstances();
	}

	public function tearDown() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::resetSingletonInstances($this->singletonInstances);
	}

	/**
	 * @return \TYPO3\CMS\Core\Resource\File
	 */
	protected function prepareFixture() {
		$fixture = new \TYPO3\CMS\Core\Resource\File('testfile');
		return $fixture;
	}

	/**
	 * @test
	 * @todo Old code getAvailableProperties() needs to be replaced by current behaviour
	 */
	public function propertiesPassedToConstructorAreAvailableViaGenericGetter() {
		$this->markTestSkipped('TYPO3\\CMS\\Core\\Resource\\File::getAvailableProperties() does not exist');
		$properties = array(
			uniqid() => uniqid(),
			uniqid() => uniqid(),
			'uid' => 1
		);
		$fixture = new \TYPO3\CMS\Core\Resource\File($properties);
		$availablePropertiesBackup = \TYPO3\CMS\Core\Resource\File::getAvailableProperties();
		\TYPO3\CMS\Core\Resource\File::setAvailableProperties(array_keys($properties));
		foreach ($properties as $key => $value) {
			$this->assertTrue($fixture->hasProperty($key));
			$this->assertEquals($value, $fixture->getProperty($key));
		}
		$this->assertFalse($fixture->hasProperty(uniqid()));
		\TYPO3\CMS\Core\Resource\File::setAvailableProperties($availablePropertiesBackup);
		$this->setExpectedException('InvalidArgumentException', '', 1314226805);
		$fixture->getProperty(uniqid());
	}

	/**
	 * @test
	 */
	public function commonPropertiesAreAvailableWithOwnGetters() {
		$properties = array(
			'name' => uniqid(),
			'storage' => $this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', array(), array(), '', FALSE),
			'size' => 1024
		);
		$fixture = new \TYPO3\CMS\Core\Resource\File($properties);
		foreach ($properties as $key => $value) {
			$this->assertEquals($value, call_user_func(array($fixture, 'get' . $key)));
		}
	}

	/**
	 * @test
	 */
	public function fileAsksRepositoryForIndexStatus() {
		$mockedRepository = $this->getMock('TYPO3\\CMS\\Core\\Resource\\FileRepository');
		$mockedRepository->expects($this->once())->method('getFileIndexRecord')->will($this->returnValue(array()));
		\TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance('TYPO3\\CMS\\Core\\Resource\\FileRepository', $mockedRepository);
		$fixture = new \TYPO3\CMS\Core\Resource\File(array());
		$this->assertTrue($fixture->isIndexed());
	}

	/**
	 * Tests if a file is seen as indexed if the record has a uid
	 *
	 * @test
	 */
	public function fileIndexStatusIsTrueIfUidIsSet() {
		$fixture = new \TYPO3\CMS\Core\Resource\File(array('uid' => 1));
		$this->assertTrue($fixture->isIndexed());
	}

	/**
	 * @test
	 */
	public function updatePropertiesUpdatesFileProperties() {
		$identifier = '/' . uniqid();
		$fixture = new \TYPO3\CMS\Core\Resource\File(array('uid' => 1, 'identifier' => '/test'));
		$fixture->updateProperties(array('identifier' => $identifier));
		$this->assertEquals($identifier, $fixture->getIdentifier());
	}

	/**
	 * @test
	 */
	public function updatePropertiesLeavesPropertiesUntouchedIfNotSetInNewProperties() {
		$fixture = new \TYPO3\CMS\Core\Resource\File(array('uid' => 1, 'foo' => 'asdf', 'identifier' => '/test'));
		$fixture->updateProperties(array('foo' => 'foobar'));
		$this->assertEquals('/test', $fixture->getIdentifier());
		$this->assertEquals('/test', $fixture->getProperty('identifier'));
	}

	/**
	 * @test
	 */
	public function updatePropertiesDiscardsUidIfAlreadySet() {
		$fixture = new \TYPO3\CMS\Core\Resource\File(array('uid' => 1, 'identifier' => '/test'));
		$fixture->updateProperties(array('uid' => 3));
		$this->assertEquals(1, $fixture->getUid());
	}

	/**
	 * @test
	 */
	public function updatePropertiesRecordsNamesOfChangedProperties() {
		$fixture = new \TYPO3\CMS\Core\Resource\File(array('uid' => 1, 'foo' => 'asdf', 'baz' => 'fdsw', 'identifier' => '/test'));
		$fixture->updateProperties(array('foo' => 'foobar', 'baz' => 'foobaz'));
		$this->assertEquals(array('foo', 'baz'), $fixture->getUpdatedProperties());
	}

	/**
	 * @test
	 */
	public function updatePropertiesDoesNotRecordPropertyNameIfSameValueIsProvided() {
		$fixture = new \TYPO3\CMS\Core\Resource\File(array('uid' => 1, 'foo' => 'asdf', 'identifier' => '/test'));
		$fixture->updateProperties(array('foo' => 'asdf'));
		$this->assertEmpty($fixture->getUpdatedProperties());
	}

	/**
	 * @test
	 */
	public function updatePropertiesMarksPropertyAsChangedOnlyOnce() {
		$fixture = new \TYPO3\CMS\Core\Resource\File(array('uid' => 1, 'foo' => 'asdf', 'baz' => 'fdsw', 'identifier' => '/test'));
		$fixture->updateProperties(array('foo' => 'foobar', 'baz' => 'foobaz'));
		$fixture->updateProperties(array('foo' => 'fdsw', 'baz' => 'asdf'));
		$this->assertEquals(array('foo', 'baz'), $fixture->getUpdatedProperties());
	}

	/**
	 * @test
	 */
	public function updatePropertiesReloadsStorageObjectIfStorageChanges() {
		$mockedNewStorage = $this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', array(), array(), '', FALSE);
		$mockedOldStorage = $this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', array(), array(), '', FALSE);
		$fixture = new \TYPO3\CMS\Core\Resource\File(array('uid' => 1, 'foo' => 'asdf', 'baz' => 'fdsw', 'identifier' => '/test', 'storage' => $mockedOldStorage));
		$mockedRepository = $this->getMock('TYPO3\\CMS\\Core\\Resource\\StorageRepository');
		$mockedRepository->expects($this->once())->method('findByUid')->with(2)->will($this->returnValue($mockedNewStorage));
		\TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance('TYPO3\\CMS\\Core\\Resource\\StorageRepository', $mockedRepository);
		$fixture->updateProperties(array('storage' => 2));
		$this->assertSame($mockedNewStorage, $fixture->getStorage());
	}

	/**
	 * @test
	 */
	public function fetchingIndexedPropertyCausesFileObjectToLoadIndexRecord() {
		$fixture = new \TYPO3\CMS\Core\Resource\File(array('identifier' => '/test', 'storage' => 1));
		$mockedRepository = $this->getMock('TYPO3\\CMS\\Core\\Resource\\FileRepository');
		$mockedRepository->expects($this->once())->method('getFileIndexRecord')->will($this->returnValue(array('uid' => 10)));
		\TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance('TYPO3\\CMS\\Core\\Resource\\FileRepository', $mockedRepository);
		$this->assertEquals(10, $fixture->getProperty('uid'));
	}

	/**
	 * @test
	 */
	public function isIndexedTriggersIndexingIfFileIsNotIndexedAlready() {
		$fixture = new \TYPO3\CMS\Core\Resource\File(array('identifier' => '/test', 'storage' => 1));
		$mockedRepository = $this->getMock('TYPO3\\CMS\\Core\\Resource\\FileRepository');
		$mockedRepository->expects($this->once())->method('getFileIndexRecord')->will($this->returnValue(FALSE));
		$mockedRepository->expects($this->once())->method('addToIndex')->will($this->returnValue(array('uid' => 10)));
		\TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance('TYPO3\\CMS\\Core\\Resource\\FileRepository', $mockedRepository);
		$fixture->isIndexed();
	}

	/**
	 * @test
	 */
	public function fileIsAutomaticallyIndexedOnPropertyAccessIfNotAlreadyIndexed() {
		$fixture = new \TYPO3\CMS\Core\Resource\File(array('identifier' => '/test', 'storage' => 1));
		$mockedRepository = $this->getMock('TYPO3\\CMS\\Core\\Resource\\FileRepository');
		$mockedRepository->expects($this->once())->method('getFileIndexRecord')->will($this->returnValue(FALSE));
		$mockedRepository->expects($this->once())->method('addToIndex')->will($this->returnValue(array('uid' => 10)));
		\TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance('TYPO3\\CMS\\Core\\Resource\\FileRepository', $mockedRepository);
		$this->assertEquals(10, $fixture->getProperty('uid'));
	}

	/**
	 * @test
	 */
	public function copyToCallsCopyOperationOnTargetFolderStorage() {
		$targetStorage = $this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', array(), array(), '', FALSE);
		$targetFolder = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Folder', array(), array(), '', FALSE);
		$targetFolder->expects($this->any())->method('getStorage')->will($this->returnValue($targetStorage));
		$fixture = new \TYPO3\CMS\Core\Resource\File(array());
		$targetStorage->expects($this->once())->method('copyFile')->with($this->equalTo($fixture), $this->equalTo($targetFolder));
		$fixture->copyTo($targetFolder);
	}

	/**
	 * @test
	 */
	public function moveToCallsMoveOperationOnTargetFolderStorage() {
		$targetStorage = $this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', array(), array(), '', FALSE);
		$targetFolder = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Folder', array(), array(), '', FALSE);
		$targetFolder->expects($this->any())->method('getStorage')->will($this->returnValue($targetStorage));
		$fixture = new \TYPO3\CMS\Core\Resource\File(array());
		$targetStorage->expects($this->once())->method('moveFile')->with($this->equalTo($fixture), $this->equalTo($targetFolder));
		$fixture->moveTo($targetFolder);
	}

	/**
	 * @test
	 */
	public function openCorrectlyOpensFileInDriver() {
		$this->markTestSkipped();
		$fixture = $this->prepareFixture();
		$fileMode = 'invalidMode';
		$mockDriver = $this->getMockForAbstractClass('t3lib_file_driver_Abstract');
		$mockDriver->expects($this->atLeastOnce())->method('getFileHandle')->with($this->equalTo($fixture), $this->equalTo($fileMode));
		$fixture->setStorageDriver($mockDriver);
		$fixture->open($fileMode);
	}

	/**
	 * @test
	 */
	public function isOpenReturnsCorrectValuesForClosedAndOpenFile() {
		$this->markTestSkipped();
		$fixture = $this->prepareFixture();
		$fileMode = 'r';
		$mockFileHandle = $this->getMock('TYPO3\\CMS\\Core\\Resource\\FileHandle', array(), array(), '', FALSE);
		$mockFileHandle->expects($this->any())->method('isOpen')->will($this->returnValue(TRUE));
		$mockDriver = $this->getMockForAbstractClass('t3lib_file_driver_Abstract');
		$mockDriver->expects($this->any())->method('getFileHandle')->will($this->returnValue($mockFileHandle));
		$fixture->setStorageDriver($mockDriver);
		$this->assertFalse($fixture->isOpen());
		$fixture->open($fileMode);
		$this->assertTrue($fixture->isOpen());
	}

	/**
	 * @test
	 */
	public function fileIsCorrectlyClosed() {
		$this->markTestSkipped();
		$fixture = $this->prepareFixture();
		$fileMode = 'r';
		$mockFileHandle = $this->getMock('TYPO3\\CMS\\Core\\Resource\\FileHandle', array(), array(), '', FALSE);
		$mockFileHandle->expects($this->once())->method('close');
		$mockDriver = $this->getMockForAbstractClass('t3lib_file_driver_Abstract');
		$mockDriver->expects($this->any())->method('getFileHandle')->will($this->returnValue($mockFileHandle));
		$fixture->setStorageDriver($mockDriver);
		$fixture->open($fileMode);
		$fixture->close();
		$this->assertFalse($fixture->isOpen());
	}

	/**
	 * @test
	 */
	public function readReturnsRequestedContentsFromDriver() {
		$this->markTestSkipped();
		$fixture = $this->prepareFixture();
		$fileMode = 'r';
		$fileContents = 'Some random file contents.';
		$bytesToRead = 10;
		$mockFileHandle = $this->getMock('TYPO3\\CMS\\Core\\Resource\\FileHandle', array(), array(), '', FALSE);
		$mockFileHandle->expects($this->any())->method('isOpen')->will($this->returnValue(TRUE));
		$mockDriver = $this->getMockForAbstractClass('t3lib_file_driver_Abstract');
		$mockDriver->expects($this->any())->method('getFileHandle')->will($this->returnValue($mockFileHandle));
		$mockDriver->expects($this->once())->method('readFromFile')->with($this->anything(), $this->equalTo($bytesToRead))->will($this->returnValue(substr($fileContents, 0, $bytesToRead)));
		$fixture->setStorageDriver($mockDriver);
		$fixture->open($fileMode);
		$this->assertEquals(substr($fileContents, 0, $bytesToRead), $fixture->read($bytesToRead));
	}

	/**
	 * @test
	 */
	public function readFailsIfFileIsClosed() {
		$this->markTestSkipped();
		$this->setExpectedException('\RuntimeException', '', 1299863431);
		$fixture = $this->prepareFixture();
		$mockFileHandle = $this->getMock('TYPO3\\CMS\\Core\\Resource\\FileHandle', array(), array(), '', FALSE);
		$mockDriver = $this->getMockForAbstractClass('t3lib_file_driver_Abstract');
		$mockDriver->expects($this->any())->method('getFileHandle')->will($this->returnValue($mockFileHandle));
		$fixture->setStorageDriver($mockDriver);
		$fixture->read(1);
	}

	/**
	 * @test
	 */
	public function writePassesContentsToDriver() {
		$this->markTestSkipped();
		$fixture = $this->prepareFixture();
		$fileMode = 'r+';
		$fileContents = 'Some random file contents.';
		$mockFileHandle = $this->getMock('TYPO3\\CMS\\Core\\Resource\\FileHandle', array(), array(), '', FALSE);
		$mockFileHandle->expects($this->any())->method('isOpen')->will($this->returnValue(TRUE));
		$mockDriver = $this->getMockForAbstractClass('t3lib_file_driver_Abstract');
		$mockDriver->expects($this->any())->method('getFileHandle')->will($this->returnValue($mockFileHandle));
		$mockDriver->expects($this->once())->method('writeToFile')->with($this->anything(), $this->equalTo($fileContents))->will($this->returnValue(TRUE));
		$fixture->setStorageDriver($mockDriver);
		$fixture->open($fileMode);
		$this->assertTrue($fixture->write($fileContents));
	}

	/**
	 * @test
	 */
	public function writeFailsIfFileIsClosed() {
		$this->markTestSkipped();
		$this->setExpectedException('\RuntimeException', '', 1299863432);
		$fixture = $this->prepareFixture();
		$mockFileHandle = $this->getMock('TYPO3\\CMS\\Core\\Resource\\FileHandle', array(), array(), '', FALSE);
		$mockDriver = $this->getMockForAbstractClass('t3lib_file_driver_Abstract');
		$mockDriver->expects($this->any())->method('getFileHandle')->will($this->returnValue($mockFileHandle));
		$fixture->setStorageDriver($mockDriver);
		$fixture->write('asdf');
	}

	public function filenameExtensionDataProvider() {
		return array(
			array('somefile.jpg', 'somefile', 'jpg'),
			array('SomeFile.PNG', 'SomeFile', 'png'),
			array('somefile', 'somefile', ''),
			array('somefile.tar.gz', 'somefile.tar', 'gz'),
			array('somefile.tar.bz2', 'somefile.tar', 'bz2'),
		);
	}

	/**
	 * @test
	 * @dataProvider filenameExtensionDataProvider
	 */
	public function getNameWithoutExtensionReturnsCorrectName($originalFilename, $expectedBasename) {
		$fixture = new \TYPO3\CMS\Core\Resource\File(array(
			'name' => $originalFilename,
			'identifier' => '/' . $originalFilename
		));
		$this->assertSame($expectedBasename, $fixture->getNameWithoutExtension());
	}

	/**
	 * @test
	 * @dataProvider filenameExtensionDataProvider
	 */
	public function getExtensionReturnsCorrectExtension($originalFilename, $expectedBasename, $expectedExtension) {
		$fixture = new \TYPO3\CMS\Core\Resource\File(array(
			'name' => $originalFilename,
			'identifier' => '/' . $originalFilename
		));
		$this->assertSame($expectedExtension, $fixture->getExtension());
	}

	/**
	 * @test
	 */
	public function indexablePropertyIsByDefaultTrue() {
		$fixture = new \TYPO3\CMS\Core\Resource\File(array());
		$this->assertAttributeEquals(TRUE, 'indexable', $fixture);
	}

	/**
	 * @test
	 */
	public function indexablePropertyCanBeSetAndGet() {
		$fixture = new \TYPO3\CMS\Core\Resource\File(array());
		foreach (array(FALSE, TRUE) as $value) {
			$fixture->setIndexable($value);
			$this->assertSame($value, $fixture->isIndexable());
		}
	}

	/**
	 * @test
	 */
	public function callMethodLoadIndexRecordWithPropertyIndexableSetToFalseAndCheckWhetherIsIndexedReturnsNull() {
		$method = new \ReflectionMethod(
			'TYPO3\CMS\Core\Resource\File', 'loadIndexRecord'
		);
		$method->setAccessible(TRUE);

		$fixture = new \TYPO3\CMS\Core\Resource\File(array());
		$fixture->setIndexable(FALSE);
		$method->invoke($fixture);
		$this->assertNull($fixture->isIndexed());
	}
}

?>