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

/**
 * Testcase for the integer validator
 *
 * @package Extbase
 * @subpackage extbase
 * @version $Id: IntegerValidator_testcase.php 2428 2010-07-20 10:18:51Z jocrau $
 */
class Tx_Extbase_Tests_Unit_Validation_Validator_IntegerValidatorTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * Data provider with valid integer numbers
	 *
	 * @return array Integers, both as int and strings
	 */
	public function validIntegerNumbers() {
		return array(
			array(1029437),
			array(-666),
			array('12345'),
			array('+12345'),
			array('-12345')
			);
	}

	/**
	 * Data provider with invalid integer numbers
	 *
	 * @return array Various values of int, float and strings
	 */
	public function invalidIntegerNumbers() {
		return array(
			array('not a number'),
			array(3.1415),
			array(-0.75),
			array('12345.987'),
			array('-123.45')
			);
	}

	/**
	 * @test
	 * @dataProvider validIntegerNumbers
	 */
	public function integerValidatorReturnsTrueForAValidInteger($number) {
		$integerValidator = new Tx_Extbase_Validation_Validator_IntegerValidator();
		$this->assertTrue($integerValidator->isValid($number), "Validator declared $number as invalid though it is valid.");
	}

	/**
	 * @test
	 * @dataProvider invalidIntegerNumbers
	 */
	public function integerValidatorReturnsFalseForAnInvalidInteger($number) {
		$integerValidator = $this->getMock('Tx_Extbase_Validation_Validator_IntegerValidator', array('addError'), array(), '', FALSE);
		$this->assertFalse($integerValidator->isValid($number), "Validator declared $number as valid though it is invalid.");
	}

	/**
	 * @test
	 */
	public function integerValidatorCreatesTheCorrectErrorForAnInvalidSubject() {
		$integerValidator = $this->getMock('Tx_Extbase_Validation_Validator_IntegerValidator', array('addError'), array(), '', FALSE);
		$integerValidator->expects($this->once())->method('addError')->with('The given subject was not a valid integer.', 1221560494);
		$integerValidator->isValid('not a number');
	}

}

?>