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

use TYPO3\CMS\Core\Crypto\PasswordHashing\BcryptPasswordHash;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class BcryptPasswordHashTest extends UnitTestCase
{
    protected ?BcryptPasswordHash $subject;

    /**
     * Sets up the fixtures for this testcase.
     */
    protected function setUp(): void
    {
        parent::setUp();
        // Set a low cost to speed up tests
        $options = [
            'cost' => 10,
        ];
        $this->subject = new BcryptPasswordHash($options);
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfMemoryCostIsTooLow(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1533902002);
        new BcryptPasswordHash(['cost' => 9]);
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfMemoryCostIsTooHigh(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1533902002);
        new BcryptPasswordHash(['cost' => 32]);
    }

    /**
     * @test
     */
    public function getHashedPasswordReturnsNullOnEmptyPassword(): void
    {
        self::assertNull($this->subject->getHashedPassword(''));
    }

    /**
     * @test
     */
    public function getHashedPasswordReturnsString(): void
    {
        $hash = $this->subject->getHashedPassword('password');
        self::assertNotNull($hash);
        self::assertIsString($hash);
    }

    /**
     * @test
     */
    public function isValidSaltedPwValidatesHastCreatedByGetHashedPassword(): void
    {
        $hash = $this->subject->getHashedPassword('password');
        self::assertTrue($this->subject->isValidSaltedPW($hash));
    }

    /**
     * Tests authentication procedure with alphabet characters.
     *
     * @test
     */
    public function checkPasswordReturnsTrueForHashedPasswordWithValidAlphaCharClassPassword(): void
    {
        $password = 'aEjOtY';
        $hash = $this->subject->getHashedPassword($password);
        self::assertTrue($this->subject->checkPassword($password, $hash));
    }

    /**
     * Tests authentication procedure with numeric characters.
     *
     * @test
     */
    public function checkPasswordReturnsTrueForHashedPasswordWithValidNumericCharClassPassword(): void
    {
        $password = '01369';
        $hash = $this->subject->getHashedPassword($password);
        self::assertTrue($this->subject->checkPassword($password, $hash));
    }

    /**
     * Tests authentication procedure with US-ASCII special characters.
     *
     * @test
     */
    public function checkPasswordReturnsTrueForHashedPasswordWithValidAsciiSpecialCharClassPassword(): void
    {
        $password = ' !"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~';
        $hash = $this->subject->getHashedPassword($password);
        self::assertTrue($this->subject->checkPassword($password, $hash));
    }

    /**
     * Tests authentication procedure with latin1 special characters.
     *
     * @test
     */
    public function checkPasswordReturnsTrueForHashedPasswordWithValidLatin1SpecialCharClassPassword(): void
    {
        $password = '';
        for ($i = 160; $i <= 191; $i++) {
            $password .= chr($i);
        }
        $password .= chr(215) . chr(247);
        $hash = $this->subject->getHashedPassword($password);
        self::assertTrue($this->subject->checkPassword($password, $hash));
    }

    /**
     * Tests authentication procedure with latin1 umlauts.
     *
     * @test
     */
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
        $hash = $this->subject->getHashedPassword($password);
        self::assertTrue($this->subject->checkPassword($password, $hash));
    }

    /**
     * @test
     */
    public function checkPasswordReturnsTrueForHashedPasswordWithNonValidPassword(): void
    {
        $password = 'password';
        $password1 = $password . 'INVALID';
        $hash = $this->subject->getHashedPassword($password);
        self::assertFalse($this->subject->checkPassword($password1, $hash));
    }

    /**
     * @test
     */
    public function isHashUpdateNeededReturnsFalseForJustGeneratedHash(): void
    {
        $hash = $this->subject->getHashedPassword('password');
        self::assertFalse($this->subject->isHashUpdateNeeded($hash));
    }

    /**
     * @test
     */
    public function isHashUpdateNeededReturnsTrueForHashGeneratedWithOldOptions(): void
    {
        $subject = new BcryptPasswordHash(['cost' => 10]);
        $hash = $subject->getHashedPassword('password');
        $subject = new BcryptPasswordHash(['cost' => 11]);
        self::assertTrue($subject->isHashUpdateNeeded($hash));
    }

    /**
     * Bcrypt truncates on NUL characters by default
     *
     * @test
     */
    public function getHashedPasswordDoesNotTruncateOnNul(): void
    {
        $password1 = 'pass' . "\x00" . 'word';
        $password2 = 'pass' . "\x00" . 'phrase';
        $hash = $this->subject->getHashedPassword($password1);
        self::assertFalse($this->subject->checkPassword($password2, $hash));
    }

    /**
     * Bcrypt truncates after 72 characters by default
     *
     * @test
     */
    public function getHashedPasswordDoesNotTruncateAfter72Chars(): void
    {
        $prefix = str_repeat('a', 72);
        $password1 = $prefix . 'one';
        $password2 = $prefix . 'two';
        $hash = $this->subject->getHashedPassword($password1);
        self::assertFalse($this->subject->checkPassword($password2, $hash));
    }
}
