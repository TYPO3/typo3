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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Validation\Validator\BooleanValidator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class BooleanValidatorTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    public static function validTrueValues(): array
    {
        return [
            [true],
            ['true'],
            ['1'],
            [1],
        ];
    }

    public static function validFalseValues(): array
    {
        return [
            [false],
            ['false'],
            ['0'],
            [0],
            [''],
        ];
    }

    #[DataProvider('validFalseValues')]
    #[Test]
    public function booleanValidatorReturnsNoErrorForAFalseStringExpectation(mixed $booleanValue): void
    {
        $options = ['is' => $booleanValue];
        $validator = new BooleanValidator();
        $validator->setOptions($options);
        self::assertFalse($validator->validate(false)->hasErrors());
    }

    #[DataProvider('validTrueValues')]
    #[Test]
    public function booleanValidatorReturnsNoErrorForATrueStringExpectation(mixed $booleanValue): void
    {
        $options = ['is' => $booleanValue];
        $validator = new BooleanValidator();
        $validator->setOptions($options);
        self::assertFalse($validator->validate(true)->hasErrors());
    }

    #[DataProvider('validTrueValues')]
    #[Test]
    public function booleanValidatorReturnsNoErrorForATrueExpectation(mixed $booleanValue): void
    {
        $options = ['is' => $booleanValue];
        $validator = new BooleanValidator();
        $validator->setOptions($options);
        self::assertFalse($validator->validate(true)->hasErrors());
    }

    #[DataProvider('validFalseValues')]
    #[Test]
    public function booleanValidatorReturnsNoErrorForAFalseExpectation(mixed $booleanValue): void
    {
        $options = ['is' => $booleanValue];
        $validator = new BooleanValidator();
        $validator->setOptions($options);
        self::assertFalse($validator->validate(false)->hasErrors());
    }

    #[DataProvider('validFalseValues')]
    #[Test]
    public function booleanValidatorReturnsErrorForTrueWhenFalseExpected(mixed $booleanValue): void
    {
        $options = ['is' => $booleanValue];
        $validator = new BooleanValidator();
        $validator->setOptions($options);
        self::assertTrue($validator->validate(true)->hasErrors());
    }

    #[DataProvider('validTrueValues')]
    #[Test]
    public function booleanValidatorReturnsErrorForFalseWhenTrueExpected(mixed $booleanValue): void
    {
        $options = ['is' => $booleanValue];
        $validator = new BooleanValidator();
        $validator->setOptions($options);
        self::assertTrue($validator->validate(false)->hasErrors());
    }

    #[DataProvider('validTrueValues')]
    #[Test]
    public function booleanValidatorReturnsErrorForAString(mixed $booleanValue): void
    {
        $options = ['is' => $booleanValue];
        $validator = new BooleanValidator();
        $validator->setOptions($options);
        self::assertTrue($validator->validate('a string')->hasErrors());
    }

    #[Test]
    public function booleanValidatorReturnsTrueIfNoParameterIsGiven(): void
    {
        $options = [];
        $validator = new BooleanValidator();
        $validator->setOptions($options);
        self::assertFalse($validator->validate(true)->hasErrors());
    }
}
