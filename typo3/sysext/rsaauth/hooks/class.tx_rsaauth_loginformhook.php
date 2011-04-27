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

require_once(t3lib_extMgm::extPath('rsaauth') . 'sv1/backends/class.tx_rsaauth_backendfactory.php');
require_once(t3lib_extMgm::extPath('rsaauth', 'sv1/storage/class.tx_rsaauth_storagefactory.php'));

/**
 * This class provides a hook to the login form to add extra javascript code
 * and supply a proper form tag.
 *
 * @author	Dmitry Dulepov <dmitry@typo3.org>
 * @package	TYPO3
 * @subpackage	tx_rsaauth
 */
class tx_rsaauth_loginformhook {

	/**
	 * Adds RSA-specific JavaScript and returns a form tag
	 *
	 * @return	string	Form tag
	 */
	public function getLoginFormTag(array $params, SC_index& $pObj) {
		$form = NULL;
		if ($pObj->loginSecurityLevel == 'rsa') {

			// If we can get the backend, we can proceed
			$backend = tx_rsaauth_backendfactory::getBackend();
			if (!is_null($backend)) {

				// Add form tag
				$form = '<form action="index.php" method="post" name="loginform" onsubmit="tx_rsaauth_encrypt();">';

				// Generate a new key pair
				$keyPair = $backend->createNewKeyPair();

				// Save private key
				$storage = tx_rsaauth_storagefactory::getStorage();
				/* @var $storage tx_rsaauth_abstract_storage */
				$storage->put($keyPair->getPrivateKey());

				// Add RSA hidden fields
				$form .= '<input type="hidden" id="rsa_n" name="n" value="' . htmlspecialchars($keyPair->getPublicKeyModulus()) . '" />';
				$form .= '<input type="hidden" id="rsa_e" name="e" value="' . sprintf('%x', $keyPair->getExponent()) . '" />';
			}
		}
		return $form;
	}


	/**
	 * Provides form code for the superchallenged authentication.
	 *
	 * @param	array	$params	Parameters to the script
	 * @param	SC_index	$pObj	Calling object
	 * @return	string	The code for the login form
	 */
	public function getLoginScripts(array $params, SC_index &$pObj) {
		$content = '';

		if ($pObj->loginSecurityLevel == 'rsa') {
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
		}

		return $content;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rsaauth/hooks/class.tx_rsaauth_loginformhook.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rsaauth/hooks/class.tx_rsaauth_loginformhook.php']);
}

?>