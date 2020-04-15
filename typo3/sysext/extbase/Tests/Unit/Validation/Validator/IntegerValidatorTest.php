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

use TYPO3\CMS\Extbase\Validation\Validator\IntegerValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for the integer validator
 */
class IntegerValidatorTest extends UnitTestCase
{
    /**
     * @var string
     */
    protected $validatorClassName = IntegerValidator::class;

    public function setup(): void
    {
        parent::setUp();
        $this->validator = $this->getMockBuilder($this->validatorClassName)
            ->setMethods(['translateErrorMessage'])
            ->getMock();
    }

    /**
     * Data provider with valid integers
     *
     * @return array
     */
    public function validIntegers()
    {
        return [
            [1029437],
            ['12345'],
            ['+12345'],
            ['-12345']
        ];
    }

    /**
     * @test
     * @dataProvider validIntegers
     * @param mixed $integer
     */
    public function integerValidatorReturnsNoErrorsForAValidInteger($integer)
    {
        self::assertFalse($this->validator->validate($integer)->hasErrors());
    }

    /**
     * Data provider with invalid integers
     *
     * @return array
     */
    public function invalidIntegers()
    {
        return [
            ['not a number'],
            [3.1415],
            ['12345.987']
        ];
    }

    /**
     * @test
     * @dataProvider invalidIntegers
     * @param mixed $invalidInteger
     */
    public function integerValidatorReturnsErrorForAnInvalidInteger($invalidInteger)
    {
        self::assertTrue($this->validator->validate($invalidInteger)->hasErrors());
    }

    /**
     * @test
     */
    public function integerValidatorCreatesTheCorrectErrorForAnInvalidSubject()
    {
        self::assertEquals(1, count($this->validator->validate('not a number')->getErrors()));
    }
}
