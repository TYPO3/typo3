<?php
namespace TYPO3\CMS\Openid;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * This class is the OpenID return script for the TYPO3 Backend (used in the user-settings module).
 */
class OpenidModuleSetup {

	/**
	 * Checks weather BE user has access to change its OpenID identifier
	 *
	 * @return bool Whether it is allowed to modify the given field
	 */
	public function accessLevelCheck() {
		$setupConfig = $this->getBackendUser()->getTSConfigProp('setup.fields');
		return empty($setupConfig['tx_openid_openid.']['disabled']);
	}

	/**
	 * Render OpenID identifier field for user setup
	 *
	 * @return string HTML input field to change the OpenId
	 */
	public function renderOpenID() {
		$openid = $this->getBackendUser()->user['tx_openid_openid'];
		$add = htmlspecialchars(
			$this->getLanguageService()->sL('LLL:EXT:openid/Resources/Private/Language/locallang.xlf:addopenid')
		);

		$parameters = ['P[itemName]' => 'data[be_users][tx_openid_openid]'];
		$popUpUrl = GeneralUtility::quoteJSvalue(BackendUtility::getModuleUrl('wizard_openid', $parameters));
		return '<div class="input-group">' .
			'<input id="field_tx_openid_openid"' .
			' class="form-control"' .
			' type="text" name="data[be_users][tx_openid_openid]"' .
			' value="' . htmlspecialchars($openid) . '" />' .
			'<div class="input-group-addon">' .
				'<a href="#" onclick="' .
				'vHWin=window.open(' . $popUpUrl . ',null,\'width=800,height=600,status=0,menubar=0,scrollbars=0\');' .
				'vHWin.focus();return false;' .
				'">' .
					'<img src="../typo3/sysext/openid/ext_icon_small.png" alt="' . $add . '" title="' . $add . '"/>' .
				'</a>' .
			'</div>' .
			'</div>';
	}

	/**
	 * @return BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}

	/**
	 * @return LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}
}
