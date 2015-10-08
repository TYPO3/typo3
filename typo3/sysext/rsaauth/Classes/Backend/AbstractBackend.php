<?php
namespace TYPO3\CMS\Rsaauth\Backend;

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
 * This class contains an abstract SSL backend for the TYPO3 RSA authentication
 * service.
 *
 * There are two steps:
 * - prepare data for encoding
 * - decode incoming data
 *
 * To prepare data for encoding, the createNewKeyPair() method should be called.
 * This method returns an instance of \TYPO3\CMS\Rsaauth\Keypair class, which contains
 * the private and public keys. Public key is sent to the client to encode data.
 * Private key should be stored somewhere (preferably in user's session).
 *
 * To decode data, the decrypt() method should be called with the private key
 * created at the previous step and the data to decode. If the data is decoded
 * successfully, the result is a string. Otherwise it is NULL.
 */
abstract class AbstractBackend
{
    /**
     * Error message for the last operation. Derived classes should always set
     * or clear this variable inside the createNewKeyPair() or decypt().
     *
     * @var string
     */
    protected $error = '';

    /**
     * Creates a new key pair for the encryption or gets the existing key pair (if one already has been generated).
     *
     * There should only be one key pair per request because the second private key would overwrites the first private
     * key. So the submitting the form with the first public key would not work anymore.
     *
     * @return \TYPO3\CMS\Rsaauth\Keypair|NULL a key pair or NULL in case of error
     */
    abstract public function createNewKeyPair();

    /**
     * Decripts the data using the private key.
     *
     * @param string $privateKey The private key (obtained from a call to createNewKeyPair())
     * @param string $data Data to decrypt (base64-encoded)
     * @return string Decrypted data or NULL in case of an error
     */
    abstract public function decrypt($privateKey, $data);

    /**
     * Checks if this backend is available for calling.
     *
     * @return bool
     */
    abstract public function isAvailable();

    /**
     * Retrieves an error message.
     *
     * @return string An error message or empty string if there were no error
     */
    public function getLastError()
    {
        return $this->error;
    }
}
