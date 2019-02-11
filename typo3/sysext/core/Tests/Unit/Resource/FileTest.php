<?php
declare(strict_types = 1);
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
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Resource\MetaDataAspect;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
        $this->storageMock = $this->createMock(ResourceStorage::class);
        $this->storageMock->expects($this->any())->method('getUid')->will($this->returnValue(5));

        $mockedMetaDataRepository = $this->createMock(MetaDataRepository::class);
        $mockedMetaDataRepository->expects($this->any())->method('findByFile')->will($this->returnValue(['file' => 1]));
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
            'name' => $this->getUniqueId(),
            'storage' => $this->storageMock,
            'size' => 1024
        ];
        $fixture = new File($properties, $this->storageMock);
        foreach ($properties as $key => $value) {
            $this->assertEquals($value, call_user_func([$fixture, 'get' . $key]));
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
        $this->assertTrue($fixture->isIndexed());
    }

    /**
     * @test
     */
    public function updatePropertiesUpdatesFileProperties(): void
    {
        $identifier = '/' . $this->getUniqueId();
        $fixture = new File(['uid' => 1, 'identifier' => '/test'], $this->storageMock);
        $fixture->updateProperties(['identifier' => $identifier]);
        $this->assertEquals($identifier, $fixture->getIdentifier());
    }

    /**
     * @test
     */
    public function updatePropertiesLeavesPropertiesUntouchedIfNotSetInNewProperties(): void
    {
        $fixture = new File(['uid' => 1, 'foo' => 'asdf', 'identifier' => '/test'], $this->storageMock);
        $fixture->updateProperties(['foo' => 'foobar']);
        $this->assertEquals('/test', $fixture->getIdentifier());
        $this->assertEquals('/test', $fixture->getProperty('identifier'));
    }

    /**
     * @test
     */
    public function updatePropertiesDiscardsUidIfAlreadySet(): void
    {
        $fixture = new File(['uid' => 1, 'identifier' => '/test'], $this->storageMock);
        $fixture->updateProperties(['uid' => 3]);
        $this->assertEquals(1, $fixture->getUid());
    }

    /**
     * @test
     */
    public function updatePropertiesRecordsNamesOfChangedProperties(): void
    {
        $fixture = new File(['uid' => 1, 'foo' => 'asdf', 'baz' => 'fdsw', 'identifier' => '/test'], $this->storageMock);
        $fixture->updateProperties(['foo' => 'foobar', 'baz' => 'foobaz']);
        $this->assertEquals(['foo', 'baz'], $fixture->getUpdatedProperties());
    }

    /**
     * @test
     */
    public function updatePropertiesDoesNotRecordPropertyNameIfSameValueIsProvided(): void
    {
        $fixture = new File(['uid' => 1, 'foo' => 'asdf', 'identifier' => '/test'], $this->storageMock);
        $fixture->updateProperties(['foo' => 'asdf']);
        $this->assertEmpty($fixture->getUpdatedProperties());
    }

    /**
     * @test
     */
    public function updatePropertiesMarksPropertyAsChangedOnlyOnce(): void
    {
        $fixture = new File(['uid' => 1, 'foo' => 'asdf', 'baz' => 'fdsw', 'identifier' => '/test'], $this->storageMock);
        $fixture->updateProperties(['foo' => 'foobar', 'baz' => 'foobaz']);
        $fixture->updateProperties(['foo' => 'fdsw', 'baz' => 'asdf']);
        $this->assertEquals(['foo', 'baz'], $fixture->getUpdatedProperties());
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
        $mockedNewStorage = $this->createMock(\TYPO3\CMS\Core\Resource\ResourceStorage::class);
        $mockedResourceFactory = $this->createMock(\TYPO3\CMS\Core\Resource\ResourceFactory::class);
        $mockedResourceFactory
            ->expects($this->once())
            ->method('getStorageObject')
            ->will($this->returnValue($mockedNewStorage));
        GeneralUtility::setSingletonInstance(\TYPO3\CMS\Core\Resource\ResourceFactory::class, $mockedResourceFactory);

        $subject->updateProperties(['storage' => 'different']);
        $this->assertSame($mockedNewStorage, $subject->getStorage());
    }

    /**
     * @test
     */
    public function copyToCallsCopyOperationOnTargetFolderStorage(): void
    {
        $targetStorage = $this->createMock(\TYPO3\CMS\Core\Resource\ResourceStorage::class);
        $targetFolder = $this->createMock(\TYPO3\CMS\Core\Resource\Folder::class);
        $targetFolder->expects($this->any())->method('getStorage')->will($this->returnValue($targetStorage));
        $fixture = new File([], $this->storageMock);
        $targetStorage->expects($this->once())->method('copyFile')->with($this->equalTo($fixture), $this->equalTo($targetFolder));
        $fixture->copyTo($targetFolder);
    }

    /**
     * @test
     */
    public function moveToCallsMoveOperationOnTargetFolderStorage(): void
    {
        $targetStorage = $this->createMock(\TYPO3\CMS\Core\Resource\ResourceStorage::class);
        $targetFolder = $this->createMock(\TYPO3\CMS\Core\Resource\Folder::class);
        $targetFolder->expects($this->any())->method('getStorage')->will($this->returnValue($targetStorage));
        $fixture = new File([], $this->storageMock);
        $targetStorage->expects($this->once())->method('moveFile')->with($this->equalTo($fixture), $this->equalTo($targetFolder));
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
        $this->assertSame($expectedBasename, $fixture->getNameWithoutExtension());
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
        $this->assertSame($expectedExtension, $fixture->getExtension());
    }

    /**
     * @test
     */
    public function hasPropertyReturnsTrueFilePropertyExists(): void
    {
        $fixture = new File(['testproperty' => 'testvalue'], $this->storageMock);
        $this->assertTrue($fixture->hasProperty('testproperty'));
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

        $metaDataAspectMock->expects($this->any())->method('get')->willReturn(['testproperty' => 'testvalue']);
        $fixture->expects($this->any())->method('getMetaData')->willReturn($metaDataAspectMock);

        $this->assertTrue($fixture->hasProperty('testproperty'));
        $this->assertSame('testvalue', $fixture->getProperty('testproperty'));
    }
}
