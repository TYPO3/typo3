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

namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence\Generic\Mapper;

use Doctrine\Instantiator\InstantiatorInterface;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Country\CountryProvider;
use TYPO3\CMS\Core\DataHandling\TableColumnType;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Extbase\Persistence\ClassesConfiguration;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMapFactory;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\QueryObjectModelFactory;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryFactory;
use TYPO3\CMS\Extbase\Persistence\Generic\Session;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DataMapperTest extends UnitTestCase
{
    private DataMapper $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $columnMapFactory = new ColumnMapFactory(
            self::createStub(ReflectionService::class)
        );
        $dataMapFactory = new DataMapFactory(
            self::createStub(ClassesConfiguration::class),
            $columnMapFactory,
            self::createStub(TcaSchemaFactory::class),
            'foo',
            self::createStub(FrontendInterface::class),
            self::createStub(FrontendInterface::class),
        );
        $this->subject = new DataMapper(
            self::createStub(ReflectionService::class),
            self::createStub(QueryObjectModelFactory::class),
            self::createStub(Session::class),
            $dataMapFactory,
            self::createStub(QueryFactory::class),
            self::createStub(EventDispatcherInterface::class),
            self::createStub(InstantiatorInterface::class),
            self::createStub(TcaSchemaFactory::class),
            self::createStub(CountryProvider::class),
        );
    }

    #[Test]
    public function getOrderingsForColumnMapReturnsNullIfNeitherForeignSortByNorForeignDefaultSortByAreSet(): void
    {
        $columnMap = new ColumnMap(
            columnName: 'foo',
            type: TableColumnType::SELECT,
            childTableName: 'tx_myextension_bar',
        );
        $orderings = $this->subject->getOrderingsForColumnMap($columnMap);
        self::assertNull($orderings);
    }

    #[Test]
    public function getOrderingsForColumnMapReturnsNullIfForeignDefaultSortByIsEmpty(): void
    {
        $columnMap = new ColumnMap(
            columnName: 'foo',
            type: TableColumnType::SELECT,
            childTableName: 'tx_myextension_bar',
            childTableDefaultSortings: '',
        );
        $orderings = $this->subject->getOrderingsForColumnMap($columnMap);
        self::assertNull($orderings);
    }

    #[Test]
    public function getOrderingsForColumnMapFallBackToAscendingOrdering(): void
    {
        $columnMap = new ColumnMap(
            columnName: 'foo',
            type: TableColumnType::SELECT,
            childTableName: 'tx_myextension_bar',
            childTableDefaultSortings: 'pid invalid',
        );
        $orderings = $this->subject->getOrderingsForColumnMap($columnMap);
        self::assertSame(
            ['pid' => QueryInterface::ORDER_ASCENDING],
            $orderings
        );
    }

    #[Test]
    public function setOneToManyRelationDetectsForeignSortBy(): void
    {
        $columnMap = new ColumnMap(
            columnName: 'foo',
            type: TableColumnType::SELECT,
            childTableName: 'tx_myextension_bar',
            childTableDefaultSortings: 'uid',
        );
        $orderings = $this->subject->getOrderingsForColumnMap($columnMap);
        self::assertSame(
            ['uid' => QueryInterface::ORDER_ASCENDING],
            $orderings
        );
    }

    #[Test]
    public function setOneToManyRelationDetectsForeignSortByWithForeignDefaultSortBy(): void
    {
        $columnMap = new ColumnMap(
            columnName: 'foo',
            type: TableColumnType::SELECT,
            childTableName: 'tx_myextension_bar',
            childSortByFieldName: 'uid',
            childTableDefaultSortings: 'pid',
        );
        $orderings = $this->subject->getOrderingsForColumnMap($columnMap);
        self::assertSame(
            ['uid' => QueryInterface::ORDER_ASCENDING],
            $orderings
        );
    }

    #[Test]
    public function setOneToManyRelationDetectsForeignDefaultSortByWithoutDirection(): void
    {
        $columnMap = new ColumnMap(
            columnName: 'foo',
            type: TableColumnType::SELECT,
            childTableName: 'tx_myextension_bar',
            childTableDefaultSortings: 'pid',
        );
        $orderings = $this->subject->getOrderingsForColumnMap($columnMap);
        self::assertSame(
            ['pid' => QueryInterface::ORDER_ASCENDING],
            $orderings
        );
    }

    #[Test]
    public function setOneToManyRelationDetectsForeignDefaultSortByWithDirection(): void
    {
        $columnMap = new ColumnMap(
            columnName: 'foo',
            type: TableColumnType::SELECT,
            childTableName: 'tx_myextension_bar',
            childTableDefaultSortings: 'pid desc',
        );
        $orderings = $this->subject->getOrderingsForColumnMap($columnMap);
        self::assertSame(
            ['pid' => QueryInterface::ORDER_DESCENDING],
            $orderings
        );
    }

    #[Test]
    public function setOneToManyRelationDetectsMultipleForeignDefaultSortByWithAndWithoutDirection(): void
    {
        $columnMap = new ColumnMap(
            columnName: 'foo',
            type: TableColumnType::SELECT,
            childTableName: 'tx_myextension_bar',
            childTableDefaultSortings: 'pid desc, title, uid asc',
        );
        $orderings = $this->subject->getOrderingsForColumnMap($columnMap);
        self::assertSame(
            ['pid' => QueryInterface::ORDER_DESCENDING, 'title' => QueryInterface::ORDER_ASCENDING, 'uid' => QueryInterface::ORDER_ASCENDING],
            $orderings
        );
    }
}
