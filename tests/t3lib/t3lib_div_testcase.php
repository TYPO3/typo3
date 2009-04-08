<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Ingo Renner <ingo@typo3.org>
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


/**
 * Testcase for class t3lib_div
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_div_testcase extends tx_phpunit_testcase {

	/**
	 * @test
	 */
	public function checkTrimExplodeTrimsSpacesAtElementStartAndEnd() {
		$testString = ' a , b , c ,d ,,  e,f,';
		$expectedArray = array('a', 'b', 'c', 'd', '', 'e', 'f', '');
		$actualArray = t3lib_div::trimExplode(',', $testString);

		$this->assertEquals($expectedArray, $actualArray);
	}

	/**
	 * @test
	 */
	public function checkTrimExplodeRemovesNewLines() {
		$testString = ' a , b , ' . chr(10) . ' ,d ,,  e,f,';
		$expectedArray = array('a', 'b', 'd', 'e', 'f');
		$actualArray = t3lib_div::trimExplode(',', $testString, true);

		$this->assertEquals($expectedArray, $actualArray);
	}

	/**
	 * @test
	 */
	public function checkTrimExplodeRemovesEmptyElements() {
		$testString = 'a , b , c , ,d ,, ,e,f,';
		$expectedArray = array('a', 'b', 'c', 'd', 'e', 'f');
		$actualArray = t3lib_div::trimExplode(',', $testString, true);

		$this->assertEquals($expectedArray, $actualArray);
	}

	/**
	 * @test
	 */
	public function checkTrimExplodeLimitsResultsToFirstXElementsWithPositiveParameter() {
		$testString = ' a , b , c , , d,, ,e ';
		$expectedArray = array('a', 'b', 'c'); // limiting returns the rest of the string as the last element
		$actualArray = t3lib_div::trimExplode(',', $testString, false, 3);

		$this->assertEquals($expectedArray, $actualArray);
	}

	/**
	 * @test
	 */
	public function checkTrimExplodeLimitsResultsToLastXElementsWithNegativeParameter() {
		$testString = ' a , b , c , d, ,e, f , , ';
		$expectedArray = array('a', 'b', 'c'); // limiting returns the rest of the string as the last element
		$actualArray = t3lib_div::trimExplode(',', $testString, true, -3);

		$this->assertEquals($expectedArray, $actualArray);
	}

	/**
	 * @test
	 */
	public function checkTrimExplodeKeepsZeroAsString() {
		$testString = 'a , b , c , ,d ,, ,e,f, 0 ,';
		$expectedArray = array('a', 'b', 'c', 'd', 'e', 'f', '0');
		$actualArray = t3lib_div::trimExplode(',', $testString, true);

		$this->assertEquals($expectedArray, $actualArray);
	}

	/**
	 * Checks whether measurement strings like "100k" return the accordant
	 * byte representation like 102400 in this case.
	 *
	 * @test
	 */
	public function checkGetBytesFromSizeMeasurement() {
		$this->assertEquals(
			'102400',
			t3lib_div::getBytesFromSizeMeasurement('100k')
		);

		$this->assertEquals(
			'104857600',
			t3lib_div::getBytesFromSizeMeasurement('100m')
		);

		$this->assertEquals(
			'107374182400',
			t3lib_div::getBytesFromSizeMeasurement('100g')
		);
	}

	/**
	 * @test
	 */
	public function checkIndpEnvTypo3SitePathNotEmpty() {
		$actualEnv = t3lib_div::getIndpEnv('TYPO3_SITE_PATH');
		$this->assertTrue(strlen($actualEnv) >= 1);
		$this->assertEquals('/', $actualEnv{0});
		$this->assertEquals('/', $actualEnv{strlen($actualEnv) - 1});
	}
}

?>