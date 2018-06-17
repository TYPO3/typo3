<?php
namespace TYPO3\CMS\Saltedpasswords\Tests\Unit\Salt;

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

use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Saltedpasswords\Salt\Pbkdf2Salt;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class Pbkdf2SaltTest extends UnitTestCase
{
    /**
     * Keeps instance of object to test.
     *
     * @var Pbkdf2Salt
     */
    protected $subject;

    /**
     * Sets up the fixtures for this testcase.
     */
    protected function setUp()
    {
        $this->subject = new Pbkdf2Salt();
        // Speed up the tests by reducing the iteration count
        $this->subject->setHashCount(1000);
        $this->subject->setMinHashCount(1000);
        $this->subject->setMaxHashCount(10000000);
    }

    /**
     * @test
     */
    public function nonZeroSaltLength()
    {
        $this->assertTrue($this->subject->getSaltLength() > 0);
    }

    /**
     * @test
     */
    public function emptyPasswordResultsInNullSaltedPassword()
    {
        $password = '';
        $this->assertNull($this->subject->getHashedPassword($password));
    }

    /**
     * @test
     */
    public function nonEmptyPasswordResultsInNonNullSaltedPassword()
    {
        $password = 'a';
        $this->assertNotNull($this->subject->getHashedPassword($password));
    }

    /**
     * @test
     */
    public function createdSaltedHashOfProperStructure()
    {
        $password = 'password';
        $saltedHashPassword = $this->subject->getHashedPassword($password);
        $this->assertTrue($this->subject->isValidSaltedPW($saltedHashPassword));
    }

    /**
     * @test
     */
    public function createdSaltedHashOfProperStructureForCustomSaltWithoutSetting()
    {
        $password = 'password';
        // custom salt without setting
        $randomBytes = (new Random())->generateRandomBytes($this->subject->getSaltLength());
        $salt = $this->subject->base64Encode($randomBytes, $this->subject->getSaltLength());
        $this->assertTrue($this->subject->isValidSalt($salt));
        $saltedHashPassword = $this->subject->getHashedPassword($password, $salt);
        $this->assertTrue($this->subject->isValidSaltedPW($saltedHashPassword));
    }

    /**
     * @test
     */
    public function createdSaltedHashOfProperStructureForMinimumHashCount()
    {
        $password = 'password';
        $minHashCount = $this->subject->getMinHashCount();
        $this->subject->setHashCount($minHashCount);
        $saltedHashPassword = $this->subject->getHashedPassword($password);
        $this->assertTrue($this->subject->isValidSaltedPW($saltedHashPassword));
        // reset hashcount
        $this->subject->setHashCount(null);
    }

    /**
     * Tests authentication procedure with fixed password and fixed (pre-generated) hash.
     *
     * Checks if a "plain-text password" is every time mapped to the
     * same "salted password hash" when using the same fixed salt.
     *
     * @test
     */
    public function authenticationWithValidAlphaCharClassPasswordAndFixedHash()
    {
        $password = 'password';
        $saltedHashPassword = '$pbkdf2-sha256$1000$woPhT0yoWm3AXJXSjuxJ3w$iZ6EvTulMqXlzr0NO8z5EyrklFcJk5Uw2Fqje68FfaQ';
        $this->assertTrue($this->subject->checkPassword($password, $saltedHashPassword));
    }

    /**
     * Tests that authentication procedure fails with broken hash to compare to
     *
     * @test
     */
    public function authenticationFailsWithBrokenHash()
    {
        $password = 'password';
        $saltedHashPassword = '$pbkdf2-sha256$1000$woPhT0yoWm3AXJXSjuxJ3w$iZ6EvTulMqXlzr0NO8z5EyrklFcJk5Uw2Fqje68Ffa';
        $this->assertFalse($this->subject->checkPassword($password, $saltedHashPassword));
    }

    /**
     * Tests authentication procedure with alphabet characters.
     *
     * Checks if a "plain-text password" is every time mapped to the
     * same "salted password hash" when using the same salt.
     *
     * @test
     */
    public function authenticationWithValidAlphaCharClassPassword()
    {
        $password = 'aEjOtY';
        $saltedHashPassword = $this->subject->getHashedPassword($password);
        $this->assertTrue($this->subject->checkPassword($password, $saltedHashPassword));
    }

    /**
     * Tests authentication procedure with numeric characters.
     *
     * Checks if a "plain-text password" is every time mapped to the
     * same "salted password hash" when using the same salt.
     *
     * @test
     */
    public function authenticationWithValidNumericCharClassPassword()
    {
        $password = '01369';
        $saltedHashPassword = $this->subject->getHashedPassword($password);
        $this->assertTrue($this->subject->checkPassword($password, $saltedHashPassword));
    }

    /**
     * Tests authentication procedure with US-ASCII special characters.
     *
     * Checks if a "plain-text password" is every time mapped to the
     * same "salted password hash" when using the same salt.
     *
     * @test
     */
    public function authenticationWithValidAsciiSpecialCharClassPassword()
    {
        $password = ' !"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~';
        $saltedHashPassword = $this->subject->getHashedPassword($password);
        $this->assertTrue($this->subject->checkPassword($password, $saltedHashPassword));
    }

    /**
     * Tests authentication procedure with latin1 special characters.
     *
     * Checks if a "plain-text password" is every time mapped to the
     * same "salted password hash" when using the same salt.
     *
     * @test
     */
    public function authenticationWithValidLatin1SpecialCharClassPassword()
    {
        $password = '';
        for ($i = 160; $i <= 191; $i++) {
            $password .= chr($i);
        }
        $password .= chr(215) . chr(247);
        $saltedHashPassword = $this->subject->getHashedPassword($password);
        $this->assertTrue($this->subject->checkPassword($password, $saltedHashPassword));
    }

    /**
     * Tests authentication procedure with latin1 umlauts.
     *
     * Checks if a "plain-text password" is every time mapped to the
     * same "salted password hash" when using the same salt.
     *
     * @test
     */
    public function authenticationWithValidLatin1UmlautCharClassPassword()
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
        $saltedHashPassword = $this->subject->getHashedPassword($password);
        $this->assertTrue($this->subject->checkPassword($password, $saltedHashPassword));
    }

    /**
     * @test
     */
    public function authenticationWithNonValidPassword()
    {
        $password = 'password';
        $password1 = $password . 'INVALID';
        $saltedHashPassword = $this->subject->getHashedPassword($password);
        $this->assertFalse($this->subject->checkPassword($password1, $saltedHashPassword));
    }

    /**
     * @test
     */
    public function passwordVariationsResultInDifferentHashes()
    {
        $pad = 'a';
        $criticalPwLength = 0;
        // We're using a constant salt.
        $saltedHashPasswordCurrent = $salt = $this->subject->getHashedPassword($pad);
        for ($i = 0; $i <= 128; $i += 8) {
            $password = str_repeat($pad, max($i, 1));
            $saltedHashPasswordPrevious = $saltedHashPasswordCurrent;
            $saltedHashPasswordCurrent = $this->subject->getHashedPassword($password, $salt);
            if ($i > 0 && $saltedHashPasswordPrevious === $saltedHashPasswordCurrent) {
                $criticalPwLength = $i;
                break;
            }
        }
        $this->assertTrue($criticalPwLength == 0 || $criticalPwLength > 32, 'Duplicates of hashed passwords with plaintext password of length ' . $criticalPwLength . '+.');
    }

    /**
     * @test
     */
    public function modifiedMinHashCount()
    {
        $minHashCount = $this->subject->getMinHashCount();
        $this->subject->setMinHashCount($minHashCount - 1);
        $this->assertTrue($this->subject->getMinHashCount() < $minHashCount);
        $this->subject->setMinHashCount($minHashCount + 1);
        $this->assertTrue($this->subject->getMinHashCount() > $minHashCount);
    }

    /**
     * @test
     */
    public function modifiedMaxHashCount()
    {
        $maxHashCount = $this->subject->getMaxHashCount();
        $this->subject->setMaxHashCount($maxHashCount + 1);
        $this->assertTrue($this->subject->getMaxHashCount() > $maxHashCount);
        $this->subject->setMaxHashCount($maxHashCount - 1);
        $this->assertTrue($this->subject->getMaxHashCount() < $maxHashCount);
    }

    /**
     * @test
     */
    public function modifiedHashCount()
    {
        $hashCount = $this->subject->getHashCount();
        $this->subject->setMaxHashCount($hashCount + 1);
        $this->subject->setHashCount($hashCount + 1);
        $this->assertTrue($this->subject->getHashCount() > $hashCount);
        $this->subject->setMinHashCount($hashCount - 1);
        $this->subject->setHashCount($hashCount - 1);
        $this->assertTrue($this->subject->getHashCount() < $hashCount);
        // reset hashcount
        $this->subject->setHashCount(null);
    }

    /**
     * @test
     */
    public function updateNecessityForValidSaltedPassword()
    {
        $password = 'password';
        $saltedHashPassword = $this->subject->getHashedPassword($password);
        $this->assertFalse($this->subject->isHashUpdateNeeded($saltedHashPassword));
    }

    /**
     * @test
     */
    public function updateNecessityForIncreasedHashcount()
    {
        $password = 'password';
        $saltedHashPassword = $this->subject->getHashedPassword($password);
        $increasedHashCount = $this->subject->getHashCount() + 1;
        $this->subject->setMaxHashCount($increasedHashCount);
        $this->subject->setHashCount($increasedHashCount);
        $this->assertTrue($this->subject->isHashUpdateNeeded($saltedHashPassword));
        // reset hashcount
        $this->subject->setHashCount(null);
    }

    /**
     * @test
     */
    public function updateNecessityForDecreasedHashcount()
    {
        $password = 'password';
        $saltedHashPassword = $this->subject->getHashedPassword($password);
        $decreasedHashCount = $this->subject->getHashCount() - 1;
        $this->subject->setMinHashCount($decreasedHashCount);
        $this->subject->setHashCount($decreasedHashCount);
        $this->assertFalse($this->subject->isHashUpdateNeeded($saltedHashPassword));
        // reset hashcount
        $this->subject->setHashCount(null);
    }

    /**
     * @test
     */
    public function isCompatibleWithPythonPasslibHashes()
    {
        $passlibSaltedHash= '$pbkdf2-sha256$6400$.6UI/S.nXIk8jcbdHx3Fhg$98jZicV16ODfEsEZeYPGHU3kbrUrvUEXOPimVSQDD44';
        $saltedHashPassword = $this->subject->getHashedPassword('password', $passlibSaltedHash);

        $this->assertSame($passlibSaltedHash, $saltedHashPassword);
    }
}
