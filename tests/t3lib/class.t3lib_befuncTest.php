<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2011 Oliver Klee (typo3-coding@oliverklee.de)
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
 * Testcase for the t3lib_BEfunc class in the TYPO3 core.
 *
 * @package TYPO3
 * @subpackage t3lib
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class t3lib_befuncTest extends tx_phpunit_testcase {
	/**
	 * @var t3lib_BEfunc
	 */
	private $fixture;

	public function setUp() {
		$this->fixture = new t3lib_BEfunc();
	}

	public function tearDown() {
		unset($this->fixture);
	}


	///////////////////////////////////////
	// Tests concerning getProcessedValue
	///////////////////////////////////////

	/**
	 * @test
	 *
	 * @see http://bugs.typo3.org/view.php?id=11875
	 */
	public function getProcessedValueForZeroStringIsZero() {
		$this->assertEquals(
			'0',
			$this->fixture->getProcessedValue(
				'tt_content', 'header', '0'
			)
		);
	}
}
?>