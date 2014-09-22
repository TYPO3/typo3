<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;

/**
 * Testcase for the ProcessedFile class of the TYPO3 FAL
 */
class ProcessedFileTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|Folder
	 */
	protected $folderMock;

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|ResourceStorage
	 */
	protected $storageMock;

	/**
	 * @var array
	 */
	protected $databaseRow = array();

	/**
	 * @throws \PHPUnit_Framework_Exception
	 */
	protected function setUp() {
		$this->storageMock = $this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', array(), array(), '', FALSE);
		$this->storageMock->expects($this->any())->method('getUid')->will($this->returnValue(5));

		$this->folderMock = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Folder', array(), array(), '', FALSE);
		$this->folderMock->expects($this->any())->method('getStorage')->willReturn($this->storageMock);

		$this->storageMock->expects($this->any())->method('getProcessingFolder')->willReturn($this->folderMock);

		$this->databaseRow = array(
			'uid' => '1234567',
			'identifier' => 'dummy.txt',
			'name' => $this->getUniqueId('dummy_'),
			'storage' => $this->storageMock->getUid(),
		);
	}

	/**
	 * @param array $dbRow
	 * @param ResourceStorage $storageMock
	 * @return File
	 */
	protected function getFileFixture($dbRow = NULL, $storageMock = NULL) {
		return new File($dbRow ?: $this->databaseRow, $storageMock ?: $this->storageMock);
	}

	/**
	 * @param array $dbRow
	 * @param File $originalFile
	 * @return ProcessedFile
	 */
	protected function getProcessedFileFixture($dbRow = NULL, $originalFile = NULL) {
		if ($originalFile === NULL) {
			$originalFile = $this->getFileFixture();
		}
		return new ProcessedFile($originalFile, 'dummy', array(), $dbRow ?: $this->databaseRow);
	}

	/**
	 * @test
	 */
	public function propertiesOfProcessedFileAreSetFromDatabaseRow() {
		$processedFileObject = $this->getProcessedFileFixture();
		$this->assertSame($this->databaseRow, $processedFileObject->getProperties());
	}

	/**
	 * @test
	 */
	public function deletingProcessedFileRemovesFile() {
		$this->storageMock->expects($this->once())->method('deleteFile');
		$processedDatabaseRow = $this->databaseRow;
		$processedDatabaseRow['identifier'] = 'processed_dummy.txt';
		$processedFile = $this->getProcessedFileFixture($processedDatabaseRow);
		$processedFile->delete(TRUE);
	}

	/**
	 * @test
	 */
	public function deletingProcessedFileThatUsesOriginalFileDoesNotRemoveFile() {
		$this->storageMock->expects($this->never())->method('deleteFile');
		$processedDatabaseRow = $this->databaseRow;
		$processedDatabaseRow['identifier'] = NULL;
		$processedFile = $this->getProcessedFileFixture($processedDatabaseRow);
		$processedFile->delete(TRUE);
	}
}
