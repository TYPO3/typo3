<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2010 Kasper Skårhøj (kasperYYYY@typo3.com)
*  (c) 2010 Benjamin Mack (benni@typo3.org)
* 
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
 *
 * Contains some parts for staging, versioning and workspaces
 * to interact with the TYPO3 Core Engine
 *
 */
class tx_version_tcemain {

		// For accumulating information about workspace stages raised
		// on elements so a single mail is sent as notification.
		// previously called "accumulateForNotifEmail" in tcemain
	protected $notificationEmailInfo = array();

		// general comment, eg. for staging in workspaces.
	protected $generalComment = '';


	/****************************
	 *****  Cmdmap  Hooks  ******
	 ****************************/

	/**
	 * hook that is called before any cmd of the commandmap is 
	 * executed
	 * @param	$tcemainObj	reference to the main tcemain object
	 * @return	void
	 */
	public function processCmdmap_beforeStart(&$tcemainObj) {
			// Reset notification array
		$this->notificationEmailInfo = array();
	}


	/**
	 * hook that is called when no prepared command was found
	 * 
	 * @param	$command	the command to be executed
	 * @param	$table	the table of the record
	 * @param	$id	the ID of the record
	 * @param	$value	the value containing the data
	 * @param	$commandIsProcessed	can be set so that other hooks or
	 * 				TCEmain knows that the default cmd doesn't have to be called
	 * @param	$tcemainObj	reference to the main tcemain object
	 * @return	void
	 */
	public function processCmdmap($command, $table, $id, $value, &$commandIsProcessed, &$tcemainObj) {
		
		
			// custom command "version"
		if ($command == 'version') {
			$commandWasProcessed = TRUE;
			$action = (string) $value['action'];
			switch ($action) {

				case 'new':
						// check if page / branch versioning is needed, 
						// or if "element" version can be used
					$versionizeTree = -1;
					if (isset($value['treeLevels'])) {
						$versionizeTree = t3lib_div::intInRange($value['treeLevels'], -1, 100);
					}
					if ($table == 'pages' && $versionizeTree >= 0) {
						$this->versionizePages($id, $value['label'], $versionizeTree, $tcemainObj);
					} else {
						$tcemainObj->versionizeRecord($table, $id, $value['label']);
					}
				break;

				case 'swap':
					$swapMode = $tcemainObj->BE_USER->getTSConfigVal('options.workspaces.swapMode');
					$elementList = array();
					if ($swapMode == 'any' || ($swapMode == 'page' && $table == 'pages')) {
							// check if we are allowed to do synchronios publish. 
							// We must have a single element in the cmdmap to be allowed
						if (count($tcemainObj->cmdmap) == 1 && count($tcemainObj->cmdmap[$table]) == 1) {
							$elementList = $this->findPageElementsForVersionSwap($table, $id, $value['swapWith']);
						}
					}
					if (count($elementList) == 0) {
						$elementList[$table][] = array($id, $value['swapWith']);
					}
					foreach ($elementList as $tbl => $idList) {
						foreach ($idList as $idKey => $idSet) {
							$this->version_swap($tbl, $idSet[0], $idSet[1], $value['swapIntoWS'], $tcemainObj);
						}
					}
				break;

				case 'clearWSID':
					$this->version_clearWSID($table, $id, FALSE, $tcemainObj);
				break;

				case 'flush':
					$this->version_clearWSID($table, $id, TRUE, $tcemainObj);
				break;

				case 'setStage':
					$elementList = array();
					$idList = $elementList[$table] = t3lib_div::trimExplode(',', $id, 1);
					$setStageMode = $tcemainObj->BE_USER->getTSConfigVal('options.workspaces.changeStageMode');
					if ($setStageMode == 'any' || $setStageMode == 'page') {
						if (count($idList) == 1) {
							$rec = t3lib_BEfunc::getRecord($table, $idList[0], 't3ver_wsid');
							$workspaceId = $rec['t3ver_wsid'];
						} else {
							$workspaceId = $tcemainObj->BE_USER->workspace;
						}
						if ($table !== 'pages') {
							if ($setStageMode == 'any') {
									// (1) Find page to change stage and (2) 
									// find other elements from the same ws to change stage
								$pageIdList = array();
								$this->findPageIdsForVersionStateChange($table, $idList, $workspaceId, $pageIdList, $elementList);
								$this->findPageElementsForVersionStageChange($pageIdList, $workspaceId, $elementList);
							}
						} else {
							// Find all elements from the same ws to change stage
							$this->findRealPageIds($idList);
							$this->findPageElementsForVersionStageChange($idList, $workspaceId, $elementList);
						}
					}

					foreach ($elementList as $tbl => $elementIdList) {
						foreach ($elementIdList as $elementId) {
							$this->version_setStage($tbl, $elementId, $value['stageId'], ($value['comment'] ? $value['comment'] : $this->generalComment), TRUE, $tcemainObj);
						}
					}
				break;
			}
		}
	}

	/**
	 * hook that is called AFTER all commands of the commandmap was 
	 * executed
	 * @param	$tcemainObj	reference to the main tcemain object
	 * @return	void
	 */
	public function processCmdmap_afterFinish(&$tcemainObj) {
			// Empty accumulation array:
		foreach ($this->notificationEmailInfo as $notifItem) {
			$this->notifyStageChange($notifItem['shared'][0], $notifItem['shared'][1], implode(', ', $notifItem['elements']), 0, $notifItem['shared'][2], $tcemainObj);
		}

			// Reset notification array
		$this->notificationEmailInfo = array();
	}


