<?php
namespace TYPO3\CMS\Rsaauth\Tests\Unit;

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

use TYPO3\CMS\Rsaauth\Keypair;

/**
 * Test case.
 */
class KeypairTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var Keypair
     */
    protected $subject = null;

    protected function setUp()
    {
        $this->subject = new Keypair();
    }

    /**
     * @test
     */
    public function classIsSingleton()
    {
        $this->assertInstanceOf(
            \TYPO3\CMS\Core\SingletonInterface::class,
            $this->subject
        );
    }

    /**
     * @test
     */
    public function getExponentInitiallyReturnsZero()
    {
        $this->assertSame(
            0,
            $this->subject->getExponent()
        );
    }

    /**
     * @test
     */
    public function setExponentSetsExponent()
    {
        $this->subject->setExponent(123456);

        $this->assertSame(
            123456,
            $this->subject->getExponent()
        );
    }

    /**
     * @test
     *
     * @expectedException \BadMethodCallException
     */
    public function setExponentCalledTwoTimesThrowsException()
    {
        $this->subject->setExponent(123456);
        $this->subject->setExponent(123456);
    }

    /**
     * @test
     */
    public function getPrivateKeyInitiallyReturnsEmptyString()
    {
        $this->assertSame(
            '',
            $this->subject->getPrivateKey()
        );
    }

    /**
     * @test
     */
    public function setPrivateKeySetsPrivateKey()
    {
        $this->subject->setPrivateKey('foo bar');

        $this->assertSame(
            'foo bar',
            $this->subject->getPrivateKey()
        );
    }

    /**
     * @test
     *
     * @expectedException \BadMethodCallException
     */
    public function setPrivateKeyCalledTwoTimesThrowsException()
    {
        $this->subject->setPrivateKey('foo');
        $this->subject->setPrivateKey('foo');
    }

    /**
     * @test
     */
    public function getPublicKeyModulusInitiallyReturnsZero()
    {
        $this->assertSame(
            0,
            $this->subject->getPublicKeyModulus()
        );
    }

    /**
     * @test
     */
    public function setPublicKeySetsPublicKeyModulus()
    {
        $this->subject->setPublicKey(123456);

        $this->assertSame(
            123456,
            $this->subject->getPublicKeyModulus()
        );
    }

    /**
     * @test
     *
     * @expectedException \BadMethodCallException
     */
    public function setPublicKeyCalledTwoTimesThrowsException()
    {
        $this->subject->setPublicKey(123456);
        $this->subject->setPublicKey(123456);
    }

    /**
     * @test
     */
    public function isReadyForExponentSetAndPrivateKeySetAndPublicKeyModulusSetReturnsTrue()
    {
        $this->subject->setExponent(1861234);
        $this->subject->setPrivateKey('lkjasbe');
        $this->subject->setPublicKey(745786268712);

        $this->assertTrue(
            $this->subject->isReady()
        );
    }

    /**
     * @test
     */
    public function isReadyForNothingSetReturnsFalse()
    {
        $this->assertFalse(
            $this->subject->isReady()
        );
    }

    /**
     * @test
     */
    public function isReadyForExponentSetAndPrivateKeySetAndPublicKeyModulusMissingReturnsFalse()
    {
        $this->subject->setExponent(1861234);
        $this->subject->setPrivateKey('lkjasbe');

        $this->assertFalse(
            $this->subject->isReady()
        );
    }

    /**
     * @test
     */
    public function isReadyForExponentSetAndPrivateMissingSetAndPublicKeyModulusSetReturnsFalse()
    {
        $this->subject->setExponent(1861234);
        $this->subject->setPublicKey(745786268712);

        $this->assertFalse(
            $this->subject->isReady()
        );
    }

    /**
     * @test
     */
    public function isReadyForExponentMissingAndPrivateKeySetAndPublicKeyModulusSetReturnsFalse()
    {
        $this->subject->setPrivateKey('lkjasbe');
        $this->subject->setPublicKey(745786268712);

        $this->assertFalse(
            $this->subject->isReady()
        );
    }
}
