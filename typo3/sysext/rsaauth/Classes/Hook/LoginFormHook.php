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
