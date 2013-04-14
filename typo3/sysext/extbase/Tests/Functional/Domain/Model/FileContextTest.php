<?php
namespace TYPO3\CMS\Extbase\Tests\Functional\Domain\Model;

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
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('extbase') . 'Tests/Functional/Domain/Model/Fixture/FileContext.php';

/**
 * Test case to check functionality type converters on FAL domain objects.
 */
class FileContextTest extends \Tx_Extbase_Tests_Functional_BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Property\PropertyMapper
	 */
	protected $propertyMapper;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfiguration
	 */
	protected $propertyMapperConfiguration;

	/**
	 * Sets up this test suite.
	 */
	protected function setUp() {
		$this->markTestIncomplete('Functional tests do not work yet.');
		parent::setUp();
		$this->importDataSet(__DIR__ . '/Fixture/data_sys_file_storage.xml');
		$this->importDataSet(__DIR__ . '/Fixture/data_sys_file.xml');
		$this->importDataSet(__DIR__ . '/Fixture/data_sys_file_reference.xml');
		$this->importDataSet(__DIR__ . '/Fixture/data_sys_file_collection.xml');
		/** @var $configurationBuilder \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationBuilder */
		$configurationBuilder = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Property\\PropertyMappingConfigurationBuilder');
		$this->propertyMapperConfiguration = $configurationBuilder->build('TYPO3\\CMS\\Extbase\\Mvc\\Controller\\MvcPropertyMappingConfiguration');
		$this->propertyMapper = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Property\\PropertyMapper');
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
			'file' => 1
		);
		/** @var $fixture \TYPO3\CMS\Extbase\Tests\Functional\Domain\Model\Fixture\FileContext */
		$fixture = $this->propertyMapper->convert($data, 'TYPO3\\CMS\\Extbase\\Tests\\Functional\\Domain\\Model\\Fixture\\FileContext', $this->propertyMapperConfiguration);
		$this->assertInstanceOf('TYPO3\\CMS\\Extbase\\Domain\\Model\\File', $fixture->getFile());
		$this->assertInstanceOf('TYPO3\\CMS\\Core\\Resource\\File', $fixture->getFile()->getOriginalResource());
		$this->assertEquals(1, $fixture->getFile()->getOriginalResource()->getUid());
	}

	/**
	 * @test
	 */
	public function areFileObjectsAvailableFromFixture() {
		$data = array(
			'files' => array(
				1,
				2
			)
		);
		/** @var $fixture \TYPO3\CMS\Extbase\Tests\Functional\Domain\Model\Fixture\FileContext */
		$fixture = $this->propertyMapper->convert($data, 'TYPO3\\CMS\\Extbase\\Tests\\Functional\\Domain\\Model\\Fixture\\FileContext', $this->propertyMapperConfiguration);
		$this->assertInstanceOf('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage', $fixture->getFiles());
		$this->assertEquals(2, $fixture->getFiles()->count());
		$fixture->getFiles()->rewind();
		$this->assertEquals(1, $fixture->getFiles()->current()->getOriginalResource()->getUid());
		$fixture->getFiles()->next();
		$this->assertEquals(2, $fixture->getFiles()->current()->getOriginalResource()->getUid());
	}

	/**
	 * @test
	 */
	public function isFileReferenceObjectAvailableFromFixture() {
		$data = array(
			'fileReference' => 1
		);
		/** @var $fixture \TYPO3\CMS\Extbase\Tests\Functional\Domain\Model\Fixture\FileContext */
		$fixture = $this->propertyMapper->convert($data, 'TYPO3\\CMS\\Extbase\\Tests\\Functional\\Domain\\Model\\Fixture\\FileContext', $this->propertyMapperConfiguration);
		$this->assertInstanceOf('TYPO3\\CMS\\Extbase\\Domain\\Model\\FileReference', $fixture->getFileReference());
		$this->assertInstanceOf('TYPO3\\CMS\\Core\\Resource\\FileReference', $fixture->getFileReference()->getOriginalResource());
		$this->assertInstanceOf('TYPO3\\CMS\\Core\\Resource\\File', $fixture->getFileReference()->getOriginalResource()->getOriginalFile());
		$this->assertEquals(1, $fixture->getFileReference()->getOriginalResource()->getOriginalFile()->getUid());
	}

	/**
	 * @test
	 */
	public function areFileReferenceObjectsAvailableFromFixture() {
		$data = array(
			'fileReferences' => array(
				1,
				2
			)
		);
		/** @var $fixture \TYPO3\CMS\Extbase\Tests\Functional\Domain\Model\Fixture\FileContext */
		$fixture = $this->propertyMapper->convert($data, 'TYPO3\\CMS\\Extbase\\Tests\\Functional\\Domain\\Model\\Fixture\\FileContext', $this->propertyMapperConfiguration);
		$this->assertInstanceOf('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage', $fixture->getFileReferences());
		$this->assertEquals(2, $fixture->getFileReferences()->count());
		$fixture->getFileReferences()->rewind();
		$this->assertInstanceOf('TYPO3\\CMS\\Core\\Resource\\FileReference', $fixture->getFileReferences()->current()->getOriginalResource());
		$this->assertInstanceOf('TYPO3\\CMS\\Core\\Resource\\File', $fixture->getFileReferences()->current()->getOriginalResource()->getOriginalFile());
		$this->assertEquals(1, $fixture->getFileReferences()->current()->getOriginalResource()->getOriginalFile()->getUid());
		$fixture->getFileReferences()->next();
		$this->assertInstanceOf('TYPO3\\CMS\\Core\\Resource\\FileReference', $fixture->getFileReferences()->current()->getOriginalResource());
		$this->assertInstanceOf('TYPO3\\CMS\\Core\\Resource\\File', $fixture->getFileReferences()->current()->getOriginalResource()->getOriginalFile());
		$this->assertEquals(2, $fixture->getFileReferences()->current()->getOriginalResource()->getOriginalFile()->getUid());
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
			'folder' => '9999:/'
		);
		/** @var $fixture \TYPO3\CMS\Extbase\Tests\Functional\Domain\Model\Fixture\FileContext */
		$fixture = $this->propertyMapper->convert($data, 'TYPO3\\CMS\\Extbase\\Tests\\Functional\\Domain\\Model\\Fixture\\FileContext', $this->propertyMapperConfiguration);
		$this->assertInstanceOf('TYPO3\\CMS\\Extbase\\Domain\\Model\\Folder', $fixture->getFolder());
		$this->assertInstanceOf('TYPO3\\CMS\\Core\\Resource\\Folder', $fixture->getFolder()->getOriginalResource());
		$this->assertEquals('/', $fixture->getFolder()->getOriginalResource()->getIdentifier());
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
				'9999:/'
			)
		);
		/** @var $fixture \TYPO3\CMS\Extbase\Tests\Functional\Domain\Model\Fixture\FileContext */
		$fixture = $this->propertyMapper->convert($data, 'TYPO3\\CMS\\Extbase\\Tests\\Functional\\Domain\\Model\\Fixture\\FileContext', $this->propertyMapperConfiguration);
		$this->assertInstanceOf('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage', $fixture->getFolders());
		$this->assertEquals(2, $fixture->getFolders()->count());
		$fixture->getFolders()->rewind();
		$this->assertInstanceOf('TYPO3\\CMS\\Extbase\\Domain\\Model\\Folder', $fixture->getFolders()->current());
		$this->assertInstanceOf('TYPO3\\CMS\\Core\\Resource\\Folder', $fixture->getFolders()->current()->getOriginalResource());
		$this->assertEquals('/', $fixture->getFolders()->current()->getOriginalResource()->getIdentifier());
		$fixture->getFolders()->next();
		$this->assertInstanceOf('TYPO3\\CMS\\Extbase\\Domain\\Model\\Folder', $fixture->getFolders()->current());
		$this->assertInstanceOf('TYPO3\\CMS\\Core\\Resource\\Folder', $fixture->getFolders()->current()->getOriginalResource());
		$this->assertEquals('/', $fixture->getFolders()->current()->getOriginalResource()->getIdentifier());
	}

	/**
	 * @test
	 */
	public function isStaticFileCollectionObjectAvailableFromFixture() {
		$data = array(
			'staticFileCollection' => 2
		);
		/** @var $fixture \TYPO3\CMS\Extbase\Tests\Functional\Domain\Model\Fixture\FileContext */
		$fixture = $this->propertyMapper->convert($data, 'TYPO3\\CMS\\Extbase\\Tests\\Functional\\Domain\\Model\\Fixture\\FileContext', $this->propertyMapperConfiguration);
		$this->assertInstanceOf('TYPO3\\CMS\\Extbase\\Domain\\Model\\StaticFileCollection', $fixture->getStaticFileCollection());
		$this->assertEquals(2, $fixture->getStaticFileCollection()->getObject()->getUid());
	}

	/**
	 * @test
	 */
	public function areStaticFileCollectionObjectsAvailableFromFixture() {
		$data = array(
			'staticFileCollections' => array(
				2
			)
		);
		/** @var $fixture \TYPO3\CMS\Extbase\Tests\Functional\Domain\Model\Fixture\FileContext */
		$fixture = $this->propertyMapper->convert($data, 'TYPO3\\CMS\\Extbase\\Tests\\Functional\\Domain\\Model\\Fixture\\FileContext', $this->propertyMapperConfiguration);
		$this->assertInstanceOf('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage', $fixture->getStaticFileCollections());
		$this->assertEquals(1, $fixture->getStaticFileCollections()->count());
		$this->assertInstanceOf('TYPO3\\CMS\\Extbase\\Domain\\Model\\StaticFileCollection', $fixture->getStaticFileCollections()->current());
		$this->assertEquals(2, $fixture->getStaticFileCollections()->current()->getObject()->getUid());
	}

	/**
	 * @test
	 */
	public function isFolderBasedFileCollectionObjectAvailableFromFixture() {
		$data = array(
			'folderBasedFileCollection' => 1
		);
		/** @var $fixture \TYPO3\CMS\Extbase\Tests\Functional\Domain\Model\Fixture\FileContext */
		$fixture = $this->propertyMapper->convert($data, 'TYPO3\\CMS\\Extbase\\Tests\\Functional\\Domain\\Model\\Fixture\\FileContext', $this->propertyMapperConfiguration);
		$this->assertInstanceOf('TYPO3\\CMS\\Extbase\\Domain\\Model\\FolderBasedFileCollection', $fixture->getFolderBasedFileCollection());
		$this->assertEquals(1, $fixture->getFolderBasedFileCollection()->getObject()->getUid());
	}

	/**
	 * @test
	 */
	public function areFolderBasedFileCollectionObjectAvailableFromFixture() {
		$data = array(
			'folderBasedFileCollections' => array(
				1
			)
		);
		/** @var $fixture \TYPO3\CMS\Extbase\Tests\Functional\Domain\Model\Fixture\FileContext */
		$fixture = $this->propertyMapper->convert($data, 'TYPO3\\CMS\\Extbase\\Tests\\Functional\\Domain\\Model\\Fixture\\FileContext', $this->propertyMapperConfiguration);
		$this->assertInstanceOf('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage', $fixture->getFolderBasedFileCollections());
		$this->assertEquals(1, $fixture->getFolderBasedFileCollections()->count());
		$this->assertInstanceOf('TYPO3\\CMS\\Extbase\\Domain\\Model\\FolderBasedFileCollection', $fixture->getFolderBasedFileCollections()->current());
		$this->assertEquals(1, $fixture->getFolderBasedFileCollections()->current()->getObject()->getUid());
	}
}

?>
