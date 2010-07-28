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
 * Testcase for the number range validator
 *
 * @package Extbase
 * @subpackage extbase
 * @version $Id: NumberRangeValidator_testcase.php 1408 2009-10-08 13:15:09Z jocrau $
 */
class Tx_Extbase_Validation_Validator_NumberRangeValidator_testcase extends Tx_Extbase_BaseTestCase {

	/**
	 * @test
	 */
	public function numberRangeValidatorReturnsTrueForASimpleIntegerInRange() {
		$numberRangeValidator = new Tx_Extbase_Validation_Validator_NumberRangeValidator();
		$numberRangeValidator->setOptions(array('startRange' => 0, 'endRange' => 1000));

		$this->assertTrue($numberRangeValidator->isValid(10.5));
	}

	/**
	 * @test
	 */
	public function numberRangeValidatorReturnsFalseForANumberOutOfRange() {
		$numberRangeValidator = $this->getMock('Tx_Extbase_Validation_Validator_NumberRangeValidator', array('addError'), array(), '', FALSE);
		$numberRangeValidator->setOptions(array('startRange' => 0, 'endRange' => 1000));
		$this->assertFalse($numberRangeValidator->isValid(1000.1));
	}

	/**
	 * @test
	 */
	public function numberRangeValidatorReturnsTrueForANumberInReversedRange() {
		$numberRangeValidator = $this->getMock('Tx_Extbase_Validation_Validator_NumberRangeValidator', array('addError'), array(), '', FALSE);
		$numberRangeValidator->setOptions(array('startRange' => 1000, 'endRange' => 0));
		$this->assertTrue($numberRangeValidator->isValid(100));
	}

	/**
	 * @test
	 */
	public function numberRangeValidatorReturnsFalseForAString() {
		$numberRangeValidator = $this->getMock('Tx_Extbase_Validation_Validator_NumberRangeValidator', array('addError'), array(), '', FALSE);
		$numberRangeValidator->setOptions(array('startRange' => 0, 'endRange' => 1000));
		$this->assertFalse($numberRangeValidator->isValid('not a number'));
	}

	/**
	 * @test
	 */
	public function numberRangeValidatorCreatesTheCorrectErrorForANumberOutOfRange() {
		$numberRangeValidator = $this->getMock('Tx_Extbase_Validation_Validator_NumberRangeValidator', array('addError'), array(), '', FALSE);
		$numberRangeValidator->expects($this->once())->method('addError')->with('The given subject was not in the valid range (1 - 42).', 1221561046);
		$numberRangeValidator->setOptions(array('startRange' => 1, 'endRange' => 42));
		$numberRangeValidator->isValid(4711);
	}

	/**
	 * @test
	 */
	public function numberRangeValidatorCreatesTheCorrectErrorForAStringSubject() {
		$numberRangeValidator = $this->getMock('Tx_Extbase_Validation_Validator_NumberRangeValidator', array('addError'), array(), '', FALSE);
		$numberRangeValidator->expects($this->once())->method('addError')->with('The given subject was not a valid number.', 1221563685);
		$numberRangeValidator->setOptions(array('startRange' => 0, 'endRange' => 42));
		$numberRangeValidator->isValid('this is not between 0 an 42');
	}
}

?>