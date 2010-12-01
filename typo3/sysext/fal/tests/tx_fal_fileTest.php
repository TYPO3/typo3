<?php
/***************************************************************
* Copyright notice
*
* (c) 2010 Andreas Wolf <andreas.wolf@ikt-werk.de>
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
 * Testcase for the tx_fal_File class in the TYPO3 Core.
 *
 * @package TYPO3
 * @subpackage t3lib
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 */
class t3lib_file_fileTest extends tx_phpunit_testcase {

	/**
	 * @test
	 */
	public function constructorStoresFileData() {
		// pseudo file data from the database
		$fileData = array(
			'uid' => 1,
			'file_name' => 'test.jpg',
			'file_path' => 'just/a/random/testfolder/',
			'file_size' => 1234,
			'file_hash' => sha1(uniqid())
		);
		$mockMount = $this->getMock('tx_fal_Mount');

		$fileObject = new tx_fal_File($mockMount, $fileData);

		$this->assertEquals($fileData['uid'], $fileObject->getUid(), 'File uid was not stored correctly.');
		$this->assertEquals($fileData['file_name'], $fileObject->getName(), 'File name was not stored correctly.');
		$this->assertEquals($fileData['file_size'], $fileObject->getSize(), 'File size was not stored correctly.');
		$this->assertEquals($fileData['file_path'], $fileObject->getPath(), 'Node path was not stored correctly.');
		$this->assertEquals($fileData['file_hash'], $fileObject->getHash(), 'Node path was not stored correctly.');
	}

	/**
	 * @test
	 */
	public function moveInsideMountQueriesStorageBackendWithCorrectPaths() {
		// pseudo file data from the database
		$fileData = array(
			'uid' => 1,
			'file_name' => 'test.jpg',
			'file_path' => 'just/a/random/testfolder/'
		);
		$newPath = 'just/some/other/folder/' . uniqid() . '/test2.jpg';
		$mockedMount = $this->getMock('tx_fal_Mount');
		$mockedStorage = $this->getMock('tx_fal_storage_Interface');
		$mockedStorage->expects($this->once())->method('moveFile')->with(
			$this->equalTo($fileData['file_path'] . $fileData['file_name']),
			$this->equalTo($newPath)
		);
		$mockedMount->expects($this->any())->method('getStorageBackend')->will($this->returnValue($mockedStorage));

		$fileObject = new tx_fal_File($mockedMount, $fileData);

		$fileObject->moveInsideMount($newPath);
	}

	/**
	 * @test
	 */
	public function renameUsesOldPathForNewFile() {
		// pseudo file data from the database
		$fileData = array(
			'uid' => 1,
			'file_name' => 'test.jpg',
			'file_path' => 'just/a/random/testfolder/'
		);
		$newName = uniqid();
		$mockedMount = $this->getMock('tx_fal_Mount');
		$mockedStorage = $this->getMock('tx_fal_storage_Interface');
		$mockedStorage->expects($this->once())->method('moveFile')->with(
			$this->equalTo($fileData['file_path'] . $fileData['file_name']),
			$this->equalTo($fileData['file_path'] . $newName)
		);
		$mockedMount->expects($this->any())->method('getStorageBackend')->will($this->returnValue($mockedStorage));

		$fileObject = new tx_fal_File($mockedMount, $fileData);

		$fileObject->rename($newName);
	}
}
?>