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

use TYPO3\CMS\Core\Crypto\PasswordHashing\Md5PasswordHash;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class Md5PasswordHashTest extends UnitTestCase
{
    /**
     * Keeps instance of object to test.
     *
     * @var Md5PasswordHash
     */
    protected $objectInstance;

    /**
     * Sets up the fixtures for this testcase.
     */
    protected function setUp()
    {
        if (!CRYPT_MD5) {
            $this->markTestSkipped('Blowfish is not supported on your platform.');
        }
        $this->objectInstance = $this->getMockBuilder(Md5PasswordHash::class)
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
}
