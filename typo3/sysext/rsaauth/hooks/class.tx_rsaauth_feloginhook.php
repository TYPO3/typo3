<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2010 Dmitry Dulepov <dmitry@typo3.org>
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
 *
 * $Id$
 */

require_once(t3lib_extMgm::extPath('rsaauth') . 'sv1/backends/class.tx_rsaauth_backendfactory.php');
require_once(t3lib_extMgm::extPath('rsaauth') . 'sv1/storage/class.tx_rsaauth_storagefactory.php');

/**
 * This class contains a hook to implement RSA authentication for the TYPO3
 * Frontend. Warning: felogin must be USER_INT for this to work!
 *
 * @author	Dmitry Dulepov <dmitry@typo3.org>
 * @package	TYPO3
 * @subpackage	tx_rsaauth
 */
class tx_rsaauth_feloginhook {

	/**
	 * Hooks to the felogin extension to provide additional code for FE login
	 *
	 * @return	array	0 => onSubmit function, 1 => extra fields and required files
	 */
	public function loginFormHook() {
		$result = array(0 => '', 1 => '');

		if ($GLOBALS['TYPO3_CONF_VARS']['FE']['loginSecurityLevel'] == 'rsa') {
			$backend = tx_rsaauth_backendfactory::getBackend();
			if ($backend) {
				$result[0] = 'tx_rsaauth_feencrypt(this);';

				$javascriptPath = t3lib_extMgm::siteRelPath('rsaauth') . 'resources/';
				$files = array(
					'jsbn/jsbn.js',
					'jsbn/prng4.js',
					'jsbn/rng.js',
					'jsbn/rsa.js',
					'jsbn/base64.js',
					'rsaauth_min.js'
				);

				foreach ($files as $file) {
					$result[1] .= '<script type="text/javascript" src="' .
						t3lib_div::getIndpEnv('TYPO3_SITE_URL') .
						$javascriptPath . $file . '"></script>';
				}

				// Generate a new key pair
				$keyPair = $backend->createNewKeyPair();

				// Save private key
				$storage = tx_rsaauth_storagefactory::getStorage();
				/* @var $storage tx_rsaauth_abstract_storage */
				$storage->put($keyPair->getPrivateKey());

				// Add RSA hidden fields
				$result[1] .= '<input type="hidden" id="rsa_n" name="n" value="' . htmlspecialchars($keyPair->getPublicKeyModulus()) . '" />';
				$result[1] .= '<input type="hidden" id="rsa_e" name="e" value="' . sprintf('%x', $keyPair->getExponent()) . '" />';
			}
		}
		return $result;
	}

}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rsaauth/hooks/class.tx_rsaauth_feloginhook.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rsaauth/hooks/class.tx_rsaauth_feloginhook.php']);
}

?>