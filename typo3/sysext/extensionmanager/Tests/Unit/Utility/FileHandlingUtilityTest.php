<?php
namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Utility;

/***************************************************************
 * Copyright notice
 *
 * (c) 2012
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

/**
 * Testcase
 *
 */
class FileHandlingUtilityTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var array List of created fake extensions to be deleted in tearDown() again
	 */
	protected $fakedExtensions = array();

	/**
	 * @var array List of resources (files or empty directories) that need to be removed in tearDown() again
	 */
	protected $resourcesToRemove = array();

	/**
	 * @return void
	 */
	public function tearDown() {
		foreach ($this->fakedExtensions as $extension => $dummy) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::rmdir(PATH_site . 'typo3conf/ext/' . $extension, TRUE);
		}
		foreach ($this->resourcesToRemove as $resource) {
			if (file_exists($resource) && is_file($resource)) {
				unlink($resource);
			} elseif(file_exists($resource) && is_dir($resource)) {
				rmdir($resource);
			}
		}
	}

	/**
	 * Creates a fake extension inside typo3temp/. No configuration is created,
	 * just the folder
	 *
	 * @param bool $extkeyOnly
	 * @return string The extension key
	 */
	protected function createFakeExtension($extkeyOnly = FALSE) {
		$extKey = strtolower(uniqid('testing'));
		$absExtPath = PATH_site . 'typo3conf/ext/' . $extKey . '/';
		$relPath = 'typo3conf/ext/' . $extKey . '/';
		$this->fakedExtensions[$extKey] = array(
			'siteRelPath' => $relPath,
			'siteAbsPath' => $absExtPath
		);
		if ($extkeyOnly === TRUE) {
			return $extKey;
		}
		\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir($absExtPath);
		return $extKey;
	}

	/**
	 * @test
	 * @return void
	 */
	public function makeAndClearExtensionDirRemovesExtensionDirIfAlreadyExists() {
		$extKey = $this->createFakeExtension();
		$fileHandlerMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\FileHandlingUtility', array('removeDirectory', 'addDirectory'));
		$fileHandlerMock->expects($this->once())->method('removeDirectory')->with(PATH_site . 'typo3conf/ext/' . $extKey . '/');
		$fileHandlerMock->_call('makeAndClearExtensionDir', $extKey);
	}

	/**
	 * @return array
	 */
	public function invalidRelativePathDataProvider() {
		return array(
			array('../../'),
			array('/foo/bar'),
			array('foo//bar'),
			array('foo/bar' . chr(0)),
		);
	}

	/**
	 * @param string $invalidRelativePath
	 * @test
	 * @dataProvider invalidRelativePathDataProvider
	 * @expectedException \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
	 */
	public function getAbsolutePathThrowsExceptionForInvalidRelativePaths($invalidRelativePath) {
		$fileHandlerMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\FileHandlingUtility', array('dummy'));
		$fileHandlerMock->_call('getAbsolutePath', $invalidRelativePath);
	}

	/**
	 * @return array
	 */
	public function validRelativePathDataProvider() {
		return array(
			array('foo/../bar', PATH_site . 'bar'),
			array('bas', PATH_site . 'bas'),
		);
	}

	/**
	 * @param string $validRelativePath
	 * @param string $expectedAbsolutePath
	 * @test
	 * @dataProvider validRelativePathDataProvider
	 */
	public function getAbsolutePathReturnsAbsolutePathForValidRelativePaths($validRelativePath, $expectedAbsolutePath) {
		$fileHandlerMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\FileHandlingUtility', array('dummy'));
		$this->assertSame($expectedAbsolutePath, $fileHandlerMock->_call('getAbsolutePath', $validRelativePath));
	}

	/**
	 * @test
	 * @return void
	 */
	public function makeAndClearExtensionDirAddsDir() {
		$extKey = $this->createFakeExtension();
		$fileHandlerMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\FileHandlingUtility', array('removeDirectory', 'addDirectory'));
		$fileHandlerMock->expects($this->once())->method('addDirectory')->with(PATH_site . 'typo3conf/ext/' . $extKey . '/');
		$fileHandlerMock->_call('makeAndClearExtensionDir', $extKey);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
	 * @return void
	 */
	public function makeAndClearExtensionDirThrowsExceptionOnInvalidPath() {
		$fileHandlerMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\FileHandlingUtility', array('removeDirectory', 'addDirectory'));
		$fileHandlerMock->_call('makeAndClearExtensionDir', 'testing123', 'fakepath');
	}

	/**
	 * @test
	 * @return void
	 */
	public function addDirectoryAddsDirectory() {
		$extDirPath = $this->fakedExtensions[$this->createFakeExtension(TRUE)]['siteAbsPath'];
		$fileHandlerMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\FileHandlingUtility', array('dummy'));
		$this->assertFalse(is_dir($extDirPath));
		$fileHandlerMock->_call('addDirectory', $extDirPath);
		$this->assertTrue(is_dir($extDirPath));
	}

	/**
	 * @test
	 * @return void
	 */
	public function removeDirectoryRemovesDirectory() {
		$extDirPath = $this->fakedExtensions[$this->createFakeExtension()]['siteAbsPath'];
		$fileHandlerMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\FileHandlingUtility', array('dummy'));
		$this->assertTrue(is_dir($extDirPath));
		$fileHandlerMock->_call('removeDirectory', $extDirPath);
		$this->assertFalse(is_dir($extDirPath));
	}

	/**
	 * @test
	 * @return void
	 */
	public function removeDirectoryRemovesSymlink() {
		$absoluteSymlinkPath = PATH_site . 'typo3temp/' . uniqid('test_symlink_');
		$absoluteFilePath = PATH_site . 'typo3temp/' . uniqid('test_file_');
		touch($absoluteFilePath);
		$this->resourcesToRemove[] = $absoluteFilePath;
		symlink($absoluteFilePath, $absoluteSymlinkPath);
		$fileHandler = new \TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility();
		$fileHandler->removeDirectory($absoluteSymlinkPath);
		$this->assertFalse(is_link($absoluteSymlinkPath));
	}

	/**
	 * @test
	 * @return void
	 */
	public function removeDirectoryDoesNotRemoveContentOfSymlinkedTargetDirectory() {
		$absoluteSymlinkPath = PATH_site . 'typo3temp/' . uniqid('test_symlink_');
		$absoluteDirectoryPath = PATH_site . 'typo3temp/' . uniqid('test_dir_') . '/';
		$relativeFilePath = uniqid('test_file_');

		mkdir($absoluteDirectoryPath);
		touch($absoluteDirectoryPath . $relativeFilePath);

		$this->resourcesToRemove[] = $absoluteDirectoryPath . $relativeFilePath;
		$this->resourcesToRemove[] = $absoluteDirectoryPath;

		symlink($absoluteDirectoryPath, $absoluteSymlinkPath);

		$fileHandler = new \TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility();
		$fileHandler->removeDirectory($absoluteSymlinkPath);
		$this->assertTrue(is_file($absoluteDirectoryPath . $relativeFilePath));
	}

	/**
	 * @test
	 * @return void
	 */
	public function unpackExtensionFromExtensionDataArrayCreatesTheExtensionDirectory() {
		$extensionData = array(
			'extKey' => 'test'
		);
		$fileHandlerMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\FileHandlingUtility', array(
			'makeAndClearExtensionDir',
			'writeEmConfToFile',
			'extractFilesArrayFromExtensionData',
			'extractDirectoriesFromExtensionData',
			'createDirectoriesForExtensionFiles',
			'writeExtensionFiles'
		));
		$fileHandlerMock->expects($this->once())->method('extractFilesArrayFromExtensionData')->will($this->returnValue(array()));
		$fileHandlerMock->expects($this->once())->method('extractDirectoriesFromExtensionData')->will($this->returnValue(array()));
		$fileHandlerMock->expects($this->once())->method('makeAndClearExtensionDir')->with($extensionData['extKey']);
		$fileHandlerMock->_call('unpackExtensionFromExtensionDataArray', $extensionData);
	}

	/**
	 * @test
	 * @return void
	 */
	public function extractFilesArrayFromExtensionDataReturnsFileArray() {
		$extensionData = array(
			'key' => 'test',
			'FILES' => array(
				'filename1' => 'dummycontent',
				'filename2' => 'dummycontent2'
			)
		);
		$fileHandlerMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\FileHandlingUtility', array('makeAndClearExtensionDir'));
		$extractedFiles = $fileHandlerMock->_call('extractFilesArrayFromExtensionData', $extensionData);
		$this->assertArrayHasKey('filename1', $extractedFiles);
		$this->assertArrayHasKey('filename2', $extractedFiles);
	}

	/**
	 * @test
	 * @return void
	 */
	public function writeExtensionFilesWritesFiles() {
		$files = array(
			'ChangeLog' => array(
				'name' => 'ChangeLog',
				'size' => 4559,
				'mtime' => 1219448527,
				'is_executable' => FALSE,
				'content' => 'some content to write'
			),
			'README' => array(
				'name' => 'README',
				'size' => 4566,
				'mtime' => 1219448533,
				'is_executable' => FALSE,
				'content' => 'FEEL FREE TO ADD SOME DOCUMENTATION HERE'
			)
		);
		$rootPath = ($extDirPath = $this->fakedExtensions[$this->createFakeExtension()]['siteAbsPath']);
		$fileHandlerMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\FileHandlingUtility', array('makeAndClearExtensionDir'));
		$fileHandlerMock->_call('writeExtensionFiles', $files, $rootPath);
		$this->assertTrue(file_exists($rootPath . 'ChangeLog'));
	}

	/**
	 * @test
	 * @return void
	 */
	public function extractDirectoriesFromExtensionDataExtractsDirectories() {
		$files = array(
			'doc/ChangeLog' => array(
				'name' => 'ChangeLog',
				'size' => 4559,
				'mtime' => 1219448527,
				'is_executable' => FALSE,
				'content' => 'some content to write'
			),
			'mod/doc/README' => array(
				'name' => 'README',
				'size' => 4566,
				'mtime' => 1219448533,
				'is_executable' => FALSE,
				'content' => 'FEEL FREE TO ADD SOME DOCUMENTATION HERE'
			)
		);
		$fileHandlerMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\FileHandlingUtility', array('makeAndClearExtensionDir'));
		$extractedDirectories = $fileHandlerMock->_call('extractDirectoriesFromExtensionData', $files);
		$this->assertContains('doc/', $extractedDirectories);
		$this->assertContains('mod/doc/', $extractedDirectories);
	}

	/**
	 * @test
	 * @return void
	 */
	public function createDirectoriesForExtensionFilesCreatesDirectories() {
		$rootPath = $this->fakedExtensions[$this->createFakeExtension()]['siteAbsPath'];
		$directories = array(
			'doc/',
			'mod/doc/'
		);
		$fileHandlerMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\FileHandlingUtility', array('makeAndClearExtensionDir'));
		$this->assertFalse(is_dir($rootPath . 'doc/'));
		$this->assertFalse(is_dir($rootPath . 'mod/doc/'));
		$fileHandlerMock->_call('createDirectoriesForExtensionFiles', $directories, $rootPath);
		$this->assertTrue(is_dir($rootPath . 'doc/'));
		$this->assertTrue(is_dir($rootPath . 'mod/doc/'));
	}

	/**
	 * @test
	 * @return void
	 */
	public function writeEmConfWritesEmConfFile() {
		$extKey = $this->createFakeExtension();
		$extensionData = array(
			'extKey' => $extKey,
			'EM_CONF' => array(
				'title' => 'Plugin cache engine',
				'description' => 'Provides an interface to cache plugin content elements based on 4.3 caching framework',
				'category' => 'Frontend',
				'shy' => 0
			)
		);
		$rootPath = $this->fakedExtensions[$extKey]['siteAbsPath'];
		$emConfUtilityMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\EmConfUtility', array('constructEmConf'));
		$emConfUtilityMock->expects($this->once())->method('constructEmConf')->with($extensionData)->will($this->returnValue(var_export($extensionData['EM_CONF'], TRUE)));
		$fileHandlerMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\FileHandlingUtility', array('makeAndClearExtensionDir'));
		$fileHandlerMock->_set('emConfUtility', $emConfUtilityMock);
		$fileHandlerMock->_call('writeEmConfToFile', $extensionData, $rootPath);
		$this->assertTrue(file_exists($rootPath . 'ext_emconf.php'));
	}

	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility
	 */
	protected function getPreparedFileHandlingMockForDirectoryCreationTests() {
		/** @var $fileHandlerMock \TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility|\PHPUnit_Framework_MockObject_MockObject */
		$fileHandlerMock = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Utility\\FileHandlingUtility', array('createNestedDirectory', 'getAbsolutePath', 'directoryExists'));
		$fileHandlerMock->expects($this->any())
			->method('getAbsolutePath')
			->will($this->returnArgument(0));
		return $fileHandlerMock;
	}

	/**
	 * @test
	 */
	public function uploadFolderIsNotCreatedIfNotRequested() {
		$fileHandlerMock = $this->getPreparedFileHandlingMockForDirectoryCreationTests();
		$fileHandlerMock->expects($this->never())
			->method('createNestedDirectory');
		$fileHandlerMock->ensureConfiguredDirectoriesExist(array(
				'key' => 'foo_bar',
				'uploadfolder' => 0,
			)
		);
	}

	/**
	 * @test
	 */
	public function additionalFoldersAreNotCreatedIfNotRequested() {
		$fileHandlerMock = $this->getPreparedFileHandlingMockForDirectoryCreationTests();
		$fileHandlerMock->expects($this->never())
			->method('createNestedDirectory');
		$fileHandlerMock->ensureConfiguredDirectoriesExist(array(
				'key' => 'foo_bar',
				'createDirs' => '',
			)
		);
	}

	/**
	 * @test
	 */
	public function configuredUploadFolderIsCreatedIfRequested() {
		$fileHandlerMock = $this->getPreparedFileHandlingMockForDirectoryCreationTests();
		$fileHandlerMock->expects($this->once())
			->method('createNestedDirectory')
			->with('uploads/tx_foobar/');
		$fileHandlerMock->ensureConfiguredDirectoriesExist(array(
				'key' => 'foo_bar',
				'uploadfolder' => 1,
			)
		);
	}

	/**
	 * @test
	 */
	public function configuredAdditionalDirectoriesAreCreatedIfRequested() {
		$fileHandlerMock = $this->getPreparedFileHandlingMockForDirectoryCreationTests();
		$fileHandlerMock->expects($this->exactly(2))
			->method('createNestedDirectory')
			->will($this->returnCallback(function($path) {
					if (!in_array($path, array('foo/bar', 'baz/foo'))) {
						throw new \Exception('Path "' . $path . '" is not expected to be created');
					}

				})
			);
		$fileHandlerMock->ensureConfiguredDirectoriesExist(array(
				'key' => 'foo_bar',
				'createDirs' => 'foo/bar, baz/foo',
			)
		);
	}

	/**
	 * @test
	 */
	public function configuredDirectoriesAreNotCreatedIfTheyAlreadyExist() {
		$fileHandlerMock = $this->getPreparedFileHandlingMockForDirectoryCreationTests();
		$fileHandlerMock->expects($this->exactly(3))
			->method('directoryExists')
			->will($this->returnValue(TRUE));
		$fileHandlerMock->expects($this->never())
			->method('createNestedDirectory');
		$fileHandlerMock->ensureConfiguredDirectoriesExist(array(
				'key' => 'foo_bar',
				'uploadfolder' => 1,
				'createDirs' => 'foo/bar, baz/foo',
			)
		);
	}

	/**
	 * Warning: This test asserts multiple things at once to keep the setup short.
	 *
	 * @test
	 */
	public function createZipFileFromExtensionGeneratesCorrectArchive() {
		// Create extension for testing:
		$extKey = $this->createFakeExtension();
		$extensionRoot = $this->fakedExtensions[$extKey]['siteAbsPath'];

		// Build mocked fileHandlingUtility:
		$fileHandlerMock = $this->getAccessibleMock(
			'TYPO3\\CMS\\Extensionmanager\\Utility\\FileHandlingUtility',
			array('getAbsoluteExtensionPath', 'getExtensionVersion')
		);
		$fileHandlerMock->expects($this->any())
			->method('getAbsoluteExtensionPath')
			->will($this->returnValue($extensionRoot));
		$fileHandlerMock->expects($this->any())
			->method('getExtensionVersion')
			->will($this->returnValue('0.0.0'));

		// Add files and directories to extension:
		touch($extensionRoot . 'emptyFile.txt');
		file_put_contents($extensionRoot . 'notEmptyFile.txt', 'content');
		touch($extensionRoot . '.hiddenFile');
		mkdir($extensionRoot . 'emptyDir');
		mkdir($extensionRoot . 'notEmptyDir');
		touch($extensionRoot . 'notEmptyDir/file.txt');

		// Create zip-file from extension
		$filename = $fileHandlerMock->_call('createZipFileFromExtension', $extKey);

		$expectedFilename = PATH_site . 'typo3temp/' . $extKey . '_0.0.0.zip';
		$this->assertEquals($expectedFilename, $filename, 'Archive file name differs from expectation');

		// File was created
		$this->assertTrue(file_exists($filename), 'Zip file not created');
		$this->resourcesToRemove[] = $filename;

		// Read archive and check its contents
		$archive = new \ZipArchive();
		$this->assertTrue($archive->open($filename), 'Unable to open archive');
		$this->assertEquals($archive->statName('emptyFile.txt')->size, 0, 'Empty file not in archive');
		$this->assertEquals($archive->getFromName('notEmptyFile.txt'), 'content', 'Expected content not found');
		$this->assertFalse($archive->statName('.hiddenFile'), 'Hidden file not in archive');
		$this->assertTrue(is_array($archive->statName('emptyDir/')), 'Empty directory not in archive');
		$this->assertTrue(is_array($archive->statName('notEmptyDir/')), 'Not empty directory not in archive');
		$this->assertTrue(is_array($archive->statName('notEmptyDir/file.txt')), 'File within directory not in archive');

		// Check that the archive has no additional content
		$this->assertEquals($archive->numFiles, 5, 'Too many or too less files in archive');
	}
}

?>
