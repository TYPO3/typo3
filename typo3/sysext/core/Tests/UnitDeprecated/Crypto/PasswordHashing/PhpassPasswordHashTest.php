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

use TYPO3\CMS\Core\Crypto\PasswordHashing\PhpassPasswordHash;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class PhpassPasswordHashTest extends UnitTestCase
{
    /**
     * Keeps instance of object to test.
     *
     * @var PhpassPasswordHash
     */
    protected $objectInstance;

    /**
     * Sets up the fixtures for this testcase.
     */
    protected function setUp()
    {
        $this->objectInstance = $this->getMockBuilder(PhpassPasswordHash::class)
            ->setMethods(['dummy'])
            ->getMock();
    }

    /**
     * @test
     */
    public function nonZeroSaltLength()
    {
        $this->assertTrue($this->objectInstance->getSaltLength() > 0);
    }

    /**
     * @test
     */
    public function createdSaltedHashOfProperStructureForCustomSaltWithoutSetting()
    {
        $password = 'password';
        // custom salt without setting
        $randomBytes = (new Random())->generateRandomBytes($this->objectInstance->getSaltLength());
        $salt = $this->objectInstance->base64Encode($randomBytes, $this->objectInstance->getSaltLength());
        $this->assertTrue($this->objectInstance->isValidSalt($salt));
        $saltedHashPassword = $this->objectInstance->getHashedPassword($password, $salt);
        $this->assertTrue($this->objectInstance->isValidSaltedPW($saltedHashPassword));
    }

    /**
     * @test
     */
    public function createdSaltedHashOfProperStructureForMinimumHashCount()
    {
        $password = 'password';
        $minHashCount = $this->objectInstance->getMinHashCount();
        $this->objectInstance->setHashCount($minHashCount);
        $saltedHashPassword = $this->objectInstance->getHashedPassword($password);
        $this->assertTrue($this->objectInstance->isValidSaltedPW($saltedHashPassword));
        // reset hashcount
        $this->objectInstance->setHashCount(null);
    }

    /**
     * @test
     */
    public function passwordVariationsResultInDifferentHashes()
    {
        $pad = 'a';
        $criticalPwLength = 0;
        // We're using a constant salt.
        $saltedHashPasswordCurrent = $salt = $this->objectInstance->getHashedPassword($pad);
        for ($i = 0; $i <= 128; $i += 8) {
            $password = str_repeat($pad, max($i, 1));
            $saltedHashPasswordPrevious = $saltedHashPasswordCurrent;
            $saltedHashPasswordCurrent = $this->objectInstance->getHashedPassword($password, $salt);
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
        $hashCount = $this->objectInstance->getHashCount();
        $this->objectInstance->setMaxHashCount($hashCount + 1);
        $this->objectInstance->setHashCount($hashCount + 1);
        $this->assertTrue($this->objectInstance->getHashCount() > $hashCount);
        $this->objectInstance->setMinHashCount($hashCount - 1);
        $this->objectInstance->setHashCount($hashCount - 1);
        $this->assertTrue($this->objectInstance->getHashCount() < $hashCount);
        // reset hashcount
        $this->objectInstance->setHashCount(null);
    }

    /**
     * @test
     */
    public function updateNecessityForIncreasedHashcount()
    {
        $password = 'password';
        $saltedHashPassword = $this->objectInstance->getHashedPassword($password);
        $increasedHashCount = $this->objectInstance->getHashCount() + 1;
        $this->objectInstance->setMaxHashCount($increasedHashCount);
        $this->objectInstance->setHashCount($increasedHashCount);
        $this->assertTrue($this->objectInstance->isHashUpdateNeeded($saltedHashPassword));
        // reset hashcount
        $this->objectInstance->setHashCount(null);
    }

    /**
     * @test
     */
    public function updateNecessityForDecreasedHashcount()
    {
        $password = 'password';
        $saltedHashPassword = $this->objectInstance->getHashedPassword($password);
        $decreasedHashCount = $this->objectInstance->getHashCount() - 1;
        $this->objectInstance->setMinHashCount($decreasedHashCount);
        $this->objectInstance->setHashCount($decreasedHashCount);
        $this->assertFalse($this->objectInstance->isHashUpdateNeeded($saltedHashPassword));
        // reset hashcount
        $this->objectInstance->setHashCount(null);
    }
}
