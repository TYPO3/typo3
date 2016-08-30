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

/**
 * Testcase for BlowfishSalt
 */
class BlowfishSaltTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * Keeps instance of object to test.
     *
     * @var \TYPO3\CMS\Saltedpasswords\Salt\BlowfishSalt
     */
    protected $objectInstance = null;

    /**
     * Sets up the fixtures for this testcase.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->objectInstance = $this->getMock(\TYPO3\CMS\Saltedpasswords\Salt\BlowfishSalt::class, ['dummy']);
    }

    /**
     * Marks tests as skipped if the blowfish method is not available.
     *
     * @return void
     */
    protected function skipTestIfBlowfishIsNotAvailable()
    {
        if (!CRYPT_BLOWFISH) {
            $this->markTestSkipped('Blowfish is not supported on your platform.');
        }
    }

    /**
     * @test
     */
    public function hasCorrectBaseClass()
    {
        $hasCorrectBaseClass = get_class($this->objectInstance) === \TYPO3\CMS\Saltedpasswords\Salt\BlowfishSalt::class;
        // XCLASS ?
        if (!$hasCorrectBaseClass && false != get_parent_class($this->objectInstance)) {
            $hasCorrectBaseClass = is_subclass_of($this->objectInstance, \TYPO3\CMS\Saltedpasswords\Salt\BlowfishSalt::class);
        }
        $this->assertTrue($hasCorrectBaseClass);
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
    public function emptyPasswordResultsInNullSaltedPassword()
    {
        $password = '';
        $this->assertNull($this->objectInstance->getHashedPassword($password));
    }

    /**
     * @test
     */
    public function nonEmptyPasswordResultsInNonNullSaltedPassword()
    {
        $this->skipTestIfBlowfishIsNotAvailable();
        $password = 'a';
        $this->assertNotNull($this->objectInstance->getHashedPassword($password));
    }

    /**
     * @test
     */
    public function createdSaltedHashOfProperStructure()
    {
        $this->skipTestIfBlowfishIsNotAvailable();
        $password = 'password';
        $saltedHashPassword = $this->objectInstance->getHashedPassword($password);
        $this->assertTrue($this->objectInstance->isValidSaltedPW($saltedHashPassword));
    }

    /**
     * @test
     */
    public function createdSaltedHashOfProperStructureForCustomSaltWithoutSetting()
    {
        $this->skipTestIfBlowfishIsNotAvailable();
        $password = 'password';
        // custom salt without setting
        $randomBytes = \TYPO3\CMS\Core\Utility\GeneralUtility::generateRandomBytes($this->objectInstance->getSaltLength());
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
        $this->skipTestIfBlowfishIsNotAvailable();
        $password = 'password';
        $minHashCount = $this->objectInstance->getMinHashCount();
        $this->objectInstance->setHashCount($minHashCount);
        $saltedHashPassword = $this->objectInstance->getHashedPassword($password);
        $this->assertTrue($this->objectInstance->isValidSaltedPW($saltedHashPassword));
        // reset hashcount
        $this->objectInstance->setHashCount(null);
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
        $this->skipTestIfBlowfishIsNotAvailable();
        $password = 'aEjOtY';
        $saltedHashPassword = $this->objectInstance->getHashedPassword($password);
        $this->assertTrue($this->objectInstance->checkPassword($password, $saltedHashPassword));
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
        $this->skipTestIfBlowfishIsNotAvailable();
        $password = '01369';
        $saltedHashPassword = $this->objectInstance->getHashedPassword($password);
        $this->assertTrue($this->objectInstance->checkPassword($password, $saltedHashPassword));
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
        $this->skipTestIfBlowfishIsNotAvailable();
        $password = ' !"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~';
        $saltedHashPassword = $this->objectInstance->getHashedPassword($password);
        $this->assertTrue($this->objectInstance->checkPassword($password, $saltedHashPassword));
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
        $this->skipTestIfBlowfishIsNotAvailable();
        $password = '';
        for ($i = 160; $i <= 191; $i++) {
            $password .= chr($i);
        }
        $password .= chr(215) . chr(247);
        $saltedHashPassword = $this->objectInstance->getHashedPassword($password);
        $this->assertTrue($this->objectInstance->checkPassword($password, $saltedHashPassword));
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
        $this->skipTestIfBlowfishIsNotAvailable();
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
        $saltedHashPassword = $this->objectInstance->getHashedPassword($password);
        $this->assertTrue($this->objectInstance->checkPassword($password, $saltedHashPassword));
    }

    /**
     * @test
     */
    public function authenticationWithNonValidPassword()
    {
        $this->skipTestIfBlowfishIsNotAvailable();
        $password = 'password';
        $password1 = $password . 'INVALID';
        $saltedHashPassword = $this->objectInstance->getHashedPassword($password);
        $this->assertFalse($this->objectInstance->checkPassword($password1, $saltedHashPassword));
    }

    /**
     * @test
     */
    public function passwordVariationsResultInDifferentHashes()
    {
        $this->skipTestIfBlowfishIsNotAvailable();
        $pad = 'a';
        $password = '';
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
    public function modifiedMinHashCount()
    {
        $minHashCount = $this->objectInstance->getMinHashCount();
        $this->objectInstance->setMinHashCount($minHashCount - 1);
        $this->assertTrue($this->objectInstance->getMinHashCount() < $minHashCount);
        $this->objectInstance->setMinHashCount($minHashCount + 1);
        $this->assertTrue($this->objectInstance->getMinHashCount() > $minHashCount);
    }

    /**
     * @test
     */
    public function modifiedMaxHashCount()
    {
        $maxHashCount = $this->objectInstance->getMaxHashCount();
        $this->objectInstance->setMaxHashCount($maxHashCount + 1);
        $this->assertTrue($this->objectInstance->getMaxHashCount() > $maxHashCount);
        $this->objectInstance->setMaxHashCount($maxHashCount - 1);
        $this->assertTrue($this->objectInstance->getMaxHashCount() < $maxHashCount);
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
    public function updateNecessityForValidSaltedPassword()
    {
        $this->skipTestIfBlowfishIsNotAvailable();
        $password = 'password';
        $saltedHashPassword = $this->objectInstance->getHashedPassword($password);
        $this->assertFalse($this->objectInstance->isHashUpdateNeeded($saltedHashPassword));
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
        $this->skipTestIfBlowfishIsNotAvailable();
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
