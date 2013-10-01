<?php
namespace TYPO3\CMS\Install\Updates;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Michael Stucki <michael@typo3.org>, Benjamin Mack <benni@typo3.org>
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
class CharsetDefaultsUpdate extends AbstractUpdate {

	/**
	 * @var string
	 */
	protected $title = 'Database Character Set';

	/**
	 * Checks if the configuration is relying on old default values or not.
	 * If needed, this updater will fix the configuration appropriately.
	 *
	 * @param string &$description The description for the update
	 * @param string &$showUpdate 0=don't show update; 1=show update and next button; 2=only show description
	 * @return boolean Whether an update is needed (TRUE) or not (FALSE)
	 */
	public function checkForUpdate(&$description, &$showUpdate = FALSE) {
		if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['setDBinit'] === '-1') {
			$description = 'The configuration variables $TYPO3_CONF_VARS[\'SYS\'][\'setDBinit\']
				is relying on empty default values.<br />
				However, the default has changed in TYPO3 4.5.<br /><br />
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
	 * @param array &$dbQueries Queries done in this update
	 * @param mixed &$customMessages Custom messages
	 * @return boolean Whether the updated was made or not
	 */
	public function performUpdate(array &$dbQueries, &$customMessages) {
		// Update "setDBinit" setting
		$result1 = FALSE;
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager')->getLocalConfigurationValueByPath('SYS/setDBinit') === '-1') {
			$result1 = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager')->setLocalConfigurationValueByPath('SYS/setDBinit', '');
		}
		if ($result1) {
			$customMessages[] = 'The configuration was successfully updated.';
			return TRUE;
		} else {
			return FALSE;
		}
	}

}
