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
use TYPO3\CMS\Extbase\Validation\Validator\BooleanValidator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class BooleanValidatorTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    public function booleanValidatorReturnsNoErrorForAFalseStringExpectation(): void
    {
        $options = ['is' => 'false'];
        $validator = new BooleanValidator();
        $validator->setOptions($options);
        self::assertFalse($validator->validate(false)->hasErrors());
    }

    #[Test]
    public function booleanValidatorReturnsNoErrorForATrueStringExpectation(): void
    {
        $options = ['is' => 'true'];
        $validator = new BooleanValidator();
        $validator->setOptions($options);
        self::assertFalse($validator->validate(true)->hasErrors());
    }

    #[Test]
    public function booleanValidatorReturnsNoErrorForATrueExpectation(): void
    {
        $options = ['is' => true];
        $validator = new BooleanValidator();
        $validator->setOptions($options);
        self::assertFalse($validator->validate(true)->hasErrors());
    }

    #[Test]
    public function booleanValidatorReturnsNoErrorForAFalseExpectation(): void
    {
        $options = ['is' => false];
        $validator = new BooleanValidator();
        $validator->setOptions($options);
        self::assertFalse($validator->validate(false)->hasErrors());
    }

    #[Test]
    public function booleanValidatorReturnsErrorForTrueWhenFalseExpected(): void
    {
        $options = ['is' => false];
        $validator = new BooleanValidator();
        $validator->setOptions($options);
        self::assertTrue($validator->validate(true)->hasErrors());
    }

    #[Test]
    public function booleanValidatorReturnsErrorForFalseWhenTrueExpected(): void
    {
        $options = ['is' => true];
        $validator = new BooleanValidator();
        $validator->setOptions($options);
        self::assertTrue($validator->validate(false)->hasErrors());
    }

    #[Test]
    public function booleanValidatorReturnsErrorForAString(): void
    {
        $options = ['is' => true];
        $validator = new BooleanValidator();
        $validator->setOptions($options);
        self::assertTrue($validator->validate('a string')->hasErrors());
    }

    #[Test]
    public function booleanValidatorReturnsTrueIfNoParameterIsGiven(): void
    {
        $options = [];
        $validator = new BooleanValidator();
        $validator->setOptions($options);
        self::assertFalse($validator->validate(true)->hasErrors());
    }
}