	/**
	 * hook that is called AFTER all commands of the commandmap was 
	 * executed
	 * @param	$tcemainObj	reference to the main tcemain object
	 * @return	void
	 */
	public function processCmdmap_deleteAction($table, $id, $record, &$recordWasDeleted, &$tcemainObj) {
		$id = $record['uid'];
	
			// For Live version, try if there is a workspace version because if so, rather "delete" that instead
			// Look, if record is an offline version, then delete directly:
		if ($record['pid'] != -1) {
			if ($wsVersion = t3lib_BEfunc::getWorkspaceVersionOfRecord($tcemainObj->BE_USER->workspace, $table, $id)) {
				$record = $wsVersion;
				$id = $record['uid'];
			}
		}

				// Look, if record is an offline version, then delete directly:
		if ($record['pid'] == -1) {
			if ($TCA[$table]['ctrl']['versioningWS']) {
					// In Live workspace, delete any. In other workspaces there must be match.
				if ($tcemainObj->BE_USER->workspace == 0 || (int) $record['t3ver_wsid'] == $tcemainObj->BE_USER->workspace) {
					$liveRec = t3lib_BEfunc::getLiveVersionOfRecord($table, $id, 'uid,t3ver_state');

						// Delete those in WS 0 + if their live records state was not "Placeholder".
					if ($record['t3ver_wsid']==0 || (int) $liveRec['t3ver_state'] <= 0) {
						$tcemainObj->deleteEl($table, $id);
					} else {
							// If live record was placeholder (new/deleted), rather clear
							// it from workspace (because it clears both version and placeholder).
						$this->version_clearWSID($table, $id, FALSE, $tcemainObj);
					}
				} else $tcemainObj->newlog('Tried to delete record from another workspace',1);
			} else $tcemainObj->newlog('Versioning not enabled for record with PID = -1!',2);
		} elseif ($res = $tcemainObj->BE_USER->workspaceAllowLiveRecordsInPID($record['pid'], $table)) {
				// Look, if record is "online" or in a versionized branch, then delete directly.
			if ($res>0) {
				$tcemainObj->deleteEl($table, $id);
			} else {
				$tcemainObj->newlog('Stage of root point did not allow for deletion',1);
			}
		} elseif ((int)$record['t3ver_state']===3) {
				// Placeholders for moving operations are deletable directly.

				// Get record which its a placeholder for and reset the t3ver_state of that:
			if ($wsRec = t3lib_BEfunc::getWorkspaceVersionOfRecord($record['t3ver_wsid'], $table, $record['t3ver_move_id'], 'uid')) {
					// Clear the state flag of the workspace version of the record
					// Setting placeholder state value for version (so it can know it is currently a new version...)
				$updateFields = array(
					't3ver_state' => 0
				);
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid=' . intval($wsRec['uid']), $updateFields);
			}
			$tcemainObj->deleteEl($table, $id);
		} else {
			// Otherwise, try to delete by versioning:
			$tcemainObj->versionizeRecord($table, $id, 'DELETED!', TRUE);
			$tcemainObj->deleteL10nOverlayRecords($table, $id);
		}
	}


	/****************************
	 *****  Notifications  ******
	 ****************************/

	/**
	 * Send an email notification to users in workspace
	 *
	 * @param	array		Workspace access array (from t3lib_userauthgroup::checkWorkspace())
	 * @param	integer		New Stage number: 0 = editing, 1= just ready for review, 10 = ready for publication, -1 = rejected!
	 * @param	string		Table name of element (or list of element names if $id is zero)
	 * @param	integer		Record uid of element (if zero, then $table is used as reference to element(s) alone)
	 * @param	string		User comment sent along with action
	 * @return	void
	 */
	protected function notifyStageChange($stat, $stageId, $table, $id, $comment, $tcemainObj) {
		$workspaceRec = t3lib_BEfunc::getRecord('sys_workspace', $stat['uid']);
			// So, if $id is not set, then $table is taken to be the complete element name!
		$elementName = $id ? $table . ':' . $id : $table;

		if (is_array($workspaceRec)) {

				// Compile label:
			switch ((int)$stageId) {
				case 1:
					$newStage = 'Ready for review';
				break;
				case 10:
					$newStage = 'Ready for publishing';
				break;
				case -1:
					$newStage = 'Element was rejected!';
				break;
				case 0:
					$newStage = 'Rejected element was noticed and edited';
				break;
				default:
					$newStage = 'Unknown state change!?';
				break;
			}

				// Compile list of recipients:
			$emails = array();
			switch((int)$stat['stagechg_notification'])	{
				case 1:
					switch((int)$stageId)	{
						case 1:
							$emails = $this->getEmailsForStageChangeNotification($workspaceRec['reviewers']);
						break;
						case 10:
							$emails = $this->getEmailsForStageChangeNotification($workspaceRec['adminusers'], TRUE);
						break;
						case -1:
#							$emails = $this->getEmailsForStageChangeNotification($workspaceRec['reviewers']);
#							$emails = array_merge($emails,$this->getEmailsForStageChangeNotification($workspaceRec['members']));

								// List of elements to reject:
							$allElements = explode(',', $elementName);
								// Traverse them, and find the history of each
							foreach ($allElements as $elRef) {
								list($eTable, $eUid) = explode(':', $elRef);

								$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
										'log_data,tstamp,userid',
										'sys_log',
										'action=6 and details_nr=30
										AND tablename=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($eTable, 'sys_log') . '
										AND recuid=' . intval($eUid),
										'',
										'uid DESC'
								);
									// Find all implicated since the last stage-raise from editing to review:
								foreach ($rows as $dat) {
									$data = unserialize($dat['log_data']);

									$emails = array_merge($emails, $this->getEmailsForStageChangeNotification($dat['userid'], TRUE));

									if ($data['stage'] == 1) {
										break;
									}
								}
							}
						break;

						case 0:
							$emails = $this->getEmailsForStageChangeNotification($workspaceRec['members']);
						break;

						default:
							$emails = $this->getEmailsForStageChangeNotification($workspaceRec['adminusers'], TRUE);
						break;
					}
				break;

				case 10:
					$emails = $this->getEmailsForStageChangeNotification($workspaceRec['adminusers'], TRUE);
					$emails = array_merge($emails, $this->getEmailsForStageChangeNotification($workspaceRec['reviewers']));
					$emails = array_merge($emails, $this->getEmailsForStageChangeNotification($workspaceRec['members']));
				break;
			}
			$emails = array_unique($emails);

				// Path to record is found:
			list($eTable,$eUid) = explode(':', $elementName);
			$eUid = intval($eUid);
			$rr = t3lib_BEfunc::getRecord($eTable, $eUid);
			$recTitle = t3lib_BEfunc::getRecordTitle($eTable, $rr);
			if ($eTable != 'pages') {
				t3lib_BEfunc::fixVersioningPid($eTable, $rr);
				$eUid = $rr['pid'];
			}
			$path = t3lib_BEfunc::getRecordPath($eUid, '', 20);

				// ALternative messages:
			$TSConfig = $tcemainObj->getTCEMAIN_TSconfig($eUid);
			$body = trim($TSConfig['notificationEmail_body']) ? trim($TSConfig['notificationEmail_body']) : '
At the TYPO3 site "%s" (%s)
in workspace "%s" (#%s)
the stage has changed for the element(s) "%11$s" (%s) at location "%10$s" in the page tree:

==> %s

User Comment:
"%s"

State was change by %s (username: %s)
			';
			$subject = trim($TSConfig['notificationEmail_subject']) ? trim($TSConfig['notificationEmail_subject']) : 'TYPO3 Workspace Note: Stage Change for %s';

				// Send email:
			if (count($emails))	{
				$message = sprintf($body,
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
				t3lib_div::getIndpEnv('TYPO3_SITE_URL').TYPO3_mainDir,
				$workspaceRec['title'],
				$workspaceRec['uid'],
				$elementName,
				$newStage,
				$comment,
				$this->BE_USER->user['realName'],
				$this->BE_USER->user['username'],
				$path,
				$recTitle);

				t3lib_div::plainMailEncoded(
					implode(',', $emails),
					sprintf($subject, $elementName),
					trim($message)
				);

				$tcemainObj->newlog2('Notification email for stage change was sent to "' . implode(', ', $emails) . '"', $table, $id);
			}
		}
	}

