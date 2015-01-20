<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator\BeforeExtbase14;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Test case
 *
 * This testcase checks the expected behavior for Extbase < 1.4.0, to make sure
 * we do not break backwards compatibility.
 */
class StringLengthValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function stgringLengthValidatorReturnsTrueForAStringShorterThanMaxLengthAndLongerThanMinLength() {
		$stringLengthValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringLengthValidator', array('addError'), array(), '', FALSE);
		$stringLengthValidator->expects($this->never())->method('addError');
		$stringLengthValidator->setOptions(array('minimum' => 0, 'maximum' => 50));
		$stringLengthValidator->isValid('this is a very simple string');
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorReturnsFalseForAStringShorterThanThanMinLength() {
		$stringLengthValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringLengthValidator', array('addError', 'translateErrorMessage'), array(), '', FALSE);
		$stringLengthValidator->expects($this->once())->method('addError');
		$stringLengthValidator->setOptions(array('minimum' => 50, 'maximum' => 100));
		$stringLengthValidator->isValid('this is a very short string');
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorReturnsFalseForAStringLongerThanThanMaxLength() {
		$stringLengthValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringLengthValidator', array('addError', 'translateErrorMessage'), array(), '', FALSE);
		$stringLengthValidator->expects($this->once())->method('addError');
		$stringLengthValidator->setOptions(array('minimum' => 5, 'maximum' => 10));
		$stringLengthValidator->isValid('this is a very short string');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException
	 */
	public function stringLengthValidatorReturnsTrueForAStringLongerThanThanMinLengthAndMaxLengthNotSpecified() {
		$stringLengthValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringLengthValidator', array('addError'), array(), '', FALSE);
		$stringLengthValidator->setOptions(array('minimum' => 5));
		$stringLengthValidator->isValid('this is a very short string');
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorReturnsTrueForAStringShorterThanThanMaxLengthAndMinLengthNotSpecified() {
		$stringLengthValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringLengthValidator', array('addError'), array(), '', FALSE);
		$stringLengthValidator->expects($this->never())->method('addError');
		$stringLengthValidator->setOptions(array('maximum' => 100));
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorReturnsTrueForAStringLengthEqualToMaxLengthAndMinLengthNotSpecified() {
		$stringLengthValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringLengthValidator', array('addError'), array(), '', FALSE);
		$stringLengthValidator->expects($this->never())->method('addError');
		$stringLengthValidator->setOptions(array('maximum' => 10));
		$stringLengthValidator->isValid('1234567890');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException
	 */
	public function stringLengthValidatorReturnsTrueForAStringLengthEqualToMinLengthAndMaxLengthNotSpecified() {
		$stringLengthValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringLengthValidator', array('addError'), array(), '', FALSE);
		$stringLengthValidator->setOptions(array('minimum' => 10));
		$stringLengthValidator->isValid('1234567890');
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorReturnsTrueIfMinLengthAndMaxLengthAreEqualAndTheGivenStringMatchesThisValue() {
		$stringLengthValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringLengthValidator', array('addError'), array(), '', FALSE);
		$stringLengthValidator->expects($this->never())->method('addError');
		$stringLengthValidator->setOptions(array('minimum' => 10, 'maximum' => 10));
		$stringLengthValidator->isValid('1234567890');
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorReturnsTrueIfTheStringLengthIsEqualToMaxLength() {
		$stringLengthValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringLengthValidator', array('addError'), array(), '', FALSE);
		$stringLengthValidator->expects($this->never())->method('addError');
		$stringLengthValidator->setOptions(array('minimum' => 1, 'maximum' => 10));
		$stringLengthValidator->isValid('1234567890');
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorReturnsTrueIfTheStringLengthIsEqualToMinLength() {
		$stringLengthValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringLengthValidator', array('addError'), array(), '', FALSE);
		$stringLengthValidator->expects($this->never())->method('addError');
		$stringLengthValidator->setOptions(array('minimum' => 10, 'maximum' => 100));
		$stringLengthValidator->isValid('1234567890');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException
	 */
	public function stringLengthValidatorThrowsAnExceptionIfMinLengthIsGreaterThanMaxLength() {
		$stringLengthValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringLengthValidator', array('addError'), array(), '', FALSE);
		$stringLengthValidator->setOptions(array('minimum' => 101, 'maximum' => 100));
		$stringLengthValidator->isValid('1234567890');
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorInsertsAnErrorObjectIfValidationFails() {
		$stringLengthValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringLengthValidator', array('addError', 'translateErrorMessage'), array(), '', FALSE);
		$stringLengthValidator->expects($this->once())->method('addError');
		$stringLengthValidator->setOptions(array('minimum' => 50, 'maximum' => 100));
		$stringLengthValidator->isValid('this is a very short string');
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorCanHandleAnObjectWithAToStringMethod() {
		$stringLengthValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringLengthValidator', array('addError'), array(), '', FALSE);
		$stringLengthValidator->expects($this->never())->method('addError');
		$stringLengthValidator->setOptions(array('minimum' => 5, 'maximum' => 100));
		$className = $this->getUniqueId('TestClass');
		eval('
			class ' . $className . ' {
				public function __toString() {
					return \'some string\';
				}
			}
		');
		$object = new $className();
		$stringLengthValidator->isValid($object);
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorAddsAnErrorIfTheGivenObjectCanNotBeConvertedToAString() {
		$stringLengthValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringLengthValidator', array('addError', 'translateErrorMessage'), array(), '', FALSE);
		$stringLengthValidator->expects($this->once())->method('addError');
		$stringLengthValidator->setOptions(array('minimum' => 5, 'maximum' => 100));
		$className = $this->getUniqueId('TestClass');
		eval('
			class ' . $className . ' {
				protected $someProperty;
			}
		');
		$object = new $className();
		$stringLengthValidator->isValid($object);
	}
}
