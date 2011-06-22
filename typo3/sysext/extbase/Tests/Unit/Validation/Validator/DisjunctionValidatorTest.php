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
 * Testcase for the Disjunction Validator
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Extbase_Tests_Unit_Validation_Validator_DisjunctionValidatorTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function allValidatorsInTheDisjunctionAreCalledEvenIfOneReturnsNoError() {
		$validatorDisjunction = new Tx_Extbase_Validation_Validator_DisjunctionValidator(array());
		$validatorObject = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface', array('validate'));
		$validatorObject->expects($this->once())->method('validate')->will($this->returnValue(new Tx_Extbase_Error_Result()));

		$errors = new Tx_Extbase_Error_Result();
		$errors->addError(new Tx_Extbase_Error_Error('Error', 123));

		$secondValidatorObject = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface', array('validate'));
		$secondValidatorObject->expects($this->exactly(1))->method('validate')->will($this->returnValue($errors));

		$validatorDisjunction->addValidator($validatorObject);
		$validatorDisjunction->addValidator($secondValidatorObject);

		$validatorDisjunction->validate('some subject');
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function validateReturnsNoErrorsIfOneValidatorReturnsNoError() {
		$validatorDisjunction = new Tx_Extbase_Validation_Validator_DisjunctionValidator(array());
		$validatorObject = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface', array('validate'));
		$validatorObject->expects($this->any())->method('validate')->will($this->returnValue(new Tx_Extbase_Error_Result()));

		$errors = new Tx_Extbase_Error_Result();
		$errors->addError(new Tx_Extbase_Error_Error('Error', 123));

		$secondValidatorObject = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface', array('validate'));
		$secondValidatorObject->expects($this->any())->method('validate')->will($this->returnValue($errors));

		$validatorDisjunction->addValidator($validatorObject);
		$validatorDisjunction->addValidator($secondValidatorObject);

		$this->assertFalse($validatorDisjunction->validate('some subject')->hasErrors());
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function validateReturnsAllErrorsIfAllValidatorsReturnErrrors() {
		$validatorDisjunction = new Tx_Extbase_Validation_Validator_DisjunctionValidator(array());

		$error1 = new Tx_Extbase_Error_Error('Error', 123);
		$error2 = new Tx_Extbase_Error_Error('Error2', 123);

		$errors1 = new Tx_Extbase_Error_Result();
		$errors1->addError($error1);
		$validatorObject = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface', array('validate'));
		$validatorObject->expects($this->any())->method('validate')->will($this->returnValue($errors1));

		$errors2 = new Tx_Extbase_Error_Result();
		$errors2->addError($error2);
		$secondValidatorObject = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface', array('validate'));
		$secondValidatorObject->expects($this->any())->method('validate')->will($this->returnValue($errors2));

		$validatorDisjunction->addValidator($validatorObject);
		$validatorDisjunction->addValidator($secondValidatorObject);

		$this->assertEquals(array($error1, $error2), $validatorDisjunction->validate('some subject')->getErrors());
	}
}

?>