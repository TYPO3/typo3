<?php
namespace TYPO3\CMS\Rsaauth\Hook;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
/**
 * This class provides a hook to the login form to add extra javascript code
 * and supply a proper form tag.
 *
 * @author Helmut Hummel <helmut.hummel@typo3.org>
 */
class UserSetupHook {

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
				$backend = \TYPO3\CMS\Rsaauth\Backend\BackendFactory::getBackend();
				/** @var $storage \TYPO3\CMS\Rsaauth\Storage\AbstractStorage */
				$storage = \TYPO3\CMS\Rsaauth\Storage\StorageFactory::getStorage();
				$key = $storage->get();
				$password = $backend->decrypt($key, substr($be_user_data['password'], 4));
				$password2 = $backend->decrypt($key, substr($be_user_data['password2'], 4));
				$be_user_data['password'] = $password ?: $be_user_data['password'];
				$be_user_data['password2'] = $password2 ?: $be_user_data['password2'];
			}
		}
	}

	/**
	 * Provides form code and javascript for the user setup.
	 *
	 * @param array $parameters Parameters to the script
	 * @param \TYPO3\CMS\Setup\Controller\SetupModuleController $userSetupObject Calling object: user setup module
	 * @return string The code for the user setup
	 */
	public function getLoginScripts(array $parameters, \TYPO3\CMS\Setup\Controller\SetupModuleController $userSetupObject) {
		$content = '';
		if ($this->isRsaAvailable()) {
			// If we can get the backend, we can proceed
			$backend = \TYPO3\CMS\Rsaauth\Backend\BackendFactory::getBackend();
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
			// Generate a new key pair
			$keyPair = $backend->createNewKeyPair();
			// Save private key
			$storage = \TYPO3\CMS\Rsaauth\Storage\StorageFactory::getStorage();
			/** @var $storage \TYPO3\CMS\Rsaauth\Storage\AbstractStorage */
			$storage->put($keyPair->getPrivateKey());
			// Add form tag
			$form = '<form action="' . \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('user_setup') . '" method="post" name="usersetup" enctype="application/x-www-form-urlencoded" onsubmit="tx_rsaauth_encryptUserSetup();">';
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
	 * @return boolean
	 */
	protected function isRsaAvailable() {
		return trim($GLOBALS['TYPO3_CONF_VARS']['BE']['loginSecurityLevel']) === 'rsa' && \TYPO3\CMS\Rsaauth\Backend\BackendFactory::getBackend() !== NULL;
	}

}
