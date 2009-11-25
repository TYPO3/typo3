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
				Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_OPERATOR_EQUAL_TO,
				"SELECT table_name_from_selector.* FROM table_name_from_selector WHERE table_name_from_property.foo = 'baz'"
				),
			'less' => array(
				Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_OPERATOR_LESS_THAN,
				"SELECT table_name_from_selector.* FROM table_name_from_selector WHERE table_name_from_property.foo < 'baz'"
				),
			'less or equal' => array(
				Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_OPERATOR_LESS_THAN_OR_EQUAL_TO,
				"SELECT table_name_from_selector.* FROM table_name_from_selector WHERE table_name_from_property.foo <= 'baz'"
				),
			'greater' => array(
				Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_OPERATOR_GREATER_THAN,
				"SELECT table_name_from_selector.* FROM table_name_from_selector WHERE table_name_from_property.foo > 'baz'"
				),
			'greater or equal' => array(
				Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_OPERATOR_GREATER_THAN_OR_EQUAL_TO,
				"SELECT table_name_from_selector.* FROM table_name_from_selector WHERE table_name_from_property.foo >= 'baz'"
				),
			
			);
	}

	/**
	 * @test
	 */	
	public function getStatementWorksWithMinimalisticQueryObjectModel() {
		$mockSource = $this->getMock('Tx_Extbase_Persistence_QOM_Selector', array(), array(), '', FALSE);
		$mockSource->expects($this->any())->method('getSelectorName')->will($this->returnValue('selector_name'));
		$mockSource->expects($this->any())->method('getNodeTypeName')->will($this->returnValue('nodetype_name'));

		$mockQueryObjectModel = $this->getMock('Tx_Extbase_Persistence_QOM_QueryObjectModel', array(), array(), '', FALSE);
		$mockQueryObjectModel->expects($this->any())->method('getSource')->will($this->returnValue($mockSource));
		$mockQueryObjectModel->expects($this->any())->method('getBoundVariableValues')->will($this->returnValue(array()));
		$mockQueryObjectModel->expects($this->any())->method('getOrderings')->will($this->returnValue(array()));
		
		$mockTypo3DbBackend = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_Storage_Typo3DbBackend'), array('parseOrderings'), array(), '', FALSE);		
		$mockTypo3DbBackend->expects($this->any())->method('parseOrderings');
		
		$parameters = array();
		$resultingStatement = $mockTypo3DbBackend->getStatement($mockQueryObjectModel, $parameters);
		$expectedStatement = 'SELECT selector_name.* FROM selector_name';
		$this->assertEquals($expectedStatement, $resultingStatement);
	}

	/**
	 * @test
	 */	
	public function getStatementWorksWithBasicEqualsCondition() {
		$mockSource = $this->getMock('Tx_Extbase_Persistence_QOM_Selector', array(), array(), '', FALSE);
		$mockSource->expects($this->any())->method('getSelectorName')->will($this->returnValue('selector_name'));
		$mockSource->expects($this->any())->method('getNodeTypeName')->will($this->returnValue('nodetype_name'));

		$mockQueryObjectModel = $this->getMock('Tx_Extbase_Persistence_QOM_QueryObjectModel', array(), array(), '', FALSE);
		$mockQueryObjectModel->expects($this->any())->method('getSource')->will($this->returnValue($mockSource));
		$mockQueryObjectModel->expects($this->any())->method('getBoundVariableValues')->will($this->returnValue(array()));
		$mockQueryObjectModel->expects($this->any())->method('getOrderings')->will($this->returnValue(array()));
		
		$mockTypo3DbBackend = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_Storage_Typo3DbBackend'), array('parseOrderings'), array(), '', FALSE);		
		$mockTypo3DbBackend->expects($this->any())->method('parseOrderings');
		
		$parameters = array();
		$resultingStatement = $mockTypo3DbBackend->getStatement($mockQueryObjectModel, $parameters);
		$expectedStatement = 'SELECT selector_name.* FROM selector_name';
		$this->assertEquals($expectedStatement, $resultingStatement);
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_Persistence_Storage_Exception_BadConstraint
	 */	
	public function countRowsWithStatementConstraintResultsInAnException() {
		$mockStatementConstraint = $this->getMock('Tx_Extbase_Persistence_QOM_Statement', array(), array(), '', FALSE);
		
		$mockQueryObjectModel = $this->getMock('Tx_Extbase_Persistence_QOM_QueryObjectModel', array('getConstraint'), array(), '', FALSE);
		$mockQueryObjectModel->expects($this->once())->method('getConstraint')->will($this->returnValue($mockStatementConstraint));

		$mockTypo3DbBackend = $this->getMock('Tx_Extbase_Persistence_Storage_Typo3DbBackend', array('dummy'), array(), '', FALSE);
		$mockTypo3DbBackend->countRows($mockQueryObjectModel);
	}

	/**
	 * @test
	 */
	public function joinStatementGenerationWorks() {
		$mockLeftSource = $this->getMock('Tx_Extbase_Persistence_QOM_Selector', array(), array(), '', FALSE);
		$mockLeftSource->expects($this->any())->method('getSelectorName')->will($this->returnValue('left_selector_name'));
		$mockLeftSource->expects($this->any())->method('getNodeTypeName')->will($this->returnValue('left_nodetype_name'));
		
		$mockRightSource = $this->getMock('Tx_Extbase_Persistence_QOM_Selector', array(), array(), '', FALSE);
		$mockRightSource->expects($this->any())->method('getSelectorName')->will($this->returnValue('right_selector_name'));
		$mockRightSource->expects($this->any())->method('getNodeTypeName')->will($this->returnValue('right_nodetype_name'));
		
		$mockJoinCondition = $this->getMock('Tx_Extbase_Persistence_QOM_EquiJoinCondition', array('getSelector1Name', 'getSelector2Name', 'getProperty1Name', 'getProperty2Name'), array(), '', FALSE);
		$mockJoinCondition->expects($this->any())->method('getSelector1Name')->will($this->returnValue('first_selector'));
		$mockJoinCondition->expects($this->any())->method('getSelector2Name')->will($this->returnValue('second_selector'));
		$mockJoinCondition->expects($this->any())->method('getProperty1Name')->will($this->returnValue('firstProperty'));
		$mockJoinCondition->expects($this->any())->method('getProperty2Name')->will($this->returnValue('secondProperty'));
		
		$mockJoin  = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_QOM_Join'), array('getLeft', 'getRight'), array(), '', FALSE);
		$mockJoin->_set('joinCondition', $mockJoinCondition);
		$mockJoin->_set('joinType', $mockJoinCondition);		
		$mockJoin->expects($this->any())->method('getLeft')->will($this->returnValue($mockLeftSource));
		$mockJoin->expects($this->any())->method('getRight')->will($this->returnValue($mockRightSource));
		
		$mockDataMapper = $this->getMock('Tx_Extbase_Persistence_Mapper_DataMapper', array('convertPropertyNameToColumnName'), array(), '', FALSE);
		$mockDataMapper->expects($this->any())
			->method('convertPropertyNameToColumnName')
			->with(
				$this->logicalOr(
					$this->equalTo('firstProperty'),
					$this->equalTo('secondProperty')
					)
				)
			->will($this->returnValue('resulting_fieldname'));
		
		$sql = array();
		$parameters = array();
		$mockQueryObjectModel = $this->getMock('Tx_Extbase_Persistence_QOM_QueryObjectModelInterface');
		$mockTypo3DbBackend = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_Storage_Typo3DbBackend'), array('parserJoin'), array(), '', FALSE);		
		$mockTypo3DbBackend->_set('dataMapper', $mockDataMapper);
		$mockTypo3DbBackend->_callRef('parseJoin', $mockQueryObjectModel, $mockJoin, $sql, $parameters);
		
		$expecedSql = array(
			'fields' => array('left_selector_name.*', 'right_selector_name.*'),
			'tables' => array(
				'left_selector_name LEFT JOIN right_selector_name', 
				'ON first_selector.resulting_fieldname = second_selector.resulting_fieldname'
				)
			);

		$this->assertEquals($expecedSql, $sql);
	}

	/**
	 * @test
	 */
	public function orderStatementGenerationWorks() {
		$mockPropertyValue = $this->getMock('Tx_Extbase_Persistence_QOM_PropertyValue', array('getPropertyName', 'getSelectorname'), array(), '', FALSE);
		$mockPropertyValue->expects($this->once())->method('getPropertyName')->will($this->returnValue('fooProperty'));
		$mockPropertyValue->expects($this->once())->method('getSelectorName')->will($this->returnValue('tx_myext_tablenamefromproperty'));
		
		$mockOrdering1 = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_QOM_Ordering'), array('getOrder', 'getOperand'), array(), '', FALSE);
		$mockOrdering1->expects($this->once())->method('getOrder')->will($this->returnValue(Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_ORDER_ASCENDING));
		$mockOrdering1->expects($this->once())->method('getOperand')->will($this->returnValue($mockPropertyValue));
		$orderings = array($mockOrdering1);

		$mockSource = $this->getMock('Tx_Extbase_Persistence_QOM_Selector', array('getSelectorName', 'getNodeTypeName'), array(), '', FALSE);
		$mockSource->expects($this->any())->method('getSelectorName')->will($this->returnValue('tx_myext_tablename'));
		$mockSource->expects($this->any())->method('getNodeTypeName')->will($this->returnValue('Tx_MyExt_ClassName'));

		$mockDataMapper = $this->getMock('Tx_Extbase_Persistence_Mapper_DataMapper', array('convertPropertyNameToColumnName'), array(), '', FALSE);
		$mockDataMapper->expects($this->once())->method('convertPropertyNameToColumnName')->with('fooProperty', 'Tx_MyExt_ClassName')->will($this->returnValue('converted_fieldname'));
		
		$sql = array();
		$mockTypo3DbBackend = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_Storage_Typo3DbBackend'), array('parserOrderings'), array(), '', FALSE);		
		$mockTypo3DbBackend->_set('dataMapper', $mockDataMapper);
		$mockTypo3DbBackend->_callRef('parseOrderings', $orderings, $mockSource, $sql);
			
		$expecedSql = array('orderings' => array('tx_myext_tablenamefromproperty.converted_fieldname ASC'));
		$this->assertSame($expecedSql, $sql);
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_Persistence_Exception_UnsupportedOrder
	 */
	public function orderStatementGenerationThrowsExceptionOnUnsupportedOrder() {
		$mockPropertyValue = $this->getMock('Tx_Extbase_Persistence_QOM_PropertyValue', array('getPropertyName', 'getSelectorname'), array(), '', FALSE);
		$mockPropertyValue->expects($this->never())->method('getPropertyName');
		$mockPropertyValue->expects($this->never())->method('getSelectorName');
		
		$mockOrdering1 = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_QOM_Ordering'), array('getOrder', 'getOperand'), array(), '', FALSE);
		$mockOrdering1->expects($this->once())->method('getOrder')->will($this->returnValue('unsupported_order'));
		$mockOrdering1->expects($this->once())->method('getOperand')->will($this->returnValue($mockPropertyValue));
		$orderings = array($mockOrdering1);

		$mockSource = $this->getMock('Tx_Extbase_Persistence_QOM_Selector', array('getSelectorName', 'getNodeTypeName'), array(), '', FALSE);
		$mockSource->expects($this->any())->method('getSelectorName')->will($this->returnValue('tx_myext_tablename'));
		
		$mockDataMapper = $this->getMock('Tx_Extbase_Persistence_Mapper_DataMapper', array('convertPropertyNameToColumnName'), array(), '', FALSE);
		$mockDataMapper->expects($this->never())->method('convertPropertyNameToColumnName');
		
		$sql = array();
		$mockTypo3DbBackend = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_Storage_Typo3DbBackend'), array('parserOrderings'), array(), '', FALSE);		
		$mockTypo3DbBackend->_set('dataMapper', $mockDataMapper);
		$mockTypo3DbBackend->_callRef('parseOrderings', $orderings, $mockSource, $sql);
	}

	/**
	 * @test
	 */
	public function orderStatementGenerationWorksWithMultipleOrderings() {
		$mockPropertyValue1 = $this->getMock('Tx_Extbase_Persistence_QOM_PropertyValue', array('getPropertyName', 'getSelectorname'), array(), '', FALSE);
		$mockPropertyValue1->expects($this->atLeastOnce())->method('getPropertyName')->will($this->returnValue('fooProperty'));
		$mockPropertyValue1->expects($this->atLeastOnce())->method('getSelectorName')->will($this->returnValue('tx_myext_bar'));
		
		$mockPropertyValue2 = $this->getMock('Tx_Extbase_Persistence_QOM_PropertyValue', array('getPropertyName', 'getSelectorname'), array(), '', FALSE);
		$mockPropertyValue2->expects($this->atLeastOnce())->method('getPropertyName')->will($this->returnValue('barProperty'));
		$mockPropertyValue2->expects($this->atLeastOnce())->method('getSelectorName')->will($this->returnValue('tx_myext_blub'));
		
		$mockOrdering1 = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_QOM_Ordering'), array('getOrder', 'getOperand'), array(), '', FALSE);
		$mockOrdering1->expects($this->once())->method('getOrder')->will($this->returnValue(Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_ORDER_ASCENDING));
		$mockOrdering1->expects($this->once())->method('getOperand')->will($this->returnValue($mockPropertyValue1));
		$mockOrdering2 = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_QOM_Ordering'), array('getOrder', 'getOperand'), array(), '', FALSE);
		$mockOrdering2->expects($this->once())->method('getOrder')->will($this->returnValue(Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_ORDER_DESCENDING));
		$mockOrdering2->expects($this->once())->method('getOperand')->will($this->returnValue($mockPropertyValue2));
		$orderings = array($mockOrdering1, $mockOrdering2);

		$mockSource = $this->getMock('Tx_Extbase_Persistence_QOM_Selector', array(), array(), '', FALSE);

		$mockDataMapper = $this->getMock('Tx_Extbase_Persistence_Mapper_DataMapper', array('convertPropertyNameToColumnName'), array(), '', FALSE);
		$mockDataMapper->expects($this->atLeastOnce())->method('convertPropertyNameToColumnName')->will($this->returnValue('foo_field'));
		
		$sql = array();
		$mockTypo3DbBackend = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_Storage_Typo3DbBackend'), array('parserOrderings'), array(), '', FALSE);		
		$mockTypo3DbBackend->_set('dataMapper', $mockDataMapper);
		$mockTypo3DbBackend->_callRef('parseOrderings', $orderings, $mockSource, $sql);
		
		$expecedSql = array('orderings' => array('tx_myext_bar.foo_field ASC', 'tx_myext_blub.foo_field DESC'));
		$this->assertEquals($expecedSql, $sql);
	}

	/**
	 * @test
	 */
	public function orderStatementGenerationWorksWithDescendingOrder() {
		$mockPropertyValue = $this->getMock('Tx_Extbase_Persistence_QOM_PropertyValue', array('getPropertyName', 'getSelectorname'), array(), '', FALSE);
		$mockPropertyValue->expects($this->once())->method('getPropertyName')->will($this->returnValue('fooProperty'));
		$mockPropertyValue->expects($this->once())->method('getSelectorName')->will($this->returnValue(''));
		
		$mockOrdering1 = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_QOM_Ordering'), array('getOrder', 'getOperand'), array(), '', FALSE);
		$mockOrdering1->expects($this->once())->method('getOrder')->will($this->returnValue(Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_ORDER_DESCENDING));
		$mockOrdering1->expects($this->once())->method('getOperand')->will($this->returnValue($mockPropertyValue));
		$orderings = array($mockOrdering1);

		$mockSource = $this->getMock('Tx_Extbase_Persistence_QOM_Selector', array(), array(), '', FALSE);

		$mockDataMapper = $this->getMock('Tx_Extbase_Persistence_Mapper_DataMapper', array('convertPropertyNameToColumnName'), array(), '', FALSE);
		$mockDataMapper->expects($this->once())->method('convertPropertyNameToColumnName')->with('fooProperty', '')->will($this->returnValue('bar_property'));
		
		$sql = array();
		$mockTypo3DbBackend = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_Storage_Typo3DbBackend'), array('parserOrderings'), array(), '', FALSE);		
		$mockTypo3DbBackend->_set('dataMapper', $mockDataMapper);
		$mockTypo3DbBackend->_callRef('parseOrderings', $orderings, $mockSource, $sql);
		
		$expecedSql = array('orderings' => array('bar_property DESC'));
		$this->assertEquals($expecedSql, $sql);
	}

	/**
	 * @test
	 */
	public function orderStatementGenerationWorksWithTheSourceSelectorNameIfNotSpecifiedInThePropertyValue() {
		$mockPropertyValue = $this->getMock('Tx_Extbase_Persistence_QOM_PropertyValue', array('getPropertyName', 'getSelectorname'), array(), '', FALSE);
		$mockPropertyValue->expects($this->once())->method('getPropertyName')->will($this->returnValue('fooProperty'));
		$mockPropertyValue->expects($this->once())->method('getSelectorName')->will($this->returnValue(''));
		
		$mockOrdering1 = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_QOM_Ordering'), array('getOrder', 'getOperand'), array(), '', FALSE);
		$mockOrdering1->expects($this->once())->method('getOrder')->will($this->returnValue(Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_ORDER_ASCENDING));
		$mockOrdering1->expects($this->once())->method('getOperand')->will($this->returnValue($mockPropertyValue));
		$orderings = array($mockOrdering1);

		$mockSource = $this->getMock('Tx_Extbase_Persistence_QOM_Selector', array(), array(), '', FALSE);

		$mockDataMapper = $this->getMock('Tx_Extbase_Persistence_Mapper_DataMapper', array('convertPropertyNameToColumnName'), array(), '', FALSE);
		$mockDataMapper->expects($this->once())->method('convertPropertyNameToColumnName')->with('fooProperty', '')->will($this->returnValue('bar_property'));
		
		$sql = array();
		$mockTypo3DbBackend = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_Storage_Typo3DbBackend'), array('parserOrderings'), array(), '', FALSE);		
		$mockTypo3DbBackend->_set('dataMapper', $mockDataMapper);
		$mockTypo3DbBackend->_callRef('parseOrderings', $orderings, $mockSource, $sql);
		
		$expecedSql = array('orderings' => array('bar_property ASC'));
		$this->assertEquals($expecedSql, $sql);
	}

}
?>