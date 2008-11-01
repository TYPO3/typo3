<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Benjamin Mack <benni@typo3.org>
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
 * Contains the update class for adding the system extension "simulate static".
 *
 * $Id$
 *
 * @author Benjamin Mack <benni@typo3.org>
 */
class tx_coreupdates_installsysexts {
	public $versionNumber;	// version number coming from t3lib_div::int_from_ver()

	/**
	 * parent object
	 *
	 * @var tx_install
	 */
	public $pObj;
	public $userInput;	// user input


	/**
	 * Checks if an update is needed
	 *
	 * @param	string		&$description: The description for the update
	 * @return	boolean		whether an update is needed (true) or not (false)
	 */
	public function checkForUpdate(&$description) {
		$result = false;
		$description = 'Installs the System Extension "simulatestatic" if you do not want to use RealURL or CoolURI but still want the Speaking URL feature. This feature was moved from the core to an extension and can be installed if used. If you used "config.simulatestaticdocuments = 1" in this installation before, you should install this system extension. Be sure to read the manual of "simulatestatic".';

		if (!t3lib_extMgm::isLoaded('simulatestatic')) {
			$result = true;
		}
		return $result;
	}


	/**
	 * Adds the extension "simulate static" to the extList in TYPO3_CONF_VARS
	 *
	 * @param	array		&$dbQueries: queries done in this update
	 * @param	mixed		&$customMessages: custom messages
	 * @return	boolean		whether it worked (true) or not (false)
	 */
	public function performUpdate(&$dbQueries, &$customMessages) {
		$result = false;
		$extList = $this->addExtToList('simulatestatic');
		if ($extList) {
			$this->writeNewExtensionList($extList);
			$result = true;
		}
		return $result;
	}


	/**
	 * Adds extension to extension list and returns new list. If -1 is returned, an error happend.
	 * Does NOT check dependencies yet.
	 *
	 * @param	string		Extension key
	 * @return	string		New list of installed extensions or -1 if error
	 */
	function addExtToList($extKey) {
			// Get list of installed extensions and add this one.
		$listArr = array_keys($GLOBALS['TYPO3_LOADED_EXT']);
		$listArr[] = $extKey;

			// Implode unique list of extensions to load and return:
		return implode(',', array_unique($listArr));
	}


	/**
	 * Writes the extension list to "localconf.php" file
	 * Removes the temp_CACHED* files before return.
	 *
	 * @param	string		List of extensions
	 * @return	void
	 */
	protected function writeNewExtensionList($newExtList)	{
			// Instance of install tool
		$instObj = new t3lib_install;
		$instObj->allowUpdateLocalConf = 1;
		$instObj->updateIdentity = 'TYPO3 Core Update Manager';

			// Get lines from localconf file
		$lines = $instObj->writeToLocalconf_control();
		$instObj->setValueInLocalconfFile($lines, '$TYPO3_CONF_VARS[\'EXT\'][\'extList\']', $newExtList);
		$instObj->writeToLocalconf_control($lines);

		$GLOBALS['TYPO3_CONF_VARS']['EXT']['extList'] = $newExtList;
		t3lib_extMgm::removeCacheFiles();
	}
}
?>