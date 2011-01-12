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
 * Testcase for the not empty validator
 *
 * @package Extbase
 * @subpackage extbase
 * @version $Id: NotEmptyValidator_testcase.php 2428 2010-07-20 10:18:51Z jocrau $
 */
class Tx_Extbase_Tests_Unit_Validation_Validator_NotEmptyValidatorTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @test
	 */
	public function notEmptyValidatorReturnsTrueForASimpleString() {
		$notEmptyValidator = new Tx_Extbase_Validation_Validator_NotEmptyValidator();
		$this->assertTrue($notEmptyValidator->isValid('a not empty string'));
	}

	/**
	 * @test
	 */
	public function notEmptyValidatorReturnsFalseForAnEmptyString() {
		$notEmptyValidator = $this->getMock('Tx_Extbase_Validation_Validator_NotEmptyValidator', array('addError'), array(), '', FALSE);
		$this->assertFalse($notEmptyValidator->isValid(''));
	}

	/**
	 * @test
	 */
	public function notEmptyValidatorReturnsFalseForANullValue() {
		$notEmptyValidator = $this->getMock('Tx_Extbase_Validation_Validator_NotEmptyValidator', array('addError'), array(), '', FALSE);
		$this->assertFalse($notEmptyValidator->isValid(NULL));
	}

	/**
	 * @test
	 */
	public function notEmptyValidatorCreatesTheCorrectErrorForAnEmptySubject() {
		$notEmptyValidator = $this->getMock('Tx_Extbase_Validation_Validator_NotEmptyValidator', array('addError'), array(), '', FALSE);
		$notEmptyValidator->expects($this->once())->method('addError')->with('The given subject was empty.', 1221560718);
		$notEmptyValidator->isValid('');
	}

	/**
	 * @test
	 */
	public function notEmptyValidatorCreatesTheCorrectErrorForANullValue() {
		$notEmptyValidator = $this->getMock('Tx_Extbase_Validation_Validator_NotEmptyValidator', array('addError'), array(), '', FALSE);
		$notEmptyValidator->expects($this->once())->method('addError')->with('The given subject was NULL.', 1221560910);
		$notEmptyValidator->isValid(NULL);
	}
}

?>