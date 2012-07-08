<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Backend User Administration Module
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */

$GLOBALS['LANG']->includeLLFile('EXT:beuser/mod/locallang.xml');
$BE_USER->modAccess($MCONF, 1);

/**
 * Main script class
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_beuser
 */
class SC_mod_tools_be_user_index {
	var $MCONF=array();
	var $MOD_MENU=array();
	var $MOD_SETTINGS=array();

	/**
	 * document emplate object
	 *
	 * @var noDoc
	 */
	var $doc;

	var $include_once=array();
	var $content;

	/**
	 * Basic initialization of the class
	 *
	 * @return	void
	 */
	function init() {
		$this->MCONF = $GLOBALS['MCONF'];

		$this->menuConfig();
		$this->switchUser(t3lib_div::_GP('SwitchUser'));

		// **************************
		// Initializing
		// **************************
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate('templates/beuser.html');
		$this->doc->form = '<form action="" method="post">';

				// JavaScript
		$this->doc->JScode = $this->doc->wrapScriptTags('
			script_ended = 0;
			function jumpToUrl(URL) {	//
				window.location.href = URL;
			}
		' . $this->doc->redirectUrls());
	}

	/**
	 * Initialization of the module menu configuration
	 *
	 * @return	void
	 */
	function menuConfig() {
		// MENU-ITEMS:
			// If array, then it's a selector box menu
			// If empty string it's just a variable, that'll be saved.
			// Values NOT in this array will not be saved in the settings-array for the module.
		$this->MOD_MENU = array(
			'function' => array(
				'compare' => $GLOBALS['LANG']->getLL('compareUserSettings', TRUE),
				'whoisonline' => $GLOBALS['LANG']->getLL('listUsersOnline', TRUE)
			)
		);
			// CLEAN SETTINGS
		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::_GP('SET'), $this->MCONF['name'], 'ses');
	}

