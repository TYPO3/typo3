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

namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence\Generic;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class QueryResultTest extends UnitTestCase
{
    /**
     * @var QueryResult
     */
    protected $queryResult;

    /**
     * @var QueryInterface
     */
    protected $mockQuery;

    /**
     * @var PersistenceManagerInterface
     */
    protected $mockPersistenceManager;

    /**
     * @var DataMapper
     */
    protected $mockDataMapper;

    protected array $sampleResult = [];

    /**
     * Sets up this test case
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $this->mockPersistenceManager->method('getObjectDataByQuery')->willReturn(['one', 'two']);
        $this->mockPersistenceManager->method('getObjectCountByQuery')->willReturn(2);
        $this->mockDataMapper = $this->createMock(DataMapper::class);
        $this->mockQuery = $this->createMock(QueryInterface::class);
        $this->queryResult = $this->getAccessibleMock(QueryResult::class, null, [], '', false);
        $this->queryResult->_set('persistenceManager', $this->mockPersistenceManager);
        $this->queryResult->_set('dataMapper', $this->mockDataMapper);
        $this->sampleResult = [['foo' => 'Foo1', 'bar' => 'Bar1'], ['foo' => 'Foo2', 'bar' => 'Bar2']];
        $this->mockDataMapper->method('map')->willReturn($this->sampleResult);
    }

    #[Test]
    public function getQueryReturnsQueryObject(): void
    {
        $this->queryResult->setQuery($this->mockQuery);
        self::assertInstanceOf(QueryInterface::class, $this->queryResult->getQuery());
    }

    #[Test]
    public function getQueryReturnsAClone(): void
    {
        $this->queryResult->setQuery($this->mockQuery);
        self::assertNotSame($this->mockQuery, $this->queryResult->getQuery());
    }

    #[Test]
    public function offsetExistsWorksAsExpected(): void
    {
        $this->queryResult->setQuery($this->mockQuery);
        self::assertTrue($this->queryResult->offsetExists(0));
        self::assertFalse($this->queryResult->offsetExists(2));
        self::assertFalse($this->queryResult->offsetExists('foo'));
    }

    #[Test]
    public function offsetGetWorksAsExpected(): void
    {
        $this->queryResult->setQuery($this->mockQuery);
        self::assertEquals(['foo' => 'Foo1', 'bar' => 'Bar1'], $this->queryResult->offsetGet(0));
        self::assertNull($this->queryResult->offsetGet(2));
        self::assertNull($this->queryResult->offsetGet('foo'));
    }

    #[Test]
    public function offsetSetWorksAsExpected(): void
    {
        $this->queryResult->setQuery($this->mockQuery);
        $this->queryResult->offsetSet(0, new \stdClass());
        self::assertInstanceOf(\stdClass::class, $this->queryResult->offsetGet(0));
    }

    #[Test]
    public function offsetUnsetWorksAsExpected(): void
    {
        $this->queryResult->setQuery($this->mockQuery);
        $this->queryResult->offsetUnset(0);
        self::assertFalse($this->queryResult->offsetExists(0));
    }

    #[Test]
    public function countDoesNotInitializeProxy(): void
    {
        $queryResult = $this->getAccessibleMock(QueryResult::class, ['initialize'], [], '', false);
        $queryResult->_set('persistenceManager', $this->mockPersistenceManager);
        $queryResult->_set('dataMapper', $this->mockDataMapper);
        $queryResult->setQuery($this->mockQuery);
        $queryResult->expects($this->never())->method('initialize');
        $queryResult->count();
    }

    #[Test]
    public function countCallsGetObjectCountByQueryOnPersistenceManager(): void
    {
        $queryResult = $this->getAccessibleMock(QueryResult::class, ['initialize'], [], '', false);
        $queryResult->_set('persistenceManager', $this->mockPersistenceManager);
        $queryResult->_set('dataMapper', $this->mockDataMapper);
        $queryResult->setQuery($this->mockQuery);
        self::assertEquals(2, $queryResult->count());
    }

    #[Test]
    public function countCountsQueryResultDirectlyIfAlreadyInitialized(): void
    {
        $this->queryResult->setQuery($this->mockQuery);
        $this->mockPersistenceManager->expects($this->never())->method('getObjectCountByQuery');
        $this->queryResult->toArray();
        self::assertEquals(2, $this->queryResult->count());
    }

    #[Test]
    public function countOnlyCallsGetObjectCountByQueryOnPersistenceManagerOnce(): void
    {
        $this->queryResult->setQuery($this->mockQuery);
        $this->mockPersistenceManager->expects($this->once())->method('getObjectCountByQuery')->willReturn(2);
        $this->queryResult->count();
        self::assertEquals(2, $this->queryResult->count());
    }

    #[Test]
    public function countCallsGetObjectCountByQueryIfOffsetChanges(): void
    {
        $this->queryResult->setQuery($this->mockQuery);
        $this->mockPersistenceManager->expects($this->once())->method('getObjectCountByQuery')->willReturn(2);
        $firstCount = $this->queryResult->count();
        $this->queryResult->offsetSet(3, new \stdClass());
        $this->queryResult->offsetSet(4, new \stdClass());
        $secondCount = $this->queryResult->count();
        $this->queryResult->offsetUnset(1);
        $thirdCount = $this->queryResult->count();

        self::assertSame(2, $firstCount);
        self::assertSame(4, $secondCount);
        self::assertSame(3, $thirdCount);
    }

    #[Test]
    public function iteratorMethodsAreCorrectlyImplemented(): void
    {
        $this->queryResult->setQuery($this->mockQuery);
        $array1 = ['foo' => 'Foo1', 'bar' => 'Bar1'];
        $array2 = ['foo' => 'Foo2', 'bar' => 'Bar2'];
        self::assertEquals($array1, $this->queryResult->current());
        self::assertTrue($this->queryResult->valid());
        $this->queryResult->next();
        self::assertEquals($array2, $this->queryResult->current());
        self::assertTrue($this->queryResult->valid());
        self::assertEquals(1, $this->queryResult->key());
        $this->queryResult->next();
        self::assertFalse($this->queryResult->current());
        self::assertFalse($this->queryResult->valid());
        self::assertNull($this->queryResult->key());
        $this->queryResult->rewind();
        self::assertEquals(0, $this->queryResult->key());
        self::assertEquals($array1, $this->queryResult->current());
    }

    #[Test]
    public function initializeExecutesQueryWithArrayFetchMode(): void
    {
        $queryResult = $this->getAccessibleMock(QueryResult::class, null, [], '', false);
        $queryResult->_set('persistenceManager', $this->mockPersistenceManager);
        $queryResult->_set('dataMapper', $this->mockDataMapper);
        $queryResult->setQuery($this->mockQuery);
        $this->mockPersistenceManager->expects($this->once())->method('getObjectDataByQuery')->with($this->mockQuery)->willReturn(['FAKERESULT']);
        $queryResult->_call('initialize');
    }
}