	/**
	 * Return emails addresses of be_users from input list.
	 * previously called notifyStageChange_getEmails() in tcemain
	 *
	 * @param	string		List of backend users, on the form "be_users_10,be_users_2" or "10,2" in case noTablePrefix is set.
	 * @param	boolean		If set, the input list are integers and not strings.
	 * @return	array		Array of emails
	 */
	protected function getEmailsForStageChangeNotification($listOfUsers, $noTablePrefix = FALSE) {
		$users = t3lib_div::trimExplode(',', $listOfUsers, 1);
		$emails = array();
		foreach ($users as $userIdent) {
			if ($noTablePrefix) {
				$id = intval($userIdent);
			} else {
				list($table, $id) = t3lib_div::revExplode('_', $userIdent, 2);
			}
			if ($table === 'be_users' || $noTablePrefix) {
				if ($userRecord = t3lib_BEfunc::getRecord('be_users', $id, 'email')) {
					if (strlen(trim($userRecord['email']))) {
						$emails[$id] = $userRecord['email'];
					}
				}
			}
		}
		return $emails;
	}



	/****************************
	 *****  Stage Changes  ******
	 ****************************/

	/**
	 * Setting stage of record
	 *
	 * @param	string		Table name
	 * @param	integer		Record UID
	 * @param	integer		Stage ID to set
	 * @param	string		Comment that goes into log
	 * @param	boolean		Accumulate state changes in memory for compiled notification email?
	 * @return	void
	 */
	protected function version_setStage($table, $id, $stageId, $comment = '', $notificationEmailInfo = FALSE, $tcemainObj) {
		if ($errorCode = $tcemainObj->BE_USER->workspaceCannotEditOfflineVersion($table, $id)) {
			$tcemainObj->newlog('Attempt to set stage for record failed: ' . $errorCode, 1);
		} elseif ($tcemainObj->checkRecordUpdateAccess($table, $id)) {
			$record = t3lib_BEfunc::getRecord($table, $id);
			$stat = $tcemainObj->BE_USER->checkWorkspace($record['t3ver_wsid']);

			if (t3lib_div::inList('admin,online,offline,reviewer,owner', $stat['_ACCESS']) || ($stageId <= 1 && $stat['_ACCESS'] === 'member')) {

					// Set stage of record:
				$updateData = array(
					't3ver_stage' => $stageId
				);
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid=' . intval($id), $updateData);
				$tcemainObj->newlog2('Stage for record was changed to ' . $stageId . '. Comment was: "' . substr($comment, 0, 100) . '"', $table, $id);

					// TEMPORARY, except 6-30 as action/detail number which is observed elsewhere!
				$tcemainObj->log($table, $id, 6, 0, 0, 'Stage raised...', 30, array('comment' => $comment, 'stage' => $stageId));

				if ((int)$stat['stagechg_notification'] > 0) {
					if ($notificationEmailInfo) {
						$this->notificationEmailInfo[$stat['uid'] . ':' . $stageId . ':' . $comment]['shared'] = array($stat, $stageId, $comment);
						$this->notificationEmailInfo[$stat['uid'] . ':' . $stageId . ':' . $comment]['elements'][] = $table . ':' . $id;
					} else {
						$this->notifyStageChange($stat, $stageId, $table, $id, $comment, $tcemainObj);
					}
				}
			} else $tcemainObj->newlog('The member user tried to set a stage value "' . $stageId . '" that was not allowed', 1);
		} else $tcemainObj->newlog('Attempt to set stage for record failed because you do not have edit access', 1);
	}
	


