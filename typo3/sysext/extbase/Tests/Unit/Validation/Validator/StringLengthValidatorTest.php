<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator;

use TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException;
use TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class StringLengthValidatorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function validateReturnsNoErrorIfTheGivenValueIsNull(): void
    {
        self::assertFalse((new StringLengthValidator())->validate(null)->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsNoErrorIfTheGivenValueIsAnEmptyString(): void
    {
        self::assertFalse((new StringLengthValidator())->validate('')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsNoErrorForAStringShorterThanMaxLengthAndLongerThanMinLength(): void
    {
        $validator = new StringLengthValidator(['minimum' => 0, 'maximum' => 50]);

        self::assertFalse($validator->validate('this is a very simple string')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsErrorForAStringShorterThanThanMinLength(): void
    {
        /** @var StringLengthValidator $validator */
        $validator = $this->getMockBuilder(StringLengthValidator::class)
            ->onlyMethods(['translateErrorMessage'])
            ->setConstructorArgs([['minimum' => 50, 'maximum' => 100]])
            ->getMock();

        self::assertTrue($validator->validate('this is a very short string')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsErrorsForAStringLongerThanThanMaxLength(): void
    {
        /** @var StringLengthValidator $validator */
        $validator = $this->getMockBuilder(StringLengthValidator::class)
            ->onlyMethods(['translateErrorMessage'])
            ->setConstructorArgs([['minimum' => 5, 'maximum' => 10]])
            ->getMock();

        self::assertTrue($validator->validate('this is a very short string')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsNoErrorsForAStringLongerThanThanMinLengthAndMaxLengthNotSpecified(): void
    {
        $validator = new StringLengthValidator(['minimum' => 5]);

        self::assertFalse($validator->validate('this is a very short string')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsNoErrorsForAStringShorterThanThanMaxLengthAndMinLengthNotSpecified(): void
    {
        $validator = new StringLengthValidator(['maximum' => 100]);

        self::assertFalse($validator->validate('this is a very short string')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsNoErrorsForAStringLengthEqualToMaxLengthAndMinLengthNotSpecified(): void
    {
        $validator = new StringLengthValidator(['maximum' => 10]);

        self::assertFalse($validator->validate('1234567890')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsNoErrorForAStringLengthEqualToMinLengthAndMaxLengthNotSpecified(): void
    {
        $validator = new StringLengthValidator(['minimum' => 10]);

        self::assertFalse($validator->validate('1234567890')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsNoErrorIfMinLengthAndMaxLengthAreEqualAndTheGivenStringMatchesThisValue(): void
    {
        $validator = new StringLengthValidator(['minimum' => 10, 'maximum' => 10]);

        self::assertFalse($validator->validate('1234567890')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsNoErrorsIfTheStringLengthIsEqualToMaxLength(): void
    {
        $validator = new StringLengthValidator(['minimum' => 1, 'maximum' => 10]);

        self::assertFalse($validator->validate('1234567890')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsNoErrorIfTheStringLengthIsEqualToMinLength(): void
    {
        $validator = new StringLengthValidator(['minimum' => 10, 'maximum' => 100]);

        self::assertFalse($validator->validate('1234567890')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorThrowsAnExceptionIfMinLengthIsGreaterThanMaxLength(): void
    {
        $this->expectException(InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1238107096);

        /** @var StringLengthValidator $validator */
        $validator = $this->getMockBuilder(StringLengthValidator::class)
            ->onlyMethods(['addError', 'translateErrorMessage'])
            ->setConstructorArgs([['minimum' => 101, 'maximum' => 100]])
            ->getMock();
        $validator->validate('1234567890');
    }

    /**
     * @test
     */
    public function stringLengthValidatorInsertsAnErrorObjectIfValidationFails(): void
    {
        /** @var StringLengthValidator $validator */
        $validator = $this->getMockBuilder(StringLengthValidator::class)
            ->onlyMethods(['translateErrorMessage'])
            ->setConstructorArgs([['minimum' => 50, 'maximum' => 100]])
            ->getMock();

        self::assertCount(1, $validator->validate('this is a very short string')->getErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorCanHandleAnObjectWithAToStringMethod(): void
    {
        $validator = new StringLengthValidator(['minimum' => 5, 'maximum' => 100]);
        $object = new class() {
            /** @return string */
            public function __toString()
            {
                return 'some string';
            }
        };

        self::assertFalse($validator->validate($object)->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsErrorsIfTheGivenObjectCanNotBeConvertedToAString(): void
    {
        $validator = new StringLengthValidator(['minimum' => 5, 'maximum' => 100]);
        $object = new class() {
        };

        $result = $validator->validate($object);

        self::assertTrue($result->hasErrors());
        self::assertCount(1, $result->getErrors());

        /** @var \TYPO3\CMS\Extbase\Validation\Error $error */
        $error = current($result->getErrors());
        self::assertSame(1238110957, $error->getCode());
    }

    /**
     * @test
     */
    public function validateRegardsMultibyteStringsCorrectly(): void
    {
        $validator = new StringLengthValidator(['minimum' => 0, 'maximum' => 8]);
        $result = $validator->validate('überlang');

        self::assertFalse($result->hasErrors());
    }
}
