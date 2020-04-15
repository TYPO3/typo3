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

namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence\Generic;

use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class QueryResultTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\QueryResult
     */
    protected $queryResult;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\QueryInterface
     */
    protected $mockQuery;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     */
    protected $mockPersistenceManager;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper
     */
    protected $mockDataMapper;

    /**
     * @var array
     */
    protected $sampleResult = [];

    /**
     * Sets up this test case
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $this->mockPersistenceManager->expects(self::any())->method('getObjectDataByQuery')->willReturn(['one', 'two']);
        $this->mockPersistenceManager->expects(self::any())->method('getObjectCountByQuery')->willReturn(2);
        $this->mockDataMapper = $this->createMock(DataMapper::class);
        $this->mockQuery = $this->createMock(QueryInterface::class);
        $this->queryResult = $this->getAccessibleMock(QueryResult::class, ['dummy'], [$this->mockQuery]);
        $this->queryResult->_set('persistenceManager', $this->mockPersistenceManager);
        $this->queryResult->_set('dataMapper', $this->mockDataMapper);
        $this->sampleResult = [['foo' => 'Foo1', 'bar' => 'Bar1'], ['foo' => 'Foo2', 'bar' => 'Bar2']];
        $this->mockDataMapper->expects(self::any())->method('map')->willReturn($this->sampleResult);
    }

    /**
     * @test
     */
    public function getQueryReturnsQueryObject()
    {
        self::assertInstanceOf(QueryInterface::class, $this->queryResult->getQuery());
    }

    /**
     * @test
     */
    public function getQueryReturnsAClone()
    {
        self::assertNotSame($this->mockQuery, $this->queryResult->getQuery());
    }

    /**
     * @test
     */
    public function offsetExistsWorksAsExpected()
    {
        self::assertTrue($this->queryResult->offsetExists(0));
        self::assertFalse($this->queryResult->offsetExists(2));
        self::assertFalse($this->queryResult->offsetExists('foo'));
    }

    /**
     * @test
     */
    public function offsetGetWorksAsExpected()
    {
        self::assertEquals(['foo' => 'Foo1', 'bar' => 'Bar1'], $this->queryResult->offsetGet(0));
        self::assertNull($this->queryResult->offsetGet(2));
        self::assertNull($this->queryResult->offsetGet('foo'));
    }

    /**
     * @test
     */
    public function offsetSetWorksAsExpected()
    {
        $this->queryResult->offsetSet(0, ['foo' => 'FooOverridden', 'bar' => 'BarOverridden']);
        self::assertEquals(['foo' => 'FooOverridden', 'bar' => 'BarOverridden'], $this->queryResult->offsetGet(0));
    }

    /**
     * @test
     */
    public function offsetUnsetWorksAsExpected()
    {
        $this->queryResult->offsetUnset(0);
        self::assertFalse($this->queryResult->offsetExists(0));
    }

    /**
     * @test
     */
    public function countDoesNotInitializeProxy()
    {
        $queryResult = $this->getAccessibleMock(QueryResult::class, ['initialize'], [$this->mockQuery]);
        $queryResult->_set('persistenceManager', $this->mockPersistenceManager);
        $queryResult->expects(self::never())->method('initialize');
        $queryResult->count();
    }

    /**
     * @test
     */
    public function countCallsGetObjectCountByQueryOnPersistenceManager()
    {
        $queryResult = $this->getAccessibleMock(QueryResult::class, ['initialize'], [$this->mockQuery]);
        $queryResult->_set('persistenceManager', $this->mockPersistenceManager);
        self::assertEquals(2, $queryResult->count());
    }

    /**
     * @test
     */
    public function countCountsQueryResultDirectlyIfAlreadyInitialized()
    {
        $this->mockPersistenceManager->expects(self::never())->method('getObjectCountByQuery');
        $this->queryResult->toArray();
        self::assertEquals(2, $this->queryResult->count());
    }

    /**
     * @test
     */
    public function countOnlyCallsGetObjectCountByQueryOnPersistenceManagerOnce()
    {
        $this->mockPersistenceManager->expects(self::once())->method('getObjectCountByQuery')->willReturn(2);
        $this->queryResult->count();
        self::assertEquals(2, $this->queryResult->count());
    }

    /**
     * @test
     */
    public function countCallsGetObjectCountByQueryIfOffsetChanges()
    {
        $this->mockPersistenceManager->expects(self::once())->method('getObjectCountByQuery')->willReturn(2);
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

    /**
     * @test
     */
    public function iteratorMethodsAreCorrectlyImplemented()
    {
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

    /**
     * @test
     */
    public function initializeExecutesQueryWithArrayFetchMode()
    {
        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\QueryResult|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $queryResult = $this->getAccessibleMock(QueryResult::class, ['dummy'], [$this->mockQuery]);
        $queryResult->_set('persistenceManager', $this->mockPersistenceManager);
        $queryResult->_set('dataMapper', $this->mockDataMapper);
        $this->mockPersistenceManager->expects(self::once())->method('getObjectDataByQuery')->with($this->mockQuery)->willReturn(['FAKERESULT']);
        $queryResult->_call('initialize');
    }

    /**
     * @test
     */
    public function usingCurrentOnTheQueryResultReturnsNull()
    {
        $queryResult = new QueryResult($this->mockQuery);
        $actualResult = current($queryResult);
        self::assertNull($actualResult);
    }
}
