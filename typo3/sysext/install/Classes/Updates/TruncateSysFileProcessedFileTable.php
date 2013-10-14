<?php
namespace TYPO3\CMS\Install\Updates;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Wouter Wolters <typo3@wouterwolters.nl>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Upgrade wizard which will truncate the sys_file_processedfile table
 */
class TruncateSysFileProcessedFileTable extends AbstractUpdate {

	/**
	 * @var string
	 */
	protected $title = 'Truncate all processed files to clean up obsolete records.';

	/**
	 * Checks whether updates are required.
	 *
	 * @param string &$description The description for the update
	 * @return boolean Whether an update is required (TRUE) or not (FALSE)
	 */
	public function checkForUpdate(&$description) {
		if ($this->isWizardDone() || !$this->checkIfTableExists('sys_file_processedfile')) {
			return FALSE;
		}

		$description = 'To re-process all files correctly we truncate the table. This will make sure there are no obsolete files in the database.';
		return TRUE;
	}

	/**
	 * Performs the accordant updates.
	 *
	 * @param array &$databaseQueries Queries done in this update
	 * @param mixed &$customMessages Custom messages
	 * @return boolean Whether everything went smoothly or not
	 */
	public function performUpdate(array &$databaseQueries, &$customMessages) {
		$GLOBALS['TYPO3_DB']->exec_TRUNCATEquery('sys_file_processedfile');
		$databaseQueries[] = $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery;
		$this->markWizardAsDone();
		return TRUE;
	}

}
?>