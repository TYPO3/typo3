<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Steffen Gebert <steffen@steffen-gebert.de>
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
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */

/**
 * This class is the OpenID return script for the TYPO3 Backend.
 *
 * $Id$
 *
 * @author	Steffen Gebert <steffen@steffen-gebert.de>
 */
class tx_openid_mod_setup {

	/**
	 * Checks weather BE user has access to change its OpenID identifier
	 *
	 * @param $config	config of the field
	 * @return boolean	TRUE if user has access, false if not
	 */
	public function accessLevelCheck($config) {
		$setupConfig = $GLOBALS['BE_USER']->getTSConfigProp('setup.fields');
		if (isset($setupConfig['tx_openid_openid.']['disabled']) && $setupConfig['tx_openid_openid.']['disabled'] == 1) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Render OpenID identifier field for user setup
	 *
	 * @param $params	config of the field
	 * @param $ref		$class reference
	 * @return	HTML code for input field or only OpenID if change not allowed
	 */
	public function renderOpenID($params, $ref) {
		$openid = $GLOBALS['BE_USER']->user['tx_openid_openid'];
		return '<input id="field_tx_openid_openid" type="text" name="data[be_users][tx_openid_openid]" value="' . $openid . '" style="width:192px;" />';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/openid/class.tx_openid_mod_setup.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/openid/class.tx_openid_mod_setup.php']);
}
?>