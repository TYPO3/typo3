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

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Driver\AbstractDriver;
use TYPO3\CMS\Core\Resource\Driver\DriverRegistry;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ResourceFactoryTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var ResourceFactory
     */
    protected $subject;

    /**
     * @var array
     */
    protected $filesCreated = [];

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->getAccessibleMock(ResourceFactory::class, ['dummy'], [], '', false);
    }

    /**
     * Tear down
     */
    protected function tearDown(): void
    {
        foreach ($this->filesCreated as $file) {
            unlink($file);
        }
        parent::tearDown();
    }

    /**********************************
     * Storage Collections
     **********************************/
    /**
     * @test
     */
    public function createStorageCollectionObjectCreatesCollectionWithCorrectArguments()
    {
        $mockedMount = $this->createMock(ResourceStorage::class);
        $path = StringUtility::getUniqueId('path_');
        $name = StringUtility::getUniqueId('name_');
        $storageCollection = $this->subject->createFolderObject($mockedMount, $path, $name, 0);
        self::assertSame($mockedMount, $storageCollection->getStorage());
        self::assertEquals($path, $storageCollection->getIdentifier());
        self::assertEquals($name, $storageCollection->getName());
    }

    /**********************************
     * Drivers
     **********************************/
    /**
     * @test
     */
    public function getDriverObjectAcceptsDriverClassName()
    {
        $mockedDriver = $this->getMockForAbstractClass(AbstractDriver::class);
        $driverFixtureClass = get_class($mockedDriver);
        GeneralUtility::addInstance($driverFixtureClass, $mockedDriver);
        $mockedRegistry = $this->createMock(DriverRegistry::class);
        $mockedRegistry->expects(self::once())->method('getDriverClass')->with(self::equalTo($driverFixtureClass))->willReturn($driverFixtureClass);
        GeneralUtility::setSingletonInstance(DriverRegistry::class, $mockedRegistry);
        $obj = $this->subject->getDriverObject($driverFixtureClass, []);
        self::assertInstanceOf(AbstractDriver::class, $obj);
    }

    /***********************************
     *  File Handling
     ***********************************/

    /**
     * @test
     */
    public function retrieveFileOrFolderObjectCallsGetFolderObjectFromCombinedIdentifierWithRelativePath()
    {
        /** @var $subject \PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface|ResourceFactory */
        $subject = $this->getAccessibleMock(
            ResourceFactory::class,
            ['getFolderObjectFromCombinedIdentifier'],
            [],
            '',
            false
        );
        $subject
            ->expects(self::once())
            ->method('getFolderObjectFromCombinedIdentifier')
            ->with('typo3');
        $subject->retrieveFileOrFolderObject('typo3');
    }

    /**
     * @test
     */
    public function retrieveFileOrFolderObjectCallsGetFolderObjectFromCombinedIdentifierWithAbsolutePath()
    {
        /** @var $subject \PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface|ResourceFactory */
        $subject = $this->getAccessibleMock(
            ResourceFactory::class,
            ['getFolderObjectFromCombinedIdentifier'],
            [],
            '',
            false
        );
        $subject
            ->expects(self::once())
            ->method('getFolderObjectFromCombinedIdentifier')
            ->with('typo3');
        $subject->retrieveFileOrFolderObject(Environment::getPublicPath() . '/typo3');
    }

    /**
     * @test
     */
    public function retrieveFileOrFolderObjectReturnsFileIfPathIsGiven()
    {
        $this->subject = $this->getAccessibleMock(ResourceFactory::class, ['getFileObjectFromCombinedIdentifier'], [], '', false);
        $filename = 'typo3temp/var/tests/4711.txt';
        $this->subject->expects(self::once())
            ->method('getFileObjectFromCombinedIdentifier')
            ->with($filename);
        // Create and prepare test file
        GeneralUtility::writeFileToTypo3tempDir(Environment::getPublicPath() . '/' . $filename, '42');
        $this->filesCreated[] = Environment::getPublicPath() . '/' . $filename;
        $this->subject->retrieveFileOrFolderObject($filename);
    }

    /***********************************
     * Storage AutoDetection
     ***********************************/

    /**
     * @param array $storageConfiguration
     * @param string $path
     * @param int $expectedStorageId
     * @test
     * @dataProvider storageDetectionDataProvider
     */
    public function findBestMatchingStorageByLocalPathReturnsDefaultStorageIfNoMatchIsFound(array $storageConfiguration, $path, $expectedStorageId)
    {
        $resourceFactory = new ResourceFactory($this->prophesize(EventDispatcherInterface::class)->reveal());
        $mock = \Closure::bind(static function (ResourceFactory $resourceFactory) use (&$path, $storageConfiguration) {
            $resourceFactory->localDriverStorageCache = $storageConfiguration;
            return $resourceFactory->findBestMatchingStorageByLocalPath($path);
        }, null, ResourceFactory::class);
        self::assertSame($expectedStorageId, $mock($resourceFactory));
    }

    /**
     * @return array
     */
    public function storageDetectionDataProvider()
    {
        return [
            'NoLocalStoragesReturnDefaultStorage' => [
                [],
                'my/dummy/Image.png',
                0
            ],
            'NoMatchReturnsDefaultStorage' => [
                [1 => 'fileadmin/', 2 => 'fileadmin2/public/'],
                'my/dummy/Image.png',
                0
            ],
            'MatchReturnsTheMatch' => [
                [1 => 'fileadmin/', 2 => 'other/public/'],
                'fileadmin/dummy/Image.png',
                1
            ],
            'TwoFoldersWithSameStartReturnsCorrect' => [
                [1 => 'fileadmin/', 2 => 'fileadmin/public/'],
                'fileadmin/dummy/Image.png',
                1
            ],
            'NestedStorageReallyReturnsTheBestMatching' => [
                [1 => 'fileadmin/', 2 => 'fileadmin/public/'],
                'fileadmin/public/Image.png',
                2
            ],
            'CommonPrefixButWrongPath' => [
                [1 => 'fileadmin/', 2 => 'uploads/test/'],
                'uploads/bogus/dummy.png',
                0
            ],
            'CommonPrefixRightPath' => [
                [1 => 'fileadmin/', 2 => 'uploads/test/'],
                'uploads/test/dummy.png',
                2
            ],
            'FindStorageFromWindowsPath' => [
                [1 => 'fileadmin/', 2 => 'uploads/test/'],
                'uploads\\test\\dummy.png',
                2
            ],
        ];
    }
}
