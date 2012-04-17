<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Andreas Wolf <andreas.wolf@ikt-werk.de>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
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


require_once 'vfsStream/vfsStream.php';
require_once dirname(dirname(__FILE__)) . '/BaseTestCase.php';
require_once dirname(__FILE__) . '/Fixtures/LocalDriverFilenameFilter.php';

/**
 * Testcase for the local storage driver class of the TYPO3 VFS
 *
 * @package TYPO3
 * @subpackage t3lib
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 */
class t3lib_file_Driver_LocalDriverTest extends t3lib_file_BaseTestCase {

	private static $testDirs = array();

	public function setUp() {
		parent::setUp();

			// use a mocked file repository to avoid updating the index when doing property update tests
		$mockedRepository = $this->getMock('t3lib_file_Repository_FileRepository');
		t3lib_div::setSingletonInstance('t3lib_file_Repository_FileRepository', $mockedRepository);
	}

	public function tearDown() {
		t3lib_div::purgeInstances();
		parent::tearDown();
	}

	public static function tearDownAfterClass() {
		foreach (self::$testDirs as $dir) {
			t3lib_div::rmdir($dir, TRUE);
		}
	}

	/**
	 * Creates a "real" directory for doing tests. This is neccessary because some file system properties (e.g. permissions)
	 * cannot be reflected by vfsStream, and some methods (like touch()) don't work there either.
	 *
	 * Created directories are automatically destroyed by the tearDownAfterClass() method.
	 *
	 * @return string
	 */
	protected function createRealTestdir() {
		$basedir = PATH_site . 'typo3temp/fal-test-' . t3lib_div::shortMD5(microtime(TRUE));
		mkdir($basedir);
		self::$testDirs[] = $basedir;
		return $basedir;
	}

	/**
	 * Creates a driver fixture object, optionally using a given mount object.
	 *
	 * IMPORTANT: Call this only after setting up the virtual file system (with the addTo* methods)!
	 *
	 * @param $driverConfiguration
	 * @param t3lib_file_Storage $storageObject
	 * @param array $mockedDriverMethods
	 * @return t3lib_file_Driver_LocalDriver
	 */
	protected function createDriverFixture($driverConfiguration, t3lib_file_Storage $storageObject = NULL, $mockedDriverMethods = array()) {
		$this->initializeVfs();

		if ($storageObject == NULL) {
			$storageObject = $this->getMock('t3lib_file_Storage', array(), array(), '', FALSE);
		}

		if (count($mockedDriverMethods) == 0) {
			$driver = new t3lib_file_Driver_LocalDriver($driverConfiguration);
		} else {
			$driver = $this->getMock('t3lib_file_Driver_LocalDriver', $mockedDriverMethods, array($driverConfiguration));
		}
		$storageObject->setDriver($driver);
		$driver->setStorage($storageObject);
		$driver->processConfiguration();
		$driver->initialize();
		return $driver;
	}


	/**
	 * @test
	 */
	public function rootLevelFolderIsCreatedWithCorrectArguments() {
		$mockedMount = $this->getMock('t3lib_file_Storage', array(), array(), '', FALSE);
		$fixture = $this->createDriverFixture(array(), $mockedMount);

		$mockedFactory = $this->getMock('t3lib_file_Factory');
		$mockedFactory->expects($this->once())->method('createFolderObject')->with(
			$this->equalTo($mockedMount), $this->equalTo('/'), $this->equalTo(''));
		t3lib_div::setSingletonInstance('t3lib_file_Factory', $mockedFactory);

		$fixture->getRootLevelFolder();

		t3lib_div::setSingletonInstance('t3lib_file_Factory', new t3lib_file_Factory());
	}

	/**
	 * @test
	 */
	public function getDefaultFolderReturnsFolderForTemporaryPath() {
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

		$folder = $fixture->getDefaultFolder();
		$this->assertStringEndsWith('_temp_/', $folder->getIdentifier());
	}

	/**
	 * @test
	 */
	public function defaultLevelFolderFolderIsCreatedIfItDoesntExist() {
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

		$storageFolder = $fixture->getDefaultFolder();
		$this->assertFileExists($this->getUrlInMount('/_temp_/'));
	}

	/**
	 * @test
	 */
	public function getFolderInFolderReturnsCorrectFolderObject() {
		$this->addToMount(array(
			'someDir' => array(
				'someSubdir' => array()
			)
		));
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

		$parentFolder = $fixture->getFolder('/someDir');
		$folder = $fixture->getFolderInFolder('someSubdir', $parentFolder);

		$this->assertEquals('/someDir/someSubdir/', $folder->getIdentifier());
	}

	/**
	 * @test
	 */
	public function createFolderCreatesFolderOnDisk() {
		$this->addToMount(array('some' => array('folder' => array())));
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

		$mockedFolder = $this->getSimpleFolderMock('/some/folder/');

		$fixture->createFolder('path', $mockedFolder);

		$this->assertFileExists($this->getUrlInMount('/some/folder/'));
		$this->assertFileExists($this->getUrlInMount('/some/folder/path'));
	}

	/**
	 * @test
	 */
	public function createFolderReturnsFolderObject() {
		$this->addToMount(array('some' => array('folder' => array())));
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

		$mockedFolder = $this->getSimpleFolderMock('/some/folder/');

		$createdFolder = $fixture->createFolder('path', $mockedFolder);

		$this->assertEquals('/some/folder/path/', $createdFolder->getIdentifier());
	}

	public function createFolderSanitizesFolderNameBeforeCreation_dataProvider() {
		return array(
			'folder name with NULL character' => array(
				"some\x00Folder",
				'some_Folder'
			),
			'folder name with directory part' => array(
				'../someFolder',
				'.._someFolder'
			),
		);
	}

