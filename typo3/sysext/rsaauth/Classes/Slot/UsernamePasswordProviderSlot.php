<?php
namespace TYPO3\CMS\Rsaauth\Slot;

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
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Class UsernamePasswordProviderSlot
 */
class UsernamePasswordProviderSlot {

	/**
	 * @param PageRenderer $pageRenderer
	 */
	public function getPageRenderer(PageRenderer $pageRenderer) {
		$loginSecurityLevel = trim($GLOBALS['TYPO3_CONF_VARS']['BE']['loginSecurityLevel']) ?: 'normal';
		if ($loginSecurityLevel === 'rsa') {
			$javascriptPath = '../' . ExtensionManagementUtility::siteRelPath('rsaauth') . 'Resources/Public/JavaScript/';
			$files = array(
				'jsbn/jsbn.js',
				'jsbn/prng4.js',
				'jsbn/rng.js',
				'jsbn/rsa.js',
				'jsbn/base64.js'
			);
			foreach ($files as $file) {
				$pageRenderer->addJsFile($javascriptPath . $file);
			}

			$pageRenderer->loadRequireJsModule('TYPO3/CMS/Rsaauth/BackendLoginFormRsaEncryption');
		}
	}
}
