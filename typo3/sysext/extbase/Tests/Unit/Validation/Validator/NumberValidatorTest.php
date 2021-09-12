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

use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Validation\Error;
use TYPO3\CMS\Extbase\Validation\Validator\NumberValidator;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class NumberValidatorTest extends UnitTestCase
{
    protected string $validatorClassName = NumberValidator::class;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    public function setup(): void
    {
        parent::setUp();
        $this->validator = $this->getMockBuilder($this->validatorClassName)
            ->onlyMethods(['translateErrorMessage'])
            ->getMock();
    }

    /**
     * @test
     */
    public function numberValidatorReturnsTrueForASimpleInteger(): void
    {
        self::assertFalse($this->validator->validate(1029437)->hasErrors());
    }

    /**
     * @test
     */
    public function numberValidatorReturnsFalseForAString(): void
    {
        $expectedResult = new Result();
        // we only test for the error code, after the message translation method is mocked
        $expectedResult->addError(new Error('', 1221563685));
        self::assertEquals($expectedResult, $this->validator->validate('not a number'));
    }
}
