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
use TYPO3\CMS\Extbase\Validation\Validator\FloatValidator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FloatValidatorTest extends FunctionalTestCase
{
    public static function validFloats(): array
    {
        return [
            [1029437.234726],
            ['123.45'],
            ['+123.45'],
            ['-123.45'],
            ['123.45e3'],
            [123450.0],
        ];
    }

    #[DataProvider('validFloats')]
    #[Test]
    public function floatValidatorReturnsNoErrorsForAValidFloat(float|string $float): void
    {
        $validator = new FloatValidator();
        $validator->setOptions([]);
        self::assertFalse($validator->validate($float)->hasErrors());
    }

    public static function invalidFloats(): array
    {
        return [
            [1029437],
            ['1029437'],
            ['not a number'],
        ];
    }

    #[DataProvider('invalidFloats')]
    #[Test]
    public function floatValidatorReturnsErrorForAnInvalidFloat(int|string $float): void
    {
        $validator = new FloatValidator();
        $validator->setOptions([]);
        self::assertTrue($validator->validate($float)->hasErrors());
    }

    /**
     * test
     */
    public function floatValidatorCreatesTheCorrectErrorForAnInvalidSubject(): void
    {
        $validator = new FloatValidator();
        $validator->setOptions([]);
        self::assertCount(1, $validator->validate(123456)->getErrors());
    }
}
