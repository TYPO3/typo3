<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011 Benjamin Mack <benni@typo3.org>
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
 * Generic class that every update wizard class inherits from.
 * Used by the update wizard in the install tool.
 *
 * @author	Benjamin Mack <benni@typo3.org>
 */
abstract class Tx_Install_Updates_Base {

	/**
	 * the human-readable title of the upgrade wizard
	 */
	protected $title;

	/**
	 * parent object
	 * @var tx_install
	 */
	public $pObj;

	/**
	 * user input, set from outside
	 */
	public $userInput;

	/**
	 * current TYPO3 version number, set from outside
	 * version number coming from t3lib_div::int_from_ver()
	 */
	public $versionNumber;



	/**
	 *
	 * API functions
	 *
	 **/

	/**
	 * The first function in the update wizard steps
	 *
	 * it works like this:
	 * @param	$explanation	string	HTML that is outputted on the first
	 * @param	$showUpdate		int	that informs you whether to show this update wizard or not. Possible values that checkForUpdate() should set:
	 * 			0 = don't show this update wizard at all (because it's not needed)
	 * 			1 = show the update wizard (explanation + next step button)
	 * 			2 = show the update wizard (explanation but not the "next step" button), useful for showing a status of a wizard
	 * @return	deprecated since TYPO3 4.5, in previous versions it was used to determine whether the update wizards should be shown, now, the $showUpdate parameter is used for that
	 */
	// public abstract function checkForUpdate(&$explanation, &$showUpdate);


	/**
	 * second step: get user input if needed
	 *
	 * @param	string	input prefix, all names of form fields have to start with this. Append custom name in [ ... ]
	 * @return	string	HTML output
	 */
	// public abstract function getUserInput($inputPrefix);


	/**
	 * third step: do the updates
	 *
	 * @param	array		&$dbQueries: queries done in this update
	 * @param	mixed		&$customMessages: custom messages
	 * @return	boolean		whether it worked (TRUE) or not (FALSE)
	 */
	// public abstract function performUpdate(&$dbQueries, &$customMessages);

	/**
	 * Checks if user input is valid
	 *
	 * @param	string		pointer to output custom messages
	 * @return	boolean		TRUE if user input is correct, then the update is performed. When FALSE, return to getUserInput
	 */
	// public abstract function checkUserInput(&$customMessages);





	/**
	 *
	 * Helper functions, getters and setters
	 *
	 **/

	/**
	 * returns the title attribute
	 * 
	 * @return	the title of this update wizard
	 **/
	public function getTitle() {
		if ($this->title) {
			return $this->title;
		} else {
			return $this->identifier;
		}
	}

	/**
	 * sets the title attribute
	 * 
	 * @param	$title	the title of this update wizard
	 * @return	void
	 **/
	public function setTitle($title) {
		$this->title = $title;
	}


	/**
	 * returns the identifier of this class
	 * 
	 * @return	the identifier of this update wizard
	 **/
	public function getIdentifier() {
		return $this->identifier;
	}

	/**
	 * sets the identifier attribute
	 * 
	 * @param	$identifier	the identifier of this update wizard
	 * @return	void
	 **/
	public function setIdentifier($identifier) {
		$this->identifier = $identifier;
	}

	/**
	 * simple wrapper function that helps dealing with the compatibility
	 * layer that some update wizards don't have a second parameter
	 * thus, it evaluates everything already
	 *
	 * @return	boolean	if the wizard should be shown at all on the overview page
	 * @see checkForUpdate()
	 */
	public function shouldRenderWizard() {
		$showUpdate = 0;
		$explanation = '';
		$res = $this->checkForUpdate($explanation, $showUpdate);
		return ($showUpdate > 0 || $res == TRUE);
	}

	/**
	 * simple wrapper function that helps to check whether (if)
	 * this feature is cool if you want to tell the user that the update wizard
	 * is working fine, just as output (useful for the character set / utf8 wizard)
	 *
	 * @return	boolean	if the wizard should render the Next() button on the overview page
	 * @see checkForUpdate()
	 */
	public function shouldRenderNextButton() {
		$showUpdate = 0;
		$explanation = '';
		$res = $this->checkForUpdate($explanation, $showUpdate);
		return ($showUpdate != 2 || $res == TRUE);
	}

	/**
	 * This method creates an instance of a connection to the Extension Manager
	 * and returns it. This is used when installing an extension.
	 * 
	 * @return tx_em_Connection_ExtDirectServer EM connection instance
	 */
	public function getExtensionManagerConnection() {
			// Create an instance of language, if necessary.
			// Needed in order to make the em_index work
		if (!is_object($GLOBALS['LANG'])) {
			$GLOBALS['LANG'] = t3lib_div::makeInstance('language');
			$GLOBALS['LANG']->csConvObj = t3lib_div::makeInstance('t3lib_cs');
		}
			// Create an instance of a connection class to the EM
		$extensionManagerConnection = t3lib_div::makeInstance('tx_em_Connection_ExtDirectServer', FALSE);
		return $extensionManagerConnection;
	}

	/**
	 * This method can be called to install extensions following all proper processes
	 * (e.g. installing in both extList and extList_FE, respecting priority, etc.)
	 *
	 * @param array $extensionKeys List of keys of extensions to install
	 * @return void
	 */
	protected function installExtensions($extensionKeys) {
		$extensionManagerConnection = $this->getExtensionManagerConnection();
		foreach ($extensionKeys as $extension) {
			$extensionManagerConnection->enableExtension($extension);
		}
	}

	/**
	 * Marks some wizard as being "seen" so that it not shown again.
	 *
	 * Writes the info in localconf.php
	 *
	 * @return void
	 */
	protected function markWizardAsDone() {
		/** @var t3lib_install $install */
		$install = t3lib_div::makeInstance('t3lib_install');
		$install->allowUpdateLocalConf = 1;
		$install->updateIdentity = 'TYPO3 Upgrade Wizard';
		// Get lines from localconf file
		$lines = $install->writeToLocalconf_control();
		$wizardClassName = get_class($this);
		$install->setValueInLocalconfFile($lines, '$TYPO3_CONF_VARS[\'INSTALL\'][\'wizardDone\'][\'' . $wizardClassName . '\']', 1);
		$install->writeToLocalconf_control($lines);
	}

	/**
	 * Checks if this wizard has been "done" before
	 *
	 * @return boolean TRUE if wizard has been done before, FALSE otherwise
	 */
	protected function isWizardDone() {
		$wizardClassName = get_class($this);
		$done = FALSE;
		if (isset($GLOBALS['TYPO3_CONF_VARS']['INSTALL']['wizardDone'][$wizardClassName]) &&
			$GLOBALS['TYPO3_CONF_VARS']['INSTALL']['wizardDone'][$wizardClassName]) {
			$done = TRUE;
		}
		return $done;
	}
}
?>