<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2010 Kasper Skaarhoj (kasperYYYY@typo3.com)
*  (c) 2010 Georg Ringer <typo3@ringerge.org>
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
 * @author		Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @author		Georg Ringer <typo3@ringerge.org>
 * @package		TYPO3
 * @subpackage	tx_sysaction
 *
 */
class tx_sysaction_task implements tx_taskcenter_Task {

	protected $taskObject;
	var $t3lib_TCEforms;

	/**
	 * Constructor
	 */
	public function __construct(SC_mod_user_task_index $taskObject) {
		$this->taskObject = $taskObject;
		$GLOBALS['LANG']->includeLLFile('EXT:sys_action/locallang.xml');
	}


	/**
	 * This method renders the task
	 *
	 * @return	string	The task as HTML
	 */
	public function getTask() {
		$content = '';
		$show = intval(t3lib_div::_GP('show'));

			// if no task selected, render the menu
		if ($show == 0) {
			$content .= $this->taskObject->description(
				$GLOBALS['LANG']->getLL('sys_action'),
				$GLOBALS['LANG']->getLL('description')
			);

			$content .= $this->renderActionList();
		} else {
			$record = t3lib_BEfunc::getRecord('sys_action', $show);

				// if the action is not found
			if (count($record) == 0) {
				$flashMessage = t3lib_div::makeInstance(
					't3lib_FlashMessage',
					$GLOBALS['LANG']->getLL('action_error-not-found', TRUE),
					$GLOBALS['LANG']->getLL('action_error'),
					t3lib_FlashMessage::ERROR
				);
				$content .= $flashMessage->render();
			} else {
					// render the task
				$content .= $this->taskObject->description($record['title'], $record['description']);

					// output depends on the type
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
						$flashMessage = t3lib_div::makeInstance(
							't3lib_FlashMessage',
							$GLOBALS['LANG']->getLL('action_noType', TRUE),
							$GLOBALS['LANG']->getLL('action_error'),
							t3lib_FlashMessage::ERROR
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
	 * @return	string Overview as HTML
	 */
	public function getOverview() {
		$content = '<p>' . $GLOBALS['LANG']->getLL('description') . '</p>';

			// get the actions
		$actionList = $this->getActions();
		if (count($actionList) > 0) {
			$items = '';

				// render a single action menu item
			foreach ($actionList as $action) {
				$active = (t3lib_div::_GP('show') === $action['uid']) ? ' class="active" ' : '';
				$items .= '<li' . $active . '>
								<a href="' . $action['link'] . '" title="' . htmlspecialchars($action['description']) . '">' .
									htmlspecialchars($action['title']) .
								'</a>
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
	 * @param	boolean		$toOverview: If TRUE, the link redirects to the taskcenter
	 * @return	array Array holding every needed information of a sys_action
	 */
	protected function getActions() {
		$actionList = array();

			// admins can see any record
		if ($GLOBALS['BE_USER']->isAdmin()) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				'sys_action',
				'',
				'',
				'sys_action.sorting'
			);
		} else {
				// editors can only see the actions which are assigned to a usergroup they belong to
			$additionalWhere = 'be_groups.uid IN (' . ($GLOBALS['BE_USER']->groupList ? $GLOBALS['BE_USER']->groupList : 0) . ')';

			$res = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
				'sys_action.*',
				'sys_action',
				'sys_action_asgr_mm',
				'be_groups',
				' AND sys_action.hidden=0 AND ' . $additionalWhere,
				'sys_action.uid',
				'sys_action.sorting'
			);
		}

		while($actionRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$editActionLink = '';

				// admins are allowed to edit sys_action records
			if ($GLOBALS['BE_USER']->isAdmin()) {
				$returnUrl = rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'));
				$link = t3lib_div::getIndpEnv('TYPO3_REQUEST_DIR') . $GLOBALS['BACK_PATH'] . 'alt_doc.php?returnUrl=' . $returnUrl . '&edit[sys_action][' . $actionRow['uid'] . ']=edit';

				$editActionLink = '<a class="edit" href="' . $link . '">' .
						'<img class="icon"' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/edit2.gif') . ' title="' . $GLOBALS['LANG']->getLL('edit-sys_action') . '" alt="" />' .
							$GLOBALS['LANG']->getLL('edit-sys_action') .
				 		'</a>';
			}

			$actionList[] = array(
				'uid'				=> $actionRow['uid'],
				'title'				=> $actionRow['title'],
				'description'		=> $actionRow['description'],
				'descriptionHtml'	=> nl2br(htmlspecialchars($actionRow['description'])) . $editActionLink,
				'link'				=> 'mod.php?M=user_task&SET[function]=sys_action.tx_sysaction_task&show=' . $actionRow['uid'],
				'icon'				=> 'EXT:sys_action/sys_action.gif'
			);
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);

		return $actionList;
	}

	/**
	 * Render the menu of sys_actions
	 *
	 * @return	string list of sys_actions as HTML
	 */
	protected function renderActionList() {
		$content = '';

			// get the sys_action records
		$actionList = $this->getActions();

			// if any actions are found for the current users
		if (count($actionList) > 0) {
			$content .= $this->taskObject->renderListMenu($actionList);
		} else {
			$flashMessage = t3lib_div::makeInstance (
				't3lib_FlashMessage',
				$GLOBALS['LANG']->getLL('action_not-found-description', TRUE),
				$GLOBALS['LANG']->getLL('action_not-found'),
				t3lib_FlashMessage::INFO
			);
			$content .= $flashMessage->render();
		}

			// Admin users can create a new action
		if ($GLOBALS['BE_USER']->isAdmin()) {
			$returnUrl = rawurlencode('mod.php?M=user_task');
			$link = t3lib_div::getIndpEnv('TYPO3_REQUEST_DIR') . $GLOBALS['BACK_PATH'] . 'alt_doc.php?returnUrl=' . $returnUrl. '&edit[sys_action][0]=new';

			$content .= '<br />
						 <a href="' . $link . '" title="' . $GLOBALS['LANG']->getLL('new-sys_action') . '">' .
							'<img class="icon"' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/new_record.gif') . ' title="' . $GLOBALS['LANG']->getLL('new-sys_action') . '" alt="" /> ' .
							$GLOBALS['LANG']->getLL('new-sys_action') .
						 '</a>';
		}

		return $content;
	}

	/**
	 * Action to create a new BE user
	 *
	 * @param	array		$record: sys_action record
	 * @return	string form to create a new user
	 */
	protected function viewNewBackendUser($record) {
		$content = '';

		$beRec = t3lib_BEfunc::getRecord('be_users', intval($record['t1_copy_of_user']));
			// a record is neeed which is used as copy for the new user
		if (!is_array($beRec)) {
			$flashMessage = t3lib_div::makeInstance(
				't3lib_FlashMessage',
				$GLOBALS['LANG']->getLL('action_notReady', TRUE),
				$GLOBALS['LANG']->getLL('action_error'),
				t3lib_FlashMessage::ERROR
			);
			$content .= $flashMessage->render();

			return $content;
		}

		$vars = t3lib_div::_POST('data');
		$key = 'NEW';

		if ($vars['sent'] == 1) {
			$errors = array();

				// basic error checks
			if (!empty($vars['email']) && !t3lib_div::validEmail($vars['email'])) {
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

				// show errors if there are any
			if (count($errors) > 0) {
				$flashMessage = t3lib_div::makeInstance (
					't3lib_FlashMessage',
					implode('<br />', $errors),
					$GLOBALS['LANG']->getLL('action_error'),
					t3lib_FlashMessage::ERROR
				);
				$content .= $flashMessage->render() . '<br />';
			} else {
					// save user
				$key = $this->saveNewBackendUser($record, $vars);

					// success messsage
				$flashMessage = t3lib_div::makeInstance (
					't3lib_FlashMessage',
					($vars['key'] === 'NEW' ? $GLOBALS['LANG']->getLL('success-user-created') : $GLOBALS['LANG']->getLL('success-user-updated')),
					$GLOBALS['LANG']->getLL('success'),
					t3lib_FlashMessage::OK
				);
				$content .= $flashMessage->render() . '<br />' ;
			}

		}

			// load BE user to edit
		if (intval(t3lib_div::_GP('be_users_uid')) > 0) {
			$tmpUserId = intval(t3lib_div::_GP('be_users_uid'));

				// check if the selected user is created by the current user
			$rawRecord = $this->isCreatedByUser($tmpUserId, $record);
			if ($rawRecord) {
					// delete user
				if (t3lib_div::_GP('delete') == 1) {
					$this->deleteUser($tmpUserId, $record['uid']);
				}

				$key = $tmpUserId;
				$vars = $rawRecord;
			}
		}

		$this->JScode();
		$loadDB = t3lib_div::makeInstance('t3lib_loadDBGroup');
		$loadDB->start($vars['db_mountpoints'], 'pages');

		$content .= '<form action="" method="post" enctype="multipart/form-data">
						<fieldset class="fields">
							<legend>General fields</legend>
							<div class="row">
								<label for="field_disable">' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_general.xml:LGL.disable') . '</label>
								<input type="checkbox" id="field_disable" name="data[disable]" value="1" class="checkbox" ' . ($vars['disable'] == 1 ? ' checked="checked" ' : '') . ' />
							</div>
							<div class="row">
								<label for="field_realname">' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_general.xml:LGL.name') . '</label>
								<input type="text" id="field_realname" name="data[realName]" value="' . htmlspecialchars($vars['realName']) .'" />
							</div>
							<div class="row">
								<label for="field_username">' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_tca.xml:be_users.username') . '</label>
								<input type="text" id="field_username" name="data[username]" value="' . htmlspecialchars($vars['username']) .'" />
							</div>
							<div class="row">
								<label for="field_password">' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_tca.xml:be_users.password') . '</label>
								<input type="password" id="field_password" name="data[password]" value="" />
							</div>
							<div class="row">
								<label for="field_email">' .$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_general.xml:LGL.email') . '</label>
								<input type="text" id="field_email" name="data[email]" value="' . htmlspecialchars($vars['email']) .'" />
							</div>
						</fieldset>
						<fieldset class="fields">
							<legend>Configuration</legend>

							<div class="row">
								<label for="field_usergroup">' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_tca.xml:be_users.usergroup') . '</label>
								<select id="field_usergroup" name="data[usergroup][]" multiple="multiple">
									' . $this->getUsergroups($record, $vars) . '
								</select>
							</div>
							<div class="row">
								<label for="field_db_mountpoints">' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_tca.xml:be_users.options_db_mounts') . '</label>
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
	 * @param	int		$userId: Id of the BE user
	 * @param	int		$actionId: Id of the action
	 * @return	void
	 */
	protected function deleteUser($userId, $actionId) {
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'be_users',
			'uid=' . $userId,
			array (
				'deleted'	=> 1,
				'tstamp'	=> $GLOBALS['ACCESS_TIME']
			)
		);

			// redirect to the original task
		$redirectUrl = 'mod.php?M=user_task&show=' . $actionId;
		t3lib_utility_Http::redirect($redirectUrl);
	}

	/**
	 * Check if a BE user is created by the current user
	 *
	 * @param	int		$id: Id of the BE user
	 * @param	array		$action: sys_action record.
	 * @return	mixed the record of the BE user if found, otherwise FALSE
	 */
	protected function isCreatedByUser($id, $action) {
		$record = t3lib_BEfunc::getRecord(
			'be_users',
			$id,
			'*',
			' AND cruser_id=' . $GLOBALS['BE_USER']->user['uid'] . ' AND createdByAction=' . $action['uid']
		);

		if (is_array($record)) {
			return $record;
		} else {
			return FALSE;
		}
	}


	/**
	 * Render all users who are created by the current BE user including a link to edit the record
	 *
	 * @param	array		$action: sys_action record.
	 * @param	int		$selectedUser: Id of a selected user
	 * @return	html list of users
	 */
	protected function getCreatedUsers($action, $selectedUser) {
		$content = '';
		$userList = array();

			// List of users
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'be_users',
			'cruser_id=' . $GLOBALS['BE_USER']->user['uid'] . ' AND createdByAction=' . intval($action['uid']) . t3lib_BEfunc::deleteClause('be_users'),
			'',
			'username'
		);

			// render the user records
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$icon = t3lib_iconworks::getIconImage('be_users', $row, $GLOBALS['BACK_PATH'], 'title="uid=' . $row['uid'] . '" hspace="2" align="top"');
			$line = $icon . $this->action_linkUserName($row['username'], $row['realName'], $action['uid'], $row['uid']);

				// selected user
			if  ($row['uid'] == $selectedUser) {
				$line = '<strong>' . $line . '</strong>';
			}

			$userList[] = $line;
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);

			// if any records found
		if (count($userList)) {
			$content .= '<br />' . $this->taskObject->doc->section($GLOBALS['LANG']->getLL('action_t1_listOfUsers'), implode('<br />', $userList));
		}

		return $content;
	}


	/**
	 * Create a link to edit a user
	 *
	 * @param	string		$username: Username
	 * @param	string		$realName: Real name of the user
	 * @param	int		$sysActionUid: Id of the sys_action record
	 * @param	int		$userId: Id of the user
	 * @return	html link
	 */
	protected function action_linkUserName($username, $realName, $sysActionUid, $userId) {
		if (!empty($realName)) {
			$username .= ' (' . $realName . ')';
		}

			// link to update the user record
		$href = 'mod.php?M=user_task&SET[function]=sys_action.tasks&show=' . intval($sysActionUid) . '&be_users_uid=' . intval($userId);
		$link = '<a href="' . $href . '">' . htmlspecialchars($username) . '</a>';

			// link to delete the user record
		$onClick = ' onClick="return confirm('.$GLOBALS['LANG']->JScharCode($GLOBALS['LANG']->getLL("lDelete_warning")).');"';
		$link .= '
				<a href="' . $href . '&delete=1" ' . $onClick . '>
					<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/delete_record.gif') . ' alt="" />
				</a>';
		return $link;
	}

	/**
	 * Save/Update a BE user
	 *
	 * @param	array		$record: Current action record
	 * @param	array		$vars: POST vars
	 * @return	int Id of the new/updated user
	 */
	protected function saveNewBackendUser($record, $vars) {
			// check if the db mount is a page the current user is allowed to.);
		$vars['db_mountpoints'] = $this->fixDbMount($vars['db_mountpoints']);
			// check if the usergroup is allowed
		$vars['usergroup'] = $this->fixUserGroup($vars['usergroup'], $record);
			// check if md5 is used as password encryption
		if (strpos($GLOBALS['TCA']['be_users']['columns']['password']['config']['eval'], 'md5') !== FALSE) {
			$vars['password'] = md5($vars['password']);
		}

		$key = $vars['key'];
		$data = '';
		$newUserId = 0;

		if ($key === 'NEW') {
			$beRec = t3lib_BEfunc::getRecord('be_users', intval($record['t1_copy_of_user']));
			if (is_array($beRec)) {
				$data = array();
				$data['be_users'][$key] = $beRec;
				$data['be_users'][$key]['username']			= $this->fixUsername($vars['username'], $record['t1_userprefix']);
				$data['be_users'][$key]['password']			= (trim($vars['password']));
				$data['be_users'][$key]['realName']			= $vars['realName'];
				$data['be_users'][$key]['email']			= $vars['email'];
				$data['be_users'][$key]['disable']			= intval($vars['disable']);
				$data['be_users'][$key]['admin']			= 0;
				$data['be_users'][$key]['usergroup']		= $vars['usergroup'];
				$data['be_users'][$key]['db_mountpoints']	= $vars['db_mountpoints'];
				$data['be_users'][$key]['createdByAction']	= $record['uid'];
			}
		} else {
				// check ownership
			$beRec = t3lib_BEfunc::getRecord('be_users', intval($key));
			if (is_array($beRec) && $beRec['cruser_id'] == $GLOBALS['BE_USER']->user['uid']) {
				$data=array();
				$data['be_users'][$key]['username'] = $this->fixUsername($vars['username'], $record['t1_userprefix']);
				if (trim($vars['password'])) {
					$data['be_users'][$key]['password'] = (trim($vars['password']));
				}

				$data['be_users'][$key]['realName']			= $vars['realName'];
				$data['be_users'][$key]['email']			= $vars['email'];
				$data['be_users'][$key]['disable']			= intval($vars['disable']);
				$data['be_users'][$key]['admin']			= 0;
				$data['be_users'][$key]['usergroup']		= $vars['usergroup'];
				$data['be_users'][$key]['db_mountpoints']	= $vars['db_mountpoints'];
				$newUserId = $key;
			}
		}

			// save/update user by using TCEmain
		if (is_array($data)) {
			$tce = t3lib_div::makeInstance("t3lib_TCEmain");
			$tce->stripslashes_values = 0;
			$tce->start($data, array(), $GLOBALS['BE_USER']);
			$tce->admin = 1;
			$tce->process_datamap();
			$newUserId = intval($tce->substNEWwithIDs['NEW']);

			if ($newUserId) {
					// Create
				$this->action_createDir($newUserId);
			} else {
					// update
				$newUserId = intval($key);
			}
			unset($tce);
		}
		return $newUserId;
	}

	/**
	 * Create the username based on the given username and the prefix
	 *
	 * @param	string		$username: username
	 * @param	string		$prefix: prefix
	 * @return string	Combined username
	 */
	private function fixUsername($username, $prefix) {
		return trim($prefix) . trim($username);
	}

	/**
	 * Clean the to be applied usergroups from not allowed ones
	 *
	 * @param	array		$appliedUsergroups: array of to be applied user groups
	 * @return array	Cleaned array
	 */
	protected function fixUserGroup($appliedUsergroups, $actionRecord) {
		if (is_array($appliedUsergroups)) {
			$cleanGroupList = array();

				// create an array from the allowed usergroups using the uid as key
			$allowedUsergroups = array_flip(explode(',', $actionRecord['t1_allowed_groups']));

				// walk through the array and check every uid if it is undder the allowed ines
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
	 * @param	string		$appliedDbMounts: List of pages like pages_123,pages456
	 * @return string	Cleaned list
	 */
	protected function fixDbMount($appliedDbMounts) {
			// Admins can see any page, no need to check there
		if (!empty($appliedDbMounts) && !$GLOBALS['BE_USER']->isAdmin()) {
			$cleanDbMountList = array();
			$dbMounts = t3lib_div::trimExplode(',', $appliedDbMounts, 1);

				// walk through every wanted DB-Mount and check if it allowed for the current user
			foreach ($dbMounts as $dbMount) {
				$uid = intval(substr($dbMount,  (strrpos($dbMount, '_') + 1)));
				$page = t3lib_BEfunc::getRecord('pages', $uid);

					// check rootline and access rights
				if ($this->checkRootline($uid) && $GLOBALS['BE_USER']->calcPerms($page)) {
					$cleanDbMountList[] = 'pages' . $uid;
				}
			}
				// build the clean list
			$appliedDbMounts = implode(',', $cleanDbMountList);
		}

		return $appliedDbMounts;
	}

	/**
	 * Check if a page is inside the rootline the current user can see
	 *
	 * @param	int		$pageId: Id of the the page to be checked
	 * @return boolean	Access to the page
	 */
	protected function checkRootline($pageId) {
		$access = FALSE;

		$dbMounts =  array_flip(explode(',', trim($GLOBALS['BE_USER']->dataLists['webmount_list'], ',')));
		$rootline = t3lib_BEfunc::BEgetRootLine($pageId);
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
	 * @param	int		$uid: Id of the user record
	 * @return void
	 */
	protected function JScode() {
		$this->t3lib_TCEforms = t3lib_div::makeInstance("t3lib_TCEforms");
		$this->t3lib_TCEforms->backPath = $GLOBALS['BACK_PATH'];
		$js = $this->t3lib_TCEforms->dbFileCon();
		$this->taskObject->doc->JScodeArray[] = $js;

		return $js;
	}

	/**
	 * Create a user directory if defined
	 *
	 * @param	int		$uid: Id of the user record
	 * @return void
	 */
	protected function action_createDir($uid) {
		$path = $this->action_getUserMainDir();
		if ($path) {
			t3lib_div::mkdir($path . $uid);
			t3lib_div::mkdir($path . $uid . '/_temp_/');
		}
	}

	/**
	 * Get the path to the user home directory which is set in the localconf.php
	 *
	 * @return string path
	 */
	protected function action_getUserMainDir() {
		$path = $GLOBALS['TYPO3_CONF_VARS']['BE']['userHomePath'];

			// if path is set and a valid directory
		if ($path && @is_dir($path) &&
				$GLOBALS['TYPO3_CONF_VARS']['BE']['lockRootPath'] &&
				t3lib_div::isFirstPartOfStr($path, $GLOBALS['TYPO3_CONF_VARS']['BE']['lockRootPath']) &&
				substr($path,-1) == '/'
			) {
			return $path;
		}
	}

	/**
	 * Get all allowed usergroups which can be applied to a user record
	 *
	 * @param array $record sys_action record
	 * @param array $vars Selected be_user record
	 * @return string rendered user groups
	 */
	protected function getUsergroups($record, $vars) {
		$content = '';
			// do nothing if no groups are allowed
		if (empty($record['t1_allowed_groups'])) {
			return $content;
		}

		$content .= '<option value=""></option>';
		$grList = t3lib_div::trimExplode(',',  $record['t1_allowed_groups'], 1);
		foreach($grList as $group) {
			$checkGroup = t3lib_BEfunc::getRecord('be_groups', $group);
			if (is_array($checkGroup)) {
				$selected = (is_array($vars['usergroup']) && t3lib_div::inList(implode(',', $vars['usergroup']), $checkGroup['uid'])) ? ' selected="selected" ' : '';
				$content .= '<option ' . $selected . 'value="' . $checkGroup['uid'] . '">' . htmlspecialchars($checkGroup['title']) . '</option>';
			}
		}

		return $content;
	}


	/**
	 * Action to create a new record
	 *
	 * @param	array		$record: sys_action record
	 * @return	redirect to form to create a record
	 */
	protected function viewNewRecord($record) {
		$returnUrl = rawurlencode('mod.php?M=user_task');
		$link = t3lib_div::getIndpEnv('TYPO3_REQUEST_DIR') . $GLOBALS['BACK_PATH'] . 'alt_doc.php?returnUrl=' . $returnUrl. '&edit[' . $record['t3_tables'] . '][' . intval($record['t3_listPid']) . ']=new';
		t3lib_utility_Http::redirect($link);
	}

	/**
	 * Action to edit records
	 *
	 * @param	array		$record: sys_action record
	 * @return	string list of records
	 */
	protected function viewEditRecord($record) {
		$content = '';
		$actionList = array();

		$dbAnalysis = t3lib_div::makeInstance('t3lib_loadDBGroup');
		$dbAnalysis->fromTC = 0;
		$dbAnalysis->start($record['t4_recordsToEdit'], '*');
		$dbAnalysis->getFromDB();

			// collect the records
		foreach ($dbAnalysis->itemArray as $el) {
			$path = t3lib_BEfunc::getRecordPath ($el['id'], $this->taskObject->perms_clause, $GLOBALS['BE_USER']->uc['titleLen']);
			$record = t3lib_BEfunc::getRecord($el['table'], $dbAnalysis->results[$el['table']][$el['id']]);
			$title = t3lib_BEfunc::getRecordTitle($el['table'], $dbAnalysis->results[$el['table']][$el['id']]);
			$description = $GLOBALS['LANG']->sL($GLOBALS['TCA'][$el['table']]['ctrl']['title'], 1);
			if (isset($record['crdate'])) { // @todo: which information could be  needfull
				$description .= ' - ' . t3lib_BEfunc::dateTimeAge($record['crdate']);
			}

			$actionList[$el['id']] = array(
				'title'				=> $title,
				'description'		=> t3lib_BEfunc::getRecordTitle($el['table'], $dbAnalysis->results[$el['table']][$el['id']]),
				'descriptionHtml'	=> $description,
				'link'				=> $GLOBALS['BACK_PATH'] . 'alt_doc.php?returnUrl=' . rawurlencode(t3lib_div::getIndpEnv("REQUEST_URI")) . '&edit[' . $el['table'] . '][' . $el['id'] . ']=edit',
				'icon'				=> t3lib_iconworks::getIconImage($el['table'], $dbAnalysis->results[$el['table']][$el['id']], $GLOBALS['BACK_PATH'], 'hspace="2" align="top" title="' . htmlspecialchars($path) . '"')
			);
		}

			// render the record list
		$content .= $this->taskObject->renderListMenu($actionList);

		return $content;
	}

	/**
	 * Action to view the result of a SQL query
	 *
	 * @param	array		$record: sys_action record
	 * @return	string result of the query
	 */
	protected function viewSqlQuery($record) {
		$content = '';

		if (t3lib_extMgm::isLoaded('lowlevel')) {
			$sql_query = unserialize($record['t2_data']);

			if (is_array($sql_query) && strtoupper(substr(trim($sql_query['qSelect']), 0, 6)) == 'SELECT') {
				$actionContent = '';

				$fullsearch = t3lib_div::makeInstance("t3lib_fullsearch");
				$fullsearch->formW = 40;
				$fullsearch->noDownloadB = 1;

				$type = $sql_query['qC']['search_query_makeQuery'];
				$res = $GLOBALS['TYPO3_DB']->sql_query($sql_query['qSelect']);

				if (!$GLOBALS['TYPO3_DB']->sql_error()) {
					$fullsearch->formW = 48;
						// additional configuration
					$GLOBALS['SOBE']->MOD_SETTINGS['search_result_labels'] = 1;
					$cP = $fullsearch->getQueryResultCode($type, $res, $sql_query['qC']['queryTable']);
					$actionContent = $cP['content'];

						// if the result is rendered as csv or xml, show a download link
					if ($type == 'csv' || $type == 'xml' ) {
						$actionContent .= '<br /><br /><a href="' . t3lib_div::getIndpEnv('REQUEST_URI') . '&download_file=1"><strong>' . $GLOBALS['LANG']->getLL('action_download_file') . '</strong></a>';
					}
				} else {
					$actionContent .= $GLOBALS['TYPO3_DB']->sql_error();
				}

				// Admin users are allowed to see and edit the query
				if ($GLOBALS['BE_USER']->isAdmin()) {
					$actionContent .= '<hr /> ' . $fullsearch->tableWrap($sql_query['qSelect']);
					$actionContent .= '<br /><a title="' . $GLOBALS['LANG']->getLL('action_editQuery') . '" href="' . $GLOBALS['BACK_PATH'] . t3lib_extMgm::extRelPath('lowlevel') . 'dbint/index.php?id=' .
						'&SET[function]=search' .
						'&SET[search]=query' .
						'&storeControl[STORE]=-' . $record['uid'] .
						'&storeControl[LOAD]=1' .
						'">
						<img class="icon"' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/edit2.gif') . ' alt="" />' .
						$GLOBALS['LANG']->getLL('action_editQuery') . '</a><br /><br />';
				}

				$content .= $this->taskObject->doc->section($GLOBALS['LANG']->getLL('action_t2_result'), $actionContent, 0, 1);
			} else {
					// query is not configured
				$flashMessage = t3lib_div::makeInstance (
					't3lib_FlashMessage',
					$GLOBALS['LANG']->getLL('action_notReady', TRUE),
					$GLOBALS['LANG']->getLL('action_error'),
					t3lib_FlashMessage::ERROR
				);
				$content .= '<br />' . $flashMessage->render();
			}
		} else {
				// required sysext lowlevel is not installed
			$flashMessage = t3lib_div::makeInstance (
				't3lib_FlashMessage',
				$GLOBALS['LANG']->getLL('action_lowlevelMissing', TRUE),
				$GLOBALS['LANG']->getLL('action_error'),
				t3lib_FlashMessage::ERROR
			);
			$content .= '<br />' . $flashMessage->render();
		}
		return $content;
	}

	/**
	 * Action to create a list of records of a specific table and pid
	 *
	 * @param	array		$record: sys_action record
	 * @return	string list of records
	 */
	protected function viewRecordList($record) {
		$content = '';

		$this->id		= intval($record['t3_listPid']);
		$this->table	= $record['t3_tables'];

		if ($this->id == 0 || $this->table == '') {
			$flashMessage = t3lib_div::makeInstance(
				't3lib_FlashMessage',
				$GLOBALS['LANG']->getLL('action_lowlevelMissing', TRUE),
				$GLOBALS['LANG']->getLL('action_error'),
				t3lib_FlashMessage::ERROR
			);
			$content .= '<br />' . $flashMessage->render();

			return $content;
		}

		require_once($GLOBALS['BACK_PATH'] . 'class.db_list.inc');
		require_once($GLOBALS['BACK_PATH'] . 'class.db_list_extra.inc');

			// Loading current page record and checking access:
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->taskObject->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;

			// If there is access to the page, then render the list contents and set up the document template object:
		if ($access) {
				// Initialize the dblist object:
			$dblist = t3lib_div::makeInstance('localRecordList');
			$dblist->script = t3lib_div::getIndpEnv('REQUEST_URI');
			$dblist->backPath = $GLOBALS['BACK_PATH'];
			$dblist->calcPerms = $GLOBALS['BE_USER']->calcPerms($this->pageinfo);
			$dblist->thumbs = $GLOBALS['BE_USER']->uc['thumbnailsByDefault'];
			$dblist->returnUrl=$this->taskObject->returnUrl;
			$dblist->allFields = 1;
			$dblist->localizationView = 1;
			$dblist->showClipboard = 0;
			$dblist->disableSingleTableView = 1;
			$dblist->pageRow = $this->pageinfo;
			$dblist->counter++;
			$dblist->MOD_MENU = array('bigControlPanel' => '', 'clipBoard' => '', 'localization' => '');
			$dblist->modTSconfig = $this->taskObject->modTSconfig;
			$dblist->dontShowClipControlPanels = $CLIENT['FORMSTYLE'] && !$this->taskObject->MOD_SETTINGS['bigControlPanel'] && $dblist->clipObj->current=='normal' && !$GLOBALS['BE_USER']->uc['disableCMlayers'] && !$this->modTSconfig['properties']['showClipControlPanelsDespiteOfCMlayers'];

				// Initialize the listing object, dblist, for rendering the list:
			$this->pointer = t3lib_div::intInRange($this->taskObject->pointer,0,100000);
			$dblist->start($this->id,$this->table,$this->pointer,$this->taskObject->search_field,$this->taskObject->search_levels,$this->taskObject->showLimit);
			$dblist->setDispFields();

				// Render the list of tables:
			$dblist->generateList();

				// Add JavaScript functions to the page:
			$this->taskObject->doc->JScode=$this->taskObject->doc->wrapScriptTags('

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
					window.location.href="' . $GLOBALS['BACK_PATH'] . 'alt_doc.php?returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI')) .
						'&edit["+table+"]["+idList+"]=edit"+addParams;
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
				T3_THIS_LOCATION = "' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI')) . '";

				if (top.fsMod) top.fsMod.recentIds["web"] = ' . intval($this->id) . ';
			');

				// Setting up the context sensitive menu:
			$this->taskObject->doc->getContextMenuCode();

				// Begin to compile the whole page
			$content .= '<form action="'.htmlspecialchars($dblist->listURL()).'" method="post" name="dblistForm">' .
							$dblist->HTMLcode .
							'<input type="hidden" name="cmd_table" /><input type="hidden" name="cmd" />
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
				// not enough rights to access the list view or the page
			$flashMessage = t3lib_div::makeInstance(
				't3lib_FlashMessage',
				$GLOBALS['LANG']->getLL('action_error-access', TRUE),
				$GLOBALS['LANG']->getLL('action_error'),
				t3lib_FlashMessage::ERROR
			);
			$content .= $flashMessage->render();
		}

		return $content;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sys_action/task/class.tx_sysaction_task.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sys_action/task/class.tx_sysaction_task.php']);
}

?>