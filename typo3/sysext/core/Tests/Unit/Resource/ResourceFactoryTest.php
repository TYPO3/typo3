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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\ResourceFactory;
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
        $mockedMount = $this->createMock(\TYPO3\CMS\Core\Resource\ResourceStorage::class);
        $path = $this->getUniqueId();
        $name = $this->getUniqueId();
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
        $mockedDriver = $this->getMockForAbstractClass(\TYPO3\CMS\Core\Resource\Driver\AbstractDriver::class);
        $driverFixtureClass = get_class($mockedDriver);
        \TYPO3\CMS\Core\Utility\GeneralUtility::addInstance($driverFixtureClass, $mockedDriver);
        $mockedRegistry = $this->createMock(\TYPO3\CMS\Core\Resource\Driver\DriverRegistry::class);
        $mockedRegistry->expects(self::once())->method('getDriverClass')->with(self::equalTo($driverFixtureClass))->willReturn($driverFixtureClass);
        \TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance(\TYPO3\CMS\Core\Resource\Driver\DriverRegistry::class, $mockedRegistry);
        $obj = $this->subject->getDriverObject($driverFixtureClass, []);
        self::assertInstanceOf(\TYPO3\CMS\Core\Resource\Driver\AbstractDriver::class, $obj);
    }

    /***********************************
     *  File Handling
     ***********************************/

    /**
     * @test
     */
    public function retrieveFileOrFolderObjectCallsGetFolderObjectFromCombinedIdentifierWithRelativePath()
    {
        /** @var $subject \PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface|ResourceFactory */
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
        /** @var $subject \PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface|ResourceFactory */
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
        \TYPO3\CMS\Core\Utility\GeneralUtility::writeFileToTypo3tempDir(Environment::getPublicPath() . '/' . $filename, '42');
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
        $this->subject->_set('localDriverStorageCache', $storageConfiguration);
        self::assertSame($expectedStorageId, $this->subject->_callRef('findBestMatchingStorageByLocalPath', $path));
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
