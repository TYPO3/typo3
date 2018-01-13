<?php
declare(strict_types = 1);
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

use TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException;
use TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator;

/**
 * Test case
 */
class StringLengthValidatorTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @test
     */
    public function validateReturnsNoErrorIfTheGivenValueIsNull()
    {
        $this->assertFalse((new StringLengthValidator())->validate(null)->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsNoErrorIfTheGivenValueIsAnEmptyString()
    {
        $this->assertFalse((new StringLengthValidator())->validate('')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsNoErrorForAStringShorterThanMaxLengthAndLongerThanMinLength()
    {
        $validator = new StringLengthValidator(['minimum' => 0, 'maximum' => 50]);

        $this->assertFalse($validator->validate('this is a very simple string')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsErrorForAStringShorterThanThanMinLength()
    {
        /** @var StringLengthValidator $validator */
        $validator = $this->getMockBuilder(StringLengthValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs([['minimum' => 50, 'maximum' => 100]])
            ->getMock();

        $this->assertTrue($validator->validate('this is a very short string')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsErrorsForAStringLongerThanThanMaxLength()
    {
        /** @var StringLengthValidator $validator */
        $validator = $this->getMockBuilder(StringLengthValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs([['minimum' => 5, 'maximum' => 10]])
            ->getMock();

        $this->assertTrue($validator->validate('this is a very short string')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsNoErrorsForAStringLongerThanThanMinLengthAndMaxLengthNotSpecified()
    {
        $validator = new StringLengthValidator(['minimum' => 5]);

        $this->assertFalse($validator->validate('this is a very short string')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsNoErrorsForAStringShorterThanThanMaxLengthAndMinLengthNotSpecified()
    {
        $validator = new StringLengthValidator(['maximum' => 100]);

        $this->assertFalse($validator->validate('this is a very short string')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsNoErrorsForAStringLengthEqualToMaxLengthAndMinLengthNotSpecified()
    {
        $validator = new StringLengthValidator(['maximum' => 10]);

        $this->assertFalse($validator->validate('1234567890')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsNoErrorForAStringLengthEqualToMinLengthAndMaxLengthNotSpecified()
    {
        $validator = new StringLengthValidator(['minimum' => 10]);

        $this->assertFalse($validator->validate('1234567890')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsNoErrorIfMinLengthAndMaxLengthAreEqualAndTheGivenStringMatchesThisValue()
    {
        $validator = new StringLengthValidator(['minimum' => 10, 'maximum' => 10]);

        $this->assertFalse($validator->validate('1234567890')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsNoErrorsfTheStringLengthIsEqualToMaxLength()
    {
        $validator = new StringLengthValidator(['minimum' => 1, 'maximum' => 10]);

        $this->assertFalse($validator->validate('1234567890')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsNoErrorIfTheStringLengthIsEqualToMinLength()
    {
        $validator = new StringLengthValidator(['minimum' => 10, 'maximum' => 100]);

        $this->assertFalse($validator->validate('1234567890')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorThrowsAnExceptionIfMinLengthIsGreaterThanMaxLength()
    {
        $this->expectException(InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1238107096);

        /** @var StringLengthValidator $validator */
        $validator = $this->getMockBuilder(StringLengthValidator::class)
            ->setMethods(['addError', 'translateErrorMessage'])
            ->setConstructorArgs([['minimum' => 101, 'maximum' => 100]])
            ->getMock();
        $validator->validate('1234567890');
    }

    /**
     * @test
     */
    public function stringLengthValidatorInsertsAnErrorObjectIfValidationFails()
    {
        /** @var StringLengthValidator $validator */
        $validator = $this->getMockBuilder(StringLengthValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs([['minimum' => 50, 'maximum' => 100]])
            ->getMock();

        $this->assertCount(1, $validator->validate('this is a very short string')->getErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorCanHandleAnObjectWithAToStringMethod()
    {
        $validator = new StringLengthValidator(['minimum' => 5, 'maximum' => 100]);
        $object = new class() {
            /** @return string */
            public function __toString()
            {
                return 'some string';
            }
        };

        $this->assertFalse($validator->validate($object)->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsErrorsIfTheGivenObjectCanNotBeConvertedToAString()
    {
        $validator = new StringLengthValidator(['minimum' => 5, 'maximum' => 100]);
        $object = new class() {
        };

        $result = $validator->validate($object);

        $this->assertTrue($result->hasErrors());
        $this->assertCount(1, $result->getErrors());

        /** @var \TYPO3\CMS\Extbase\Validation\Error $error */
        $error = current($result->getErrors());
        $this->assertSame(1238110957, $error->getCode());
    }

    /**
     * @test
     */
    public function validateRegardsMultibyteStringsCorrectly()
    {
        $validator = new StringLengthValidator(['minimum' => 0, 'maximum' => 8]);
        $result = $validator->validate('überlang');

        $this->assertFalse($result->hasErrors());
    }
}
