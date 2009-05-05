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
 * Testcase for the string length validator
 *
 * @package TYPO3
 * @subpackage extbase
 * @version $Id: $
 */
class Tx_Extbase_Validation_Validator_StringLengthValidator_testcase extends Tx_Extbase_Base_testcase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stgringLengthValidatorReturnsTrueForAStringShorterThanMaxLengthAndLongerThanMinLength() {
		$stringLengthValidator = $this->getMock('Tx_Extbase_Validation_Validator_StringLengthValidator', array('addError'), array(), '', FALSE);
		$stringLengthValidator->setOptions(array('minimum' => 0, 'maximum' => 50));
		$this->assertTrue($stringLengthValidator->isValid('this is a very simple string'));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorReturnsFalseForAStringShorterThanThanMinLength() {
		$stringLengthValidator = $this->getMock('Tx_Extbase_Validation_Validator_StringLengthValidator', array('addError'), array(), '', FALSE);
		$stringLengthValidator->setOptions(array('minimum' => 50, 'maximum' => 100));
		$this->assertFalse($stringLengthValidator->isValid('this is a very short string'));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorReturnsFalseForAStringLongerThanThanMaxLength() {
		$stringLengthValidator = $this->getMock('Tx_Extbase_Validation_Validator_StringLengthValidator', array('addError'), array(), '', FALSE);
		$stringLengthValidator->setOptions(array('minimum' => 5, 'maximum' => 10));
		$this->assertFalse($stringLengthValidator->isValid('this is a very short string'));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorReturnsTrueForAStringLongerThanThanMinLengthAndMaxLengthNotSpecified() {
		$stringLengthValidator = $this->getMock('Tx_Extbase_Validation_Validator_StringLengthValidator', array('addError'), array(), '', FALSE);
		$stringLengthValidator->setOptions(array('minimum' => 5));
		$this->assertTrue($stringLengthValidator->isValid('this is a very short string'));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorReturnsTrueForAStringShorterThanThanMaxLengthAndMinLengthNotSpecified() {
		$stringLengthValidator = $this->getMock('Tx_Extbase_Validation_Validator_StringLengthValidator', array('addError'), array(), '', FALSE);
		$stringLengthValidator->setOptions(array('maximum' => 100));
		$this->assertTrue($stringLengthValidator->isValid('this is a very short string'));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorReturnsTrueForAStringLengthEqualToMaxLengthAndMinLengthNotSpecified() {
		$stringLengthValidator = $this->getMock('Tx_Extbase_Validation_Validator_StringLengthValidator', array('addError'), array(), '', FALSE);
		$stringLengthValidator->setOptions(array('maximum' => 10));
		$this->assertTrue($stringLengthValidator->isValid('1234567890'));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorReturnsTrueForAStringLengthEqualToMinLengthAndMaxLengthNotSpecified() {
		$stringLengthValidator = $this->getMock('Tx_Extbase_Validation_Validator_StringLengthValidator', array('addError'), array(), '', FALSE);
		$stringLengthValidator->setOptions(array('minimum' => 10));
		$this->assertTrue($stringLengthValidator->isValid('1234567890'));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorReturnsTrueIfMinLengthAndMaxLengthAreEqualAndTheGivenStringMatchesThisValue() {
		$stringLengthValidator = $this->getMock('Tx_Extbase_Validation_Validator_StringLengthValidator', array('addError'), array(), '', FALSE);
		$stringLengthValidator->setOptions(array('minimum' => 10, 'maximum' => 10));
		$this->assertTrue($stringLengthValidator->isValid('1234567890'));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorReturnsTrueIfTheStringLengthIsEqualToMaxLength() {
		$stringLengthValidator = $this->getMock('Tx_Extbase_Validation_Validator_StringLengthValidator', array('addError'), array(), '', FALSE);
		$stringLengthValidator->setOptions(array('minimum' => 1, 'maximum' => 10));
		$this->assertTrue($stringLengthValidator->isValid('1234567890'));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorReturnsTrueIfTheStringLengthIsEqualToMinLength() {
		$stringLengthValidator = $this->getMock('Tx_Extbase_Validation_Validator_StringLengthValidator', array('addError'), array(), '', FALSE);
		$stringLengthValidator->setOptions(array('minimum' => 10, 'maximum' => 100));
		$this->assertTrue($stringLengthValidator->isValid('1234567890'));
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_Validation_Exception_InvalidValidationOptions
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorThrowsAnExceptionIfMinLengthIsGreaterThanMaxLength() {
		$stringLengthValidator = $this->getMock('Tx_Extbase_Validation_Validator_StringLengthValidator', array('addError'), array(), '', FALSE);
		$stringLengthValidator->setOptions(array('minimum' => 101, 'maximum' => 100));
		$stringLengthValidator->isValid('1234567890');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorInsertsAnErrorObjectIfValidationFails() {
		$stringLengthValidator = $this->getMock('Tx_Extbase_Validation_Validator_StringLengthValidator', array('addError'), array(), '', FALSE);
		$stringLengthValidator->expects($this->once())->method('addError');
		$stringLengthValidator->setOptions(array('minimum' => 50, 'maximum' => 100));

		$stringLengthValidator->isValid('this is a very short string');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorCanHandleAnObjectWithAToStringMethod() {
		$stringLengthValidator = $this->getMock('Tx_Extbase_Validation_Validator_StringLengthValidator', array('addError'), array(), '', FALSE);
		$stringLengthValidator->setOptions(array('minimum' => 5, 'maximum' => 100));

		$className = uniqid('TestClass');

		eval('
			class ' . $className . ' {
				public function __toString() {
					return \'some string\';
				}
			}
		');

		$object = new $className();
		$this->assertTrue($stringLengthValidator->isValid($object));
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_Validation_Exception_InvalidSubject
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorThrowsAnExceptionIfTheGivenObjectCanNotBeConvertedToAString() {
		$stringLengthValidator = $this->getMock('Tx_Extbase_Validation_Validator_StringLengthValidator', array('addError'), array(), '', FALSE);
		$stringLengthValidator->setOptions(array('minimum' => 5, 'maximum' => 100));

		$className = uniqid('TestClass');

		eval('
			class ' . $className . ' {
				protected $someProperty;
			}
		');

		$object = new $className();
		$stringLengthValidator->isValid($object);
	}
}

?>