	/*****************************
	 *****  CMD versioning  ******
	 *****************************/

	/**
	 * Creates a new version of a page including content and possible subpages.
	 *
	 * @param	integer		Page uid to create new version of.
	 * @param	string		Version label
	 * @param	integer		Indicating "treeLevel" - "page" (0) or "branch" (>=1) ["element" type must call versionizeRecord() directly]
	 * @return	void
	 * @see copyPages()
	 */
	protected function versionizePages($uid, $label, $versionizeTree, &$tcemainObj) {
		global $TCA;

		$uid = intval($uid);
			// returns the branch
		$brExist = $tcemainObj->doesBranchExist('', $uid, $tcemainObj->pMap['show'], 1);

			// Checks if we had permissions
		if ($brExist != -1) {

				// Make list of tables that should come along with a new version of the page:
			$verTablesArray = array();
			$allTables = array_keys($TCA);
			foreach ($allTables as $tableName) {
				if ($tableName != 'pages' && ($versionizeTree > 0 || $TCA[$tableName]['ctrl']['versioning_followPages'])) {
					$verTablesArray[] = $tableName;
				}
			}

				// Begin to copy pages if we're allowed to:
			if ($tcemainObj->BE_USER->workspaceVersioningTypeAccess($versionizeTree)) {

					// Versionize this page:
				$theNewRootID = $tcemainObj->versionizeRecord('pages', $uid, $label, FALSE, $versionizeTree);
				if ($theNewRootID) {
					$tcemainObj->rawCopyPageContent($uid, $theNewRootID, $verTablesArray, $tcemainObj);

						// If we're going to copy recursively...:
					if ($versionizeTree > 0) {

							// Get ALL subpages to copy (read permissions respected - they should NOT be...):
						$CPtable = $tcemainObj->int_pageTreeInfo(array(), $uid, intval($versionizeTree), $theNewRootID);

							// Now copying the subpages
						foreach ($CPtable as $thePageUid => $thePagePid)	{
							$newPid = $tcemainObj->copyMappingArray['pages'][$thePagePid];
							if (isset($newPid)) {
								$theNewRootID = $tcemainObj->copyRecord_raw('pages', $thePageUid, $newPid);
								$tcemainObj->rawCopyPageContent($thePageUid, $theNewRootID, $verTablesArray, $tcemainObj);
							} else {
								$tcemainObj->newlog('Something went wrong during copying branch (for versioning)', 1);
								break;
							}
						}
					}	// else the page was not copied. Too bad...
				} else $tcemainObj->newlog('The root version could not be created!',1);
			} else $tcemainObj->newlog('Versioning type "'.$versionizeTree.'" was not allowed in workspace',1);
		} else $tcemainObj->newlog('Could not read all subpages to versionize.',1);
	}


