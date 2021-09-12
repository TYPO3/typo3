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

/**
 * Testcase for the float validator
 */
class FloatValidatorTest extends UnitTestCase
{
    protected string $validatorClassName = FloatValidator::class;

    public function setup(): void
    {
        parent::setUp();
        $this->validator = $this->getMockBuilder($this->validatorClassName)
            ->onlyMethods(['translateErrorMessage'])
            ->getMock();
    }

    /**
     * Data provider with valid floats
     *
     * @return array
     */
    public function validFloats()
    {
        return [
            [1029437.234726],
            ['123.45'],
            ['+123.45'],
            ['-123.45'],
            ['123.45e3'],
            [123450.0]
        ];
    }

    /**
     * @test
     * @dataProvider validFloats
     * @param mixed $float
     */
    public function floatValidatorReturnsNoErrorsForAValidFloat($float): void
    {
        self::assertFalse($this->validator->validate($float)->hasErrors());
    }

    /**
     * Data provider with invalid floats
     *
     * @return array
     */
    public function invalidFloats(): array
    {
        return [
            [1029437],
            ['1029437'],
            ['not a number']
        ];
    }

    /**
     * @test
     * @dataProvider invalidFloats
     * @param mixed $float
     */
    public function floatValidatorReturnsErrorForAnInvalidFloat($float): void
    {
        self::assertTrue($this->validator->validate($float)->hasErrors());
    }

    /**
     * test
     */
    public function floatValidatorCreatesTheCorrectErrorForAnInvalidSubject(): void
    {
        self::assertCount(1, $this->validator->validate(123456)->getErrors());
    }
}
