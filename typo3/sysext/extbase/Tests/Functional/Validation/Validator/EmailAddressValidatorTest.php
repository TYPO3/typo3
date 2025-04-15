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
use TYPO3\CMS\Extbase\Validation\Validator\EmailAddressValidator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class EmailAddressValidatorTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    public function emailAddressValidatorReturnsNoErrorsForAValidEmailAddress(): void
    {
        $subject = new EmailAddressValidator();
        $subject->setOptions([]);
        self::assertFalse($subject->validate('valid.email@example.com')->hasErrors());
    }

    #[Test]
    public function emailAddressValidatorReturnsFalseForAnInvalidEmailAddress(): void
    {
        $subject = new EmailAddressValidator();
        $subject->setOptions([]);
        self::assertTrue($subject->validate('@typo3.org')->hasErrors());
    }

    #[Test]
    public function emailAddressValidatorReturnsFalseForNonStringAddress(): void
    {
        $subject = new EmailAddressValidator();
        $subject->setOptions([]);
        self::assertTrue($subject->validate(123)->hasErrors());
    }

    #[Test]
    public function emailValidatorCreatesTheCorrectErrorForAnInvalidEmailAddress(): void
    {
        $subject = new EmailAddressValidator();
        $subject->setOptions([]);
        self::assertCount(1, $subject->validate('someone@typo3.')->getErrors());
    }
}
