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
 * Testcase for the float validator
 *
 * This testcase checks the expected behavior for Extbase < 1.4.0, to make sure
 * we do not break backwards compatibility.
 *
 * @package Extbase
 * @subpackage extbase
 * @version $Id: FloatValidator_testcase.php 2428 2010-07-20 10:18:51Z jocrau $
 */
class Tx_Extbase_Tests_Unit_Validation_Validator_BeforeExtbase14_FloatValidatorTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * Data provider with valid floating point numbers
	 *
	 * @return array Floats, both as float and string
	 */
	public function validFloatingPointNumbers() {
		return array(
			array(1029437.234726),
			array(-666.66),
			array('123.45'),
			array('+123.45'),
			array('-123.45'),
			array('123.45e3'),
			array(123.45e3)
		);
	}

	/**
	 * Data provider with valid floating point numbers
	 *
	 * @return array Floats, both as float and string
	 */
	public function invalidFloatingPointNumbers() {
		return array(
			array(1029437),
			array(-666),
			array('1029437'),
			array('-666'),
			array('not a number')
		);
	}

	/**
	 * @test
	 * @dataProvider validFloatingPointNumbers
	 */
	public function floatValidatorReturnsTrueForAValidFloat($number) {
		$floatValidator = new Tx_Extbase_Validation_Validator_FloatValidator();
		$this->assertTrue($floatValidator->isValid($number), "Validator declared $number as invalid though it is valid.");
	}

	/**
	 * @test
	 * @dataProvider invalidFloatingPointNumbers
	 */
	public function floatValidatorReturnsFalseForAnInvalidFloat($number) {
		$floatValidator = $this->getMock('Tx_Extbase_Validation_Validator_FloatValidator', array('addError'), array(), '', FALSE);
		$this->assertFalse($floatValidator->isValid($number), "Validator declared $number as valid though it is invalid.");
	}

	/**
	 * @test
	 */
	public function floatValidatorCreatesTheCorrectErrorForAnInvalidSubject() {
		$floatValidator = new Tx_Extbase_Validation_Validator_FloatValidator();
		$floatValidator = $this->getMock('Tx_Extbase_Validation_Validator_FloatValidator', array('addError'), array(), '', FALSE);
		$floatValidator->expects($this->once())->method('addError')->with('The given subject was not a valid float.', 1221560288);
		$floatValidator->isValid(123456);
	}

}

?>