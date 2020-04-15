<?php

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

use TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator\Fixture\AbstractValidatorClass;
use TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for the abstract base-class of validators
 */
class AbstractValidatorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function validatorAcceptsSupportedOptions(): void
    {
        $inputOptions = [
            'requiredOption' => 666,
            'demoOption' => 42
        ];
        $expectedOptions = $inputOptions;
        $validator = new AbstractValidatorClass($inputOptions);
        self::assertSame($expectedOptions, $validator->getOptions());
    }

    /**
     * @test
     */
    public function validatorHasDefaultOptions(): void
    {
        $inputOptions = ['requiredOption' => 666];
        $expectedOptions = [
            'requiredOption' => 666,
            'demoOption' => PHP_INT_MAX
        ];
        $validator = new AbstractValidatorClass($inputOptions);
        self::assertSame($expectedOptions, $validator->getOptions());
    }

    /**
     * @test
     */
    public function validatorThrowsExceptionOnNotSupportedOptions(): void
    {
        $inputOptions = ['invalidoption' => 42];
        $this->expectException(InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1379981890);
        new AbstractValidatorClass($inputOptions);
    }

    /**
     * @test
     */
    public function validatorThrowsExceptionOnMissingRequiredOptions(): void
    {
        $inputOptions = [];
        $this->expectException(InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1379981891);
        new AbstractValidatorClass($inputOptions);
    }
}
