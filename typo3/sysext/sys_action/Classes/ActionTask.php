<?php
namespace TYPO3\CMS\SysAction;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
 *  (c) 2010-2013 Georg Ringer <typo3@ringerge.org>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * This class provides a task for the taskcenter
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author Georg Ringer <typo3@ringerge.org>
 */
class ActionTask implements \TYPO3\CMS\Taskcenter\TaskInterface {

	/**
	 * @var \TYPO3\CMS\Taskcenter\Controller\TaskModuleController
	 */
	protected $taskObject;

	/**
	 * @var \TYPO3\CMS\Backend\Form\FormEngine
	 * @todo Define visibility
	 */
	public $t3lib_TCEforms;

	/**
	 * All hook objects get registered here for later use
	 *
	 * @var array
	 */
	protected $hookObjects = array();

	/**
	 * Constructor
	 */
	public function __construct(\TYPO3\CMS\Taskcenter\Controller\TaskModuleController $taskObject) {
		$this->taskObject = $taskObject;
		$GLOBALS['LANG']->includeLLFile('EXT:sys_action/locallang.xml');
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sys_action']['tx_sysaction_task'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sys_action']['tx_sysaction_task'] as $classRef) {
				$this->hookObjects[] = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
			}
		}
	}

	/**
	 * This method renders the task
	 *
	 * @return string The task as HTML
	 */
	public function getTask() {
		$content = '';
		$show = intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('show'));
		foreach ($this->hookObjects as $hookObject) {
			if (method_exists($hookObject, 'getTask')) {
				$show = $hookObject->getTask($show, $this);
			}
		}
		// If no task selected, render the menu
		if ($show == 0) {
			$content .= $this->taskObject->description($GLOBALS['LANG']->getLL('sys_action'), $GLOBALS['LANG']->getLL('description'));
			$content .= $this->renderActionList();
		} else {
			$record = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('sys_action', $show);
			// If the action is not found
			if (count($record) == 0) {
				$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $GLOBALS['LANG']->getLL('action_error-not-found', TRUE), $GLOBALS['LANG']->getLL('action_error'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
				$content .= $flashMessage->render();
			} else {
				// Render the task
				$content .= $this->taskObject->description($record['title'], $record['description']);
				// Output depends on the type
				switch ($record['type']) {
					case 1:
						$content .= $this->viewNewBackendUser($record);
						break;
					case 2:
						$content .= $this->viewSqlQuery($record);
						break;
					case 3:
						$content .= $this->viewRecordList($record);
						break;
					case 4:
						$content .= $this->viewEditRecord($record);
						break;
					case 5:
						$content .= $this->viewNewRecord($record);
						break;
					default:
						$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
							'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
							$GLOBALS['LANG']->getLL('action_noType', TRUE),
							$GLOBALS['LANG']->getLL('action_error'),
							\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
						);
						$content .= '<br />' . $flashMessage->render();
				}
			}
		}
		return $content;
	}

	/**
	 * Gemeral overview over the task in the taskcenter menu
	 *
	 * @return string Overview as HTML
	 */
	public function getOverview() {
		$content = '<p>' . $GLOBALS['LANG']->getLL('description') . '</p>';
		// Get the actions
		$actionList = $this->getActions();
		if (count($actionList) > 0) {
			$items = '';
			// Render a single action menu item
			foreach ($actionList as $action) {
				$active = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('show') === $action['uid'] ? ' class="active" ' : '';
				$items .= '<li' . $active . '>
								<a href="' . $action['link'] . '" title="' . htmlspecialchars($action['description']) . '">' . htmlspecialchars($action['title']) . '</a>
							</li>';
			}
			$content .= '<ul>' . $items . '</ul>';
		}
		return $content;
	}

	/**
	 * Get all actions of an user. Admins can see any action, all others only those
	 * whic are allowed in sys_action record itself.
	 *
	 * @return array Array holding every needed information of a sys_action
	 */
	protected function getActions() {
		$actionList = array();
		// admins can see any record
		if ($GLOBALS['BE_USER']->isAdmin()) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_action', '', '', 'sys_action.sorting');
		} else {
			// Editors can only see the actions which are assigned to a usergroup they belong to
			$additionalWhere = 'be_groups.uid IN (' . ($GLOBALS['BE_USER']->groupList ? $GLOBALS['BE_USER']->groupList : 0) . ')';
			$res = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query('sys_action.*', 'sys_action', 'sys_action_asgr_mm', 'be_groups', ' AND sys_action.hidden=0 AND ' . $additionalWhere, 'sys_action.uid', 'sys_action.sorting');
		}
		while ($actionRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$editActionLink = '';
			// Admins are allowed to edit sys_action records
			if ($GLOBALS['BE_USER']->isAdmin()) {
				$returnUrl = rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI'));
				$link = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_DIR') . $GLOBALS['BACK_PATH'] . 'alt_doc.php?returnUrl=' . $returnUrl . '&edit[sys_action][' . $actionRow['uid'] . ']=edit';
				$editActionLink = '<a class="edit" href="' . $link . '">' . '<img class="icon"' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/edit2.gif') . ' title="' . $GLOBALS['LANG']->getLL('edit-sys_action') . '" alt="" />' . $GLOBALS['LANG']->getLL('edit-sys_action') . '</a>';
			}
			$actionList[] = array(
				'uid' => $actionRow['uid'],
				'title' => $actionRow['title'],
				'description' => $actionRow['description'],
				'descriptionHtml' => nl2br(htmlspecialchars($actionRow['description'])) . $editActionLink,
				'link' => 'mod.php?M=user_task&SET[function]=sys_action.tx_sysaction_task&show=' . $actionRow['uid'],
				'icon' => 'EXT:sys_action/sys_action.gif'
			);
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		return $actionList;
	}

	/**
	 * Render the menu of sys_actions
	 *
	 * @return string List of sys_actions as HTML
	 */
	protected function renderActionList() {
		$content = '';
		// Get the sys_action records
		$actionList = $this->getActions();
		// If any actions are found for the current users
		if (count($actionList) > 0) {
			$content .= $this->taskObject->renderListMenu($actionList);
		} else {
			$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $GLOBALS['LANG']->getLL('action_not-found-description', TRUE), $GLOBALS['LANG']->getLL('action_not-found'), \TYPO3\CMS\Core\Messaging\FlashMessage::INFO);
			$content .= $flashMessage->render();
		}
		// Admin users can create a new action
		if ($GLOBALS['BE_USER']->isAdmin()) {
			$returnUrl = rawurlencode('mod.php?M=user_task');
			$link = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_DIR') . $GLOBALS['BACK_PATH'] . 'alt_doc.php?returnUrl=' . $returnUrl . '&edit[sys_action][0]=new';
			$content .= '<br />
						<a href="' . $link . '" title="' . $GLOBALS['LANG']->getLL('new-sys_action') . '">' . '<img class="icon"' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/new_record.gif') . ' title="' . $GLOBALS['LANG']->getLL('new-sys_action') . '" alt="" /> ' . $GLOBALS['LANG']->getLL('new-sys_action') . '</a>';
		}
		return $content;
	}

	/**
	 * Action to create a new BE user
	 *
	 * @param array $record sys_action record
	 * @return string form to create a new user
	 */
	protected function viewNewBackendUser($record) {
		$content = '';
		$beRec = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('be_users', intval($record['t1_copy_of_user']));
		// A record is neeed which is used as copy for the new user
		if (!is_array($beRec)) {
			$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $GLOBALS['LANG']->getLL('action_notReady', TRUE), $GLOBALS['LANG']->getLL('action_error'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
			$content .= $flashMessage->render();
			return $content;
		}
		$vars = \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('data');
		$key = 'NEW';
		if ($vars['sent'] == 1) {
			$errors = array();
			// Basic error checks
			if (!empty($vars['email']) && !\TYPO3\CMS\Core\Utility\GeneralUtility::validEmail($vars['email'])) {
				$errors[] = $GLOBALS['LANG']->getLL('error-wrong-email');
			}
			if (empty($vars['username'])) {
				$errors[] = $GLOBALS['LANG']->getLL('error-username-empty');
			}
			if (empty($vars['password'])) {
				$errors[] = $GLOBALS['LANG']->getLL('error-password-empty');
			}
			if ($vars['key'] !== 'NEW' && !$this->isCreatedByUser($vars['key'], $record)) {
				$errors[] = $GLOBALS['LANG']->getLL('error-wrong-user');
			}
			foreach ($this->hookObjects as $hookObject) {
				if (method_exists($hookObject, 'viewNewBackendUser_Error')) {
					$errors = $hookObject->viewNewBackendUser_Error($vars, $errors, $this);
				}
			}
			// Show errors if there are any
			if (count($errors) > 0) {
				$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', implode('<br />', $errors), $GLOBALS['LANG']->getLL('action_error'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
				$content .= $flashMessage->render() . '<br />';
			} else {
				// Save user
				$key = $this->saveNewBackendUser($record, $vars);
				// Success messsage
				$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $vars['key'] === 'NEW' ? $GLOBALS['LANG']->getLL('success-user-created') : $GLOBALS['LANG']->getLL('success-user-updated'), $GLOBALS['LANG']->getLL('success'), \TYPO3\CMS\Core\Messaging\FlashMessage::OK);
				$content .= $flashMessage->render() . '<br />';
			}
		}
		// Load BE user to edit
		if (intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('be_users_uid')) > 0) {
			$tmpUserId = intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('be_users_uid'));
			// Check if the selected user is created by the current user
			$rawRecord = $this->isCreatedByUser($tmpUserId, $record);
			if ($rawRecord) {
				// Delete user
				if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('delete') == 1) {
					$this->deleteUser($tmpUserId, $record['uid']);
				}
				$key = $tmpUserId;
				$vars = $rawRecord;
			}
		}
		$this->JScode();
		$loadDB = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\RelationHandler');
		$loadDB->start($vars['db_mountpoints'], 'pages');
		$content .= '<form action="" method="post" enctype="multipart/form-data">
						<fieldset class="fields">
							<legend>General fields</legend>
							<div class="row">
								<label for="field_disable">' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_general.xlf:LGL.disable') . '</label>
								<input type="checkbox" id="field_disable" name="data[disable]" value="1" class="checkbox" ' . ($vars['disable'] == 1 ? ' checked="checked" ' : '') . ' />
							</div>
							<div class="row">
								<label for="field_realname">' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_general.xlf:LGL.name') . '</label>
								<input type="text" id="field_realname" name="data[realName]" value="' . htmlspecialchars($vars['realName']) . '" />
							</div>
							<div class="row">
								<label for="field_username">' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_tca.xlf:be_users.username') . '</label>
								<input type="text" id="field_username" name="data[username]" value="' . htmlspecialchars($vars['username']) . '" />
							</div>
							<div class="row">
								<label for="field_password">' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_tca.xlf:be_users.password') . '</label>
								<input type="password" id="field_password" name="data[password]" value="" />
							</div>
							<div class="row">
								<label for="field_email">' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_general.xlf:LGL.email') . '</label>
								<input type="text" id="field_email" name="data[email]" value="' . htmlspecialchars($vars['email']) . '" />
							</div>
						</fieldset>
						<fieldset class="fields">
							<legend>Configuration</legend>

							<div class="row">
								<label for="field_usergroup">' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_tca.xlf:be_users.usergroup') . '</label>
								<select id="field_usergroup" name="data[usergroup][]" multiple="multiple">
									' . $this->getUsergroups($record, $vars) . '
								</select>
							</div>
							<div class="row">
								<label for="field_db_mountpoints">' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_tca.xlf:be_users.options_db_mounts') . '</label>
								' . $this->t3lib_TCEforms->dbFileIcons('data[db_mountpoints]', 'db', 'pages', $loadDB->itemArray, '', array('size' => 3)) . '
							</div>
							<div class="row">
								<input type="hidden" name="data[key]" value="' . $key . '" />
								<input type="hidden" name="data[sent]" value="1" />
								<input type="submit" value="' . ($key === 'NEW' ? $GLOBALS['LANG']->getLL('action_Create') : $GLOBALS['LANG']->getLL('action_Update')) . '" />
							</div>
						</fieldset>
					</form>';
		$content .= $this->getCreatedUsers($record, $key);
		return $content;
	}

	/**
	 * Delete a BE user and redirect to the action by its id
	 *
	 * @param integer $userId Id of the BE user
	 * @param integer $actionId Id of the action
	 * @return void
	 */
	protected function deleteUser($userId, $actionId) {
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery('be_users', 'uid=' . $userId, array(
			'deleted' => 1,
			'tstamp' => $GLOBALS['ACCESS_TIME']
		));
		// redirect to the original task
		$redirectUrl = 'mod.php?M=user_task&show=' . $actionId;
		\TYPO3\CMS\Core\Utility\HttpUtility::redirect($redirectUrl);
	}

	/**
	 * Check if a BE user is created by the current user
	 *
	 * @param integer $id Id of the BE user
	 * @param array $action sys_action record.
	 * @return mixed The record of the BE user if found, otherwise FALSE
	 */
	protected function isCreatedByUser($id, $action) {
		$record = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('be_users', $id, '*', ' AND cruser_id=' . $GLOBALS['BE_USER']->user['uid'] . ' AND createdByAction=' . $action['uid']);
		if (is_array($record)) {
			return $record;
		} else {
			return FALSE;
		}
	}

	/**
	 * Render all users who are created by the current BE user including a link to edit the record
	 *
	 * @param array $action sys_action record.
	 * @param integer $selectedUser Id of a selected user
	 * @return string html list of users
	 */
	protected function getCreatedUsers($action, $selectedUser) {
		$content = '';
		$userList = array();
		// List of users
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'be_users', 'cruser_id=' . $GLOBALS['BE_USER']->user['uid'] . ' AND createdByAction=' . intval($action['uid']) . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('be_users'), '', 'username');
		// Render the user records
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$icon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('be_users', $row, array('title' => 'uid=' . $row['uid']));
			$line = $icon . $this->action_linkUserName($row['username'], $row['realName'], $action['uid'], $row['uid']);
			// Selected user
			if ($row['uid'] == $selectedUser) {
				$line = '<strong>' . $line . '</strong>';
			}
			$userList[] = $line;
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		// If any records found
		if (count($userList)) {
			$content .= '<br />' . $this->taskObject->doc->section($GLOBALS['LANG']->getLL('action_t1_listOfUsers'), implode('<br />', $userList));
		}
		return $content;
	}

	/**
	 * Create a link to edit a user
	 *
	 * @param string $username Username
	 * @param string $realName Real name of the user
	 * @param integer $sysActionUid Id of the sys_action record
	 * @param integer $userId Id of the user
	 * @return string html link
	 */
	protected function action_linkUserName($username, $realName, $sysActionUid, $userId) {
		if (!empty($realName)) {
			$username .= ' (' . $realName . ')';
		}
		// Link to update the user record
		$href = 'mod.php?M=user_task&SET[function]=sys_action.tx_sysaction_task&show=' . intval($sysActionUid) . '&be_users_uid=' . intval($userId);
		$link = '<a href="' . htmlspecialchars($href) . '">' . htmlspecialchars($username) . '</a>';
		// Link to delete the user record
		$onClick = ' onClick="return confirm(' . $GLOBALS['LANG']->JScharCode($GLOBALS['LANG']->getLL('lDelete_warning')) . ');"';
		$link .= '
				<a href="' . htmlspecialchars(($href . '&delete=1')) . '" ' . $onClick . '>
					<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/delete_record.gif') . ' alt="" />
				</a>';
		return $link;
	}

	/**
	 * Save/Update a BE user
	 *
	 * @param array $record Current action record
	 * @param array $vars POST vars
	 * @return integer Id of the new/updated user
	 */
	protected function saveNewBackendUser($record, $vars) {
		// Check if the db mount is a page the current user is allowed to.);
		$vars['db_mountpoints'] = $this->fixDbMount($vars['db_mountpoints']);
		// Check if the usergroup is allowed
		$vars['usergroup'] = $this->fixUserGroup($vars['usergroup'], $record);
		// Check if md5 is used as password encryption
		if (strpos($GLOBALS['TCA']['be_users']['columns']['password']['config']['eval'], 'md5') !== FALSE) {
			$vars['password'] = md5($vars['password']);
		}
		$key = $vars['key'];
		$data = '';
		$newUserId = 0;
		if ($key === 'NEW') {
			$beRec = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('be_users', intval($record['t1_copy_of_user']));
			if (is_array($beRec)) {
				$data = array();
				$data['be_users'][$key] = $beRec;
				$data['be_users'][$key]['username'] = $this->fixUsername($vars['username'], $record['t1_userprefix']);
				$data['be_users'][$key]['password'] = trim($vars['password']);
				$data['be_users'][$key]['realName'] = $vars['realName'];
				$data['be_users'][$key]['email'] = $vars['email'];
				$data['be_users'][$key]['disable'] = intval($vars['disable']);
				$data['be_users'][$key]['admin'] = 0;
				$data['be_users'][$key]['usergroup'] = $vars['usergroup'];
				$data['be_users'][$key]['db_mountpoints'] = $vars['db_mountpoints'];
				$data['be_users'][$key]['createdByAction'] = $record['uid'];
			}
		} else {
			// Check ownership
			$beRec = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('be_users', intval($key));
			if (is_array($beRec) && $beRec['cruser_id'] == $GLOBALS['BE_USER']->user['uid']) {
				$data = array();
				$data['be_users'][$key]['username'] = $this->fixUsername($vars['username'], $record['t1_userprefix']);
				if (trim($vars['password'])) {
					$data['be_users'][$key]['password'] = trim($vars['password']);
				}
				$data['be_users'][$key]['realName'] = $vars['realName'];
				$data['be_users'][$key]['email'] = $vars['email'];
				$data['be_users'][$key]['disable'] = intval($vars['disable']);
				$data['be_users'][$key]['admin'] = 0;
				$data['be_users'][$key]['usergroup'] = $vars['usergroup'];
				$data['be_users'][$key]['db_mountpoints'] = $vars['db_mountpoints'];
				$newUserId = $key;
			}
		}
		// Save/update user by using TCEmain
		if (is_array($data)) {
			$tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
			$tce->stripslashes_values = 0;
			$tce->start($data, array(), $GLOBALS['BE_USER']);
			$tce->admin = 1;
			$tce->process_datamap();
			$newUserId = intval($tce->substNEWwithIDs['NEW']);
			if ($newUserId) {
				// Create
				$this->action_createDir($newUserId);
			} else {
				// Update
				$newUserId = intval($key);
			}
			unset($tce);
		}
		return $newUserId;
	}

	/**
	 * Create the username based on the given username and the prefix
	 *
	 * @param string $username Username
	 * @param string $prefix Prefix
	 * @return string Combined username
	 */
	protected function fixUsername($username, $prefix) {
		return trim($prefix) . trim($username);
	}

	/**
	 * Clean the to be applied usergroups from not allowed ones
	 *
	 * @param array $appliedUsergroups Array of to be applied user groups
	 * @param array $actionRecord The action record
	 * @return array Cleaned array
	 */
	protected function fixUserGroup($appliedUsergroups, $actionRecord) {
		if (is_array($appliedUsergroups)) {
			$cleanGroupList = array();
			// Create an array from the allowed usergroups using the uid as key
			$allowedUsergroups = array_flip(explode(',', $actionRecord['t1_allowed_groups']));
			// Walk through the array and check every uid if it is undder the allowed ines
			foreach ($appliedUsergroups as $group) {
				if (isset($allowedUsergroups[$group])) {
					$cleanGroupList[] = $group;
				}
			}
			$appliedUsergroups = $cleanGroupList;
		}
		return $appliedUsergroups;
	}

	/**
	 * Clean the to be applied DB-Mounts from not allowed ones
	 *
	 * @param string $appliedDbMounts List of pages like pages_123,pages456
	 * @return string Cleaned list
	 */
	protected function fixDbMount($appliedDbMounts) {
		// Admins can see any page, no need to check there
		if (!empty($appliedDbMounts) && !$GLOBALS['BE_USER']->isAdmin()) {
			$cleanDbMountList = array();
			$dbMounts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $appliedDbMounts, 1);
			// Walk through every wanted DB-Mount and check if it allowed for the current user
			foreach ($dbMounts as $dbMount) {
				$uid = intval(substr($dbMount, strrpos($dbMount, '_') + 1));
				$page = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', $uid);
				// Check rootline and access rights
				if ($this->checkRootline($uid) && $GLOBALS['BE_USER']->calcPerms($page)) {
					$cleanDbMountList[] = 'pages_' . $uid;
				}
			}
			// Build the clean list
			$appliedDbMounts = implode(',', $cleanDbMountList);
		}
		return $appliedDbMounts;
	}

	/**
	 * Check if a page is inside the rootline the current user can see
	 *
	 * @param integer $pageId Id of the the page to be checked
	 * @return boolean Access to the page
	 */
	protected function checkRootline($pageId) {
		$access = FALSE;
		$dbMounts = array_flip(explode(',', trim($GLOBALS['BE_USER']->dataLists['webmount_list'], ',')));
		$rootline = \TYPO3\CMS\Backend\Utility\BackendUtility::BEgetRootLine($pageId);
		foreach ($rootline as $page) {
			if (isset($dbMounts[$page['uid']]) && !$access) {
				$access = TRUE;
			}
		}
		return $access;
	}

	/**
	 * Add additional JavaScript to use the tceform select box
	 *
	 * @return void
	 */
	protected function JScode() {
		$this->t3lib_TCEforms = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Form\\FormEngine');
		$this->t3lib_TCEforms->backPath = $GLOBALS['BACK_PATH'];
		$js = $this->t3lib_TCEforms->dbFileCon();
		$this->taskObject->doc->JScodeArray[] = $js;
		return $js;
	}

	/**
	 * Create a user directory if defined
	 *
	 * @param integer $uid Id of the user record
	 * @return void
	 */
	protected function action_createDir($uid) {
		$path = $this->action_getUserMainDir();
		if ($path) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir($path . $uid);
			\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir($path . $uid . '/_temp_/');
		}
	}

	/**
	 * Get the path to the user home directory which is set in the localconf.php
	 *
	 * @return string Path
	 */
	protected function action_getUserMainDir() {
		$path = $GLOBALS['TYPO3_CONF_VARS']['BE']['userHomePath'];
		// If path is set and a valid directory
		if ($path && @is_dir($path) && $GLOBALS['TYPO3_CONF_VARS']['BE']['lockRootPath'] && \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($path, $GLOBALS['TYPO3_CONF_VARS']['BE']['lockRootPath']) && substr($path, -1) == '/') {
			return $path;
		}
	}

	/**
	 * Get all allowed usergroups which can be applied to a user record
	 *
	 * @param array $record sys_action record
	 * @param array $vars Selected be_user record
	 * @return string Rendered user groups
	 */
	protected function getUsergroups($record, $vars) {
		$content = '';
		// Do nothing if no groups are allowed
		if (empty($record['t1_allowed_groups'])) {
			return $content;
		}
		$content .= '<option value=""></option>';
		$grList = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $record['t1_allowed_groups'], 1);
		foreach ($grList as $group) {
			$checkGroup = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('be_groups', $group);
			if (is_array($checkGroup)) {
				$selected = \TYPO3\CMS\Core\Utility\GeneralUtility::inList($vars['usergroup'], $checkGroup['uid']) ? ' selected="selected" ' : '';
				$content .= '<option ' . $selected . 'value="' . $checkGroup['uid'] . '">' . htmlspecialchars($checkGroup['title']) . '</option>';
			}
		}
		return $content;
	}

	/**
	 * Action to create a new record
	 *
	 * @param array $record sys_action record
	 * @return void Redirect to form to create a record
	 */
	protected function viewNewRecord($record) {
		$returnUrl = rawurlencode('mod.php?M=user_task');
		$link = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_DIR') . $GLOBALS['BACK_PATH'] . 'alt_doc.php?returnUrl=' . $returnUrl . '&edit[' . $record['t3_tables'] . '][' . intval($record['t3_listPid']) . ']=new';
		\TYPO3\CMS\Core\Utility\HttpUtility::redirect($link);
	}

	/**
	 * Action to edit records
	 *
	 * @param array $record sys_action record
	 * @return string list of records
	 */
	protected function viewEditRecord($record) {
		$content = '';
		$actionList = array();
		$dbAnalysis = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\RelationHandler');
		$dbAnalysis->setFetchAllFields(TRUE);
		$dbAnalysis->start($record['t4_recordsToEdit'], '*');
		$dbAnalysis->getFromDB();
		// collect the records
		foreach ($dbAnalysis->itemArray as $el) {
			$path = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordPath($el['id'], $this->taskObject->perms_clause, $GLOBALS['BE_USER']->uc['titleLen']);
			$record = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($el['table'], $dbAnalysis->results[$el['table']][$el['id']]);
			$title = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle($el['table'], $dbAnalysis->results[$el['table']][$el['id']]);
			$description = $GLOBALS['LANG']->sL($GLOBALS['TCA'][$el['table']]['ctrl']['title'], 1);
			// @todo: which information could be needfull
			if (isset($record['crdate'])) {
				$description .= ' - ' . \TYPO3\CMS\Backend\Utility\BackendUtility::dateTimeAge($record['crdate']);
			}
			$actionList[$el['id']] = array(
				'title' => $title,
				'description' => \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle($el['table'], $dbAnalysis->results[$el['table']][$el['id']]),
				'descriptionHtml' => $description,
				'link' => $GLOBALS['BACK_PATH'] . 'alt_doc.php?returnUrl=' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI')) . '&edit[' . $el['table'] . '][' . $el['id'] . ']=edit',
				'icon' => \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord($el['table'], $dbAnalysis->results[$el['table']][$el['id']], array('title' => htmlspecialchars($path)))
			);
		}
		// Render the record list
		$content .= $this->taskObject->renderListMenu($actionList);
		return $content;
	}

	/**
	 * Action to view the result of a SQL query
	 *
	 * @param array $record sys_action record
	 * @return string Result of the query
	 */
	protected function viewSqlQuery($record) {
		$content = '';
		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('lowlevel')) {
			$sql_query = unserialize($record['t2_data']);
			if (!is_array($sql_query) || is_array($sql_query) && strtoupper(substr(trim($sql_query['qSelect']), 0, 6)) === 'SELECT') {
				$actionContent = '';
				$fullsearch = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\QueryView');
				$fullsearch->formW = 40;
				$fullsearch->noDownloadB = 1;
				$type = $sql_query['qC']['search_query_makeQuery'];
				if ($sql_query['qC']['labels_noprefix'] === 'on') {
					$GLOBALS['SOBE']->MOD_SETTINGS['labels_noprefix'] = 'on';
				}
				$sqlQuery = $sql_query['qSelect'];
				$queryIsEmpty = FALSE;
				if ($sqlQuery) {
					$res = $GLOBALS['TYPO3_DB']->sql_query($sqlQuery);
					if (!$GLOBALS['TYPO3_DB']->sql_error()) {
						$fullsearch->formW = 48;
						// Additional configuration
						$GLOBALS['SOBE']->MOD_SETTINGS['search_result_labels'] = 1;
						$cP = $fullsearch->getQueryResultCode($type, $res, $sql_query['qC']['queryTable']);
						$actionContent = $cP['content'];
						// If the result is rendered as csv or xml, show a download link
						if ($type === 'csv' || $type === 'xml') {
							$actionContent .= '<br /><br /><a href="' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI') . '&download_file=1"><strong>' . $GLOBALS['LANG']->getLL('action_download_file') . '</strong></a>';
						}
					} else {
						$actionContent .= $GLOBALS['TYPO3_DB']->sql_error();
					}
				} else {
					// Query is empty (not built)
					$queryIsEmpty = TRUE;
					$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $GLOBALS['LANG']->getLL('action_emptyQuery', TRUE), $GLOBALS['LANG']->getLL('action_error'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
					$content .= '<br />' . $flashMessage->render();
				}
				// Admin users are allowed to see and edit the query
				if ($GLOBALS['BE_USER']->isAdmin()) {
					if (!$queryIsEmpty) {
						$actionContent .= '<hr /> ' . $fullsearch->tableWrap($sql_query['qSelect']);
					}
					$actionContent .= '<br /><a title="' . $GLOBALS['LANG']->getLL('action_editQuery') . '" href="'
						. \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('tools_dbint')
						. '&id=' . '&SET[function]=search' . '&SET[search]=query'
						. '&storeControl[STORE]=-' . $record['uid'] . '&storeControl[LOAD]=1' . '">
						<img class="icon"' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'],
						'gfx/edit2.gif') . ' alt="" />' . $GLOBALS['LANG']->getLL(($queryIsEmpty ? 'action_createQuery'
						: 'action_editQuery')) . '</a><br /><br />';
				}
				$content .= $this->taskObject->doc->section($GLOBALS['LANG']->getLL('action_t2_result'), $actionContent, 0, 1);
			} else {
				// Query is not configured
				$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $GLOBALS['LANG']->getLL('action_notReady', TRUE), $GLOBALS['LANG']->getLL('action_error'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
				$content .= '<br />' . $flashMessage->render();
			}
		} else {
			// Required sysext lowlevel is not installed
			$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $GLOBALS['LANG']->getLL('action_lowlevelMissing', TRUE), $GLOBALS['LANG']->getLL('action_error'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
			$content .= '<br />' . $flashMessage->render();
		}
		return $content;
	}

	/**
	 * Action to create a list of records of a specific table and pid
	 *
	 * @param array $record sys_action record
	 * @return string list of records
	 */
	protected function viewRecordList($record) {
		$content = '';
		$this->id = intval($record['t3_listPid']);
		$this->table = $record['t3_tables'];
		if ($this->id == 0 || $this->table == '') {
			$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $GLOBALS['LANG']->getLL('action_notReady', TRUE), $GLOBALS['LANG']->getLL('action_error'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
			$content .= '<br />' . $flashMessage->render();
			return $content;
		}
		// Loading current page record and checking access:
		$this->pageinfo = \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess($this->id, $this->taskObject->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;
		// If there is access to the page, then render the list contents and set up the document template object:
		if ($access) {
			// Initialize the dblist object:
			$dblist = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\SysAction\\ActionList');
			$dblist->script = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI');
			$dblist->backPath = $GLOBALS['BACK_PATH'];
			$dblist->calcPerms = $GLOBALS['BE_USER']->calcPerms($this->pageinfo);
			$dblist->thumbs = $GLOBALS['BE_USER']->uc['thumbnailsByDefault'];
			$dblist->returnUrl = $this->taskObject->returnUrl;
			$dblist->allFields = 1;
			$dblist->localizationView = 1;
			$dblist->showClipboard = 0;
			$dblist->disableSingleTableView = 1;
			$dblist->pageRow = $this->pageinfo;
			$dblist->counter++;
			$dblist->MOD_MENU = array('bigControlPanel' => '', 'clipBoard' => '', 'localization' => '');
			$dblist->modTSconfig = $this->taskObject->modTSconfig;
			$dblist->dontShowClipControlPanels = $CLIENT['FORMSTYLE'] && !$this->taskObject->MOD_SETTINGS['bigControlPanel'] && $dblist->clipObj->current == 'normal' && !$this->modTSconfig['properties']['showClipControlPanelsDespiteOfCMlayers'];
			// Initialize the listing object, dblist, for rendering the list:
			$this->pointer = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('pointer'), 0, 100000);
			$dblist->start($this->id, $this->table, $this->pointer, $this->taskObject->search_field, $this->taskObject->search_levels, $this->taskObject->showLimit);
			$dblist->setDispFields();
			// Render the list of tables:
			$dblist->generateList();
			// Add JavaScript functions to the page:
			$this->taskObject->doc->JScode = $this->taskObject->doc->wrapScriptTags('

				function jumpToUrl(URL) {
					window.location.href = URL;
					return false;
				}
				function jumpExt(URL,anchor) {
					var anc = anchor?anchor:"";
					window.location.href = URL+(T3_THIS_LOCATION?"&returnUrl="+T3_THIS_LOCATION:"")+anc;
					return false;
				}
				function jumpSelf(URL) {
					window.location.href = URL+(T3_RETURN_URL?"&returnUrl="+T3_RETURN_URL:"");
					return false;
				}

				function setHighlight(id) {
					top.fsMod.recentIds["web"]=id;
					top.fsMod.navFrameHighlightedID["web"]="pages"+id+"_"+top.fsMod.currentBank;	// For highlighting

					if (top.content && top.content.nav_frame && top.content.nav_frame.refresh_nav) {
						top.content.nav_frame.refresh_nav();
					}
				}

				' . $dblist->CBfunctions() . '
				function editRecords(table,idList,addParams,CBflag) {
					window.location.href="' . $GLOBALS['BACK_PATH'] . 'alt_doc.php?returnUrl=' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI')) . '&edit["+table+"]["+idList+"]=edit"+addParams;
				}
				function editList(table,idList) {
					var list="";

						// Checking how many is checked, how many is not
					var pointer=0;
					var pos = idList.indexOf(",");
					while (pos!=-1) {
						if (cbValue(table+"|"+idList.substr(pointer,pos-pointer))) {
							list+=idList.substr(pointer,pos-pointer)+",";
						}
						pointer=pos+1;
						pos = idList.indexOf(",",pointer);
					}
					if (cbValue(table+"|"+idList.substr(pointer))) {
						list+=idList.substr(pointer)+",";
					}

					return list ? list : idList;
				}
				T3_THIS_LOCATION = "' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI')) . '";

				if (top.fsMod) top.fsMod.recentIds["web"] = ' . intval($this->id) . ';
			');
			// Setting up the context sensitive menu:
			$this->taskObject->doc->getContextMenuCode();
			// Begin to compile the whole page
			$content .= '<form action="' . htmlspecialchars($dblist->listURL()) . '" method="post" name="dblistForm">' . $dblist->HTMLcode . '<input type="hidden" name="cmd_table" /><input type="hidden" name="cmd" />
						</form>';
			// If a listing was produced, create the page footer with search form etc:
			if ($dblist->HTMLcode) {
				// Making field select box (when extended view for a single table is enabled):
				if ($dblist->table) {
					$tmpBackpath = $GLOBALS['BACK_PATH'];
					$GLOBALS['BACK_PATH'] = '';
					$content .= $dblist->fieldSelectBox($dblist->table);
					$GLOBALS['BACK_PATH'] = $tmpBackpath;
				}
			}
		} else {
			// Not enough rights to access the list view or the page
			$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $GLOBALS['LANG']->getLL('action_error-access', TRUE), $GLOBALS['LANG']->getLL('action_error'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
			$content .= $flashMessage->render();
		}
		return $content;
	}

}


?>
