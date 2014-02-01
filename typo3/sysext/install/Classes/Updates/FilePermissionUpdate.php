<?php
namespace TYPO3\CMS\Install\Updates;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Nicole Cordes <typo3@cordes.co>
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
 * Upgrade wizard which goes through all users and groups file permissions and stores them as list in a new field.
 */
class FilePermissionUpdate extends AbstractUpdate {

	/**
	 * @var \TYPO3\CMS\Install\Service\SqlSchemaMigrationService
	 */
	protected $installToolSqlParser;

	/**
	 * @var string
	 */
	protected $title = 'Rewrite binary file permissions into detailed list';

	/**
	 * Constructor function.
	 */
	public function __construct() {
		$this->installToolSqlParser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Install\\Service\\SqlSchemaMigrationService');
	}

	/**
	 * Checks if an update is needed
	 *
	 * @param string &$description The description for the update
	 * @return boolean TRUE if an update is needed, FALSE otherwise
	 */
	public function checkForUpdate(&$description) {
		$description = 'There are backend users and backend groups with specified file permissions.' .
			' This update migrates old combined (binary) file permissions to new separate ones.';
		$updateNeeded = FALSE;
		$updateStatements = $this->getUpdateStatements();
		if (!empty($updateStatements['add'])) {
			// Field might not be there, so we need an update run to add the field
			return TRUE;
		}
		$beUsersFieldInformation = $GLOBALS['TYPO3_DB']->admin_get_fields('be_users');
		if (isset($beUsersFieldInformation['fileoper_perms'])) {
			// Fetch user records where the old permission field is not empty but the new one is
			$notMigratedRowsCount = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
				'uid',
				'be_users',
				$this->getWhereClause()
			);
			if ($notMigratedRowsCount > 0) {
				$updateNeeded = TRUE;
			}
		} else {
			$beGroupsFieldInformation = $GLOBALS['TYPO3_DB']->admin_get_fields('be_groups');
			if (isset($beGroupsFieldInformation['fileoper_perms'])) {
				// Fetch group records where the old permission field is not empty but the new one is
				$notMigratedRowsCount = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
					'uid',
					'be_groups',
					$this->getWhereClause()
				);
				if ($notMigratedRowsCount > 0) {
					$updateNeeded = TRUE;
				}
			}
		}
		return $updateNeeded;
	}

	/**
	 * Performs the database update.
	 *
	 * @param array &$dbQueries Queries done in this update
	 * @param mixed &$customMessages Custom messages
	 * @return boolean TRUE on success, FALSE on error
	 */
	public function performUpdate(array &$dbQueries, &$customMessages) {
		// First perform all add update statements to database
		$updateStatements = $this->getUpdateStatements();
		foreach ((array) $updateStatements['add'] as $query) {
			$GLOBALS['TYPO3_DB']->admin_query($query);
			$dbQueries[] = $query;
			if ($GLOBALS['TYPO3_DB']->sql_error()) {
				$customMessages = 'SQL-ERROR: ' . htmlspecialchars($GLOBALS['TYPO3_DB']->sql_error());
				return FALSE;
			}
		}

		// Iterate over users and groups table to perform permission updates
		$tablesToProcess = array('be_groups', 'be_users');
		foreach ($tablesToProcess as $table) {
			$records = $this->getRecordsFromTable($table);
			foreach ($records as $singleRecord) {
				$filePermission = $this->getFilePermissions($singleRecord['fileoper_perms']);
				$updateArray = array(
					'file_permissions' => $filePermission
				);
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid=' . (int)$singleRecord['uid'], $updateArray);
				// Get last executed query
				$dbQueries[] = str_replace(chr(10), ' ', $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery);
				// Check for errors
				if ($GLOBALS['TYPO3_DB']->sql_error()) {
					$customMessages = 'SQL-ERROR: ' . htmlspecialchars($GLOBALS['TYPO3_DB']->sql_error());
					return FALSE;
				}
			}
		}

		return TRUE;
	}

	/**
	 * Gets all create, add and change queries from core/ext_tables.sql
	 *
	 * @return array
	 */
	protected function getUpdateStatements() {
		$updateStatements = array();

		// Get all necessary statements for ext_tables.sql file
		$rawDefinitions = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('core') . '/ext_tables.sql');
		$fieldDefinitionsFromFile = $this->installToolSqlParser->getFieldDefinitions_fileContent($rawDefinitions);
		if (count($fieldDefinitionsFromFile)) {
			$fieldDefinitionsFromCurrentDatabase = $this->installToolSqlParser->getFieldDefinitions_database();
			$diff = $this->installToolSqlParser->getDatabaseExtra($fieldDefinitionsFromFile, $fieldDefinitionsFromCurrentDatabase);
			$updateStatements = $this->installToolSqlParser->getUpdateSuggestions($diff);
		}

		return $updateStatements;
	}

	/**
	 * Processes the actual transformation from old binary file permissions to new separate list
	 *
	 * @param integer $oldFileOperationPermissions
	 * @return string
	 */
	protected function getFilePermissions($oldFileOperationPermissions) {
		if ($oldFileOperationPermissions == 0) {
			return '';
		}
		$defaultOptions = array(
			// File permissions
			'addFile' => TRUE,
			'readFile' => TRUE,
			'writeFile' => TRUE,
			'copyFile' => TRUE,
			'moveFile' => TRUE,
			'renameFile' => TRUE,
			'unzipFile' => TRUE,
			'deleteFile' => TRUE,
			// Folder permissions
			'addFolder' => TRUE,
			'readFolder' => TRUE,
			'writeFolder' => TRUE,
			'copyFolder' => TRUE,
			'moveFolder' => TRUE,
			'renameFolder' => TRUE,
			'deleteFolder' => TRUE,
			'recursivedeleteFolder' => TRUE
		);
		if (!($oldFileOperationPermissions & 1)) {
			unset($defaultOptions['addFile']);
			unset($defaultOptions['readFile']);
			unset($defaultOptions['writeFile']);
			unset($defaultOptions['copyFile']);
			unset($defaultOptions['moveFile']);
			unset($defaultOptions['renameFile']);
			unset($defaultOptions['deleteFile']);
		}
		if (!($oldFileOperationPermissions & 2)) {
			unset($defaultOptions['unzipFile']);
		}
		if (!($oldFileOperationPermissions & 4)) {
			unset($defaultOptions['addFolder']);
			unset($defaultOptions['writeFolder']);
			unset($defaultOptions['moveFolder']);
			unset($defaultOptions['renameFolder']);
			unset($defaultOptions['deleteFolder']);
		}
		if (!($oldFileOperationPermissions & 8)) {
			unset($defaultOptions['copyFolder']);
		}
		if (!($oldFileOperationPermissions & 16)) {
			unset($defaultOptions['recursivedeleteFolder']);
		}

		return implode(',', array_keys($defaultOptions));
	}

	/**
	 * Retrieve every record which needs to be processed
	 *
	 * @param string $table
	 * @return array
	 */
	protected function getRecordsFromTable($table) {
		$fields = implode(',', array('uid', 'fileoper_perms'));
		$records = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($fields, $table, $this->getWhereClause());
		return $records;
	}

	/**
	 * Returns the where clause for database requests
	 *
	 * @return string
	 */
	protected function getWhereClause() {
		return 'fileoper_perms>0 AND ISNULL(file_permissions)';
	}

}
