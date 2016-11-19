<?php
namespace TYPO3\CMS\Form\Tests\Unit\Mvc\Validation;

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

use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Form\Mvc\Validation\EmptyValidator;

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

        $this->assertFalse($validator->validate($input)->hasErrors());
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

        $this->assertFalse($validator->validate($input)->hasErrors());
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

        $this->assertFalse($validator->validate($input)->hasErrors());
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

        $this->assertFalse($validator->validate($input)->hasErrors());
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

        $this->assertFalse($validator->validate($input)->hasErrors());
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

        $this->assertTrue($validator->validate($input)->hasErrors());
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

        $this->assertTrue($validator->validate($input)->hasErrors());
    }
}
