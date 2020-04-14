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

namespace TYPO3\CMS\Form\Tests\Unit\Mvc\Validation;

use TYPO3\CMS\Form\Mvc\Validation\DateRangeValidator;
use TYPO3\CMS\Form\Mvc\Validation\Exception\InvalidValidationOptionsException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class DateRangeValidatorTest extends UnitTestCase
{

    /**
     * @test
     */
    public function validateOptionsThrowsExceptionIfMinimumOptionIsInvalid()
    {
        $this->expectException(InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1521293813);

        $options = ['minimum' => '1972-01', 'maximum' => ''];
        $validator = $this->getMockBuilder(DateRangeValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs([$options])
            ->getMock();

        $validator->validate(true);
    }

    /**
     * @test
     */
    public function validateOptionsThrowsExceptionIfMaximumOptionIsInvalid()
    {
        $this->expectException(InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1521293814);

        $options = ['minimum' => '', 'maximum' => '1972-01'];
        $validator = $this->getMockBuilder(DateRangeValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs([$options])
            ->getMock();

        $validator->validate(true);
    }

    /**
     * @test
     */
    public function DateRangeValidatorReturnsTrueIfInputIsNoDateTime()
    {
        $options = ['minimum' => '2018-03-17', 'maximum' => '2018-03-17'];
        $validator = $this->getMockBuilder(DateRangeValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs([$options])
            ->getMock();

        self::assertTrue($validator->validate(true)->hasErrors());
    }

    /**
     * @test
     */
    public function DateRangeValidatorReturnsTrueIfInputIsLowerThanMinimumOption()
    {
        $input = \DateTime::createFromFormat('Y-m-d', '2018-03-17');
        $options = ['minimum' => '2018-03-18', 'maximum' => ''];
        $validator = $this->getMockBuilder(DateRangeValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs([$options])
            ->getMock();

        self::assertTrue($validator->validate($input)->hasErrors());
    }

    /**
     * @test
     */
    public function DateRangeValidatorReturnsFalseIfInputIsEqualsMinimumOption()
    {
        $input = \DateTime::createFromFormat('Y-m-d', '2018-03-18');
        $options = ['minimum' => '2018-03-18', 'maximum' => ''];
        $validator = $this->getMockBuilder(DateRangeValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs([$options])
            ->getMock();

        self::assertFalse($validator->validate($input)->hasErrors());
    }

    /**
     * @test
     */
    public function DateRangeValidatorReturnsFalseIfInputIsGreaterThanMinimumOption()
    {
        $input = \DateTime::createFromFormat('Y-m-d', '2018-03-19');
        $options = ['minimum' => '2018-03-18', 'maximum' => ''];
        $validator = $this->getMockBuilder(DateRangeValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs([$options])
            ->getMock();

        self::assertFalse($validator->validate($input)->hasErrors());
    }

    /**
     * @test
     */
    public function DateRangeValidatorReturnsFalseIfInputIsLowerThanMaximumOption()
    {
        $input = \DateTime::createFromFormat('Y-m-d', '2018-03-17');
        $options = ['maximum' => '', 'maximum' => '2018-03-18'];
        $validator = $this->getMockBuilder(DateRangeValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs([$options])
            ->getMock();

        self::assertFalse($validator->validate($input)->hasErrors());
    }

    /**
     * @test
     */
    public function DateRangeValidatorReturnsFalseIfInputIsEqualsMaximumOption()
    {
        $input = \DateTime::createFromFormat('Y-m-d', '2018-03-18');
        $options = ['maximum' => '', 'maximum' => '2018-03-18'];
        $validator = $this->getMockBuilder(DateRangeValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs([$options])
            ->getMock();

        self::assertFalse($validator->validate($input)->hasErrors());
    }

    /**
     * @test
     */
    public function DateRangeValidatorReturnsTrueIfInputIsGreaterThanMaximumOption()
    {
        $input = \DateTime::createFromFormat('Y-m-d', '2018-03-19');
        $options = ['maximum' => '', 'maximum' => '2018-03-18'];
        $validator = $this->getMockBuilder(DateRangeValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs([$options])
            ->getMock();

        self::assertTrue($validator->validate($input)->hasErrors());
    }
}
