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

use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for the not empty validator
 */
class NotEmptyValidatorTest extends UnitTestCase
{
    protected string $validatorClassName = NotEmptyValidator::class;

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
    public function notEmptyValidatorReturnsNoErrorForASimpleString(): void
    {
        self::assertFalse($this->validator->validate('a not empty string')->hasErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorReturnsErrorForAnEmptyString(): void
    {
        self::assertTrue($this->validator->validate('')->hasErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorReturnsErrorForANullValue(): void
    {
        self::assertTrue($this->validator->validate(null)->hasErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorCreatesTheCorrectErrorForAnEmptySubject(): void
    {
        self::assertCount(1, $this->validator->validate('')->getErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorCreatesTheCorrectErrorForANullValue(): void
    {
        self::assertCount(1, $this->validator->validate(null)->getErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorWorksForEmptyArrays(): void
    {
        self::assertTrue($this->validator->validate([])->hasErrors());
        self::assertFalse($this->validator->validate([1 => 2])->hasErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorWorksForEmptyCountableObjects(): void
    {
        self::assertTrue($this->validator->validate(new \SplObjectStorage())->hasErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorWorksForNotEmptyCountableObjects(): void
    {
        $countableObject = new \SplObjectStorage();
        $countableObject->attach(new \stdClass());
        self::assertFalse($this->validator->validate($countableObject)->hasErrors());
    }
}
