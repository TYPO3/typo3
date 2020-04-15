<?php

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

use org\bovigo\vfs\vfsStream;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class FolderTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    protected $basedir = 'basedir';

    protected function setUp(): void
    {
        parent::setUp();
        vfsStream::setup($this->basedir);
    }

    protected function createFolderFixture($path, $name, $mockedStorage = null)
    {
        if ($mockedStorage === null) {
            $mockedStorage = $this->createMock(ResourceStorage::class);
        }
        return new Folder($mockedStorage, $path, $name);
    }

    /**
     * @test
     */
    public function constructorArgumentsAreAvailableAtRuntime()
    {
        $path = StringUtility::getUniqueId('path_');
        $name = StringUtility::getUniqueId('name_');
        $mockedStorage = $this->createMock(ResourceStorage::class);
        $fixture = $this->createFolderFixture($path, $name, $mockedStorage);
        self::assertSame($mockedStorage, $fixture->getStorage());
        self::assertStringStartsWith($path, $fixture->getIdentifier());
        self::assertSame($name, $fixture->getName());
    }

    /**
     * @test
     */
    public function propertiesCanBeUpdated()
    {
        $fixture = $this->createFolderFixture('/somePath', 'someName');
        $fixture->updateProperties(['identifier' => '/someOtherPath', 'name' => 'someNewName']);
        self::assertSame('someNewName', $fixture->getName());
        self::assertSame('/someOtherPath', $fixture->getIdentifier());
    }

    /**
     * @test
     */
    public function propertiesAreNotUpdatedIfNotSetInInput()
    {
        $fixture = $this->createFolderFixture('/somePath/someName/', 'someName');
        $fixture->updateProperties(['identifier' => '/someOtherPath']);
        self::assertSame('someName', $fixture->getName());
    }

    /**
     * @test
     */
    public function getFilesReturnsArrayWithFilenamesAsKeys()
    {
        $mockedStorage = $this->createMock(ResourceStorage::class);
        $mockedStorage->expects(self::once())->method('getFilesInFolder')->willReturn(
            [
                'somefile.png' => [
                    'name' => 'somefile.png'
                ],
                'somefile.jpg' => [
                    'name' => 'somefile.jpg'
                ]
            ]
        );
        $fixture = $this->createFolderFixture('/somePath', 'someName', $mockedStorage);

        $fileList = $fixture->getFiles();

        self::assertSame(['somefile.png', 'somefile.jpg'], array_keys($fileList));
    }

    /**
     * @test
     */
    public function getFilesHandsOverRecursiveFALSEifNotExplicitlySet()
    {
        $mockedStorage = $this->createMock(ResourceStorage::class);
        $mockedStorage
            ->expects(self::once())
            ->method('getFilesInFolder')
            ->with(self::anything(), self::anything(), self::anything(), self::anything(), false)
            ->willReturn([]);

        $fixture = $this->createFolderFixture('/somePath', 'someName', $mockedStorage);
        $fixture->getFiles();
    }

    /**
     * @test
     */
    public function getFilesHandsOverRecursiveTRUEifSet()
    {
        $mockedStorage = $this->createMock(ResourceStorage::class);
        $mockedStorage
            ->expects(self::once())
            ->method('getFilesInFolder')
            ->with(self::anything(), self::anything(), self::anything(), self::anything(), true)
            ->willReturn([]);

        $fixture = $this->createFolderFixture('/somePath', 'someName', $mockedStorage);
        $fixture->getFiles(0, 0, Folder::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS, true);
    }

    /**
     * @test
     */
    public function getSubfolderCallsFactoryWithCorrectArguments()
    {
        $mockedStorage = $this->createMock(ResourceStorage::class);
        $mockedStorage->expects(self::once())->method('hasFolderInFolder')->with(self::equalTo('someSubfolder'))->willReturn(true);
        /** @var \PHPUnit\Framework\MockObject\MockObject|\TYPO3\CMS\Core\Resource\ResourceFactory $mockedFactory */
        $mockedFactory = $this->createMock(ResourceFactory::class);
        $folderFixture = $this->createFolderFixture(
            '/somePath/someFolder/',
            'someFolder',
            $mockedStorage
        );
        $subfolderFixture = $this->createFolderFixture(
            '/somePath/someSubfolder/',
            'someSubfolder',
            $mockedStorage
        );
        $mockedStorage->expects(self::once())->method('getFolderInFolder')->willReturn($subfolderFixture);
        GeneralUtility::setSingletonInstance(
            ResourceFactory::class,
            $mockedFactory
        );
        self::assertEquals($subfolderFixture, $folderFixture->getSubfolder('someSubfolder'));
    }

    /**
     * @test
     */
    public function getParentFolderGetsParentFolderFromStorage()
    {
        $parentIdentifier = '/parent/';
        $currentIdentifier = '/parent/current/';

        $parentFolderFixture = $this->createFolderFixture($parentIdentifier, 'parent');
        $mockedStorage = $this->getMockBuilder(ResourceStorage::class)
            ->setMethods(['getFolderIdentifierFromFileIdentifier', 'getFolder'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockedStorage->expects(self::once())->method('getFolderIdentifierFromFileIdentifier')->with($currentIdentifier)->willReturn($parentIdentifier);
        $mockedStorage->expects(self::once())->method('getFolder')->with($parentIdentifier)->willReturn($parentFolderFixture);

        $currentFolderFixture = $this->createFolderFixture($currentIdentifier, 'current', $mockedStorage);

        self::assertSame($parentFolderFixture, $currentFolderFixture->getParentFolder());
    }
}