	/**
	 * @test
	 * @dataProvider createFolderSanitizesFolderNameBeforeCreation_dataProvider
	 */
	public function createFolderSanitizesFolderNameBeforeCreation($newFolderName, $expectedFolderName) {
		$this->addToMount(array('some' => array('folder' => array())));
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

		$mockedFolder = $this->getSimpleFolderMock('/some/folder/');

		$fixture->createFolder($newFolderName, $mockedFolder);

		$this->assertFileExists($this->getUrlInMount('/some/folder/' . $expectedFolderName));
	}

	/**
	 * @test
	 */
	public function driverConfigVerificationFailsIfConfiguredBasePathDoesNotExist() {
		$this->setExpectedException('t3lib_file_exception_InvalidConfigurationException', '', 1299233097);

		$driverConfiguration = array(
			'basePath' => vfsStream::url($this->basedir . 'doesntexist/')
		);

		$this->assertFalse(file_exists($driverConfiguration['basePath']));
		t3lib_file_Driver_LocalDriver::verifyConfiguration($driverConfiguration);
	}

	/**
	 * @test
	 */
	public function basePathIsNormalizedWithTrailingSlash() {
		$driverConfiguration = array(
			'basePath' => $this->getMountRootUrl()
		);

		$fixture = $this->createDriverFixture($driverConfiguration);
		$this->assertEquals('/', substr($fixture->getAbsoluteBasePath(), -1));
	}

	/**
	 * @test
	 */
	public function noSecondSlashIsAddedIfBasePathAlreadyHasTrailingSlash() {
		$driverConfiguration = array(
			'basePath' => $this->getMountRootUrl()
		);

		$fixture = $this->createDriverFixture($driverConfiguration);
		$this->assertNotEquals('/', substr($fixture->getAbsoluteBasePath(), -2, 1));
	}

	/**
	 * @test
	 */
	public function getAbsolutePathReturnsCorrectPath() {
		$this->addToMount(array(
			'someFolder' => array(
				'file1.ext' => 'asdfg'
			)
		));
		$mockedFile = $this->getSimpleFileMock('someFolder/file1.ext');
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

		$path = $fixture->getAbsolutePath($mockedFile);
		$this->assertTrue(file_exists($path));
		$this->assertEquals($this->getUrlInMount('/someFolder/file1.ext'), $path);
	}

	/**
	 * @test
	 */
	public function statReturnsCorrectFileInfo() {
		$contents = 'asdfg';
		$this->addToMount(array('file1.ext' => $contents));
		$mockedFile = $this->getSimpleFileMock('file1.ext');
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

		$path = $fixture->getAbsolutePath($mockedFile);
		$stat = $fixture->getLowLevelFileInfo($mockedFile);
		$this->assertEquals(strlen($contents), $stat['size']);
		$this->assertEquals(filectime($path), $stat['ctime']);
		$this->assertEquals(fileatime($path), $stat['mtime']);
		$this->assertEquals(filemtime($path), $stat['atime']);
	}

	/**
	 * @test
	 */
	public function addFileMovesFileToCorrectLocation() {
		$mockedFolder = $this->getSimpleFolderMock('/targetFolder/');

		$this->addToMount(array('targetFolder' => array()));
		$this->addToVfs(array(
			'sourceFolder' => array(
				'file' => 'asdf'
			)
		));
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

		$this->assertTrue(file_exists($this->getUrl('sourceFolder/file')));
		$fixture->addFile($this->getUrl('sourceFolder/file'), $mockedFolder, 'file');
		$this->assertTrue(file_exists($this->getUrlInMount('/targetFolder/file')));
	}

	/**
	 * @test
	 */
	public function addFileUsesFilenameIfGiven() {
		$mockedFolder = $this->getSimpleFolderMock('/targetFolder/');

		$this->addToMount(array('targetFolder' => array()));
		$this->addToVfs(array(
			'sourceFolder' => array(
				'file' => 'asdf'
			)
		));
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

		$this->assertTrue(file_exists($this->getUrl('sourceFolder/file')));
		$fixture->addFile($this->getUrl('sourceFolder/file'), $mockedFolder, 'targetFile');
		$this->assertTrue(file_exists($this->getUrlInMount('/targetFolder/targetFile')));
	}

	/**
	 * @test
	 */
	public function addFileFailsIfFileIsInDriverStorage() {
		$mockedFolder = $this->getMock('t3lib_file_Folder', array(), array(), '', FALSE);
		$mockedFolder->expects($this->any())->method('getIdentifier')->will($this->returnValue('/targetFolder/'));

		$this->setExpectedException('InvalidArgumentException', '', 1314778269);

		$this->addToMount(array(
			'targetFolder' => array(
				'file' => 'asdf'
			)
		));
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

		$fixture->addFile($this->getUrlInMount('/targetFolder/file'), $mockedFolder, 'file');
	}

	/**
	 * @test
	 */
	public function addFileReturnsFileObject() {
		$mockedFolder = $this->getMock('t3lib_file_Folder', array(), array(), '', FALSE);
		$mockedFolder->expects($this->any())->method('getIdentifier')->will($this->returnValue('/targetFolder/'));

		$this->addToMount(array('targetFolder' => array()));
		$this->addToVfs(array(
			'sourceFolder' => array(
				'file' => 'asdf'
			)
		));
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

		$this->assertTrue(file_exists($this->getUrl('sourceFolder/file')));
		$fileObject = $fixture->addFile($this->getUrl('sourceFolder/file'), $mockedFolder, 'file');

		$this->assertInstanceOf('t3lib_file_File', $fileObject);
		$this->assertEquals('file', $fileObject->getName());
		$this->assertEquals('/targetFolder/file', $fileObject->getIdentifier());
	}

