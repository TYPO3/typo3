<?php
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
 * Testcase for the Tx_Extensionmanager_Utility_List class in the TYPO3 Core.
 *
 * @package Extension Manager
 * @subpackage Tests
 */
class Tx_Extensionmanager_Utility_FileHandlingTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	public $fakedExtensions;

	/**
	 * @return void
	 */
	public function tearDown() {
		foreach ($this->fakedExtensions as $extension => $dummy) {
			t3lib_div::rmdir(PATH_site . 'typo3conf/ext/' . $extension, TRUE);
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
		t3lib_div::mkdir($absExtPath);
		return $extKey;
	}

	/**
	 * @test
	 * @return void
	 */
	public function makeAndClearExtensionDirRemovesExtensionDirIfAlreadyExists() {
		$extKey = $this->createFakeExtension();
		$fileHandlerMock = $this->getAccessibleMock(
			'Tx_Extensionmanager_Utility_FileHandling',
			array('removeDirectory', 'addDirectory')
		);
		$fileHandlerMock->expects($this->once())->method('removeDirectory')->with(PATH_site . 'typo3conf/ext/' . $extKey . '/');
		$fileHandlerMock->_call('makeAndClearExtensionDir', $extKey);
	}

	/**
	 * @test
	 * @return void
	 */
	public function makeAndClearExtensionDirAddsDir() {
		$extKey = $this->createFakeExtension();
		$fileHandlerMock = $this->getAccessibleMock(
			'Tx_Extensionmanager_Utility_FileHandling',
			array('removeDirectory', 'addDirectory')
		);
		$fileHandlerMock->expects($this->once())->method('addDirectory')->with(PATH_site . 'typo3conf/ext/' . $extKey . '/');
		$fileHandlerMock->_call('makeAndClearExtensionDir', $extKey);
	}

	/**
	 * @test
	 * @expectedException Tx_Extensionmanager_Exception_ExtensionManager
	 * @return void
	 */
	public function makeAndClearExtensionDirThrowsExceptionOnInvalidPath() {
		$fileHandlerMock = $this->getAccessibleMock(
			'Tx_Extensionmanager_Utility_FileHandling',
			array('removeDirectory', 'addDirectory')
		);
		$fileHandlerMock->_call('makeAndClearExtensionDir', 'testing123', 'fakepath');
	}

	/**
	 * @test
	 * @return void
	 */
	public function addDirectoryAddsDirectory() {
		$extDirPath = $this->fakedExtensions[$this->createFakeExtension(TRUE)]['siteAbsPath'];
		$fileHandlerMock = $this->getAccessibleMock('Tx_Extensionmanager_Utility_FileHandling', array('dummy'));
		$this->assertFalse(is_dir($extDirPath));
		$fileHandlerMock->_call('addDirectory', $extDirPath);
		$this->assertTrue(is_dir($extDirPath));
	}

	/**
	 * @test
	 * @expectedException Tx_Extensionmanager_Exception_ExtensionManager
	 * @return void
	 */
	public function addDirectoryThrowsExceptionOnError() {
		$extDirPath = '/etc/test123/';
		$fileHandlerMock = $this->getAccessibleMock('Tx_Extensionmanager_Utility_FileHandling', array('dummy'));
		$fileHandlerMock->_call('addDirectory', $extDirPath);
	}

	/**
	 * @test
	 * @return void
	 */
	public function removeDirectoryRemovesDirectory() {
		$extDirPath = $this->fakedExtensions[$this->createFakeExtension()]['siteAbsPath'];
		$fileHandlerMock = $this->getAccessibleMock('Tx_Extensionmanager_Utility_FileHandling', array('dummy'));
		$this->assertTrue(is_dir($extDirPath));
		$fileHandlerMock->_call('removeDirectory', $extDirPath);
		$this->assertFalse(is_dir($extDirPath));
	}

	/**
	 * @test
	 * @expectedException Tx_Extensionmanager_Exception_ExtensionManager
	 * @return void
	 */
	public function removeDirectoryThrowsExceptionOnError() {
		$extDirPath = '/etc/test123/';
		$fileHandlerMock = $this->getAccessibleMock('Tx_Extensionmanager_Utility_FileHandling', array('dummy'));
		$fileHandlerMock->_call('removeDirectory', $extDirPath);
	}

	/**
	 * @test
	 * @return void
	 */
	public function unpackExtensionFromExtensionDataArrayCreatesTheExtensionDirectory() {
		$extensionData = array(
			'extKey' => 'test'
		);
		$fileHandlerMock = $this->getAccessibleMock('Tx_Extensionmanager_Utility_FileHandling',
			array(
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
		$fileHandlerMock = $this->getAccessibleMock('Tx_Extensionmanager_Utility_FileHandling', array('makeAndClearExtensionDir'));
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
		$rootPath = $extDirPath = $this->fakedExtensions[$this->createFakeExtension()]['siteAbsPath'];
		$fileHandlerMock = $this->getAccessibleMock('Tx_Extensionmanager_Utility_FileHandling', array('makeAndClearExtensionDir'));
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
		$fileHandlerMock = $this->getAccessibleMock('Tx_Extensionmanager_Utility_FileHandling', array('makeAndClearExtensionDir'));
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
		$fileHandlerMock = $this->getAccessibleMock('Tx_Extensionmanager_Utility_FileHandling', array('makeAndClearExtensionDir'));
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
				'shy' => 0,
			),
		);
		$rootPath = $this->fakedExtensions[$extKey]['siteAbsPath'];
		$emConfUtilityMock = $this->getAccessibleMock('Tx_Extensionmanager_Utility_EmConf', array('constructEmConf'));
		$emConfUtilityMock->expects($this->once())
			->method('constructEmConf')
			->with($extensionData)
			->will($this->returnValue(var_export($extensionData['EM_CONF'], TRUE)));
		$fileHandlerMock = $this->getAccessibleMock('Tx_Extensionmanager_Utility_FileHandling', array('makeAndClearExtensionDir'));
		$fileHandlerMock->_set('emConfUtility', $emConfUtilityMock);
		$fileHandlerMock->_call('writeEmConfToFile', $extensionData, $rootPath);
		$this->assertTrue(file_exists($rootPath . 'ext_emconf.php'));
	}

}

?>