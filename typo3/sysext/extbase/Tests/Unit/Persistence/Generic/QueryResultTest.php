<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence\Generic;

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

/**
 * Test case
 */
class QueryResultTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
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
     *
     * @return void
     */
    protected function setUp()
    {
        $this->mockPersistenceManager = $this->getMock(\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface::class);
        $this->mockPersistenceManager->expects($this->any())->method('getObjectDataByQuery')->will($this->returnValue(['one', 'two']));
        $this->mockPersistenceManager->expects($this->any())->method('getObjectCountByQuery')->will($this->returnValue(2));
        $this->mockDataMapper = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper::class);
        $this->mockQuery = $this->getMock(\TYPO3\CMS\Extbase\Persistence\QueryInterface::class);
        $this->queryResult = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\QueryResult::class, ['dummy'], [$this->mockQuery]);
        $this->queryResult->_set('persistenceManager', $this->mockPersistenceManager);
        $this->queryResult->_set('dataMapper', $this->mockDataMapper);
        $this->sampleResult = [['foo' => 'Foo1', 'bar' => 'Bar1'], ['foo' => 'Foo2', 'bar' => 'Bar2']];
        $this->mockDataMapper->expects($this->any())->method('map')->will($this->returnValue($this->sampleResult));
    }

    /**
     * @test
     */
    public function getQueryReturnsQueryObject()
    {
        $this->assertInstanceOf(\TYPO3\CMS\Extbase\Persistence\QueryInterface::class, $this->queryResult->getQuery());
    }

    /**
     * @test
     */
    public function getQueryReturnsAClone()
    {
        $this->assertNotSame($this->mockQuery, $this->queryResult->getQuery());
    }

    /**
     * @test
     */
    public function offsetExistsWorksAsExpected()
    {
        $this->assertTrue($this->queryResult->offsetExists(0));
        $this->assertFalse($this->queryResult->offsetExists(2));
        $this->assertFalse($this->queryResult->offsetExists('foo'));
    }

    /**
     * @test
     */
    public function offsetGetWorksAsExpected()
    {
        $this->assertEquals(['foo' => 'Foo1', 'bar' => 'Bar1'], $this->queryResult->offsetGet(0));
        $this->assertNull($this->queryResult->offsetGet(2));
        $this->assertNull($this->queryResult->offsetGet('foo'));
    }

    /**
     * @test
     */
    public function offsetSetWorksAsExpected()
    {
        $this->queryResult->offsetSet(0, ['foo' => 'FooOverridden', 'bar' => 'BarOverridden']);
        $this->assertEquals(['foo' => 'FooOverridden', 'bar' => 'BarOverridden'], $this->queryResult->offsetGet(0));
    }

    /**
     * @test
     */
    public function offsetUnsetWorksAsExpected()
    {
        $this->queryResult->offsetUnset(0);
        $this->assertFalse($this->queryResult->offsetExists(0));
    }

    /**
     * @test
     */
    public function countDoesNotInitializeProxy()
    {
        $queryResult = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\QueryResult::class, ['initialize'], [$this->mockQuery]);
        $queryResult->_set('persistenceManager', $this->mockPersistenceManager);
        $queryResult->expects($this->never())->method('initialize');
        $queryResult->count();
    }

    /**
     * @test
     */
    public function countCallsGetObjectCountByQueryOnPersistenceManager()
    {
        $queryResult = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\QueryResult::class, ['initialize'], [$this->mockQuery]);
        $queryResult->_set('persistenceManager', $this->mockPersistenceManager);
        $this->assertEquals(2, $queryResult->count());
    }

    /**
     * @test
     */
    public function countCountsQueryResultDirectlyIfAlreadyInitialized()
    {
        $this->mockPersistenceManager->expects($this->never())->method('getObjectCountByQuery');
        $this->queryResult->toArray();
        $this->assertEquals(2, $this->queryResult->count());
    }

    /**
     * @test
     */
    public function countOnlyCallsGetObjectCountByQueryOnPersistenceManagerOnce()
    {
        $this->mockPersistenceManager->expects($this->once())->method('getObjectCountByQuery')->will($this->returnValue(2));
        $this->queryResult->count();
        $this->assertEquals(2, $this->queryResult->count());
    }

    /**
     * @test
     */
    public function iteratorMethodsAreCorrectlyImplemented()
    {
        $array1 = ['foo' => 'Foo1', 'bar' => 'Bar1'];
        $array2 = ['foo' => 'Foo2', 'bar' => 'Bar2'];
        $this->assertEquals($array1, $this->queryResult->current());
        $this->assertTrue($this->queryResult->valid());
        $this->queryResult->next();
        $this->assertEquals($array2, $this->queryResult->current());
        $this->assertTrue($this->queryResult->valid());
        $this->assertEquals(1, $this->queryResult->key());
        $this->queryResult->next();
        $this->assertFalse($this->queryResult->current());
        $this->assertFalse($this->queryResult->valid());
        $this->assertNull($this->queryResult->key());
        $this->queryResult->rewind();
        $this->assertEquals(0, $this->queryResult->key());
        $this->assertEquals($array1, $this->queryResult->current());
    }

    /**
     * @test
     */
    public function initializeExecutesQueryWithArrayFetchMode()
    {
        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\QueryResult|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $queryResult = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\QueryResult::class, ['dummy'], [$this->mockQuery]);
        $queryResult->_set('persistenceManager', $this->mockPersistenceManager);
        $queryResult->_set('dataMapper', $this->mockDataMapper);
        $this->mockPersistenceManager->expects($this->once())->method('getObjectDataByQuery')->with($this->mockQuery)->will($this->returnValue(['FAKERESULT']));
        $queryResult->_call('initialize');
    }

    /**
     * @test
     */
    public function usingCurrentOnTheQueryResultReturnsNull()
    {
        $queryResult = new \TYPO3\CMS\Extbase\Persistence\Generic\QueryResult($this->mockQuery);
        $actualResult = current($queryResult);
        $this->assertNull($actualResult);
    }
}