	/**
	 * Swapping versions of a record
	 * Version from archive (future/past, called "swap version") will get the uid of the "t3ver_oid", the official element with uid = "t3ver_oid" will get the new versions old uid. PIDs are swapped also
	 *
	 * @param	string		Table name
	 * @param	integer		UID of the online record to swap
	 * @param	integer		UID of the archived version to swap with!
	 * @param	boolean		If set, swaps online into workspace instead of publishing out of workspace.
	 * @return	void
	 */
	protected function version_swap($table, $id, $swapWith, $swapIntoWS=0, &$tcemainObj) {
		global $TCA;

			// First, check if we may actually edit the online record
		if ($tcemainObj->checkRecordUpdateAccess($table, $id)) {

				// Select the two versions:
			$curVersion = t3lib_BEfunc::getRecord($table, $id, '*');
			$swapVersion = t3lib_BEfunc::getRecord($table, $swapWith, '*');
			$movePlh = array();
			$movePlhID = 0;

			if (is_array($curVersion) && is_array($swapVersion)) {
				if ($tcemainObj->BE_USER->workspacePublishAccess($swapVersion['t3ver_wsid'])) {
					$wsAccess = $tcemainObj->BE_USER->checkWorkspace($swapVersion['t3ver_wsid']);
					if ($swapVersion['t3ver_wsid'] <= 0 || !($wsAccess['publish_access'] & 1) || (int)$swapVersion['t3ver_stage'] === 10) {
						if ($tcemainObj->doesRecordExist($table,$swapWith,'show') && $tcemainObj->checkRecordUpdateAccess($table,$swapWith)) {
							if (!$swapIntoWS || $tcemainObj->BE_USER->workspaceSwapAccess()) {

									// Check if the swapWith record really IS a version of the original!
								if ((int)$swapVersion['pid'] == -1 && (int)$curVersion['pid'] >= 0 && !strcmp($swapVersion['t3ver_oid'], $id)) {

										// Lock file name:
									$lockFileName = PATH_site.'typo3temp/swap_locking/' . $table . ':' . $id . '.ser';

									if (!@is_file($lockFileName))	{

											// Write lock-file:
										t3lib_div::writeFileToTypo3tempDir($lockFileName, serialize(array(
											'tstamp' => $GLOBALS['EXEC_TIME'],
											'user'   => $tcemainObj->BE_USER->user['username'],
											'curVersion'  => $curVersion,
											'swapVersion' => $swapVersion
										)));

											// Find fields to keep
										$keepFields = $tcemainObj->getUniqueFields($table);
										if ($TCA[$table]['ctrl']['sortby']) {
											$keepFields[] = $TCA[$table]['ctrl']['sortby'];
										}
											// l10n-fields must be kept otherwise the localization 
											// will be lost during the publishing
										if (!isset($TCA[$table]['ctrl']['transOrigPointerTable']) && $TCA[$table]['ctrl']['transOrigPointerField']) {
											$keepFields[] = $TCA[$table]['ctrl']['transOrigPointerField'];
										}

											// Swap "keepfields"
										foreach ($keepFields as $fN) {
											$tmp = $swapVersion[$fN];
											$swapVersion[$fN] = $curVersion[$fN];
											$curVersion[$fN] = $tmp;
										}

											// Preserve states:
										$t3ver_state = array();
										$t3ver_state['swapVersion'] = $swapVersion['t3ver_state'];
										$t3ver_state['curVersion'] = $curVersion['t3ver_state'];

											// Modify offline version to become online:
										$tmp_wsid = $swapVersion['t3ver_wsid'];
											// Set pid for ONLINE
										$swapVersion['pid'] = intval($curVersion['pid']);
											// We clear this because t3ver_oid only make sense for offline versions
											// and we want to prevent unintentional misuse of this
											// value for online records.
										$swapVersion['t3ver_oid'] = 0;
											// In case of swapping and the offline record has a state
											// (like 2 or 4 for deleting or move-pointer) we set the
											// current workspace ID so the record is not deselected
											// in the interface by t3lib_BEfunc::versioningPlaceholderClause()
										$swapVersion['t3ver_wsid'] = 0;
										if ($swapIntoWS) {
											if ($t3ver_state['swapVersion'] > 0) {
												$swapVersion['t3ver_wsid'] = $tcemainObj->BE_USER->workspace;
											} else {
												$swapVersion['t3ver_wsid'] = intval($curVersion['t3ver_wsid']);
											}
										}
										$swapVersion['t3ver_tstamp'] = $GLOBALS['EXEC_TIME'];
										$swapVersion['t3ver_stage'] = 0;
										if (!$swapIntoWS) {
											$swapVersion['t3ver_state'] = 0;
										}

											// Moving element.
										if ((int)$TCA[$table]['ctrl']['versioningWS']>=2)	{		//  && $t3ver_state['swapVersion']==4   // Maybe we don't need this?
											if ($plhRec = t3lib_BEfunc::getMovePlaceholder($table, $id, 't3ver_state,pid,uid' . ($TCA[$table]['ctrl']['sortby'] ? ',' . $TCA[$table]['ctrl']['sortby'] : ''))) {
												$movePlhID = $plhRec['uid'];
												$movePlh['pid'] = $swapVersion['pid'];
												$swapVersion['pid'] = intval($plhRec['pid']);

												$curVersion['t3ver_state'] = intval($swapVersion['t3ver_state']);
												$swapVersion['t3ver_state'] = 0;

												if ($TCA[$table]['ctrl']['sortby']) {
														// sortby is a "keepFields" which is why this will work...
													$movePlh[$TCA[$table]['ctrl']['sortby']] = $swapVersion[$TCA[$table]['ctrl']['sortby']];
													$swapVersion[$TCA[$table]['ctrl']['sortby']] = $plhRec[$TCA[$table]['ctrl']['sortby']];
												}
											}
										}

											// Take care of relations in each field (e.g. IRRE):
										if (is_array($GLOBALS['TCA'][$table]['columns'])) {
											foreach ($GLOBALS['TCA'][$table]['columns'] as $field => $fieldConf) {
												$this->version_swap_procBasedOnFieldType(
													$table, $field, $fieldConf['config'], $curVersion, $swapVersion, $tcemainObj
												);
											}
										}
										unset($swapVersion['uid']);

											// Modify online version to become offline:
										unset($curVersion['uid']);
											// Set pid for OFFLINE
										$curVersion['pid'] = -1;
										$curVersion['t3ver_oid'] = intval($id);
										$curVersion['t3ver_wsid'] = ($swapIntoWS ? intval($tmp_wsid) : 0);
										$curVersion['t3ver_tstamp'] = $GLOBALS['EXEC_TIME'];
										$curVersion['t3ver_count'] = $curVersion['t3ver_count']+1;	// Increment lifecycle counter
										$curVersion['t3ver_stage'] = 0;
										if (!$swapIntoWS) {
											$curVersion['t3ver_state'] = 0;
										}

											// Keeping the swapmode state
										if ($table === 'pages') {
											$curVersion['t3ver_swapmode'] = $swapVersion['t3ver_swapmode'];
										}

											// Registering and swapping MM relations in current and swap records:
										$tcemainObj->version_remapMMForVersionSwap($table, $id, $swapWith);

											// Generating proper history data to prepare logging
										$tcemainObj->compareFieldArrayWithCurrentAndUnset($table, $id, $swapVersion);
										$tcemainObj->compareFieldArrayWithCurrentAndUnset($table, $swapWith, $curVersion);

											// Execute swapping:
										$sqlErrors = array();
										$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid=' . intval($id), $swapVersion);
										if ($GLOBALS['TYPO3_DB']->sql_error()) {
											$sqlErrors[] = $GLOBALS['TYPO3_DB']->sql_error();
										} else {
											$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid=' . intval($swapWith), $curVersion);
											if ($GLOBALS['TYPO3_DB']->sql_error()) {
												$sqlErrors[] = $GLOBALS['TYPO3_DB']->sql_error();
											} else {
												unlink($lockFileName);
											}
										}

										if (!count($sqlErrors)) {

												// If a moving operation took place...:
											if ($movePlhID) {
													// Remove, if normal publishing:
												if (!$swapIntoWS) {
													 	// For delete + completely delete!
													$tcemainObj->deleteEl($table, $movePlhID, TRUE, TRUE);
												} else {
													// Otherwise update the movePlaceholder:
													$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid=' . intval($movePlhID), $movePlh);
													$tcemainObj->updateRefIndex($table, $movePlhID);
												}
											}

												// Checking for delete:
												// Delete only if new/deleted placeholders are there.
											if (!$swapIntoWS && ((int)$t3ver_state['swapVersion'] === 1 || (int)$t3ver_state['swapVersion'] === 2)) {
													// Force delete
												$tcemainObj->deleteEl($table, $id, TRUE);
											}

											$tcemainObj->newlog2(($swapIntoWS ? 'Swapping' : 'Publishing') . ' successful for table "' . $table . '" uid ' . $id . '=>' . $swapWith, $table, $id, $swapVersion['pid']);

												// Update reference index of the live record:
											$tcemainObj->updateRefIndex($table, $id);

												// Set log entry for live record:
											$propArr = $tcemainObj->getRecordPropertiesFromRow($table, $swapVersion);
											if ($propArr['_ORIG_pid'] == -1) {
												$label = $GLOBALS['LANG']->sL ('LLL:EXT:lang/locallang_tcemain.xml:version_swap.offline_record_updated');
											} else {
												$label = $GLOBALS['LANG']->sL ('LLL:EXT:lang/locallang_tcemain.xml:version_swap.online_record_updated');
											}
											$theLogId = $tcemainObj->log($table, $id, 2, $propArr['pid'], 0, $label , 10, array($propArr['header'], $table . ':' . $id), $propArr['event_pid']);
											$tcemainObj->setHistory($table, $id, $theLogId);

												// Update reference index of the offline record:
											$tcemainObj->updateRefIndex($table, $swapWith);
												// Set log entry for offline record:
											$propArr = $tcemainObj->getRecordPropertiesFromRow($table, $curVersion);
											if ($propArr['_ORIG_pid'] == -1) {
												$label = $GLOBALS['LANG']->sL ('LLL:EXT:lang/locallang_tcemain.xml:version_swap.offline_record_updated');
											} else {
												$label = $GLOBALS['LANG']->sL ('LLL:EXT:lang/locallang_tcemain.xml:version_swap.online_record_updated');
											}
											$theLogId = $tcemainObj->log($table, $swapWith, 2, $propArr['pid'], 0, $label, 10, array($propArr['header'], $table . ':' . $swapWith), $propArr['event_pid']);
											$tcemainObj->setHistory($table, $swapWith, $theLogId);

												// SWAPPING pids for subrecords:
											if ($table=='pages' && $swapVersion['t3ver_swapmode'] >= 0) {

													// Collect table names that should be copied along with the tables:
												foreach ($TCA as $tN => $tCfg)	{
														// For "Branch" publishing swap ALL, 
														// otherwise for "page" publishing, swap only "versioning_followPages" tables
													if ($swapVersion['t3ver_swapmode'] > 0 || $TCA[$tN]['ctrl']['versioning_followPages']) {
														$temporaryPid = -($id+1000000);

														$GLOBALS['TYPO3_DB']->exec_UPDATEquery($tN, 'pid=' . intval($id), array('pid' => $temporaryPid));
														if ($GLOBALS['TYPO3_DB']->sql_error()) {
															$sqlErrors[] = $GLOBALS['TYPO3_DB']->sql_error();
														}

														$GLOBALS['TYPO3_DB']->exec_UPDATEquery($tN, 'pid=' . intval($swapWith), array('pid' => $id));
														if ($GLOBALS['TYPO3_DB']->sql_error()) {
															$sqlErrors[] = $GLOBALS['TYPO3_DB']->sql_error();
														}

														$GLOBALS['TYPO3_DB']->exec_UPDATEquery($tN, 'pid=' . intval($temporaryPid), array('pid' => $swapWith));
														if ($GLOBALS['TYPO3_DB']->sql_error()) {
															$sqlErrors[] = $GLOBALS['TYPO3_DB']->sql_error();
														}

														if (count($sqlErrors)) {
															$tcemainObj->newlog('During Swapping: SQL errors happened: ' . implode('; ', $sqlErrors), 2);
														}
													}
												}
											}
												// Clear cache:
											$tcemainObj->clear_cache($table, $id);

												// Checking for "new-placeholder" and if found, delete it (BUT FIRST after swapping!):
											if (!$swapIntoWS && $t3ver_state['curVersion']>0) {
												 	// For delete + completely delete!
												$tcemainObj->deleteEl($table, $swapWith, TRUE, TRUE);
											}
										} else $tcemainObj->newlog('During Swapping: SQL errors happened: ' . implode('; ', $sqlErrors), 2);
									} else $tcemainObj->newlog('A swapping lock file was present. Either another swap process is already running or a previous swap process failed. Ask your administrator to handle the situation.', 2);
								} else $tcemainObj->newlog('In swap version, either pid was not -1 or the t3ver_oid didn\'t match the id of the online version as it must!', 2);
							} else $tcemainObj->newlog('Workspace #' . $swapVersion['t3ver_wsid'] . ' does not support swapping.', 1);
						} else $tcemainObj->newlog('You cannot publish a record you do not have edit and show permissions for', 1);
					} else $tcemainObj->newlog('Records in workspace #' . $swapVersion['t3ver_wsid'] . ' can only be published when in "Publish" stage.', 1);
				} else $tcemainObj->newlog('User could not publish records from workspace #' . $swapVersion['t3ver_wsid'], 1);
			} else $tcemainObj->newlog('Error: Either online or swap version could not be selected!', 2);
		} else $tcemainObj->newlog('Error: You cannot swap versions for a record you do not have access to edit!', 1);
	}


