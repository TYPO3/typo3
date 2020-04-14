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

use TYPO3\CMS\Extbase\Validation\Validator\BooleanValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase
 */
class BooleanValidatorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function booleanValidatorReturnsNoErrorForAFalseStringExpectation()
    {
        $options = ['is' => 'false'];
        $validator = $this->getMockBuilder(BooleanValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs([$options])
            ->getMock();
        self::assertFalse($validator->validate(false)->hasErrors());
    }

    /**
     * @test
     */
    public function booleanValidatorReturnsNoErrorForATrueStringExpectation()
    {
        $options = ['is' => 'true'];
        $validator = $this->getMockBuilder(BooleanValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs([$options])
            ->getMock();
        self::assertFalse($validator->validate(true)->hasErrors());
    }

    /**
     * @test
     */
    public function booleanValidatorReturnsNoErrorForATrueExpectation()
    {
        $options = ['is' => true];
        $validator = $this->getMockBuilder(BooleanValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs([$options])
            ->getMock();
        self::assertFalse($validator->validate(true)->hasErrors());
    }

    /**
     * @test
     */
    public function booleanValidatorReturnsNoErrorForAFalseExpectation()
    {
        $options = ['is' => false];
        $validator = $this->getMockBuilder(BooleanValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs([$options])
            ->getMock();
        self::assertFalse($validator->validate(false)->hasErrors());
    }

    /**
     * @test
     */
    public function booleanValidatorReturnsErrorForTrueWhenFalseExpected()
    {
        $options = ['is' => false];
        $validator = $this->getMockBuilder(BooleanValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs([$options])
            ->getMock();
        self::assertTrue($validator->validate(true)->hasErrors());
    }

    /**
     * @test
     */
    public function booleanValidatorReturnsErrorForFalseWhenTrueExpected()
    {
        $options = ['is' => true];
        $validator = $this->getMockBuilder(BooleanValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs([$options])
            ->getMock();
        self::assertTrue($validator->validate(false)->hasErrors());
    }

    /**
     * @test
     */
    public function booleanValidatorReturnsErrorForAString()
    {
        $options = ['is' => true];
        $validator = $this->getMockBuilder(BooleanValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs([$options])
            ->getMock();
        self::assertTrue($validator->validate('a string')->hasErrors());
    }

    /**
     * @test
     */
    public function booleanValidatorReturnsTrueIfNoParameterIsGiven()
    {
        $options = [];
        $validator = $this->getMockBuilder(BooleanValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs([$options])
            ->getMock();
        self::assertFalse($validator->validate(true)->hasErrors());
    }
}
