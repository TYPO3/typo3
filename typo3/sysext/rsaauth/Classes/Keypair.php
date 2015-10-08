<?php
namespace TYPO3\CMS\Rsaauth;

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
 * This class contain an RSA key pair. Its purpose is to keep to keys
 * and transfer these keys between other PHP classes.
 */
class Keypair implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * RSA public exponent (3 or 0x10001)
     *
     * @var int
     */
    protected $exponent = 0;

    /**
     * The private key
     *
     * @var string
     */
    protected $privateKey = '';

    /**
     * The public key modulus
     *
     * @var int
     */
    protected $publicKeyModulus = 0;

    /**
     * Checks if this key pair already has been provided with all data.
     *
     * @return bool
     */
    public function isReady()
    {
        return $this->hasExponent() && $this->hasPrivateKey() && $this->hasPublicKeyModulus();
    }

    /**
     * Retrieves the exponent.
     *
     * @return int the exponent
     */
    public function getExponent()
    {
        return $this->exponent;
    }

    /**
     * Sets the exponent
     *
     * Note: This method must not be called more than one time.
     *
     * @param int $exponent the new exponent
     * @return void
     * @throws \BadMethodCallException if the method was called more than one time
     */
    public function setExponent($exponent)
    {
        if ($this->hasExponent()) {
            throw new \BadMethodCallException('setExponent() must not be called more than one time.', 1296062830);
        }

        $this->exponent = $exponent;
    }

    /**
     * Checks whether an exponent already has been set.
     *
     * @return bool
     */
    protected function hasExponent()
    {
        return $this->getExponent() !== 0;
    }

    /**
     * Retrieves the private key.
     *
     * @return string The private key
     */
    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    /**
     * Sets the private key.
     *
     * Note: This method must not be called more than one time.
     *
     * @param string $privateKey The new private key
     * @return void
     * @throws \BadMethodCallException if the method was called more than one time
     */
    public function setPrivateKey($privateKey)
    {
        if ($this->hasPrivateKey()) {
            throw new \BadMethodCallException('setPrivateKey() must not be called more than one time.', 1296062831);
        }

        $this->privateKey = $privateKey;
    }

    /**
     * Checks whether a private key already has been set.
     *
     * @return bool
     */
    protected function hasPrivateKey()
    {
        return $this->getPrivateKey() !== '';
    }

    /**
     * Retrieves the public key modulus
     *
     * @return int the public key modulus
     */
    public function getPublicKeyModulus()
    {
        return $this->publicKeyModulus;
    }

    /**
     * Sets the public key modulus.
     *
     * Note: This method must not be called more than one time.
     *
     * @param int $publicKeyModulus the new public key modulus
     * @return void
     * @throws \BadMethodCallException if the method was called more than one time
     */
    public function setPublicKey($publicKeyModulus)
    {
        if ($this->hasPublicKeyModulus()) {
            throw new \BadMethodCallException('setPublicKey() must not be called more than one time.', 1296062832);
        }

        $this->publicKeyModulus = $publicKeyModulus;
    }

    /**
     * Checks whether a public key modulus already has been set.
     *
     * @return bool
     */
    protected function hasPublicKeyModulus()
    {
        return $this->getPublicKeyModulus() !== 0;
    }
}
