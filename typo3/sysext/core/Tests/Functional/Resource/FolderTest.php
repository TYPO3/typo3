<?php
namespace TYPO3\CMS\Core\Tests\Functional\Resource;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Andreas Wolf <andreas.wolf@typo3.org>
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

use TYPO3\CMS\Core\Resource;

require_once 'vfsStream/vfsStream.php';

/**
 * Functional test case for the FAL folder class.
 *
 * @author Andreas Wolf <andreas.wolf@typo3.org>
 */
class FolderTest extends BaseTestCase {

	/**
	 * Helper method for testing restore of filters in the storage
	 *
	 * @param $filterMode
	 * @param $listCallback
	 */
	protected function _testFileAndFoldernameFilterRestoreAfterList($filterMode, $listCallback) {
		$this->isType('callable')->evaluate($listCallback);
		$storageFilter = new \TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter();
		$storageFilter->setAllowedFileExtensions('jpg,png');
		$folderFilter = new \TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter();
		$folderFilter->setAllowedFileExtensions('png');

		$storageObject = $this->getStorageObject();
		$storageObject->setFileAndFolderNameFilters(array(array($storageFilter, 'filterFileList')));
		$folder = $storageObject->getRootLevelFolder();
		$folder->setFileAndFolderNameFilters(array(array($storageFilter, 'filterFileList')));

		$filtersBackup = $storageObject->getFileAndFolderNameFilters();
		$listCallback($folder, $filterMode);

		$this->assertEquals($filtersBackup, $storageObject->getFileAndFolderNameFilters());
	}


	/***********************
	 * Tests for getFiles()
	 ***********************/

	/**
	 * @test
	 */
	public function getFilesRestoresFileAndFoldernameFiltersOfStorageAfterFetchingFileListIfFilterModeIsUseOwnFilters() {
		$this->_testFileAndFoldernameFilterRestoreAfterList(\TYPO3\CMS\Core\Resource\Folder::FILTER_MODE_USE_OWN_FILTERS,
			function(Resource\Folder $folder, $filterMode) {
				$folder->getFiles(0, 0, $filterMode);
			}
		);
	}

	/**
	 * @test
	 */
	public function getFilesRestoresFileAndFoldernameFiltersOfStorageAfterFetchingFileListIfFilterModeIsUseOwnAndStorageFiltersAndFiltersHaveBeenSetInFolder() {
		$this->_testFileAndFoldernameFilterRestoreAfterList(\TYPO3\CMS\Core\Resource\Folder::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS,
			function(Resource\Folder $folder, $filterMode) {
				$folder->getFiles(0, 0, $filterMode);
			}
		);
	}

	/**
	 * @test
	 */
	public function getFilesRespectsSetFileAndFoldernameFiltersIfFilterModeIsUseOwnFilters() {
		$filter = new \TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter();
		$filter->setAllowedFileExtensions('jpg,png');

		$this->addToMount(array('somefile.png' => '', 'somefile.jpg' => '', 'somefile.exe' => ''));
		$storageObject = $this->getStorageObject();
		$folder = $storageObject->getRootLevelFolder();
		$folder->setFileAndFolderNameFilters(array(array($filter, 'filterFileList')));

		$fileList = $folder->getFiles(0, 0, \TYPO3\CMS\Core\Resource\Folder::FILTER_MODE_USE_OWN_FILTERS);

		$this->assertArrayNotHasKey('somefile.exe', $fileList);
		$this->assertCount(2, $fileList);
	}

	/**
	 * @test
	 */
	public function getFilesMergesSetFileAndFoldernameFiltersIntoStoragesFiltersIfFilterModeIsUseOwnAndStorageFilters() {
		$foldersFilter = new \TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter();
		$foldersFilter->setAllowedFileExtensions('jpg,png');
		$storagesFilter = new \TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter();
		$storagesFilter->setDisallowedFileExtensions('png');

		$this->addToMount(array('somefile.png' => '', 'somefile.jpg' => '', 'somefile.exe' => ''));
		$storageObject = $this->getStorageObject();
		$storageObject->setFileAndFolderNameFilters(array(array($storagesFilter, 'filterFileList')));
		$folder = $storageObject->getRootLevelFolder();
		$folder->setFileAndFolderNameFilters(array(array($foldersFilter, 'filterFileList')));

		$fileList = $folder->getFiles(0, 0, \TYPO3\CMS\Core\Resource\Folder::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS);

		$this->assertArrayNotHasKey('somefile.exe', $fileList);
		$this->assertArrayNotHasKey('somefile.png', $fileList);
		$this->assertCount(1, $fileList);
	}

