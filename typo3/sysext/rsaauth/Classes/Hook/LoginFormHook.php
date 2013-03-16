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
/**
 * This class provides a hook to the login form to add extra javascript code
 * and supply a proper form tag.
 *
 * @author Dmitry Dulepov <dmitry@typo3.org>
 */
class LoginFormHook {

	/**
	 * Adds RSA-specific JavaScript and returns a form tag
	 *
	 * @return string Form tag
	 */
	public function getLoginFormTag(array $params, \TYPO3\CMS\Backend\Controller\LoginController &$pObj) {
		$form = NULL;
		if ($pObj->loginSecurityLevel == 'rsa') {
			// If we can get the backend, we can proceed
			$backend = \TYPO3\CMS\Rsaauth\Backend\BackendFactory::getBackend();
			if (!is_null($backend)) {
				// Add form tag
				$form = '<form action="index.php" method="post" name="loginform" onsubmit="tx_rsaauth_encrypt();">';
				// Generate a new key pair
				$keyPair = $backend->createNewKeyPair();
				// Save private key
				$storage = \TYPO3\CMS\Rsaauth\Storage\StorageFactory::getStorage();
				/** @var $storage \TYPO3\CMS\Rsaauth\Storage\AbstractStorage */
				$storage->put($keyPair->getPrivateKey());
				// Add RSA hidden fields
				$form .= '<input type="hidden" id="rsa_n" name="n" value="' . htmlspecialchars($keyPair->getPublicKeyModulus()) . '" />';
				$form .= '<input type="hidden" id="rsa_e" name="e" value="' . sprintf('%x', $keyPair->getExponent()) . '" />';
			} else {
				throw new \TYPO3\CMS\Core\Error\Exception('No OpenSSL backend could be obtained for rsaauth.', 1318283565);
			}
		}
		return $form;
	}

	/**
	 * Provides form code for the superchallenged authentication.
	 *
	 * @param array $params Parameters to the script
	 * @param \TYPO3\CMS\Backend\Controller\LoginController $pObj Calling object
	 * @return string The code for the login form
	 */
	public function getLoginScripts(array $params, \TYPO3\CMS\Backend\Controller\LoginController &$pObj) {
		$content = '';
		if ($pObj->loginSecurityLevel == 'rsa') {
			$javascriptPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('rsaauth') . 'resources/';
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
				$content .= '<script type="text/javascript" src="' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $javascriptPath . $file . '"></script>';
			}
		}
		return $content;
	}

}


?>