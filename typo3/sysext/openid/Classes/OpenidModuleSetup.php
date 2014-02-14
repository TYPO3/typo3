<?php
namespace TYPO3\CMS\Openid;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Steffen Gebert <steffen@steffen-gebert.de>
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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class is the OpenID return script for the TYPO3 Backend (used in the user-settings module).
 *
 * @author Steffen Gebert <steffen@steffen-gebert.de>
 */
class OpenidModuleSetup {

	/**
	 * Checks weather BE user has access to change its OpenID identifier
	 *
	 * @param array $config: Configuration of the field
	 * @return boolean Whether it is allowed to modify the given field
	 */
	public function accessLevelCheck(array $config) {
		$setupConfig = $GLOBALS['BE_USER']->getTSConfigProp('setup.fields');
		if (isset($setupConfig['tx_openid_openid.']['disabled']) && $setupConfig['tx_openid_openid.']['disabled']) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Render OpenID identifier field for user setup
	 *
	 * @param array $parameters Configuration of the field
	 * @param \TYPO3\CMS\Setup\Controller\SetupModuleController $parent: The calling parent object
	 * @return string HTML input field to change the OpenId
	 */
	public function renderOpenID(array $parameters, \TYPO3\CMS\Setup\Controller\SetupModuleController $parent) {
		$openid = $GLOBALS['BE_USER']->user['tx_openid_openid'];
		$add = htmlspecialchars(
			$GLOBALS['LANG']->sL('LLL:EXT:openid/Resources/Private/Language/Wizard.xlf:addopenid')
		);

		return '<input id="field_tx_openid_openid"' .
			$GLOBALS['TBE_TEMPLATE']->formWidth(20) .
			' type="text" name="data[be_users][tx_openid_openid]"' .
			' value="' . htmlspecialchars($openid) . '" />' .
			'&nbsp;&nbsp;<a href="#" onclick="' .
			'vHWin=window.open(' . GeneralUtility::quoteJSvalue(BackendUtility::getModuleUrl('wizard_openid') . '&P[itemName]=data%5Bbe_users%5D%5Btx_openid_openid%5D') .
			',null,\'width=600,height=400,status=0,menubar=0,scrollbars=0\');' .
			'vHWin.focus();return false;' .
			'">' .
			'<img src="../typo3/sysext/openid/ext_icon.gif" alt="' . $add . '" title="' . $add . '"/>' .
			'</a>';
	}

}
