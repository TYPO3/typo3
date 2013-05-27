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
 * Testcase for the raw validator
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Extbase_Tests_Unit_Validation_Validator_RawValidatorTest extends Tx_Extbase_Tests_Unit_Validation_Validator_AbstractValidatorTestcase {

	protected $validatorClassName = 'Tx_Extbase_Validation_Validator_RawValidator';

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theRawValidatorAlwaysReturnsNoErrors() {
		$rawValidator = new Tx_Extbase_Validation_Validator_RawValidator(array());

		$this->assertFalse($rawValidator->validate('simple1expression')->hasErrors());
		$this->assertFalse($rawValidator->validate('')->hasErrors());
		$this->assertFalse($rawValidator->validate(NULL)->hasErrors());
		$this->assertFalse($rawValidator->validate(FALSE)->hasErrors());
		$this->assertFalse($rawValidator->validate(new ArrayObject())->hasErrors());
	}
}

?>