<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Christopher Hlubek <hlubek@networkteam.com>
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

class Tx_Extbase_Tests_Unit_Persistence_QueryTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @var Tx_Extbase_Persistence_Query
	 */
	protected $query;

	/**
	 * @var Tx_Extbase_Persistence_QuerySettingsInterface
	 */
	protected $querySettings;

	/**
	 * @var Tx_Extbase_Object_ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var Tx_Extbase_Persistence_ManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var Tx_Extbase_Persistence_BackendInterface
	 */
	protected $backend;

	/**
	 * @var Tx_Extbase_Persistence_Mapper_DataMapper
	 */
	protected $dataMapper;

	/**
	 * Sets up this test case
	 * @return void
	 */
	public function setUp() {
		$this->objectManager = $this->getMock('Tx_Extbase_Object_ObjectManagerInterface');
		$this->query = new Tx_Extbase_Persistence_Query('someType');
		$this->query->injectObjectManager($this->objectManager);
		$this->querySettings = $this->getMock('Tx_Extbase_Persistence_QuerySettingsInterface');
		$this->query->setQuerySettings($this->querySettings);
		$this->persistenceManager = $this->getMock('Tx_Extbase_Persistence_ManagerInterface');
		$this->backend = $this->getMock('Tx_Extbase_Persistence_BackendInterface');
		$this->backend->expects($this->any())->method('getQomFactory')->will($this->returnValue(NULL));
		$this->persistenceManager->expects($this->any())->method('getBackend')->will($this->returnValue($this->backend));
		$this->query->injectPersistenceManager($this->persistenceManager);
		$this->dataMapper = $this->getMock('Tx_Extbase_Persistence_Mapper_DataMapper');
		$this->query->injectDataMapper($this->dataMapper);
	}

	/**
	 * @test
	 */
	public function executeReturnsQueryResultInstanceAndInjectsItself() {
		$queryResult = $this->getMock('Tx_Extbase_Persistence_QueryResult', array(), array(), '', FALSE);
		$this->objectManager->expects($this->once())->method('create')->with('Tx_Extbase_Persistence_QueryResultInterface', $this->query)->will($this->returnValue($queryResult));
		$actualResult = $this->query->execute();
		$this->assertSame($queryResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function executeReturnsRawObjectDataIfRawQueryResultSettingIsTrue() {
		$this->querySettings->expects($this->once())->method('getReturnRawQueryResult')->will($this->returnValue(TRUE));
		$this->persistenceManager->expects($this->once())->method('getObjectDataByQuery')->with($this->query)->will($this->returnValue('rawQueryResult'));
		$expectedResult = 'rawQueryResult';
		$actualResult = $this->query->execute();
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function setLimitAcceptsOnlyIntegers() {
		$this->query->setLimit(1.5);
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function setLimitRejectsIntegersLessThanOne() {
		$this->query->setLimit(0);
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function setOffsetAcceptsOnlyIntegers() {
		$this->query->setOffset(1.5);
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function setOffsetRejectsIntegersLessThanZero() {
		$this->query->setOffset(-1);
	}

	/**
	 * @test
	 */
	public function itCanBeTestedIfTheQueryTypeHasAGivenPropertyForAnUnknownProperty() {
		$mockClassSchema = $this->getMock('Tx_Extbase_Reflection_ClassSchema', array('hasProperty'), array(), '', FALSE);
		$mockClassSchema->expects($this->once())->method('hasProperty')->with($this->equalTo('unknownProperty'))->will($this->returnValue(FALSE));

		$mockReflectionService = $this->getMock('Tx_Extbase_Reflection_Service', array('getClassSchema'), array(), '', FALSE);
		$mockReflectionService->expects($this->once())->method('getClassSchema')->with($this->equalTo('Foo_Class_Name'))->will($this->returnValue($mockClassSchema));

		$mockQuery = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_Query'), array('dummy'), array('Foo_Class_Name'));
		$mockQuery->_set('reflectionService', $mockReflectionService);
		$this->assertEquals(FALSE, $mockQuery->_call('queryTypeHasProperty', 'unknownProperty'));
	}

	/**
	 * @test
	 */
	public function itCanBeTestedIfTheQueryTypeHasAGivenPropertyForAKnownProperty() {
		$mockClassSchema = $this->getMock('Tx_Extbase_Reflection_ClassSchema', array('hasProperty'), array(), '', FALSE);
		$mockClassSchema->expects($this->once())->method('hasProperty')->with($this->equalTo('knownProperty'))->will($this->returnValue(TRUE));

		$mockReflectionService = $this->getMock('Tx_Extbase_Reflection_Service', array('getClassSchema'), array(), '', FALSE);
		$mockReflectionService->expects($this->once())->method('getClassSchema')->with($this->equalTo('Foo_Class_Name'))->will($this->returnValue($mockClassSchema));

		$mockQuery = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_Query'), array('dummy'), array('Foo_Class_Name'));
		$mockQuery->_set('reflectionService', $mockReflectionService);
		$this->assertEquals(TRUE, $mockQuery->_call('queryTypeHasProperty', 'knownProperty'));
	}

	/**
	 * dataProvider for methodNames
	 */
	public function methodNames() {
		return array(
			'equals' => array('equals'),
			'like' => array('like'),
			'contains' => array('contains'),
			'in' => array('in'),
			'lessThan' => array('lessThan'),
			'lessThanOrEqual' => array('lessThanOrEqual'),
			'greaterThan' => array('greaterThan'),
			'greaterThanOrEqual' => array('greaterThanOrEqual')
			);
	}

	/**
	 * @test
	 * @dataProvider methodNames
	 * @expectedException Tx_Extbase_Persistence_Exception_UnknownProperty
	 */
	public function anExceptionIsThrownIfAGivenPropertyIsNotAvailable($methodName) {
		$mockQomFactory = $this->getMock('Tx_Extbase_Persistence_QOM_QueryObjectModelFactory', array('dummy'), array(), '', FALSE);

		$mockQuery = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_Query'), array('queryTypeHasProperty', 'getSelectorName'), array(), '', FALSE);
		$mockQuery->_set('qomFactory', $mockQomFactory);
		$mockQuery->expects($this->once())->method('queryTypeHasProperty')->with($this->equalTo('unknownProperty'))->will($this->returnValue(FALSE));
		$mockQuery->$methodName('unknownProperty', array());
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_Persistence_Exception_UnknownProperty
	 */
	public function anExceptionIsThrownIfAPropertyOfGivenOrderingsIsNotAvailable() {
		$mockQomFactory = $this->getMock('Tx_Extbase_Persistence_QOM_QueryObjectModelFactory', array('dummy'), array(), '', FALSE);

		$mockQuery = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_Query'), array('queryTypeHasProperty', 'getSelectorName'), array(), '', FALSE);
		$mockQuery->_set('qomFactory', $mockQomFactory);
		$mockQuery->expects($this->at(0))->method('queryTypeHasProperty')->with($this->equalTo('knownProperty'))->will($this->returnValue(TRUE));
		$mockQuery->expects($this->at(1))->method('queryTypeHasProperty')->with($this->equalTo('unknownProperty'))->will($this->returnValue(FALSE));
		$mockQuery->setOrderings(array('knownProperty' => 'ASC', 'unknownProperty' => 'ASC'));
	}

}
?>