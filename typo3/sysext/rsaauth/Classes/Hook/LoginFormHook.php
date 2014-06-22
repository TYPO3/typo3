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
 * @author Dmitry Dulepov <dmitry@typo3.org>
 * @author Helmut Hummel <helmut@typo3.org>
 */
class LoginFormHook {

	/**
	 * Adds RSA-specific JavaScript and returns a form tag
	 *
	 * @param array $params
	 * @param \TYPO3\CMS\Backend\Controller\LoginController $pObj
	 * @return string Form tag
	 * @throws \TYPO3\CMS\Core\Error\Exception
	 */
	public function getLoginFormTag(array $params, \TYPO3\CMS\Backend\Controller\LoginController &$pObj) {
		$form = NULL;
		if ($pObj->loginSecurityLevel === 'rsa') {
			/** @var $pageRenderer \TYPO3\CMS\Core\Page\PageRenderer */
			$pageRenderer = $GLOBALS['TBE_TEMPLATE']->getPageRenderer();
			$javascriptPath = '../' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('rsaauth') . 'resources/';
			$files = array(
				'jsbn/jsbn.js',
				'jsbn/prng4.js',
				'jsbn/rng.js',
				'jsbn/rsa.js',
				'jsbn/base64.js',
				'BackendLoginFormRsaEncryption.js'
			);
			foreach ($files as $file) {
				$pageRenderer->addJsFile($javascriptPath . $file);
			}

			return '<form action="index.php" id="typo3-login-form" method="post" name="loginform">';
		}
		return $form;
	}
}
