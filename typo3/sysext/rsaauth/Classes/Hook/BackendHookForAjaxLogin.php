<?php
namespace TYPO3\CMS\Rsaauth\Hook;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Helmut Hummel <helmut.hummel@typo3.org>
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
		$backendController->getPageRenderer()->addInlineSetting('BackendLogin.BackendLogin::getRsaPublicKey', 'ajaxUrl', BackendUtility::getAjaxUrl('BackendLogin::getRsaPublicKey'));
	}
}
