<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2012 Oliver Hader <oliver.hader@typo3.org>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

require_once t3lib_extMgm::extPath('extbase') . 'Tests/Functional/Domain/Model/Fixture/FileContext.php';

/**
 * Test case to check functionality type converters on FAL domain objects.
 */
class Tx_Extbase_Tests_Functional_Domain_Model_FileContextTest extends Tx_Extbase_Tests_Functional_BaseTestCase {
	/**
	 * @var Tx_Extbase_Property_PropertyMapper
	 */
	protected $propertyMapper;

	/**
	 * @var Tx_Extbase_MVC_Controller_MvcPropertyMappingConfiguration
	 */
	protected $propertyMapperConfiguration;

	/**
	 * Sets up this test suite.
	 */
	protected function setUp() {
		parent::setUp();

		$this->importDataSet(dirname(__FILE__) . '/Fixture/data_sys_file_storage.xml');
		$this->importDataSet(dirname(__FILE__) . '/Fixture/data_sys_file.xml');
		$this->importDataSet(dirname(__FILE__) . '/Fixture/data_sys_file_reference.xml');
		$this->importDataSet(dirname(__FILE__) . '/Fixture/data_sys_file_collection.xml');

		/** @var $configurationBuilder Tx_Extbase_Property_PropertyMappingConfigurationBuilder */
		$configurationBuilder = $this->objectManager->get('Tx_Extbase_Property_PropertyMappingConfigurationBuilder');

		$this->propertyMapperConfiguration = $configurationBuilder->build(
			'Tx_Extbase_MVC_Controller_MvcPropertyMappingConfiguration'
		);

		$this->propertyMapper = $this->objectManager->get('Tx_Extbase_Property_PropertyMapper');
	}

	/**
	 * Cleans up this test suite.
	 */
	protected function tearDown() {
		parent::tearDown();

		unset($this->propertyMapperConfiguration);
		unset($this->propertyMapper);
	}

	/**
	 * @test
	 */
	public function fileObjectIsAvailable() {
		$data = array(
			'file' => 1,
		);

		/** @var $fixture Tx_Extbase_Tests_Functional_Domain_Model_Fixture_FileContext */
		$fixture = $this->propertyMapper->convert(
			$data,
			'Tx_Extbase_Tests_Functional_Domain_Model_Fixture_FileContext',
			$this->propertyMapperConfiguration
		);

		$this->assertInstanceOf('Tx_Extbase_Domain_Model_File', $fixture->getFile());
		$this->assertInstanceOf('t3lib_file_File', $fixture->getFile()->getObject());
		$this->assertEquals(1, $fixture->getFile()->getObject()->getUid());
	}

	/**
	 * @test
	 */
	public function areFileObjectsAvailableFromFixture() {
		$data = array(
			'files' => array(
				1,
				2,
			),
		);

		/** @var $fixture Tx_Extbase_Tests_Functional_Domain_Model_Fixture_FileContext */
		$fixture = $this->propertyMapper->convert(
			$data,
			'Tx_Extbase_Tests_Functional_Domain_Model_Fixture_FileContext',
			$this->propertyMapperConfiguration
		);

		$this->assertInstanceOf('Tx_Extbase_Persistence_ObjectStorage', $fixture->getFiles());
		$this->assertEquals(2, $fixture->getFiles()->count());

		$fixture->getFiles()->rewind();
		$this->assertEquals(1, $fixture->getFiles()->current()->getObject()->getUid());
		$fixture->getFiles()->next();
		$this->assertEquals(2, $fixture->getFiles()->current()->getObject()->getUid());
	}

	/**
	 * @test
	 */
	public function isFileReferenceObjectAvailableFromFixture() {
		$data = array(
			'fileReference' => 1,
		);

		/** @var $fixture Tx_Extbase_Tests_Functional_Domain_Model_Fixture_FileContext */
		$fixture = $this->propertyMapper->convert(
			$data,
			'Tx_Extbase_Tests_Functional_Domain_Model_Fixture_FileContext',
			$this->propertyMapperConfiguration
		);

		$this->assertInstanceOf('Tx_Extbase_Domain_Model_FileReference', $fixture->getFileReference());
		$this->assertInstanceOf('t3lib_file_FileReference', $fixture->getFileReference()->getObject());
		$this->assertInstanceOf('t3lib_file_File', $fixture->getFileReference()->getObject()->getOriginalFile());
		$this->assertEquals(1, $fixture->getFileReference()->getObject()->getOriginalFile()->getUid());
	}

