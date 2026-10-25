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
     * Verify that the generated password has the requested length
     */
    #[Test]
    public function generateReturnsCorrectLength(): void
    {
        $options = ['length' => 12];
        $password = $this->subject->generate($options);

        self::assertSame(12, strlen($password));
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

        self::assertMatchesRegularExpression('/\A[A-Z]+\z/', $password);
    }

    #[Test]
    public function generateContainsOnlyLowercaseWhenOtherSetsAreDisabled(): void
    {
        $password = $this->subject->generate([
            'upperCaseCharacters' => false,
            'lowerCaseCharacters' => true,
            'digitCharacters' => false,
            'specialCharacters' => false,
        ]);

        self::assertMatchesRegularExpression('/\A[a-z]+\z/', $password);
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

        self::assertMatchesRegularExpression('/\A[0-9]+\z/', $password);
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

        self::assertMatchesRegularExpression('/\A[[:punct:]]+\z/', $password);
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

        self::assertSame(10, strlen($password));
        self::assertMatchesRegularExpression('/[A-Z]/', $password);
        self::assertMatchesRegularExpression('/[a-z]/', $password);
        self::assertMatchesRegularExpression('/\d/', $password);
        self::assertMatchesRegularExpression('/[!"#$%&\'()*+,\-\.\/:;<=>\?@\[\]\\\^_`{\|}~]/', $password);
    }

    #[Test]
    public function generateRejectsOptionsWhenAllCharacterSetsAreDisabled(): void
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
