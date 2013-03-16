<?php
namespace TYPO3\CMS\Install\CoreUpdates;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013  Steffen Ritter (info@rs-websystems.de)
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
 * @author 	Steffen Ritter <info@rs-websystems.de>
 */
class T3skinUpdate extends \TYPO3\CMS\Install\Updates\AbstractUpdate {

	/**
	 * @var string
	 */
	protected $title = 'Install the new TYPO3 Skin "t3skin"';

	/**
	 * Checks if t3skin is not installed.
	 *
	 * @param 	string		&$description: The description for the update
	 * @return 	boolean		whether an update is needed (TRUE) or not (FALSE)
	 */
	public function checkForUpdate(&$description) {
		$result = FALSE;
		$description[] = '<strong>The backend skin "t3skin" is not loaded.</strong>
		TYPO3 4.4 introduced many changes in backend skinning and old backend skins are now incompatible.
		<strong>Without "t3skin" the backend may be unusable.</strong> Install extension "t3skin".';
		if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('t3skin')) {
			$result = TRUE;
		}
		return $result;
	}

	/**
	 * second step: get user info
	 *
	 * @param 	string		input prefix, all names of form fields have to start with this. Append custom name in [ ... ]
	 * @return 	string		HTML output
	 */
	public function getUserInput($inputPrefix) {
		$content = '<strong>Install the system extension</strong><br />You are about to install the extension "t3skin".';
		return $content;
	}

	/**
	 * performs the action of the UpdateManager
	 *
	 * @param 	array		&$dbQueries: queries done in this update
	 * @param 	mixed		&$customMessages: custom messages
	 * @return 	bool		whether everything went smoothly or not
	 */
	public function performUpdate(array &$dbQueries, &$customMessages) {
		$result = FALSE;
		if ($this->versionNumber >= 4004000 && !\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('t3skin')) {
			// check wether the table can be truncated or if sysext with tca has to be installed
			if ($this->checkForUpdate($customMessages)) {
				try {
					\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::loadExtension('t3skin');
					$customMessages = 'The system extension "t3skin" was successfully loaded.';
					$result = TRUE;
				} catch (\RuntimeException $e) {
					$result = FALSE;
				}
			}
		}
		return $result;
	}

}


?>