<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Andreas Wolf <andreas.wolf@ikt-werk.de>
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
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use \org\bovigo\vfs\vfsStream;

/**
 * Testcase for the storage collection class of the TYPO3 FAL
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 */
class FolderTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var array A backup of registered singleton instances
	 */
	protected $singletonInstances = array();

	protected $basedir = 'basedir';

	public function setUp() {
		$this->singletonInstances = \TYPO3\CMS\Core\Utility\GeneralUtility::getSingletonInstances();
		vfsStream::setup($this->basedir);
	}

	protected function tearDown() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::resetSingletonInstances($this->singletonInstances);
		parent::tearDown();
	}

	protected function createFolderFixture($path, $name, $mockedStorage = NULL) {
		if ($mockedStorage === NULL) {
			$mockedStorage = $this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', array(), array(), '', FALSE);
		}
		return new \TYPO3\CMS\Core\Resource\Folder($mockedStorage, $path, $name, 0);
	}

	/**
	 * @test
	 */
	public function constructorArgumentsAreAvailableAtRuntime() {
		$path = uniqid();
		$name = uniqid();
		$mockedStorage = $this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', array(), array(), '', FALSE);
		$fixture = $this->createFolderFixture($path, $name, $mockedStorage);
		$this->assertSame($mockedStorage, $fixture->getStorage());
		$this->assertStringStartsWith($path, $fixture->getIdentifier());
		$this->assertSame($name, $fixture->getName());
	}

	/**
	 * @test
	 */
	public function propertiesCanBeUpdated() {
		$fixture = $this->createFolderFixture('/somePath', 'someName');
		$fixture->updateProperties(array('identifier' => '/someOtherPath', 'name' => 'someNewName'));
		$this->assertSame('someNewName', $fixture->getName());
		$this->assertSame('/someOtherPath', $fixture->getIdentifier());
	}

	/**
	 * @test
	 */
	public function propertiesAreNotUpdatedIfNotSetInInput() {
		$fixture = $this->createFolderFixture('/somePath/someName/', 'someName');
		$fixture->updateProperties(array('identifier' => '/someOtherPath'));
		$this->assertSame('someName', $fixture->getName());
	}

	/**
	 * @test
	 */
	public function getFilesReturnsArrayWithFilenamesAsKeys() {
		$mockedStorage = $this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', array(), array(), '', FALSE);
		$mockedStorage->expects($this->once())->method('getFilesInFolder')->will($this->returnValue(array(
				'somefile.png' => array(
					'name' => 'somefile.png'
				),
				'somefile.jpg' => array(
					'name' => 'somefile.jpg'
				)
			)
		));
		$fixture = $this->createFolderFixture('/somePath', 'someName', $mockedStorage);

		$fileList = $fixture->getFiles();

		$this->assertSame(array('somefile.png', 'somefile.jpg'), array_keys($fileList));
	}

	/**
	 * @test
	 */
	public function getFilesHandsOverRecursiveFALSEifNotExplicitlySet() {
		$mockedStorage = $this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', array(), array(), '', FALSE);
		$mockedStorage
			->expects($this->once())
			->method('getFilesInFolder')
			->with($this->anything(), $this->anything(), $this->anything(), $this->anything(), FALSE)
			->will($this->returnValue(array()));

		$fixture = $this->createFolderFixture('/somePath', 'someName', $mockedStorage);
		$fixture->getFiles();
	}

	/**
	 * @test
	 */
	public function getFilesHandsOverRecursiveTRUEifSet() {
		$mockedStorage = $this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', array(), array(), '', FALSE);
		$mockedStorage
			->expects($this->once())
			->method('getFilesInFolder')
			->with($this->anything(), $this->anything(), $this->anything(), $this->anything(), TRUE)
			->will($this->returnValue(array()));

		$fixture = $this->createFolderFixture('/somePath', 'someName', $mockedStorage);
		$fixture->getFiles(0, 0, \TYPO3\CMS\Core\Resource\Folder::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS, TRUE);
	}

	/**
	 * @test
	 */
	public function getSubfolderCallsFactoryWithCorrectArguments() {
		$mockedStorage = $this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', array(), array(), '', FALSE);
		$mockedStorage->expects($this->once())->method('hasFolderInFolder')->with($this->equalTo('someSubfolder'))->will($this->returnValue(TRUE));
		$mockedFactory = $this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceFactory');
		$mockedFactory->expects($this->once())->method('createFolderObject')->with($mockedStorage, '/somePath/someFolder/someSubfolder/', 'someSubfolder');
		\TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance('TYPO3\\CMS\\Core\\Resource\\ResourceFactory', $mockedFactory);
		$fixture = $this->createFolderFixture('/somePath/someFolder/', 'someFolder', $mockedStorage);
		$fixture->getSubfolder('someSubfolder');
	}

	/**
	 * @test
	 */
	public function getParentFolderGetsParentFolderFromStorage() {
		$parentIdentifier = '/parent/';
		$currentIdentifier = '/parent/current/';

		$parentFolderFixture = $this->createFolderFixture($parentIdentifier, 'parent');
		$mockedStorage = $this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', array('getFolderIdentifierFromFileIdentifier', 'getFolder'), array(), '', FALSE);
		$mockedStorage->expects($this->once())->method('getFolderIdentifierFromFileIdentifier')->with($currentIdentifier)->will($this->returnValue($parentIdentifier));
		$mockedStorage->expects($this->once())->method('getFolder')->with($parentIdentifier)->will($this->returnValue($parentFolderFixture));

		$currentFolderFixture = $this->createFolderFixture($currentIdentifier, 'current', $mockedStorage);

		$this->assertSame($parentFolderFixture, $currentFolderFixture->getParentFolder());
	}
}
