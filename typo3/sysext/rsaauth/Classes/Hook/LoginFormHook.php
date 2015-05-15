<?php
namespace TYPO3\CMS\Rsaauth\Hook;

/*
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
use TYPO3\CMS\Backend\Controller\LoginController;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * This class provides a hook to the login form to add extra javascript code
 * and supply a proper form tag.
 *
 * @author Dmitry Dulepov <dmitry@typo3.org>
 * @author Helmut Hummel <helmut@typo3.org>
 */
class LoginFormHook {

	/**
	 * Adds RSA-specific JavaScript
	 *
	 * @param array $params
	 * @param LoginController $pObj
	 * @return string|NULL Dummy JS or NULL if security level is not rsa
	 */
	public function getLoginFormJS(array $params, LoginController $pObj) {
		if ($pObj->loginSecurityLevel !== 'rsa') {
			return NULL;
		}
		$javascriptPath = '../' . ExtensionManagementUtility::siteRelPath('rsaauth') . 'Resources/Public/JavaScript/';
		$files = array(
			'jsbn/jsbn.js',
			'jsbn/prng4.js',
			'jsbn/rng.js',
			'jsbn/rsa.js',
			'jsbn/base64.js'
		);

		/** @var DocumentTemplate $doc */
		$doc = $GLOBALS['TBE_TEMPLATE'];
		$pageRenderer = $doc->getPageRenderer();
		foreach ($files as $file) {
			$pageRenderer->addJsFile($javascriptPath . $file);
		}

		$pageRenderer->loadRequireJsModule('TYPO3/CMS/Rsaauth/BackendLoginFormRsaEncryption');

		return '// no content';
	}

}
