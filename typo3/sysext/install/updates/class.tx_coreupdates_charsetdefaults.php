<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011 Michael Stucki <michael@typo3.org>, Benjamin Mack <benni@typo3.org>
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
 * Displays warnings and information about the database character set
 */
class tx_coreupdates_charsetDefaults extends Tx_Install_Updates_Base {
	protected $title = 'Database Character Set';

	/**
	 * Checks if the configuration is relying on old default values or not.
	 * If needed, this updater will fix the configuration appropriately.
	 *
	 * @param	string		&$description: The description for the update
	 * @param	string		&$showUpdate: 0=dont show update; 1=show update and next button; 2=only show description
	 * @return	boolean		whether an update is needed (true) or not (FALSE)
	 */
	public function checkForUpdate(&$description, &$showUpdate = FALSE) {
		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] === '-1' ||
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['setDBinit'] === '-1') {

			$description = 'The configuration variables $TYPO3_CONF_VARS[\'SYS\'][\'setDBinit\'] and/or
				$TYPO3_CONF_VARS[\'BE\'][\'forceCharset\'] are relying on empty default values.<br />
				However, the defaults for both values have changed in TYPO3 4.5.<br /><br />
				Please click "Next" to write the former default settings to your localconf.php,
				so that your setup will continue to work like before.';
			$showUpdate = 1;
		}
	}


	/**
	 * Write the current configuration to localconf.php
	 * This is needed for any sites that were relying on the former default
	 * values which are going to change in TYPO3 4.5.
	 *
	 * @param	array		&$dbQueries: queries done in this update
	 * @param	mixed		&$customMessages: custom messages
	 * @return	boolean		whether the updated was made or not
	 */
	public function performUpdate(array &$dbQueries, &$customMessages) {
		$localconf = $this->pObj->writeToLocalconf_control();

			// Update "setDBinit" setting
		if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['setDBinit'] === '-1') {
			$this->pObj->setValueInLocalconfFile($localconf, '$TYPO3_CONF_VARS[\'SYS\'][\'setDBinit\']', '');
		}

			// Update the "forceCharset" setting
		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] === '-1') {
			$this->pObj->setValueInLocalconfFile($localconf, '$TYPO3_CONF_VARS[\'BE\'][\'forceCharset\']', '');
		}

		$message = $this->pObj->writeToLocalconf_control($localconf);
		if ($message == 'continue') {
			$customMessages[] = 'The configuration was successfully updated.';
			return TRUE;
		} else {
			return FALSE;   // something went wrong
		}
	}
}

?>