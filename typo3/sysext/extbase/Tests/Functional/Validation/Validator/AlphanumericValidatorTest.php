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

namespace TYPO3\CMS\Extbase\Tests\Functional\Validation\Validator;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Validation\Validator\AlphanumericValidator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class AlphanumericValidatorTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    public function alphanumericValidatorShouldReturnNoErrorsForAnAlphanumericString(): void
    {
        $subject = new AlphanumericValidator();
        $subject->setOptions([]);
        self::assertFalse($subject->validate('12ssDF34daweidf')->hasErrors());
    }

    #[Test]
    public function alphanumericValidatorReturnsErrorsForAStringWithSpecialCharacters(): void
    {
        $subject = new AlphanumericValidator();
        $subject->setOptions([]);
        self::assertTrue($subject->validate('adsf%&/$jklsfdö')->hasErrors());
    }

    #[Test]
    public function alphanumericValidatorCreatesTheCorrectErrorForAnInvalidSubject(): void
    {
        $subject = new AlphanumericValidator();
        $subject->setOptions([]);
        self::assertCount(1, $subject->validate('adsf%&/$jklsfdö')->getErrors());
    }

    #[Test]
    public function alphanumericValidatorShouldReturnNoErrorsForAnAlphanumericUnicodeString(): void
    {
        $subject = new AlphanumericValidator();
        $subject->setOptions([]);
        self::assertFalse($subject->validate('12ssDF34daweidfäøüößØœ你好')->hasErrors());
    }
}
