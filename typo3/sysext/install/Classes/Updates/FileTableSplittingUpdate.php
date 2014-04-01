<?php
namespace TYPO3\CMS\Install\Updates;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Steffen Ritter <steffen.ritter@typo3.org>
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
 *  A copy is found in the text file GPL.txt and important notices to the license
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
 * Migrate metadata from sys_file table to sys_filemetadata.
 * Also takes care of custom TCA fields if they have been created beforehand.
 *
 * @author Steffen Ritter <steffen.ritter@typo3.org>
 */
class FileTableSplittingUpdate extends AbstractUpdate {

	/**
	 * The table the metadata is to be stored in
	 * @var string
	 */
	protected $metaDataTable = 'sys_file_metadata';

	/**
	 * @var string
	 */
	protected $title = 'Migrate file metadata from sys_file to an external metadata table';

	/**
	 * Checks if an update is needed
	 *
	 * @param string &$description The description for the update
	 * @return boolean Whether an update is needed (TRUE) or not (FALSE)
	 */
	public function checkForUpdate(&$description) {
		$result = FALSE;

		$description = 'In TYPO3 CMS 6.2 LTS the metadata has been split off to an external table. This wizard will migrate the data. If you have extended the sys_file table manually your custom data will be included, too, if you create TCA and columns in sys_file_metadata before running this wizard.';

		if (!array_key_exists($this->metaDataTable, $GLOBALS['TYPO3_DB']->admin_get_tables())) {
			$result = TRUE;
		} else {
			$fields = $GLOBALS['TYPO3_DB']->admin_get_fields('sys_file');
			// Check if the field exists on sys_file if not there is no data to migrate (TYPO3 < 6.0 or >= 6.2)
			if (isset($fields['width']) && isset($fields['height'])) {
				// Check if the 1:1 integrity is ok, if not we need to migrate the metadata.
				$sysFileCount = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('uid', 'sys_file');
				$sysFileMetaDataCount = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('uid', $this->metaDataTable);
				$result = $sysFileCount > $sysFileMetaDataCount;
			}
		}

		return $result;
	}

	/**
	 * Performs the database update. Won't run if the table is not present.
	 * Will stop if the table does not exist to give users the possibility to
	 * migrate custom fields to and therefore move their TCA and sql upfront.
	 *
	 * @param array &$dbQueries Queries done in this update
	 * @param mixed &$customMessages Custom messages
	 * @return boolean Whether it worked (TRUE) or not (FALSE)
	 */
	public function performUpdate(array &$dbQueries, &$customMessages) {

		if (!array_key_exists($this->metaDataTable, $GLOBALS['TYPO3_DB']->admin_get_tables())) {
			$customMessages = 'ERROR! Make sure you already created the table. If you added custom metadata to sys_file table add TCA ' .
				'configuration as well as SQL definitions to sys_file_metadata, too.';
			return FALSE;
		}

		$fieldsToMigrate = $this->detectFieldsToMigrate();
		if (empty($fieldsToMigrate)) {
			return TRUE;
		}
		$fieldsToWrite = $fieldsToMigrate;
		$fieldsToWrite[] = 'file';
		$fieldsToSelect = $fieldsToMigrate;
		$fieldsToSelect[] = 'uid AS file';

		$resultObject = $GLOBALS['TYPO3_DB']->sql_query(
				'INSERT INTO ' . $this->metaDataTable . ' (' . implode(',', $fieldsToWrite) . ')' .
				' SELECT ' . implode(',', $fieldsToSelect) .
				' FROM sys_file' .
				' WHERE uid NOT IN (SELECT file FROM ' . $this->metaDataTable . ')');

		return $resultObject !== FALSE;
	}

	/**
	 * Looks at the table sql definitions and checks which fields are present in both tables.
	 * ignories some Management field
	 *
	 * @return array
	 */
	protected function detectFieldsToMigrate() {
		$fieldsBlackListed = array('uid', 'deleted', 'sys_language_uid');
		$fieldsInSysFile = array_keys($GLOBALS['TYPO3_DB']->admin_get_fields('sys_file'));
		$fieldsInSysFileMetaData = array_keys($GLOBALS['TYPO3_DB']->admin_get_fields($this->metaDataTable));

		$commonFields = array_intersect($fieldsInSysFileMetaData, $fieldsInSysFile);
		$commonFields = array_diff($commonFields, $fieldsBlackListed);

		return $commonFields;

	}
}
