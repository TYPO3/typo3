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

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
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

class DataMapperTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected ColumnMap $columnMap;
    protected ColumnMapFactory $columnMapFactory;
    protected DataMapper $dataMapper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->columnMap = new ColumnMap('foo');

        $this->columnMapFactory = new ColumnMapFactory(
            $this->createMock(ReflectionService::class)
        );

        $dataMapFactory = new DataMapFactory(
            $this->createMock(ReflectionService::class),
            $this->createMock(ConfigurationManager::class),
            $this->createMock(CacheManager::class),
            $this->createMock(ClassesConfiguration::class),
            $this->columnMapFactory,
            'foo'
        );

        $this->dataMapper = new DataMapper(
            $this->createMock(ReflectionService::class),
            $this->createMock(QueryObjectModelFactory::class),
            $this->createMock(Session::class),
            $dataMapFactory,
            $this->createMock(QueryFactory::class),
            $this->createMock(EventDispatcherInterface::class),
        );
    }

    /**
     * @test
     */
    public function getOrderingsForColumnMapReturnsNullIfNeitherForeignSortByNorForeignDefaultSortByAreSet(): void
    {
        // Arrange
        $this->columnMapFactory->setOneToManyRelation(
            $this->columnMap,
            [
                'foreign_table' => 'tx_myextension_bar',
            ]
        );

        // Act
        $orderings = $this->dataMapper->getOrderingsForColumnMap($this->columnMap);

        // Assert
        self::assertNull($orderings);
    }

    /**
     * @test
     */
    public function getOrderingsForColumnMapReturnsNullIfForeignDefaultSortByIsEmpty(): void
    {
        // Arrange
        $this->columnMapFactory->setOneToManyRelation(
            $this->columnMap,
            [
                'foreign_table' => 'tx_myextension_bar',
                'foreign_default_sortby' => '',
            ]
        );

        // Act
        $orderings = $this->dataMapper->getOrderingsForColumnMap($this->columnMap);

        // Assert
        self::assertNull($orderings);
    }

    /**
     * @test
     */
    public function getOrderingsForColumnMapFallBackToAscendingOrdering(): void
    {
        // Arrange
        $this->columnMapFactory->setOneToManyRelation(
            $this->columnMap,
            [
                'foreign_table' => 'tx_myextension_bar',
                'foreign_default_sortby' => 'pid invalid',
            ]
        );

        // Act
        $orderings = $this->dataMapper->getOrderingsForColumnMap($this->columnMap);

        // Assert
        self::assertSame(
            ['pid' => QueryInterface::ORDER_ASCENDING],
            $orderings
        );
    }

    /**
     * @test
     */
    public function setOneToManyRelationDetectsForeignSortBy(): void
    {
        // Arrange
        $this->columnMapFactory->setOneToManyRelation(
            $this->columnMap,
            [
                'foreign_table' => 'tx_myextension_bar',
                'foreign_sortby' => 'uid',
            ]
        );

        // Act
        $orderings = $this->dataMapper->getOrderingsForColumnMap($this->columnMap);

        // Assert
        self::assertSame(
            ['uid' => QueryInterface::ORDER_ASCENDING],
            $orderings
        );
    }

    /**
     * @test
     */
    public function setOneToManyRelationDetectsForeignSortByWithForeignDefaultSortBy(): void
    {
        // Arrange
        $this->columnMapFactory->setOneToManyRelation(
            $this->columnMap,
            [
                'foreign_table' => 'tx_myextension_bar',
                'foreign_sortby' => 'uid',
                'foreign_default_sortby' => 'pid',
            ]
        );

        // Act
        $orderings = $this->dataMapper->getOrderingsForColumnMap($this->columnMap);

        // Assert
        self::assertSame(
            ['uid' => QueryInterface::ORDER_ASCENDING],
            $orderings
        );
    }

    /**
     * @test
     */
    public function setOneToManyRelationDetectsForeignDefaultSortByWithoutDirection(): void
    {
        // Arrange
        $this->columnMapFactory->setOneToManyRelation(
            $this->columnMap,
            [
                'foreign_table' => 'tx_myextension_bar',
                'foreign_default_sortby' => 'pid',
            ]
        );

        // Act
        $orderings = $this->dataMapper->getOrderingsForColumnMap($this->columnMap);

        // Assert
        self::assertSame(
            ['pid' => QueryInterface::ORDER_ASCENDING],
            $orderings
        );
    }

    /**
     * @test
     */
    public function setOneToManyRelationDetectsForeignDefaultSortByWithDirection(): void
    {
        // Arrange
        $this->columnMapFactory->setOneToManyRelation(
            $this->columnMap,
            [
                'foreign_table' => 'tx_myextension_bar',
                'foreign_default_sortby' => 'pid desc',
            ]
        );

        // Act
        $orderings = $this->dataMapper->getOrderingsForColumnMap($this->columnMap);

        // Assert
        self::assertSame(
            ['pid' => QueryInterface::ORDER_DESCENDING],
            $orderings
        );
    }

    /**
     * @test
     */
    public function setOneToManyRelationDetectsMultipleForeignDefaultSortByWithAndWithoutDirection(): void
    {
        // Arrange
        $this->columnMapFactory->setOneToManyRelation(
            $this->columnMap,
            [
                'foreign_table' => 'tx_myextension_bar',
                'foreign_default_sortby' => 'pid desc, title, uid asc',
            ]
        );

        // Act
        $orderings = $this->dataMapper->getOrderingsForColumnMap($this->columnMap);

        // Assert
        self::assertSame(
            ['pid' => QueryInterface::ORDER_DESCENDING, 'title' => QueryInterface::ORDER_ASCENDING, 'uid' => QueryInterface::ORDER_ASCENDING],
            $orderings
        );
    }
}
