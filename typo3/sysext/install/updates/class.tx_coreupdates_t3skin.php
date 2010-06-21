<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010  Steffen Ritter (info@rs-websystems.de)
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
 * Contains the update class for not installed t3skin. Used by the update wizard in the install tool.
 *
 * @author	Steffen Ritter <info@rs-websystems.de>
 */
class tx_coreupdates_t3skin {
	public $versionNumber;	// version number coming from t3lib_div::int_from_ver()
	public $pObj;	// parent object (tx_install)
	public $userInput;	// user input


	/**
	 * Checks if t3skin is not installed.
	 *
	 * @param	string		&$description: The description for the update
	 * @return	boolean		whether an update is needed (true) or not (false)
	 */
	public function checkForUpdate(&$description) {
		$result = FALSE;
		$description = '<strong>The backend skin "t3skin" is not loaded.</strong>
		TYPO3 4.4 introduced many changes in backend skinning and old backend skins are now incompatible.
		<strong>Without "t3skin" the backend may be unusable.</strong> Install extension "t3skin".';
		if (!t3lib_extMgm::isLoaded('t3skin')) {
			$result = TRUE;
		}
		return $result;
	}

	/**
	 * second step: get user info
	 *
	 * @param	string		input prefix, all names of form fields have to start with this. Append custom name in [ ... ]
	 * @return	string		HTML output
	 */
	public function getUserInput($inputPrefix) {
		$content = '<strong>Install the system extension</strong><br />You are about to install the extension "t3skin".';

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
		$result = FALSE;
		if ($this->versionNumber >= 4004000 && !t3lib_extMgm::isLoaded('t3skin')) {
			// check wether the table can be truncated or if sysext with tca has to be installed
			if ($this->checkForUpdate($customMessages[])) {
				$extList = $this->addExtToList(array('t3skin'));
				if ($extList) {
					$message = $this->writeNewExtensionList($extList);
				}

				if ($message == 'continue') {
					$customMessages = 'The system extension "t3skin" was succesfully loaded.';
					$result = TRUE;
				}
				}
			}
		return $result;
	}

	/**
	 * Adds extension to extension list and returns new list. If -1 is returned, an error happend.
	 * Does NOT check dependencies yet.
	 *
	 * @param	array		Extension keys to add
	 * @return	string		New list of installed extensions or -1 if error
	 */
	protected function addExtToList(array $extKeys) {
			// Get list of installed extensions and add this one.
		$tmpLoadedExt = $GLOBALS['TYPO3_LOADED_EXT'];
		if (isset($tmpLoadedExt['_CACHEFILE'])) {
			unset($tmpLoadedExt['_CACHEFILE']);
		}

		$listArr = array_keys($tmpLoadedExt);
		$listArr = array_merge($listArr, $extKeys);

			// Implode unique list of extensions to load and return:
		return implode(',', array_unique($listArr));
		}


	/**
	 * Writes the extension list to "localconf.php" file
	 * Removes the temp_CACHED* files before return.
	 *
	 * @param	string		List of extensions
	 * @return	string		Result of writeToLocalconf_control()
	 */
	protected function writeNewExtensionList($newExtList)	{
			// Instance of install tool
		$instObj = new t3lib_install;
		$instObj->allowUpdateLocalConf = 1;
		$instObj->updateIdentity = 'TYPO3 Core Update Manager';

			// Get lines from localconf file
		$lines = $instObj->writeToLocalconf_control();
		$instObj->setValueInLocalconfFile($lines, '$TYPO3_CONF_VARS[\'EXT\'][\'extList\']', $newExtList);
		$result = $instObj->writeToLocalconf_control($lines);

		$GLOBALS['TYPO3_CONF_VARS']['EXT']['extList'] = $newExtList;
		t3lib_extMgm::removeCacheFiles();

		return $result;
	}
}
?>