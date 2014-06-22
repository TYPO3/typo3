<?php
namespace TYPO3\CMS\Install\Updates;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

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