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
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Resource\Exception\InvalidUidException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Resource\MetaDataAspect;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class MetaDataAspectTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;
    protected ResourceStorage&MockObject $storageMock;

    protected function setUp(): void
    {
        parent::setUp();
        $metaDataRepository = new MetaDataRepository(new NoopEventDispatcher());
        GeneralUtility::setSingletonInstance(MetaDataRepository::class, $metaDataRepository);
        $this->storageMock = $this->createMock(ResourceStorage::class);
        $this->storageMock->method('getUid')->willReturn(12);
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    #[Test]
    public function knownMetaDataIsAdded(): void
    {
        $metaData = [
            'width' => 4711,
            'title' => 'Lorem ipsum meta sit amet',
        ];
        $file = new File([], $this->storageMock, $metaData);

        self::assertSame($metaData, $file->getMetaData()->get());
    }

    #[Test]
    public function manuallyAddedMetaDataIsMerged(): void
    {
        $metaData = [
            'width' => 4711,
            'title' => 'Lorem ipsum meta sit amet',
        ];
        $file = new File([], $this->storageMock, $metaData);
        $file->getMetaData()->add([
            'height' => 900,
            'description' => 'This file is presented by TYPO3',
        ]);

        $expected = [
            'width' => 4711,
            'title' => 'Lorem ipsum meta sit amet',
            'height' => 900,
            'description' => 'This file is presented by TYPO3',
        ];

        self::assertSame($expected, $file->getMetaData()->get());
    }

    #[Test]
    public function metaDataGetsRemoved(): void
    {
        $metaData = ['foo' => 'bar'];

        $file = new File(['uid' => 12], $this->storageMock);

        $metaDataAspectMock = $this->getMockBuilder(MetaDataAspect::class)
            ->setConstructorArgs([$file])
            ->onlyMethods(['getMetaDataRepository'])
            ->getMock();

        $metaDataAspectMock->add($metaData);
        $metaDataAspectMock->remove();

        self::assertEmpty($metaDataAspectMock->get());
    }

    #[Test]
    public function positiveUidOfFileIsExpectedToLoadMetaData(): void
    {
        $this->expectException(InvalidUidException::class);
        $this->expectExceptionCode(1381590731);

        $file = new File(['uid' => -3], $this->storageMock);
        $file->getMetaData()->get();
    }

    #[Test]
    public function newMetaDataIsCreated(): void
    {
        $GLOBALS['EXEC_TIME'] = 1534530781;
        $metaData = [
            'title' => 'Hooray',
            // This value is ignored on purpose, we simulate the non-existence of the field "description"
            'description' => 'Yipp yipp yipp',
        ];

        $file = new File(['uid' => 12], $this->storageMock);

        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->method('insert')->with(self::anything())->willReturn(1);
        $connectionMock->method('lastInsertId')->willReturn('5');
        $connectionPoolMock = $this->createMock(ConnectionPool::class);
        $connectionPoolMock->method('getConnectionForTable')->with(self::anything())->willReturn($connectionMock);
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolMock);

        $metaDataRepositoryMock = $this->getMockBuilder(MetaDataRepository::class)
            ->onlyMethods(['findByFileUid', 'getTableFields', 'update'])
            ->setConstructorArgs([new NoopEventDispatcher()])
            ->getMock();
        $metaDataRepositoryMock->method('findByFileUid')->willReturn([]);
        $metaDataRepositoryMock->method('getTableFields')->willReturn(['title' => 'sometype']);
        $metaDataRepositoryMock->expects($this->never())->method('update');
        GeneralUtility::setSingletonInstance(MetaDataRepository::class, $metaDataRepositoryMock);

        $file->getMetaData()->add($metaData)->save();

        $expected = [
            'file' => $file->getUid(),
            'pid' => 0,
            'crdate' => 1534530781,
            'tstamp' => 1534530781,
            'l10n_diffsource' => '',
            'title' => 'Hooray',
            'uid' => '5',
        ];

        self::assertSame($expected, $file->getMetaData()->get());
    }

    #[Test]
    public function existingMetaDataGetsUpdated(): void
    {
        $metaData = ['uid' => 12, 'foo' => 'bar'];
        $updatedMetadata = array_merge($metaData, ['testproperty' => 'testvalue']);

        $file = new File(['uid' => 12], $this->storageMock);

        $eventDispatcherMock = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
        $eventDispatcherMock->expects($this->atLeastOnce())->method('dispatch')->with(self::anything())->willReturnArgument(0);

        $metaDataRepositoryMock = $this->getMockBuilder(MetaDataRepository::class)
            ->onlyMethods(['createMetaDataRecord', 'getTableFields'])
            ->setConstructorArgs([$eventDispatcherMock])
            ->getMock();

        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->method('update')->with('sys_file_metadata', self::anything())->willReturn(1);
        $connectionPoolMock = $this->createMock(ConnectionPool::class);
        $connectionPoolMock->method('getConnectionForTable')->with(self::anything())->willReturn($connectionMock);
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolMock);

        $metaDataRepositoryMock->method('createMetaDataRecord')->willReturn($metaData);
        $metaDataRepositoryMock->method('getTableFields')->willReturn(array_flip(['foo', 'testproperty']));
        GeneralUtility::setSingletonInstance(MetaDataRepository::class, $metaDataRepositoryMock);

        $metaDataAspectMock = $this->getMockBuilder(MetaDataAspect::class)
            ->setConstructorArgs([$file])
            ->onlyMethods(['loadFromRepository'])
            ->getMock();

        $metaDataAspectMock->method('loadFromRepository')->willReturn([], $metaData, $updatedMetadata);
        $metaDataAspectMock->add($metaData)->save();
        $metaDataAspectMock->add(['testproperty' => 'testvalue'])->save();

        self::assertSame('bar', $metaDataAspectMock->offsetGet('foo'));
        self::assertSame('testvalue', $metaDataAspectMock->offsetGet('testproperty'));

        $metaDataAspectMock->add($updatedMetadata)->save();
        self::assertFalse($metaDataAspectMock->offsetExists('tstamp'));
    }

    public static function propertyDataProvider(): array
    {
        return [
            [
                [
                    'width' => 4711,
                    'title' => 'Lorem ipsum meta sit amet',
                ],
                [
                    'property' => 'width',
                    'expected' => true,
                ],
                [
                    'property' => 'width',
                    'expected' => 4711,
                ],
            ],
            [
                [
                    'foo' => 'bar',
                ],
                [
                    'property' => 'husel',
                    'expected' => false,
                ],
                [
                    'property' => 'husel',
                    'expected' => null,
                ],
            ],
        ];
    }

    #[DataProvider('propertyDataProvider')]
    #[Test]
    public function propertyIsFetchedProperly(array $metaData, array $has, array $get): void
    {
        $file = new File([], $this->storageMock, $metaData);

        self::assertSame($has['expected'], isset($file->getMetaData()[$has['property']]));
        self::assertSame($get['expected'], $file->getMetaData()[$get['property']] ?? null);
    }
}
