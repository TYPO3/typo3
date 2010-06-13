<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2010  Steffen Ritter (info@rs-websystems.de)
*           2009  Benjamin Mack (benni@typo3.org)
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
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Contains the update class for old static templates. Used by the update wizard in the install tool.
 *
 * @author	Steffen Ritter <info@rs-websystems.de>
 * @author      Benjamin Mack <benni@typo3.org>
 */
class tx_coreupdates_statictemplates {
	public $versionNumber;	// version number coming from t3lib_div::int_from_ver()
	public $pObj;	// parent object (tx_install)
	public $userInput;	// user input


	/**
	 * Checks if there are any references to static_templates and an update is needed.
	 *
	 * @param	string		&$description: The description for the update
	 * @return	boolean		whether an update is needed (true) or not (false)
	 */
	public function checkForUpdate(&$description) {
		$description = '<strong>Check dependencies / references to old TypoScript templates in table static_template.</strong><br />
		This updater checks if you are using the old TypoScript static templates. These are extracted into its own extension "statictemplates". If you need them, this updater will install this extension.<br /><br />';
		if ($this->versionNumber >= 4004000 && !t3lib_extMgm::isLoaded('statictemplates')) {
			$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
				'*',
				'sys_refindex',
				'ref_table = "static_template" AND tablename != "static_template" AND deleted=0'
			);
			if ($count) {
				$description .= '<strong style="color:#f00">Dependencies found! You MUST install the extenion "statictemplates"!</strong>';
				return TRUE;
			}
			else {
				$description .= 'No Dependencies found! You may use the COMPARE - Tool to delete the static_template table.';
			}
		}
		return FALSE;
	}

	/**
	 * second step: get user info
	 *
	 * @param	string		input prefix, all names of form fields have to start with this. Append custom name in [ ... ]
	 * @return	string		HTML output
	 */
	public function getUserInput($inputPrefix) {
		$content = '<strong>Install the system extension</strong><br />You are about to install the extension "statictemplates". Make sure it is available in the TYPO3 source.';

		return $content;
	}

	/**
	 * performs the action of the UpdateManager
	 *
	 * @param	array		&$dbQueries: queries done in this update
	 * @param	mixed		&$customMessages: custom messages
	 * @return	bool		whether everything went smoothly or not
	 */
	public function performUpdate(array &$dbQueries, &$customMessages) {
		if ($this->versionNumber >= 4004000 && !t3lib_extMgm::isLoaded('statictemplates')) {
			// check wether the table can be truncated or if sysext with tca has to be installed
			if ($this->checkForUpdate($customMessages[])) {
				$localconf = $this->pObj->writeToLocalconf_control();
				$this->pObj->setValueInLocalconfFile($localconf, '$TYPO3_CONF_VARS[\'EXT\'][\'extList\']', $GLOBALS['TYPO3_CONF_VARS']['EXT']['extList'] . ',statictemplates');
				$message = $this->pObj->writeToLocalconf_control($localconf);
				if ($message == 'continue') {
					$customMessages[] = 'System Extension "statictemplates" was succesfully loaded, static templates are now supported.';
					return TRUE;
				} else { 
					return FALSE;	// something went wrong
				}
			}
			return TRUE;
		}
	}
}
?>