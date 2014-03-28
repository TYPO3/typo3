<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource\Driver;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use \org\bovigo\vfs\vfsStream;
use \org\bovigo\vfs\vfsStreamWrapper;

/**
 * Testcase for the local storage driver class of the TYPO3 VFS
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 */
class LocalDriverTest extends \TYPO3\CMS\Core\Tests\Unit\Resource\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Resource\Driver\LocalDriver
	 */
	protected $localDriver = NULL;

	/**
	 * @var array A backup of registered singleton instances
	 */
	protected $singletonInstances = array();

	/**
	 * @var array
	 */
	protected $testDirs = array();

	/**
	 * @var string
	 */
	protected $iso88591GreaterThan127 = '';

	/**
	 * @var string
	 */
	protected $utf8Latin1Supplement = '';

	/**
	 * @var string
	 */
	protected $utf8Latin1ExtendedA = '';

	/**
	 * Tear down
	 */
	public function tearDown() {
		foreach ($this->testDirs as $dir) {
			chmod($dir, 0777);
			\TYPO3\CMS\Core\Utility\GeneralUtility::rmdir($dir, TRUE);
		}
		parent::tearDown();
	}

	/**
	 * Creates a "real" directory for doing tests. This is necessary because some file system properties (e.g. permissions)
	 * cannot be reflected by vfsStream, and some methods (like touch()) don't work there either.
	 *
	 * Created directories are automatically destroyed during tearDown()
	 *
	 * @return string
	 */
	protected function createRealTestdir() {
		$basedir = PATH_site . 'typo3temp/' . uniqid('fal-test-');
		mkdir($basedir);
		$this->testDirs[] = $basedir;
		return $basedir;
	}

	/**
	 * Create a "real" directory together with a driverFixture configured
	 * for this directory.
	 *
	 * @return array With path to base directory and driver fixture
	 */
	protected function prepareRealTestEnvironment() {
		$basedir = $this->createRealTestdir();
		$fixture = $this->createDriverFixture(array(
			'basePath' => $basedir
		));
		return array($basedir, $fixture);
	}

	/**
	 * Creates a driver fixture object, optionally using a given mount object.
	 *
	 * IMPORTANT: Call this only after setting up the virtual file system (with the addTo* methods)!
	 *
	 * @param array $driverConfiguration
	 * @param array $mockedDriverMethods
	 * @return \TYPO3\CMS\Core\Resource\Driver\LocalDriver
	 */
	protected function createDriverFixture($driverConfiguration = array(), $mockedDriverMethods = array()) {
			// it's important to do that here, so vfsContents could have been set before
		if (!isset($driverConfiguration['basePath'])) {
			$this->initializeVfs();
			$driverConfiguration['basePath'] = $this->getMountRootUrl();
		}
		/** @var \TYPO3\CMS\Core\Resource\Driver\LocalDriver $driver */
		$mockedDriverMethods[] = 'isPathValid';
		$driver = $this->getAccessibleMock('TYPO3\\CMS\\Core\\Resource\\Driver\\LocalDriver', $mockedDriverMethods, array($driverConfiguration));
		$driver->expects($this->any())
			->method('isPathValid')
			->will(
				$this->returnValue(TRUE)
			);

		$driver->setStorageUid(5);
		$driver->processConfiguration();
		$driver->initialize();
		return $driver;
	}

	/**
	 * @test
	 */
	public function createFolderRecursiveSanitizesFilename() {
		/** @var \TYPO3\CMS\Core\Resource\Driver\LocalDriver|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $driver */
		$driver = $this->createDriverFixture(array(), array('sanitizeFilename'));
		$driver->expects($this->exactly(2))
			->method('sanitizeFileName')
			->will(
				$this->returnValue('sanitized')
			);
		$driver->createFolder('newFolder/andSubfolder', '/', TRUE);
		$this->assertFileExists($this->getUrlInMount('/sanitized/sanitized/'));
	}

	/**
	 * @test
	 */
	public function determineBaseUrlUrlEncodesUriParts() {
		/** @var \TYPO3\CMS\Core\Resource\Driver\LocalDriver|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $driver */
		$driver = $this->getAccessibleMock('TYPO3\\CMS\\Core\\Resource\\Driver\\LocalDriver', array('hasCapability'), array(), '', FALSE);
		$driver->expects($this->once())
			->method('hasCapability')
			->with(\TYPO3\CMS\Core\Resource\ResourceStorage::CAPABILITY_PUBLIC)
			->will(
				$this->returnValue(TRUE)
			);
		$driver->_set('absoluteBasePath', PATH_site . 'un encö/ded %path/');
		$driver->_call('determineBaseUrl');
		$baseUri = $driver->_get('baseUri');
		$this->assertEquals(rawurlencode('un encö') . '/' . rawurlencode('ded %path') . '/', $baseUri);
	}

	/**
	 * @test
	 */
	public function getDefaultFolderReturnsFolderForUserUploadPath() {
		$fixture = $this->createDriverFixture();
		$folderIdentifier = $fixture->getDefaultFolder();
		$this->assertEquals('/user_upload/', $folderIdentifier);
	}

	/**
	 * @test
	 */
	public function defaultLevelFolderFolderIsCreatedIfItDoesntExist() {
		$fixture = $this->createDriverFixture();
		$this->assertFileExists($this->getUrlInMount($fixture->getDefaultFolder()));
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
		$fixture = $this->createDriverFixture();
		$folder = $fixture->getFolderInFolder('someSubdir', '/someDir/');
		$this->assertEquals('/someDir/someSubdir/', $folder);
	}

	/**
	 * @test
	 */
	public function createFolderCreatesFolderOnDisk() {
		$this->addToMount(array('some' => array('folder' => array())));
		$fixture = $this->createDriverFixture();
		$fixture->createFolder('path', '/some/folder/');
		$this->assertFileExists($this->getUrlInMount('/some/folder/'));
		$this->assertFileExists($this->getUrlInMount('/some/folder/path'));
	}

	/**
	 * @test
	 */
	public function createFolderReturnsFolderObject() {
		$this->addToMount(array('some' => array('folder' => array())));
		$fixture = $this->createDriverFixture();
		$createdFolder = $fixture->createFolder('path', '/some/folder/');
		$this->assertEquals('/some/folder/path/', $createdFolder);
	}

	public static function createFolderSanitizesFolderNameBeforeCreationDataProvider() {
		return array(
			'folder name with NULL character' => array(
				'some' . chr(0) . 'Folder',
				'some_Folder'
			),
			'folder name with directory part' => array(
				'../someFolder',
				'.._someFolder'
			)
		);
	}

	/**
	 * @test
	 * @dataProvider createFolderSanitizesFolderNameBeforeCreationDataProvider
	 */
	public function createFolderSanitizesFolderNameBeforeCreation($newFolderName, $expectedFolderName) {
		$this->addToMount(array('some' => array('folder' => array())));
		$fixture = $this->createDriverFixture();
		$fixture->createFolder($newFolderName, '/some/folder/');
		$this->assertFileExists($this->getUrlInMount('/some/folder/' . $expectedFolderName));
	}

	/**
	 * @test
	 */
	public function basePathIsNormalizedWithTrailingSlash() {
		$fixture = $this->createDriverFixture();
		$this->assertEquals('/', substr($fixture->_call('getAbsoluteBasePath'), -1));
	}

	/**
	 * @test
	 */
	public function noSecondSlashIsAddedIfBasePathAlreadyHasTrailingSlash() {
		$fixture = $this->createDriverFixture();
		$this->assertNotEquals('/', substr($fixture->_call('getAbsoluteBasePath'), -2, 1));
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
		$fixture = $this->createDriverFixture();
		$path = $fixture->_call('getAbsolutePath', '/someFolder/file1.ext');
		$this->assertTrue(file_exists($path));
		$this->assertEquals($this->getUrlInMount('/someFolder/file1.ext'), $path);
	}

	/**
	 * @test
	 */
	public function addFileMovesFileToCorrectLocation() {
		$this->addToMount(array('targetFolder' => array()));
		$this->addToVfs(array(
			'sourceFolder' => array(
				'file' => 'asdf'
			)
		));
		$fixture = $this->createDriverFixture(
			array(),
			array('getMimeTypeOfFile')
		);
		$this->assertTrue(file_exists($this->getUrl('sourceFolder/file')));
		$fixture->addFile($this->getUrl('sourceFolder/file'), '/targetFolder/', 'file');
		$this->assertTrue(file_exists($this->getUrlInMount('/targetFolder/file')));
	}

	/**
	 * @test
	 */
	public function addFileUsesFilenameIfGiven() {
		$this->addToMount(array('targetFolder' => array()));
		$this->addToVfs(array(
			'sourceFolder' => array(
				'file' => 'asdf'
			)
		));
		$fixture = $this->createDriverFixture(
			array(),
			array('getMimeTypeOfFile')
		);
		$this->assertTrue(file_exists($this->getUrl('sourceFolder/file')));
		$fixture->addFile($this->getUrl('sourceFolder/file'), '/targetFolder/', 'targetFile');
		$this->assertTrue(file_exists($this->getUrlInMount('/targetFolder/targetFile')));
	}

	/**
	 * @test
	 */
	public function addFileFailsIfFileIsInDriverStorage() {
		$this->setExpectedException('InvalidArgumentException', '', 1314778269);
		$this->addToMount(array(
			'targetFolder' => array(
				'file' => 'asdf'
			)
		));
		$fixture = $this->createDriverFixture();
		$fixture->addFile($this->getUrlInMount('/targetFolder/file'), '/targetFolder/', 'file');
	}

	/**
	 * @test
	 */
	public function addFileReturnsFileIdentifier() {
		$this->addToMount(array('targetFolder' => array()));
		$this->addToVfs(array(
			'sourceFolder' => array(
				'file' => 'asdf'
			)
		));
		$fixture = $this->createDriverFixture(
			array(),
			array('getMimeTypeOfFile')
		);
		$this->assertTrue(file_exists($this->getUrl('sourceFolder/file')));
		$fileIdentifier = $fixture->addFile($this->getUrl('sourceFolder/file'), '/targetFolder/', 'file');
		$this->assertEquals('file', basename($fileIdentifier));
		$this->assertEquals('/targetFolder/file', $fileIdentifier);
	}

	/**
	 * @test
	 */
	public function existenceChecksWorkForFilesAndFolders() {
		$this->addToMount(array(
			'file' => 'asdf',
			'folder' => array()
		));
		$fixture = $this->createDriverFixture();
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
		$this->addToMount(array(
			'subfolder' => array(
				'file' => 'asdf',
				'folder' => array()
			)
		));
		$fixture = $this->createDriverFixture();
		$this->assertTrue($fixture->fileExistsInFolder('file', '/subfolder/'));
		$this->assertTrue($fixture->folderExistsInFolder('folder', '/subfolder/'));
		$this->assertFalse($fixture->fileExistsInFolder('nonexistingFile', '/subfolder/'));
		$this->assertFalse($fixture->folderExistsInFolder('nonexistingFolder', '/subfolder/'));
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
			'baseUri' => $baseUri
		));
		$this->assertEquals($baseUri . '/file.ext', $fixture->getPublicUrl('/file.ext'));
		$this->assertEquals($baseUri . '/subfolder/file2.ext', $fixture->getPublicUrl('/subfolder/file2.ext'));
	}

	/**
	 * Data provider for getPublicUrlReturnsValidUrlContainingSpecialCharacters().
	 *
	 * @return array
	 */
	public function getPublicUrlReturnsValidUrlContainingSpecialCharacters_dataProvider() {
		return array(
			array('/single file with some special chars äüö!.txt'),
			array('/on subfolder/with special chars äüö!.ext'),
			array('/who names a file like !"§$%&()=?*+~"#\'´`<>-.ext'),
			array('no leading slash !"§$%&()=?*+~#\'"´`"<>-.txt')
		);
	}

	/**
	 * @test
	 * @dataProvider getPublicUrlReturnsValidUrlContainingSpecialCharacters_dataProvider
	 */
	public function getPublicUrlReturnsValidUrlContainingSpecialCharacters($fileIdentifier) {
		$baseUri = 'http://example.org/foobar/' . uniqid();
		$fixture = $this->createDriverFixture(array(
			'baseUri' => $baseUri
		));
		$publicUrl = $fixture->getPublicUrl($fileIdentifier);
		$this->assertTrue(GeneralUtility::isValidUrl($publicUrl), 'getPublicUrl did not return a valid URL:' . $publicUrl);
	}

	/**
	 * @test
	 */
	public function fileContentsCanBeWrittenAndRead() {
		$fileContents = 'asdf';
		$this->addToMount(array(
			'file.ext' => $fileContents
		));
		$fixture = $this->createDriverFixture();
		$this->assertEquals($fileContents, $fixture->getFileContents('/file.ext'), 'File contents could not be read');
		$newFileContents = 'asdfgh';
		$fixture->setFileContents('/file.ext', $newFileContents);
		$this->assertEquals($newFileContents, $fixture->getFileContents('/file.ext'), 'New file contents could not be read.');
	}

	/**
	 * @test
	 */
	public function setFileContentsReturnsNumberOfBytesWrittenToFile() {
		$fileContents = 'asdf';
		$this->addToMount(array(
			'file.ext' => $fileContents
		));
		$fixture = $this->createDriverFixture();
		$newFileContents = 'asdfgh';
		$bytesWritten = $fixture->setFileContents('/file.ext', $newFileContents);
		$this->assertEquals(strlen($newFileContents), $bytesWritten);
	}

	/**
	 * @test
	 * @see http://phpmagazin.de/vfsStream-1.1.0-nutzt-PHP-5.4-M%C3%B6glichkeiten-064406.html
	 */
	public function newFilesCanBeCreated() {
		if (version_compare(PHP_VERSION, '5.4.0', '<')) {
			$this->markTestSkipped('touch() does not work with vfsStream in PHP 5.3 and below.');
		}
		$fixture = $this->createDriverFixture();
		$fixture->createFile('testfile.txt', '/');
		$this->assertTrue($fixture->fileExists('/testfile.txt'));
	}

	/**
	 * @test
	 * @see http://phpmagazin.de/vfsStream-1.1.0-nutzt-PHP-5.4-M%C3%B6glichkeiten-064406.html
	 */
	public function createdFilesAreEmpty() {
		if (version_compare(PHP_VERSION, '5.4.0', '<')) {
			$this->markTestSkipped('touch() does not work with vfsStream in PHP 5.3 and below.');
		}
		$fixture = $this->createDriverFixture();
		$fixture->createFile('testfile.txt', '/');
		$this->assertTrue($fixture->fileExists('/testfile.txt'));
		$fileData = $fixture->getFileContents('/testfile.txt');
		$this->assertEquals(0, strlen($fileData));
	}

	/**
	 * @test
	 */
	public function createFileFixesPermissionsOnCreatedFile() {
		if (TYPO3_OS == 'WIN') {
			$this->markTestSkipped('createdFilesHaveCorrectRights() tests not available on Windows');
		}

		// No one will use this as his default file create mask so we hopefully don't get any false positives
		$testpattern = '0646';
		$GLOBALS['TYPO3_CONF_VARS']['BE']['fileCreateMask'] = $testpattern;

		$this->addToMount(
			array(
				'someDir' => array()
			)
		);
		/** @var $fixture \TYPO3\CMS\Core\Resource\Driver\LocalDriver */
		list($basedir, $fixture) = $this->prepareRealTestEnvironment();
		mkdir($basedir . '/someDir');
		$fixture->createFile('testfile.txt', '/someDir');
		$this->assertEquals($testpattern, decoct(fileperms($basedir . '/someDir/testfile.txt') & 0777));
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
		$fixture = $this->createDriverFixture(
			array(),
			array('getMimeTypeOfFile')
		);
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
		$fixture = $this->createDriverFixture();
		$fixture->getFileInfoByIdentifier('/some/file/at/a/random/path');
	}

	/**
	 * @test
	 */
	public function getFilesInFolderReturnsEmptyArrayForEmptyDirectory() {
		$fixture = $this->createDriverFixture();
		$fileList = $fixture->getFilesInFolder('/');
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
		$fixture = $this->createDriverFixture(
			array(),
				// Mocked because finfo() can not deal with vfs streams and throws warnings
			array('getMimeTypeOfFile')
		);
		$fileList = $fixture->getFilesInFolder('/');
		$this->assertEquals(array('/file1', '/file2'), array_keys($fileList));
	}

	/**
	 * @test
	 */
	public function getFileListReturnsAllFilesInSubdirectoryIfRecursiveParameterIsSet() {
		$dirStructure = array(
			'aDir' => array(
				'file3' => 'asdfgh',
				'subdir' => array(
					'file4' => 'asklfjklasjkl'
				)
			),
			'file1' => 'asdfg',
			'file2' => 'fdsa'
		);
		$this->addToMount($dirStructure);
		$fixture = $this->createDriverFixture(
			array(),
				// Mocked because finfo() can not deal with vfs streams and throws warnings
			array('getMimeTypeOfFile')
		);
		$fileList = $fixture->getFilesInFolder('/', 0, 0, TRUE);
		$this->assertEquals(array('/aDir/subdir/file4', '/aDir/file3', '/file1', '/file2'), array_keys($fileList));
	}

	/**
	 * @test
	 */
	public function getFileListFailsIfDirectoryDoesNotExist() {
		$this->setExpectedException('InvalidArgumentException', '', 1314349666);
		$this->addToMount(array('somefile' => ''));
		$fixture = $this->createDriverFixture();
		$fixture->getFilesInFolder('somedir/');
	}

	/**
	 * @test
	 */
	public function getFileInFolderCallsConfiguredCallbackFunctionWithGivenItemName() {
		$dirStructure = array(
			'file2' => 'fdsa'
		);
		// register static callback to self
		$callback = array(
			array(
				get_class($this),
				'callbackStaticTestFunction'
			)
		);
		$this->addToMount($dirStructure);
		$fixture = $this->createDriverFixture();
		// the callback function will throw an exception used to check if it was called with correct $itemName
		$this->setExpectedException('InvalidArgumentException', '$itemName', 1336159604);
		$fixture->getFilesInFolder('/', 0, 0, FALSE, $callback);
	}

	/**
	 * Static callback function used to test if the filter callbacks work
	 * As it is static we are using an exception to test if it is really called and works
	 *
	 * @static
	 * @throws \InvalidArgumentException
	 * @see getFileListCallsConfiguredCallbackFunction
	 */
	static public function callbackStaticTestFunction() {
		list($itemName) = func_get_args();
		if ($itemName === 'file2') {
			throw new \InvalidArgumentException('$itemName', 1336159604);
		}
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
		$fixture = $this->createDriverFixture(
			array(),
				// Mocked because finfo() can not deal with vfs streams and throws warnings
			array('getMimeTypeOfFile')
		);
		$filterCallbacks = array(
			array(
				'TYPO3\CMS\Core\Tests\Unit\Resource\Driver\Fixtures\LocalDriverFilenameFilter',
				'filterFilename',
			),
		);
		$fileList = $fixture->getFilesInFolder('/', 0, 0, FALSE, $filterCallbacks);
		$this->assertNotContains('/fileA', array_keys($fileList));
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
		$fixture = $this->createDriverFixture();
		$fileList = $fixture->getFoldersInFolder('/');
		$this->assertEquals(array('/dir1/', '/dir2/'), array_keys($fileList));
	}

	/**
	 * @test
	 */
	public function getFolderListReturnsHiddenFoldersByDefault() {
		$dirStructure = array(
			'.someHiddenDir' => array(),
			'aDir' => array(),
			'file1' => ''
		);
		$this->addToMount($dirStructure);
		$fixture = $this->createDriverFixture();

		$fileList = $fixture->getFoldersInFolder('/');

		$this->assertEquals(array('/.someHiddenDir/', '/aDir/'), array_keys($fileList));
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
		$fixture = $this->createDriverFixture();
		$fileList = $fixture->getFoldersInFolder('/');
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
		$fixture = $this->createDriverFixture();
		$filterCallbacks = array(
			array(
				'TYPO3\CMS\Core\Tests\Unit\Resource\Driver\Fixtures\LocalDriverFilenameFilter',
				'filterFilename',
			),
		);
		$folderList = $fixture->getFoldersInFolder('/', 0, 0, $filterCallbacks);
		$this->assertNotContains('folderA', array_keys($folderList));
	}

	/**
	 * @test
	 */
	public function getFolderListFailsIfDirectoryDoesNotExist() {
		$this->setExpectedException('InvalidArgumentException', '', 1314349666);
		$fixture = $this->createDriverFixture();
		vfsStream::create(array($this->basedir => array('somefile' => '')));
		$fixture->getFoldersInFolder('somedir/');
	}

	/**
	 * @test
	 */
	public function hashReturnsCorrectHashes() {
		$contents = '68b329da9893e34099c7d8ad5cb9c940';
		$expectedMd5Hash = '8c67dbaf0ba22f2e7fbc26413b86051b';
		$expectedSha1Hash = 'a60cd808ba7a0bcfa37fa7f3fb5998e1b8dbcd9d';
		$this->addToMount(array('hashFile' => $contents));
		$fixture = $this->createDriverFixture();
		$this->assertEquals($expectedSha1Hash, $fixture->hash('/hashFile', 'sha1'));
		$this->assertEquals($expectedMd5Hash, $fixture->hash('/hashFile', 'md5'));
	}

	/**
	 * @test
	 */
	public function hashingWithUnsupportedAlgorithmFails() {
		$this->setExpectedException('InvalidArgumentException', '', 1304964032);
		$fixture = $this->createDriverFixture();
		$fixture->hash('/hashFile', uniqid());
	}

	/**
	 * @test
	 * @covers TYPO3\CMS\Core\Resource\Driver\LocalDriver::getFileForLocalProcessing
	 */
	public function getFileForLocalProcessingCreatesCopyOfFileByDefault() {
		$fileContents = 'asdfgh';
		$this->addToMount(array(
			'someDir' => array(
				'someFile' => $fileContents
			)
		));
		$fixture = $this->createDriverFixture(array(), array('copyFileToTemporaryPath'));
		$fixture->expects($this->once())->method('copyFileToTemporaryPath');
		$fixture->getFileForLocalProcessing('/someDir/someFile');
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
		$fixture = $this->createDriverFixture();
		$filePath = $fixture->getFileForLocalProcessing('/someDir/someFile', FALSE);
		$this->assertEquals($filePath, $this->getUrlInMount('someDir/someFile'));
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
		$fixture = $this->createDriverFixture();
		$filePath = GeneralUtility::fixWindowsFilePath($fixture->_call('copyFileToTemporaryPath', '/someDir/someFile'));
		$this->assertContains('/typo3temp/', $filePath);
		$this->assertEquals($fileContents, file_get_contents($filePath));
	}

	/**
	 * @test
	 */
	public function permissionsAreCorrectlyRetrievedForAllowedFile() {
		/** @var $fixture \TYPO3\CMS\Core\Resource\Driver\LocalDriver */
		list($basedir, $fixture) = $this->prepareRealTestEnvironment();
		touch($basedir . '/someFile');
		chmod($basedir . '/someFile', 448);
		clearstatcache();
		$this->assertEquals(array('r' => TRUE, 'w' => TRUE), $fixture->getPermissions('/someFile'));
	}

	/**
	 * @test
	 */
	public function permissionsAreCorrectlyRetrievedForForbiddenFile() {
		if (function_exists('posix_getegid') && posix_getegid() === 0) {
			$this->markTestSkipped('Test skipped if run on linux as root');
		} elseif (TYPO3_OS === 'WIN') {
			$this->markTestSkipped('Test skipped if run on Windows system');
		}
		/** @var $fixture \TYPO3\CMS\Core\Resource\Driver\LocalDriver */
		list($basedir, $fixture) = $this->prepareRealTestEnvironment();
		touch($basedir . '/someForbiddenFile');
		chmod($basedir . '/someForbiddenFile', 0);
		clearstatcache();
		$this->assertEquals(array('r' => FALSE, 'w' => FALSE), $fixture->getPermissions('/someForbiddenFile'));
	}

	/**
	 * @test
	 */
	public function permissionsAreCorrectlyRetrievedForAllowedFolder() {
		/** @var $fixture \TYPO3\CMS\Core\Resource\Driver\LocalDriver */
		list($basedir, $fixture) = $this->prepareRealTestEnvironment();
		mkdir($basedir . '/someFolder');
		chmod($basedir . '/someFolder', 448);
		clearstatcache();
		$this->assertEquals(array('r' => TRUE, 'w' => TRUE), $fixture->getPermissions('/someFolder'));
	}

	/**
	 * @test
	 */
	public function permissionsAreCorrectlyRetrievedForForbiddenFolder() {
		if (function_exists('posix_getegid') && posix_getegid() === 0) {
			$this->markTestSkipped('Test skipped if run on linux as root');
		} elseif (TYPO3_OS === 'WIN') {
			$this->markTestSkipped('Test skipped if run on Windows system');
		}
		/** @var $fixture \TYPO3\CMS\Core\Resource\Driver\LocalDriver */
		list($basedir, $fixture) = $this->prepareRealTestEnvironment();
		mkdir($basedir . '/someForbiddenFolder');
		chmod($basedir . '/someForbiddenFolder', 0);
		clearstatcache();
		$result = $fixture->getPermissions('/someForbiddenFolder');
		// Change permissions back to writable, so the sub-folder can be removed in tearDown
		chmod($basedir . '/someForbiddenFolder', 0777);
		$this->assertEquals(array('r' => FALSE, 'w' => FALSE), $result);
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
					48,
					array('r' => TRUE, 'w' => TRUE)
				),
				'current group, readable/not writable' => array(
					posix_getgid(),
					32,
					array('r' => TRUE, 'w' => FALSE)
				),
				'current group, not readable/not writable' => array(
					posix_getgid(),
					0,
					array('r' => FALSE, 'w' => FALSE)
				)
			);
		}
		$data = array_merge_recursive($data, array(
			'arbitrary group, readable/writable' => array(
				vfsStream::GROUP_USER_1,
				6,
				array('r' => TRUE, 'w' => TRUE)
			),
			'arbitrary group, readable/not writable' => array(
				vfsStream::GROUP_USER_1,
				436,
				array('r' => TRUE, 'w' => FALSE)
			),
			'arbitrary group, not readable/not writable' => array(
				vfsStream::GROUP_USER_1,
				432,
				array('r' => FALSE, 'w' => FALSE)
			)
		));
		return $data;
	}

	/**
	 * @test
	 * @dataProvider getFilePermissionsReturnsCorrectPermissionsForFilesNotOwnedByCurrentUser_dataProvider
	 */
	public function getFilePermissionsReturnsCorrectPermissionsForFilesNotOwnedByCurrentUser($group, $permissions, $expectedResult) {
		if (TYPO3_OS === 'WIN') {
			$this->markTestSkipped('Test skipped if run on Windows system');
		}
		$this->addToMount(array(
			'testfile' => 'asdfg'
		));
		$fixture = $this->createDriverFixture();
		/** @var $fileObject vfsStreamContent */
		$fileObject = vfsStreamWrapper::getRoot()->getChild($this->mountDir)->getChild('testfile');
		// just use an "arbitrary" user here - it is only important that
		$fileObject->chown(vfsStream::OWNER_USER_1);
		$fileObject->chgrp($group);
		$fileObject->chmod($permissions);
		$this->assertEquals($expectedResult, $fixture->getPermissions('/testfile'));
	}

	/**
	 * @test
	 */
	public function isWithinRecognizesFilesWithinFolderAndInOtherFolders() {
		$fixture = $this->createDriverFixture();
		$this->assertTrue($fixture->isWithin('/someFolder/', '/someFolder/test.jpg'));
		$this->assertTrue($fixture->isWithin('/someFolder/', '/someFolder/subFolder/test.jpg'));
		$this->assertFalse($fixture->isWithin('/someFolder/', '/someFolderWithALongName/test.jpg'));
	}

	/**
	 * @test
	 */
	public function isWithinAcceptsFileAndFolderObjectsAsContent() {
		$fixture = $this->createDriverFixture();
		$this->assertTrue($fixture->isWithin('/someFolder/', '/someFolder/test.jpg'));
		$this->assertTrue($fixture->isWithin('/someFolder/', '/someFolder/subfolder/'));
	}

	/**********************************
	 * Copy/move file
	 **********************************/

	/**
	 * @test
	 */
	public function filesCanBeCopiedWithinStorage() {
		$fileContents = uniqid();
		$this->addToMount(array(
			'someFile' => $fileContents,
			'targetFolder' => array()
		));
		$fixture = $this->createDriverFixture(
			array(),
			array('getMimeTypeOfFile')
		);
		$fixture->copyFileWithinStorage('/someFile', '/targetFolder/', 'someFile');
		$this->assertFileEquals($this->getUrlInMount('/someFile'), $this->getUrlInMount('/targetFolder/someFile'));
	}

	/**
	 * @test
	 */
	public function filesCanBeMovedWithinStorage() {
		$fileContents = uniqid();
		$this->addToMount(array(
			'targetFolder' => array(),
			'someFile' => $fileContents
		));
		$fixture = $this->createDriverFixture();
		$newIdentifier = $fixture->moveFileWithinStorage('/someFile', '/targetFolder/', 'file');
		$this->assertEquals($fileContents, file_get_contents($this->getUrlInMount('/targetFolder/file')));
		$this->assertFileNotExists($this->getUrlInMount('/someFile'));
		$this->assertEquals('/targetFolder/file', $newIdentifier);
	}

	/**
	 * @test
	 */
	public function fileMetadataIsChangedAfterMovingFile() {
		$fileContents = uniqid();
		$this->addToMount(array(
			'targetFolder' => array(),
			'someFile' => $fileContents
		));
		$fixture = $this->createDriverFixture(
			array(),
				// Mocked because finfo() can not deal with vfs streams and throws warnings
			array('getMimeTypeOfFile')
		);
		$newIdentifier = $fixture->moveFileWithinStorage('/someFile', '/targetFolder/', 'file');
		$fileMetadata = $fixture->getFileInfoByIdentifier($newIdentifier);
		$this->assertEquals($newIdentifier, $fileMetadata['identifier']);
	}

	public function renamingFiles_dataProvider() {
		return array(
			'file in subfolder' => array(
				array(
					'targetFolder' => array('file' => '')
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
		$fixture = $this->createDriverFixture();
		$newIdentifier = $fixture->renameFile($oldFileIdentifier, $newFileName);
		$this->assertFalse($fixture->fileExists($oldFileIdentifier));
		$this->assertTrue($fixture->fileExists($newIdentifier));
		$this->assertEquals($expectedNewIdentifier, $newIdentifier);
	}

	/**
	 * @test
	 */
	public function renamingFilesFailsIfTargetFileExists() {
		$this->setExpectedException('TYPO3\\CMS\\Core\\Resource\\Exception\\ExistingTargetFileNameException', '', 1320291063);
		$this->addToMount(array(
			'targetFolder' => array('file' => '', 'newFile' => '')
		));
		$fixture = $this->createDriverFixture();
		$fixture->renameFile('/targetFolder/file', 'newFile');
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
					'someFolder' => array()
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
		$fixture = $this->createDriverFixture();
		$mapping = $fixture->renameFolder($oldFolderIdentifier, $newFolderName);
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
				'file2' => 'asdfg'
			)
		));
		$fixture = $this->createDriverFixture();
		$mappingInformation = $fixture->renameFolder('/sourceFolder/', 'newFolder');
		$this->isTrue(is_array($mappingInformation));
		$this->assertEquals('/newFolder/', $mappingInformation['/sourceFolder/']);
		$this->assertEquals('/newFolder/file2', $mappingInformation['/sourceFolder/file2']);
		$this->assertEquals('/newFolder/subFolder/file', $mappingInformation['/sourceFolder/subFolder/file']);
		$this->assertEquals('/newFolder/subFolder/', $mappingInformation['/sourceFolder/subFolder/']);
	}

	/**
	 * @test
	 */
	public function renameFolderRevertsRenamingIfFilenameMapCannotBeCreated() {
		$this->setExpectedException('\\RuntimeException', '', 1334160746);
		$this->addToMount(array(
			'sourceFolder' => array(
				'file' => 'asdfg'
			)
		));
		$fixture = $this->createDriverFixture(array(), array('createIdentifierMap'));
		$fixture->expects($this->atLeastOnce())->method('createIdentifierMap')->will($this->throwException(new \TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException()));
		$fixture->renameFolder('/sourceFolder/', 'newFolder');
		$this->assertFileExists($this->getUrlInMount('/sourceFolder/file'));
	}

	/**
	 * @test
	 */
	public function isFolderEmptyReturnsTrueForEmptyFolder() {
		// This also prepares the next few tests, so add more info than required for this test
		$this->addToMount(array(
			'emptyFolder' => array()
		));
		$fixture = $this->createDriverFixture();
		$this->assertTrue($fixture->isFolderEmpty('/emptyFolder/'));
		return $fixture;
	}

	/**
	 * @test
	 */
	public function isFolderEmptyReturnsFalseIfFolderHasFile() {
		$this->addToMount(array(
			'folderWithFile' => array(
				'someFile' => ''
			)
		));
		$fixture = $this->createDriverFixture();
		$this->assertFalse($fixture->isFolderEmpty('/folderWithFile/'));
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
		$fixture = $this->createDriverFixture();
		$this->assertFalse($fixture->isFolderEmpty('/folderWithSubfolder/'));
	}

	/**********************************
	 * Copy/move folder
	 **********************************/
	/**
	 * @test
	 */
	public function foldersCanBeMovedWithinStorage() {
		$fileContents =  uniqid();
		$this->addToMount(array(
			'sourceFolder' => array(
				'file' => $fileContents,
			),
			'targetFolder' => array(),
		));
		$fixture = $this->createDriverFixture();
		/** @var \TYPO3\CMS\Core\Resource\Driver\LocalDriver $fixture */
		$fixture->moveFolderWithinStorage('/sourceFolder/', '/targetFolder/', 'someFolder');
		$this->assertTrue(file_exists($this->getUrlInMount('/targetFolder/someFolder/')));
		$this->assertEquals($fileContents, file_get_contents($this->getUrlInMount('/targetFolder/someFolder/file')));
		$this->assertFileNotExists($this->getUrlInMount('/sourceFolder'));
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
		$fixture = $this->createDriverFixture();
		$mappingInformation = $fixture->moveFolderWithinStorage('/sourceFolder/', '/targetFolder/', 'sourceFolder');
		$this->assertEquals('/targetFolder/sourceFolder/file', $mappingInformation['/sourceFolder/file']);
		$this->assertEquals('/targetFolder/sourceFolder/subFolder/file', $mappingInformation['/sourceFolder/subFolder/file']);
		$this->assertEquals('/targetFolder/sourceFolder/subFolder/', $mappingInformation['/sourceFolder/subFolder/']);
	}

	/**
	 * @test
	 */
	public function folderCanBeRenamedWhenMoving() {
		$this->addToMount(array(
			'sourceFolder' => array(
				'file' => uniqid(),
			),
			'targetFolder' => array(),
		));
		$fixture = $this->createDriverFixture();
		$fixture->moveFolderWithinStorage('/sourceFolder/', '/targetFolder/', 'newFolder');
		$this->assertTrue(file_exists($this->getUrlInMount('/targetFolder/newFolder/')));
	}

	/**
	 * @test
	 */
	public function copyFolderWithinStorageCopiesSingleFileToNewFolderName() {
		$this->addToMount(array(
			'sourceFolder' => array(
				'file' => uniqid(),
			),
			'targetFolder' => array(),
		));
		$fixture = $this->createDriverFixture();
		$fixture->copyFolderWithinStorage('/sourceFolder/', '/targetFolder/', 'newFolderName');
		$this->assertTrue(is_file($this->getUrlInMount('/targetFolder/newFolderName/file')));
	}

	/**
	 * @test
	 */
	public function copyFolderWithinStorageCopiesSingleSubFolderToNewFolderName() {
		list($basePath, $fixture) = $this->prepareRealTestEnvironment();
		GeneralUtility::mkdir_deep($basePath, '/sourceFolder/subFolder');
		GeneralUtility::mkdir_deep($basePath, '/targetFolder');

		$fixture->copyFolderWithinStorage('/sourceFolder/', '/targetFolder/', 'newFolderName');
		$this->isTrue(is_dir($basePath . '/targetFolder/newFolderName/subFolder'));
	}

	/**
	 * @test
	 */
	public function copyFolderWithinStorageCopiesFileInSingleSubFolderToNewFolderName() {
		list($basePath, $fixture) = $this->prepareRealTestEnvironment();
		GeneralUtility::mkdir_deep($basePath, '/sourceFolder/subFolder');
		GeneralUtility::mkdir_deep($basePath, '/targetFolder');
		file_put_contents($basePath . '/sourceFolder/subFolder/file', uniqid());
		GeneralUtility::fixPermissions($basePath . '/sourceFolder/subFolder/file');

		$fixture->copyFolderWithinStorage('/sourceFolder/', '/targetFolder/', 'newFolderName');
		$this->assertTrue(is_file($basePath . '/targetFolder/newFolderName/subFolder/file'));
	}

	///////////////////////
	// Tests concerning sanitizeFileName
	///////////////////////

	/**
	 * Set up data for sanitizeFileName tests
	 */
	public function setUpCharacterStrings() {
		// Generate string containing all characters for the iso8859-1 charset, charcode greater than 127
		$this->iso88591GreaterThan127 = '';
		for ($i = 0xA0; $i <= 0xFF; $i++) {
			$this->iso88591GreaterThan127 .= chr($i);
		}

		// Generate string containing all characters for the utf-8 Latin-1 Supplement (U+0080 to U+00FF)
		// without U+0080 to U+009F: control characters
		// Based on http://www.utf8-chartable.de/unicode-utf8-table.pl
		$this->utf8Latin1Supplement = '';
		for ($i = 0xA0; $i <= 0xBF; $i++) {
			$this->utf8Latin1Supplement .= chr(0xC2) . chr($i);
		}
		for ($i = 0x80; $i <= 0xBF; $i++) {
			$this->utf8Latin1Supplement .= chr(0xC3) . chr($i);
		}

		// Generate string containing all characters for the utf-8 Latin-1 Extended-A (U+0100 to U+017F)
		$this->utf8Latin1ExtendedA = '';
		for ($i = 0x80; $i <= 0xBF; $i++) {
			$this->utf8Latin1ExtendedA .= chr(0xC4) . chr($i);
		}
		for ($i = 0x80; $i <= 0xBF; $i++) {
			$this->utf8Latin1ExtendedA .= chr(0xC5) . chr($i);
		}
	}

	/**
	 * Data provider for sanitizeFileNameUTF8FilesystemDataProvider
	 *
	 * Every array splits into:
	 * - String value fileName
	 * - String value charset (none = '', utf-8, latin1, etc.)
	 * - Expected result (cleaned fileName)
	 *
	 * @return array
	 */
	public function sanitizeFileNameUTF8FilesystemDataProvider() {
		$this->setUpCharacterStrings();
		return array(
			// Characters ordered by ASCII table
			'allowed characters utf-8 (ASCII part)' => array(
				'-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz',
				'-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz'
			),
			// Characters ordered by ASCII table (except for space-character, because space-character ist trimmed)
			'replace special characters with _ (not allowed characters) utf-8 (ASCII part)' => array(
				'! "#$%&\'()*+,/:;<=>?[\\]^`{|}~',
				'_____________________________'
			),
			'utf-8 (Latin-1 Supplement)' => array(
				$this->utf8Latin1Supplement,
				'________________________________ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõö÷øùúûüýþÿ'
			),
			'trim leading and tailing spaces utf-8' => array(
				' test.txt  ',
				'test.txt'
			),
			'remove tailing dot' => array(
				'test.txt.',
				'test.txt'
			),
		);
	}

	/**
	 * @test
	 * @dataProvider sanitizeFileNameUTF8FilesystemDataProvider
	 */
	public function sanitizeFileNameUTF8Filesystem($fileName, $expectedResult) {
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem'] = 1;
		$this->assertEquals(
			$expectedResult,
			$this->createDriverFixture()->sanitizeFileName($fileName)
		);
	}


	/**
	 * Data provider for sanitizeFileNameNonUTF8Filesystem
	 *
	 * Every array splits into:
	 * - String value fileName
	 * - String value charset (none = '', utf-8, latin1, etc.)
	 * - Expected result (cleaned fileName)
	 *
	 * @return array
	 */
	public function sanitizeFileNameNonUTF8FilesystemDataProvider() {
		$this->setUpCharacterStrings();
		return array(
			// Characters ordered by ASCII table
			'allowed characters iso-8859-1' => array(
				'-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz',
				'iso-8859-1',
				'-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz'
			),
			// Characters ordered by ASCII table
			'allowed characters utf-8' => array(
				'-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz',
				'utf-8',
				'-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz'
			),
			// Characters ordered by ASCII table (except for space-character, because space-character ist trimmed)
			'replace special characters with _ (not allowed characters) iso-8859-1' => array(
				'! "#$%&\'()*+,/:;<=>?[\\]^`{|}~',
				'iso-8859-1',
				'_____________________________'
			),
			// Characters ordered by ASCII table (except for space-character, because space-character ist trimmed)
			'replace special characters with _ (not allowed characters) utf-8' => array(
				'! "#$%&\'()*+,/:;<=>?[\\]^`{|}~',
				'utf-8',
				'_____________________________'
			),
			'iso-8859-1 (code > 127)' => array(
				// http://de.wikipedia.org/wiki/ISO_8859-1
				// chr(0xA0) = NBSP (no-break space) => gets trimmed
				$this->iso88591GreaterThan127,
				'iso-8859-1',
				'_centpound_yen____c_a_____R_____-23_u___1o__1_41_23_4_AAAAAEAAAECEEEEIIIIDNOOOOOExOEUUUUEYTHssaaaaaeaaaeceeeeiiiidnoooooe_oeuuuueythy'
			),
			'utf-8 (Latin-1 Supplement)' => array(
				// chr(0xC2) . chr(0x0A) = NBSP (no-break space) => gets trimmed
				$this->utf8Latin1Supplement,
				'utf-8',
				'_centpound__yen______c_a_______R_______-23__u_____1o__1_41_23_4_AAAAAEAAAECEEEEIIIIDNOOOOOExOEUUUUEYTHssaaaaaeaaaeceeeeiiiidnoooooe_oeuuuueythy'
			),
			'utf-8 (Latin-1 Extended A)' => array(
				$this->utf8Latin1ExtendedA,
				'utf-8',
				'AaAaAaCcCcCcCcDdDdEeEeEeEeEeGgGgGgGgHhHhIiIiIiIiIiIJijJjKk__LlLlLlL_l_LlNnNnNn_n____OOooOoOoOEoeRrRrRrSsSsSsSsTtTtTtUuUuUuUuUuUuWwYyYZzZzZzs'
			),
			'trim leading and tailing spaces iso-8859-1' => array(
				' test.txt  ',
				'iso-8859-1',
				'test.txt'
			),
			'trim leading and tailing spaces utf-8' => array(
				' test.txt  ',
				'utf-8',
				'test.txt'
			),
			'remove tailing dot iso-8859-1' => array(
				'test.txt.',
				'iso-8859-1',
				'test.txt'
			),
			'remove tailing dot utf-8' => array(
				'test.txt.',
				'utf-8',
				'test.txt'
			),
		);
	}

	/**
	 * @test
	 * @dataProvider sanitizeFileNameNonUTF8FilesystemDataProvider
	 */
	public function sanitizeFileNameNonUTF8Filesystem($fileName, $charset, $expectedResult) {
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem'] = 0;
		$this->assertEquals(
			$expectedResult,
			$this->createDriverFixture()->sanitizeFileName($fileName, $charset)
		);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Core\Resource\Exception\InvalidFileNameException
	 */
	public function sanitizeFileNameThrowsExceptionOnInvalidFileName() {
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem'] = 1;
		$this->createDriverFixture()->sanitizeFileName('');
	}

}
