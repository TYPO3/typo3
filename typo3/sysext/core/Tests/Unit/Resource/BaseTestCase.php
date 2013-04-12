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

/**
 * Basic test case for the \TYPO3\CMS\Core\Resource\File tests
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 */
abstract class BaseTestCase extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var string
	 */
	protected $basedir = 'basedir';

	protected $mountDir;

	protected $vfsContents = array();

	public function setUp() {
		$this->mountDir = uniqid('mount-');
		$this->basedir = uniqid('base-');
		\vfsStream::setup($this->basedir);
		// Add an entry for the mount directory to the VFS contents
		$this->vfsContents = array($this->mountDir => array());
	}

	protected function getMountRootUrl() {
		return $this->getUrlInMount('');
	}

	protected function mergeToVfsContents($contents) {
		$this->vfsContents = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($this->vfsContents, $contents);
	}

	protected function initializeVfs() {
		if (is_callable('vfsStream::create') === FALSE) {
			$this->markTestSkipped('vfsStream::create() does not exist');
		}
		\vfsStream::create($this->vfsContents);
	}

	/**
	 * Adds the given directory structure to the mount folder in the VFS. Existing files will be overwritten!
	 *
	 * @param array $dirStructure
	 * @return void
	 */
	protected function addToMount(array $dirStructure) {
		$this->mergeToVfsContents(array($this->mountDir => $dirStructure));
	}

	/**
	 * Returns the URL for a path inside the mount directory
	 *
	 * @param $path
	 * @return string
	 */
	protected function getUrlInMount($path) {
		return \vfsStream::url($this->basedir . '/' . $this->mountDir . '/' . ltrim($path, '/'));
	}

	/**
	 * Adds the given directory structure to the VFS. Existing files will be overwritten!
	 *
	 * @param array $dirStructure
	 * @return void
	 */
	protected function addToVfs(array $dirStructure) {
		$this->mergeToVfsContents($dirStructure);
	}

	/**
	 * Returns the URL for a path inside the VFS
	 *
	 * @param $path
	 * @return string
	 */
	protected function getUrl($path) {
		return \vfsStream::url($this->basedir . '/' . ltrim($path, '/'));
	}

	/**
	 * Creates a file or folder mock. This should not be called directly, but only through getSimple{File,Folder}Mock()
	 *
	 * @param $type
	 * @param $identifier
	 * @param $mockedMethods
	 * @return \TYPO3\CMS\Core\Resource\File|\TYPO3\CMS\Core\Resource\Folder
	 */
	protected function _createFileFolderMock($type, $identifier, $mockedMethods) {
		if (!empty($mockedMethods)) {
			if (!in_array('getIdentifier', $mockedMethods)) {
				$mockedMethods[] = 'getIdentifier';
			}
			if (!in_array('getName', $mockedMethods)) {
				$mockedMethods[] = 'getName';
			}
		}
		$mock = $this->getMock($type, $mockedMethods, array(), '', FALSE);
		$mock->expects($this->any())->method('getIdentifier')->will($this->returnValue($identifier));
		$mock->expects($this->any())->method('getName')->will($this->returnValue(basename($identifier)));
		return $mock;
	}

	/**
	 * Returns a simple mock of a file object that just knows its identifier
	 *
	 * @param string $identifier
	 * @param array $mockedMethods the methods to mock
	 * @return \TYPO3\CMS\Core\Resource\File
	 */
	protected function getSimpleFileMock($identifier, $mockedMethods = array()) {
		return $this->_createFileFolderMock('TYPO3\\CMS\\Core\\Resource\\File', $identifier, $mockedMethods);
	}

	/**
	 * Returns a simple mock of a file object that just knows its identifier
	 *
	 * @param string $identifier
	 * @param array $mockedMethods the methods to mock
	 * @return \TYPO3\CMS\Core\Resource\Folder
	 */
	protected function getSimpleFolderMock($identifier, $mockedMethods = array()) {
		return $this->_createFileFolderMock('TYPO3\\CMS\\Core\\Resource\\Folder', $identifier, $mockedMethods);
	}

	/**
	 * Returns a mock of a folder object with subfolders and files.
	 *
	 * @param $identifier
	 * @param array $mockedMethods Methods to mock, in addition to getFiles and getSubfolders
	 * @param \TYPO3\CMS\Core\Resource\Folder[] $subfolders
	 * @param \TYPO3\CMS\Core\Resource\File[] $files
	 * @return \TYPO3\CMS\Core\Resource\File|\TYPO3\CMS\Core\Resource\Folder
	 */
	protected function getFolderMock($identifier, $mockedMethods = array(), $subfolders = array(), $files = array()) {
		$folder = $this->_createFileFolderMock('TYPO3\\CMS\\Core\\Resource\\Folder', $identifier, array_merge($mockedMethods, array('getFiles', 'getSubfolders')));
		$folder->expects($this->any())->method('getSubfolders')->will($this->returnValue($subfolders));
		$folder->expects($this->any())->method('getFiles')->will($this->returnValue($files));
		return $folder;
	}

}

?>