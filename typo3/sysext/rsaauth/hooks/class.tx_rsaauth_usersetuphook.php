<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Helmut Hummel <helmut.hummel@typo3.org>
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
 * This class provides a hook to the login form to add extra javascript code
 * and supply a proper form tag.
 *
 * @author	Helmut Hummel <helmut.hummel@typo3.org>
 * @package	TYPO3
 * @subpackage	tx_rsaauth
 */
class tx_rsaauth_usersetuphook {

	/**
	 * Decrypt the password fields if they are filled.
	 *
	 * @param array $parameters Parameters to the script
	 * @return void
	 */
	public function decryptPassword(array $parameters) {

		if ($this->isRsaAvailable()) {
			$be_user_data = &$parameters['be_user_data'];

			if (substr($be_user_data['password'], 0, 4) === 'rsa:' && substr($be_user_data['password2'], 0, 4) === 'rsa:') {
				$backend = tx_rsaauth_backendfactory::getBackend();
				$storage = tx_rsaauth_storagefactory::getStorage();
				/* @var $storage tx_rsaauth_abstract_storage */

				$key = $storage->get();

				$password = $backend->decrypt($key, substr($be_user_data['password'], 4));
				$password2 = $backend->decrypt($key, substr($be_user_data['password2'], 4));

				$be_user_data['password'] = $password ? $password : $be_user_data['password'];
				$be_user_data['password2'] = $password2 ? $password2 : $be_user_data['password2'];
			}
		}
	}


	/**
	 * Provides form code and javascript for the user setup.
	 *
	 * @param array $parameters Parameters to the script
	 * @param SC_index $userSetupObject Calling object: user setup module
	 * @return string The code for the user setup
	 */
	public function getLoginScripts(array $parameters, SC_mod_user_setup_index $userSetupObject) {
		$content = '';
		if ($this->isRsaAvailable()) {
				// If we can get the backend, we can proceed
			$backend = tx_rsaauth_backendfactory::getBackend();

			$javascriptPath = t3lib_extMgm::siteRelPath('rsaauth') . 'resources/';
			$files = array(
				'jsbn/jsbn.js',
				'jsbn/prng4.js',
				'jsbn/rng.js',
				'jsbn/rsa.js',
				'jsbn/base64.js',
				'rsaauth_min.js'
			);

			$content = '';
			foreach ($files as $file) {
				$content .= '<script type="text/javascript" src="' .
					t3lib_div::getIndpEnv('TYPO3_SITE_URL') .
					$javascriptPath . $file . '"></script>';
			}
			// Generate a new key pair
			$keyPair = $backend->createNewKeyPair();

			// Save private key
			$storage = tx_rsaauth_storagefactory::getStorage();
			/* @var $storage tx_rsaauth_abstract_storage */
			$storage->put($keyPair->getPrivateKey());

			// Add form tag
			$form = '<form action="index.php" method="post" name="usersetup" enctype="application/x-www-form-urlencoded" onsubmit="tx_rsaauth_encryptUserSetup();">';
			// Add RSA hidden fields
			$form .= '<input type="hidden" id="rsa_n" name="n" value="' . htmlspecialchars($keyPair->getPublicKeyModulus()) . '" />';
			$form .= '<input type="hidden" id="rsa_e" name="e" value="' . sprintf('%x', $keyPair->getExponent()) . '" />';

			$userSetupObject->doc->form = $form;
		}
		return $content;
	}

	/**
	 * Rsa is available if loginSecurityLevel is set and rsa backend is working.
	 *
	 * @return bool
	 */
	protected function isRsaAvailable() {
		return (trim($GLOBALS['TYPO3_CONF_VARS']['BE']['loginSecurityLevel']) === 'rsa') && (tx_rsaauth_backendfactory::getBackend() !== NULL);
	}


}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rsaauth/hooks/class.tx_rsaauth_loginformhook.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rsaauth/hooks/class.tx_rsaauth_loginformhook.php']);
}

?>