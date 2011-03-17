<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
*  (c) 2010-2011 Benjamin Mack (benni@typo3.org)
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

	/**
	 * For accumulating information about workspace stages raised
	 * on elements so a single mail is sent as notification.
	 * previously called "accumulateForNotifEmail" in tcemain
	 *
	 * @var array
	 */
	protected $notificationEmailInfo = array();

	/**
	 * General comment, eg. for staging in workspaces
	 *
	 * @var string
	 */
	protected $generalComment = '';

	/**
	 * Contains remapped IDs.
	 *
	 * @var array
	 */
	protected $remappedIds = array();

	/****************************
	 *****  Cmdmap  Hooks  ******
	 ****************************/

	/**
	 * hook that is called before any cmd of the commandmap is executed
	 *
	 * @param t3lib_TCEmain $tcemainObj reference to the main tcemain object
	 * @return void
	 */
	public function processCmdmap_beforeStart(t3lib_TCEmain $tcemainObj) {
			// Reset notification array
		$this->notificationEmailInfo = array();
			// Resolve dependencies of version/workspaces actions:
		$tcemainObj->cmdmap = $this->getCommandMap($tcemainObj, $tcemainObj->cmdmap)->process()->get();
	}


	/**
	 * hook that is called when no prepared command was found
	 *
	 * @param string $command the command to be executed
	 * @param string $table the table of the record
	 * @param integer $id the ID of the record
	 * @param mixed $value the value containing the data
	 * @param boolean $commandIsProcessed can be set so that other hooks or
	 * 				TCEmain knows that the default cmd doesn't have to be called
	 * @param t3lib_TCEmain $tcemainObj reference to the main tcemain object
	 * @return	void
	 */
	public function processCmdmap($command, $table, $id, $value, &$commandIsProcessed, t3lib_TCEmain $tcemainObj) {

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
					$this->version_swap($table, $id, $value['swapWith'], $value['swapIntoWS'], $tcemainObj);
				break;

				case 'clearWSID':
					$this->version_clearWSID($table, $id, FALSE, $tcemainObj);
				break;

				case 'flush':
					$this->version_clearWSID($table, $id, TRUE, $tcemainObj);
				break;

				case 'setStage':
					$elementIds = t3lib_div::trimExplode(',', $id, TRUE);
					foreach ($elementIds as $elementId) {
						$this->version_setStage($table, $elementId, $value['stageId'],
							(isset($value['comment']) && $value['comment'] ? $value['comment'] : $this->generalComment),
							TRUE,
							$tcemainObj,
							$value['notificationAlternativeRecipients']
						);
					}
				break;
			}
		}
	}

	/**
	 * hook that is called AFTER all commands of the commandmap was
	 * executed
	 *
	 * @param t3lib_TCEmain $tcemainObj reference to the main tcemain object
	 * @return	void
	 */
	public function processCmdmap_afterFinish(t3lib_TCEmain $tcemainObj) {
			// Empty accumulation array:
		foreach ($this->notificationEmailInfo as $notifItem) {
			$this->notifyStageChange($notifItem['shared'][0], $notifItem['shared'][1], implode(', ', $notifItem['elements']), 0, $notifItem['shared'][2], $tcemainObj, $notifItem['alternativeRecipients']);
		}

			// Reset notification array
		$this->notificationEmailInfo = array();
			// Reset remapped IDs
		$this->remappedIds = array();
	}


	/**
	 * hook that is called AFTER all commands of the commandmap was
	 * executed
	 *
	 * @param string $table the table of the record
	 * @param integer $id the ID of the record
	 * @param array $record The accordant database record
	 * @param boolean $recordWasDeleted can be set so that other hooks or
	 * 				TCEmain knows that the default delete action doesn't have to be called
	 * @param t3lib_TCEmain $tcemainObj reference to the main tcemain object
	 * @return	void
	 */
	public function processCmdmap_deleteAction($table, $id, array $record, &$recordWasDeleted, t3lib_TCEmain $tcemainObj) {
			// only process the hook if it wasn't processed
			// by someone else before
		if (!$recordWasDeleted) {
			$recordWasDeleted = TRUE;

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
				if ($GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
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
				$copyMappingArray = $tcemainObj->copyMappingArray;
				$tcemainObj->versionizeRecord($table, $id, 'DELETED!', TRUE);

				// Determine newly created versions:
				$versionzedElements = t3lib_div::arrayDiffAssocRecursive(
					$tcemainObj->copyMappingArray,
					$copyMappingArray
				);
				// Delete localization overlays:
				foreach ($versionzedElements as $versionizedTableName => $versionizedOriginalIds) {
					foreach ($versionizedOriginalIds as $versionzedOriginalId => $_) {
						$tcemainObj->deleteL10nOverlayRecords($versionizedTableName, $versionzedOriginalId);
					}
				}
			}
		}
	}


	/**
	 * hook for t3lib_TCEmain::moveRecord that cares about moving records that
	 * are *not* in the live workspace
	 *
	 * @param string $table the table of the record
	 * @param integer $id the ID of the record
	 * @param integer $destPid Position to move to: $destPid: >=0 then it points to
	 * 				a page-id on which to insert the record (as the first element).
	 * 				<0 then it points to a uid from its own table after which to insert it
	 * @param array $propArr Record properties, like header and pid (includes workspace overlay)
	 * @param array $moveRec Record properties, like header and pid (without workspace overlay)
	 * @param integer $resolvedPid The final page ID of the record
	 * 				(workspaces and negative values are resolved)
	 * @param boolean $recordWasMoved can be set so that other hooks or
	 * 				TCEmain knows that the default move action doesn't have to be called
	 * @param	$table	the table
	 */
	public function moveRecord($table, $uid, $destPid, array $propArr, array $moveRec, $resolvedPid, &$recordWasMoved, t3lib_TCEmain $tcemainObj) {
		global $TCA;

			// Only do something in Draft workspace
		if ($tcemainObj->BE_USER->workspace !== 0) {
			$recordWasMoved = TRUE;

				// Get workspace version of the source record, if any:
			$WSversion = t3lib_BEfunc::getWorkspaceVersionOfRecord($tcemainObj->BE_USER->workspace, $table, $uid, 'uid,t3ver_oid');

				// If no version exists and versioningWS is in version 2, a new placeholder is made automatically:
			if (!$WSversion['uid'] && (int)$TCA[$table]['ctrl']['versioningWS']>=2 && (int)$moveRec['t3ver_state']!=3)	{
				$tcemainObj->versionizeRecord($table, $uid, 'Placeholder version for moving record');
				$WSversion = t3lib_BEfunc::getWorkspaceVersionOfRecord($tcemainObj->BE_USER->workspace, $table, $uid, 'uid,t3ver_oid');	// Will not create new versions in live workspace though...
			}

				// Check workspace permissions:
			$workspaceAccessBlocked = array();
				// Element was in "New/Deleted/Moved" so it can be moved...
			$recIsNewVersion = (int)$moveRec['t3ver_state']>0;

			$destRes = $tcemainObj->BE_USER->workspaceAllowLiveRecordsInPID($resolvedPid, $table);
			$canMoveRecord = $recIsNewVersion || (int)$TCA[$table]['ctrl']['versioningWS'] >= 2;

				// Workspace source check:
			if (!$recIsNewVersion) {
				$errorCode = $tcemainObj->BE_USER->workspaceCannotEditRecord($table, $WSversion['uid'] ? $WSversion['uid'] : $uid);
				if ($errorCode) {
					$workspaceAccessBlocked['src1'] = 'Record could not be edited in workspace: ' . $errorCode . ' ';
				} elseif (!$canMoveRecord && $tcemainObj->BE_USER->workspaceAllowLiveRecordsInPID($moveRec['pid'], $table) <= 0) {
					$workspaceAccessBlocked['src2'] = 'Could not remove record from table "' . $table . '" from its page "'.$moveRec['pid'].'" ';
				}
			}

				// Workspace destination check:

				// All records can be inserted if $destRes is greater than zero.
				// Only new versions can be inserted if $destRes is false.
				// NO RECORDS can be inserted if $destRes is negative which indicates a stage
				//  not allowed for use. If "versioningWS" is version 2, moving can take place of versions.
			if (!($destRes > 0 || ($canMoveRecord && !$destRes))) {
				$workspaceAccessBlocked['dest1'] = 'Could not insert record from table "' . $table . '" in destination PID "' . $resolvedPid . '" ';
			} elseif ($destRes == 1 && $WSversion['uid']) {
				$workspaceAccessBlocked['dest2'] = 'Could not insert other versions in destination PID ';
			}

			if (!count($workspaceAccessBlocked)) {
					// If the move operation is done on a versioned record, which is
					// NOT new/deleted placeholder and versioningWS is in version 2, then...
				if ($WSversion['uid'] && !$recIsNewVersion && (int)$TCA[$table]['ctrl']['versioningWS'] >= 2) {
					$this->moveRecord_wsPlaceholders($table, $uid, $destPid, $WSversion['uid'], $tcemainObj);
				} else {
					// moving not needed, just behave like in live workspace
					$recordWasMoved = FALSE;
				}
			} else {
				$tcemainObj->newlog("Move attempt failed due to workspace restrictions: " . implode(' // ', $workspaceAccessBlocked), 1);
			}
		}
	}



	/****************************
	 *****  Notifications  ******
	 ****************************/

	/**
	 * Send an email notification to users in workspace
	 *
	 * @param array $stat Workspace access array (from t3lib_userauthgroup::checkWorkspace())
	 * @param integer $stageId New Stage number: 0 = editing, 1= just ready for review, 10 = ready for publication, -1 = rejected!
	 * @param string $table Table name of element (or list of element names if $id is zero)
	 * @param integer $id Record uid of element (if zero, then $table is used as reference to element(s) alone)
	 * @param string $comment User comment sent along with action
	 * @param t3lib_TCEmain $tcemainObj TCEmain object
	 * @param string $notificationAlternativeRecipients Comma separated list of recipients to notificate instead of be_users selected by sys_workspace, list is generated by workspace extension module
	 * @return void
	 */
	protected function notifyStageChange(array $stat, $stageId, $table, $id, $comment, t3lib_TCEmain $tcemainObj, $notificationAlternativeRecipients = FALSE) {
		$workspaceRec = t3lib_BEfunc::getRecord('sys_workspace', $stat['uid']);
			// So, if $id is not set, then $table is taken to be the complete element name!
		$elementName = $id ? $table . ':' . $id : $table;

		if (is_array($workspaceRec)) {

				 // Get the new stage title from workspaces library, if workspaces extension is installed
			if (t3lib_extMgm::isLoaded('workspaces')) {
				$stageService = t3lib_div::makeInstance('Tx_Workspaces_Service_Stages');
				$newStage = $stageService->getStageTitle((int)$stageId);
			} else {
					// TODO: CONSTANTS SHOULD BE USED - tx_service_workspace_workspaces
					// TODO: use localized labels
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
			}

			if ($notificationAlternativeRecipients == false) {
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
#								$emails = $this->getEmailsForStageChangeNotification($workspaceRec['reviewers']);
#								$emails = array_merge($emails,$this->getEmailsForStageChangeNotification($workspaceRec['members']));

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

										$emails = t3lib_div::array_merge($emails, $this->getEmailsForStageChangeNotification($dat['userid'], TRUE));

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
						$emails = t3lib_div::array_merge($emails, $this->getEmailsForStageChangeNotification($workspaceRec['reviewers']));
						$emails = t3lib_div::array_merge($emails, $this->getEmailsForStageChangeNotification($workspaceRec['members']));
					break;
				}
			} else {
				$emails = array();
				foreach ($notificationAlternativeRecipients as $emailAddress) {
					$emails[] = array('email' => $emailAddress);
				}
			}

				// prepare and then send the emails
			if (count($emails)) {

					// Path to record is found:
				list($elementTable, $elementUid) = explode(':', $elementName);
				$elementUid = intval($elementUid);
				$elementRecord = t3lib_BEfunc::getRecord($elementTable, $elementUid);
				$recordTitle = t3lib_BEfunc::getRecordTitle($elementTable, $elementRecord);

				if ($elementTable == 'pages') {
					$pageUid = $elementUid;
				} else {
					t3lib_BEfunc::fixVersioningPid($elementTable, $elementRecord);
					$pageUid = $elementUid = $elementRecord['pid'];
				}

					// fetch the TSconfig settings for the email

					// old way, options are TCEMAIN.notificationEmail_body/subject
				$TCEmainTSConfig = $tcemainObj->getTCEMAIN_TSconfig($pageUid);

					// these options are deprecated since TYPO3 4.5, but are still
					// used in order to provide backwards compatibility
				$emailMessage = trim($TCEmainTSConfig['notificationEmail_body']);
				$emailSubject = trim($TCEmainTSConfig['notificationEmail_subject']);

					// new way, options are
					// pageTSconfig: tx_version.workspaces.stageNotificationEmail.subject
					// userTSconfig: page.tx_version.workspaces.stageNotificationEmail.subject
				$pageTsConfig = t3lib_BEfunc::getPagesTSconfig($pageUid);
				$emailConfig = $pageTsConfig['tx_version.']['workspaces.']['stageNotificationEmail.'];

				$markers = array(
					'###RECORD_TITLE###' => $recordTitle,
					'###RECORD_PATH###' => t3lib_BEfunc::getRecordPath($elementUid, '', 20),
					'###SITE_NAME###' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
					'###SITE_URL###' => t3lib_div::getIndpEnv('TYPO3_SITE_URL') . TYPO3_mainDir,
					'###WORKSPACE_TITLE###' => $workspaceRec['title'],
					'###WORKSPACE_UID###' => $workspaceRec['uid'],
					'###ELEMENT_NAME###' => $elementName,
					'###NEXT_STAGE###' => $newStage,
					'###COMMENT###' => $comment,
					'###USER_REALNAME###' => $tcemainObj->BE_USER->user['realName'],
					'###USER_USERNAME###' => $tcemainObj->BE_USER->user['username']
				);


					// sending the emails the old way with sprintf(),
					// because it was set explicitly in TSconfig
				if ($emailMessage && $emailSubject) {
					t3lib_div::deprecationLog('This TYPO3 installation uses Workspaces staging notification by setting the TSconfig options "TCEMAIN.notificationEmail_subject" / "TCEMAIN.notificationEmail_body". Please use the more flexible marker-based options tx_version.workspaces.stageNotificationEmail.message / tx_version.workspaces.stageNotificationEmail.subject');

					$emailSubject = sprintf($emailSubject, $elementName);
					$emailMessage = sprintf($emailMessage,
						$markers['###SITE_NAME###'],
						$markers['###SITE_URL###'],
						$markers['###WORKSPACE_TITLE###'],
						$markers['###WORKSPACE_UID###'],
						$markers['###ELEMENT_NAME###'],
						$markers['###NEXT_STAGE###'],
						$markers['###COMMENT###'],
						$markers['###USER_REALNAME###'],
						$markers['###USER_USERNAME###'],
						$markers['###RECORD_PATH###'],
						$markers['###RECORD_TITLE###']
					);

						// filter out double email addresses
					$emailRecipients = array();
					foreach ($emails as $recip) {
						$emailRecipients[$recip['email']] = $recip['email'];
					}
					$emailRecipients = implode(',', $emailRecipients);

						// Send one email to everybody
					t3lib_div::plainMailEncoded(
						$emailRecipients,
						$emailSubject,
						$emailMessage
					);
				} else {
						// send an email to each individual user, to ensure the
						// multilanguage version of the email

					$emailHeaders = $emailConfig['additionalHeaders'];
					$emailRecipients = array();

						// an array of language objects that are needed
						// for emails with different languages
					$languageObjects = array(
						$GLOBALS['LANG']->lang => $GLOBALS['LANG']
					);

						// loop through each recipient and send the email
					foreach ($emails as $recipientData) {
							// don't send an email twice
						if (isset($emailRecipients[$recipientData['email']])) {
							continue;
						}
						$emailSubject = $emailConfig['subject'];
						$emailMessage = $emailConfig['message'];
						$emailRecipients[$recipientData['email']] = $recipientData['email'];

							// check if the email needs to be localized
							// in the users' language
						if (t3lib_div::isFirstPartOfStr($emailSubject, 'LLL:') || t3lib_div::isFirstPartOfStr($emailMessage, 'LLL:')) {
							$recipientLanguage = ($recipientData['lang'] ? $recipientData['lang'] : 'default');
							if (!isset($languageObjects[$recipientLanguage])) {
									// a LANG object in this language hasn't been
									// instantiated yet, so this is done here
								/** @var $languageObject language */
								$languageObject = t3lib_div::makeInstance('language');
								$languageObject->init($recipientLanguage);
								$languageObjects[$recipientLanguage] = $languageObject;
							} else {
								$languageObject = $languageObjects[$recipientLanguage];
							}

							if (t3lib_div::isFirstPartOfStr($emailSubject, 'LLL:')) {
								$emailSubject = $languageObject->sL($emailSubject);
							}

							if (t3lib_div::isFirstPartOfStr($emailMessage, 'LLL:')) {
								$emailMessage = $languageObject->sL($emailMessage);
							}
						}

						$emailSubject = t3lib_parseHtml::substituteMarkerArray($emailSubject, $markers, '', TRUE, TRUE);
						$emailMessage = t3lib_parseHtml::substituteMarkerArray($emailMessage, $markers, '', TRUE, TRUE);
							// Send an email to the recipient
						t3lib_div::plainMailEncoded(
							$recipientData['email'],
							$emailSubject,
							$emailMessage,
							$emailHeaders
						);
					}
					$emailRecipients = implode(',', $emailRecipients);
				}
				$tcemainObj->newlog2('Notification email for stage change was sent to "' . $emailRecipients . '"', $table, $id);
			}
		}
	}

	/**
	 * Return be_users that should be notified on stage change from input list.
	 * previously called notifyStageChange_getEmails() in tcemain
	 *
	 * @param	string		$listOfUsers List of backend users, on the form "be_users_10,be_users_2" or "10,2" in case noTablePrefix is set.
	 * @param	boolean		$noTablePrefix If set, the input list are integers and not strings.
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
				if ($userRecord = t3lib_BEfunc::getRecord('be_users', $id, 'uid,email,lang,realName')) {
					if (strlen(trim($userRecord['email']))) {
						$emails[$id] = $userRecord;
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
	 * @param string $table Table name
	 * @param integer $integer Record UID
	 * @param integer $stageId Stage ID to set
	 * @param string $comment Comment that goes into log
	 * @param boolean $notificationEmailInfo Accumulate state changes in memory for compiled notification email?
	 * @param t3lib_TCEmain $tcemainObj TCEmain object
	 * @param string $notificationAlternativeRecipients comma separated list of recipients to notificate instead of normal be_users
	 * @return void
	 */
	protected function version_setStage($table, $id, $stageId, $comment = '', $notificationEmailInfo = FALSE, t3lib_TCEmain $tcemainObj, $notificationAlternativeRecipients = FALSE) {
		if ($errorCode = $tcemainObj->BE_USER->workspaceCannotEditOfflineVersion($table, $id)) {
			$tcemainObj->newlog('Attempt to set stage for record failed: ' . $errorCode, 1);
		} elseif ($tcemainObj->checkRecordUpdateAccess($table, $id)) {
			$record = t3lib_BEfunc::getRecord($table, $id);
			$stat = $tcemainObj->BE_USER->checkWorkspace($record['t3ver_wsid']);
				// check if the usere is allowed to the current stage, so it's also allowed to send to next stage
			if ($GLOBALS['BE_USER']->workspaceCheckStageForCurrent($record['t3ver_stage'])) {

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
						$this->notificationEmailInfo[$stat['uid'] . ':' . $stageId . ':' . $comment]['alternativeRecipients'] = $notificationAlternativeRecipients;
					} else {
						$this->notifyStageChange($stat, $stageId, $table, $id, $comment, $tcemainObj, $notificationAlternativeRecipients);
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
	 * @param integer $uid Page uid to create new version of.
	 * @param string $label Version label
	 * @param integer $versionizeTree Indicating "treeLevel" - "page" (0) or "branch" (>=1) ["element" type must call versionizeRecord() directly]
	 * @param t3lib_TCEmain $tcemainObj TCEmain object
	 * @return void
	 * @see copyPages()
	 */
	protected function versionizePages($uid, $label, $versionizeTree, t3lib_TCEmain $tcemainObj) {
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

				// Remove the possible inline child tables from the tables to be versioniozed automatically:
			$verTablesArray = array_diff($verTablesArray, $this->getPossibleInlineChildTablesOfParentTable('pages'));

				// Begin to copy pages if we're allowed to:
			if ($tcemainObj->BE_USER->workspaceVersioningTypeAccess($versionizeTree)) {

					// Versionize this page:
				$theNewRootID = $tcemainObj->versionizeRecord('pages', $uid, $label, FALSE, $versionizeTree);
				if ($theNewRootID) {
					$this->rawCopyPageContent($uid, $theNewRootID, $verTablesArray, $tcemainObj);

						// If we're going to copy recursively...:
					if ($versionizeTree > 0) {

							// Get ALL subpages to copy (read permissions respected - they should NOT be...):
						$CPtable = $tcemainObj->int_pageTreeInfo(array(), $uid, intval($versionizeTree), $theNewRootID);

							// Now copying the subpages
						foreach ($CPtable as $thePageUid => $thePagePid)	{
							$newPid = $tcemainObj->copyMappingArray['pages'][$thePagePid];
							if (isset($newPid)) {
								$theNewRootID = $tcemainObj->copyRecord_raw('pages', $thePageUid, $newPid);
								$this->rawCopyPageContent($thePageUid, $theNewRootID, $verTablesArray, $tcemainObj);
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
	 * @param string $table Table name
	 * @param integer $id UID of the online record to swap
	 * @param integer $swapWith UID of the archived version to swap with!
	 * @param boolean $swapIntoWS If set, swaps online into workspace instead of publishing out of workspace.
	 * @param t3lib_TCEmain $tcemainObj TCEmain object
	 * @return void
	 */
	protected function version_swap($table, $id, $swapWith, $swapIntoWS=0, t3lib_TCEmain $tcemainObj) {
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
					if ($swapVersion['t3ver_wsid'] <= 0 || !($wsAccess['publish_access'] & 1) || (int)$swapVersion['t3ver_stage'] === -10) {
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
												// Register swapped ids for later remapping:
											$this->remappedIds[$table][$id] =$swapWith;
											$this->remappedIds[$table][$swapWith] = $id;

												// If a moving operation took place...:
											if ($movePlhID) {
													// Remove, if normal publishing:
												if (!$swapIntoWS) {
													 	// For delete + completely delete!
													$tcemainObj->deleteEl($table, $movePlhID, TRUE, TRUE);
												} else {
													// Otherwise update the movePlaceholder:
													$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid=' . intval($movePlhID), $movePlh);
													$tcemainObj->addRemapStackRefIndex($table, $movePlhID);
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
											$tcemainObj->addRemapStackRefIndex($table, $id);

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
											$tcemainObj->addRemapStackRefIndex($table, $swapWith);
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
	 * @param string $table: Record Table
	 * @param string $field: Record field
	 * @param array $conf: TCA configuration of current field
	 * @param array $curVersion: Reference to the current (original) record
	 * @param array $swapVersion: Reference to the record (workspace/versionized) to publish in or swap with
	 * @param t3lib_TCEmain $tcemainObj TCEmain object
	 * @return  void
	 */
	protected function version_swap_procBasedOnFieldType($table, $field, array $conf, array &$curVersion, array &$swapVersion, t3lib_TCEmain $tcemainObj) {
		$inlineType = $tcemainObj->getInlineFieldType($conf);

			// Process pointer fields on normalized database:
		if ($inlineType == 'field') {
			// Read relations that point to the current record (e.g. live record):
			/** @var $dbAnalysisCur t3lib_loadDBGroup */
			$dbAnalysisCur = t3lib_div::makeInstance('t3lib_loadDBGroup');
			$dbAnalysisCur->setUpdateReferenceIndex(FALSE);
			$dbAnalysisCur->start('', $conf['foreign_table'], '', $curVersion['uid'], $table, $conf);
			// Read relations that point to the record to be swapped with e.g. draft record):
			/** @var $dbAnalysisSwap t3lib_loadDBGroup */
			$dbAnalysisSwap = t3lib_div::makeInstance('t3lib_loadDBGroup');
			$dbAnalysisSwap->setUpdateReferenceIndex(FALSE);
			$dbAnalysisSwap->start('', $conf['foreign_table'], '', $swapVersion['uid'], $table, $conf);
				// Update relations for both (workspace/versioning) sites:

			if (count($dbAnalysisCur->itemArray)) {
				$dbAnalysisCur->writeForeignField($conf, $curVersion['uid'], $swapVersion['uid']);
				$tcemainObj->addRemapAction(
					$table, $curVersion['uid'],
					array($this, 'writeRemappedForeignField'),
					array($dbAnalysisCur, $conf, $swapVersion['uid'])
				);
			}

			if (count($dbAnalysisSwap->itemArray)) {
				$dbAnalysisSwap->writeForeignField($conf, $swapVersion['uid'], $curVersion['uid']);
				$tcemainObj->addRemapAction(
					$table, $curVersion['uid'],
					array($this, 'writeRemappedForeignField'),
					array($dbAnalysisSwap, $conf, $curVersion['uid'])
				);
			}

			$items = array_merge($dbAnalysisCur->itemArray, $dbAnalysisSwap->itemArray);
			foreach ($items as $item) {
				$tcemainObj->addRemapStackRefIndex($item['table'], $item['id']);
			}

			// Swap field values (CSV):
			// BUT: These values will be swapped back in the next steps, when the *CHILD RECORD ITSELF* is swapped!
		} elseif ($inlineType == 'list') {
			$tempValue = $curVersion[$field];
			$curVersion[$field] = $swapVersion[$field];
			$swapVersion[$field] = $tempValue;
		}
	}

	/**
	 * Writes remapped foreign field (IRRE).
	 *
	 * @param t3lib_loadDBGroup $dbAnalysis Instance that holds the sorting order of child records
	 * @param array $configuration The TCA field configuration
	 * @param integer $parentId The uid of the parent record
	 * @return void
	 */
	public function writeRemappedForeignField(t3lib_loadDBGroup $dbAnalysis, array $configuration, $parentId) {
		foreach ($dbAnalysis->itemArray as &$item) {
			if (isset($this->remappedIds[$item['table']][$item['id']])) {
				$item['id'] = $this->remappedIds[$item['table']][$item['id']];
			}
		}

		$dbAnalysis->writeForeignField($configuration, $parentId);
	}



	/**
	 * Release version from this workspace (and into "Live" workspace but as an offline version).
	 *
	 * @param string $table Table name
	 * @param integer $id Record UID
 	 * @param boolean $flush If set, will completely delete element
	 * @param t3lib_TCEmain $tcemainObj TCEmain object
	 * @return	void
	 */
	protected function version_clearWSID($table, $id, $flush = FALSE, t3lib_TCEmain $tcemainObj) {
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
	 * @param integer $oldPageId Current page id.
	 * @param integer $newPageId New page id
	 * @param array $copyTablesArray Array of tables from which to copy
	 * @param t3lib_TCEmain $tcemainObj TCEmain object
	 * @return void
	 * @see versionizePages()
	 */
	protected function rawCopyPageContent($oldPageId, $newPageId, array $copyTablesArray, t3lib_TCEmain $tcemainObj) {
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
						if (!$tcemainObj->copyMappingArray[$table][$row['uid']]) {
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
	 * @param  string $table Table name of the original element to swap
	 * @param integer $id UID of the original element to swap (online)
	 * @param integer $offlineId As above but offline
	 * @return array Element data. Key is table name, values are array with first element as online UID, second - offline UID
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
	 * @param array $pageIdList List of PIDs to search
	 * @param integer $workspaceId Workspace ID
	 * @param array $elementList List of found elements. Key is table name, value is array of element UIDs
	 * @return void
	 */
	protected function findPageElementsForVersionStageChange(array $pageIdList, $workspaceId, array &$elementList) {
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
	 * @param string $table Table to search
	 * @param array $idList List of records' UIDs
	 * @param integer $workspaceId Workspace ID. We need this parameter because user can be in LIVE but he still can publisg DRAFT from ws module!
	 * @param array $pageIdList List of found page UIDs
	 * @param array $elementList List of found element UIDs. Key is table name, value is list of UIDs
	 * @return void
	 */
	protected function findPageIdsForVersionStateChange($table, array $idList, $workspaceId, array &$pageIdList, array &$elementList) {
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
	protected function findRealPageIds(array &$idList) {
		foreach ($idList as $key => $id) {
			$rec = t3lib_BEfunc::getRecord('pages', $id, 't3ver_oid');
			if ($rec['t3ver_oid'] > 0) {
				$idList[$key] = $rec['t3ver_oid'];
			}
		}
	}


	/**
	 * Creates a move placeholder for workspaces.
	 * USE ONLY INTERNALLY
	 * Moving placeholder: Can be done because the system sees it as a placeholder for NEW elements like t3ver_state=1
	 * Moving original: Will either create the placeholder if it doesn't exist or move existing placeholder in workspace.
	 *
	 * @param string $table Table name to move
	 * @param integer $uid Record uid to move (online record)
	 * @param integer $destPid Position to move to: $destPid: >=0 then it points to a page-id on which to insert the record (as the first element). <0 then it points to a uid from its own table after which to insert it (works if
	 * @param integer $wsUid UID of offline version of online record
	 * @param t3lib_TCEmain $tcemainObj TCEmain object
	 * @return void
	 * @see moveRecord()
	 */
	protected function moveRecord_wsPlaceholders($table, $uid, $destPid, $wsUid, t3lib_TCEmain $tcemainObj) {
		global $TCA;

		if ($plh = t3lib_BEfunc::getMovePlaceholder($table, $uid, 'uid')) {
				// If already a placeholder exists, move it:
			$tcemainObj->moveRecord_raw($table, $plh['uid'], $destPid);
		} else {
				// First, we create a placeholder record in the Live workspace that
				// represents the position to where the record is eventually moved to.
			$newVersion_placeholderFieldArray = array();
			if ($TCA[$table]['ctrl']['crdate']) {
				$newVersion_placeholderFieldArray[$TCA[$table]['ctrl']['crdate']] = $GLOBALS['EXEC_TIME'];
			}
			if ($TCA[$table]['ctrl']['cruser_id']) {
				$newVersion_placeholderFieldArray[$TCA[$table]['ctrl']['cruser_id']] = $tcemainObj->userid;
			}
			if ($TCA[$table]['ctrl']['tstamp']) {
				$newVersion_placeholderFieldArray[$TCA[$table]['ctrl']['tstamp']] = $GLOBALS['EXEC_TIME'];
			}

			if ($table == 'pages') {
					// Copy page access settings from original page to placeholder
				$perms_clause = $tcemainObj->BE_USER->getPagePermsClause(1);
				$access = t3lib_BEfunc::readPageAccess($uid, $perms_clause);

				$newVersion_placeholderFieldArray['perms_userid']    = $access['perms_userid'];
				$newVersion_placeholderFieldArray['perms_groupid']   = $access['perms_groupid'];
				$newVersion_placeholderFieldArray['perms_user']      = $access['perms_user'];
				$newVersion_placeholderFieldArray['perms_group']     = $access['perms_group'];
				$newVersion_placeholderFieldArray['perms_everybody'] = $access['perms_everybody'];
			}

			$newVersion_placeholderFieldArray['t3ver_label'] = 'MOVE-TO PLACEHOLDER for #' . $uid;
			$newVersion_placeholderFieldArray['t3ver_move_id'] = $uid;
				// Setting placeholder state value for temporary record
			$newVersion_placeholderFieldArray['t3ver_state'] = 3;

				// Setting workspace - only so display of place holders can filter out those from other workspaces.
			$newVersion_placeholderFieldArray['t3ver_wsid'] = $tcemainObj->BE_USER->workspace;
			$newVersion_placeholderFieldArray[$TCA[$table]['ctrl']['label']] = '[MOVE-TO PLACEHOLDER for #' . $uid . ', WS#' . $tcemainObj->BE_USER->workspace . ']';

				// moving localized records requires to keep localization-settings for the placeholder too
			if (array_key_exists('languageField', $GLOBALS['TCA'][$table]['ctrl']) && array_key_exists('transOrigPointerField', $GLOBALS['TCA'][$table]['ctrl'])) {
				$l10nParentRec = t3lib_BEfunc::getRecord($table, $uid);
				$newVersion_placeholderFieldArray[$GLOBALS['TCA'][$table]['ctrl']['languageField']] = $l10nParentRec[$GLOBALS['TCA'][$table]['ctrl']['languageField']];
				$newVersion_placeholderFieldArray[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] = $l10nParentRec[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']];
				unset($l10nParentRec);
			}

				// Initially, create at root level.
			$newVersion_placeholderFieldArray['pid'] = 0;
			$id = 'NEW_MOVE_PLH';
				// Saving placeholder as 'original'
			$tcemainObj->insertDB($table, $id, $newVersion_placeholderFieldArray, FALSE);

				// Move the new placeholder from temporary root-level to location:
			$tcemainObj->moveRecord_raw($table, $tcemainObj->substNEWwithIDs[$id], $destPid);

				// Move the workspace-version of the original to be the version of the move-to-placeholder:
				// Setting placeholder state value for version (so it can know it is currently a new version...)
			$updateFields = array(
				't3ver_state' => 4
			);
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid=' . intval($wsUid), $updateFields);
		}

			// Check for the localizations of that element and move them as well
		$tcemainObj->moveL10nOverlayRecords($table, $uid, $destPid);
	}

	/**
	 * Gets all possible child tables that are used on each parent table as field.
	 *
	 * @param string $parentTable Name of the parent table
	 * @param array $possibleInlineChildren Collected possible inline children
	 * 				(will be filled automatically during recursive calls)
	 * @return array
	 */
	protected function getPossibleInlineChildTablesOfParentTable($parentTable, array $possibleInlineChildren = array()) {
		t3lib_div::loadTCA($parentTable);

		foreach ($GLOBALS['TCA'][$parentTable]['columns'] as $parentField => $parentFieldDefinition) {
			if (isset($parentFieldDefinition['config']['type'])) {
				$parentFieldConfiguration = $parentFieldDefinition['config'];
				if ($parentFieldConfiguration['type'] == 'inline' && isset($parentFieldConfiguration['foreign_table'])) {
					if (!in_array($parentFieldConfiguration['foreign_table'], $possibleInlineChildren)) {
						$possibleInlineChildren = $this->getPossibleInlineChildTablesOfParentTable(
							$parentFieldConfiguration['foreign_table'],
							array_merge($possibleInlineChildren, $parentFieldConfiguration['foreign_table'])
						);
					}
				}
			}
		}

		return $possibleInlineChildren;
	}

	/**
	 * Gets an instance of the command map helper.
	 *
	 * @param t3lib_TCEmain $tceMain TCEmain object
	 * @param array $commandMap The command map as submitted to t3lib_TCEmain
	 * @return tx_version_tcemain_CommandMap
	 */
	public function getCommandMap(t3lib_TCEmain $tceMain, array $commandMap) {
		return t3lib_div::makeInstance('tx_version_tcemain_CommandMap', $this, $tceMain, $commandMap);
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/version/class.tx_version_tcemain.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/version/class.tx_version_tcemain.php']);
}
?>