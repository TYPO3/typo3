<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator;

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
require_once __DIR__ . '/AbstractValidatorTestcase.php';

/**
 * Testcase for the string length validator
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class StringLengthValidatorTest extends \TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator\AbstractValidatorTestcase {

	protected $validatorClassName = 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringLengthValidator';

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorReturnsNoErrorForAStringShorterThanMaxLengthAndLongerThanMinLength() {
		$this->validatorOptions(array('minimum' => 0, 'maximum' => 50));
		$this->assertFalse($this->validator->validate('this is a very simple string')->hasErrors());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorReturnsErrorForAStringShorterThanThanMinLength() {
		$this->validatorOptions(array('minimum' => 50, 'maximum' => 100));
		$this->assertTrue($this->validator->validate('this is a very short string')->hasErrors());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorReturnsErrorsForAStringLongerThanThanMaxLength() {
		$this->validatorOptions(array('minimum' => 5, 'maximum' => 10));
		$this->assertTrue($this->validator->validate('this is a very short string')->hasErrors());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorReturnsNoErrorsForAStringLongerThanThanMinLengthAndMaxLengthNotSpecified() {
		$this->validatorOptions(array('minimum' => 5));
		$this->assertFalse($this->validator->validate('this is a very short string')->hasErrors());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorReturnsNoErrorsForAStringShorterThanThanMaxLengthAndMinLengthNotSpecified() {
		$this->validatorOptions(array('maximum' => 100));
		$this->assertFalse($this->validator->validate('this is a very short string')->hasErrors());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorReturnsNoErrorsForAStringLengthEqualToMaxLengthAndMinLengthNotSpecified() {
		$this->validatorOptions(array('maximum' => 10));
		$this->assertFalse($this->validator->validate('1234567890')->hasErrors());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorReturnsNoErrorForAStringLengthEqualToMinLengthAndMaxLengthNotSpecified() {
		$this->validatorOptions(array('minimum' => 10));
		$this->assertFalse($this->validator->validate('1234567890')->hasErrors());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorReturnsNoErrorIfMinLengthAndMaxLengthAreEqualAndTheGivenStringMatchesThisValue() {
		$this->validatorOptions(array('minimum' => 10, 'maximum' => 10));
		$this->assertFalse($this->validator->validate('1234567890')->hasErrors());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorReturnsNoErrorsfTheStringLengthIsEqualToMaxLength() {
		$this->validatorOptions(array('minimum' => 1, 'maximum' => 10));
		$this->assertFalse($this->validator->validate('1234567890')->hasErrors());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorReturnsNoErrorIfTheStringLengthIsEqualToMinLength() {
		$this->validatorOptions(array('minimum' => 10, 'maximum' => 100));
		$this->assertFalse($this->validator->validate('1234567890')->hasErrors());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorThrowsAnExceptionIfMinLengthIsGreaterThanMaxLength() {
		$this->validator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringLengthValidator', array('addError'), array(), '', FALSE);
		$this->validatorOptions(array('minimum' => 101, 'maximum' => 100));
		$this->validator->validate('1234567890');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorInsertsAnErrorObjectIfValidationFails() {
		$this->validatorOptions(array('minimum' => 50, 'maximum' => 100));
		$this->assertEquals(1, count($this->validator->validate('this is a very short string')->getErrors()));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorCanHandleAnObjectWithAToStringMethod() {
		$this->validator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringLengthValidator', array('addError'), array(), '', FALSE);
		$this->validatorOptions(array('minimum' => 5, 'maximum' => 100));
		$className = 'TestClass' . md5(uniqid(mt_rand(), TRUE));
		eval('
			class ' . $className . ' {
				public function __toString() {
					return \'some string\';
				}
			}
		');
		$object = new $className();
		$this->assertFalse($this->validator->validate($object)->hasErrors());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Validation\Exception\InvalidSubjectException
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function stringLengthValidatorThrowsAnExceptionIfTheGivenObjectCanNotBeConvertedToAString() {
		$this->validator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringLengthValidator', array('addError'), array(), '', FALSE);
		$this->validatorOptions(array('minimum' => 5, 'maximum' => 100));
		$className = 'TestClass' . md5(uniqid(mt_rand(), TRUE));
		eval('
			class ' . $className . ' {
				protected $someProperty;
			}
		');
		$object = new $className();
		$this->validator->validate($object);
	}
}

?>