	/**
	 * Update relations on version/workspace swapping.
	 *
	 * @param	string		$table: Record Table
	 * @param	string		$field: Record field
	 * @param	array		$conf: TCA configuration of current field
	 * @param	string		$curVersion: Reference to the current (original) record
	 * @param	string		$swapVersion: Reference to the record (workspace/versionized) to publish in or swap with
	 * @return 	void
	 */
	protected function version_swap_procBasedOnFieldType($table, $field, $conf, &$curVersion, &$swapVersion, $tcemainObj) {
		$inlineType = $tcemainObj->getInlineFieldType($conf);

			// Process pointer fields on normalized database:
		if ($inlineType == 'field') {
				// Read relations that point to the current record (e.g. live record):
			$dbAnalysisCur = t3lib_div::makeInstance('t3lib_loadDBGroup');
			$dbAnalysisCur->start('', $conf['foreign_table'], '', $curVersion['uid'], $table, $conf);
				// Read relations that point to the record to be swapped with e.g. draft record):
			$dbAnalysisSwap = t3lib_div::makeInstance('t3lib_loadDBGroup');
			$dbAnalysisSwap->start('', $conf['foreign_table'], '', $swapVersion['uid'], $table, $conf);
				// Update relations for both (workspace/versioning) sites:
			$dbAnalysisCur->writeForeignField($conf, $curVersion['uid'], $swapVersion['uid']);
			$dbAnalysisSwap->writeForeignField($conf, $swapVersion['uid'], $curVersion['uid']);

			// Swap field values (CSV):
			// BUT: These values will be swapped back in the next steps, when the *CHILD RECORD ITSELF* is swapped!
		} elseif ($inlineType == 'list') {
			$tempValue = $curVersion[$field];
			$curVersion[$field] = $swapVersion[$field];
			$swapVersion[$field] = $tempValue;
		}
	}



