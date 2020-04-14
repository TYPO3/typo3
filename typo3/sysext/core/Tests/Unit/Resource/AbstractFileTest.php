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

use TYPO3\CMS\Core\Resource\AbstractFile;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for the abstract file class of the TYPO3 FAL
 */
class AbstractFileTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getParentFolderGetsParentFolderFromStorage(): void
    {
        $parentIdentifier = '/parent/';
        $currentIdentifier = '/parent/current/';

        /** @var ResourceStorage|\PHPUnit\Framework\MockObject\MockObject $mockedStorageForParent */
        $mockedStorageForParent = $this->createMock(ResourceStorage::class);

        /** @var AbstractFile $parentFolderFixture */
        $parentFolderFixture = $this->getMockForAbstractClass(AbstractFile::class);
        $parentFolderFixture->setIdentifier($parentIdentifier)->setStorage($mockedStorageForParent);

        /** @var ResourceStorage|\PHPUnit\Framework\MockObject\MockObject $mockedStorage */
        $mockedStorage = $this->getMockBuilder(ResourceStorage::class)
            ->setMethods(['getFolderIdentifierFromFileIdentifier', 'getFolder'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockedStorage->expects(self::once())->method('getFolderIdentifierFromFileIdentifier')->with($currentIdentifier)->willReturn($parentIdentifier);
        $mockedStorage->expects(self::once())->method('getFolder')->with($parentIdentifier)->willReturn($parentFolderFixture);

        /** @var AbstractFile $currentFolderFixture */
        $currentFolderFixture = $this->getMockForAbstractClass(AbstractFile::class);
        $currentFolderFixture->setIdentifier($currentIdentifier)->setStorage($mockedStorage);

        self::assertSame($parentFolderFixture, $currentFolderFixture->getParentFolder());
    }

    /**
     * This test accounts for an inconsistency in the Storage–Driver interface of FAL: The driver returns the MIME
     * type in a field "mimetype", while the file object and the database table use mime_type.
     * The test is placed in the test case for AbstractFile because the broken functionality resides there, though
     * it is only triggered when constructing a File instance with an index record.
     *
     * @test
     */
    public function storageIsNotAskedForMimeTypeForPersistedRecord(): void
    {
        /** @var ResourceStorage|\PHPUnit\Framework\MockObject\MockObject $mockedStorage */
        $mockedStorage = $this->getMockBuilder(ResourceStorage::class)->disableOriginalConstructor()->getMock();
        $mockedStorage->expects(self::never())->method('getFileInfoByIdentifier')->with('/foo', 'mimetype');
        $subject = new File(['identifier' => '/foo', 'mime_type' => 'my/mime-type'], $mockedStorage);

        self::assertEquals('my/mime-type', $subject->getMimeType());
    }
}
