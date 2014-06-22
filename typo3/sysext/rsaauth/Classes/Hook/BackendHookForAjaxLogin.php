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
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * This class adds RSA JavaScript to the backend
 */
class BackendHookForAjaxLogin {
	/**
	 * Adds RSA-specific JavaScript
	 *
	 * @param array $configuration
	 * @param \TYPO3\CMS\Backend\Controller\BackendController $backendController
	 * @return void
	 */
	public function addRsaJsLibraries(array $configuration, \TYPO3\CMS\Backend\Controller\BackendController $backendController) {
		$javascriptPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('rsaauth') . 'resources/';
		$files = array(
			'jsbn/jsbn.js',
			'jsbn/prng4.js',
			'jsbn/rng.js',
			'jsbn/rsa.js',
			'jsbn/base64.js'
		);
		foreach ($files as $file) {
			$backendController->getPageRenderer()->addJsLibrary($file, $javascriptPath . $file);
		}
	}
}
