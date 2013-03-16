<?php
namespace TYPO3\CMS\Install\CoreUpdates;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Tolleiv Nietsch <info@tolleiv.de>
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
 */
class MigrateWorkspacesUpdate extends \TYPO3\CMS\Install\CoreUpdates\InstallSysExtsUpdate {

	protected $title = 'Versioning and Workspaces';

	public $sqlQueries;

	/**
	 * Checks if an update is needed
	 *
	 * @param 	string		&$description: The description for the update, which will be updated with a description of the script's purpose
	 * @return 	boolean		whether an update is needed (TRUE) or not (FALSE)
	 */
	public function checkForUpdate(&$description) {
		$result = FALSE;
		$description = 'Migrates the old hardcoded draft workspace to be a real workspace record,
		updates workspace owner fields to support either users or groups and
		migrates the old-style workspaces with fixed workflow to a custom-stage workflow. If required
		the extbase, fluid, version and workspaces extensions are installed.';
		$reason = '';
		// TYPO3 version 4.5 and above
		if ($this->versionNumber >= 4005000) {
			// If neither version nor workspaces is installed, we're not doing a migration
			// Present the user with the choice of activating versioning and workspaces
			if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('version') && !\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('workspaces')) {
				$result = TRUE;
				// Override the default description
				$description = 'Activates the usage of workspaces in your installation. Workspaces let you edit elements
					without the changes being visible on the live web site right away. Modified elements can then go
					through a validation process and eventually be published.<br /><br />';
				$description .= 'This wizard will install system extensions "version" and "workspaces" (and may
					install "fluid" and "extbase" too, as they are used by the "workspaces" extension).';
			} else {
				\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->loadExtensionTables(FALSE);
				if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('version') || !\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('workspaces')) {
					$result = TRUE;
					$reason .= ' Both extensions "version" and "workspaces" need to be
						present to use the entire versioning and workflow featureset of TYPO3.';
				}
				$tables = array_keys($GLOBALS['TYPO3_DB']->admin_get_tables());
				// sys_workspace table might not exists if version extension was never installed
				if (!in_array('sys_workspace', $tables) || !in_array('sys_workspace_stage', $tables)) {
					$result = TRUE;
					$reason .= ' The database tables for the workspace functionality are missing.';
				} elseif ($this->isOldStyleAdminFieldUsed() || $this->isOldStyleWorkspace()) {
					$wsCount = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('uid', 'sys_workspace', '');
					$result |= $wsCount > 0;
					$reason .= ' The existing workspaces will be checked for compatibility with the new features.';
				}
				$draftWorkspaceTestResult = $this->isDraftWorkspaceUsed();
				if ($draftWorkspaceTestResult) {
					$reason .= ' The old style draft workspace is used.
						Related records will be moved into a full featured workspace.';
					$result = TRUE;
				}
				$description .= '<br /><strong>Why do you need this wizard?</strong><br />' . $reason;
			}
		}
		return $result;
	}

	/**
	 * This method requests input from the user about the upgrade process, if needed
	 *
	 * @param string $inputPrefix
	 * @return void
	 */
	public function getUserInput($inputPrefix) {
		$content = '';
		if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('version') && !\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('workspaces')) {
			// We need feedback only if versioning is not activated at all
			// In such a case we want to leave the user with the choice of not activating the stuff at all
			$content = '
				<fieldset>
					<ol>
			';
			$content .= '
				<li class="labelAfter">
					<input type="checkbox" id="versioning" name="' . $inputPrefix . '[versioning]" value="1" checked="checked" />
					<label for="versioning">Activate workspaces?</label>
				</li>
			';
			$content .= '
					</ol>
				</fieldset>
			';
		} else {
			// No feedback needed, just include the update flag as a hidden field
			$content = '<input type="hidden" id="versioning" name="' . $inputPrefix . '[versioning]" value="1" />';
		}
		return $content;
	}

	/**
	 * Performs the database update. Changes existing workspaces to use the new custom workspaces
	 *
	 * @param 	array		&$databaseQueries: queries done in this update
	 * @param 	mixed		&$customMessages: custom messages
	 * @return 	boolean		whether it worked (TRUE) or not (FALSE)
	 */
	public function performUpdate(array &$databaseQueries, &$customMessages) {
		$result = TRUE;
		// TYPO3 version below 4.5
		if ($this->versionNumber < 4005000) {
			return FALSE;
		}
		// Wizard skipped by the user
		if (empty($this->pObj->INSTALL['update']['migrateWorkspaces']['versioning'])) {
			return TRUE;
		}
		\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->loadExtensionTables(FALSE);
		// install version and workspace extension (especially when updating from very old TYPO3 versions
		$this->installExtensions(array('extbase', 'fluid', 'version', 'workspaces'));
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
		$workspaces = $this->getWorkspacesWithoutStages();
		$this->sqlQueries[] = $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery;
		$label = 'Review';
		foreach ($workspaces as $workspace) {
			// Find all workspaces and add "review" stage record
			// Add review users and groups to the new IRRE record
			$reviewStageId = $this->createReviewStageForWorkspace($workspace['uid'], $label, $workspace['reviewers']);
			// Update all "review" state records in the database to point to the new state
			$this->migrateOldRecordsToStage($workspace['uid'], 1, $reviewStageId);
			// Update all "ready to publish" records in the database to point to the new ready to publish state
			$this->migrateOldRecordsToStage($workspace['uid'], 10, -99);
		}
		if (is_array($this->sqlQueries) && is_array($databaseQueries)) {
			$databaseQueries = array_merge($databaseQueries, $this->sqlQueries);
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
			if (is_array($GLOBALS['TCA'][$table])) {
				$versioningVer = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($GLOBALS['TCA'][$table]['ctrl']['versioningWS'], 0, 2, 0);
				if ($versioningVer > 0) {
					if ($this->hasElementsOnWorkspace($table, -1)) {
						$foundDraftRecords = TRUE;
						break;
					}
				}
			}
		}
		return $foundDraftRecords;
	}

	/**
	 * Find workspaces which have no sys_workspace_state(s) but have records using states
	 * If "
	 *
	 * @return bool
	 */
	protected function isOldStyleWorkspace() {
		$foundOldStyleStages = FALSE;
		$workspaces = $this->getWorkspacesWithoutStages();
		$workspacesWithReviewers = 0;
		$workspaceUids = array();
		foreach ($workspaces as $workspace) {
			$workspaceUids[] = $workspace['uid'];
			if ($workspace['reviewers']) {
				$workspacesWithReviewers++;
			}
		}
		if (!$workspacesWithReviewers && !empty($workspaceUids)) {
			$tables = array_keys($GLOBALS['TCA']);
			foreach ($tables as $table) {
				$versioningVer = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($GLOBALS['TCA'][$table]['ctrl']['versioningWS'], 0, 2, 0);
				if ($versioningVer > 0) {
					$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('uid', $table, 't3ver_wsid IN (' . implode(',', $workspaceUids) . ') AND t3ver_stage IN (-1,1,10) AND pid = -1');
					if ($count > 0) {
						$foundOldStyleStages = TRUE;
						break;
					}
				}
			}
		}
		return $foundOldStyleStages || $workspacesWithReviewers;
	}

	/**
	 * Create a new stage for the given workspace
	 *
	 * @param 		integer	Workspace ID
	 * @param 		string		The label of the new stage
	 * @param 		string		The users or groups which are authorized for that stage
	 * @return 	integer	The id of the new stage
	 */
	protected function createReviewStageForWorkspace($workspaceId, $stageLabel, $stageMembers) {
		$data = array(
			'parentid' => $workspaceId,
			'parenttable' => 'sys_workspace',
			'title' => $stageLabel,
			'responsible_persons' => $stageMembers
		);
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_workspace_stage', $data);
		$this->sqlQueries[] = $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery;
		return $GLOBALS['TYPO3_DB']->sql_insert_id();
	}

	/**
	 * Updates the stages of placeholder records within the given workspace from $oldId to $newId
	 *
	 * @param 		integer	Workspace ID
	 * @param 		integer	Old stage od
	 * @param 		integer	New stage od
	 * @return 	void
	 */
	protected function migrateOldRecordsToStage($workspaceId, $oldStageId, $newStageId) {
		$tables = array_keys($GLOBALS['TCA']);
		$where = 't3ver_wsid = ' . intval($workspaceId) . ' AND t3ver_stage = ' . intval($oldStageId) . ' AND pid = -1';
		$values = array(
			't3ver_stage' => intval($newStageId)
		);
		foreach ($tables as $table) {
			$versioningVer = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($GLOBALS['TCA'][$table]['ctrl']['versioningWS'], 0, 2, 0);
			if ($versioningVer > 0) {
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, $values);
				$this->sqlQueries[] = $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery;
			}
		}
	}

	/**
	 * Check if there's any workspace which doesn't support the new admin-field format yet
	 *
	 * @return bool
	 */
	protected function isOldStyleAdminFieldUsed() {
		$where = 'adminusers != "" AND adminusers NOT LIKE "%be_users%" AND adminusers NOT LIKE "%be_groups%" AND deleted=0';
		$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('uid', 'sys_workspace', $where);
		$this->sqlQueries[] = $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery;
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
		$this->sqlQueries[] = $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery;
		return $GLOBALS['TYPO3_DB']->sql_insert_id();
	}

	/**
	 * Migrates all elements from the old draft workspace to the new one.
	 *
	 * @param integer $wsId
	 * @return void
	 */
	protected function migrateDraftWorkspaceRecordsToWorkspace($wsId) {
		$tables = array_keys($GLOBALS['TCA']);
		$where = 't3ver_wsid=-1';
		$values = array(
			't3ver_wsid' => intval($wsId)
		);
		foreach ($tables as $table) {
			$versioningVer = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($GLOBALS['TCA'][$table]['ctrl']['versioningWS'], 0, 2, 0);
			if ($versioningVer > 0 && $this->hasElementsOnWorkspace($table, -1)) {
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, $values);
				$this->sqlQueries[] = $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery;
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
		$this->sqlQueries[] = $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery;
		foreach ($workspaces as $workspace) {
			$updateArray = array(
				'adminusers' => 'be_users_' . implode(',be_users_', \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $workspace['adminusers'], TRUE))
			);
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_workspace', 'uid = ' . $workspace['uid'], $updateArray);
			$this->sqlQueries[] = $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery;
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
		$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('uid', $table, 't3ver_wsid=' . intval($workspaceId));
		$this->sqlQueries[] = $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery;
		return $count > 0;
	}

	/**
	 * Returns all sys_workspace records which are not referenced by any sys_workspace_stages record
	 *
	 * @return array
	 */
	protected function getWorkspacesWithoutStages() {
		$stages = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('parentid', 'sys_workspace_stage', 'parenttable=\'sys_workspace\'');
		$wsWhitelist = array();
		foreach ($stages as $stage) {
			$wsWhitelist[] = $stage['parentid'];
		}
		$where = 'deleted=0';
		$where .= !empty($wsWhitelist) ? ' AND uid NOT IN (' . implode(',', $wsWhitelist) . ')' : '';
		return $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'sys_workspace', $where);
	}

}


?>