	/**
	 * @test
	 */
	public function addFileRawCreatesCopyOfFile() {
		$this->addToMount(array('targetFolder' => array()));
		$this->addToVfs(array(
			'sourceFolder' => array(
				'file' => 'asdf'
			)
		));
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

		$this->assertTrue(file_exists($this->getUrl('sourceFolder/file')));
		$fileIdentifier = $fixture->addFileRaw($this->getUrl('sourceFolder/file'), $this->getSimpleFolderMock('/targetFolder/'), 'somefile');
		$this->assertTrue(file_exists($this->getUrl('sourceFolder/file')));
		$this->assertTrue(file_exists($this->getUrlInMount('targetFolder/somefile')));
		$this->assertEquals('/targetFolder/somefile', $fileIdentifier);
	}

	/**
	 * @test
	 */
	public function deleteFileRawRemovesFile() {
		$this->addToMount(array('targetFolder' => array(
			'file' => 'asdjlkfa'
		)));
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

		$this->assertTrue(file_exists($this->getUrlInMount('targetFolder/file')));
		$fixture->deleteFileRaw('/targetFolder/file');
		$this->assertFalse(file_exists($this->getUrlInMount('targetFolder/file')));
	}

	/**
	 * @test
	 */
	public function replacingFileUpdatesMetadataInFileObject() {
		$this->addToMount(array('targetFolder' => array(
			'file' => 'asdjlkfa'
		)));
		$this->addToVfs(array('sourceFolder' => array(
			'file' => 'asjdalks'
		)));
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

		$mockedFile = $this->getSimpleFileMock('/targetFolder/file', array('updateProperties'));
		$mockedFile->expects($this->once())->method('updateProperties');

		$fixture->replaceFile($mockedFile, $this->getUrl('sourceFolder/file'));
	}

	/**
	 * @test
	 */
	public function existenceChecksWorkForFilesAndFolders() {
		$this->addToMount(array(
			'file' => 'asdf',
			'folder' => array()
		));
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

			// Using slashes at the beginning of paths because they will be stored in the DB this way.
		$this->assertTrue($fixture->fileExists('/file'));
		$this->assertTrue($fixture->folderExists('/folder/'));
		$this->assertFalse($fixture->fileExists('/nonexistingFile'));
		$this->assertFalse($fixture->folderExists('/nonexistingFolder/'));
	}

	/**
	 * @test
	 */
	public function existenceChecksInFolderWorkForFilesAndFolders() {
		$mockedFolder = $this->getSimpleFolderMock('/subfolder/');

		$this->addToMount(array('subfolder' => array(
			'file' => 'asdf',
			'folder' => array()
		)));
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

		$this->assertTrue($fixture->fileExistsInFolder('file', $mockedFolder));
		$this->assertTrue($fixture->folderExistsInFolder('folder', $mockedFolder));
		$this->assertFalse($fixture->fileExistsInFolder('nonexistingFile', $mockedFolder));
		$this->assertFalse($fixture->folderExistsInFolder('nonexistingFolder', $mockedFolder));
	}

