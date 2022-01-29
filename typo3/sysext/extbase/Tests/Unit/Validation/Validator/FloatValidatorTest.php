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

use TYPO3\CMS\Extbase\Validation\Validator\FloatValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class FloatValidatorTest extends UnitTestCase
{
    public function validFloats(): array
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

    /**
     * @test
     * @dataProvider validFloats
     */
    public function floatValidatorReturnsNoErrorsForAValidFloat($float): void
    {
        $validator = $this->getMockBuilder(FloatValidator::class)
            ->onlyMethods(['translateErrorMessage'])
            ->getMock();
        self::assertFalse($validator->validate($float)->hasErrors());
    }

    public function invalidFloats(): array
    {
        return [
            [1029437],
            ['1029437'],
            ['not a number'],
        ];
    }

    /**
     * @test
     * @dataProvider invalidFloats
     */
    public function floatValidatorReturnsErrorForAnInvalidFloat($float): void
    {
        $validator = $this->getMockBuilder(FloatValidator::class)
            ->onlyMethods(['translateErrorMessage'])
            ->getMock();
        self::assertTrue($validator->validate($float)->hasErrors());
    }

    /**
     * test
     */
    public function floatValidatorCreatesTheCorrectErrorForAnInvalidSubject(): void
    {
        $validator = $this->getMockBuilder(FloatValidator::class)
            ->onlyMethods(['translateErrorMessage'])
            ->getMock();
        self::assertCount(1, $validator->validate(123456)->getErrors());
    }
}
