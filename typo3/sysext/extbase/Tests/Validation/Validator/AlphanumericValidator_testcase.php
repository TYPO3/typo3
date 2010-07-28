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
 * Testcase for the alphanumeric validator
 *
 * @package Extbase
 * @subpackage extbase
 * @version $Id: AlphanumericValidator_testcase.php 1408 2009-10-08 13:15:09Z jocrau $
 */
class Tx_Extbase_Validation_Validator_AlphanumericValidator_testcase extends Tx_Extbase_BaseTestCase {

	/**
	 * @test
	 */
	public function alphanumericValidatorReturnsTrueForAnAlphanumericString() {
		$alphanumericValidator = new Tx_Extbase_Validation_Validator_AlphanumericValidator();
		$this->assertTrue($alphanumericValidator->isValid('12ssDF34daweidf'));
	}

	/**
	 * @test
	 */
	public function alphanumericValidatorReturnsFalseForAStringWithSpecialCharacters() {
	$alphanumericValidator = $this->getMock('Tx_Extbase_Validation_Validator_AlphanumericValidator', array('addError'), array(), '', FALSE);
	$this->assertFalse($alphanumericValidator->isValid('adsf%&/$jklsfdö'));
	}

	/**
	 * @test
	 */
	public function alphanumericValidatorCreatesTheCorrectErrorForAnInvalidSubject() {
		$alphanumericValidator = $this->getMock('Tx_Extbase_Validation_Validator_AlphanumericValidator', array('addError'), array(), '', FALSE);
		$alphanumericValidator->expects($this->once())->method('addError')->with('The given subject was not a valid alphanumeric string.', 1221551320);
		$alphanumericValidator->isValid('adsf%&/$jklsfdö');
	}
}

?>