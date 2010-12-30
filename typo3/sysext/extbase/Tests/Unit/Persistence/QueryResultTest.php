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
	protected $query;

	/**
	 * @var Tx_Extbase_Persistence_ManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var Tx_Extbase_Persistence_DataMapper
	 */
	protected $dataMapper;

	/**
	 * Sets up this test case
	 *
	 * @return void
	 */
	public function setUp() {
		$this->persistenceManager = $this->getMock('Tx_Extbase_Persistence_ManagerInterface');
		$this->persistenceManager->expects($this->any())->method('getObjectDataByQuery')->will($this->returnValue(array('one', 'two')));
		$this->persistenceManager->expects($this->any())->method('getObjectCountByQuery')->will($this->returnValue(2));
		$this->dataMapper = $this->getMock('Tx_Extbase_Persistence_Mapper_DataMapper');
		$this->query = $this->getMock('Tx_Extbase_Persistence_QueryInterface');
		$this->queryResult = new Tx_Extbase_Persistence_QueryResult($this->query);
		$this->queryResult->injectPersistenceManager($this->persistenceManager);
		$this->queryResult->injectDataMapper($this->dataMapper);
		$this->sampleResult = array(array('foo' => 'Foo1', 'bar' => 'Bar1'), array('foo' => 'Foo2', 'bar' => 'Bar2'));
		$this->dataMapper->expects($this->any())->method('map')->will($this->returnValue($this->sampleResult));
	}

	/**
	 * @test
	 */
	public function getQueryReturnsQueryObject() {
		$this->assertType('Tx_Extbase_Persistence_QueryInterface', $this->queryResult->getQuery());
	}

	/**
	 * @test
	 */
	public function getQueryReturnsAClone() {
		$this->assertNotSame($this->query, $this->queryResult->getQuery());
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
		$queryResult = $this->getMock('Tx_Extbase_Persistence_QueryResult', array('initialize'), array($this->query));
		$queryResult->injectPersistenceManager($this->persistenceManager);
		$queryResult->expects($this->never())->method('initialize');
		$queryResult->count();
	}

	/**
	 * @test
	 */
	public function countCallsGetObjectCountByQueryOnPersistenceManager() {
		$queryResult = $this->getMock('Tx_Extbase_Persistence_QueryResult', array('initialize'), array($this->query));
		$queryResult->injectPersistenceManager($this->persistenceManager);
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
		$queryResult = $this->getAccessibleMock('Tx_Extbase_Persistence_QueryResult', array('dummy'), array($this->query));
		$queryResult->injectPersistenceManager($this->persistenceManager);
		$queryResult->injectDataMapper($this->dataMapper);
		$this->persistenceManager->expects($this->once())->method('getObjectDataByQuery')->with($this->query)->will($this->returnValue(array('FAKERESULT')));
		$queryResult->_call('initialize');
	}

}
?>