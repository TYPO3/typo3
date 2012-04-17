<?php

require_once 'vfsStream/vfsStream.php';
require_once realpath(dirname(__FILE__) . '/../../../t3lib/file/BaseTestCase.php');

class t3lib_Tests_Functional_file_StorageTest extends t3lib_file_BaseTestCase {

	/**
	 * @var t3lib_file_Storage
	 */
	protected $fixture;

	protected function prepareFixture() {
		$this->initializeVfs();

		$driver = new t3lib_file_Driver_LocalDriver(array('pathType' => 'absolute', 'basePath' => $this->getMountRootUrl()));
		$driver->initialize();

		$this->fixture = new t3lib_file_Storage($driver, array());
	}

	/**
	 * Test if the default filters filter out hidden files (like .htaccess)
	 *
	 * @test
	 */
	public function fileListingsDoNotContainHiddenFilesWithDefaultFilters() {
			// we cannot use fixture->createFile() because touch() does not work with vfsStream
		$this->addToMount(array('someFile' => '', '.someHiddenFile' => ''));
		$this->prepareFixture();

		$this->fixture->resetFileAndFolderNameFiltersToDefault();
		$fileList = $this->fixture->getFileList('/');

		$this->assertContains('someFile', array_keys($fileList));
		$this->assertNotContains('.someHiddenFile', array_keys($fileList));
	}

	/**
	 * Test if the default filters filter out hidden folders (like .htaccess)
	 *
	 * @test
	 */
	public function folderListingsDoNotContainHiddenFoldersByDefault() {
		$this->addToMount(array('someFolder' => array(), '.someHiddenFolder' => array()));
		$this->prepareFixture();

		$this->fixture->resetFileAndFolderNameFiltersToDefault();
		$folderList = $this->fixture->getFolderList('/');

		$this->assertContains('someFolder', array_keys($folderList));
		$this->assertNotContains('.someHiddenFolder', array_keys($folderList));
	}
}