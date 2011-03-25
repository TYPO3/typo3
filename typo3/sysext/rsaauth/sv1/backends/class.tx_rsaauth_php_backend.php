<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2011 Dmitry Dulepov <dmitry@typo3.org>
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
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */

require_once(t3lib_extMgm::extPath('rsaauth', 'sv1/backends/class.tx_rsaauth_abstract_backend.php'));

/**
 * This class contains a PHP OpenSSL backend for the TYPO3 RSA authentication
 * service. See class tx_rsaauth_abstract_backend for the information on using
 * backends.
 *
 * @author	Dmitry Dulepov <dmitry@typo3.org>
 * @package	TYPO3
 * @subpackage	tx_rsaauth
 */
class tx_rsaauth_php_backend extends tx_rsaauth_abstract_backend {

	/**
	 * Creates a new public/private key pair using PHP OpenSSL extension.
	 *
	 * @return tx_rsaauth_keypair	A new key pair or null in case of error
	 * @see tx_rsaauth_abstract_backend::createNewKeyPair()
	 */
	public function createNewKeyPair() {
		$result = null;
		$privateKey = @openssl_pkey_new();
		if ($privateKey) {
			// Create private key as string
			$privateKeyStr = '';
			openssl_pkey_export($privateKey, $privateKeyStr);

			// Prepare public key information
			$exportedData = '';
			$csr = openssl_csr_new(array(), $privateKey);
			openssl_csr_export($csr, $exportedData, false);

			// Get public key (in fact modulus) and exponent
			$publicKey = $this->extractPublicKeyModulus($exportedData);
			$exponent = $this->extractExponent($exportedData);

			// Create result object
			$result = t3lib_div::makeInstance('tx_rsaauth_keypair');
			/* @var $result tx_rsaauth_keypair */
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
	 * @param string	$privateKey	The private key (obtained from a call to createNewKeyPair())
	 * @param string	$data	Data to decrypt (base64-encoded)
	 * @return string	Decrypted data or null in case of a error
	 * @see tx_rsaauth_abstract_backend::decrypt()
	 */
	public function decrypt($privateKey, $data) {
		$result = '';
		if (!@openssl_private_decrypt(base64_decode($data), $result, $privateKey)) {
			$result = null;
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
		$result = false;
		if (is_callable('openssl_pkey_new')) {
			if (TYPO3_OS !== 'WIN') {
				// If the server does not run Windows, we can be sure than
				// OpenSSL will work
				$result = true;
			}
			else {
				// On Windows PHP extension has to be configured properly. It
				// can be installed and available but will not work unless
				// configured. So we check if it works.
				$testKey = @openssl_pkey_new();
				if ($testKey) {
					openssl_free_key($testKey);
					$result = true;
				}
			}
		}
		return $result;
	}

	/**
	 * Extracts the exponent from the OpenSSL CSR
	 *
	 * @param	string	$data	The result of openssl_csr_export()
	 * @return	int	The exponent as a number
	 */
	protected function extractExponent($data) {
		$index = strpos($data, 'Exponent: ');
		// We do not check for '$index === false' because the exponent is
		// always there!
		return intval(substr($data, $index + 10));
	}

	/**
	 * Extracts public key modulus from the OpenSSL CSR.
	 *
	 * @param	string	$data	The result of openssl_csr_export()
	 * @return	string	Modulus as uppercase hex string
	 */
	protected function extractPublicKeyModulus($data) {
		$fragment = preg_replace('/.*Modulus.*?\n(.*)Exponent:.*/ms', '\1', $data);
		$fragment = preg_replace('/[\s\n\r:]/', '', $fragment);
		$result = trim(strtoupper(substr($fragment, 2)));

		return $result;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rsaauth/sv1/backends/class.tx_rsaauth_php_backend.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rsaauth/sv1/backends/class.tx_rsaauth_php_backend.php']);
}

?>