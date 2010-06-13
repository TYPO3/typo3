<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2010 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   68: class wslib
 *   81:     function getCmdArrayForPublishWS($wsid, $doSwap,$pageId=0)
 *  127:     function selectVersionsInWorkspace($wsid,$filter=0,$stage=-99,$pageId=-1)
 *
 *              SECTION: CLI functions
 *  183:     function CLI_main()
 *  193:     function autoPublishWorkspaces()
 *
 * TOTAL FUNCTIONS: 4
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */












/**
 * Library with Workspace related functionality
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class wslib {




	/**
	 * Building tcemain CMD-array for swapping all versions in a workspace.
	 *
	 * @param	integer		Real workspace ID, cannot be ONLINE (zero).
	 * @param	boolean		If set, then the currently online versions are swapped into the workspace in exchange for the offline versions. Otherwise the workspace is emptied.
	 * @param	[type]		$pageId: ...
	 * @return	array		Command array for tcemain
	 */
	function getCmdArrayForPublishWS($wsid, $doSwap,$pageId=0)	{

		$wsid = intval($wsid);
		$cmd = array();

		if ($wsid>=-1 && $wsid!==0)	{

				// Define stage to select:
			$stage = -99;
			if ($wsid>0)	{
				$workspaceRec = t3lib_BEfunc::getRecord('sys_workspace',$wsid);
				if ($workspaceRec['publish_access']&1)	{
					$stage = 10;
				}
			}

				// Select all versions to swap:
			$versions = $this->selectVersionsInWorkspace($wsid,0,$stage,($pageId?$pageId:-1));

				// Traverse the selection to build CMD array:
			foreach($versions as $table => $records)	{
				foreach($records as $rec)	{

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
	 * @param	integer		Workspace ID. If -99, will select ALL versions from ANY workspace. If -98 will select all but ONLINE. >=-1 will select from the actual workspace
	 * @param	integer		Lifecycle filter: 1 = select all drafts (never-published), 2 = select all published one or more times (archive/multiple), anything else selects all.
	 * @param	integer		Stage filter: -99 means no filtering, otherwise it will be used to select only elements with that stage. For publishing, that would be "10"
	 * @param	integer		Page id: Live page for which to find versions in workspace!
	 * @return	array		Array of all records uids etc. First key is table name, second key incremental integer. Records are associative arrays with uid, t3ver_oid and t3ver_swapmode fields. The REAL pid of the online record is found as "realpid"
	 */
	function selectVersionsInWorkspace($wsid,$filter=0,$stage=-99,$pageId=-1)	{
		global $TCA;

		$wsid = intval($wsid);
		$filter = intval($filter);
		$output = array();

			// Traversing all tables supporting versioning:
		foreach($TCA as $table => $cfg)	{
			if ($TCA[$table]['ctrl']['versioningWS'])	{

					// Select all records from this table in the database from the workspace
					// This joins the online version with the offline version as tables A and B
				$recs = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows (
					'A.uid, A.t3ver_oid,'.($table==='pages' ? ' A.t3ver_swapmode,':'').' B.pid AS realpid',
					$table.' A,'.$table.' B',
					'A.pid=-1'.	// Table A is the offline version and pid=-1 defines offline
						($pageId!=-1 ? ($table==='pages' ? ' AND B.uid='.intval($pageId) : ' AND B.pid='.intval($pageId)) : '').
						($wsid>-98 ? ' AND A.t3ver_wsid='.$wsid : ($wsid===-98 ? ' AND A.t3ver_wsid!=0' : '')).	// For "real" workspace numbers, select by that. If = -98, select all that are NOT online (zero). Anything else below -1 will not select on the wsid and therefore select all!
						($filter===1 ? ' AND A.t3ver_count=0' : ($filter===2 ? ' AND A.t3ver_count>0' : '')).	// lifecycle filter: 1 = select all drafts (never-published), 2 = select all published one or more times (archive/multiple)
						($stage!=-99 ? ' AND A.t3ver_stage='.intval($stage) : '').
						' AND B.pid>=0'.	// Table B (online) must have PID >= 0 to signify being online.
						' AND A.t3ver_oid=B.uid'.	// ... and finally the join between the two tables.
						t3lib_BEfunc::deleteClause($table,'A').
						t3lib_BEfunc::deleteClause($table,'B'),
					'',
					'B.uid'		// Order by UID, mostly to have a sorting in the backend overview module which doesn't "jump around" when swapping.
				);
				if (count($recs)) {
					$output[$table] = $recs;
				}
			}
		}

		return $output;
	}












	/****************************
	 *
	 * CLI functions
	 *
	 ****************************/

	/**
	 * Main function to call from cli-script
	 *
	 * @return	void
	 */
	function CLI_main()	{
		$this->autoPublishWorkspaces();
	}

	/**
	 * CLI internal function:
	 * Will search for workspaces which has timed out regarding publishing and publish them
	 *
	 * @return	void
	 */
	function autoPublishWorkspaces()	{
		global $TYPO3_CONF_VARS;

			// Temporarily set high power...
		$GLOBALS['BE_USER']->user['admin'] = 1;

			// Select all workspaces that needs to be published / unpublished:
		$workspaces = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'uid,swap_modes,publish_time,unpublish_time',
			'sys_workspace',
			'pid=0
				AND
				((publish_time!=0 AND publish_time<='.intval($GLOBALS['EXEC_TIME']).')
				OR (publish_time=0 AND unpublish_time!=0 AND unpublish_time<='.intval($GLOBALS['EXEC_TIME']).'))'.
				t3lib_BEfunc::deleteClause('sys_workspace')
		);

		foreach($workspaces as $rec)	{

				// First, clear start/end time so it doesn't get select once again:
			$fieldArray = $rec['publish_time']!=0 ? array('publish_time'=>0) : array('unpublish_time'=>0);
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_workspace','uid='.intval($rec['uid']),$fieldArray);

				// Get CMD array:
			$cmd = $this->getCmdArrayForPublishWS($rec['uid'], $rec['swap_modes']==1);	// $rec['swap_modes']==1 means that auto-publishing will swap versions, not just publish and empty the workspace.

				// Execute CMD array:
			$tce = t3lib_div::makeInstance('t3lib_TCEmain');
			$tce->stripslashes_values = 0;
			$tce->start(array(),$cmd);
			$tce->process_cmdmap();
		}
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/mod/user/ws/class.wslib.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/mod/user/ws/class.wslib.php']);
}
?>