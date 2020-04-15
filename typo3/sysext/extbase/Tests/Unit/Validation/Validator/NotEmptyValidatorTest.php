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

use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for the not empty validator
 */
class NotEmptyValidatorTest extends UnitTestCase
{
    /**
     * @var string
     */
    protected $validatorClassName = NotEmptyValidator::class;

    public function setup(): void
    {
        parent::setUp();
        $this->validator = $this->getMockBuilder($this->validatorClassName)
            ->setMethods(['translateErrorMessage'])
            ->getMock();
    }

    /**
     * @test
     */
    public function notEmptyValidatorReturnsNoErrorForASimpleString()
    {
        self::assertFalse($this->validator->validate('a not empty string')->hasErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorReturnsErrorForAnEmptyString()
    {
        self::assertTrue($this->validator->validate('')->hasErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorReturnsErrorForANullValue()
    {
        self::assertTrue($this->validator->validate(null)->hasErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorCreatesTheCorrectErrorForAnEmptySubject()
    {
        self::assertEquals(1, count($this->validator->validate('')->getErrors()));
    }

    /**
     * @test
     */
    public function notEmptyValidatorCreatesTheCorrectErrorForANullValue()
    {
        self::assertEquals(1, count($this->validator->validate(null)->getErrors()));
    }

    /**
     * @test
     */
    public function notEmptyValidatorWorksForEmptyArrays()
    {
        self::assertTrue($this->validator->validate([])->hasErrors());
        self::assertFalse($this->validator->validate([1 => 2])->hasErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorWorksForEmptyCountableObjects()
    {
        self::assertTrue($this->validator->validate(new \SplObjectStorage())->hasErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorWorksForNotEmptyCountableObjects()
    {
        $countableObject = new \SplObjectStorage();
        $countableObject->attach(new \stdClass());
        self::assertFalse($this->validator->validate($countableObject)->hasErrors());
    }
}
