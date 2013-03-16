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
 * This class contains a OpenSSL backend for the TYPO3 RSA authentication
 * service. It uses shell version of OpenSSL to perform tasks. See class
 * tx_rsaauth_abstract_backend for the information on using backends.
 *
 * @author Dmitry Dulepov <dmitry@typo3.org>
 */
class CommandLineBackend extends \TYPO3\CMS\Rsaauth\Backend\AbstractBackend {

	/**
	 * A path to the openssl binary or FALSE if the binary does not exist
	 *
	 * @var mixed
	 */
	protected $opensslPath;

	/**
	 * Temporary directory. It is best of it is outside of the web site root and
	 * not publically readable.
	 * For now we use typo3temp/.
	 *
	 * @var string
	 */
	protected $temporaryDirectory;

	/**
	 * Creates an instance of this class. It obtains a path to the OpenSSL
	 * binary.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->opensslPath = \TYPO3\CMS\Core\Utility\CommandUtility::getCommand('openssl');
		$this->temporaryDirectory = PATH_site . 'typo3temp';
		// Get temporary directory from the configuration
		$extconf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['rsaauth']);
		if ($extconf['temporaryDirectory'] != '' && $extconf['temporaryDirectory'][0] == '/' && @is_dir($extconf['temporaryDirectory']) && is_writable($extconf['temporaryDirectory'])) {
			$this->temporaryDirectory = $extconf['temporaryDirectory'];
		}
	}

	/**
	 * @return \TYPO3\CMS\Rsaauth\Keypair A new key pair or NULL in case of error
	 * @see tx_rsaauth_abstract_backend::createNewKeyPair()
	 */
	public function createNewKeyPair() {
		$result = NULL;
		// Create a temporary file. Security: tempnam() sets permissions to 0600
		$privateKeyFile = tempnam($this->temporaryDirectory, uniqid());
		// Generate the private key.
		//
		// PHP generates 1024 bit key files. We force command line version
		// to do the same and use the F4 (0x10001) exponent. This is the most
		// secure.
		$command = $this->opensslPath . ' genrsa -out ' . escapeshellarg($privateKeyFile) . ' 1024';
		\TYPO3\CMS\Core\Utility\CommandUtility::exec($command);
		// Test that we got a private key
		$privateKey = file_get_contents($privateKeyFile);
		if (FALSE !== strpos($privateKey, 'BEGIN RSA PRIVATE KEY')) {
			// Ok, we got the private key. Get the modulus.
			$command = $this->opensslPath . ' rsa -noout -modulus -in ' . escapeshellarg($privateKeyFile);
			$value = \TYPO3\CMS\Core\Utility\CommandUtility::exec($command);
			if (substr($value, 0, 8) === 'Modulus=') {
				$publicKey = substr($value, 8);
				// Create a result object
				$result = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Rsaauth\\Keypair');
				/** @var $result tx_rsa_keypair */
				$result->setExponent(65537);
				$result->setPrivateKey($privateKey);
				$result->setPublicKey($publicKey);
			}
		}
		@unlink($privateKeyFile);
		return $result;
	}

	/**
	 * @param string $privateKey The private key (obtained from a call to createNewKeyPair())
	 * @param string $data Data to decrypt (base64-encoded)
	 * @return string Decrypted data or NULL in case of a error
	 * @see tx_rsaauth_abstract_backend::decrypt()
	 */
	public function decrypt($privateKey, $data) {
		// Key must be put to the file
		$privateKeyFile = tempnam($this->temporaryDirectory, uniqid());
		file_put_contents($privateKeyFile, $privateKey);
		$dataFile = tempnam($this->temporaryDirectory, uniqid());
		file_put_contents($dataFile, base64_decode($data));
		// Prepare the command
		$command = $this->opensslPath . ' rsautl -inkey ' . escapeshellarg($privateKeyFile) . ' -in ' . escapeshellarg($dataFile) . ' -decrypt';
		// Execute the command and capture the result
		$output = array();
		\TYPO3\CMS\Core\Utility\CommandUtility::exec($command, $output);
		// Remove the file
		@unlink($privateKeyFile);
		@unlink($dataFile);
		return implode(LF, $output);
	}

	/**
	 * Checks if command line version of the OpenSSL is available and can be
	 * executed successfully.
	 *
	 * @return void
	 * @see tx_rsaauth_abstract_backend::isAvailable()
	 */
	public function isAvailable() {
		$result = FALSE;
		if ($this->opensslPath) {
			// If path exists, test that command runs and can produce output
			$test = \TYPO3\CMS\Core\Utility\CommandUtility::exec($this->opensslPath . ' version');
			$result = substr($test, 0, 8) == 'OpenSSL ';
		}
		return $result;
	}

}


?>