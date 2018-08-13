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

use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class PasswordHashFactoryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function abstractComposedSaltBase64EncodeReturnsProperLength()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['saltedpasswords'] = [
            'BE' => [
                'saltedPWHashingMethod' => \TYPO3\CMS\Core\Crypto\PasswordHashing\Pbkdf2PasswordHash::class,
            ],
            'FE' => [
                'saltedPWHashingMethod' => \TYPO3\CMS\Core\Crypto\PasswordHashing\Pbkdf2PasswordHash::class,
            ],
        ];

        // set up an instance that extends AbstractComposedSalt first
        $saltPbkdf2 = '$pbkdf2-sha256$6400$0ZrzXitFSGltTQnBWOsdAw$Y11AchqV4b0sUisdZd0Xr97KWoymNE0LNNrnEgY4H9M';
        $objectInstance = PasswordHashFactory::getSaltingInstance($saltPbkdf2);

        // 3 Bytes should result in a 6 char length base64 encoded string
        // used for MD5 and PHPass salted hashing
        $byteLength = 3;
        $reqLengthBase64 = (int)ceil($byteLength * 8 / 6);
        $randomBytes = (new Random())->generateRandomBytes($byteLength);
        $this->assertTrue(strlen($objectInstance->base64Encode($randomBytes, $byteLength)) == $reqLengthBase64);
        // 16 Bytes should result in a 22 char length base64 encoded string
        // used for Blowfish salted hashing
        $byteLength = 16;
        $reqLengthBase64 = (int)ceil($byteLength * 8 / 6);
        $randomBytes = (new Random())->generateRandomBytes($byteLength);
        $this->assertTrue(strlen($objectInstance->base64Encode($randomBytes, $byteLength)) == $reqLengthBase64);
    }

    /**
     * @test
     */
    public function objectInstanceForPhpPasswordHashBcryptSalts()
    {
        $saltBcrypt = '$2y$12$Tz.al0seuEgRt61u0bzqAOWu67PgG2ThG25oATJJ0oS5KLCPCgBOe';
        $objectInstance = PasswordHashFactory::getSaltingInstance($saltBcrypt);
        $this->assertInstanceOf(\TYPO3\CMS\Core\Crypto\PasswordHashing\BcryptPasswordHash::class, $objectInstance);
    }

    /**
     * @test
     */
    public function objectInstanceForPhpPasswordHashArgon2iSalts()
    {
        $saltArgon2i = '$argon2i$v=19$m=8,t=1,p=1$djZiNkdEa3lOZm1SSmZsdQ$9iiRjpLZAT7kfHwS1xU9cqSU7+nXy275qpB/eKjI1ig';
        $objectInstance = PasswordHashFactory::getSaltingInstance($saltArgon2i);
        $this->assertInstanceOf(\TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2iPasswordHash::class, $objectInstance);
    }

    /**
     * @test
     */
    public function resettingFactoryInstanceSucceeds()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['saltedpasswords'] = [
            'BE' => [
                'saltedPWHashingMethod' => \TYPO3\CMS\Core\Crypto\PasswordHashing\Pbkdf2PasswordHash::class,
            ],
            'FE' => [
                'saltedPWHashingMethod' => \TYPO3\CMS\Core\Crypto\PasswordHashing\Pbkdf2PasswordHash::class,
            ],
        ];

        $defaultClassNameToUse = \TYPO3\CMS\Core\Crypto\PasswordHashing\SaltedPasswordsUtility::getDefaultSaltingHashingMethod();
        if ($defaultClassNameToUse == \TYPO3\CMS\Core\Crypto\PasswordHashing\Md5PasswordHash::class) {
            $saltedPW = '$P$CWF13LlG/0UcAQFUjnnS4LOqyRW43c.';
        } else {
            $saltedPW = '$1$rasmusle$rISCgZzpwk3UhDidwXvin0';
        }
        $objectInstance = PasswordHashFactory::getSaltingInstance($saltedPW);
        // resetting
        $objectInstance = PasswordHashFactory::getSaltingInstance(null);
        $this->assertTrue(get_class($objectInstance) == $defaultClassNameToUse || is_subclass_of($objectInstance, $defaultClassNameToUse));
    }
}
