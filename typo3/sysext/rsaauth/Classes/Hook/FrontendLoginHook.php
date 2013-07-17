<?php
namespace TYPO3\CMS\Rsaauth\Hook;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class contains a hook to implement RSA authentication for the TYPO3
 * Frontend. Warning: felogin must be USER_INT for this to work!
 *
 * @author Dmitry Dulepov <dmitry@typo3.org>
 */
class FrontendLoginHook {

	/**
	 * Hooks to the felogin extension to provide additional code for FE login
	 *
	 * @return array 0 => onSubmit function, 1 => extra fields and required files
	 */
	public function loginFormHook() {
		$result = array(0 => '', 1 => '');
		if (trim($GLOBALS['TYPO3_CONF_VARS']['FE']['loginSecurityLevel']) === 'rsa') {
			$backend = \TYPO3\CMS\Rsaauth\Backend\BackendFactory::getBackend();
			if ($backend) {
				$result[0] = 'tx_rsaauth_feencrypt(this);';
				$javascriptPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('rsaauth') . 'resources/';
				$files = array(
					'jsbn/jsbn.js',
					'jsbn/prng4.js',
					'jsbn/rng.js',
					'jsbn/rsa.js',
					'jsbn/base64.js',
					'rsaauth_min.js'
				);
				foreach ($files as $file) {
					$result[1] .= '<script type="text/javascript" src="' . GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $javascriptPath . $file . '"></script>';
				}
				// Generate a new key pair
				$keyPair = $backend->createNewKeyPair();
				// Save private key
				$storage = \TYPO3\CMS\Rsaauth\Storage\StorageFactory::getStorage();
				/** @var $storage \TYPO3\CMS\Rsaauth\Storage\AbstractStorage */
				$storage->put($keyPair->getPrivateKey());
				// Add RSA hidden fields
				$result[1] .= '<input type="hidden" id="rsa_n" name="n" value="' . htmlspecialchars($keyPair->getPublicKeyModulus()) . '" />';
				$result[1] .= '<input type="hidden" id="rsa_e" name="e" value="' . sprintf('%x', $keyPair->getExponent()) . '" />';
			}
		}
		return $result;
	}

	/**
	 * Hooks to the felogin extension to provide additional code for the change password form of FE login
	 *
	 * @return array 0 => onSubmit function, 1 => extra fields and required files
	 */
	public function changePasswordFormOnSubmitHook() {
		$result = $this->loginFormHook();
		$result[0] = 'tx_rsaauth_feChangePasswordEncrypt(this);';
		return $result;
	}

	/**
	 * Hook for the felogin extension to decrypt passwords
	 *
	 * @param array $postData Post data containing password1 and password2
	 * @return void
	 */
	public function changePasswordFormModifyPostDataHook(array &$postData) {
		if ($GLOBALS['TSFE']->fe_user->security_level !== 'rsa') {
			return;
		}
		$cryptService = GeneralUtility::makeInstance('TYPO3\\CMS\\Rsaauth\\RsaCryptService');
		$postData['password1'] = $cryptService->decrypt($postData['password1']);
		$postData['password2'] = $cryptService->decrypt($postData['password2']);
	}
}


?>