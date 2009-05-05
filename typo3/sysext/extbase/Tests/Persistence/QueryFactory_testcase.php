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

class Tx_Extbase_Persistence_QueryFactory_testcase extends Tx_Extbase_Base_testcase {

	public function test_createIsInvokedOnce() {
		$queryFactory = $this->getMock('Tx_Extbase_Persistence_QueryFactory', array('create'));
		$queryFactory->expects($this->once())
			->method('create')
			->with($this->equalTo('MyClassName'));
		$query = $queryFactory->create('MyClassName');
	}

	public function test_queryFactoryCanCreateQuery() {
		$queryFactory = new Tx_Extbase_Persistence_QueryFactory;
		$query = $queryFactory->create('Tx_Extbase_Persistence_QueryFactory'); // TODO Encapsulate t3lib_div::makeInstance() to make it mockable
		$this->assertTrue(in_array('Tx_Extbase_Persistence_QueryInterface', class_implements($query)), 'The query was not an instance of Tx_Extbase_Persistence_QueryInterface');
		
	}

}
?>