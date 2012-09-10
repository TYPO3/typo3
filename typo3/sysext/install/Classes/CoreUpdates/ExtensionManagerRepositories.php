<?php
namespace TYPO3\CMS\Install\CoreUpdates;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Georg Ringer <typo3@ringerge.org>
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
 * Contains the update class for filling the basic repository record of the extension manager
 *
 * @author Georg Ringer <typo3@ringerge.org>
 */
class ExtensionManagerRepositories extends \TYPO3\CMS\Install\Updates\AbstractUpdate {

	protected $title = 'Add the default extension manager repository';

	/**
	 * Checks if an update is needed
	 *
	 * @param string &$description: The description for the update
	 * @return boolean Whether an update is needed (TRUE) or not (FALSE)
	 */
	public function checkForUpdate(&$description) {
		$result = FALSE;
		$description = 'Add the default extension manager repository to the database.';

		$databaseTables = $GLOBALS['TYPO3_DB']->admin_get_tables();
		if (!isset($databaseTables['tx_extensionmanager_domain_model_repository'])) {
			$result = TRUE;
		} else {
			$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'tx_extensionmanager_domain_model_repository');
			if ($count === 0) {
				$result = TRUE;
			}
		}
		return $result;
	}

	/**
	 * Performs the database update.
	 *
	 * @param array &$dbQueries: queries done in this update
	 * @param mixed &$customMessages: custom messages
	 * @return boolean Whether it worked (TRUE) or not (FALSE)
	 */
	public function performUpdate(array &$dbQueries, &$customMessages) {
		$result = FALSE;
		$sqlFile = \TYPO3\CMS\Core\Extension\ExtensionManager::extPath('extensionmanager') . DIRECTORY_SEPARATOR . 'ext_tables_static+adt.sql';
		$sqlStatements = explode(';', \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($sqlFile));

		foreach ($sqlStatements as $sqlStatement) {
			if (trim($sqlStatement) !== '') {
				$res = $GLOBALS['TYPO3_DB']->sql_query($sqlStatement);
				$dbQueries[] = $sqlStatement;
				if ($GLOBALS['TYPO3_DB']->sql_error()) {
					$customMessages = 'SQL-ERROR: ' . htmlspecialchars($GLOBALS['TYPO3_DB']->sql_error());
				} else {
					$result = TRUE;
				}
			}
		}
		return $result;
	}

}

?>