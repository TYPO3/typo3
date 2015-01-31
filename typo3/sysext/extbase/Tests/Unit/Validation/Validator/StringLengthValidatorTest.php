<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator;

/*
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
 */
class StringLengthValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	protected $validatorClassName = 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringLengthValidator';

	public function setup() {
		$this->validator = $this->getMock($this->validatorClassName, array('translateErrorMessage'));
	}

	/**
	 * @var \TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator
	 */
	protected $validator;

	/**
	 * @test
	 */
	public function validateReturnsNoErrorIfTheGivenValueIsNull() {
		$this->assertFalse($this->validator->validate(NULL)->hasErrors());
	}

	/**
	 * @test
	 */
	public function validateReturnsNoErrorIfTheGivenValueIsAnEmptyString() {
		$this->assertFalse($this->validator->validate('')->hasErrors());
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorReturnsNoErrorForAStringShorterThanMaxLengthAndLongerThanMinLength() {
		$options = array('minimum' => 0, 'maximum' => 50);
		$validator = $this->getMock($this->validatorClassName, array('dummy'), array($options));
		$this->assertFalse($validator->validate('this is a very simple string')->hasErrors());
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorReturnsErrorForAStringShorterThanThanMinLength() {
		$options = array('minimum' => 50, 'maximum' => 100);
		$validator = $this->getMock($this->validatorClassName, array('translateErrorMessage'), array($options));
		$this->assertTrue($validator->validate('this is a very short string')->hasErrors());
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorReturnsErrorsForAStringLongerThanThanMaxLength() {
		$options = array('minimum' => 5, 'maximum' => 10);
		$validator = $this->getMock($this->validatorClassName, array('translateErrorMessage'), array($options));
		$this->assertTrue($validator->validate('this is a very short string')->hasErrors());
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorReturnsNoErrorsForAStringLongerThanThanMinLengthAndMaxLengthNotSpecified() {
		$options = array('minimum' => 5);
		$validator = $this->getMock($this->validatorClassName, array('dummy'), array($options));
		$this->assertFalse($validator->validate('this is a very short string')->hasErrors());
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorReturnsNoErrorsForAStringShorterThanThanMaxLengthAndMinLengthNotSpecified() {
		$options = array('maximum' => 100);
		$validator = $this->getMock($this->validatorClassName, array('dummy'), array($options));
		$this->assertFalse($validator->validate('this is a very short string')->hasErrors());
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorReturnsNoErrorsForAStringLengthEqualToMaxLengthAndMinLengthNotSpecified() {
		$options = array('maximum' => 10);
		$validator = $this->getMock($this->validatorClassName, array('dummy'), array($options));
		$this->assertFalse($validator->validate('1234567890')->hasErrors());
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorReturnsNoErrorForAStringLengthEqualToMinLengthAndMaxLengthNotSpecified() {
		$options = array('minimum' => 10);
		$validator = $this->getMock($this->validatorClassName, array('dummy'), array($options));
		$this->assertFalse($validator->validate('1234567890')->hasErrors());
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorReturnsNoErrorIfMinLengthAndMaxLengthAreEqualAndTheGivenStringMatchesThisValue() {
		$options = array('minimum' => 10, 'maximum' => 10);
		$validator = $this->getMock($this->validatorClassName, array('dummy'), array($options));
		$this->assertFalse($validator->validate('1234567890')->hasErrors());
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorReturnsNoErrorsfTheStringLengthIsEqualToMaxLength() {
		$options = array('minimum' => 1, 'maximum' => 10);
		$validator = $this->getMock($this->validatorClassName, array('dummy'), array($options));
		$this->assertFalse($validator->validate('1234567890')->hasErrors());
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorReturnsNoErrorIfTheStringLengthIsEqualToMinLength() {
		$options = array('minimum' => 10, 'maximum' => 100);
		$validator = $this->getMock($this->validatorClassName, array('dummy'), array($options));
		$this->assertFalse($validator->validate('1234567890')->hasErrors());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException
	 */
	public function stringLengthValidatorThrowsAnExceptionIfMinLengthIsGreaterThanMaxLength() {
		$options = array('minimum' => 101, 'maximum' => 100);
		$validator = $this->getMock($this->validatorClassName, array('addError', 'translateErrorMessage'), array($options));
		$validator->validate('1234567890');
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorInsertsAnErrorObjectIfValidationFails() {
		$options = array('minimum' => 50, 'maximum' => 100);
		$validator = $this->getMock($this->validatorClassName, array('translateErrorMessage'), array($options));
		$this->assertEquals(1, count($validator->validate('this is a very short string')->getErrors()));
	}

	/**
	 * @test
	 */
	public function stringLengthValidatorCanHandleAnObjectWithAToStringMethod() {
		$options = array('minimum' => 5, 'maximum' => 100);
		$validator = $this->getMock($this->validatorClassName, array('addError', 'translateErrorMessage'), array($options));

		$className = $this->getUniqueId('TestClass');

		eval('
			class ' . $className . ' {
				public function __toString() {
					return \'some string\';
				}
			}
		');

		$object = new $className();
		$this->assertFalse($validator->validate($object)->hasErrors());
	}

	/**
	 * @test
	 */
	public function validateReturnsErrorsIfTheGivenObjectCanNotBeConvertedToAString() {
		$options = array('minimum' => 5, 'maximum' => 100);
		$validator = $this->getMock($this->validatorClassName, array('translateErrorMessage'), array($options));

		$className = $this->getUniqueId('TestClass');

		eval('
			class ' . $className . ' {
				protected $someProperty;
			}
		');

		$object = new $className();
		$this->assertTrue($validator->validate($object)->hasErrors());
	}

	/**
	 * @test
	 */
	public function validateRegardsMultibyteStringsCorrectly() {
//		$this->validatorOptions(array('maximum' => 8));
//		$this->assertFalse($this->validator->validate('überlang')->hasErrors());
		$this->markTestSkipped('Validator needs to be adjusted regarding multibyte char lengths.');
	}
}
