<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator\BeforeExtbase14;

/***************************************************************
 *  Copyright notice
 *
 *  This class is a backport of the corresponding class of TYPO3 Flow.
 *  All credits go to the TYPO3 Flow team.
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
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
 * This testcase checks the expected behavior for Extbase < 1.4.0, to make sure
 * we do not break backwards compatibility.
 */
class StringLengthValidatorTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @test
	 */
	public function stgringLengthValidatorReturnsTrueForAStringShorterThanMaxLengthAndLongerThanMinLength() {
		$stringLengthValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringLengthValidator', array('addError'), array(), '', FALSE);
		$stringLengthValidator->setOptions(array('minimum' => 0, 'maximum' => 50));
		$this->assertTrue($stringLengthValidator->isValid('this is a very simple string'));
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorReturnsFalseForAStringShorterThanThanMinLength() {
		$stringLengthValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringLengthValidator', array('addError'), array(), '', FALSE);
		$stringLengthValidator->setOptions(array('minimum' => 50, 'maximum' => 100));
		$this->assertFalse($stringLengthValidator->isValid('this is a very short string'));
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorReturnsFalseForAStringLongerThanThanMaxLength() {
		$stringLengthValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringLengthValidator', array('addError'), array(), '', FALSE);
		$stringLengthValidator->setOptions(array('minimum' => 5, 'maximum' => 10));
		$this->assertFalse($stringLengthValidator->isValid('this is a very short string'));
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorReturnsTrueForAStringLongerThanThanMinLengthAndMaxLengthNotSpecified() {
		$stringLengthValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringLengthValidator', array('addError'), array(), '', FALSE);
		$stringLengthValidator->setOptions(array('minimum' => 5));
		$this->assertTrue($stringLengthValidator->isValid('this is a very short string'));
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorReturnsTrueForAStringShorterThanThanMaxLengthAndMinLengthNotSpecified() {
		$stringLengthValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringLengthValidator', array('addError'), array(), '', FALSE);
		$stringLengthValidator->setOptions(array('maximum' => 100));
		$this->assertTrue($stringLengthValidator->isValid('this is a very short string'));
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorReturnsTrueForAStringLengthEqualToMaxLengthAndMinLengthNotSpecified() {
		$stringLengthValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringLengthValidator', array('addError'), array(), '', FALSE);
		$stringLengthValidator->setOptions(array('maximum' => 10));
		$this->assertTrue($stringLengthValidator->isValid('1234567890'));
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorReturnsTrueForAStringLengthEqualToMinLengthAndMaxLengthNotSpecified() {
		$stringLengthValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringLengthValidator', array('addError'), array(), '', FALSE);
		$stringLengthValidator->setOptions(array('minimum' => 10));
		$this->assertTrue($stringLengthValidator->isValid('1234567890'));
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorReturnsTrueIfMinLengthAndMaxLengthAreEqualAndTheGivenStringMatchesThisValue() {
		$stringLengthValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringLengthValidator', array('addError'), array(), '', FALSE);
		$stringLengthValidator->setOptions(array('minimum' => 10, 'maximum' => 10));
		$this->assertTrue($stringLengthValidator->isValid('1234567890'));
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorReturnsTrueIfTheStringLengthIsEqualToMaxLength() {
		$stringLengthValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringLengthValidator', array('addError'), array(), '', FALSE);
		$stringLengthValidator->setOptions(array('minimum' => 1, 'maximum' => 10));
		$this->assertTrue($stringLengthValidator->isValid('1234567890'));
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorReturnsTrueIfTheStringLengthIsEqualToMinLength() {
		$stringLengthValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringLengthValidator', array('addError'), array(), '', FALSE);
		$stringLengthValidator->setOptions(array('minimum' => 10, 'maximum' => 100));
		$this->assertTrue($stringLengthValidator->isValid('1234567890'));
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
		$stringLengthValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringLengthValidator', array('addError'), array(), '', FALSE);
		$stringLengthValidator->expects($this->once())->method('addError');
		$stringLengthValidator->setOptions(array('minimum' => 50, 'maximum' => 100));
		$stringLengthValidator->isValid('this is a very short string');
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorCanHandleAnObjectWithAToStringMethod() {
		$stringLengthValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringLengthValidator', array('addError'), array(), '', FALSE);
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
	 * @expectedException \TYPO3\CMS\Extbase\Validation\Exception\InvalidSubjectException
	 */
	public function stringLengthValidatorThrowsAnExceptionIfTheGivenObjectCanNotBeConvertedToAString() {
		$stringLengthValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringLengthValidator', array('addError'), array(), '', FALSE);
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