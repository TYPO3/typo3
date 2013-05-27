<?php

/*                                                                        *
 * This script belongs to the Extbase framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the number validator
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Extbase_Tests_Unit_Validation_Validator_NumberValidatorTest extends Tx_Extbase_Tests_Unit_Validation_Validator_AbstractValidatorTestcase {

	protected $validatorClassName = 'Tx_Extbase_Validation_Validator_NumberValidator';

	/**
	 * @test
	 */
	public function numberValidatorReturnsTrueForASimpleInteger() {
		$numberValidator = new Tx_Extbase_Validation_Validator_NumberValidator();
		$this->assertFalse($numberValidator->validate(1029437)->hasErrors());
	}

	/**
	 * @test
	 */
	public function numberValidatorReturnsFalseForAString() {
		$expectedResult = new Tx_Extbase_Error_Result();
		$expectedResult->addError(new Tx_Extbase_Validation_Error('The given subject was not a valid number.', 1221563685));
		$numberValidator = new Tx_Extbase_Validation_Validator_NumberValidator();
		$this->assertEquals($expectedResult, $numberValidator->validate('not a number'));
	}
}

?>