	/**
	 * @test
	 */
	public function getFilesUsesOnlyFileAndFoldernameFiltersOfStorageIfNoFiltersAreSetAndFilterModeIsUseOwnAndStorageFilters() {
		$filter = new \TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter();
		$filter->setAllowedFileExtensions('jpg,png');

		$this->addToMount(array('somefile.png' => '', 'somefile.jpg' => '', 'somefile.exe' => ''));
		$storageObject = $this->getStorageObject();
		$folder = $storageObject->getRootLevelFolder();
		$storageObject->setFileAndFolderNameFilters(array(array($filter, 'filterFileList')));

		$fileList = $folder->getFiles(0, 0, \TYPO3\CMS\Core\Resource\Folder::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS);

		$this->assertArrayNotHasKey('somefile.exe', $fileList);
		$this->assertCount(2, $fileList);
	}

	/**
	 * @test
	 */
	public function getFilesIgnoresSetFileAndFoldernameFiltersIfFilterModeIsSetToUseStorageFilters() {
		$filter = new \TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter();
		$filter->setAllowedFileExtensions('jpg,png');

		$this->addToMount(array('somefile.png' => '', 'somefile.jpg' => '', 'somefile.exe' => ''));
		$storageObject = $this->getStorageObject();
		$folder = $storageObject->getRootLevelFolder();
		$folder->setFileAndFolderNameFilters(array(array($filter, 'filterFileList')));

		$fileList = $folder->getFiles(0, 0, \TYPO3\CMS\Core\Resource\Folder::FILTER_MODE_USE_STORAGE_FILTERS);

		$this->assertCount(3, $fileList);
	}


	/****************************
	 * Tests for getSubfolders()
	 ****************************/

	/**
	 * @test
	 */
	public function getSubfoldersRestoresFileAndFoldernameFiltersOfStorageAfterFetchingFolderListIfFilterModeIsUseOwnFilters() {
		$this->_testFileAndFoldernameFilterRestoreAfterList(\TYPO3\CMS\Core\Resource\Folder::FILTER_MODE_USE_OWN_FILTERS,
			function(Resource\Folder $folder, $filterMode) {
				$folder->getSubfolders(0, 0, $filterMode);
			}
		);
	}

	/**
	 * @test
	 */
	public function getSubfoldersRestoresFileAndFoldernameFiltersOfStorageAfterFetchingFolderListIfFilterModeIsUseOwnAndStorageFiltersAndFiltersHaveBeenSetInFolder() {
		$this->_testFileAndFoldernameFilterRestoreAfterList(\TYPO3\CMS\Core\Resource\Folder::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS,
			function(Resource\Folder $folder, $filterMode) {
				$folder->getSubfolders(0, 0, $filterMode);
			}
		);
	}

	/**
	 * @test
	 */
	public function getSubfoldersRespectsSetFileAndFoldernameFiltersIfFilterModeIsUseOwnFilters() {
		\TYPO3\CMS\Core\Resource\Filter\FileNameFilter::setShowHiddenFilesAndFolders(FALSE);

		$this->addToMount(array('.hiddenFolder' => array(), 'someFolder' => array(), 'anotherFolder' => array()));
		$storageObject = $this->getStorageObject();
		$storageObject->setFileAndFolderNameFilters(array());
		$folder = $storageObject->getRootLevelFolder();
		$folder->setFileAndFolderNameFilters(array(array('TYPO3\\CMS\\Core\\Resource\\Filter\\FileNameFilter', 'filterHiddenFilesAndFolders')));

		$folderList = $folder->getSubfolders(0, 0, \TYPO3\CMS\Core\Resource\Folder::FILTER_MODE_USE_OWN_FILTERS);

		$this->assertArrayNotHasKey('.hiddenFolder', $folderList);
		$this->assertCount(2, $folderList);
	}

	// TODO implement the other tests from getFiles
}

?>