	/**
	 * This functions builds the content of the page
	 *
	 * @return	void
	 */
	function main() {
		$this->content = $this->doc->header($GLOBALS['LANG']->getLL('backendUserAdministration', TRUE));

		switch($this->MOD_SETTINGS['function']) {
			case 'compare':
				if (t3lib_div::_GP('ads')) {
					$compareFlags = t3lib_div::_GP('compareFlags');
					$GLOBALS['BE_USER']->pushModuleData('tools_beuser/index.php/compare', $compareFlags);
				} else {
					$compareFlags = $GLOBALS['BE_USER']->getModuleData('tools_beuser/index.php/compare', 'ses');
				}
				$this->content.=$this->compareUsers($compareFlags);
			break;
			case 'whoisonline':
				$this->content.=$this->whoIsOnline();
			break;
		}
			// Setting up the buttons and markers for docheader
		$docHeaderButtons = $this->getButtons();
		$markers['FUNC_MENU'] = t3lib_BEfunc::getFuncMenu(0, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function']);
		$markers['CONTENT'] = $this->content;

			// Build the <body> for the module
		$this->content = $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
			// Renders the module page
		$this->content = $this->doc->render(
			'Backend User Administration',
			$this->content
		);
	}

	/**
	 * Prints the content of the page
	 *
	 * @return	void
	 */
	function printContent() {
		echo $this->content;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return	array	all available buttons as an assoc. array
	 */
	protected function getButtons() {
		$buttons = array(
			'add' => '',
			'csh' => '',
			'shortcut' => '',
			'save' => ''
		);

			// Add user
		if ($this->MOD_SETTINGS['function'] === 'compare') {
			$buttons['add'] = '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::editOnClick('&edit[be_users][0]=new', $this->doc->backPath, -1)) .
				'" title="' . $GLOBALS['LANG']->getLL('newUser', TRUE) . '">' . t3lib_iconWorks::getSpriteIcon('actions-document-new') . '</a>';
		}

			// Shortcut
		if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
			$buttons['shortcut'] = $this->doc->makeShortcutIcon('be_user_uid,compareFlags', 'function', $this->MCONF['name']);
		}

		return $buttons;
	}

	/***************************
	 *
	 * OTHER FUNCTIONS:
	 *
	 ***************************/

	/**
	 * Compares the users with the given flags
	 *
	 * @param	array		options that should be taking into account to compare the users
	 * @return	string		the content
	 */
	function compareUsers($compareFlags) {
			// Menu:
		$options = array(
			'filemounts' => $GLOBALS['LANG']->getLL('filemounts', TRUE),
			'webmounts' => $GLOBALS['LANG']->getLL('webmounts', TRUE),
			'tempPath' => $GLOBALS['LANG']->getLL('defaultUploadPath', TRUE),
			'firstMainGroup' => $GLOBALS['LANG']->getLL('mainUserGroup', TRUE),
			'groupList' => $GLOBALS['LANG']->getLL('memberOfGroups', TRUE),
			'pagetypes_select' => $GLOBALS['LANG']->getLL('pageTypesAccess', TRUE),
			'tables_select' => $GLOBALS['LANG']->getLL('selectTables', TRUE),
			'tables_modify' => $GLOBALS['LANG']->getLL('modifyTables', TRUE),
			'non_exclude_fields' => $GLOBALS['LANG']->getLL('nonExcludeFields', TRUE),
			'explicit_allowdeny' => $GLOBALS['LANG']->getLL('explicitAllowDeny', TRUE),
			'allowed_languages' => $GLOBALS['LANG']->getLL('limitToLanguages', TRUE),
			'workspace_perms' => $GLOBALS['LANG']->getLL('workspacePermissions', TRUE),
			'workspace_membership' => $GLOBALS['LANG']->getLL('workspaceMembership', TRUE),
			'custom_options' => $GLOBALS['LANG']->getLL('customOptions', TRUE),
			'modules' => $GLOBALS['LANG']->getLL('modules', TRUE),
			'userTS' => $GLOBALS['LANG']->getLL('tsconfig', TRUE),
			'userTS_hl' => $GLOBALS['LANG']->getLL('tsconfigHL', TRUE),
		);

		$be_user_uid = t3lib_div::_GP('be_user_uid');
		if ($be_user_uid) {
				// This is used to test with other users. Development ONLY!
			$tempBE_USER = t3lib_div::makeInstance('tx_beuser_local_beUserAuth');	// New backend user object
			$tempBE_USER->userTS_dontGetCached=1;
			$tempBE_USER->OS = TYPO3_OS;
			$tempBE_USER->setBeUserByUid($be_user_uid);
			$tempBE_USER->fetchGroupData();

			$uInfo = $tempBE_USER->ext_compileUserInfoForHash();
			$uInfo_dat = $tempBE_USER->ext_printOverview($uInfo, $options, 1);

			$lines=array();
			foreach ($options as $kk => $vv) {
				$lines[]='<tr class="bgColor4">
					<td nowrap="nowrap" valign="top">'.$vv.':&nbsp;&nbsp;</td>
					<td>'.$uInfo_dat[$kk].'&nbsp;</td>
				</tr>';

				if ($kk=='webmounts' && !$tempBE_USER->isAdmin()) {
					$lines[]='<tr class="bgColor4">
						<td nowrap="nowrap" valign="top">' . $GLOBALS['LANG']->getLL('nonMountedReadablePages', TRUE) . '&nbsp;&nbsp;</td>
						<td>'.$tempBE_USER->ext_getReadableButNonmounted().'&nbsp;</td>
					</tr>';
				}
			}

			$email = htmlspecialchars($tempBE_USER->user['email']);
			$realname = htmlspecialchars($tempBE_USER->user['realName']);
			$outTable = '<table border="0" cellpadding="1" cellspacing="1"><tr class="bgColor5"><td>'.t3lib_iconWorks::getSpriteIconForRecord('be_users', $tempBE_USER->user, array('title'=>$tempBE_USER->user['uid'])).htmlspecialchars($tempBE_USER->user['username']).'</td>';
			$outTable.= '<td>'.($realname?$realname.', ':'').($email ? '<a href="mailto:'.$email.'">'.$email.'</a>' : '').'</td>';
			$outTable.= '<td>'.$this->elementLinks('be_users', $tempBE_USER->user).'</td></tr></table>';
			$outTable.= '<strong><a href="'.htmlspecialchars($this->MCONF['_']).'">' . $GLOBALS['LANG']->getLL('backToOverview', TRUE) . '</a></strong><br />';

			$outTable.= '<br /><table border="0" cellpadding="2" cellspacing="1">'.implode('', $lines).'</table>';
			$content .= $this->doc->section($GLOBALS['LANG']->getLL('userInfo', TRUE), $outTable, FALSE, TRUE);
		} else {
			$menu = array(0 => array());
			$rowCounter = 0;
			$columnCounter = 0;
			$itemsPerColumn = ceil(count($options) / 3);
			foreach ($options as $kk => $vv) {
				if ($rowCounter == $itemsPerColumn) {
					$rowCounter = 0;
					$columnCounter++;
					$menu[$columnCounter] = array();
				}
				$rowCounter++;
				$menu[$columnCounter][]='<input type="checkbox" class="checkbox" value="1" name="compareFlags['.$kk.']" id="checkCompare_'.$kk.'"'.($compareFlags[$kk]?' checked="checked"':'').'> <label for="checkCompare_'.$kk.'">'.htmlspecialchars($vv).'</label>';
			}
			$outCode = '<p>' . $GLOBALS['LANG']->getLL('groupBy', TRUE) . '</p>';
			$outCode .= '<table border="0" cellpadding="3" cellspacing="1" class="compare-checklist valign-top"><tr>';
			foreach ($menu as $column) {
				$outCode .= '<td>' . implode('<br />', $column) . '</td>';
			}
			$outCode .= '</tr></table>';
			$outCode.='<br /><input type="submit" name="ads" value="' . $GLOBALS['LANG']->getLL('update', TRUE) . '">';
			$content = $this->doc->section($GLOBALS['LANG']->getLL('groupAndCompareUsers', TRUE), $outCode, FALSE, TRUE);

				// Traverse all users
			$users = t3lib_BEfunc::getUserNames();
			$comparation=array();
			$counter=0;


			$offset=0;
			$numberAtTime=1000;
			$tooManyUsers='';

			foreach ($users as $r) {
				if ($counter>=$offset) {
						// This is used to test with other users. Development ONLY!
					$tempBE_USER = t3lib_div::makeInstance('tx_beuser_local_beUserAuth');	// New backend user object
					/* @var $tempBE_USER tx_beuser_local_beUserAuth */
					$tempBE_USER->OS = TYPO3_OS;
					$tempBE_USER->setBeUserByUid($r['uid']);
					$tempBE_USER->fetchGroupData();

						// Making group data
					$md5pre='';
					$menu=array();
					$uInfo = $tempBE_USER->ext_compileUserInfoForHash((array)$compareFlags);
					foreach ($options as $kk => $vv) {
						if ($compareFlags[$kk]) {
							$md5pre.=serialize($uInfo[$kk]).'|';
						}
					}
						// setting md5:
					$md5=md5($md5pre);
					if (!isset($comparation[$md5])) {
						$comparation[$md5]=$tempBE_USER->ext_printOverview($uInfo, $compareFlags);
						$comparation[$md5]['users']=array();
					}
					$comparation[$md5]['users'][]=$tempBE_USER->user;
					unset($tempBE_USER);
				}
				$counter++;
				if ($counter>=($numberAtTime+$offset)) {
					$tooManyUsers=$GLOBALS['LANG']->getLL('tooManyUsers', TRUE) . ' ' . count($users) . '. ' . $GLOBALS['LANG']->getLL('canOnlyDisplay', TRUE) . ' ' . $numberAtTime . '.';
					break;
				}
			}

				// Print the groups:
			$allGroups=array();
				// Header:
			$allCells = array();

			$allCells['USERS'] = '<table border="0" cellspacing="0" cellpadding="0" width="100%"><tr><td><strong>' . $GLOBALS['LANG']->getLL('usernames', TRUE) . '</strong></td></table>';

			foreach ($options as $kk => $vv) {
				if ($compareFlags[$kk]) {
					$allCells[$kk] = '<strong>'.$vv.':</strong>';
				}
			}
			$allGroups[]=$allCells;

			foreach ($comparation as $dat) {
				$allCells = array();

				$curUid = $GLOBALS['BE_USER']->user['uid'];
				$uListArr=array();

				foreach ($dat['users'] as $uDat) {
					$uItem = '<tr><td width="130">' . t3lib_iconWorks::getSpriteIconForRecord('be_users', $uDat, array('title'=> $uDat['uid'] )) . $this->linkUser($uDat['username'], $uDat) . '&nbsp;&nbsp;</td><td nowrap="nowrap">' . $this->elementLinks('be_users', $uDat);
					if ($curUid != $uDat['uid'] && !$uDat['disable'] && ($uDat['starttime'] == 0 ||
						$uDat['starttime'] < $GLOBALS['EXEC_TIME']) && ($uDat['endtime'] == 0 ||
						$uDat['endtime'] > $GLOBALS['EXEC_TIME'])) {
						$uItem .= '<a href="' . t3lib_div::linkThisScript(array('SwitchUser'=>$uDat['uid'])) . '" target="_top" title="' . htmlspecialchars($GLOBALS['LANG']->getLL('switchUserTo', TRUE) . ' ' . $uDat['username']) . ' ' . $GLOBALS['LANG']->getLL('changeToMode', TRUE) . '">' .
								t3lib_iconWorks::getSpriteIcon('actions-system-backend-user-switch') .
							'</a>'.
							'<a href="' . t3lib_div::linkThisScript(array('SwitchUser'=>$uDat['uid'], 'switchBackUser' => 1)) . '" target="_top" title="' . htmlspecialchars($GLOBALS['LANG']->getLL('switchUserTo', TRUE) . ' ' . $uDat['username']) . ' ' . $GLOBALS['LANG']->getLL('switchBackMode', TRUE) . '">' .
								t3lib_iconWorks::getSpriteIcon('actions-system-backend-user-emulate') .
							'</a>';
					}
					$uItem .= '</td></tr>';
					$uListArr[] = $uItem;
				}
				$allCells['USERS'] = '<table border="0" cellspacing="0" cellpadding="0" width="100%">'.implode('', $uListArr).'</table>';

				foreach ($options as $kk => $vv) {
					if ($compareFlags[$kk]) {
						$allCells[$kk] = $dat[$kk];
					}
				}
				$allGroups[]=$allCells;
			}

				// Make table
			$outTable='';
			$TDparams=' nowrap="nowrap" class="bgColor5" valign="top"';
			$i = 0;
			foreach ($allGroups as $allCells) {
				$outTable.='<tr><td'.$TDparams.'>'.implode('</td><td'.$TDparams.'>', $allCells).'</td></tr>';
				$TDparams=' nowrap="nowrap" class="'.($i++ % 2 == 0 ? 'bgColor4' : 'bgColor6').'" valign="top"';
			}
			$outTable='<table border="0" cellpadding="2" cellspacing="2">' . $outTable . '</table>';
			$flashMessageCachedGrouplistsUpdated = t3lib_div::makeInstance(
				't3lib_FlashMessage',
				'',
				$GLOBALS['LANG']->getLL('cachedGrouplistsUpdated', TRUE),
				t3lib_FlashMessage::INFO
			);
			t3lib_FlashMessageQueue::addMessage($flashMessageCachedGrouplistsUpdated);
			if ($tooManyUsers) {
				$flashMessageTooManyUsers = t3lib_div::makeInstance(
					't3lib_FlashMessage',
					'',
					$tooManyUsers,
					t3lib_FlashMessage::ERROR
				);
				t3lib_FlashMessageQueue::addMessage($flashMessageTooManyUsers);
			}
			$content.= $this->doc->spacer(10);
			$content.= $this->doc->section($GLOBALS['LANG']->getLL('result', TRUE), $outTable, 0, 1);
		}
		return $content;
	}

	/**
	 * Creates a HTML anchor to the user record
	 *
	 * @param	string		the string used to identify the user (inside the <a>...</a>)
	 * @param	array		the BE user record to link
	 * @return	string		the HTML anchor
	 */
	function linkUser($str, $rec) {
		return '<a href="'.htmlspecialchars($this->MCONF['_']).'&be_user_uid='.$rec['uid'].'">' . htmlspecialchars($str) . '</a>';
	}

	/**
	 * Builds a list of all links for a specific element (here: BE user) and returns it for print.
	 *
	 * @param	string		the db table that should be used
	 * @param	array		the BE user record to use
	 * @return	string		a HTML formatted list of the link
	 */
	function elementLinks($table, $row) {
			// Info:
		$cells[]='<a href="#" onclick="top.launchView(\'' . $table . '\', \'' . $row['uid'] . '\',\'' . $GLOBALS['BACK_PATH'] . '\'); return false;" title="' . $GLOBALS['LANG']->getLL('showInformation', TRUE) . '">' .
				t3lib_iconWorks::getSpriteIcon('actions-document-info') .
			'</a>';

			// Edit:
		$params='&edit[' . $table . '][' . $row['uid'] . ']=edit';
		$cells[]='<a href="#" onclick="' . t3lib_BEfunc::editOnClick($params, $GLOBALS['BACK_PATH'], '') . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:edit', TRUE) . '">' .
				t3lib_iconWorks::getSpriteIcon('actions-document-open') .
			'</a>';

			// Hide:
		$hiddenField = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'];
		if ($row[$hiddenField]) {
			$params='&data[' . $table . '][' . $row['uid'] . '][' . $hiddenField . ']=0';
			$cells[]='<a href="' . $this->doc->issueCommand($params) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:enable', TRUE) . '">' .
				t3lib_iconWorks::getSpriteIcon('actions-edit-unhide') .
			'</a>';
		} else {
			$params='&data[' . $table . '][' . $row['uid'] . '][' . $hiddenField . ']=1';
			$cells[]='<a href="' . $this->doc->issueCommand($params) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:disable', TRUE) . '">' .
				t3lib_iconWorks::getSpriteIcon('actions-edit-hide') .
			'</a>';
		}

			// Delete
		$params='&cmd[' . $table . '][' . $row['uid'] . '][delete]=1';
		$cells[]='<a href="' . $this->doc->issueCommand($params) . '" onclick="return confirm(unescape(\'' . $GLOBALS['LANG']->getLL('sureToDelete', TRUE) . '\'));" title="' . $GLOBALS['LANG']->getLL('delete', TRUE) . '">' .
				t3lib_iconWorks::getSpriteIcon('actions-edit-delete') .
			'</a>';

		return implode('', $cells);
	}

	/**
	 * Inits all BE-users available, for development ONLY!
	 *
	 * @return	void
	 */
	function initUsers() {
			// Initializing all users in order to generate the usergroup_cached_list
		$users = t3lib_BEfunc::getUserNames();

			// This is used to test with other users. Development ONLY!
		foreach ($users as $r) {
			$tempBE_USER = t3lib_div::makeInstance('tx_beuser_local_beUserAuth');	// New backend user object
			/* @var $tempBE_USER tx_beuser_local_beUserAuth */
			$tempBE_USER->OS = TYPO3_OS;
			$tempBE_USER->setBeUserByUid($r['uid']);
			$tempBE_USER->fetchGroupData();
		}
	}

	/**
	 * Returns the local path for this string (removes the PATH_site if it is included)
	 *
	 * @param	string		the path that will be checked
	 * @return	string		the local path
	 */
	function localPath($str) {
		if (substr($str, 0, strlen(PATH_site))==PATH_site) {
			return substr($str, strlen(PATH_site));
		} else {
			return $str;
		}
	}

	/**
	 * Switches to a given user (SU-mode) and then redirects to the start page of the backend to refresh the navigation etc.
	 *
	 * @param	array		BE-user record that will be switched to
	 * @return	void
	 */
	function switchUser($switchUser) {
		$uRec=t3lib_BEfunc::getRecord('be_users', $switchUser);
		if (is_array($uRec) && $GLOBALS['BE_USER']->isAdmin()) {
			$updateData['ses_userid'] = $uRec['uid'];
				// user switchback
			if (t3lib_div::_GP('switchBackUser')) {
				$updateData['ses_backuserid'] = intval($GLOBALS['BE_USER']->user['uid']);
			}
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('be_sessions', 'ses_id=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($GLOBALS['BE_USER']->id, 'be_sessions') . ' AND ses_name=' . $GLOBALS['TYPO3_DB']->fullQuoteStr(t3lib_beUserAuth::getCookieName(), 'be_sessions') . ' AND ses_userid=' . intval($GLOBALS['BE_USER']->user['uid']), $updateData);

			$redirectUrl = $GLOBALS['BACK_PATH'] . 'index.php' . ($GLOBALS['TYPO3_CONF_VARS']['BE']['interfaces'] ? '' : '?commandLI=1');
			t3lib_utility_Http::redirect($redirectUrl);
		}
	}

	/***************************
	 *
	 * "WHO IS ONLINE" FUNCTIONS:
	 *
	 ***************************/

	/**
	 * @author Martin Kutschker
	 */
	function whoIsOnline() {
		$select_fields = 'ses_id, ses_tstamp, ses_iplock, u.uid,u.username, u.admin, u.realName, u.disable, u.starttime, u.endtime, u.deleted, bu.uid AS bu_uid,bu.username AS bu_username, bu.realName AS bu_realName';
		$from_table = 'be_sessions INNER JOIN be_users u ON ses_userid=u.uid LEFT OUTER JOIN be_users bu ON ses_backuserid=bu.uid';
		$where_clause = '';
		$orderBy = 'u.username';

		$timeout = intval($GLOBALS['TYPO3_CONF_VARS']['BE']['sessionTimeout']);
		if ($timeout > 0) {
			$where_clause = 'ses_tstamp > ' . ($GLOBALS['EXEC_TIME'] - $timeout);
		}

			// Fetch active sessions of other users from storage:
		$sessions = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($select_fields, $from_table, $where_clause, '', $orderBy);
			// Process and visualized each active session as a table row:
		if (is_array($sessions)) {
			foreach ($sessions as $session) {
				$ip = $session['ses_iplock'];
				$hostName = '';
				if ($session['ses_iplock'] == '[DISABLED]' || $GLOBALS['TYPO3_CONF_VARS']['BE']['lockIP'] == 0) {
					$ip = '-';
				} elseif ($GLOBALS['TYPO3_CONF_VARS']['BE']['lockIP'] == 4) {
					$hostName = ' title="' . @gethostbyaddr($session['ses_iplock']) . '"';
				} else {
					$ip .= str_repeat('.*', 4-$GLOBALS['TYPO3_CONF_VARS']['BE']['lockIP']);
				}
				$outTable .= '
					<tr class="bgColor4" height="17" valign="top">' .
						'<td nowrap="nowrap">' .
							t3lib_BEfunc::datetime($session['ses_tstamp']) .
						'</td>' .
						'<td nowrap="nowrap">' .
							'<span'.$hostName.'>'.$ip.'</span>' .
						'</td>' .
						'<td width="130">' .
							t3lib_iconWorks::getSpriteIconForRecord('be_users', $session, array('title'=>$session['uid'])).htmlspecialchars($session['username']).'&nbsp;' .
						'</td>' .
						'<td nowrap="nowrap">'.htmlspecialchars($session['realName']).'&nbsp;&nbsp;</td>' .
						'<td nowrap="nowrap">'.$this->elementLinks('be_users', $session).'</td>' .
						'<td nowrap="nowrap" valign="top">'.($session['bu_username'] ? '&nbsp;SU from: ' : '').htmlspecialchars($session['bu_username']).'&nbsp;</td>' .
						'<td nowrap="nowrap" valign="top">&nbsp;'.htmlspecialchars($session['bu_realName']).'</td>' .
					'</tr>';
			}
		}
			// Wrap <table> tag around the rows:
		$outTable = '
		<table border="0" cellpadding="0" cellspacing="0" class="typo3-dblist">
			<tr class="t3-row-header">
				<td>' . $GLOBALS['LANG']->getLL('timestamp', TRUE) . '</td>
				<td>' . $GLOBALS['LANG']->getLL('host', TRUE) . '</td>
				<td colspan="5">' . $GLOBALS['LANG']->getLL('username', TRUE) . '</td>
			</tr>' . $outTable . '
		</table>';

		$content .= $this->doc->section($GLOBALS['LANG']->getLL('whoIsOnline', TRUE), $outTable, FALSE, TRUE);
		return $content;
	}

}

// Make instance:
$SOBE = t3lib_div::makeInstance('SC_mod_tools_be_user_index');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

?>