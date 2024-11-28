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

namespace TYPO3\CMS\Core\Tests\Unit\Crypto\PasswordHashing;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Crypto\PasswordHashing\BcryptPasswordHash;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class BcryptPasswordHashTest extends UnitTestCase
{
    private const DEFAULT_OPTIONS = [
        // Set low cost to speed up tests
        'cost' => 10,
    ];

    #[Test]
    public function constructorThrowsExceptionIfMemoryCostIsTooLow(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1533902002);
        new BcryptPasswordHash(['cost' => 9]);
    }

    #[Test]
    public function constructorThrowsExceptionIfMemoryCostIsTooHigh(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1533902002);
        new BcryptPasswordHash(['cost' => 32]);
    }

    #[Test]
    public function getHashedPasswordReturnsNullOnEmptyPassword(): void
    {
        $subject = new BcryptPasswordHash(self::DEFAULT_OPTIONS);
        self::assertNull($subject->getHashedPassword(''));
    }

    #[Test]
    public function getHashedPasswordDoesNotReturnNull(): void
    {
        $subject = new BcryptPasswordHash(self::DEFAULT_OPTIONS);
        $hash = $subject->getHashedPassword('password');
        self::assertNotNull($hash);
    }

    #[Test]
    public function isValidSaltedPwValidatesHastCreatedByGetHashedPassword(): void
    {
        $subject = new BcryptPasswordHash(self::DEFAULT_OPTIONS);
        $hash = $subject->getHashedPassword('password');
        self::assertTrue($subject->isValidSaltedPW($hash));
    }

    /**
     * Tests authentication procedure with alphabet characters.
     */
    #[Test]
    public function checkPasswordReturnsTrueForHashedPasswordWithValidAlphaCharClassPassword(): void
    {
        $password = 'aEjOtY';
        $subject = new BcryptPasswordHash(self::DEFAULT_OPTIONS);
        $hash = $subject->getHashedPassword($password);
        self::assertTrue($subject->checkPassword($password, $hash));
    }

    /**
     * Tests authentication procedure with numeric characters.
     */
    #[Test]
    public function checkPasswordReturnsTrueForHashedPasswordWithValidNumericCharClassPassword(): void
    {
        $password = '01369';
        $subject = new BcryptPasswordHash(self::DEFAULT_OPTIONS);
        $hash = $subject->getHashedPassword($password);
        self::assertTrue($subject->checkPassword($password, $hash));
    }

    /**
     * Tests authentication procedure with US-ASCII special characters.
     */
    #[Test]
    public function checkPasswordReturnsTrueForHashedPasswordWithValidAsciiSpecialCharClassPassword(): void
    {
        $password = ' !"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~';
        $subject = new BcryptPasswordHash(self::DEFAULT_OPTIONS);
        $hash = $subject->getHashedPassword($password);
        self::assertTrue($subject->checkPassword($password, $hash));
    }

    /**
     * Tests authentication procedure with latin1 special characters.
     */
    #[Test]
    public function checkPasswordReturnsTrueForHashedPasswordWithValidLatin1SpecialCharClassPassword(): void
    {
        $password = '';
        for ($i = 160; $i <= 191; $i++) {
            $password .= chr($i);
        }
        $password .= chr(215) . chr(247);
        $subject = new BcryptPasswordHash(self::DEFAULT_OPTIONS);
        $hash = $subject->getHashedPassword($password);
        self::assertTrue($subject->checkPassword($password, $hash));
    }

    /**
     * Tests authentication procedure with latin1 umlauts.
     */
    #[Test]
    public function checkPasswordReturnsTrueForHashedPasswordWithValidLatin1UmlautCharClassPassword(): void
    {
        $password = '';
        for ($i = 192; $i <= 255; $i++) {
            if ($i === 215 || $i === 247) {
                // skip multiplication sign (×) and obelus (÷)
                continue;
            }
            $password .= chr($i);
        }
        $subject = new BcryptPasswordHash(self::DEFAULT_OPTIONS);
        $hash = $subject->getHashedPassword($password);
        self::assertTrue($subject->checkPassword($password, $hash));
    }

    #[Test]
    public function checkPasswordReturnsTrueForHashedPasswordWithNonValidPassword(): void
    {
        $password = 'password';
        $password1 = $password . 'INVALID';
        $subject = new BcryptPasswordHash(self::DEFAULT_OPTIONS);
        $hash = $subject->getHashedPassword($password);
        self::assertFalse($subject->checkPassword($password1, $hash));
    }

    #[Test]
    public function isHashUpdateNeededReturnsFalseForJustGeneratedHash(): void
    {
        $subject = new BcryptPasswordHash(self::DEFAULT_OPTIONS);
        $hash = $subject->getHashedPassword('password');
        self::assertFalse($subject->isHashUpdateNeeded($hash));
    }

    #[Test]
    public function isHashUpdateNeededReturnsTrueForHashGeneratedWithOldOptions(): void
    {
        $subject = new BcryptPasswordHash(['cost' => 10]);
        $hash = $subject->getHashedPassword('password');
        $subject = new BcryptPasswordHash(['cost' => 11]);
        self::assertTrue($subject->isHashUpdateNeeded($hash));
    }

    /**
     * Bcrypt truncates on NUL characters by default
     */
    #[Test]
    public function getHashedPasswordDoesNotTruncateOnNul(): void
    {
        $password1 = 'pass' . "\x00" . 'word';
        $password2 = 'pass' . "\x00" . 'phrase';
        $subject = new BcryptPasswordHash(self::DEFAULT_OPTIONS);
        $hash = $subject->getHashedPassword($password1);
        self::assertFalse($subject->checkPassword($password2, $hash));
    }

    /**
     * Bcrypt truncates after 72 characters by default
     */
    #[Test]
    public function getHashedPasswordDoesNotTruncateAfter72Chars(): void
    {
        $prefix = str_repeat('a', 72);
        $password1 = $prefix . 'one';
        $password2 = $prefix . 'two';
        $subject = new BcryptPasswordHash(self::DEFAULT_OPTIONS);
        $hash = $subject->getHashedPassword($password1);
        self::assertFalse($subject->checkPassword($password2, $hash));
    }
}
