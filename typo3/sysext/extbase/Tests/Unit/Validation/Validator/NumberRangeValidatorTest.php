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

use TYPO3\CMS\Extbase\Validation\Validator\NumberRangeValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class NumberRangeValidatorTest extends UnitTestCase
{
    /**
     * @var string
     */
    protected $validatorClassName = NumberRangeValidator::class;

    /**
     * @test
     */
    public function numberRangeValidatorReturnsNoErrorForASimpleIntegerInRange()
    {
        $options = ['minimum' => 0, 'maximum' => 1000];
        $validator = $this->getMockBuilder($this->validatorClassName)
            ->setMethods(['dummy'])
            ->setConstructorArgs([$options])
            ->getMock();
        self::assertFalse($validator->validate(10.5)->hasErrors());
    }

    /**
     * @test
     */
    public function numberRangeValidatorReturnsErrorForANumberOutOfRange()
    {
        $options = ['minimum' => 0, 'maximum' => 1000];
        $validator = $this->getMockBuilder($this->validatorClassName)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs([$options])
            ->getMock();
        self::assertTrue($validator->validate(1000.1)->hasErrors());
    }

    /**
     * @test
     */
    public function numberRangeValidatorReturnsNoErrorForANumberInReversedRange()
    {
        $options = ['minimum' => 1000, 'maximum' => 0];
        $validator = $this->getMockBuilder($this->validatorClassName)
            ->setMethods(['dummy'])
            ->setConstructorArgs([$options])
            ->getMock();
        self::assertFalse($validator->validate(100)->hasErrors());
    }

    /**
     * @test
     */
    public function numberRangeValidatorReturnsErrorForAString()
    {
        $options = ['minimum' => 0, 'maximum' => 1000];
        $validator = $this->getMockBuilder($this->validatorClassName)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs([$options])
            ->getMock();
        self::assertTrue($validator->validate('not a number')->hasErrors());
    }
}
