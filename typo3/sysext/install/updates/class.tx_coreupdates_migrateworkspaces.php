<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Tolleiv Nietsch <info@tolleiv.de>
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
 * Migrates workspaces from TYPO3 versions below 4.5.
 *
 * @author Tolleiv Nietsch <info@tolleiv.de>
 * @version $Id$
 */
class tx_coreupdates_migrateworkspaces extends tx_coreupdates_installsysexts {
	public $versionNumber;	// version number coming from t3lib_div::int_from_ver()

	/**
	 * parent object
	 *
	 * @var tx_install
	 */
	public $pObj;
	public $userInput;	// user input
	public $sqlQueries;

	/**
	 * Checks if an update is needed
	 *
	 * @param	string		&$description: The description for the update, which will be updated with a description of the script's purpose
	 * @return	boolean		whether an update is needed (true) or not (false)
	 */
	public function checkForUpdate(&$description) {
		$result = FALSE;
		$description = 'Migrates the old hardcoded draft workspace to be a real workspace element and update workspace owner fields  to support either users or groups.';

			// TYPO3 version 4.5 and above
		if ($this->versionNumber >= 4005000) {
			$tables = array_keys($GLOBALS['TYPO3_DB']->admin_get_tables());
				// sys_workspace table might not exists if version extension was never installed
			if (in_array('sys_workspace', $tables)) {
				$result = $this->isOldStyleAdminFieldUsed();
			}

			if (!$result) {
				$this->includeTCA();
				$result = $this->isDraftWorkspaceUsed();
			}
		}

		return $result;
	}

	/**
	 * If there's any user-input which we'd love to process, this method allows to specify what
	 * kind of input we need.
	 *
	 * Since we don't need input this is left empty. It's still here to avoid that this is inherited.
	 *
	 * @param string $inputPrefix
	 * @return void
	 */
	public function getUserInput($inputPrefix) {
		return;
	}

	/**
	 * Performs the database update. Changes existing workspaces to use the new custom workspaces
	 *
	 * @param	array		&$databaseQueries: queries done in this update
	 * @param	mixed		&$customMessages: custom messages
	 * @return	boolean		whether it worked (true) or not (false)
	 */
	public function performUpdate(array &$databaseQueries, &$customMessages) {
		$result = TRUE;

			// TYPO3 version below 4.5
		if ($this->versionNumber < 4005000)	{
			return FALSE;
		}

			// There's no TCA available yet
		$this->includeTCA();

			// install version extension (especially when updating from very old TYPO3 versions
		$this->installExtensions(array('version'));

			// migrate all workspaces to support groups and be_users
		if ($this->isOldStyleAdminFieldUsed()) {
			$this->migrateAdminFieldToNewStyle();
		}

			// create a new dedicated "Draft" workspace and move all records to that new workspace
		if ($this->isDraftWorkspaceUsed()) {
			$draftWorkspaceId = $this->createWorkspace();
			if (is_integer($draftWorkspaceId)) {
				$this->migrateDraftWorkspaceRecordsToWorkspace($draftWorkspaceId);
			}
		}

		if (is_array($this->sqlQueries) && is_array($databaseQueries)) {
			$databaseQueries = array_merge($databaseQueries, $this->sqlQueries);
		}

		return $result;
	}

	/**
	 * Install a extensions
	 *
	 * @param	array		List of extension keys
	 * @return	boolean	Determines whether this was successful or not
	 */
	protected function installExtensions($extensionKeys) {
		if (!is_array($extensionKeys)) {
			return FALSE;
		}

		$result = FALSE;
		$extList = $this->addExtToList($extensionKeys);
		if ($extList) {
			$this->writeNewExtensionList($extList);
			$result = TRUE;
		}
		return $result;
	}

	/**
	 * Check if any table contains draft-workspace records
	 *
	 * @return bool
	 */
	protected function isDraftWorkspaceUsed() {
		$foundDraftRecords = FALSE;

		$tables = array_keys($GLOBALS['TCA']);
		foreach ($tables as $table) {
			$versioningVer = t3lib_div::intInRange($GLOBALS['TCA'][$table]['ctrl']['versioningWS'], 0, 2, 0);
			if ($versioningVer > 0) {
				if ($this->hasElementsOnWorkspace($table, -1)) {
					$foundDraftRecords = TRUE;
					break;
				}
			}
		}

		return $foundDraftRecords;
	}

