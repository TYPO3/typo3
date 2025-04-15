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
use TYPO3\CMS\Extbase\Validation\Validator\StringValidator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class StringValidatorTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    public function stringValidatorShouldValidateString(): void
    {
        $subject = new StringValidator();
        $subject->setOptions([]);
        self::assertFalse($subject->validate('Hello World')->hasErrors());
    }

    #[Test]
    public function stringValidatorShouldReturnErrorIfNumberIsGiven(): void
    {
        $subject = new StringValidator();
        $subject->setOptions([]);
        self::assertTrue($subject->validate(42)->hasErrors());
    }

    #[Test]
    public function stringValidatorShouldReturnErrorIfObjectWithToStringMethodStringIsGiven(): void
    {
        $subject = new StringValidator();
        $subject->setOptions([]);
        $object = new class () {
            public function __toString(): string
            {
                return 'ASDF';
            }
        };
        self::assertTrue($subject->validate($object)->hasErrors());
    }
}
