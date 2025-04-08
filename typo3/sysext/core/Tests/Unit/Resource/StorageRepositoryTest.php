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
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\Driver\DriverRegistry;
use TYPO3\CMS\Core\Resource\LocalPath;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class StorageRepositoryTest extends UnitTestCase
{
    #[Test]
    public function getDriverObjectAcceptsDriverClassName(): void
    {
        $mockedDriver = $this->createMock(DriverInterface::class);
        $driverFixtureClass = get_class($mockedDriver);
        $registry = new DriverRegistry();
        $registry->registerDriverClass($driverFixtureClass);
        $subject = $this->getAccessibleMock(
            StorageRepository::class,
            null,
            [
                new NoopEventDispatcher(),
                $this->createMock(ConnectionPool::class),
                $registry,
                $this->createMock(FlexFormTools::class),
                new FlexFormService(),
                $this->createMock(LoggerInterface::class),
            ]
        );
        $obj = $subject->_call('getDriverObject', $driverFixtureClass, []);
        self::assertInstanceOf(DriverInterface::class, $obj);
    }

    public static function storageDetectionDataProvider(): array
    {
        $asRelativePathClosure = fn($value) => new LocalPath($value, LocalPath::TYPE_RELATIVE);
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

    #[DataProvider('storageDetectionDataProvider')]
    #[Test]
    public function findBestMatchingStorageByLocalPathReturnsDefaultStorageIfNoMatchIsFound(array $storageConfiguration, string $path, int $expectedStorageId): void
    {
        $subject = new StorageRepository(
            new NoopEventDispatcher(),
            $this->createMock(ConnectionPool::class),
            $this->createMock(DriverRegistry::class),
            $this->createMock(FlexFormTools::class),
            new FlexFormService(),
            $this->createMock(LoggerInterface::class),
        );
        $mock = \Closure::bind(static function (StorageRepository $storageRepository) use (&$path, $storageConfiguration) {
            $storageRepository->localDriverStorageCache = $storageConfiguration;
            return $storageRepository->findBestMatchingStorageByLocalPath($path);
        }, null, StorageRepository::class);
        self::assertSame($expectedStorageId, $mock($subject));
    }
}
