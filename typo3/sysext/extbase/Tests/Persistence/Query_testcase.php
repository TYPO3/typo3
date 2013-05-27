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

class Tx_Extbase_Persistence_Query_testcase extends Tx_Extbase_BaseTestCase {
	
	public function setUp() {
	}
	
	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function setLimitAcceptsOnlyIntegers() {
		$query = new Tx_Extbase_Persistence_Query('Foo_Class_Name');
		$query->setLimit(1.5);
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function setLimitRejectsIntegersLessThanOne() {
		$query = new Tx_Extbase_Persistence_Query('Foo_Class_Name');
		$query->setLimit(0);
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function setOffsetAcceptsOnlyIntegers() {
		$query = new Tx_Extbase_Persistence_Query('Foo_Class_Name');
		$query->setOffset(1.5);
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function setOffsetRejectsIntegersLessThanZero() {
		$query = new Tx_Extbase_Persistence_Query('Foo_Class_Name');
		$query->setOffset(-1);
	}
	
}
?>