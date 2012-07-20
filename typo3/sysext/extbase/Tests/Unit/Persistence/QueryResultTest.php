<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Bastian Waidelich <bastian@typo3.org>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

class Tx_Extbase_Tests_Unit_Persistence_QueryResultTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @var Tx_Extbase_Persistence_QueryResult
	 */
	protected $queryResult;

	/**
	 * @var Tx_Extbase_Persistence_QueryInterface
	 */
	protected $mockQuery;

	/**
	 * @var Tx_Extbase_Persistence_ManagerInterface
	 */
	protected $mockPersistenceManager;

	/**
	 * @var Tx_Extbase_Persistence_Mapper_DataMapper
	 */
	protected $mockDataMapper;

	/**
	 * Sets up this test case
	 *
	 * @return void
	 */
	public function setUp() {
		$this->mockPersistenceManager = $this->getMock('Tx_Extbase_Persistence_ManagerInterface');
		$this->mockPersistenceManager->expects($this->any())->method('getObjectDataByQuery')->will($this->returnValue(array('one', 'two')));
		$this->mockPersistenceManager->expects($this->any())->method('getObjectCountByQuery')->will($this->returnValue(2));
		$this->mockDataMapper = $this->getMock('Tx_Extbase_Persistence_Mapper_DataMapper');
		$this->mockQuery = $this->getMock('Tx_Extbase_Persistence_QueryInterface');
		$this->queryResult = new Tx_Extbase_Persistence_QueryResult($this->mockQuery);
		$this->queryResult->injectPersistenceManager($this->mockPersistenceManager);
		$this->queryResult->injectDataMapper($this->mockDataMapper);
		$this->sampleResult = array(array('foo' => 'Foo1', 'bar' => 'Bar1'), array('foo' => 'Foo2', 'bar' => 'Bar2'));
		$this->mockDataMapper->expects($this->any())->method('map')->will($this->returnValue($this->sampleResult));
	}

	/**
	 * @test
	 */
	public function getQueryReturnsQueryObject() {
		$this->assertInstanceOf('Tx_Extbase_Persistence_QueryInterface', $this->queryResult->getQuery());
	}

	/**
	 * @test
	 */
	public function getQueryReturnsAClone() {
		$this->assertNotSame($this->mockQuery, $this->queryResult->getQuery());
	}

	/**
	 * @test
	 */
	public function offsetExistsWorksAsExpected() {
		$this->assertTrue($this->queryResult->offsetExists(0));
		$this->assertFalse($this->queryResult->offsetExists(2));
		$this->assertFalse($this->queryResult->offsetExists('foo'));
	}

	/**
	 * @test
	 */
	public function offsetGetWorksAsExpected() {
		$this->assertEquals(array('foo' => 'Foo1', 'bar' => 'Bar1'), $this->queryResult->offsetGet(0));
		$this->assertNull($this->queryResult->offsetGet(2));
		$this->assertNull($this->queryResult->offsetGet('foo'));
	}

	/**
	 * @test
	 */
	public function offsetSetWorksAsExpected() {
		$this->queryResult->offsetSet(0, array('foo' => 'FooOverridden', 'bar' => 'BarOverridden'));
		$this->assertEquals(array('foo' => 'FooOverridden', 'bar' => 'BarOverridden'), $this->queryResult->offsetGet(0));
	}

	/**
	 * @test
	 */
	public function offsetUnsetWorksAsExpected() {
		$this->queryResult->offsetUnset(0);
		$this->assertFalse($this->queryResult->offsetExists(0));
	}

	/**
	 * @test
	 */
	public function countDoesNotInitializeProxy() {
		$queryResult = $this->getMock('Tx_Extbase_Persistence_QueryResult', array('initialize'), array($this->mockQuery));
		$queryResult->injectPersistenceManager($this->mockPersistenceManager);
		$queryResult->expects($this->never())->method('initialize');
		$queryResult->count();
	}

	/**
	 * @test
	 */
	public function countCallsGetObjectCountByQueryOnPersistenceManager() {
		$queryResult = $this->getMock('Tx_Extbase_Persistence_QueryResult', array('initialize'), array($this->mockQuery));
		$queryResult->injectPersistenceManager($this->mockPersistenceManager);
		$this->assertEquals(2, $queryResult->count());
	}

	/**
	 * @test
	 */
	public function iteratorMethodsAreCorrectlyImplemented() {
		$array1 = array('foo' => 'Foo1', 'bar' => 'Bar1');
		$array2 = array('foo' => 'Foo2', 'bar' => 'Bar2');
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
	public function initializeExecutesQueryWithArrayFetchMode() {
		$queryResult = $this->getAccessibleMock('Tx_Extbase_Persistence_QueryResult', array('dummy'), array($this->mockQuery));
		$queryResult->injectPersistenceManager($this->mockPersistenceManager);
		$queryResult->injectDataMapper($this->mockDataMapper);
		$this->mockPersistenceManager->expects($this->once())->method('getObjectDataByQuery')->with($this->mockQuery)->will($this->returnValue(array('FAKERESULT')));
		$queryResult->_call('initialize');
	}

	/**
	 * @test
	 */
	public function usingCurrentOnTheQueryResultReturnsAWarning() {
		$queryResult = new Tx_Extbase_Persistence_QueryResult($this->mockQuery);
		$expectedResult = 'You should never see this warning. If you do, you probably used PHP array functions like current() on the Tx_Extbase_Persistence_QueryResult. To retrieve the first result, you can use the getFirst() method.';
		$actualResult = current($queryResult);
		$this->assertEquals($expectedResult, $actualResult);
	}

}
?>