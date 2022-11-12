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

use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Resource\Driver\AbstractDriver;
use TYPO3\CMS\Core\Resource\Driver\DriverRegistry;
use TYPO3\CMS\Core\Resource\LocalPath;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class StorageRepositoryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getDriverObjectAcceptsDriverClassName(): void
    {
        $mockedDriver = $this->getMockForAbstractClass(AbstractDriver::class);
        $driverFixtureClass = get_class($mockedDriver);
        $registry = new DriverRegistry();
        $registry->registerDriverClass($driverFixtureClass);
        $subject = $this->getAccessibleMock(
            StorageRepository::class,
            ['dummy'],
            [
                new NoopEventDispatcher(),
                $registry,
            ]
        );
        $obj = $subject->_call('getDriverObject', $driverFixtureClass, []);
        self::assertInstanceOf(AbstractDriver::class, $obj);
    }

    public function storageDetectionDataProvider(): array
    {
        $asRelativePathClosure = fn ($value) => new LocalPath($value, LocalPath::TYPE_RELATIVE);
        return [
            'NoLocalStoragesReturnDefaultStorage' => [
                [],
                'my/dummy/Image.png',
                0,
            ],
            'NoMatchReturnsDefaultStorage' => [
                array_map($asRelativePathClosure, [1 => 'fileadmin/', 2 => 'fileadmin2/public/']),
                'my/dummy/Image.png',
                0,
            ],
            'MatchReturnsTheMatch' => [
                array_map($asRelativePathClosure, [1 => 'fileadmin/', 2 => 'other/public/']),
                'fileadmin/dummy/Image.png',
                1,
            ],
            'TwoFoldersWithSameStartReturnsCorrect' => [
                array_map($asRelativePathClosure, [1 => 'fileadmin/', 2 => 'fileadmin/public/']),
                'fileadmin/dummy/Image.png',
                1,
            ],
            'NestedStorageReallyReturnsTheBestMatching' => [
                array_map($asRelativePathClosure, [1 => 'fileadmin/', 2 => 'fileadmin/public/']),
                'fileadmin/public/Image.png',
                2,
            ],
            'CommonPrefixButWrongPath' => [
                array_map($asRelativePathClosure, [1 => 'fileadmin/', 2 => 'uploads/test/']),
                'uploads/bogus/dummy.png',
                0,
            ],
            'CommonPrefixRightPath' => [
                array_map($asRelativePathClosure, [1 => 'fileadmin/', 2 => 'uploads/test/']),
                'uploads/test/dummy.png',
                2,
            ],
            'FindStorageFromWindowsPath' => [
                array_map($asRelativePathClosure, [1 => 'fileadmin/', 2 => 'uploads/test/']),
                'uploads\\test\\dummy.png',
                2,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider storageDetectionDataProvider
     */
    public function findBestMatchingStorageByLocalPathReturnsDefaultStorageIfNoMatchIsFound(array $storageConfiguration, string $path, int $expectedStorageId): void
    {
        $subject = new StorageRepository(
            new NoopEventDispatcher(),
            $this->createMock(DriverRegistry::class)
        );
        $mock = \Closure::bind(static function (StorageRepository $storageRepository) use (&$path, $storageConfiguration) {
            $storageRepository->localDriverStorageCache = $storageConfiguration;
            return $storageRepository->findBestMatchingStorageByLocalPath($path);
        }, null, StorageRepository::class);
        self::assertSame($expectedStorageId, $mock($subject));
    }
}
