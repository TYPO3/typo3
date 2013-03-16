<?php
namespace TYPO3\CMS\Install\CoreUpdates;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Ernesto Baschny <ernst@cron-it.de>
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
 * Contains the update class for transitioning from ".gif" flags to sprites
 *
 * @author Ernesto Baschny <ernst@cron-it.de>
 */
class FlagsFromSpriteUpdate extends \TYPO3\CMS\Install\Updates\AbstractUpdate {

	protected $title = 'Update Graphics, Using Sprites for sys_language Records';

	/**
	 * Checks if an update is needed
	 *
	 * @param 	string		&$description: The description for the update
	 * @return 	boolean		whether an update is needed (TRUE) or not (FALSE)
	 */
	public function checkForUpdate(&$description) {
		$result = FALSE;
		$description = 'Removes the ".gif" suffix from entries in sys_language, because flags now come from a sprite provided by t3skin and not individual .gif files.';
		if ($this->versionNumber >= 4005000) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'sys_language', 'flag LIKE \'%.gif\'', '', '', '1');
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res)) {
				$result = TRUE;
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}
		return $result;
	}

	/**
	 * Performs the database update. Changes the flags from ".gif" to flag without suffix
	 *
	 * @param 	array		&$dbQueries: queries done in this update
	 * @param 	mixed		&$customMessages: custom messages
	 * @return 	boolean		whether it worked (TRUE) or not (FALSE)
	 */
	public function performUpdate(array &$dbQueries, &$customMessages) {
		$result = FALSE;
		if ($this->versionNumber >= 4005000) {
			$sql = 'UPDATE sys_language SET flag=REPLACE(flag, \'.gif\', \'\') WHERE flag LIKE \'%.gif\'';
			$res = $GLOBALS['TYPO3_DB']->sql_query($sql);
			$dbQueries[] = $sql;
			if ($GLOBALS['TYPO3_DB']->sql_error()) {
				$customMessages = 'SQL-ERROR: ' . htmlspecialchars($GLOBALS['TYPO3_DB']->sql_error());
			} else {
				$result = TRUE;
			}
			$sql = 'UPDATE sys_language SET flag=\'multiple\' WHERE flag=\'multi-language\'';
			$res = $GLOBALS['TYPO3_DB']->sql_query($sql);
			$dbQueries[] = $sql;
			if ($GLOBALS['TYPO3_DB']->sql_error()) {
				$customMessages = 'SQL-ERROR: ' . htmlspecialchars($GLOBALS['TYPO3_DB']->sql_error());
			} else {
				$result = TRUE;
			}
		}
		return $result;
	}

}


?>