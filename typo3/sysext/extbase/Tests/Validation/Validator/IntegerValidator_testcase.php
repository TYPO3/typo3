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
 * @version $Id: IntegerValidator_testcase.php 2293 2010-05-25 11:11:15Z jocrau $
 */
class Tx_Extbase_Validation_Validator_IntegerValidator_testcase extends Tx_Extbase_BaseTestCase {

	/**
	 * An array of valid floating point numbers addresses
	 * @var array
	 */
	protected $validIntegerNumbers;
	
	/**
	 * An array of invalid floating point numbers addresses
	 * @var array
	 */
	protected $invalidIntegerNumbers;
	
	public function setUp() {
		$this->validIntegerNumbers = array(
			1029437,
			'12345',
			'+12345',
			'-12345'
			);
			
		$this->invalidIntegerNumbers = array(
			'not a number',
			3.1415,
			'12345.987'
			);
	}
	
	/**
	 * @test
	 */
	public function integerValidatorReturnsTrueForAValidInteger() {
		$integerValidator = new Tx_Extbase_Validation_Validator_IntegerValidator();
		foreach ($this->validIntegerNumbers as $integerNumber) {
			$this->assertTrue($integerValidator->isValid($integerNumber), "$integerNumber was declared to be invalid, but it is valid.");
		}
	}

	/**
	 * Data provider with invalid email addresses
	 *
	 * @return array
	 */
	public function invalidIntegers() {
		return array(
			array('not a number'),
			array(3.1415),
			array('12345.987')
		);
	}

	/**
	 * @test
	 * @dataProvider invalidIntegers
	 */
	public function integerValidatorReturnsTrueForAnInvalidInteger() {
		$integerValidator = $this->getMock('Tx_Extbase_Validation_Validator_IntegerValidator', array('addError'), array(), '', FALSE);
		foreach ($this->invalidIntegerNumbers as $integerNumber) {
			$this->assertFalse($integerValidator->isValid($integerNumber), "$integerNumber was declared to be valid, but it is invalid.");
		}
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