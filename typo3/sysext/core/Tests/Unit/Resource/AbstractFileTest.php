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
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Tests\Unit\Resource\Fixtures\TestingFile;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for the abstract file class of the TYPO3 FAL
 */
final class AbstractFileTest extends UnitTestCase
{
    #[Test]
    public function getParentFolderGetsParentFolderFromStorage(): void
    {
        $parentIdentifier = '/parent/';
        $currentIdentifier = '/parent/current/';

        $mockedStorageForParent = $this->createMock(ResourceStorage::class);

        $parentFolderFixture = $this->createMock(Folder::class);
        $parentFolderFixture->method('getStorage')->willReturn($mockedStorageForParent);

        $mockedStorage = $this->getMockBuilder(ResourceStorage::class)
            ->onlyMethods(['getFolderIdentifierFromFileIdentifier', 'getFolder'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockedStorage->expects($this->once())->method('getFolderIdentifierFromFileIdentifier')->with($currentIdentifier)->willReturn($parentIdentifier);
        $mockedStorage->expects($this->once())->method('getFolder')->with($parentIdentifier)->willReturn($parentFolderFixture);

        $currentFolderFixture = new TestingFile();
        $currentFolderFixture->setIdentifier($currentIdentifier)->setStorage($mockedStorage);

        self::assertSame($parentFolderFixture, $currentFolderFixture->getParentFolder());
    }

    /**
     * This test accounts for an inconsistency in the Storage–Driver interface of FAL: The driver returns the MIME
     * type in a field "mimetype", while the file object and the database table use mime_type.
     * The test is placed in the test case for AbstractFile because the broken functionality resides there, though
     * it is only triggered when constructing a File instance with an index record.
     */
    #[Test]
    public function storageIsNotAskedForMimeTypeForPersistedRecord(): void
    {
        $mockedStorage = $this->getMockBuilder(ResourceStorage::class)->disableOriginalConstructor()->getMock();
        $mockedStorage->expects($this->never())->method('getFileInfoByIdentifier')->with('/foo', 'mimetype');
        $subject = new File(['identifier' => '/foo', 'mime_type' => 'my/mime-type'], $mockedStorage);

        self::assertEquals('my/mime-type', $subject->getMimeType());
    }
}
