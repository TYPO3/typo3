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

use TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2idPasswordHash;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class Argon2idPasswordHashTest extends UnitTestCase
{
    /**
     * @var Argon2idPasswordHash
     */
    protected $subject;

    /**
     * Sets up the subject for this test case.
     *
     * @requires PHP 7.3
     */
    protected function setUp(): void
    {
        parent::setUp();
        $options = [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 2,
        ];
        $this->subject = new Argon2idPasswordHash($options);
    }

    /**
     * @test
     * @requires PHP 7.3
     */
    public function constructorThrowsExceptionIfMemoryCostIsTooLow()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1533899612);
        new Argon2idPasswordHash(['memory_cost' => 1]);
    }

    /**
     * @test
     * @requires PHP 7.3
     */
    public function constructorThrowsExceptionIfTimeCostIsTooLow()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1533899613);
        new Argon2idPasswordHash(['time_cost' => 1]);
    }

    /**
     * @test
     * @requires PHP 7.3
     */
    public function constructorThrowsExceptionIfThreadsIsTooLow()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1533899614);
        new Argon2idPasswordHash(['threads' => 0]);
    }

    /**
     * @test
     * @requires PHP 7.3
     */
    public function getHashedPasswordReturnsNullOnEmptyPassword()
    {
        self::assertNull($this->subject->getHashedPassword(''));
    }

    /**
     * @test
     * @requires PHP 7.3
     */
    public function getHashedPasswordReturnsString()
    {
        $hash = $this->subject->getHashedPassword('password');
        self::assertNotNull($hash);
        self::assertTrue(is_string($hash));
    }

    /**
     * @test
     * @requires PHP 7.3
     */
    public function isValidSaltedPwValidatesHastCreatedByGetHashedPassword()
    {
        $hash = $this->subject->getHashedPassword('password');
        self::assertTrue($this->subject->isValidSaltedPW($hash));
    }

    /**
     * Tests authentication procedure with alphabet characters.
     *
     * @test
     * @requires PHP 7.3
     */
    public function checkPasswordReturnsTrueForHashedPasswordWithValidAlphaCharClassPassword()
    {
        $password = 'aEjOtY';
        $hash = $this->subject->getHashedPassword($password);
        self::assertTrue($this->subject->checkPassword($password, $hash));
    }

    /**
     * Tests authentication procedure with numeric characters.
     *
     * @test
     * @requires PHP 7.3
     */
    public function checkPasswordReturnsTrueForHashedPasswordWithValidNumericCharClassPassword()
    {
        $password = '01369';
        $hash = $this->subject->getHashedPassword($password);
        self::assertTrue($this->subject->checkPassword($password, $hash));
    }

    /**
     * Tests authentication procedure with US-ASCII special characters.
     *
     * @test
     * @requires PHP 7.3
     */
    public function checkPasswordReturnsTrueForHashedPasswordWithValidAsciiSpecialCharClassPassword()
    {
        $password = ' !"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~';
        $hash = $this->subject->getHashedPassword($password);
        self::assertTrue($this->subject->checkPassword($password, $hash));
    }

    /**
     * Tests authentication procedure with latin1 special characters.
     *
     * @test
     * @requires PHP 7.3
     */
    public function checkPasswordReturnsTrueForHashedPasswordWithValidLatin1SpecialCharClassPassword()
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
     * @requires PHP 7.3
     */
    public function checkPasswordReturnsTrueForHashedPasswordWithValidLatin1UmlautCharClassPassword()
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
     * @requires PHP 7.3
     */
    public function checkPasswordReturnsTrueForHashedPasswordWithNonValidPassword()
    {
        $password = 'password';
        $password1 = $password . 'INVALID';
        $hash = $this->subject->getHashedPassword($password);
        self::assertFalse($this->subject->checkPassword($password1, $hash));
    }

    /**
     * @test
     * @requires PHP 7.3
     */
    public function isHashUpdateNeededReturnsFalseForJustGeneratedHash()
    {
        $password = 'password';
        $hash = $this->subject->getHashedPassword($password);
        self::assertFalse($this->subject->isHashUpdateNeeded($hash));
    }

    /**
     * @test
     * @requires PHP 7.3
     */
    public function isHashUpdateNeededReturnsTrueForHashGeneratedWithOldOptions()
    {
        $originalOptions = [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 2,
        ];
        $subject = new Argon2idPasswordHash($originalOptions);
        $hash = $subject->getHashedPassword('password');

        // Change $memoryCost
        $newOptions = $originalOptions;
        $newOptions['memory_cost'] = $newOptions['memory_cost'] + 1;
        $subject = new Argon2idPasswordHash($newOptions);
        self::assertTrue($subject->isHashUpdateNeeded($hash));

        // Change $timeCost
        $newOptions = $originalOptions;
        $newOptions['time_cost'] = $newOptions['time_cost'] + 1;
        $subject = new Argon2idPasswordHash($newOptions);
        self::assertTrue($subject->isHashUpdateNeeded($hash));

        // Change $threads
        $newOptions = $originalOptions;
        $newOptions['threads'] = $newOptions['threads'] + 1;
        $subject = new Argon2idPasswordHash($newOptions);
        self::assertTrue($subject->isHashUpdateNeeded($hash));
    }
}
