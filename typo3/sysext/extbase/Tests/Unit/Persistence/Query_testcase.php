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

class Tx_Extbase_Persistence_Query_testcase extends Tx_Extbase_Tests_Unit_BaseTestCase {

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
	 * @var Tx_Extbase_Persistence_DataMapper
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

}
?>