<?php
namespace TYPO3\CMS\Version\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Library with Workspace related functionality
 *
 * @author 	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class WorkspacesUtility {

	/**
	 * Building tcemain CMD-array for swapping all versions in a workspace.
	 *
	 * @param 	integer		Real workspace ID, cannot be ONLINE (zero).
	 * @param 	boolean		If set, then the currently online versions are swapped into the workspace in exchange for the offline versions. Otherwise the workspace is emptied.
	 * @param 	[type]		$pageId: ...
	 * @return 	array		Command array for tcemain
	 * @todo Define visibility
	 */
	public function getCmdArrayForPublishWS($wsid, $doSwap, $pageId = 0) {
		$wsid = intval($wsid);
		$cmd = array();
		if ($wsid >= -1 && $wsid !== 0) {
			// Define stage to select:
			$stage = -99;
			if ($wsid > 0) {
				$workspaceRec = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('sys_workspace', $wsid);
				if ($workspaceRec['publish_access'] & 1) {
					$stage = 10;
				}
			}
			// Select all versions to swap:
			$versions = $this->selectVersionsInWorkspace($wsid, 0, $stage, $pageId ? $pageId : -1);
			// Traverse the selection to build CMD array:
			foreach ($versions as $table => $records) {
				foreach ($records as $rec) {
					// Build the cmd Array:
					$cmd[$table][$rec['t3ver_oid']]['version'] = array(
						'action' => 'swap',
						'swapWith' => $rec['uid'],
						'swapIntoWS' => $doSwap ? 1 : 0
					);
				}
			}
		}
		return $cmd;
	}

	/**
	 * Select all records from workspace pending for publishing
	 * Used from backend to display workspace overview
	 * User for auto-publishing for selecting versions for publication
	 *
	 * @param 	integer		Workspace ID. If -99, will select ALL versions from ANY workspace. If -98 will select all but ONLINE. >=-1 will select from the actual workspace
	 * @param 	integer		Lifecycle filter: 1 = select all drafts (never-published), 2 = select all published one or more times (archive/multiple), anything else selects all.
	 * @param 	integer		Stage filter: -99 means no filtering, otherwise it will be used to select only elements with that stage. For publishing, that would be "10
	 * @param 	integer		Page id: Live page for which to find versions in workspace!
	 * @return 	array		Array of all records uids etc. First key is table name, second key incremental integer. Records are associative arrays with uid and t3ver_oid fields. The REAL pid of the online record is found as "realpid
	 * @todo Define visibility
	 */
	public function selectVersionsInWorkspace($wsid, $filter = 0, $stage = -99, $pageId = -1) {
		$wsid = intval($wsid);
		$filter = intval($filter);
		$output = array();
		// Traversing all tables supporting versioning:
		foreach ($GLOBALS['TCA'] as $table => $cfg) {
			if ($GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
				// Select all records from this table in the database from the workspace
				// This joins the online version with the offline version as tables A and B
				$recs = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('A.uid, A.t3ver_oid, B.pid AS realpid', $table . ' A,' . $table . ' B', 'A.pid=-1' . ($pageId != -1 ? ($table === 'pages' ? ' AND B.uid=' . intval($pageId) : ' AND B.pid=' . intval($pageId)) : '') . ($wsid > -98 ? ' AND A.t3ver_wsid=' . $wsid : ($wsid === -98 ? ' AND A.t3ver_wsid!=0' : '')) . ($filter === 1 ? ' AND A.t3ver_count=0' : ($filter === 2 ? ' AND A.t3ver_count>0' : '')) . ($stage != -99 ? ' AND A.t3ver_stage=' . intval($stage) : '') . ' AND B.pid>=0' . ' AND A.t3ver_oid=B.uid' . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table, 'A') . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table, 'B'), '', 'B.uid');
				if (count($recs)) {
					$output[$table] = $recs;
				}
			}
		}
		return $output;
	}

	/****************************
	 *
	 * Scheduler methods
	 *
	 ****************************/
	/**
	 * This method is called by the Scheduler task that triggers
	 * the autopublication process
	 * It searches for workspaces whose publication date is in the past
	 * and publishes them
	 *
	 * @return 	void
	 * @todo Define visibility
	 */
	public function autoPublishWorkspaces() {
		// Temporarily set admin rights
		// FIXME: once workspaces are cleaned up a better solution should be implemented
		$currentAdminStatus = $GLOBALS['BE_USER']->user['admin'];
		$GLOBALS['BE_USER']->user['admin'] = 1;
		// Select all workspaces that needs to be published / unpublished:
		$workspaces = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,swap_modes,publish_time,unpublish_time', 'sys_workspace', 'pid=0
				AND
				((publish_time!=0 AND publish_time<=' . intval($GLOBALS['EXEC_TIME']) . ')
				OR (publish_time=0 AND unpublish_time!=0 AND unpublish_time<=' . intval($GLOBALS['EXEC_TIME']) . '))' . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('sys_workspace'));
		foreach ($workspaces as $rec) {
			// First, clear start/end time so it doesn't get select once again:
			$fieldArray = $rec['publish_time'] != 0 ? array('publish_time' => 0) : array('unpublish_time' => 0);
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_workspace', 'uid=' . intval($rec['uid']), $fieldArray);
			// Get CMD array:
			$cmd = $this->getCmdArrayForPublishWS($rec['uid'], $rec['swap_modes'] == 1);
			// $rec['swap_modes']==1 means that auto-publishing will swap versions, not just publish and empty the workspace.
			// Execute CMD array:
			$tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
			$tce->stripslashes_values = 0;
			$tce->start(array(), $cmd);
			$tce->process_cmdmap();
		}
		// Restore admin status
		$GLOBALS['BE_USER']->user['admin'] = $currentAdminStatus;
	}

}


?>