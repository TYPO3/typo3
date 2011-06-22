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

/**
 * Testcase for the Abstract Validator
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
abstract class Tx_Extbase_Tests_Unit_Validation_Validator_AbstractValidatorTestcase extends Tx_Extbase_Tests_Unit_BaseTestCase {

	protected $validatorClassName;

	/**
	 *
	 * @var Tx_Extbase_Validation_Validator_ValidatorInterface
	 */
	protected $validator;

	public function setUp() {
		$this->validator = $this->getValidator();
	}

	protected function getValidator($options = array()) {
		$validator = new $this->validatorClassName($options);

		return $validator;
	}

	protected function validatorOptions($options) {
		$this->validator = $this->getValidator($options);
	}
}

?>