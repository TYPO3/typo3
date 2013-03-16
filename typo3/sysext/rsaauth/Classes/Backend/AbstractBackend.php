<?php
namespace TYPO3\CMS\Rsaauth\Backend;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Dmitry Dulepov <dmitry@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * This class contains an abstract SSL backend for the TYPO3 RSA authentication
 * service.
 *
 * There are two steps:
 * - prepare data for encoding
 * - decode incoming data
 *
 * To prepare data for encoding, the createNewKeyPair() method should be called.
 * This method returns an instance of tx_rsaauth_keypair class, which contains
 * the private and public keys. Public key is sent to the client to encode data.
 * Private key should be stored somewhere (preferrably in user's session).
 *
 * To decode data, the decrypt() method should be called with the private key
 * created at the previous step and the data to decode. If the data is decoded
 * successfully, the result is a string. Otherwise it is NULL.
 *
 * @author Dmitry Dulepov <dmitry@typo3.org>
 */
abstract class AbstractBackend {

	/**
	 * Error message for the last operation. Derieved classes should always set
	 * or clear this variable inside the createNewKeyPair() or decypt().
	 *
	 * @var string
	 */
	protected $error = '';

	/**
	 * Creates a new key pair for the encryption.
	 *
	 * @return \TYPO3\CMS\Rsaauth\Keypair A new key pair or NULL in case of error
	 */
	abstract public function createNewKeyPair();

	/**
	 * Decripts the data using the private key.
	 *
	 * @param string $privateKey The private key (obtained from a call to createNewKeyPair())
	 * @param string $data Data to decrypt (base64-encoded)
	 * @return string Decrypted data or NULL in case of a error
	 */
	abstract public function decrypt($privateKey, $data);

	/**
	 * Checks if this backend is available for calling.
	 *
	 * @return void
	 */
	abstract public function isAvailable();

	/**
	 * Retrieves a error message.
	 *
	 * @return string A error message or empty string if there were no error
	 */
	public function getLastError() {
		return $this->error;
	}

}


?>