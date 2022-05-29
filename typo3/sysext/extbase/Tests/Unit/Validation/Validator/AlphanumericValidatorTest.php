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

use TYPO3\CMS\Extbase\Validation\Validator\AlphanumericValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class AlphanumericValidatorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function alphanumericValidatorShouldReturnNoErrorsForAnAlphanumericString(): void
    {
        $subject = $this->getMockBuilder(AlphanumericValidator::class)
            ->onlyMethods(['translateErrorMessage'])
            ->getMock();
        self::assertFalse($subject->validate('12ssDF34daweidf')->hasErrors());
    }

    /**
     * @test
     */
    public function alphanumericValidatorReturnsErrorsForAStringWithSpecialCharacters(): void
    {
        $subject = $this->getMockBuilder(AlphanumericValidator::class)
            ->onlyMethods(['translateErrorMessage'])
            ->getMock();
        self::assertTrue($subject->validate('adsf%&/$jklsfdö')->hasErrors());
    }

    /**
     * @test
     */
    public function alphanumericValidatorCreatesTheCorrectErrorForAnInvalidSubject(): void
    {
        $subject = $this->getMockBuilder(AlphanumericValidator::class)
            ->onlyMethods(['translateErrorMessage'])
            ->getMock();
        self::assertCount(1, $subject->validate('adsf%&/$jklsfdö')->getErrors());
    }

    /**
     * @test
     */
    public function alphanumericValidatorShouldReturnNoErrorsForAnAlphanumericUnicodeString(): void
    {
        $subject = $this->getMockBuilder(AlphanumericValidator::class)
            ->onlyMethods(['translateErrorMessage'])
            ->getMock();
        self::assertFalse($subject->validate('12ssDF34daweidfäøüößØœ你好')->hasErrors());
    }
}
