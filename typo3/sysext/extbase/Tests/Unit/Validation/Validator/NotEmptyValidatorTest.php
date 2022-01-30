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
    /**
     * @test
     */
    public function notEmptyValidatorReturnsNoErrorForASimpleString(): void
    {
        $validator = $this->getMockBuilder(NotEmptyValidator::class)->onlyMethods(['translateErrorMessage'])->getMock();
        self::assertFalse($validator->validate('a not empty string')->hasErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorReturnsErrorForAnEmptyString(): void
    {
        $validator = $this->getMockBuilder(NotEmptyValidator::class)->onlyMethods(['translateErrorMessage'])->getMock();
        self::assertTrue($validator->validate('')->hasErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorReturnsErrorForANullValue(): void
    {
        $validator = $this->getMockBuilder(NotEmptyValidator::class)->onlyMethods(['translateErrorMessage'])->getMock();
        self::assertTrue($validator->validate(null)->hasErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorCreatesTheCorrectErrorForAnEmptySubject(): void
    {
        $validator = $this->getMockBuilder(NotEmptyValidator::class)->onlyMethods(['translateErrorMessage'])->getMock();
        self::assertCount(1, $validator->validate('')->getErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorCreatesTheCorrectErrorForANullValue(): void
    {
        $validator = $this->getMockBuilder(NotEmptyValidator::class)->onlyMethods(['translateErrorMessage'])->getMock();
        self::assertCount(1, $validator->validate(null)->getErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorWorksForEmptyArrays(): void
    {
        $validator = $this->getMockBuilder(NotEmptyValidator::class)->onlyMethods(['translateErrorMessage'])->getMock();
        self::assertTrue($validator->validate([])->hasErrors());
        self::assertFalse($validator->validate([1 => 2])->hasErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorWorksForEmptyCountableObjects(): void
    {
        $validator = $this->getMockBuilder(NotEmptyValidator::class)->onlyMethods(['translateErrorMessage'])->getMock();
        self::assertTrue($validator->validate(new \SplObjectStorage())->hasErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorWorksForNotEmptyCountableObjects(): void
    {
        $countableObject = new \SplObjectStorage();
        $countableObject->attach(new \stdClass());
        $validator = $this->getMockBuilder(NotEmptyValidator::class)->onlyMethods(['translateErrorMessage'])->getMock();
        self::assertFalse($validator->validate($countableObject)->hasErrors());
    }
}
