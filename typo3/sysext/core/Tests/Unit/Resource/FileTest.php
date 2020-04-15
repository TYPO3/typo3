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
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Resource\MetaDataAspect;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for the file class of the TYPO3 FAL
 */
class FileTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var ResourceStorage
     */
    protected $storageMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storageMock = $this->createMock(ResourceStorage::class);
        $this->storageMock->expects(self::any())->method('getUid')->willReturn(5);

        $mockedMetaDataRepository = $this->createMock(MetaDataRepository::class);
        $mockedMetaDataRepository->expects(self::any())->method('findByFile')->willReturn(['file' => 1]);
        GeneralUtility::setSingletonInstance(MetaDataRepository::class, $mockedMetaDataRepository);
    }

    /**
     * @return File
     */
    protected function prepareFixture(): File
    {
        $fixture = new File(['testfile'], $this->storageMock);
        return $fixture;
    }

    /**
     * @test
     */
    public function commonPropertiesAreAvailableWithOwnGetters(): void
    {
        $properties = [
            'name' => StringUtility::getUniqueId('name_'),
            'storage' => $this->storageMock,
            'size' => 1024
        ];
        $fixture = new File($properties, $this->storageMock);
        foreach ($properties as $key => $value) {
            self::assertEquals($value, call_user_func([$fixture, 'get' . $key]));
        }
    }

    /**
     * Tests if a file is seen as indexed if the record has a uid
     *
     * @test
     */
    public function fileIndexStatusIsTrueIfUidIsSet(): void
    {
        $fixture = new File(['uid' => 1], $this->storageMock);
        self::assertTrue($fixture->isIndexed());
    }

    /**
     * @test
     */
    public function updatePropertiesUpdatesFileProperties(): void
    {
        $identifier = '/' . StringUtility::getUniqueId('identifier_');
        $fixture = new File(['uid' => 1, 'identifier' => '/test'], $this->storageMock);
        $fixture->updateProperties(['identifier' => $identifier]);
        self::assertEquals($identifier, $fixture->getIdentifier());
    }

    /**
     * @test
     */
    public function updatePropertiesLeavesPropertiesUntouchedIfNotSetInNewProperties(): void
    {
        $fixture = new File(['uid' => 1, 'foo' => 'asdf', 'identifier' => '/test'], $this->storageMock);
        $fixture->updateProperties(['foo' => 'foobar']);
        self::assertEquals('/test', $fixture->getIdentifier());
        self::assertEquals('/test', $fixture->getProperty('identifier'));
    }

    /**
     * @test
     */
    public function updatePropertiesDiscardsUidIfAlreadySet(): void
    {
        $fixture = new File(['uid' => 1, 'identifier' => '/test'], $this->storageMock);
        $fixture->updateProperties(['uid' => 3]);
        self::assertEquals(1, $fixture->getUid());
    }

    /**
     * @test
     */
    public function updatePropertiesRecordsNamesOfChangedProperties(): void
    {
        $fixture = new File(['uid' => 1, 'foo' => 'asdf', 'baz' => 'fdsw', 'identifier' => '/test'], $this->storageMock);
        $fixture->updateProperties(['foo' => 'foobar', 'baz' => 'foobaz']);
        self::assertEquals(['foo', 'baz'], $fixture->getUpdatedProperties());
    }

    /**
     * @test
     */
    public function updatePropertiesDoesNotRecordPropertyNameIfSameValueIsProvided(): void
    {
        $fixture = new File(['uid' => 1, 'foo' => 'asdf', 'identifier' => '/test'], $this->storageMock);
        $fixture->updateProperties(['foo' => 'asdf']);
        self::assertEmpty($fixture->getUpdatedProperties());
    }

    /**
     * @test
     */
    public function updatePropertiesMarksPropertyAsChangedOnlyOnce(): void
    {
        $fixture = new File(['uid' => 1, 'foo' => 'asdf', 'baz' => 'fdsw', 'identifier' => '/test'], $this->storageMock);
        $fixture->updateProperties(['foo' => 'foobar', 'baz' => 'foobaz']);
        $fixture->updateProperties(['foo' => 'fdsw', 'baz' => 'asdf']);
        self::assertEquals(['foo', 'baz'], $fixture->getUpdatedProperties());
    }

    /**
     * @test
     */
    public function updatePropertiesReloadsStorageObjectIfStorageChanges(): void
    {
        $fileProperties = [
            'uid' => 1,
            'storage' => 'first',
        ];
        $subject = $this->getMockBuilder(File::class)
            ->setMethods(['loadStorage'])
            ->setConstructorArgs([$fileProperties, $this->storageMock])
            ->getMock();
        $mockedNewStorage = $this->createMock(ResourceStorage::class);
        $mockedResourceFactory = $this->createMock(ResourceFactory::class);
        $mockedResourceFactory
            ->expects(self::once())
            ->method('getStorageObject')
            ->willReturn($mockedNewStorage);
        GeneralUtility::setSingletonInstance(ResourceFactory::class, $mockedResourceFactory);

        $subject->updateProperties(['storage' => 'different']);
        self::assertSame($mockedNewStorage, $subject->getStorage());
    }

    /**
     * @test
     */
    public function copyToCallsCopyOperationOnTargetFolderStorage(): void
    {
        $targetStorage = $this->createMock(ResourceStorage::class);
        $targetFolder = $this->createMock(Folder::class);
        $targetFolder->expects(self::any())->method('getStorage')->willReturn($targetStorage);
        $fixture = new File([], $this->storageMock);
        $targetStorage->expects(self::once())->method('copyFile')->with(self::equalTo($fixture), self::equalTo($targetFolder));
        $fixture->copyTo($targetFolder);
    }

    /**
     * @test
     */
    public function moveToCallsMoveOperationOnTargetFolderStorage(): void
    {
        $targetStorage = $this->createMock(ResourceStorage::class);
        $targetFolder = $this->createMock(Folder::class);
        $targetFolder->expects(self::any())->method('getStorage')->willReturn($targetStorage);
        $fixture = new File([], $this->storageMock);
        $targetStorage->expects(self::once())->method('moveFile')->with(self::equalTo($fixture), self::equalTo($targetFolder));
        $fixture->moveTo($targetFolder);
    }

    public function filenameExtensionDataProvider(): array
    {
        return [
            ['somefile.jpg', 'somefile', 'jpg'],
            ['SomeFile.PNG', 'SomeFile', 'png'],
            ['somefile', 'somefile', ''],
            ['somefile.tar.gz', 'somefile.tar', 'gz'],
            ['somefile.tar.bz2', 'somefile.tar', 'bz2'],
        ];
    }

    /**
     * @test
     * @dataProvider filenameExtensionDataProvider
     */
    public function getNameWithoutExtensionReturnsCorrectName($originalFilename, $expectedBasename): void
    {
        $fixture = new File(
            [
            'name' => $originalFilename,
            'identifier' => '/' . $originalFilename
        ],
            $this->storageMock
        );
        self::assertSame($expectedBasename, $fixture->getNameWithoutExtension());
    }

    /**
     * @test
     * @dataProvider filenameExtensionDataProvider
     */
    public function getExtensionReturnsCorrectExtension($originalFilename, $expectedBasename, $expectedExtension): void
    {
        $fixture = new File([
            'name' => $originalFilename,
            'identifier' => '/' . $originalFilename
        ], $this->storageMock);
        self::assertSame($expectedExtension, $fixture->getExtension());
    }

    /**
     * @test
     */
    public function hasPropertyReturnsTrueFilePropertyExists(): void
    {
        $fixture = new File(['testproperty' => 'testvalue'], $this->storageMock);
        self::assertTrue($fixture->hasProperty('testproperty'));
    }

    /**
     * @test
     */
    public function hasPropertyReturnsTrueIfMetadataPropertyExists(): void
    {
        $fixture = $this->getMockBuilder(File::class)
            ->setConstructorArgs([[], $this->storageMock])
            ->setMethods(['getMetaData'])
            ->getMock();

        $metaDataAspectMock = $this->getMockBuilder(MetaDataAspect::class)
            ->setConstructorArgs([$fixture])
            ->setMethods(['get'])
            ->getMock();

        $metaDataAspectMock->expects(self::any())->method('get')->willReturn(['testproperty' => 'testvalue']);
        $fixture->expects(self::any())->method('getMetaData')->willReturn($metaDataAspectMock);

        self::assertTrue($fixture->hasProperty('testproperty'));
        self::assertSame('testvalue', $fixture->getProperty('testproperty'));
    }
}
