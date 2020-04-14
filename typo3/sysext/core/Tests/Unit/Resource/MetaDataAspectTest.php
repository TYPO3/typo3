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

use Prophecy\Argument;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Exception\InvalidUidException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Resource\MetaDataAspect;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class MetaDataAspectTest extends UnitTestCase
{
    /**
     * @var ResourceStorage|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $storageMock;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(Argument::cetera())->willReturnArgument(0);
        $metaDataRepository = new MetaDataRepository($eventDispatcher->reveal());
        GeneralUtility::setSingletonInstance(MetaDataRepository::class, $metaDataRepository);
        $this->storageMock = $this->createMock(ResourceStorage::class);
        $this->storageMock->expects(self::any())->method('getUid')->willReturn(12);
    }

    /**
     * Tear down
     */
    protected function tearDown(): void
    {
        $this->resetSingletonInstances = true;

        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function knownMetaDataIsAdded(): void
    {
        $metaData = [
            'width' => 4711,
            'title' => 'Lorem ipsum meta sit amet',
        ];
        $file = new File([], $this->storageMock, $metaData);

        self::assertSame($metaData, $file->getMetaData()->get());
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function metaDataGetsRemoved(): void
    {
        $metaData = ['foo' => 'bar'];

        $file = new File(['uid' => 12], $this->storageMock);

        /** @var MetaDataAspect|\PHPUnit\Framework\MockObject\MockObject $metaDataAspectMock */
        $metaDataAspectMock = $this->getMockBuilder(MetaDataAspect::class)
            ->setConstructorArgs([$file])
            ->setMethods(['getMetaDataRepository'])
            ->getMock();

        $metaDataAspectMock->add($metaData);
        $metaDataAspectMock->remove();

        self::assertEmpty($metaDataAspectMock->get());
    }

    /**
     * @test
     */
    public function positiveUidOfFileIsExpectedToLoadMetaData(): void
    {
        $this->expectException(InvalidUidException::class);
        $this->expectExceptionCode(1381590731);

        $file = new File(['uid' => -3], $this->storageMock);
        $file->getMetaData()->get();
    }

    /**
     * @test
     */
    public function newMetaDataIsCreated(): void
    {
        $GLOBALS['EXEC_TIME'] = 1534530781;
        $metaData = [
            'title' => 'Hooray',
            // This value is ignored on purpose, we simulate the non-existence of the field "description"
            'description' => 'Yipp yipp yipp',
        ];

        $file = new File(['uid' => 12], $this->storageMock);

        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->insert(Argument::cetera())->willReturn(1);
        $connectionProphecy->lastInsertId(Argument::cetera())->willReturn(5);
        $connectionPoolProphecy = $this->prophesize(ConnectionPool::class);
        $connectionPoolProphecy->getConnectionForTable(Argument::cetera())->willReturn($connectionProphecy->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphecy->reveal());

        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(Argument::cetera())->willReturnArgument(0);
        $metaDataRepositoryMock = $this->getMockBuilder(MetaDataRepository::class)
            ->setMethods(['findByFileUid', 'getTableFields', 'update'])
            ->setConstructorArgs([$eventDispatcher->reveal()])
            ->getMock();
        $metaDataRepositoryMock->expects(self::any())->method('findByFileUid')->willReturn([]);
        $metaDataRepositoryMock->expects(self::any())->method('getTableFields')->willReturn(['title' => 'sometype']);
        $metaDataRepositoryMock->expects(self::never())->method('update');
        GeneralUtility::setSingletonInstance(MetaDataRepository::class, $metaDataRepositoryMock);

        $file->getMetaData()->add($metaData)->save();

        $expected = [
            'file' => $file->getUid(),
            'pid' => 0,
            'crdate' => 1534530781,
            'tstamp' => 1534530781,
            'cruser_id' => 0,
            'l10n_diffsource' => '',
            'title' => 'Hooray',
            'uid' => '5',
        ];

        self::assertSame($expected, $file->getMetaData()->get());
    }

    /**
     * @test
     */
    public function existingMetaDataGetsUpdated(): void
    {
        $metaData = ['foo' => 'bar'];

        $file = new File(['uid' => 12], $this->storageMock);

        $metaDataRepositoryMock = $this->getMockBuilder(MetaDataRepository::class)
            ->setMethods(['loadFromRepository', 'createMetaDataRecord', 'update'])
            ->disableOriginalConstructor()
            ->getMock();

        $metaDataRepositoryMock->expects(self::any())->method('createMetaDataRecord')->willReturn($metaData);
        GeneralUtility::setSingletonInstance(MetaDataRepository::class, $metaDataRepositoryMock);

        $metaDataAspectMock = $this->getMockBuilder(MetaDataAspect::class)
            ->setConstructorArgs([$file])
            ->setMethods(['loadFromRepository'])
            ->getMock();

        $metaDataAspectMock->expects(self::any())->method('loadFromRepository')->will(self::onConsecutiveCalls([], $metaData));
        $metaDataAspectMock->add($metaData)->save();
        $metaDataAspectMock->add(['testproperty' => 'testvalue'])->save();

        self::assertSame(['foo' => 'bar', 'testproperty' => 'testvalue'], $metaDataAspectMock->get());
    }

    /**
     * @return array
     */
    public function propertyDataProvider(): array
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

    /**
     * @param $metaData
     * @param $has
     * @param $get
     * @test
     * @dataProvider propertyDataProvider
     */
    public function propertyIsFetchedProperly($metaData, $has, $get): void
    {
        $file = new File([], $this->storageMock, $metaData);

        self::assertSame($has['expected'], isset($file->getMetaData()[$has['property']]));
        self::assertSame($get['expected'], $file->getMetaData()[$get['property']] ?? null);
    }
}
