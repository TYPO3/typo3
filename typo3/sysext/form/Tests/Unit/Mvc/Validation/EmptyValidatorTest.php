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

use TYPO3\CMS\Form\Mvc\Validation\EmptyValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class EmptyValidatorTest extends UnitTestCase
{

    /**
     * @test
     */
    public function EmptyValidatorReturnsFalseIfInputIsEmptyString()
    {
        $validator = $this->getMockBuilder(EmptyValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->getMock();

        $input = '';

        self::assertFalse($validator->validate($input)->hasErrors());
    }

    /**
     * @test
     */
    public function EmptyValidatorReturnsFalseIfInputIsNull()
    {
        $validator = $this->getMockBuilder(EmptyValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->getMock();

        $input = null;

        self::assertFalse($validator->validate($input)->hasErrors());
    }

    /**
     * @test
     */
    public function EmptyValidatorReturnsFalseIfInputIsEmptyArray()
    {
        $validator = $this->getMockBuilder(EmptyValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->getMock();

        $input = [];

        self::assertFalse($validator->validate($input)->hasErrors());
    }

    /**
     * @test
     */
    public function EmptyValidatorReturnsFalseIfInputIsZero()
    {
        $validator = $this->getMockBuilder(EmptyValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->getMock();

        $input = 0;

        self::assertFalse($validator->validate($input)->hasErrors());
    }

    /**
     * @test
     */
    public function EmptyValidatorReturnsFalseIfInputIsZeroAsString()
    {
        $validator = $this->getMockBuilder(EmptyValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->getMock();

        $input = '0';

        self::assertFalse($validator->validate($input)->hasErrors());
    }

    /**
     * @test
     */
    public function EmptyValidatorReturnsTrueIfInputIsNonEmptyString()
    {
        $validator = $this->getMockBuilder(EmptyValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->getMock();

        $input = 'hellö';

        self::assertTrue($validator->validate($input)->hasErrors());
    }

    /**
     * @test
     */
    public function EmptyValidatorReturnsTrueIfInputIsNonEmptyArray()
    {
        $validator = $this->getMockBuilder(EmptyValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->getMock();

        $input = ['hellö'];

        self::assertTrue($validator->validate($input)->hasErrors());
    }
}