	/**
	 * Check if there's any workspace which doesn't support the new admin-field format yet
	 * @return bool
	 */
	protected function isOldStyleAdminFieldUsed() {
		$where = 'adminusers != "" AND adminusers NOT LIKE "%be_users%" AND adminusers NOT LIKE "%be_groups%" AND deleted=0';
		$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('uid', 'sys_workspace', $where);
		$this->sqlQueries[] =  $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery;
		return $count > 0;
	}

	/**
	 * Create a real workspace named "Draft"
	 *
	 * @return integer
	 */
	protected function createWorkspace() {
			// @todo who are the reviewers and owners for this workspace?
			// In previous versions this was defined in be_groups/be_users with the setting "Edit in Draft"
		$data = array(
			'title' => 'Draft'
		);
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_workspace', $data);
		$this->sqlQueries[] =  $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery;
		return $GLOBALS['TYPO3_DB']->sql_insert_id();
	}

	/**
	 * Migrates all elements from the old draft workspace to the new one.
	 *
	 * @param  integer $wsId
	 * @return void
	 */
	protected function migrateDraftWorkspaceRecordsToWorkspace($wsId) {
		$tables = array_keys($GLOBALS['TCA']);
		$where = 't3ver_wsid=-1';
		$values = array(
			't3ver_wsid' => intval($wsId)
		);
		foreach($tables as $table) {
			$versioningVer = t3lib_div::intInRange($GLOBALS['TCA'][$table]['ctrl']['versioningWS'], 0, 2, 0);
			if ($versioningVer > 0 && $this->hasElementsOnWorkspace($table, -1)) {
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, $values);
				$this->sqlQueries[] =  $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery;
			}
		}
	}

	/**
	 * Migrate all workspace adminusers fields to support groups aswell,
	 * this means that the old comma separated list of uids (referring to be_users)
	 * is updated to be a list of uids with the tablename as prefix
	 *
	 * @return void
	 */
	protected function migrateAdminFieldToNewStyle() {
		$where = 'adminusers != "" AND adminusers NOT LIKE "%be_users%" AND adminusers NOT LIKE "%be_groups%" AND deleted=0';
		$workspaces = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid, adminusers', 'sys_workspace', $where);
		$this->sqlQueries[] =  $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery;
		foreach ($workspaces as $workspace) {
			$updateArray = array(
				'adminusers' => 'be_users_' . implode(',be_users_', t3lib_div::trimExplode(',', $workspace['adminusers'], TRUE)),
			);
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
				'sys_workspace',
				'uid = "' . $workspace['uid'] . '"',
				$updateArray
			);
			$this->sqlQueries[] =  $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery;
		}
	}

	/**
	 * Includes the TCA definition of installed extensions.
	 *
	 * This method is used because usually the TCA is included within the init.php script, this doesn't happen
	 * if the install-tool is used, therefore this has to be done by hand.
	 *
	 * @return void
	 */
	protected function includeTCA() {
		global $TCA; // this is relevant because it's used within the included ext_tables.php files - do NOT remove it

		include_once(TYPO3_tables_script ? PATH_typo3conf . TYPO3_tables_script : PATH_t3lib . 'stddb/tables.php');
			// Extension additions
		if ($GLOBALS['TYPO3_LOADED_EXT']['_CACHEFILE']) {
			include_once(PATH_typo3conf . $GLOBALS['TYPO3_LOADED_EXT']['_CACHEFILE'] . '_ext_tables.php');
		} else {
			include_once(PATH_t3lib . 'stddb/load_ext_tables.php');
		}
	}

	/**
	 * Determines whether a table has elements in a particular workspace.
	 *
	 * @param string $table Name of the table
	 * @param integer $workspaceId Id of the workspace
	 * @return boolean
	 */
	protected function hasElementsOnWorkspace($table, $workspaceId) {
		$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
			'uid',
			$table,
			't3ver_wsid=' . intval($workspaceId)
		);

		$this->sqlQueries[] =  $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery;

		return ($count > 0);
	}
}
?>
