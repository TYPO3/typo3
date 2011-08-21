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

require_once(t3lib_extMgm::extPath('sv') . 'class.tx_sv_auth.php');

// Include backends

/**
 * Service "RSA authentication" for the "rsaauth" extension. This service will
 * authenticate a user using hos password encoded with one time public key. It
 * uses the standard TYPO3 service to do all dirty work. Firsts, it will decode
 * the password and then pass it to the parent service ('sv'). This ensures that it
 * always works, even if other TYPO3 internals change.
 *
 * @author	Dmitry Dulepov <dmitry@typo3.org>
 * @package	TYPO3
 * @subpackage	tx_rsaauth
 */
class tx_rsaauth_sv1 extends tx_sv_auth  {

	/**
	 * An RSA backend.
	 *
	 * @var	tx_rsaauth_abstract_backend
	 */
	protected	$backend = NULL;

	/**
	 * Standard extension key for the service
	 *
	 * @var	string
	 */
	public	$extKey = 'rsaauth';	// The extension key.

	/**
	 * Standard prefix id for the service
	 *
	 * @var	string
	 */
	public	$prefixId = 'tx_rsaauth_sv1';		// Same as class name

	/**
	 * Standard relative path for the service
	 *
	 * @var	string
	 */
	public	$scriptRelPath = 'sv1/class.tx_rsaauth_sv1.php';	// Path to this script relative to the extension dir.


	/**
	 * Process the submitted credentials.
	 * In this case decrypt the password if it is RSA encrypted.
	 *
	 * @param array $loginData Credentials that are submitted and potentially modified by other services
	 * @param string $passwordTransmissionStrategy Keyword of how the password has been hashed or encrypted before submission
	 * @return bool
	 */
	public function processLoginData(array &$loginData, $passwordTransmissionStrategy) {

		$isProcessed = FALSE;

		if ($passwordTransmissionStrategy === 'rsa') {
			$storage = tx_rsaauth_storagefactory::getStorage();
			/* @var $storage tx_rsaauth_abstract_storage */

				// Decrypt the password
			$password = $loginData['uident'];
			$key = $storage->get();
			if ($key != NULL && substr($password, 0, 4) === 'rsa:') {
					// Decode password and store it in loginData
				$decryptedPassword = $this->backend->decrypt($key, substr($password, 4));
				if ($decryptedPassword != NULL) {
					$loginData['uident_text'] = $decryptedPassword;
					$isProcessed = TRUE;
				} else {
					if ($this->pObj->writeDevLog) {
						t3lib_div::devLog('Process login data: Failed to RSA decrypt password', 'tx_rsaauth_sv1');
					}
				}
					// Remove the key
				$storage->put(NULL);
			} else {
				if ($this->pObj->writeDevLog) {
					t3lib_div::devLog('Process login data: passwordTransmissionStrategy has been set to "rsa" but no rsa encrypted password has been found.', 'tx_rsaauth_sv1');
				}
			}
		}

		return $isProcessed;
	}

	/**
	 * Initializes the service.
	 *
	 * @return	boolean
	 */
	public function init()	{
		$available = parent::init();
		if ($available) {
			// Get the backend
			$this->backend = tx_rsaauth_backendfactory::getBackend();
			if (is_null($this->backend)) {
				$available = FALSE;
			}
		}

		return $available;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rsaauth/sv1/class.tx_rsaauth_sv1.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rsaauth/sv1/class.tx_rsaauth_sv1.php']);
}

?>