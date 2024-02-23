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
use TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2iPasswordHash;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class Argon2iPasswordHashTest extends UnitTestCase
{
    protected ?Argon2iPasswordHash $subject;

    /**
     * Sets up the subject for this test case.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $options = [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 1,
        ];
        $this->subject = new Argon2iPasswordHash($options);
    }

    #[Test]
    public function constructorThrowsExceptionIfMemoryCostIsTooLow(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1533899612);
        new Argon2iPasswordHash(['memory_cost' => 1]);
    }

    #[Test]
    public function constructorThrowsExceptionIfTimeCostIsTooLow(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1533899613);
        new Argon2iPasswordHash(['time_cost' => 1]);
    }

    #[Test]
    public function getHashedPasswordReturnsNullOnEmptyPassword(): void
    {
        self::assertNull($this->subject->getHashedPassword(''));
    }

    #[Test]
    public function getHashedPasswordReturnsString(): void
    {
        $hash = $this->subject->getHashedPassword('password');
        self::assertNotNull($hash);
        self::assertIsString($hash);
    }

    #[Test]
    public function isValidSaltedPwValidatesHastCreatedByGetHashedPassword(): void
    {
        $hash = $this->subject->getHashedPassword('password');
        self::assertTrue($this->subject->isValidSaltedPW($hash));
    }

    /**
     * Tests authentication procedure with alphabet characters.
     */
    #[Test]
    public function checkPasswordReturnsTrueForHashedPasswordWithValidAlphaCharClassPassword(): void
    {
        $password = 'aEjOtY';
        $hash = $this->subject->getHashedPassword($password);
        self::assertTrue($this->subject->checkPassword($password, $hash));
    }

    /**
     * Tests authentication procedure with numeric characters.
     */
    #[Test]
    public function checkPasswordReturnsTrueForHashedPasswordWithValidNumericCharClassPassword(): void
    {
        $password = '01369';
        $hash = $this->subject->getHashedPassword($password);
        self::assertTrue($this->subject->checkPassword($password, $hash));
    }

    /**
     * Tests authentication procedure with US-ASCII special characters.
     */
    #[Test]
    public function checkPasswordReturnsTrueForHashedPasswordWithValidAsciiSpecialCharClassPassword(): void
    {
        $password = ' !"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~';
        $hash = $this->subject->getHashedPassword($password);
        self::assertTrue($this->subject->checkPassword($password, $hash));
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
        $hash = $this->subject->getHashedPassword($password);
        self::assertTrue($this->subject->checkPassword($password, $hash));
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
        $hash = $this->subject->getHashedPassword($password);
        self::assertTrue($this->subject->checkPassword($password, $hash));
    }

    #[Test]
    public function checkPasswordReturnsTrueForHashedPasswordWithNonValidPassword(): void
    {
        $password = 'password';
        $password1 = $password . 'INVALID';
        $hash = $this->subject->getHashedPassword($password);
        self::assertFalse($this->subject->checkPassword($password1, $hash));
    }

    #[Test]
    public function isHashUpdateNeededReturnsFalseForJustGeneratedHash(): void
    {
        $password = 'password';
        $hash = $this->subject->getHashedPassword($password);
        self::assertFalse($this->subject->isHashUpdateNeeded($hash));
    }

    #[Test]
    public function isHashUpdateNeededReturnsTrueForHashGeneratedWithOldOptions(): void
    {
        $originalOptions = [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 2,
        ];
        $subject = new Argon2iPasswordHash($originalOptions);
        $hash = $subject->getHashedPassword('password');

        // Change $memoryCost
        $newOptions = $originalOptions;
        $newOptions['memory_cost'] = $newOptions['memory_cost'] + 1;
        $subject = new Argon2iPasswordHash($newOptions);
        self::assertTrue($subject->isHashUpdateNeeded($hash));

        // Change $timeCost
        $newOptions = $originalOptions;
        $newOptions['time_cost'] = $newOptions['time_cost'] + 1;
        $subject = new Argon2iPasswordHash($newOptions);
        self::assertTrue($subject->isHashUpdateNeeded($hash));

        // Change $threads
        // Changing $threads does nothing with libsodium, so skip that.
        if (!extension_loaded('sodium')) {
            $newOptions = $originalOptions;
            $newOptions['threads'] = $newOptions['threads'] + 1;
            $subject = new Argon2iPasswordHash($newOptions);
            self::assertTrue($subject->isHashUpdateNeeded($hash));
        }
    }
}
