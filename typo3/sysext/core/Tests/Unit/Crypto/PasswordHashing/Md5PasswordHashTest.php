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

use TYPO3\CMS\Core\Crypto\PasswordHashing\Md5PasswordHash;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class Md5PasswordHashTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getHashedPasswordReturnsNullWithEmptyPassword()
    {
        self::assertNull((new Md5PasswordHash())->getHashedPassword(''));
    }

    /**
     * @test
     */
    public function getHashedPasswordReturnsNotNullWithNonEmptyPassword()
    {
        self::assertNotNull((new Md5PasswordHash())->getHashedPassword('a'));
    }

    /**
     * @test
     */
    public function getHashedPasswordCreatesAHashThatValidates()
    {
        $password = 'password';
        $subject = new Md5PasswordHash();
        $saltedHashPassword = $subject->getHashedPassword($password);
        self::assertTrue($subject->isValidSaltedPW($saltedHashPassword));
    }

    /**
     * Tests authentication procedure with fixed password and fixed (pre-generated) hash.
     *
     * Checks if a "plain-text password" is every time mapped to the
     * same "salted password hash" when using the same fixed salt.
     *
     * @test
     */
    public function checkPasswordReturnsTrueWithValidAlphaCharClassPasswordAndFixedHash()
    {
        $password = 'password';
        $saltedHashPassword = '$1$GNu9HdMt$RwkPb28pce4nXZfnplVZY/';
        self::assertTrue((new Md5PasswordHash())->checkPassword($password, $saltedHashPassword));
    }

    /**
     * Tests that authentication procedure fails with broken hash to compare to
     *
     * @test
     */
    public function checkPasswordReturnsFalseWithBrokenHash()
    {
        $password = 'password';
        $saltedHashPassword = '$1$GNu9HdMt$RwkPb28pce4nXZfnplVZY';
        self::assertFalse((new Md5PasswordHash())->checkPassword($password, $saltedHashPassword));
    }

    /**
     * Tests authentication procedure with alphabet characters.
     *
     * Checks if a "plain-text password" is every time mapped to the
     * same "salted password hash" when using the same salt.
     *
     * @test
     */
    public function checkPasswordReturnsTrueWithValidAlphaCharClassPassword()
    {
        $password = 'aEjOtY';
        $subject = new Md5PasswordHash();
        $saltedHashPassword = $subject->getHashedPassword($password);
        self::assertTrue($subject->checkPassword($password, $saltedHashPassword));
    }

    /**
     * Tests authentication procedure with numeric characters.
     *
     * Checks if a "plain-text password" is every time mapped to the
     * same "salted password hash" when using the same salt.
     *
     * @test
     */
    public function checkPasswordReturnsTrueWithValidNumericCharClassPassword()
    {
        $password = '01369';
        $subject = new Md5PasswordHash();
        $saltedHashPassword = $subject->getHashedPassword($password);
        self::assertTrue($subject->checkPassword($password, $saltedHashPassword));
    }

    /**
     * Tests authentication procedure with US-ASCII special characters.
     *
     * Checks if a "plain-text password" is every time mapped to the
     * same "salted password hash" when using the same salt.
     *
     * @test
     */
    public function checkPasswordReturnsTrueWithValidAsciiSpecialCharClassPassword()
    {
        $password = ' !"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~';
        $subject = new Md5PasswordHash();
        $saltedHashPassword = $subject->getHashedPassword($password);
        self::assertTrue($subject->checkPassword($password, $saltedHashPassword));
    }

    /**
     * Tests authentication procedure with latin1 special characters.
     *
     * Checks if a "plain-text password" is every time mapped to the
     * same "salted password hash" when using the same salt.
     *
     * @test
     */
    public function checkPasswordReturnsTrueWithValidLatin1SpecialCharClassPassword()
    {
        $password = '';
        for ($i = 160; $i <= 191; $i++) {
            $password .= chr($i);
        }
        $password .= chr(215) . chr(247);
        $subject = new Md5PasswordHash();
        $saltedHashPassword = $subject->getHashedPassword($password);
        self::assertTrue($subject->checkPassword($password, $saltedHashPassword));
    }

    /**
     * Tests authentication procedure with latin1 umlauts.
     *
     * Checks if a "plain-text password" is every time mapped to the
     * same "salted password hash" when using the same salt.
     *
     * @test
     */
    public function checkPasswordReturnsTrueWithValidLatin1UmlautCharClassPassword()
    {
        $password = '';
        for ($i = 192; $i <= 214; $i++) {
            $password .= chr($i);
        }
        for ($i = 216; $i <= 246; $i++) {
            $password .= chr($i);
        }
        for ($i = 248; $i <= 255; $i++) {
            $password .= chr($i);
        }
        $subject = new Md5PasswordHash();
        $saltedHashPassword = $subject->getHashedPassword($password);
        self::assertTrue($subject->checkPassword($password, $saltedHashPassword));
    }

    /**
     * @test
     */
    public function checkPasswordReturnsFalseWithNonValidPassword()
    {
        $password = 'password';
        $password1 = $password . 'INVALID';
        $subject = new Md5PasswordHash();
        $saltedHashPassword = $subject->getHashedPassword($password);
        self::assertFalse($subject->checkPassword($password1, $saltedHashPassword));
    }

    /**
     * @test
     */
    public function isHashUpdateNeededReturnsFalse()
    {
        $password = 'password';
        $subject = new Md5PasswordHash();
        $saltedHashPassword = $subject->getHashedPassword($password);
        self::assertFalse($subject->isHashUpdateNeeded($saltedHashPassword));
    }
}
