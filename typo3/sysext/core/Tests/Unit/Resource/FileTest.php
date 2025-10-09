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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\MetaDataAspect;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FileTest extends UnitTestCase
{
    #[Test]
    public function commonPropertiesAreAvailableWithOwnGetters(): void
    {
        $storageMock = $this->createMock(ResourceStorage::class);
        $storageMock->method('getUid')->willReturn(5);
        $properties = [
            'name' => StringUtility::getUniqueId('name_'),
            'storage' => $storageMock,
            'size' => 1024,
        ];
        $fixture = new File($properties, $storageMock);
        foreach ($properties as $key => $value) {
            self::assertEquals($value, $fixture->{'get' . $key}());
        }
    }

    #[Test]
    public function fileIndexStatusIsTrueIfUidIsSet(): void
    {
        $storageMock = $this->createMock(ResourceStorage::class);
        $storageMock->method('getUid')->willReturn(5);

        $fixture = new File(['uid' => 1], $storageMock);
        self::assertTrue($fixture->isIndexed());
    }

    #[Test]
    public function updatePropertiesUpdatesFileProperties(): void
    {
        $storageMock = $this->createMock(ResourceStorage::class);
        $storageMock->method('getUid')->willReturn(5);

        $identifier = '/' . StringUtility::getUniqueId('identifier_');
        $fixture = new File(['uid' => 1, 'identifier' => '/test'], $storageMock);
        $fixture->updateProperties(['identifier' => $identifier]);
        self::assertEquals($identifier, $fixture->getIdentifier());
    }

    #[Test]
    public function updatePropertiesLeavesPropertiesUntouchedIfNotSetInNewProperties(): void
    {
        $storageMock = $this->createMock(ResourceStorage::class);
        $storageMock->method('getUid')->willReturn(5);

        $fixture = new File(['uid' => 1, 'foo' => 'asdf', 'identifier' => '/test'], $storageMock);
        $fixture->updateProperties(['foo' => 'foobar']);
        self::assertEquals('/test', $fixture->getIdentifier());
        self::assertEquals('/test', $fixture->getProperty('identifier'));
    }

    #[Test]
    public function updatePropertiesDiscardsUidIfAlreadySet(): void
    {
        $storageMock = $this->createMock(ResourceStorage::class);
        $storageMock->method('getUid')->willReturn(5);

        $fixture = new File(['uid' => 1, 'identifier' => '/test'], $storageMock);
        $fixture->updateProperties(['uid' => 3]);
        self::assertEquals(1, $fixture->getUid());
    }

    #[Test]
    public function updatePropertiesRecordsNamesOfChangedProperties(): void
    {
        $storageMock = $this->createMock(ResourceStorage::class);
        $storageMock->method('getUid')->willReturn(5);

        $fixture = new File(['uid' => 1, 'foo' => 'asdf', 'baz' => 'fdsw', 'identifier' => '/test'], $storageMock);
        $fixture->updateProperties(['foo' => 'foobar', 'baz' => 'foobaz']);
        self::assertEquals(['foo', 'baz'], $fixture->getUpdatedProperties());
    }

    #[Test]
    public function updatePropertiesDoesNotRecordPropertyNameIfSameValueIsProvided(): void
    {
        $storageMock = $this->createMock(ResourceStorage::class);
        $storageMock->method('getUid')->willReturn(5);

        $fixture = new File(['uid' => 1, 'foo' => 'asdf', 'identifier' => '/test'], $storageMock);
        $fixture->updateProperties(['foo' => 'asdf']);
        self::assertEmpty($fixture->getUpdatedProperties());
    }

    #[Test]
    public function updatePropertiesMarksPropertyAsChangedOnlyOnce(): void
    {
        $storageMock = $this->createMock(ResourceStorage::class);
        $storageMock->method('getUid')->willReturn(5);

        $fixture = new File(['uid' => 1, 'foo' => 'asdf', 'baz' => 'fdsw', 'identifier' => '/test'], $storageMock);
        $fixture->updateProperties(['foo' => 'foobar', 'baz' => 'foobaz']);
        $fixture->updateProperties(['foo' => 'fdsw', 'baz' => 'asdf']);
        self::assertEquals(['foo', 'baz'], $fixture->getUpdatedProperties());
    }

    #[Test]
    public function updatePropertiesReloadsStorageObjectIfStorageChanges(): void
    {
        $storageMock = $this->createMock(ResourceStorage::class);
        $storageMock->method('getUid')->willReturn(5);

        $fileProperties = [
            'uid' => 1,
            'storage' => 'first',
        ];
        $subject = $this->getMockBuilder(File::class)
            ->onlyMethods([])
            ->setConstructorArgs([$fileProperties, $storageMock])
            ->getMock();
        $mockedNewStorage = $this->createMock(ResourceStorage::class);
        $mockedStorageRepository = $this->createMock(StorageRepository::class);
        $mockedStorageRepository
            ->expects($this->once())
            ->method('findByUid')
            ->willReturn($mockedNewStorage);
        GeneralUtility::addInstance(StorageRepository::class, $mockedStorageRepository);

        $subject->updateProperties(['storage' => 'different']);
        self::assertSame($mockedNewStorage, $subject->getStorage());
    }

    #[Test]
    public function copyToCallsCopyOperationOnTargetFolderStorage(): void
    {
        $storageMock = $this->createMock(ResourceStorage::class);
        $storageMock->method('getUid')->willReturn(5);

        $targetStorage = $this->createMock(ResourceStorage::class);
        $targetFolder = $this->createMock(Folder::class);
        $targetFolder->method('getStorage')->willReturn($targetStorage);
        $fixture = new File([], $storageMock);
        $targetStorage->expects($this->once())->method('copyFile')->with(self::equalTo($fixture), self::equalTo($targetFolder));
        $fixture->copyTo($targetFolder);
    }

    #[Test]
    public function moveToCallsMoveOperationOnTargetFolderStorage(): void
    {
        $storageMock = $this->createMock(ResourceStorage::class);
        $storageMock->method('getUid')->willReturn(5);

        $targetStorage = $this->createMock(ResourceStorage::class);
        $targetFolder = $this->createMock(Folder::class);
        $targetFolder->method('getStorage')->willReturn($targetStorage);
        $fixture = new File([], $storageMock);
        $targetStorage->expects($this->once())->method('moveFile')->with(self::equalTo($fixture), self::equalTo($targetFolder));
        $fixture->moveTo($targetFolder);
    }

    public static function filenameExtensionDataProvider(): array
    {
        return [
            ['somefile.jpg', 'somefile', 'jpg'],
            ['SomeFile.PNG', 'SomeFile', 'png'],
            ['somefile', 'somefile', ''],
            ['somefile.tar.gz', 'somefile.tar', 'gz'],
            ['somefile.tar.bz2', 'somefile.tar', 'bz2'],
        ];
    }

    #[DataProvider('filenameExtensionDataProvider')]
    #[Test]
    public function getNameWithoutExtensionReturnsCorrectName($originalFilename, $expectedBasename): void
    {
        $storageMock = $this->createMock(ResourceStorage::class);
        $storageMock->method('getUid')->willReturn(5);

        $fixture = new File(
            [
                'name' => $originalFilename,
                'identifier' => '/' . $originalFilename,
            ],
            $storageMock
        );
        self::assertSame($expectedBasename, $fixture->getNameWithoutExtension());
    }

    #[DataProvider('filenameExtensionDataProvider')]
    #[Test]
    public function getExtensionReturnsCorrectExtension($originalFilename, $expectedBasename, $expectedExtension): void
    {
        $storageMock = $this->createMock(ResourceStorage::class);
        $storageMock->method('getUid')->willReturn(5);

        $fixture = new File([
            'name' => $originalFilename,
            'identifier' => '/' . $originalFilename,
        ], $storageMock);
        self::assertSame($expectedExtension, $fixture->getExtension());
    }

    #[Test]
    public function hasPropertyReturnsTrueFilePropertyExists(): void
    {
        $storageMock = $this->createMock(ResourceStorage::class);
        $storageMock->method('getUid')->willReturn(5);

        $fixture = new File(['testproperty' => 'testvalue'], $storageMock);
        self::assertTrue($fixture->hasProperty('testproperty'));
    }

    #[Test]
    public function hasPropertyReturnsTrueIfMetadataPropertyExists(): void
    {
        $storageMock = $this->createMock(ResourceStorage::class);
        $storageMock->method('getUid')->willReturn(5);

        $fixture = $this->getMockBuilder(File::class)
            ->setConstructorArgs([[], $storageMock])
            ->onlyMethods(['getMetaData'])
            ->getMock();

        $metaDataAspectMock = $this->getMockBuilder(MetaDataAspect::class)
            ->setConstructorArgs([$fixture])
            ->onlyMethods(['get'])
            ->getMock();

        $metaDataAspectMock->method('get')->willReturn(['testproperty' => 'testvalue']);
        $fixture->method('getMetaData')->willReturn($metaDataAspectMock);

        self::assertTrue($fixture->hasProperty('testproperty'));
        self::assertSame('testvalue', $fixture->getProperty('testproperty'));
    }

    #[Test]
    public function getPropertiesContainsUidOfSysFileMetadata(): void
    {
        $storageMock = $this->createMock(ResourceStorage::class);
        $storageMock->method('getUid')->willReturn(5);

        $fileData = [
            'uid' => 1357,
            'name' => 'dummy.svg',
        ];
        $metaData = [
            'uid' => 2468,
            'file' => 1357,
            'title' => 'Dummy SVG',
        ];
        $file = new File($fileData, $storageMock, $metaData);

        self::assertSame(
            1357,
            $file->getProperties()['uid']
        );
        self::assertSame(
            2468,
            $file->getProperties()['metadata_uid']
        );
    }
}
