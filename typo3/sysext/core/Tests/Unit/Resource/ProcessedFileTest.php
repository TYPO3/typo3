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

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for the ProcessedFile class of the TYPO3 FAL
 */
final class ProcessedFileTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected Folder&MockObject $folderMock;

    protected ResourceStorage&MockObject $storageMock;

    protected array $databaseRow = [];

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->storageMock = $this->createMock(ResourceStorage::class);
        $this->storageMock->method('getUid')->willReturn(5);
        $this->storageMock->method('hashFile')->willReturn('');
        $this->storageMock->method('deleteFile')->willReturn(true);

        $this->folderMock = $this->createMock(Folder::class);
        $this->folderMock->method('getStorage')->willReturn($this->storageMock);

        $this->storageMock->method('getProcessingFolder')->willReturn($this->folderMock);

        $this->databaseRow = [
            'uid' => '1234567',
            'identifier' => 'dummy.txt',
            'name' => StringUtility::getUniqueId('dummy_'),
            'storage' => $this->storageMock->getUid(),
            'configuration' => null,
            'originalfilesha1' => null,
        ];
    }

    protected function getFileFixture(?array $dbRow = null, ?ResourceStorage $storageMock = null): File
    {
        return new File($dbRow ?: $this->databaseRow, $storageMock ?: $this->storageMock);
    }

    protected function getProcessedFileFixture(?array $dbRow = null, ?File $originalFile = null): ProcessedFile
    {
        if ($originalFile === null) {
            $originalFile = $this->getFileFixture();
        }
        return new ProcessedFile($originalFile, 'dummy', [], $dbRow ?: $this->databaseRow);
    }

    #[Test]
    public function propertiesOfProcessedFileAreSetFromDatabaseRow(): void
    {
        $processedFileObject = $this->getProcessedFileFixture();
        self::assertSame($this->databaseRow, $processedFileObject->getProperties());
    }

    #[Test]
    public function deletingProcessedFileRemovesFile(): void
    {
        $this->storageMock->expects($this->once())->method('deleteFile');
        $processedDatabaseRow = $this->databaseRow;
        $processedDatabaseRow['identifier'] = 'processed_dummy.txt';
        $processedFile = $this->getProcessedFileFixture($processedDatabaseRow);
        $processedFile->delete(true);
    }

    #[Test]
    public function deletingProcessedFileThatUsesOriginalFileDoesNotRemoveFile(): void
    {
        $this->storageMock->expects($this->never())->method('deleteFile');
        $processedDatabaseRow = $this->databaseRow;
        $processedDatabaseRow['identifier'] = null;
        $processedFile = $this->getProcessedFileFixture($processedDatabaseRow);
        $processedFile->delete(true);
    }
}
