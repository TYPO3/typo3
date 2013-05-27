<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
 *  All rights reserved
 *
 *  This class is a backport of the corresponding class of FLOW3.
 *  All credits go to the v5 team.
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

class Tx_Extbase_Persistence_Storage_Typo3DbBackend_testcase extends Tx_Extbase_BaseTestCase {
	
	/**
	 * This is the data provider for the statement generation with a basic comparison
	 *
	 * @return array An array of data
	 */
	public function providerForBasicComparison() {
		return array(
			'equal' => array(
				Tx_Extbase_Persistence_QueryInterface::OPERATOR_EQUAL_TO,
				"SELECT table_name_from_selector.* FROM table_name_from_selector WHERE table_name_from_property.foo = 'baz'"
				),
			'less' => array(
				Tx_Extbase_Persistence_QueryInterface::OPERATOR_LESS_THAN,
				"SELECT table_name_from_selector.* FROM table_name_from_selector WHERE table_name_from_property.foo < 'baz'"
				),
			'less or equal' => array(
				Tx_Extbase_Persistence_QueryInterface::OPERATOR_LESS_THAN_OR_EQUAL_TO,
				"SELECT table_name_from_selector.* FROM table_name_from_selector WHERE table_name_from_property.foo <= 'baz'"
				),
			'greater' => array(
				Tx_Extbase_Persistence_QueryInterface::OPERATOR_GREATER_THAN,
				"SELECT table_name_from_selector.* FROM table_name_from_selector WHERE table_name_from_property.foo > 'baz'"
				),
			'greater or equal' => array(
				Tx_Extbase_Persistence_QueryInterface::OPERATOR_GREATER_THAN_OR_EQUAL_TO,
				"SELECT table_name_from_selector.* FROM table_name_from_selector WHERE table_name_from_property.foo >= 'baz'"
				),
			
			);
	}

	/**
	 * @test
	 */	
	public function getStatementWorksWithMinimalisticQueryObjectModel() {
		$this->markTestIncomplete();
	}

	/**
	 * @test
	 */	
	public function getStatementWorksWithBasicEqualsCondition() {
		$this->markTestIncomplete();
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_Persistence_Storage_Exception_BadConstraint
	 */	
	public function countRowsWithStatementConstraintResultsInAnException() {
		$this->markTestIncomplete();
	}

	/**
	 * @test
	 */
	public function joinStatementGenerationWorks() {
		$this->markTestIncomplete();
	}

	/**
	 * @test
	 */
	public function orderStatementGenerationWorks() {
		$mockSource = $this->getMock('Tx_Extbase_Persistence_QOM_Selector', array('getNodeTypeName'), array(), '', FALSE);
		$mockSource->expects($this->any())->method('getNodeTypeName')->will($this->returnValue('Tx_MyExt_ClassName'));

		$mockDataMapper = $this->getMock('Tx_Extbase_Persistence_Mapper_DataMapper', array('convertPropertyNameToColumnName', 'convertClassNameToTableName'), array(), '', FALSE);
		$mockDataMapper->expects($this->once())->method('convertClassNameToTableName')->with('Tx_MyExt_ClassName')->will($this->returnValue('tx_myext_tablename'));
		$mockDataMapper->expects($this->once())->method('convertPropertyNameToColumnName')->with('fooProperty', 'Tx_MyExt_ClassName')->will($this->returnValue('converted_fieldname'));
		
		$sql = array();
		$orderings = array('fooProperty' => Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_ORDER_ASCENDING);
		$mockTypo3DbBackend = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_Storage_Typo3DbBackend'), array('parserOrderings'), array(), '', FALSE);		
		$mockTypo3DbBackend->_set('dataMapper', $mockDataMapper);
		$mockTypo3DbBackend->_callRef('parseOrderings', $orderings, $mockSource, $sql);
			
		$expecedSql = array('orderings' => array('tx_myext_tablename.converted_fieldname ASC'));
		$this->assertSame($expecedSql, $sql);
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_Persistence_Exception_UnsupportedOrder
	 */
	public function orderStatementGenerationThrowsExceptionOnUnsupportedOrder() {
		$mockSource = $this->getMock('Tx_Extbase_Persistence_QOM_Selector', array('getNodeTypeName'), array(), '', FALSE);
		$mockSource->expects($this->never())->method('getNodeTypeName');

		$mockDataMapper = $this->getMock('Tx_Extbase_Persistence_Mapper_DataMapper', array('convertPropertyNameToColumnName', 'convertClassNameToTableName'), array(), '', FALSE);
		$mockDataMapper->expects($this->never())->method('convertClassNameToTableName');
		$mockDataMapper->expects($this->never())->method('convertPropertyNameToColumnName');
		
		$sql = array();
		$orderings = array('fooProperty' => 'unsupported_order');
		$mockTypo3DbBackend = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_Storage_Typo3DbBackend'), array('parserOrderings'), array(), '', FALSE);		
		$mockTypo3DbBackend->_set('dataMapper', $mockDataMapper);
		$mockTypo3DbBackend->_callRef('parseOrderings', $orderings, $mockSource, $sql);
	}

	/**
	 * @test
	 */
	public function orderStatementGenerationWorksWithMultipleOrderings() {
		$mockSource = $this->getMock('Tx_Extbase_Persistence_QOM_Selector', array('getNodeTypeName'), array(), '', FALSE);
		$mockSource->expects($this->any())->method('getNodeTypeName')->will($this->returnValue('Tx_MyExt_ClassName'));

		$mockDataMapper = $this->getMock('Tx_Extbase_Persistence_Mapper_DataMapper', array('convertPropertyNameToColumnName', 'convertClassNameToTableName'), array(), '', FALSE);
		$mockDataMapper->expects($this->any())->method('convertClassNameToTableName')->with('Tx_MyExt_ClassName')->will($this->returnValue('tx_myext_tablename'));
		$mockDataMapper->expects($this->any())->method('convertPropertyNameToColumnName')->will($this->returnValue('converted_fieldname'));
		
		$sql = array();
		$orderings = array(
			'fooProperty' => Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_ORDER_ASCENDING,
			'barProperty' => Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_ORDER_DESCENDING
			);
		$mockTypo3DbBackend = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_Storage_Typo3DbBackend'), array('parserOrderings'), array(), '', FALSE);		
		$mockTypo3DbBackend->_set('dataMapper', $mockDataMapper);
		$mockTypo3DbBackend->_callRef('parseOrderings', $orderings, $mockSource, $sql);
			
		$expecedSql = array('orderings' => array('tx_myext_tablename.converted_fieldname ASC', 'tx_myext_tablename.converted_fieldname DESC'));
		$this->assertSame($expecedSql, $sql);
	}
	
}
?>