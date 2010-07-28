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
 * Testcase for the regular expression validator
 *
 * @package Extbase
 * @subpackage extbase
 * @version $Id: RegularExpressionValidator_testcase.php 1408 2009-10-08 13:15:09Z jocrau $
 */
class Tx_Extbase_Validation_Validator_RegularExpressionValidator_testcase extends Tx_Extbase_BaseTestCase {

	/**
	 * @test
	 */
	public function regularExpressionValidatorMatchesABasicExpressionCorrectly() {
		$regularExpressionValidator = $this->getMock('Tx_Extbase_Validation_Validator_RegularExpressionValidator', array('addError'), array(), '', FALSE);
		$regularExpressionValidator->setOptions(array('regularExpression' => '/^simple[0-9]expression$/'));

		$this->assertTrue($regularExpressionValidator->isValid('simple1expression'));
		$this->assertFalse($regularExpressionValidator->isValid('simple1expressions'));
	}

	/**
	 * @test
	 */
	public function regularExpressionValidatorCreatesTheCorrectErrorIfTheExpressionDidNotMatch() {
		$regularExpressionValidator = $this->getMock('Tx_Extbase_Validation_Validator_RegularExpressionValidator', array('addError'), array(), '', FALSE);
		$regularExpressionValidator->expects($this->once())->method('addError')->with('The given subject did not match the pattern.', 1221565130);
		$regularExpressionValidator->setOptions(array('regularExpression' => '/^simple[0-9]expression$/'));
		$regularExpressionValidator->isValid('some subject that will not match');
	}
}

?>