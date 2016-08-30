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

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceStorage;

/**
 * Testcase for the ProcessedFile class of the TYPO3 FAL
 */
class ProcessedFileTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
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
    protected $databaseRow = [];

    /**
     * @throws \PHPUnit_Framework_Exception
     */
    protected function setUp()
    {
        $this->storageMock = $this->getMock(ResourceStorage::class, [], [], '', false);
        $this->storageMock->expects($this->any())->method('getUid')->will($this->returnValue(5));

        $this->folderMock = $this->getMock(Folder::class, [], [], '', false);
        $this->folderMock->expects($this->any())->method('getStorage')->willReturn($this->storageMock);

        $this->storageMock->expects($this->any())->method('getProcessingFolder')->willReturn($this->folderMock);

        $this->databaseRow = [
            'uid' => '1234567',
            'identifier' => 'dummy.txt',
            'name' => $this->getUniqueId('dummy_'),
            'storage' => $this->storageMock->getUid(),
        ];
    }

    /**
     * @param array $dbRow
     * @param ResourceStorage $storageMock
     * @return File
     */
    protected function getFileFixture($dbRow = null, $storageMock = null)
    {
        return new File($dbRow ?: $this->databaseRow, $storageMock ?: $this->storageMock);
    }

    /**
     * @param array $dbRow
     * @param File $originalFile
     * @return ProcessedFile
     */
    protected function getProcessedFileFixture($dbRow = null, $originalFile = null)
    {
        if ($originalFile === null) {
            $originalFile = $this->getFileFixture();
        }
        return new ProcessedFile($originalFile, 'dummy', [], $dbRow ?: $this->databaseRow);
    }

    /**
     * @test
     */
    public function propertiesOfProcessedFileAreSetFromDatabaseRow()
    {
        $processedFileObject = $this->getProcessedFileFixture();
        $this->assertSame($this->databaseRow, $processedFileObject->getProperties());
    }

    /**
     * @test
     */
    public function deletingProcessedFileRemovesFile()
    {
        $this->storageMock->expects($this->once())->method('deleteFile');
        $processedDatabaseRow = $this->databaseRow;
        $processedDatabaseRow['identifier'] = 'processed_dummy.txt';
        $processedFile = $this->getProcessedFileFixture($processedDatabaseRow);
        $processedFile->delete(true);
    }

    /**
     * @test
     */
    public function deletingProcessedFileThatUsesOriginalFileDoesNotRemoveFile()
    {
        $this->storageMock->expects($this->never())->method('deleteFile');
        $processedDatabaseRow = $this->databaseRow;
        $processedDatabaseRow['identifier'] = null;
        $processedFile = $this->getProcessedFileFixture($processedDatabaseRow);
        $processedFile->delete(true);
    }
}
