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
 * Testcase for the Conjunction Validator
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Extbase_Tests_Unit_Validation_Validator_ConjunctionValidatorTest extends Tx_Extbase_Tests_Unit_BaseTestCase {
	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addingValidatorsToAJunctionValidatorWorks() {
		$proxyClassName = $this->buildAccessibleProxy('Tx_Extbase_Validation_Validator_ConjunctionValidator');
		$conjunctionValidator = new $proxyClassName(array());

		$mockValidator = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface');
		$conjunctionValidator->addValidator($mockValidator);
		$this->assertTrue($conjunctionValidator->_get('validators')->contains($mockValidator));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function allValidatorsInTheConjunctionAreCalledEvenIfOneReturnsError() {
		$validatorConjunction = new Tx_Extbase_Validation_Validator_ConjunctionValidator(array());
		$validatorObject = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface', array('validate'));
		$validatorObject->expects($this->once())->method('validate')->will($this->returnValue(new Tx_Extbase_Error_Result()));

		$errors = new Tx_Extbase_Error_Result();
		$errors->addError(new Tx_Extbase_Error_Error('Error', 123));
		$secondValidatorObject = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface', array('validate'));
		$secondValidatorObject->expects($this->once())->method('validate')->will($this->returnValue($errors));

		$thirdValidatorObject = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface', array('validate'));
		$thirdValidatorObject->expects($this->once())->method('validate')->will($this->returnValue(new Tx_Extbase_Error_Result()));

		$validatorConjunction->addValidator($validatorObject);
		$validatorConjunction->addValidator($secondValidatorObject);
		$validatorConjunction->addValidator($thirdValidatorObject);

		$validatorConjunction->validate('some subject');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function validatorConjunctionReturnsNoErrorsIfAllJunctionedValidatorsReturnNoErrors() {
		$validatorConjunction = new Tx_Extbase_Validation_Validator_ConjunctionValidator(array());
		$validatorObject = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface', array('validate'));
		$validatorObject->expects($this->any())->method('validate')->will($this->returnValue(new Tx_Extbase_Error_Result()));

		$secondValidatorObject = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface', array('validate'));
		$secondValidatorObject->expects($this->any())->method('validate')->will($this->returnValue(new Tx_Extbase_Error_Result()));

		$validatorConjunction->addValidator($validatorObject);
		$validatorConjunction->addValidator($secondValidatorObject);

		$this->assertFalse($validatorConjunction->validate('some subject')->hasErrors());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function validatorConjunctionReturnsErrorsIfOneValidatorReturnsErrors() {
		$validatorConjunction = new Tx_Extbase_Validation_Validator_ConjunctionValidator(array());
		$validatorObject = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface', array('validate'));

		$errors = new Tx_Extbase_Error_Result();
		$errors->addError(new Tx_Extbase_Error_Error('Error', 123));

		$validatorObject->expects($this->any())->method('validate')->will($this->returnValue($errors));

		$validatorConjunction->addValidator($validatorObject);

		$this->assertTrue($validatorConjunction->validate('some subject')->hasErrors());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function removingAValidatorOfTheValidatorConjunctionWorks() {
		$validatorConjunction = $this->getAccessibleMock('Tx_Extbase_Validation_Validator_ConjunctionValidator', array('dummy'), array(array()), '', TRUE);

		$validator1 = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface', array('validate'));
		$validator2 = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface', array('validate'));

		$validatorConjunction->addValidator($validator1);
		$validatorConjunction->addValidator($validator2);

		$validatorConjunction->removeValidator($validator1);

		$this->assertFalse($validatorConjunction->_get('validators')->contains($validator1));
		$this->assertTrue($validatorConjunction->_get('validators')->contains($validator2));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException Tx_Extbase_Validation_Exception_NoSuchValidator
	 */
	public function removingANotExistingValidatorIndexThrowsException() {
		$validatorConjunction = new Tx_Extbase_Validation_Validator_ConjunctionValidator(array());
		$validator = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface', array('validate'));
		$validatorConjunction->removeValidator($validator);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function countReturnesTheNumberOfValidatorsContainedInTheConjunction() {
		$validatorConjunction = new Tx_Extbase_Validation_Validator_ConjunctionValidator(array());

		$validator1 = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface', array('validate'));
		$validator2 = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface', array('validate'));

		$this->assertSame(0, count($validatorConjunction));

		$validatorConjunction->addValidator($validator1);
		$validatorConjunction->addValidator($validator2);

		$this->assertSame(2, count($validatorConjunction));
	}
}

?>