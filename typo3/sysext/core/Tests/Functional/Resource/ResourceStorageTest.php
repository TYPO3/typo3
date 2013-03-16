<?php
namespace TYPO3\CMS\Core\Tests\Functional\Resource;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Andreas Wolf <andreas.wolf@ikt-werk.de>
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

/**
 * Functional test case for the FAL Storage.
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 */
class ResourceStorageTest extends \TYPO3\CMS\Core\Tests\Unit\Resource\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Resource\ResourceStorage
	 */
	protected $fixture;

	protected function prepareFixture() {
		$this->initializeVfs();
		$driver = new \TYPO3\CMS\Core\Resource\Driver\LocalDriver(array('pathType' => 'absolute', 'basePath' => $this->getMountRootUrl()));
		$driver->initialize();
		$this->fixture = new \TYPO3\CMS\Core\Resource\ResourceStorage($driver, array());
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

?>