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
 * Testcase for SaltFactory
 * @since 2009-09-06
 */
class SaltFactoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * Keeps instance of object to test.
     *
     * @var \TYPO3\CMS\Saltedpasswords\Salt\AbstractSalt
     */
    protected $objectInstance = null;

    /**
     * Sets up the fixtures for this testcase.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->objectInstance = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance();
    }

    /**
     * @test
     */
    public function objectInstanceNotNull()
    {
        $this->assertNotNull($this->objectInstance);
    }

    /**
     * @test
     */
    public function objectInstanceExtendsAbstractClass()
    {
        $this->assertTrue(is_subclass_of($this->objectInstance, \TYPO3\CMS\Saltedpasswords\Salt\AbstractSalt::class));
    }

    /**
     * @test
     */
    public function objectInstanceImplementsInterface()
    {
        $this->assertTrue(method_exists($this->objectInstance, 'checkPassword'), 'Missing method checkPassword() from interface ' . \TYPO3\CMS\Saltedpasswords\Salt\SaltInterface::class . '.');
        $this->assertTrue(method_exists($this->objectInstance, 'isHashUpdateNeeded'), 'Missing method isHashUpdateNeeded() from interface ' . \TYPO3\CMS\Saltedpasswords\Salt\SaltInterface::class . '.');
        $this->assertTrue(method_exists($this->objectInstance, 'isValidSalt'), 'Missing method isValidSalt() from interface ' . \TYPO3\CMS\Saltedpasswords\Salt\SaltInterface::class . '.');
        $this->assertTrue(method_exists($this->objectInstance, 'isValidSaltedPW'), 'Missing method isValidSaltedPW() from interface ' . \TYPO3\CMS\Saltedpasswords\Salt\SaltInterface::class . '.');
        $this->assertTrue(method_exists($this->objectInstance, 'getHashedPassword'), 'Missing method getHashedPassword() from interface ' . \TYPO3\CMS\Saltedpasswords\Salt\SaltInterface::class . '.');
        $this->assertTrue(method_exists($this->objectInstance, 'getSaltLength'), 'Missing method getSaltLength() from interface ' . \TYPO3\CMS\Saltedpasswords\Salt\SaltInterface::class . '.');
    }

    /**
     * @test
     */
    public function base64EncodeReturnsProperLength()
    {
        // 3 Bytes should result in a 6 char length base64 encoded string
        // used for MD5 and PHPass salted hashing
        $byteLength = 3;
        $reqLengthBase64 = (int)ceil($byteLength * 8 / 6);
        $randomBytes = \TYPO3\CMS\Core\Utility\GeneralUtility::generateRandomBytes($byteLength);
        $this->assertTrue(strlen($this->objectInstance->base64Encode($randomBytes, $byteLength)) == $reqLengthBase64);
        // 16 Bytes should result in a 22 char length base64 encoded string
        // used for Blowfish salted hashing
        $byteLength = 16;
        $reqLengthBase64 = (int)ceil($byteLength * 8 / 6);
        $randomBytes = \TYPO3\CMS\Core\Utility\GeneralUtility::generateRandomBytes($byteLength);
        $this->assertTrue(strlen($this->objectInstance->base64Encode($randomBytes, $byteLength)) == $reqLengthBase64);
    }

    /**
     * @test
     */
    public function objectInstanceForMD5Salts()
    {
        $saltMD5 = '$1$rasmusle$rISCgZzpwk3UhDidwXvin0';
        $this->objectInstance = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance($saltMD5);
        $this->assertTrue(get_class($this->objectInstance) == \TYPO3\CMS\Saltedpasswords\Salt\Md5Salt::class || is_subclass_of($this->objectInstance, \TYPO3\CMS\Saltedpasswords\Salt\Md5Salt::class));
    }

    /**
     * @test
     */
    public function objectInstanceForBlowfishSalts()
    {
        $saltBlowfish = '$2a$07$abcdefghijklmnopqrstuuIdQV69PAxWYTgmnoGpe0Sk47GNS/9ZW';
        $this->objectInstance = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance($saltBlowfish);
        $this->assertTrue(get_class($this->objectInstance) == \TYPO3\CMS\Saltedpasswords\Salt\BlowfishSalt::class || is_subclass_of($this->objectInstance, \TYPO3\CMS\Saltedpasswords\Salt\BlowfishSalt::class));
    }

    /**
     * @test
     */
    public function objectInstanceForPhpassSalts()
    {
        $saltPhpass = '$P$CWF13LlG/0UcAQFUjnnS4LOqyRW43c.';
        $this->objectInstance = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance($saltPhpass);
        $this->assertTrue(get_class($this->objectInstance) == \TYPO3\CMS\Saltedpasswords\Salt\PhpassSalt::class || is_subclass_of($this->objectInstance, \TYPO3\CMS\Saltedpasswords\Salt\PhpassSalt::class));
    }

    /**
     * @test
     */
    public function resettingFactoryInstanceSucceeds()
    {
        $defaultClassNameToUse = \TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility::getDefaultSaltingHashingMethod();
        if ($defaultClassNameToUse == \TYPO3\CMS\Saltedpasswords\Salt\Md5Salt::class) {
            $saltedPW = '$P$CWF13LlG/0UcAQFUjnnS4LOqyRW43c.';
        } else {
            $saltedPW = '$1$rasmusle$rISCgZzpwk3UhDidwXvin0';
        }
        $this->objectInstance = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance($saltedPW);
        // resetting
        $this->objectInstance = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance(null);
        $this->assertTrue(get_class($this->objectInstance) == $defaultClassNameToUse || is_subclass_of($this->objectInstance, $defaultClassNameToUse));
    }
}
