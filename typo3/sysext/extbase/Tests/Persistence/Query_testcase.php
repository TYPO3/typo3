<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Christopher Hlubek <hlubek@networkteam.com>
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

class Tx_Extbase_Persistence_Query_testcase extends Tx_Extbase_Base_testcase {

	public function setUp() {
		$dataBase = $this->getMock('t3lib_DB', array('fullQuoteStr'));
		$dataBase->expects($this->any())->method('fullQuoteStr')->will($this->returnValue($this->returnArgument(0)));
		$GLOBALS['TSFE'] = $this->getMock('tslib_fe', array('includeTCA'), array(), '', FALSE);
		$GLOBALS['TSFE']->expects($this->any())
			->method('includeTCA')
			->will($this->returnValue(NULL));		
		$this->dataMapper = $this->getMock('Tx_Extbase_Persistence_Mapper_ObjectRelationalMapper', array(), array($dataBase));
		$this->query = new Tx_Extbase_Persistence_Query('Tx_MyExtension_Domain_Model_Class');
		$this->query->injectPersistenceBackend($dataBase);
		$this->query->injectDataMapper($this->dataMapper);
	}

	public function test_queryObjectImplementsQueryInterface() {
		$query = clone($this->query);
		
		$this->assertTrue($query instanceof Tx_Extbase_Persistence_QueryInterface);
	}
	
	// public function test_generateWhereClauseWithRawStatement() {
	// 	$query = $this->getMock($this->buildAccessibleProxy(('Tx_Extbase_Persistence_Query'), array(), array('Tx_MyExtension_Domain_Model_Class')));
	// 	$constraint = $this->getMock('Tx_Extbase_Persistence_RawSqlConstraint', array('dummy'), array("SELECT * FROM tx_myextension_domain_model_class WHERE foo='bar'"));
	// 	$query->matching($constraint);
	// 	
	// 	$this->assertEquals($constraint, $query->_get('constraint'));
	// }
	// 
	// public function test_generateWhereClauseWithPlaceholders() {
	// 	$query = clone($this->query);
	// 	
	// 	$dataMap = $this->getMock('Tx_Extbase_Persistence_Mapper_DataMap', array('convertPropertyValueToFieldValue'), array('Tx_MyExtension_Domain_Model_Class'));
	// 	$dataMap->expects($this->any())
	// 		->method('convertPropertyValueToFieldValue')
	// 		->will($this->returnCallback(array($this, 'convertPropertyValueToFieldValue')));
	// 	$whereClause = $query->generateWhereClause($dataMap,
	// 		array(
	// 			array('name LIKE ? OR name LIKE ?', 'foo', 'bar'),
	// 			array('hidden = ?', FALSE)
	// 			)
	// 		);
	// 	
	// 	$this->assertEquals("(name LIKE 'foo' OR name LIKE 'bar') AND (hidden = 0)", $whereClause);
	// }
	// 
	// public function test_generateWhereClauseWithExample() {
	// 	$query = clone($this->query);
	// 	$dataMap = $this->getMock('Tx_Extbase_Persistence_Mapper_DataMap', array('convertPropertyValueToFieldValue', 'getColumnMap'), array('Tx_MyExtension_Domain_Model_Class'));
	// 	$dataMap->expects($this->any())
	// 		->method('convertPropertyValueToFieldValue')
	// 		->will($this->returnCallback(array($this, 'convertPropertyValueToFieldValue')));		
	// 	$dataMap->expects($this->any())
	// 		->method('getColumnMap')
	// 		->withAnyParameters()
	// 		->will($this->returnCallback(array($this, 'getColumnMap')));
	// 	$whereClause = $query->generateWhereClause($dataMap,
	// 		array(
	// 			'name' => 'foo',
	// 			'hidden' => FALSE
	// 			)
	// 		);
	// 	
	// 	$this->assertEquals("(tx_myextension_domain_model_class.name = 'foo') AND (tx_myextension_domain_model_class.hidden = 0)", $whereClause);
	// }
	// 
	// public function test_generateWhereClauseWithNestedExample() {
	// 	$query = clone($this->query);
	// 	$dataMap = $this->getMock('Tx_Extbase_Persistence_Mapper_DataMap', array('convertPropertyValueToFieldValue', 'getColumnMap'), array('Tx_MyExtension_Domain_Model_Class'));
	// 	$dataMap->expects($this->any())
	// 		->method('convertPropertyValueToFieldValue')
	// 		->will($this->returnCallback(array($this, 'convertPropertyValueToFieldValue')));		
	// 	$dataMap->expects($this->any())
	// 		->method('getColumnMap')
	// 		->withAnyParameters()
	// 		->will($this->returnCallback(array($this, 'getColumnMap')));
	// 	$whereClause = $query->generateWhereClause($dataMap,
	// 		array(
	// 			'name' => 'foo',
	// 			'hidden' => FALSE
	// 			)
	// 		);
	// 	$whereClause = $query->generateWhereClause($dataMap,
	// 		array(
	// 			'hidden' => FALSE,
	// 			'posts' => array(
	// 				'title' => 'foo'
	// 			)
	// 		));
	// 	
	// 	$this->assertEquals("(tx_blogexample_domain_model_blog.hidden = 0) AND ((tx_blogexample_domain_model_post.title = 'foo'))", $whereClause);
	// }
	// 
	// public function convertPropertyValueToFieldValue($propertyValue) {
	// 	if ($propertyValue === TRUE) return 1;
	// 	if ($propertyValue === FALSE) return 0;
	// 	return $GLOBALS['TYPO3_DB']->fullQuoteStr((string)$propertyValue, '');
	// }
	// 
	// public function getColumnMap($propertyName) {
	// 	$columnMap = $this->getMock('Tx_Extbase_Persistence_Mapper_ColumnMap', array('getColumnName'), array($propertyName));
	// 	if ($propertyName === 'name') {
	// 		$columnMap->expects($this->any())
	// 			->method('getColumnName')
	// 			->will($this->returnValue('name'));
	// 	} elseif ($propertyName === 'hidden') {
	// 		$columnMap->expects($this->any())
	// 			->method('getColumnName')
	// 			->will($this->returnValue('hidden'));
	// 	}
	// 	return $columnMap;
	// }
	// 	
}

?>