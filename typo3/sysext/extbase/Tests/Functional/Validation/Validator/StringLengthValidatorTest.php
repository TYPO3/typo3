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

namespace TYPO3\CMS\Extbase\Tests\Functional\Validation\Validator;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException;
use TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class StringLengthValidatorTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    public function validateReturnsNoErrorIfTheGivenValueIsNull(): void
    {
        self::assertFalse((new StringLengthValidator())->validate(null)->hasErrors());
    }

    #[Test]
    public function validateReturnsNoErrorIfTheGivenValueIsAnEmptyString(): void
    {
        self::assertFalse((new StringLengthValidator())->validate('')->hasErrors());
    }

    #[Test]
    public function stringLengthValidatorReturnsNoErrorForAStringShorterThanMaxLengthAndLongerThanMinLength(): void
    {
        $validator = new StringLengthValidator();
        $validator->setOptions(['minimum' => 0, 'maximum' => 50]);
        self::assertFalse($validator->validate('this is a very simple string')->hasErrors());
    }

    #[Test]
    public function stringLengthValidatorReturnsErrorForAStringShorterThanThanMinLength(): void
    {
        $validator = new StringLengthValidator();
        $validator->setOptions(['minimum' => 50, 'maximum' => 100]);
        self::assertTrue($validator->validate('this is a very short string')->hasErrors());
    }

    #[Test]
    public function stringLengthValidatorReturnsErrorsForAStringLongerThanThanMaxLength(): void
    {
        $validator = new StringLengthValidator();
        $validator->setOptions(['minimum' => 5, 'maximum' => 10]);
        self::assertTrue($validator->validate('this is a very short string')->hasErrors());
    }

    #[Test]
    public function stringLengthValidatorReturnsNoErrorsForAStringLongerThanThanMinLengthAndMaxLengthNotSpecified(): void
    {
        $validator = new StringLengthValidator();
        $validator->setOptions(['minimum' => 5]);
        self::assertFalse($validator->validate('this is a very short string')->hasErrors());
    }

    #[Test]
    public function stringLengthValidatorReturnsNoErrorsForAStringShorterThanThanMaxLengthAndMinLengthNotSpecified(): void
    {
        $validator = new StringLengthValidator();
        $validator->setOptions(['maximum' => 100]);
        self::assertFalse($validator->validate('this is a very short string')->hasErrors());
    }

    #[Test]
    public function stringLengthValidatorReturnsNoErrorsForAStringLengthEqualToMaxLengthAndMinLengthNotSpecified(): void
    {
        $validator = new StringLengthValidator();
        $validator->setOptions(['maximum' => 10]);
        self::assertFalse($validator->validate('1234567890')->hasErrors());
    }

    #[Test]
    public function stringLengthValidatorReturnsNoErrorForAStringLengthEqualToMinLengthAndMaxLengthNotSpecified(): void
    {
        $validator = new StringLengthValidator();
        $validator->setOptions(['minimum' => 10]);
        self::assertFalse($validator->validate('1234567890')->hasErrors());
    }

    #[Test]
    public function stringLengthValidatorReturnsNoErrorIfMinLengthAndMaxLengthAreEqualAndTheGivenStringMatchesThisValue(): void
    {
        $validator = new StringLengthValidator();
        $validator->setOptions(['minimum' => 10, 'maximum' => 10]);
        self::assertFalse($validator->validate('1234567890')->hasErrors());
    }

    #[Test]
    public function stringLengthValidatorReturnsNoErrorsIfTheStringLengthIsEqualToMaxLength(): void
    {
        $validator = new StringLengthValidator();
        $validator->setOptions(['minimum' => 1, 'maximum' => 10]);
        self::assertFalse($validator->validate('1234567890')->hasErrors());
    }

    #[Test]
    public function stringLengthValidatorReturnsNoErrorIfTheStringLengthIsEqualToMinLength(): void
    {
        $validator = new StringLengthValidator();
        $validator->setOptions(['minimum' => 10, 'maximum' => 100]);
        self::assertFalse($validator->validate('1234567890')->hasErrors());
    }

    #[Test]
    public function stringLengthValidatorThrowsAnExceptionIfMinLengthIsGreaterThanMaxLength(): void
    {
        $this->expectException(InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1238107096);
        $validator = new StringLengthValidator();
        $validator->setOptions(['minimum' => 101, 'maximum' => 100]);
        $validator->validate('1234567890');
    }

    #[Test]
    public function stringLengthValidatorInsertsAnErrorObjectIfValidationFails(): void
    {
        $validator = new StringLengthValidator();
        $validator->setOptions(['minimum' => 50, 'maximum' => 100]);
        self::assertCount(1, $validator->validate('this is a very short string')->getErrors());
    }

    #[Test]
    public function stringLengthValidatorCanHandleAnObjectWithAToStringMethod(): void
    {
        $validator = new StringLengthValidator();
        $validator->setOptions(['minimum' => 5, 'maximum' => 100]);
        $object = new class () {
            public function __toString(): string
            {
                return 'some string';
            }
        };
        self::assertFalse($validator->validate($object)->hasErrors());
    }

    #[Test]
    public function validateReturnsErrorsIfTheGivenObjectCanNotBeConvertedToAString(): void
    {
        $validator = new StringLengthValidator();
        $validator->setOptions(['minimum' => 5, 'maximum' => 100]);
        $object = new class () {};
        $result = $validator->validate($object);

        self::assertTrue($result->hasErrors());
        self::assertCount(1, $result->getErrors());

        $error = current($result->getErrors());
        self::assertSame(1238110957, $error->getCode());
    }

    #[Test]
    public function validateRegardsMultibyteStringsCorrectly(): void
    {
        $validator = new StringLengthValidator();
        $validator->setOptions(['minimum' => 0, 'maximum' => 8]);
        $result = $validator->validate('überlang');
        self::assertFalse($result->hasErrors());
    }
}
