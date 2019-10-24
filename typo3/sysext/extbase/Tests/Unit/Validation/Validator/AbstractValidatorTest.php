<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator;

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

use TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator\Fixture\AbstractValidatorClass;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for the abstract base-class of validators
 */
class AbstractValidatorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function validatorAcceptsSupportedOptions()
    {
        $inputOptions = [
            'requiredOption' => 666,
            'demoOption' => 42
        ];
        $expectedOptions = $inputOptions;
        $validator = $this->getAccessibleMock(AbstractValidatorClass::class, ['dummy'], [$inputOptions]);
        self::assertSame($expectedOptions, $validator->_get('options'));
    }

    /**
     * @test
     */
    public function validatorHasDefaultOptions()
    {
        $inputOptions = ['requiredOption' => 666];
        $expectedOptions = [
            'requiredOption' => 666,
            'demoOption' => PHP_INT_MAX
        ];
        $validator = $this->getAccessibleMock(AbstractValidatorClass::class, ['dummy'], [$inputOptions]);
        self::assertSame($expectedOptions, $validator->_get('options'));
    }

    /**
     * @test
     */
    public function validatorThrowsExceptionOnNotSupportedOptions()
    {
        $inputOptions = ['invalidoption' => 42];
        $this->expectException(\TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1379981890);
        $this->getAccessibleMock(AbstractValidatorClass::class, ['dummy'], [$inputOptions]);
    }

    /**
     * @test
     */
    public function validatorThrowsExceptionOnMissingRequiredOptions()
    {
        $inputOptions = [];
        $this->expectException(\TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1379981891);
        $this->getAccessibleMock(AbstractValidatorClass::class, ['dummy'], [$inputOptions]);
    }
}
