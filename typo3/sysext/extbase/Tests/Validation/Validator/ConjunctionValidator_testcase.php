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
 * Testcase for the Conjunction Validators
 *
 * @package Extbase
 * @subpackage extbase
 * @version $Id: ConjunctionValidator_testcase.php 1729 2009-11-25 21:37:20Z stucki $
 */
class Tx_Extbase_Validation_Validator_ConjunctionValidator_testcase extends Tx_Extbase_BaseTestCase {

	/**
	 * @test
	 */
	public function addingValidatorsToAJunctionValidatorWorks() {
		$proxyClassName = $this->buildAccessibleProxy('Tx_Extbase_Validation_Validator_ConjunctionValidator');
		$conjunctionValidator = new $proxyClassName;

		$mockValidator = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface');
		$conjunctionValidator->addValidator($mockValidator);
		$this->assertTrue($conjunctionValidator->_get('validators')->contains($mockValidator));
	}

	/**
	 * @test
	 */
	public function allValidatorsInTheConjunctionAreCalledEvenIfOneReturnsFalse() {
		$validatorConjunction = new Tx_Extbase_Validation_Validator_ConjunctionValidator();
		$validatorObject = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface');
		$validatorObject->expects($this->once())->method('isValid')->will($this->returnValue(TRUE));

		$secondValidatorObject = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface');
		$secondValidatorObject->expects($this->once())->method('isValid')->will($this->returnValue(FALSE));
		$secondValidatorObject->expects($this->any())->method('getErrors')->will($this->returnValue(array()));

		$thirdValidatorObject = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface');
		$thirdValidatorObject->expects($this->once())->method('isValid')->will($this->returnValue(TRUE));
		
		$validatorConjunction->addValidator($validatorObject);
		$validatorConjunction->addValidator($secondValidatorObject);
		$validatorConjunction->addValidator($thirdValidatorObject);

		$validatorConjunction->isValid('some subject');
	}

	/**
	 * @test
	 */
	public function validatorConjunctionReturnsTrueIfAllJunctionedValidatorsReturnTrue() {
		$validatorConjunction = new Tx_Extbase_Validation_Validator_ConjunctionValidator();
		$validatorObject = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface');
		$validatorObject->expects($this->any())->method('isValid')->will($this->returnValue(TRUE));

		$secondValidatorObject = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface');
		$secondValidatorObject->expects($this->any())->method('isValid')->will($this->returnValue(TRUE));

		$validatorConjunction->addValidator($validatorObject);
		$validatorConjunction->addValidator($secondValidatorObject);

		$this->assertTrue($validatorConjunction->isValid('some subject'));
	}

	/**
	 * @test
	 */
	public function validatorConjunctionReturnsFalseIfOneValidatorReturnsFalse() {
		$validatorConjunction = new Tx_Extbase_Validation_Validator_ConjunctionValidator();
		$validatorObject = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface');
		$validatorObject->expects($this->any())->method('isValid')->will($this->returnValue(FALSE));
		$validatorObject->expects($this->any())->method('getErrors')->will($this->returnValue(array()));

		$validatorConjunction->addValidator($validatorObject);

		$this->assertFalse($validatorConjunction->isValid('some subject'));
	}

	/**
	 * @test
	 */
	public function removingAValidatorOfTheValidatorConjunctionWorks() {
		$validatorConjunction = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Validation_Validator_ConjunctionValidator'), array('dummy'), array(), '', TRUE);

		$validator1 = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface');
		$validator2 = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface');

		$validatorConjunction->addValidator($validator1);
		$validatorConjunction->addValidator($validator2);

		$validatorConjunction->removeValidator($validator1);

		$this->assertFalse($validatorConjunction->_get('validators')->contains($validator1));
		$this->assertTrue($validatorConjunction->_get('validators')->contains($validator2));
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_Validation_Exception_NoSuchValidator
	 */
	public function removingANotExistingValidatorIndexThrowsException() {
		$validatorConjunction = new Tx_Extbase_Validation_Validator_ConjunctionValidator();
		$validator = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface');
		$validatorConjunction->removeValidator($validator);
	}

	/**
	 * @test
	 */
	public function countReturnesTheNumberOfValidatorsContainedInTheConjunction() {
		$validatorConjunction = new Tx_Extbase_Validation_Validator_ConjunctionValidator();

		$validator1 = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface');
		$validator2 = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface');

		$this->assertSame(0, count($validatorConjunction));

		$validatorConjunction->addValidator($validator1);
		$validatorConjunction->addValidator($validator2);

		$this->assertSame(2, count($validatorConjunction));
	}
}

?>