	/**
	 * @test
	 */
	public function areFileReferenceObjectsAvailableFromFixture() {
		$data = array(
			'fileReferences' => array(
				1,
				2,
			),
		);

		/** @var $fixture Tx_Extbase_Tests_Functional_Domain_Model_Fixture_FileContext */
		$fixture = $this->propertyMapper->convert(
			$data,
			'Tx_Extbase_Tests_Functional_Domain_Model_Fixture_FileContext',
			$this->propertyMapperConfiguration
		);

		$this->assertInstanceOf('Tx_Extbase_Persistence_ObjectStorage', $fixture->getFileReferences());
		$this->assertEquals(2, $fixture->getFileReferences()->count());

		$fixture->getFileReferences()->rewind();
		$this->assertInstanceOf('t3lib_file_FileReference', $fixture->getFileReferences()->current()->getObject());
		$this->assertInstanceOf('t3lib_file_File', $fixture->getFileReferences()->current()->getObject()->getOriginalFile());
		$this->assertEquals(1, $fixture->getFileReferences()->current()->getObject()->getOriginalFile()->getUid());
		$fixture->getFileReferences()->next();
		$this->assertInstanceOf('t3lib_file_FileReference', $fixture->getFileReferences()->current()->getObject());
		$this->assertInstanceOf('t3lib_file_File', $fixture->getFileReferences()->current()->getObject()->getOriginalFile());
		$this->assertEquals(2, $fixture->getFileReferences()->current()->getObject()->getOriginalFile()->getUid());
	}

	/**
	 * @test
	 * @todo Find a way to mock the storage through all instances of Extbase
	 */
	public function isFolderObjectAvailableFromFixture() {
		if (!defined('TYPO3_MODE') || TYPO3_MODE !== 'BE') {
			$this->markTestSkipped('This test currently relies on an existing fileadmin/ storage in the filesystem.');
		}

		$data = array(
			'folder' => '9999:/',
		);

		/** @var $fixture Tx_Extbase_Tests_Functional_Domain_Model_Fixture_FileContext */
		$fixture = $this->propertyMapper->convert(
			$data,
			'Tx_Extbase_Tests_Functional_Domain_Model_Fixture_FileContext',
			$this->propertyMapperConfiguration
		);

		$this->assertInstanceOf('Tx_Extbase_Domain_Model_Folder', $fixture->getFolder());
		$this->assertInstanceOf('t3lib_file_Folder', $fixture->getFolder()->getObject());
		$this->assertEquals('/', $fixture->getFolder()->getObject()->getIdentifier());
	}

	/**
	 * @test
	 * @todo Find a way to mock the storage through all instances of Extbase
	 */
	public function areFolderObjectsAvailableFromFixture() {
		if (!defined('TYPO3_MODE') || TYPO3_MODE !== 'BE') {
			$this->markTestSkipped('This test currently relies on an existing fileadmin/ storage in the filesystem.');
		}

		$data = array(
			'folders' => array(
				'9999:/',
				'9999:/',
			),
		);

		/** @var $fixture Tx_Extbase_Tests_Functional_Domain_Model_Fixture_FileContext */
		$fixture = $this->propertyMapper->convert(
			$data,
			'Tx_Extbase_Tests_Functional_Domain_Model_Fixture_FileContext',
			$this->propertyMapperConfiguration
		);

		$this->assertInstanceOf('Tx_Extbase_Persistence_ObjectStorage', $fixture->getFolders());
		$this->assertEquals(2, $fixture->getFolders()->count());

		$fixture->getFolders()->rewind();
		$this->assertInstanceOf('Tx_Extbase_Domain_Model_Folder', $fixture->getFolders()->current());
		$this->assertInstanceOf('t3lib_file_Folder', $fixture->getFolders()->current()->getObject());
		$this->assertEquals('/', $fixture->getFolders()->current()->getObject()->getIdentifier());
		$fixture->getFolders()->next();
		$this->assertInstanceOf('Tx_Extbase_Domain_Model_Folder', $fixture->getFolders()->current());
		$this->assertInstanceOf('t3lib_file_Folder', $fixture->getFolders()->current()->getObject());
		$this->assertEquals('/', $fixture->getFolders()->current()->getObject()->getIdentifier());
	}

