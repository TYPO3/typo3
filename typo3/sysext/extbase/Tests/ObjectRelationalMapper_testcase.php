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

require_once('Base_testcase.php');

class Tx_ExtBase_Persistence_Mapper_ObjectRelationalMapper_testcase extends Tx_ExtBase_Base_testcase {

	public function setUp() {
		$GLOBALS['TSFE'] = $this->getMock('tslib_fe', array('includeTCA'));
		$GLOBALS['TSFE']->expects($this->any())
			->method('includeTCA')
			->will($this->returnValue(NULL));
		
		$GLOBALS['TYPO3_DB'] = $this->getMock('tslib_DB', array('fullQuoteStr'));
	}

	public function test_FindByConditionWithPlaceholders() {
		$mapper = $this->getMock('Tx_ExtBase_Persistence_Mapper_ObjectRelationalMapper', array('fetch'));
		$mapper->expects($this->once())
			->method('fetch')
			->with($this->equalTo('Tx_BlogExample_Domain_Blog'), $this->equalTo('(name LIKE "foo" OR name LIKE "bar") AND (hidden = 0)'));
		
		$GLOBALS['TYPO3_DB']->expects($this->at(0))
			->method('fullQuoteStr')
			->with($this->equalTo('foo'))
			->will($this->returnValue('"foo"'));

		$GLOBALS['TYPO3_DB']->expects($this->at(1))
			->method('fullQuoteStr')
			->with($this->equalTo('bar'))
			->will($this->returnValue('"bar"'));

		$GLOBALS['TYPO3_DB']->expects($this->at(2))
			->method('fullQuoteStr')
			->with($this->equalTo('0'))
			->will($this->returnValue('0'));
		
		$mapper->find('Tx_BlogExample_Domain_Blog',
			array(
				array('name LIKE ? OR name LIKE ?', 'foo', 'bar'),
				array('hidden = ?', FALSE)
			));
	}

	public function test_FindByConditionWithExample() {
		$mapper = $this->getMock('Tx_ExtBase_Persistence_Mapper_ObjectRelationalMapper', array('fetch', 'getDataMap'));
		$mapper->expects($this->once())
			->method('fetch')
			->with($this->equalTo('Tx_BlogExample_Domain_Blog'), $this->equalTo('(blog_name = "foo") AND (hidden = 0)'));

		$columnMap1 = $this->getMock('Tx_ExtBase_Persistence_Mapper_ColumnMap', array('getColumnName'), array(), '', FALSE);
		$columnMap1->expects($this->once())
			->method('getColumnName')
			->will($this->returnValue('blog_name'));

		$columnMap2 = $this->getMock('Tx_ExtBase_Persistence_Mapper_ColumnMap', array('getColumnName'), array(), '', FALSE);
		$columnMap2->expects($this->once())
			->method('getColumnName')
			->will($this->returnValue('hidden'));

		$dataMap = $this->getMock('Tx_ExtBase_Persistence_Mapper_DataMap', array('getColumnMap'), array(), '', FALSE);
		$dataMap->expects($this->at(0))
			->method('getColumnMap')
			->with($this->equalTo('blogName'))
			->will($this->returnValue($columnMap1));

		$dataMap->expects($this->at(1))
			->method('getColumnMap')
			->with($this->equalTo('hidden'))
			->will($this->returnValue($columnMap2));
		
		$mapper->expects($this->any())
			->method('getDataMap')
			->with($this->equalTo('Tx_BlogExample_Domain_Blog'))
			->will($this->returnValue($dataMap));
		
		$GLOBALS['TYPO3_DB']->expects($this->at(0))
			->method('fullQuoteStr')
			->with($this->equalTo('foo'))
			->will($this->returnValue('"foo"'));

		$GLOBALS['TYPO3_DB']->expects($this->at(1))
			->method('fullQuoteStr')
			->with($this->equalTo('0'))
			->will($this->returnValue('0'));
		
		$mapper->find('Tx_BlogExample_Domain_Blog',
			array(
				'blogName' => 'foo',
				'hidden' => FALSE
			));
	}
}
?>