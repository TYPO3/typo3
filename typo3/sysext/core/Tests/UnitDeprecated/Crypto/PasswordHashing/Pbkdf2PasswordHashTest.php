<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Tests\UnitDeprecated\Crypto\PasswordHashing;

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

use TYPO3\CMS\Core\Crypto\PasswordHashing\Pbkdf2PasswordHash;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class Pbkdf2PasswordHashTest extends UnitTestCase
{
    /**
     * Keeps instance of object to test.
     *
     * @var Pbkdf2PasswordHash
     */
    protected $subject;

    /**
     * Sets up the fixtures for this testcase.
     */
    protected function setUp()
    {
        $this->subject = new Pbkdf2PasswordHash(['hash_count' => 1001]);
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
    public function createdSaltedHashOfProperStructureForCustomSaltWithoutSetting()
    {
        $password = 'password';
        // custom salt without setting
        $randomBytes = (new Random())->generateRandomBytes($this->subject->getSaltLength());
        $salt = $this->subject->base64Encode($randomBytes, $this->subject->getSaltLength());
        $this->assertTrue($this->subject->isValidSalt($salt));
        $saltedHashPassword = $this->subject->getHashedPassword($password, '6400$' . $salt);
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
    public function modifiedHashCount()
    {
        $hashCount = $this->subject->getHashCount();
        $this->subject->setMaxHashCount($hashCount + 1);
        $this->subject->setHashCount($hashCount + 1);
        $this->assertTrue($this->subject->getHashCount() > $hashCount);
        $this->subject->setMinHashCount($hashCount - 1);
        $this->subject->setHashCount($hashCount - 1);
        $this->assertTrue($this->subject->getHashCount() < $hashCount);
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
    }
}
