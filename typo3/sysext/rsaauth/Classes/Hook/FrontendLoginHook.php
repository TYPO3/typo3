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
				$result[0] = 'return TYPO3FrontendLoginFormRsaEncryption.submitForm(this, TYPO3FrontendLoginFormRsaEncryptionPublicKeyUrl);';
				$javascriptPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('rsaauth') . 'resources/';
				$files = array(
					'jsbn/jsbn.js',
					'jsbn/prng4.js',
					'jsbn/rng.js',
					'jsbn/rsa.js',
					'jsbn/base64.js',
					'FrontendLoginFormRsaEncryption.min.js'
				);
				$eIdUrl = \TYPO3\CMS\Core\Utility\GeneralUtility::quoteJSvalue($GLOBALS['TSFE']->absRefPrefix . 'index.php?eID=FrontendLoginRsaPublicKey');
				$additionalHeader = '<script type="text/javascript">var TYPO3FrontendLoginFormRsaEncryptionPublicKeyUrl = ' . $eIdUrl . ';</script>';
				foreach ($files as $file) {
					$additionalHeader .= '<script type="text/javascript" src="' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $javascriptPath . $file . '"></script>';
				}
				$GLOBALS['TSFE']->additionalHeaderData['rsaauth_js'] = $additionalHeader;
			}
		}
		return $result;
	}

}
