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
 * @package Extbase
 * @subpackage extbase
 * @version $Id: FloatValidator_testcase.php 2293 2010-05-25 11:11:15Z jocrau $
 */
class Tx_Extbase_Validation_Validator_FloatValidator_testcase extends Tx_Extbase_BaseTestCase {

	/**
	 * An array of valid floating point numbers addresses
	 * @var array
	 */
	protected $validFloatingPointNumbers;
	
	/**
	 * An array of invalid floating point numbers addresses
	 * @var array
	 */
	protected $invalidFloatingPointNumbers;
	
	public function setUp() {
		$this->validFloatingPointNumbers = array(
			1029437.234726,
			'123.45',
			'+123.45',
			'-123.45',
			'123.45e3',
			123.45e3
			);
			
		$this->invalidFloatingPointNumbers = array(
			1029437,
			'1029437',
			'not a number'
			);
	}
	
	/**
	 * @test
	 */
	public function floatValidatorReturnsTrueForAValidFloat() {
		$floatValidator = new Tx_Extbase_Validation_Validator_FloatValidator();
		foreach ($this->validFloatingPointNumbers as $floatingPointNumber) {
			$this->assertTrue($floatValidator->isValid($floatingPointNumber), "$floatingPointNumber was declared to be invalid, but it is valid.");
		}
	}

	/**
	 * @test
	 */
	public function floatValidatorReturnsFalseForAnInvalidFloat() {
		$floatValidator = $this->getMock('Tx_Extbase_Validation_Validator_FloatValidator', array('addError'), array(), '', FALSE);
		foreach ($this->invalidFloatingPointNumbers as $floatingPointNumber) {
			$this->assertFalse($floatValidator->isValid($floatingPointNumber), "$floatingPointNumber was declared to be valid, but it is invalid.");
		}
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