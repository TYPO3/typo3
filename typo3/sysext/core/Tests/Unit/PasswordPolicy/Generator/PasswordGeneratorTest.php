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

namespace TYPO3\CMS\Core\Tests\Unit\PasswordPolicy\Generator;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Exception\InvalidPasswordRulesException;
use TYPO3\CMS\Core\PasswordPolicy\Generator\PasswordGenerator;

final class PasswordGeneratorTest extends TestCase
{
    private PasswordGenerator $subject;

    protected function setUp(): void
    {
        $this->subject = new PasswordGenerator(new Random());
    }

    /**
     * Verify that the generated password meets the minimum length requirement
     */
    #[Test]
    public function generateReturnsCorrectLength(): void
    {
        $options = ['length' => 12];
        $password = $this->subject->generate($options);

        self::assertGreaterThanOrEqual(12, mb_strlen($password));
    }

    /**
     * Verify that uppercase characters are present when required
     */
    #[Test]
    public function generateContainsUppercaseWhenRequired(): void
    {
        $options = [
            'upperCaseCharacters' => true,
            'lowerCaseCharacters' => false,
            'digitCharacters' => false,
            'specialCharacters' => false,
        ];

        $password = $this->subject->generate($options);

        self::assertMatchesRegularExpression('/[A-Z]/', $password);
    }

    /**
     * Verify that digits are present when required
     */
    #[Test]
    public function generateContainsDigitWhenRequired(): void
    {
        $options = [
            'upperCaseCharacters' => false,
            'lowerCaseCharacters' => false,
            'digitCharacters' => true,
            'specialCharacters' => false,
        ];

        $password = $this->subject->generate($options);

        self::assertMatchesRegularExpression('/\d/', $password);
    }

    /**
     * Verify that special characters are present when required
     */
    #[Test]
    public function generateContainsSpecialCharacterWhenRequired(): void
    {
        $options = [
            'upperCaseCharacters' => false,
            'lowerCaseCharacters' => false,
            'digitCharacters' => false,
            'specialCharacters' => true,
        ];

        $password = $this->subject->generate($options);

        // Match one of the special characters defined in the generator
        self::assertMatchesRegularExpression('/[!@#$%^&*()\-=_+\[\]{}|;:,.<>?]/', $password);
    }

    /**
     * Verify that all requirements can be met simultaneously
     */
    #[Test]
    public function generateMeetsAllRequirementsSimultaneously(): void
    {
        $options = [
            'length' => 10,
            'upperCaseCharacters' => true,
            'lowerCaseCharacters' => true,
            'digitCharacters' => true,
            'specialCharacters' => true,
        ];

        $password = $this->subject->generate($options);

        self::assertGreaterThanOrEqual(10, strlen($password));
        self::assertMatchesRegularExpression('/[A-Z]/', $password);
        self::assertMatchesRegularExpression('/[a-z]/', $password);
        self::assertMatchesRegularExpression('/\d/', $password);
        self::assertMatchesRegularExpression('/[!"#$%&\'()*+,\-\.\/:;<=>\?@\[\]\\\^_`{\|}~]/', $password);
    }

    /**
     * Verify fallback to alphanumeric if all specific requirements are disabled
     */
    #[Test]
    public function generateFallsBackToAlphanumericWhenNoRequirementsSet(): void
    {
        $this->expectException(InvalidPasswordRulesException::class);

        $options = [
            'upperCaseCharacters' => false,
            'lowerCaseCharacters' => false,
            'digitCharacters' => false,
            'specialCharacters' => false,
        ];

        $this->subject->generate($options);
    }
}