	/**
	 * Release version from this workspace (and into "Live" workspace but as an offline version).
	 *
	 * @param	string		Table name
	 * @param	integer		Record UID
 	 * @param	boolean		If set, will completely delete element
	 * @return	void
	 */
	protected function version_clearWSID($table, $id, $flush = FALSE, &$tcemainObj) {
		global $TCA;

		if ($errorCode = $tcemainObj->BE_USER->workspaceCannotEditOfflineVersion($table, $id)) {
			$tcemainObj->newlog('Attempt to reset workspace for record failed: ' . $errorCode, 1);
		} elseif ($tcemainObj->checkRecordUpdateAccess($table, $id)) {
			if ($liveRec = t3lib_BEfunc::getLiveVersionOfRecord($table, $id, 'uid,t3ver_state')) {
					// Clear workspace ID:
				$updateData = array(
					't3ver_wsid' => 0
				);
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid=' . intval($id), $updateData);

					// Clear workspace ID for live version AND DELETE IT as well because it is a new record!
				if ((int) $liveRec['t3ver_state'] == 1 || (int) $liveRec['t3ver_state'] == 2) {
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table,'uid=' . intval($liveRec['uid']), $updateData);
						// THIS assumes that the record was placeholder ONLY for ONE record (namely $id)
					$tcemainObj->deleteEl($table, $liveRec['uid'], TRUE);
				}

					// If "deleted" flag is set for the version that got released
					// it doesn't make sense to keep that "placeholder" anymore and we delete it completly.
				$wsRec = t3lib_BEfunc::getRecord($table, $id);
				if ($flush || ((int) $wsRec['t3ver_state'] == 1 || (int) $wsRec['t3ver_state'] == 2)) {
					$tcemainObj->deleteEl($table, $id, TRUE, TRUE);
				}

					// Remove the move-placeholder if found for live record.
				if ((int)$TCA[$table]['ctrl']['versioningWS'] >= 2) {
					if ($plhRec = t3lib_BEfunc::getMovePlaceholder($table, $liveRec['uid'], 'uid')) {
						$tcemainObj->deleteEl($table, $plhRec['uid'], TRUE, TRUE);
					}
				}
			}
		} else $tcemainObj->newlog('Attempt to reset workspace for record failed because you do not have edit access',1);
	}


	/*******************************
	 *****  helper functions  ******
	 *******************************/


	/**
	 * Copies all records from tables in $copyTablesArray from page with $old_pid to page with $new_pid
	 * Uses raw-copy for the operation (meant for versioning!)
	 *
	 * @param	integer		Current page id.
	 * @param	integer		New page id
	 * @param	array		Array of tables from which to copy
	 * @return	void
	 * @see versionizePages()
	 */
	protected function rawCopyPageContent($oldPageId, $newPageId, $copyTablesArray, &$tcemainObj) {
		global $TCA;

		if ($newPageId) {
			foreach ($copyTablesArray as $table) {
						// all records under the page is copied.
				if ($table && is_array($TCA[$table]) && $table != 'pages') {
					$mres = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'uid',
						$table,
						'pid=' . intval($oldPageId) . $tcemainObj->deleteClause($table)
					);
					while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($mres)) {
							// Check, if this record has already been copied by a parent record as relation:
						if (!$this->copyMappingArray[$table][$row['uid']]) {
								// Copying each of the underlying records (method RAW)
							$tcemainObj->copyRecord_raw($table, $row['uid'], $newPageId);
						}
					}
					$GLOBALS['TYPO3_DB']->sql_free_result($mres);
				}
			}
		}
	}


	/**
	 * Finds all elements for swapping versions in workspace
	 *
	 * @param 	string	$table	Table name of the original element to swap
	 * @param	int	$id	UID of the original element to swap (online)
	 * @param	int	$offlineId As above but offline
	 * @return	array	Element data. Key is table name, values are array with first element as online UID, second - offline UID
	 */
	protected function findPageElementsForVersionSwap($table, $id, $offlineId) {
		global	$TCA;

		$rec = t3lib_BEfunc::getRecord($table, $offlineId, 't3ver_wsid');
		$workspaceId = $rec['t3ver_wsid'];

		$elementData = array();
		if ($workspaceId != 0) {
			// Get page UID for LIVE and workspace
			if ($table != 'pages') {
				$rec = t3lib_BEfunc::getRecord($table, $id, 'pid');
				$pageId = $rec['pid'];
				$rec = t3lib_BEfunc::getRecord('pages', $pageId);
				t3lib_BEfunc::workspaceOL('pages', $rec, $workspaceId);
				$offlinePageId = $rec['_ORIG_uid'];
			} else {
				$pageId = $id;
				$offlinePageId = $offlineId;
			}

			// Traversing all tables supporting versioning:
			foreach ($TCA as $table => $cfg) {
				if ($TCA[$table]['ctrl']['versioningWS'] && $table != 'pages') {
					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('A.uid AS offlineUid, B.uid AS uid',
							$table . ' A,' . $table . ' B',
							'A.pid=-1 AND B.pid=' . $pageId . ' AND A.t3ver_wsid=' . $workspaceId .
							' AND B.uid=A.t3ver_oid' .
							t3lib_BEfunc::deleteClause($table, 'A') . t3lib_BEfunc::deleteClause($table, 'B'));
					while (FALSE != ($row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res))) {
						$elementData[$table][] = array($row[1], $row[0]);
					}
					$GLOBALS['TYPO3_DB']->sql_free_result($res);
				}
			}
			if ($offlinePageId && $offlinePageId != $pageId) {
				$elementData['pages'][] = array($pageId, $offlinePageId);
			}
		}
		return $elementData;
	}

	/**
	 * Searches for all elements from all tables on the given pages in the same workspace.
	 *
	 * @param	array	$pageIdList	List of PIDs to search
	 * @param	int	$workspaceId	Workspace ID
	 * @param	array	$elementList	List of found elements. Key is table name, value is array of element UIDs
	 * @return	void
	 */
	protected function findPageElementsForVersionStageChange($pageIdList, $workspaceId, &$elementList) {
		global $TCA;

		if ($workspaceId != 0) {
				// Traversing all tables supporting versioning:
			foreach ($TCA as $table => $cfg)	{
				if ($TCA[$table]['ctrl']['versioningWS'] && $table != 'pages')	{
					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('DISTINCT A.uid',
						$table . ' A,' . $table . ' B',
						'A.pid=-1' .		// Offline version
						' AND A.t3ver_wsid=' . $workspaceId .
						' AND B.pid IN (' . implode(',', $pageIdList) . ') AND A.t3ver_oid=B.uid' .
						t3lib_BEfunc::deleteClause($table,'A').
						t3lib_BEfunc::deleteClause($table,'B')
					);
					while (FALSE !== ($row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res))) {
						$elementList[$table][] = $row[0];
					}
					$GLOBALS['TYPO3_DB']->sql_free_result($res);
					if (is_array($elementList[$table])) {
						// Yes, it is possible to get non-unique array even with DISTINCT above! 
						// It happens because several UIDs are passed in the array already.
						$elementList[$table] = array_unique($elementList[$table]);
					}
				}
			}
		}
	}


	/**
	 * Finds page UIDs for the element from table <code>$table</code> with UIDs from <code>$idList</code>
	 *
	 * @param	array	$table	Table to search
	 * @param	array	$idList	List of records' UIDs
	 * @param	int	$workspaceId	Workspace ID. We need this parameter because user can be in LIVE but he still can publisg DRAFT from ws module!
	 * @param	array	$pageIdList	List of found page UIDs
	 * @param	array	$elementList	List of found element UIDs. Key is table name, value is list of UIDs
	 * @return	void
	 */
	protected function findPageIdsForVersionStateChange($table, $idList, $workspaceId, &$pageIdList, &$elementList) {
		if ($workspaceId != 0) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('DISTINCT B.pid',
				$table . ' A,' . $table . ' B',
				'A.pid=-1' .		// Offline version
				' AND A.t3ver_wsid=' . $workspaceId .
				' AND A.uid IN (' . implode(',', $idList) . ') AND A.t3ver_oid=B.uid' .
				t3lib_BEfunc::deleteClause($table,'A').
				t3lib_BEfunc::deleteClause($table,'B')
			);
			while (FALSE !== ($row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res))) {
				$pageIdList[] = $row[0];
					// Find ws version
					// Note: cannot use t3lib_BEfunc::getRecordWSOL() 
					// here because it does not accept workspace id!
				$rec = t3lib_BEfunc::getRecord('pages', $row[0]);
				t3lib_BEfunc::workspaceOL('pages', $rec, $workspaceId);
				if ($rec['_ORIG_uid']) {
					$elementList['pages'][$row[0]] = $rec['_ORIG_uid'];
				}
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
				// The line below is necessary even with DISTINCT
				// because several elements can be passed by caller
			$pageIdList = array_unique($pageIdList);
		}
	}


	/**
	 * Finds real page IDs for state change.
	 *
	 * @param	array	$idList	List of page UIDs, possibly versioned
	 * @return	void
	 */
	protected function findRealPageIds(&$idList) {
		foreach ($idList as $key => $id) {
			$rec = t3lib_BEfunc::getRecord('pages', $id, 't3ver_oid');
			if ($rec['t3ver_oid'] > 0) {
				$idList[$key] = $rec['t3ver_oid'];
			}
		}
	}
}

?>