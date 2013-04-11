<?php
namespace TYPO3\CMS\Install\Updates;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Benjamin Mack <benni@typo3.org>
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
 * @author 	Benjamin Mack <benni@typo3.org>
 */
abstract class AbstractUpdate {

	/**
	 * the human-readable title of the upgrade wizard
	 */
	protected $title;

	/**
	 * parent object
	 *
	 * @var \TYPO3\CMS\Install\Installer
	 */
	public $pObj;

	/**
	 * user input, set from outside
	 */
	public $userInput;

	/**
	 * current TYPO3 version number, set from outside
	 * version number coming from \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger()
	 */
	public $versionNumber;

	/**
	 * Helper functions, getters and setters
	 */
	/**
	 * returns the title attribute
	 *
	 * @return 	the title of this update wizard
	 */
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
	 * @param 	$title	the title of this update wizard
	 * @return 	void
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * returns the identifier of this class
	 *
	 * @return 	the identifier of this update wizard
	 */
	public function getIdentifier() {
		return $this->identifier;
	}

	/**
	 * sets the identifier attribute
	 *
	 * @param 	$identifier	the identifier of this update wizard
	 * @return 	void
	 */
	public function setIdentifier($identifier) {
		$this->identifier = $identifier;
	}

	/**
	 * simple wrapper function that helps dealing with the compatibility
	 * layer that some update wizards don't have a second parameter
	 * thus, it evaluates everything already
	 *
	 * @return 	boolean	if the wizard should be shown at all on the overview page
	 * @see checkForUpdate()
	 */
	public function shouldRenderWizard() {
		$showUpdate = 0;
		$explanation = '';
		$res = $this->checkForUpdate($explanation, $showUpdate);
		return $showUpdate > 0 || $res == TRUE;
	}

	/**
	 * simple wrapper function that helps to check whether (if)
	 * this feature is cool if you want to tell the user that the update wizard
	 * is working fine, just as output (useful for the character set / utf8 wizard)
	 *
	 * @return 	boolean	if the wizard should render the Next() button on the overview page
	 * @see checkForUpdate()
	 */
	public function shouldRenderNextButton() {
		$showUpdate = 0;
		$explanation = '';
		$res = $this->checkForUpdate($explanation, $showUpdate);
		return $showUpdate != 2 || $res == TRUE;
	}

	/**
	 * Checks whether updates are required.
	 *
	 * @param string &$description: The description for the update
	 * @return boolean Whether an update is required (TRUE) or not (FALSE)
	 */
	abstract public function checkForUpdate(&$description);

	/**
	 * Performs the accordant updates.
	 *
	 * @param array &$dbQueries: queries done in this update
	 * @param mixed &$customMessages: custom messages
	 * @return boolean Whether everything went smoothly or not
	 */
	abstract public function performUpdate(array &$dbQueries, &$customMessages);

	/**
	 * This method can be called to install extensions following all proper processes
	 * (e.g. installing in extList, respecting priority, etc.)
	 *
	 * @param array $extensionKeys List of keys of extensions to install
	 * @return void
	 */
	protected function installExtensions($extensionKeys) {
		/** @var $installUtility \TYPO3\CMS\Extensionmanager\Utility\InstallUtility */
		$installUtility = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Extensionmanager\\Utility\\InstallUtility'
		);
		foreach ($extensionKeys as $extension) {
			$installUtility->install($extension);
		}
	}

	/**
	 * Marks some wizard as being "seen" so that it not shown again.
	 *
	 * Writes the info in LocalConfiguration.php
	 *
	 * @param mixed $confValue The configuration is set to this value
	 * @return void
	 */
	protected function markWizardAsDone($confValue = 1) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager')->setLocalConfigurationValueByPath('INSTALL/wizardDone/' . get_class($this), $confValue);
	}

	/**
	 * Checks if this wizard has been "done" before
	 *
	 * @return boolean TRUE if wizard has been done before, FALSE otherwise
	 */
	protected function isWizardDone() {
		$wizardClassName = get_class($this);
		$done = FALSE;
		if (isset($GLOBALS['TYPO3_CONF_VARS']['INSTALL']['wizardDone'][$wizardClassName]) && $GLOBALS['TYPO3_CONF_VARS']['INSTALL']['wizardDone'][$wizardClassName]) {
			$done = TRUE;
		}
		return $done;
	}

}


?>