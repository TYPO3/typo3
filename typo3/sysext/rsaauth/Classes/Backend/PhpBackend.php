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
 * This class contains a PHP OpenSSL backend for the TYPO3 RSA authentication
 * service. See class tx_rsaauth_abstract_backend for the information on using
 * backends.
 *
 * @author Dmitry Dulepov <dmitry@typo3.org>
 */
class PhpBackend extends \TYPO3\CMS\Rsaauth\Backend\AbstractBackend {

	/**
	 * Creates a new public/private key pair using PHP OpenSSL extension.
	 *
	 * @return \TYPO3\CMS\Rsaauth\Keypair A new key pair or NULL in case of error
	 * @see tx_rsaauth_abstract_backend::createNewKeyPair()
	 */
	public function createNewKeyPair() {
		$result = NULL;
		$privateKey = @openssl_pkey_new();
		if ($privateKey) {
			// Create private key as string
			$privateKeyStr = '';
			openssl_pkey_export($privateKey, $privateKeyStr);
			// Prepare public key information
			$exportedData = '';
			$csr = openssl_csr_new(array(), $privateKey);
			openssl_csr_export($csr, $exportedData, FALSE);
			// Get public key (in fact modulus) and exponent
			$publicKey = $this->extractPublicKeyModulus($exportedData);
			$exponent = $this->extractExponent($exportedData);
			// Create result object
			$result = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Rsaauth\\Keypair');
			/** @var $result \TYPO3\CMS\Rsaauth\Keypair */
			$result->setExponent($exponent);
			$result->setPrivateKey($privateKeyStr);
			$result->setPublicKey($publicKey);
			// Clean up all resources
			openssl_free_key($privateKey);
		}
		return $result;
	}

	/**
	 * Decrypts data using the private key. This implementation uses PHP OpenSSL
	 * extension.
	 *
	 * @param string $privateKey The private key (obtained from a call to createNewKeyPair())
	 * @param string $data Data to decrypt (base64-encoded)
	 * @return string Decrypted data or NULL in case of a error
	 * @see tx_rsaauth_abstract_backend::decrypt()
	 */
	public function decrypt($privateKey, $data) {
		$result = '';
		if (!@openssl_private_decrypt(base64_decode($data), $result, $privateKey)) {
			$result = NULL;
		}
		return $result;
	}

	/**
	 * Checks if this backend is available for calling. In particular checks if
	 * PHP OpenSSl extension is installed and functional.
	 *
	 * @return void
	 * @see tx_rsaauth_abstract_backend::isAvailable()
	 */
	public function isAvailable() {
		$result = FALSE;
		if (is_callable('openssl_pkey_new')) {
			// PHP extension has to be configured properly. It
			// can be installed and available but will not work unless
			// properly configured. So we check if it works.
			$testKey = @openssl_pkey_new();
			if (is_resource($testKey)) {
				openssl_free_key($testKey);
				$result = TRUE;
			}
		}
		return $result;
	}

	/**
	 * Extracts the exponent from the OpenSSL CSR
	 *
	 * @param string $data The result of openssl_csr_export()
	 * @return integer The exponent as a number
	 */
	protected function extractExponent($data) {
		$index = strpos($data, 'Exponent: ');
		// We do not check for '$index === FALSE' because the exponent is
		// always there!
		return intval(substr($data, $index + 10));
	}

	/**
	 * Extracts public key modulus from the OpenSSL CSR.
	 *
	 * @param string $data The result of openssl_csr_export()
	 * @return string Modulus as uppercase hex string
	 */
	protected function extractPublicKeyModulus($data) {
		$fragment = preg_replace('/.*Modulus.*?\\n(.*)Exponent:.*/ms', '\\1', $data);
		$fragment = preg_replace('/[\\s\\n\\r:]/', '', $fragment);
		$result = trim(strtoupper(substr($fragment, 2)));
		return $result;
	}

}


?>