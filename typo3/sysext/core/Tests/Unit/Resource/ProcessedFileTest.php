<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Tests\Unit\Resource;

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for the ProcessedFile class of the TYPO3 FAL
 */
class ProcessedFileTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Folder
     */
    protected $folderMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ResourceStorage
     */
    protected $storageMock;

    /**
     * @var array
     */
    protected $databaseRow = [];

    /**
     * @throws \PHPUnit\Framework\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->storageMock = $this->createMock(ResourceStorage::class);
        $this->storageMock->expects(self::any())->method('getUid')->willReturn(5);

        $this->folderMock = $this->createMock(Folder::class);
        $this->folderMock->expects(self::any())->method('getStorage')->willReturn($this->storageMock);

        $this->storageMock->expects(self::any())->method('getProcessingFolder')->willReturn($this->folderMock);

        $this->databaseRow = [
            'uid' => '1234567',
            'identifier' => 'dummy.txt',
            'name' => StringUtility::getUniqueId('dummy_'),
            'storage' => $this->storageMock->getUid(),
            'configuration' => null,
            'originalfilesha1' => null,
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
        self::assertSame($this->databaseRow, $processedFileObject->getProperties());
    }

    /**
     * @test
     */
    public function deletingProcessedFileRemovesFile()
    {
        $this->storageMock->expects(self::once())->method('deleteFile');
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
        $this->storageMock->expects(self::never())->method('deleteFile');
        $processedDatabaseRow = $this->databaseRow;
        $processedDatabaseRow['identifier'] = null;
        $processedFile = $this->getProcessedFileFixture($processedDatabaseRow);
        $processedFile->delete(true);
    }
}
