<?php
/***************************************************************
* Copyright notice
*
* (c) 2010 Ernesto Baschny (ernst@cron-it.de)
* All rights reserved
*
* This script is part of the TYPO3 project. The TYPO3 project is
* free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* The GNU General Public License can be found at
* http://www.gnu.org/copyleft/gpl.html.
*
* This script is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Testcase for the t3lib_cs class in the TYPO3 Core.
 *
 * @package TYPO3
 * @subpackage t3lib
 *
 * @author Ernesto Baschny <ernst@cron-it.de>
 */
class t3lib_dbTest extends tx_phpunit_testcase {
	/**
	 * @var t3lib_db
	 */
	private $fixture = null;

	public function setUp() {
		$this->fixture = new t3lib_db();
	}

	public function tearDown() {
		unset($this->fixture);
	}


	////////////////////////////////
	// Tests concerning listQuery
	////////////////////////////////

	/**
	 * @test
	 *
	 * @see http://bugs.typo3.org/view.php?id=15211
	 */
	public function listQueryWithIntegerCommaAsValue() {
			// Note: 44 = ord(',')
		$this->assertEquals(
			$this->fixture->listQuery('dummy', 44, 'table'),
			$this->fixture->listQuery('dummy', '44', 'table')
		);
	}

}
?>