	/**
	 * @test
	 */
	public function getPublicUrlReturnsCorrectUriForConfiguredBaseUri() {
		$baseUri = 'http://example.org/foobar/' . uniqid();
		$this->addToMount(array(
			'file.ext' => 'asdf',
			'subfolder' => array(
				'file2.ext' => 'asdf'
			)
		));
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl(),
			'baseUri' => $baseUri
		));

		$mockedFile1 = $this->getMock('t3lib_file_File', array(), array(), '', FALSE);
		$mockedFile1->expects($this->any())->method('getIdentifier')->will($this->returnValue('/file.ext'));
		$mockedFile2 = $this->getMock('t3lib_file_File', array(), array(), '', FALSE);
		$mockedFile2->expects($this->any())->method('getIdentifier')->will($this->returnValue('/subfolder/file2.ext'));

		$this->assertEquals($baseUri . '/file.ext', $fixture->getPublicUrl($mockedFile1));
		$this->assertEquals($baseUri . '/subfolder/file2.ext', $fixture->getPublicUrl($mockedFile2));
	}

	/**
	 * @test
	 */
	public function fileContentsCanBeWrittenAndRead() {
		$fileContents = 'asdf';
		$this->addToMount(array(
			'file.ext' => $fileContents
		));
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

		$mockedFile = $this->getMock('t3lib_file_File', array(), array(), '', FALSE);
		$mockedFile->expects($this->any())->method('getIdentifier')->will($this->returnValue('/file.ext'));

		$this->assertEquals($fileContents, $fixture->getFileContents($mockedFile), 'File contents could not be read');
		$newFileContents = 'asdfgh';
		$fixture->setFileContents($mockedFile, $newFileContents);
		$this->assertEquals($newFileContents, $fixture->getFileContents($mockedFile), 'New file contents could not be read.');
	}

	/**
	 * @test
	 */
	public function setFileContentsReturnsNumberOfBytesWrittenToFile() {
		$fileContents = 'asdf';
		$this->addToMount(array(
			'file.ext' => $fileContents
		));
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

		$mockedFile = $this->getSimpleFileMock('/file.ext');

		$newFileContents = 'asdfgh';
		$bytesWritten = $fixture->setFileContents($mockedFile, $newFileContents);

		$this->assertEquals(strlen($newFileContents), $bytesWritten);
	}

	/**
	 * @test
	 * @depends existenceChecksWorkForFilesAndFolders
	 * @return array The driver fixture, the mocked file
	 */
	public function newFilesCanBeCreated() {
		$this->addToMount(array(
			'someDir' => array()
		));
		/** @var $fixture t3lib_file_Driver_LocalDriver */
		list($basedir, $fixture) = $this->prepareRealTestEnvironment();

		mkdir($basedir . '/someDir');
		$fixture->createFile('testfile.txt', $fixture->getFolder('someDir'));

		$mockedFile = $this->getSimpleFileMock('/someDir/testfile.txt');

		$this->assertTrue($fixture->fileExists('/someDir/testfile.txt'));

		return array($fixture, $mockedFile);
	}

	/**
	 * @test
	 * @depends newFilesCanBeCreated
	 */
	public function createdFilesAreEmpty(array $arguments) {
		/** @var $fixture t3lib_file_Driver_LocalDriver */
		list($fixture, $mockedFile) = $arguments;
		$fileData = $fixture->getFileContents($mockedFile);
		$this->assertEquals(0, strlen($fileData));
	}

	/**********************************
	 * File and directory listing
	 **********************************/

	/**
	 * @test
	 */
	public function getFileReturnsCorrectIdentifier() {
		$this->addToMount(array(
			'someDir' => array(
				'someFile' => 'asdfg'
			),
			'someFileAtRootLevel' => 'foobar'
		));
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

		$subdirFileInfo = $fixture->getFileInfoByIdentifier('/someDir/someFile');
		$this->assertEquals('/someDir/someFile', $subdirFileInfo['identifier']);
		$rootFileInfo = $fixture->getFileInfoByIdentifier('/someFileAtRootLevel');
		$this->assertEquals('/someFileAtRootLevel', $rootFileInfo['identifier']);
	}

	/**
	 * @test
	 */
	public function getFileThrowsExceptionIfFileDoesNotExist() {
		$this->setExpectedException('InvalidArgumentException', '', 1314516809);
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

		$fixture->getFileInfoByIdentifier('/some/file/at/a/random/path');
	}

	/**
	 * @test
	 */
	public function getFileListReturnsEmptyArrayForEmptyDirectory() {
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

		$fileList = $fixture->getFileList('/');

		$this->assertEmpty($fileList);
	}

	/**
	 * @test
	 */
	public function getFileListReturnsAllFilesInDirectory() {
		$dirStructure = array(
			'aDir' => array(),
			'file1' => 'asdfg',
			'file2' => 'fdsa'
		);
		$this->addToMount($dirStructure);
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

		$fileList = $fixture->getFileList('/');

		$this->assertEquals(array('file1', 'file2'), array_keys($fileList));
	}

	/**
	 * @test
	 */
	public function getFileListFailsIfDirectoryDoesNotExist() {
		$this->setExpectedException('InvalidArgumentException', '', 1314349666);

		$this->addToMount(array('somefile' => ''));
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

		$fixture->getFileList('somedir/');
	}

	/**
	 * @test
	 */
	public function getFileListDoesNotReturnHiddenFilesByDefault() {
		$dirStructure = array(
			'aDir' => array(),
			'.someHiddenFile' => 'asdf',
			'file1' => 'asdfg',
			'file2' => 'fdsa'
		);
		$this->addToMount($dirStructure);
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

		$fileList = $fixture->getFileList('/');

		$this->assertEquals(array('file1', 'file2'), array_keys($fileList));
	}

	/**
	 * @test
	 */
	public function getFileListFiltersItemsWithGivenFilterMethods() {
		$dirStructure = array(
			'fileA' => 'asdfg',
			'fileB' => 'fdsa'
		);
		$this->addToMount($dirStructure);
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));
		$filterCallbacks = array(
			array('t3lib_file_Tests_Driver_Fixtures_LocalDriverFilenameFilter', 'filterFilename')
		);

		$fileList = $fixture->getFileList('/', 0, 0, $filterCallbacks);

		$this->assertNotContains('fileA', array_keys($fileList));
	}

	/**
	 * @test
	 */
	public function getFolderListReturnsAllDirectoriesInDirectory() {
		$dirStructure = array(
			'dir1' => array(),
			'dir2' => array(),
			'file' => 'asdfg'
		);
		$this->addToMount($dirStructure);

		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

		$fileList = $fixture->getFolderList('/');

		$this->assertEquals(array('dir1', 'dir2'), array_keys($fileList));
	}

	/**
	 * @test
	 */
	public function getFolderListDoesNotReturnHiddenFoldersByDefault() {
		$dirStructure = array(
			'.someHiddenFile' => array(),
			'aDir' => array(),
			'file1' => '',
		);
		$this->addToMount($dirStructure);
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

		$fileList = $fixture->getFolderList('/');

		$this->assertEquals(array('aDir'), array_keys($fileList));
	}

	/**
	 * @test
	 */
	public function getFolderListUsesCorrectPathForItems() {
		$this->addToMount(array(
			'dir1' => array(
				'subdir1' => array()
			)
		));

		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

		$FolderList = $fixture->getFolderList('/');
		$this->assertEquals('/dir1/', $FolderList['dir1']['identifier']);

		$FolderList = $fixture->getFolderList('/dir1/');
		$this->assertEquals('/dir1/subdir1/', $FolderList['subdir1']['identifier']);
	}

	/**
	 * Checks if the folder names . and .. are ignored when listing subdirectories
	 *
	 * @test
	 */
	public function getFolderListLeavesOutNavigationalEntries() {
			// we have to add .. and . manually, as these are not included in vfsStream directory listings (as opposed
			// to normal file listings)
		$this->addToMount(array(
			'..' => array(),
			'.' => array()
		));
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

		$fileList = $fixture->getFolderList('/');

		$this->assertEmpty($fileList);
	}

	/**
	 * @test
	 */
	public function getFolderListFiltersItemsWithGivenFilterMethods() {
		$dirStructure = array(
			'folderA' => array(),
			'folderB' => array()
		);
		$this->addToMount($dirStructure);
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));
		$filterCallbacks = array(
			array('t3lib_file_Tests_Driver_Fixtures_LocalDriverFilenameFilter', 'filterFilename')
		);

		$folderList = $fixture->getFolderList('/', 0, 0, $filterCallbacks);

		$this->assertNotContains('folderA', array_keys($folderList));
	}

	/**
	 * @test
	 */
	public function getFolderListFailsIfDirectoryDoesNotExist() {
		$this->setExpectedException('InvalidArgumentException', '', 1314349666);

		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));
		vfsStream::create(array($this->basedir => array('somefile' => '')));

		$fixture->getFolderList('somedir/');
	}

	/**
	 * @test
	 */
	public function hashReturnsCorrectHashes() {
		$contents = "68b329da9893e34099c7d8ad5cb9c940";
		$expectedMd5Hash = "8c67dbaf0ba22f2e7fbc26413b86051b";
		$expectedSha1Hash = "a60cd808ba7a0bcfa37fa7f3fb5998e1b8dbcd9d";

		$mockedFile = $this->getSimpleFileMock('/hashFile');
		$this->addToMount(array('hashFile' => $contents));
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

		$this->assertEquals($expectedSha1Hash, $fixture->hash($mockedFile, 'sha1'));
		$this->assertEquals($expectedMd5Hash, $fixture->hash($mockedFile, 'md5'));
	}

	/**
	 * @test
	 */
	public function hashingWithUnsupportedAlgorithmFails() {
		$this->setExpectedException('InvalidArgumentException', '', 1304964032);

		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));
		$fixture->hash($this->getSimpleFileMock('/hashFile'), uniqid());
	}

	/**
	 * @test
	 * @covers t3lib_file_Driver_LocalDriver::getFileForLocalProcessing
	 */
	public function getFileForLocalProcessingCreatesCopyOfFileByDefault() {
		$fileContents = 'asdfgh';
		$this->addToMount(array(
			'someDir' => array(
				'someFile' => $fileContents
			)
		));
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		), NULL, array('copyFileToTemporaryPath'));
		$mockedFile = $this->getSimpleFileMock('/someDir/someFile');
			// TODO add parameter expectation for $mockedFile as soon as PHPUnit supports object identity matching in parameter expectations
		$fixture->expects($this->once())->method('copyFileToTemporaryPath');

		$fixture->getFileForLocalProcessing($mockedFile);
	}

	/**
	 * @test
	 */
	public function getFileForLocalProcessingReturnsOriginalFilepathForReadonlyAccess() {
		$fileContents = 'asdfgh';
		$this->addToMount(array(
			'someDir' => array(
				'someFile' => $fileContents
			)
		));
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));
		$mockedFile = $this->getSimpleFileMock('/someDir/someFile');

		$filePath = $fixture->getFileForLocalProcessing($mockedFile, FALSE);

		$this->assertEquals($filePath, $this->getMountRootUrl() . 'someDir/someFile');
	}

	/**
	 * @test
	 */
	public function filesCanBeCopiedToATemporaryPath() {
		$fileContents = 'asdfgh';
		$this->addToMount(array(
			'someDir' => array(
				'someFile' => $fileContents
			)
		));
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

		$mockedFile = $this->getSimpleFileMock('/someDir/someFile');

		$filePath = $fixture->copyFileToTemporaryPath($mockedFile);

		$this->assertContains('/typo3temp/', $filePath);
		$this->assertEquals($fileContents, file_get_contents($filePath));
	}

	protected function prepareRealTestEnvironment() {
		$basedir = $this->createRealTestdir();
		$fixture = $this->createDriverFixture(array(
			'basePath' => $basedir
		));

		return array($basedir, $fixture);
	}

	/**
	 * @test
	 */
	public function permissionsAreCorrectlyRetrieved() {
		/** @var $fixture t3lib_file_Driver_LocalDriver */
		list($basedir, $fixture) = $this->prepareRealTestEnvironment();
		touch($basedir . '/someFile');
		chmod($basedir . '/someFile', 0700);
		touch($basedir . '/someForbiddenFile');
		chmod($basedir . '/someForbiddenFile', 0000);
		mkdir($basedir . '/someFolder');
		chmod($basedir . '/someFolder', 0700);
		mkdir($basedir . '/someForbiddenFolder');
		chmod($basedir . '/someForbiddenFolder', 0000);
		clearstatcache();

		$this->assertEquals(array('r' => TRUE, 'w' => TRUE), $fixture->getFilePermissions($this->getSimpleFileMock('/someFile')));
		$this->assertEquals(array('r' => FALSE, 'w' => FALSE), $fixture->getFilePermissions($this->getSimpleFileMock('/someForbiddenFile')));
		$this->assertEquals(array('r' => TRUE, 'w' => TRUE), $fixture->getFolderPermissions($this->getSimpleFolderMock('/someFolder')));
		$this->assertEquals(array('r' => FALSE, 'w' => FALSE), $fixture->getFolderPermissions($this->getSimpleFolderMock('/someForbiddenFolder')));
	}

	/**
	 * Dataprovider for getFilePermissionsReturnsCorrectPermissionsForFilesNotOwnedByCurrentUser test
	 *
	 * @return array group, filemode and expected result
	 */
	public function getFilePermissionsReturnsCorrectPermissionsForFilesNotOwnedByCurrentUser_dataProvider() {
		$data = array();

			// On some OS, the posix_* functions do not exits
		if (function_exists('posix_getgid')) {
			$data = array(
				'current group, readable/writable' => array(
					posix_getgid(),
					0060,
					array('r' => TRUE, 'w' => TRUE)
				),
				'current group, readable/not writable' => array(
					posix_getgid(),
					0040,
					array('r' => TRUE, 'w' => FALSE)
				),
				'current group, not readable/not writable' => array(
					posix_getgid(),
					0000,
					array('r' => FALSE, 'w' => FALSE)
				),
			);
		}

		$data = array_merge_recursive($data, array(
			'arbitrary group, readable/writable' => array(
				vfsStream::GROUP_USER_1,
				0006,
				array('r' => TRUE, 'w' => TRUE)
			),
			'arbitrary group, readable/not writable' => array(
				vfsStream::GROUP_USER_1,
				0664,
				array('r' => TRUE, 'w' => FALSE)
			),
			'arbitrary group, not readable/not writable' => array(
				vfsStream::GROUP_USER_1,
				0660,
				array('r' => FALSE, 'w' => FALSE)
			),
		));

		return $data;
	}

	/**
	 * @test
	 * @dataProvider getFilePermissionsReturnsCorrectPermissionsForFilesNotOwnedByCurrentUser_dataProvider
	 */
	public function getFilePermissionsReturnsCorrectPermissionsForFilesNotOwnedByCurrentUser($group, $permissions, $expectedResult) {
		$this->addToMount(array(
			'testfile' => 'asdfg'
		));
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));
		/** @var $fileObject vfsStreamContent */
		$fileObject = vfsStreamWrapper::getRoot()->getChild($this->mountDir)->getChild('testfile');
			// just use an "arbitrary" user here - it is only important that
		$fileObject->chown(vfsStream::OWNER_USER_1);
		$fileObject->chgrp($group);
		$fileObject->chmod($permissions);

		$this->assertEquals($expectedResult, $fixture->getFilePermissions($this->getSimpleFileMock('/testfile')));
	}

	/**
	 * @test
	 */
	public function isWithinRecognizesFilesWithinFolderAndInOtherFolders() {
		$mockedStorage = $this->getMock('t3lib_file_Storage', array(), array(), '', FALSE);

		$mockedFolder = new t3lib_file_Folder($mockedStorage, '/someFolder/', 'someFolder');
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		), $mockedStorage);

		$this->assertTrue($fixture->isWithin($mockedFolder, '/someFolder/test.jpg'));
		$this->assertTrue($fixture->isWithin($mockedFolder, '/someFolder/subFolder/test.jpg'));
		$this->assertFalse($fixture->isWithin($mockedFolder, '/someFolderWithALongName/test.jpg'));
	}

	/**
	 * @test
	 */
	public function isWithinAcceptsFileAndFolderObjectsAsContent() {
		$mockedStorage = $this->getMock('t3lib_file_Storage', array(), array(), '', FALSE);

		$mockedFolder = new t3lib_file_Folder($mockedStorage, '/someFolder/', 'someFolder');
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		), $mockedStorage);

		$mockedSubfolder = $this->getSimpleFolderMock('/someFolder/subfolder/');
		$mockedFile = $this->getSimpleFileMock('/someFolder/test.jpg');

		$this->assertTrue($fixture->isWithin($mockedFolder, $mockedFile));
		$this->assertTrue($fixture->isWithin($mockedFolder, $mockedSubfolder));
	}

	/**
	 * @test
	 */
	public function isWithinAlwaysReturnsFalseIfFolderIsWithinDifferentStorage() {
		$mockedStorage1 = $this->getMock('t3lib_file_Storage', array(), array(), '', FALSE);
		$mockedStorage2 = $this->getMock('t3lib_file_Storage', array(), array(), '', FALSE);

		$mockedFolder = new t3lib_file_Folder($mockedStorage1, '/someFolder/', 'someFolder');
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		), $mockedStorage2);

		$fileIdentifier = '/someFolder/test.jpg';
		$subfolderIdentifier = '/someFolder/subfolder/';
		$mockedFile = $this->getSimpleFileMock($fileIdentifier);
		$mockedSubfolder = $this->getSimpleFolderMock($subfolderIdentifier);

		$this->assertFalse($fixture->isWithin($mockedFolder, $mockedFile));
		$this->assertFalse($fixture->isWithin($mockedFolder, $fileIdentifier));
		$this->assertFalse($fixture->isWithin($mockedFolder, $mockedSubfolder));
		$this->assertFalse($fixture->isWithin($mockedFolder, $subfolderIdentifier));
	}

	/**********************************
	 * Copy/move file
	 **********************************/

	/**
	 * @param $identifier
	 * @param null|t3lib_file_Storage $storage
	 * @return t3lib_file_File
	 */
	protected function mockFileForCopyingAndMoving($identifier, t3lib_file_Storage $storage = NULL) {
		if (!$storage) {
			$storage = $this->getMock('t3lib_file_Storage', array(), array(), '', FALSE);
		}

		$fileObject = new t3lib_file_File(array('identifier' => $identifier, 'name' => basename($identifier), 'storage' => $storage));
		return $fileObject;
	}

	/**
	 * @param $identifier
	 * @param null|t3lib_file_Storage $storage
	 * @return t3lib_file_Folder
	 */
	protected function mockFolderForCopyingAndMoving($identifier, t3lib_file_Storage $storage = NULL) {
		if (!$storage) {
			$storage = $this->getMock('t3lib_file_Storage', array(), array(), '', FALSE);
		}

		$folderObject = new t3lib_file_Folder($storage, $identifier, basename($identifier), 0);
		return $folderObject;
	}

	/**
	 * Prepares a simple two-folder environment with /someFolder/ and /targetFolder/. /someFolder contains a file with random
	 * contents
	 *
	 * @return array $mockedFolder, $sourceFolder, $fileContents, $fixture
	 */
	protected function _prepareFolderEnvironmentForMoveTest() {
		$mockedFolder = $this->getSimpleFolderMock('/targetFolder/');
		$sourceFolder = $this->getSimpleFolderMock('/someFolder/');

		$fileContents = uniqid();
		$this->addToMount(array(
		                       'targetFolder' => array(),
		                       'someFolder' => array('file' => $fileContents)
		                  ));
		$fixture = $this->createDriverFixture(array(
		                                           'basePath' => $this->getMountRootUrl()
		                                      ));
		return array($mockedFolder, $sourceFolder, $fileContents, $fixture);
	}


	/**
	 * @test
	 */
	public function filesCanBeCopiedWithinStorage() {
		$fileContents = uniqid();
		$this->addToMount(array(
			'someFile' => $fileContents,
			'targetFolder' => array()
		));
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

		$storage = $this->getMock('t3lib_file_Storage', array(), array(), '', FALSE);
		$sourceFile = $this->mockFileForCopyingAndMoving('/someFile', $storage);
		$targetFolder = $this->mockFolderForCopyingAndMoving('/targetFolder/', $storage);

		$fixture->copyFileWithinStorage($sourceFile, $targetFolder, 'someFile');
		$this->assertFileEquals($this->getUrlInMount('/someFile'), $this->getUrlInMount('/targetFolder/someFile'));
	}

	/**
	 * @test
	 */
	public function filesCanBeMovedWithinStorage() {
		$mockedFolder = $this->getSimpleFolderMock('/targetFolder/');
		$storage = $this->getMock('t3lib_file_Storage', array(), array(), '', FALSE);
		$sourceFile = $this->mockFileForCopyingAndMoving('/someFile', $storage);

		$fileContents = uniqid();
		$this->addToMount(array(
			'targetFolder' => array(),
			'someFile' => $fileContents
		));
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

		$newIdentifier = $fixture->moveFileWithinStorage($sourceFile, $mockedFolder, 'file');
		$this->assertEquals($fileContents, file_get_contents($this->getUrlInMount('/targetFolder/file')));
		$this->assertFileNotExists($this->getUrlInMount('/someFile'));
		$this->assertEquals('/targetFolder/file', $newIdentifier);
	}

	/**
	 * @test
	 */
	public function fileMetadataIsChangedAfterMovingFile() {
		$mockedFolder = $this->getSimpleFolderMock('/targetFolder/');
		$storage = $this->getMock('t3lib_file_Storage', array(), array(), '', FALSE);
		$sourceFile = $this->mockFileForCopyingAndMoving('/someFile', $storage);

		$fileContents = uniqid();
		$this->addToMount(array(
			'targetFolder' => array(),
			'someFile' => $fileContents
		));
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

		$newIdentifier = $fixture->moveFileWithinStorage($sourceFile, $mockedFolder, 'file');
		$fileMetadata = $fixture->getFileInfoByIdentifier($newIdentifier);
		$this->assertEquals($newIdentifier, $fileMetadata['identifier']);
	}

	public function renamingFiles_dataProvider() {
		return array(
			'file in subfolder' => array(
				array(
					'targetFolder' => array('file' => ''),
				),
				'/targetFolder/file',
				'newFile',
				'/targetFolder/newFile'
			),
			'file in rootfolder' => array(
				array(
					'fileInRoot' => ''
				),
				'/fileInRoot',
				'newFile',
				'/newFile'
			)
		);
	}

	/**
	 * @test
	 * @dataProvider renamingFiles_dataProvider
	 */
	public function renamingFilesChangesFilenameOnDisk(array $filesystemStructure, $oldFileIdentifier, $newFileName, $expectedNewIdentifier) {
		$this->addToMount($filesystemStructure);
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

		$file = $this->getSimpleFileMock($oldFileIdentifier);

		$newIdentifier = $fixture->renameFile($file, $newFileName);
		$this->assertFalse($fixture->fileExists($oldFileIdentifier));
		$this->assertTrue($fixture->fileExists($newIdentifier));
		$this->assertEquals($expectedNewIdentifier, $newIdentifier);
	}

	/**
	 * @test
	 */
	public function renamingFilesFailsIfTargetFileExists() {
		$this->setExpectedException('t3lib_file_exception_ExistingTargetFileNameException', '', 1320291063);

		$this->addToMount(array(
			'targetFolder' => array('file' => '', 'newFile' => ''),
		));
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

		$file = $this->getSimpleFileMock('/targetFolder/file');

		$fixture->renameFile($file, 'newFile');
	}

	/**
	 * We use this data provider for testing move methods because there are some issues with the
	 *
	 * @return array
	 */
	public function renamingFolders_dataProvider() {
		return array(
			'folder in root folder' => array(
				array(
					'someFolder' => array(),
				),
				'/someFolder/',
				'newFolder',
				'/newFolder/'
			),
			'file in subfolder' => array(
				array(
					'subfolder' => array(
						'someFolder' => array()
					)
				),
				'/subfolder/someFolder/',
				'newFolder',
				'/subfolder/newFolder/'
			)
		);
	}

	/**
	 * @test
	 * @dataProvider renamingFolders_dataProvider
	 */
	public function renamingFoldersChangesFolderNameOnDisk(array $filesystemStructure, $oldFolderIdentifier, $newFolderName, $expectedNewIdentifier) {
		$this->addToMount($filesystemStructure);
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));
		$mockedFolder = $this->getSimpleFolderMock($oldFolderIdentifier);

		$mapping = $fixture->renameFolder($mockedFolder, $newFolderName);

		$this->assertFalse($fixture->folderExists($oldFolderIdentifier));
		$this->assertTrue($fixture->folderExists($expectedNewIdentifier));
		$this->assertEquals($expectedNewIdentifier, $mapping[$oldFolderIdentifier]);
	}

	/**
	 * @test
	 */
	public function renameFolderReturnsCorrectMappingInformationForAllFiles() {
		$fileContents = 'asdfg';
		$this->addToMount(array(
			'sourceFolder' => array(
				'subFolder' => array('file' => $fileContents),
				'file' => 'asdfg'
			)
		));
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

		$sourceFolder = $this->getSimpleFolderMock('/sourceFolder/');

		$mappingInformation = $fixture->renameFolder($sourceFolder, 'newFolder');

		$this->assertEquals('/newFolder/', $mappingInformation['/sourceFolder/']);
		$this->assertEquals('/newFolder/file', $mappingInformation['/sourceFolder/file']);
		$this->assertEquals('/newFolder/subFolder/file', $mappingInformation['/sourceFolder/subFolder/file']);
		$this->assertEquals('/newFolder/subFolder/', $mappingInformation['/sourceFolder/subFolder/']);
	}

	/**
	 * @test
	 */
	public function renameFolderRevertsRenamingIfFilenameMapCannotBeCreated() {
		$this->setExpectedException('RuntimeException', '', 1334160746);

		$this->addToMount(array(
			'sourceFolder' => array(
				'file' => 'asdfg'
			)
		));
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		), NULL, array('createIdentifierMap'));
		$fixture->expects($this->atLeastOnce())->method('createIdentifierMap')
			->will($this->throwException(new t3lib_file_exception_FileOperationErrorException()));

		$sourceFolder = $this->getSimpleFolderMock('/sourceFolder/');

		$fixture->renameFolder($sourceFolder, 'newFolder');

		$this->assertFileExists($this->getUrlInMount('/sourceFolder/file'));
	}

	/**
	 * @test
	 */
	public function isFolderEmptyReturnsTrueForEmptyFolder() {
			// This also prepares the next few tests, so add more info than required for this test
		$this->addToMount(array(
			'emptyFolder' => array(),
		));
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));
		$mockedFolder = $this->getSimpleFolderMock('/emptyFolder/');

		$this->assertTrue($fixture->isFolderEmpty($mockedFolder));

		return $fixture;
	}

	/**
	 * @test
	 */
	public function isFolderEmptyReturnsFalseIfFolderHasFile() {
		$this->addToMount(array(
			'folderWithFile' => array(
				'someFile' => ''
			),
		));
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));
		$mockedFolder = $this->getSimpleFolderMock('/folderWithFile/');

		$this->assertFalse($fixture->isFolderEmpty($mockedFolder));
	}

	/**
	 * @test
	 */
	public function isFolderEmptyReturnsFalseIfFolderHasSubfolder() {
		$this->addToMount(array(
			'folderWithSubfolder' => array(
				'someFolder' => array()
			)
		));
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));
		$mockedFolder = $this->getSimpleFolderMock('/folderWithSubfolder/');

		$this->assertFalse($fixture->isFolderEmpty($mockedFolder));
	}


	/**********************************
	 * Copy/move folder
	 **********************************/

	/**
	 * @test
	 */
	public function foldersCanBeMovedWithinStorage() {
		/** @var t3lib_file_Driver_LocalDriver $fixture */
		list($mockedFolder, $sourceFolder, $fileContents, $fixture) = $this->_prepareFolderEnvironmentForMoveTest();

		$fixture->moveFolderWithinStorage($sourceFolder, $mockedFolder, 'someFolder');
		$this->assertTrue(file_exists($this->getUrlInMount('/targetFolder/someFolder/')));
		$this->assertEquals($fileContents, file_get_contents($this->getUrlInMount('/targetFolder/someFolder/file')));
		$this->assertFileNotExists($this->getUrlInMount('/someFile'));
	}

	/**
	 * @test
	 */
	public function moveFolderWithinStorageReturnsCorrectMappingInformationForAllFiles() {
		$fileContents = 'asdfg';
		$this->addToMount(array(
			'targetFolder' => array(),
			'sourceFolder' => array(
				'subFolder' => array('file' => $fileContents),
				'file' => 'asdfg'
			)
		));
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));

		$sourceFolder = $this->getSimpleFolderMock('/sourceFolder/');
		$targetFolder = $this->getSimpleFolderMock('/targetFolder/');

		$mappingInformation = $fixture->moveFolderWithinStorage($sourceFolder, $targetFolder, 'sourceFolder');

		$this->assertEquals('/targetFolder/sourceFolder/file', $mappingInformation['/sourceFolder/file']);
		$this->assertEquals('/targetFolder/sourceFolder/subFolder/file', $mappingInformation['/sourceFolder/subFolder/file']);
		$this->assertEquals('/targetFolder/sourceFolder/subFolder/', $mappingInformation['/sourceFolder/subFolder/']);
	}

	/**
	 * @test
	 */
	public function folderCanBeRenamedWhenMoving() {
		/** @var t3lib_file_Driver_LocalDriver $fixture */
		list($mockedFolder, $sourceFolder, $fileContents, $fixture) = $this->_prepareFolderEnvironmentForMoveTest();

		$fixture->moveFolderWithinStorage($sourceFolder, $mockedFolder, 'newFolder');
		$this->assertTrue(file_exists($this->getUrlInMount('/targetFolder/newFolder/')));
	}


	protected function _setupFolderForCopyTest() {
		$fileContents1 = uniqid();
		$fileContents2 = uniqid();
		$this->addToMount(array(
			'targetFolder' => array(),
			'sourceFolder' => array(
				'subFolder' => array('file' => $fileContents1),
				'file' => $fileContents2
			)
		));
		$fixture = $this->createDriverFixture(array(
			'basePath' => $this->getMountRootUrl()
		));
		return $fixture;
	}

	/**
	 * @test
	 * @see _setupFolderForCopyTest
	 */
	public function foldersCanBeCopiedWithinSameStorage() {
		$fixture = $this->_setupFolderForCopyTest();

		$sourceFolder = $this->getSimpleFolderMock('/sourceFolder/');
		$targetFolder = $this->getSimpleFolderMock('/targetFolder/');

		$fixture->copyFolderWithinStorage($sourceFolder, $targetFolder, 'sourceFolder');

		$this->assertTrue($fixture->folderExists('/targetFolder/sourceFolder/'));
		$this->assertTrue($fixture->fileExists('/targetFolder/sourceFolder/file'));
		$this->assertTrue($fixture->folderExists('/targetFolder/sourceFolder/subFolder/'));
		$this->assertTrue($fixture->fileExists('/targetFolder/sourceFolder/subFolder/file'));
	}

	/**
	 * @test
	 * @see _setupFolderForCopyTest
	 */
	public function folderNameCanBeChangedWhileCopying() {
		$fixture = $this->_setupFolderForCopyTest();

		$sourceFolder = $this->getSimpleFolderMock('/sourceFolder/');
		$targetFolder = $this->getSimpleFolderMock('/targetFolder/');

		$fixture->copyFolderWithinStorage($sourceFolder, $targetFolder, 'newFolder');

		$this->assertTrue($fixture->folderExists('/targetFolder/newFolder/'));
		$this->assertTrue($fixture->fileExists('/targetFolder/newFolder/file'));
		$this->assertFalse($fixture->folderExists('/targetFolder/sourceFolder/'));
	}
}
?>
