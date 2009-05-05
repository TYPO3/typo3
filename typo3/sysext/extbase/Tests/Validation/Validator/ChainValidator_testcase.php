<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
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
 * Testcase for ValidatorChains
 *
 * @package TYPO3
 * @subpackage extbase
 * @version $Id: $
 */
class Tx_Extbase_Validation_Validator_ChainValidator_testcase extends Tx_Extbase_Base_testcase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addingValidatorsToAValidatorChainWorks() {
		$proxyClassName = $this->buildAccessibleProxy('Tx_Extbase_Validation_Validator_ChainValidator');
		$validatorChain = new $proxyClassName;

		$mockValidator = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface');
		$validatorChain->addValidator($mockValidator);
		$this->assertTrue($validatorChain->_get('validators')->contains($mockValidator));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function allValidatorsInTheChainAreCalledIfEachOfThemReturnsTrue() {
		$validatorChain = new Tx_Extbase_Validation_Validator_ChainValidator();
		$validatorObject = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface');
		$validatorObject->expects($this->once())->method('isValid')->will($this->returnValue(TRUE));

		$secondValidatorObject = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface');
		$secondValidatorObject->expects($this->once())->method('isValid')->will($this->returnValue(TRUE));

		$validatorChain->addValidator($validatorObject);
		$validatorChain->addValidator($secondValidatorObject);

		$validatorChain->isValid('some subject');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function validatorChainReturnsTrueIfAllChainedValidatorsReturnTrue() {
		$validatorChain = new Tx_Extbase_Validation_Validator_ChainValidator();
		$validatorObject = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface');
		$validatorObject->expects($this->any())->method('isValid')->will($this->returnValue(TRUE));

		$secondValidatorObject = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface');
		$secondValidatorObject->expects($this->any())->method('isValid')->will($this->returnValue(TRUE));

		$validatorChain->addValidator($validatorObject);
		$validatorChain->addValidator($secondValidatorObject);

		$this->assertTrue($validatorChain->isValid('some subject'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function validatorChainImmediatelyReturnsFalseIfOneValidatorsReturnFalse() {
		$validatorChain = new Tx_Extbase_Validation_Validator_ChainValidator();
		$validatorObject = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface');
		$validatorObject->expects($this->any())->method('isValid')->will($this->returnValue(FALSE));

		$secondValidatorObject = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface');
		$secondValidatorObject->expects($this->never())->method('isValid');

		$validatorChain->addValidator($validatorObject);
		$validatorChain->addValidator($secondValidatorObject);

		$this->assertFalse($validatorChain->isValid('some subject'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function removingAValidatorOfTheValidatorChainWorks() {
		$validatorChain = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Validation_Validator_ChainValidator'), array('dummy'), array(), '', TRUE);

		$validator1 = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface');
		$validator2 = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface');

		$validatorChain->addValidator($validator1);
		$validatorChain->addValidator($validator2);

		$validatorChain->removeValidator($validator1);

		$this->assertFalse($validatorChain->_get('validators')->contains($validator1));
		$this->assertTrue($validatorChain->_get('validators')->contains($validator2));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException Tx_Extbase_Validation_Exception_NoSuchValidator
	 */
	public function removingANotExistingValidatorIndexThrowsException() {
		$validatorChain = new Tx_Extbase_Validation_Validator_ChainValidator;
		$validator = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface');
		$validatorChain->removeValidator($validator);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function countReturnesTheNumberOfValidatorsContainedInThechain() {
		$validatorChain = new Tx_Extbase_Validation_Validator_ChainValidator;

		$validator1 = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface');
		$validator2 = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface');

		$this->assertSame(0, count($validatorChain));

		$validatorChain->addValidator($validator1);
		$validatorChain->addValidator($validator2);

		$this->assertSame(2, count($validatorChain));
	}
}

?>