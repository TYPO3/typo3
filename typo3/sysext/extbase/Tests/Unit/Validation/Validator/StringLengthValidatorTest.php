<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator;

/*                                                                        *
 * This script belongs to the Extbase framework.                          *
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
 * Test case
 */
class StringLengthValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    protected $validatorClassName = \TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator::class;

    public function setup()
    {
        $this->validator = $this->getMock($this->validatorClassName, ['translateErrorMessage']);
    }

    /**
     * @var \TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator
     */
    protected $validator;

    /**
     * @test
     */
    public function validateReturnsNoErrorIfTheGivenValueIsNull()
    {
        $this->assertFalse($this->validator->validate(null)->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsNoErrorIfTheGivenValueIsAnEmptyString()
    {
        $this->assertFalse($this->validator->validate('')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsNoErrorForAStringShorterThanMaxLengthAndLongerThanMinLength()
    {
        $options = ['minimum' => 0, 'maximum' => 50];
        $validator = $this->getMock($this->validatorClassName, ['dummy'], [$options]);
        $this->assertFalse($validator->validate('this is a very simple string')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsErrorForAStringShorterThanThanMinLength()
    {
        $options = ['minimum' => 50, 'maximum' => 100];
        $validator = $this->getMock($this->validatorClassName, ['translateErrorMessage'], [$options]);
        $this->assertTrue($validator->validate('this is a very short string')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsErrorsForAStringLongerThanThanMaxLength()
    {
        $options = ['minimum' => 5, 'maximum' => 10];
        $validator = $this->getMock($this->validatorClassName, ['translateErrorMessage'], [$options]);
        $this->assertTrue($validator->validate('this is a very short string')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsNoErrorsForAStringLongerThanThanMinLengthAndMaxLengthNotSpecified()
    {
        $options = ['minimum' => 5];
        $validator = $this->getMock($this->validatorClassName, ['dummy'], [$options]);
        $this->assertFalse($validator->validate('this is a very short string')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsNoErrorsForAStringShorterThanThanMaxLengthAndMinLengthNotSpecified()
    {
        $options = ['maximum' => 100];
        $validator = $this->getMock($this->validatorClassName, ['dummy'], [$options]);
        $this->assertFalse($validator->validate('this is a very short string')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsNoErrorsForAStringLengthEqualToMaxLengthAndMinLengthNotSpecified()
    {
        $options = ['maximum' => 10];
        $validator = $this->getMock($this->validatorClassName, ['dummy'], [$options]);
        $this->assertFalse($validator->validate('1234567890')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsNoErrorForAStringLengthEqualToMinLengthAndMaxLengthNotSpecified()
    {
        $options = ['minimum' => 10];
        $validator = $this->getMock($this->validatorClassName, ['dummy'], [$options]);
        $this->assertFalse($validator->validate('1234567890')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsNoErrorIfMinLengthAndMaxLengthAreEqualAndTheGivenStringMatchesThisValue()
    {
        $options = ['minimum' => 10, 'maximum' => 10];
        $validator = $this->getMock($this->validatorClassName, ['dummy'], [$options]);
        $this->assertFalse($validator->validate('1234567890')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsNoErrorsfTheStringLengthIsEqualToMaxLength()
    {
        $options = ['minimum' => 1, 'maximum' => 10];
        $validator = $this->getMock($this->validatorClassName, ['dummy'], [$options]);
        $this->assertFalse($validator->validate('1234567890')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsNoErrorIfTheStringLengthIsEqualToMinLength()
    {
        $options = ['minimum' => 10, 'maximum' => 100];
        $validator = $this->getMock($this->validatorClassName, ['dummy'], [$options]);
        $this->assertFalse($validator->validate('1234567890')->hasErrors());
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException
     */
    public function stringLengthValidatorThrowsAnExceptionIfMinLengthIsGreaterThanMaxLength()
    {
        $options = ['minimum' => 101, 'maximum' => 100];
        $validator = $this->getMock($this->validatorClassName, ['addError', 'translateErrorMessage'], [$options]);
        $validator->validate('1234567890');
    }

    /**
     * @test
     */
    public function stringLengthValidatorInsertsAnErrorObjectIfValidationFails()
    {
        $options = ['minimum' => 50, 'maximum' => 100];
        $validator = $this->getMock($this->validatorClassName, ['translateErrorMessage'], [$options]);
        $this->assertEquals(1, count($validator->validate('this is a very short string')->getErrors()));
    }

    /**
     * @test
     */
    public function stringLengthValidatorCanHandleAnObjectWithAToStringMethod()
    {
        $options = ['minimum' => 5, 'maximum' => 100];
        $validator = $this->getMock($this->validatorClassName, ['addError', 'translateErrorMessage'], [$options]);

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
    public function validateReturnsErrorsIfTheGivenObjectCanNotBeConvertedToAString()
    {
        $options = ['minimum' => 5, 'maximum' => 100];
        $validator = $this->getMock($this->validatorClassName, ['translateErrorMessage'], [$options]);

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
    public function validateRegardsMultibyteStringsCorrectly()
    {
        //		$this->validatorOptions(array('maximum' => 8));
//		$this->assertFalse($this->validator->validate('überlang')->hasErrors());
        $this->markTestSkipped('Validator needs to be adjusted regarding multibyte char lengths.');
    }
}