	/**
	 * @test
	 */
	public function isStaticFileCollectionObjectAvailableFromFixture() {
		$data = array(
			'staticFileCollection' => 2,
		);

		/** @var $fixture Tx_Extbase_Tests_Functional_Domain_Model_Fixture_FileContext */
		$fixture = $this->propertyMapper->convert(
			$data,
			'Tx_Extbase_Tests_Functional_Domain_Model_Fixture_FileContext',
			$this->propertyMapperConfiguration
		);

		$this->assertInstanceOf('Tx_Extbase_Domain_Model_StaticFileCollection', $fixture->getStaticFileCollection());
		$this->assertEquals(2, $fixture->getStaticFileCollection()->getObject()->getUid());
	}

	/**
	 * @test
	 */
	public function areStaticFileCollectionObjectsAvailableFromFixture() {
		$data = array(
			'staticFileCollections' => array(
				2,
			),
		);

		/** @var $fixture Tx_Extbase_Tests_Functional_Domain_Model_Fixture_FileContext */
		$fixture = $this->propertyMapper->convert(
			$data,
			'Tx_Extbase_Tests_Functional_Domain_Model_Fixture_FileContext',
			$this->propertyMapperConfiguration
		);

		$this->assertInstanceOf('Tx_Extbase_Persistence_ObjectStorage', $fixture->getStaticFileCollections());
		$this->assertEquals(1, $fixture->getStaticFileCollections()->count());

		$this->assertInstanceOf('Tx_Extbase_Domain_Model_StaticFileCollection', $fixture->getStaticFileCollections()->current());
		$this->assertEquals(2, $fixture->getStaticFileCollections()->current()->getObject()->getUid());
	}

	/**
	 * @test
	 */
	public function isFolderBasedFileCollectionObjectAvailableFromFixture() {
		$data = array(
			'folderBasedFileCollection' => 1,
		);

		/** @var $fixture Tx_Extbase_Tests_Functional_Domain_Model_Fixture_FileContext */
		$fixture = $this->propertyMapper->convert(
			$data,
			'Tx_Extbase_Tests_Functional_Domain_Model_Fixture_FileContext',
			$this->propertyMapperConfiguration
		);

		$this->assertInstanceOf('Tx_Extbase_Domain_Model_FolderBasedFileCollection', $fixture->getFolderBasedFileCollection());
		$this->assertEquals(1, $fixture->getFolderBasedFileCollection()->getObject()->getUid());
	}

	/**
	 * @test
	 */
	public function areFolderBasedFileCollectionObjectAvailableFromFixture() {
		$data = array(
			'folderBasedFileCollections' => array(
				1,
			),
		);

		/** @var $fixture Tx_Extbase_Tests_Functional_Domain_Model_Fixture_FileContext */
		$fixture = $this->propertyMapper->convert(
			$data,
			'Tx_Extbase_Tests_Functional_Domain_Model_Fixture_FileContext',
			$this->propertyMapperConfiguration
		);

		$this->assertInstanceOf('Tx_Extbase_Persistence_ObjectStorage', $fixture->getFolderBasedFileCollections());
		$this->assertEquals(1, $fixture->getFolderBasedFileCollections()->count());

		$this->assertInstanceOf('Tx_Extbase_Domain_Model_FolderBasedFileCollection', $fixture->getFolderBasedFileCollections()->current());
		$this->assertEquals(1, $fixture->getFolderBasedFileCollections()->current()->getObject()->getUid());
	}
}
?>
