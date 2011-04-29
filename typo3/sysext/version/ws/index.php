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
 * Module: Workspace manager
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	Dmitry Dulepov <typo3@accio.lv>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  101: class SC_mod_user_ws_index extends t3lib_SCbase
 *
 *              SECTION: Standard module initialization
 *  128:     function menuConfig()
 *  175:     function init()
 *  233:     function main()
 *  280:     function printContent()
 *
 *              SECTION: Module content: Publish
 *  310:     function moduleContent_publish()
 *  411:     function displayVersionDetails($details)
 *  420:     function displayWorkspaceOverview()
 *
 *              SECTION: Module content: Workspace list
 *  461:     function moduleContent_workspaceList()
 *  476:     function workspaceList_displayUserWorkspaceList()
 *  553:     function workspaceList_getUserWorkspaceList()
 *  592:     function workspaceList_formatWorkspaceData(&$wksp)
 *  634:     function workspaceList_getWebMountPoints(&$wksp)
 *  683:     function workspaceList_getFileMountPoints(&$wksp)
 *  736:     function workspaceList_displayUserWorkspaceListHeader()
 *  756:     function workspaceList_getUserList(&$wksp)
 *  783:     function workspaceList_getUserListForSysWorkspace(&$wksp)
 *  810:     function workspaceList_getUserListWithAccess(&$list, $access)
 *  883:     function workspaceList_displayIcons($currentWorkspace, &$wksp)
 *  931:     function workspaceList_hasEditAccess(&$wksp)
 *  943:     function workspaceList_createFakeWorkspaceRecord($uid)
 *
 * TOTAL FUNCTIONS: 20
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

	// Initialize module:
unset($MCONF);
require ('conf.php');
require ($BACK_PATH.'init.php');
require ($BACK_PATH.'template.php');
$BE_USER->modAccess($MCONF,1);

	// Include libraries of various kinds used inside:
$LANG->includeLLFile('EXT:lang/locallang_mod_user_ws.xml');
$LANG->includeLLFile('EXT:lang/locallang_misc.xml');
require_once('class.wslib.php');
require_once('class.wslib_gui.php');




/**
 * Module: Workspace manager
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_mod_user_ws_index extends t3lib_SCbase {

		// Default variables for backend modules
	var $MCONF = array();				// Module configuration
	var $MOD_MENU = array();			// Module menu items
	var $MOD_SETTINGS = array();		// Module session settings

	/**
	 * Document Template Object
	 *
	 * @var noDoc
	 */
	var $doc;
	var $content;						// Accumulated content


		// Internal:
	var $publishAccess = FALSE;
	var $be_user_Array = array();
	var $be_user_Array_full = array();	// not blinded, used by workspace listing
	protected $showDraftWorkspace = FALSE;	// Determines whether the draft workspace is shown


	/*********************************
	 *
	 * Standard module initialization
	 *
	 *********************************/

	/**
	 * Initialize menu configuration
	 *
	 * @return	void
	 */
	function menuConfig()	{
		global	$LANG;

			// fetches the configuration of the version extension
		$versionExtconf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['version']);
			// show draft workspace only if enabled in the version extensions config
		if($versionExtconf['showDraftWorkspace']) {
			$this->showDraftWorkspace = TRUE;
		}

			// Menu items:
		$this->MOD_MENU = array(
			'function' => array(
				'publish' => $LANG->getLL('menuitem_review'),
				'workspaces' => $LANG->getLL('menuitem_workspaces'),
			),
			'filter' => array(
				1 => $LANG->getLL('filter_drafts'),
				2 => $LANG->getLL('filter_archive'),
				0 => $LANG->getLL('filter_all'),
			),
			'display' => array(
				0 => '['.$LANG->getLL('shortcut_onlineWS').']',
				-98 => $LANG->getLL('label_offlineWSes'),
				-99 => $LANG->getLL('label_allWSes')
			),
			'diff' => array(
				0 => $LANG->getLL('diff_no_diff'),
				1 => $LANG->getLL('diff_show_inline'),
				2 => $LANG->getLL('diff_show_popup'),
			),
			'expandSubElements' => '',
		);

			// check if draft workspace was enabled, and if the user has access to it
		if ($this->showDraftWorkspace === TRUE && $GLOBALS['BE_USER']->checkWorkspace(array('uid' => -1))) {
			$this->MOD_MENU['display'][-1] = '[' . $LANG->getLL('shortcut_offlineWS') . ']';
		}

			// Add workspaces:
		if ($GLOBALS['BE_USER']->workspace===0)	{	// Spend time on this only in online workspace because it might take time:
			$workspaces = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,title,adminusers,members,reviewers','sys_workspace','pid=0'.t3lib_BEfunc::deleteClause('sys_workspace'),'','title');
			foreach($workspaces as $rec)	{
				if ($GLOBALS['BE_USER']->checkWorkspace($rec))	{
					$this->MOD_MENU['display'][$rec['uid']] = '[' . $rec['uid'] . '] ' . htmlspecialchars($rec['title']);
				}
			}
		}

			// CLEANSE SETTINGS
		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::_GP('SET'), $this->MCONF['name'], 'ses');
	}

	/**
	 * Executes action for selected elements, if any is sent:
	 */
	function execute()	{
		$post = t3lib_div::_POST();

#		debug($post);

		if ($post['_with_selected_do'])	{
			if (is_array($post['items']) && count($post['items']))	{
				$cmdArray = array();

				foreach($post['items'] as $item => $v)	{
					list($table,$uid) = explode(':',$item,2);

					if ($GLOBALS['TCA'][$table] && t3lib_div::testInt($uid))	{
						switch($post['_with_selected_do'])	{
							case "stage_-1":
								$cmdArray[$table][$uid]['version']['action'] = 'setStage';
								$cmdArray[$table][$uid]['version']['stageId'] = -1;
							break;
							case "stage_0":
								$cmdArray[$table][$uid]['version']['action'] = 'setStage';
								$cmdArray[$table][$uid]['version']['stageId'] = 0;
							break;
							case "stage_1":
								$cmdArray[$table][$uid]['version']['action'] = 'setStage';
								$cmdArray[$table][$uid]['version']['stageId'] = 1;
							break;
							case "stage_10":
								$cmdArray[$table][$uid]['version']['action'] = 'setStage';
								$cmdArray[$table][$uid]['version']['stageId'] = 10;
							break;
							case "publish":
								if ($onlineRec = t3lib_BEfunc::getLiveVersionOfRecord($table,$uid,'uid'))	{
									$cmdArray[$table][$onlineRec['uid']]['version']['action'] = 'swap';
									$cmdArray[$table][$onlineRec['uid']]['version']['swapWith'] = $uid;
								}
							break;
							case "swap":
							if ($onlineRec = t3lib_BEfunc::getLiveVersionOfRecord($table,$uid,'uid'))	{
								$cmdArray[$table][$onlineRec['uid']]['version']['action'] = 'swap';
								$cmdArray[$table][$onlineRec['uid']]['version']['swapWith'] = $uid;
								$cmdArray[$table][$onlineRec['uid']]['version']['swapIntoWS'] = 1;
							}
							break;
							case "release":
								$cmdArray[$table][$uid]['version']['action'] = 'clearWSID';
							break;
							case "flush":
								$cmdArray[$table][$uid]['version']['action'] = 'flush';
							break;
						}
					}
				}

				/** @var $tce t3lib_TCEmain */
				$tce = t3lib_div::makeInstance('t3lib_TCEmain');
				$tce->stripslashes_values = 0;
				$tce->start(array(), $cmdArray);
				$tce->process_cmdmap();
				$tce->printLogErrorMessages('');
			}
		}
	}

	/**
	 * Standard init function of a module.
	 *
	 * @return	void
	 */
	function init()	{
		global $BACK_PATH, $BE_USER;

			// Setting module configuration:
		$this->MCONF = $GLOBALS['MCONF'];

			// Initialize Document Template object:
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $BACK_PATH;
		$this->doc->setModuleTemplate('templates/ws.html');

			// JavaScript
		$this->doc->JScode = $this->doc->wrapScriptTags('
			script_ended = 0;
			function jumpToUrl(URL)	{	//
				window.location.href = URL;
			}

			function expandCollapse(rowNumber) {
				elementId = "wl_" + rowNumber;
				element = document.getElementById(elementId);
				image = document.getElementById("spanw1_" + rowNumber);
				if (element.style)	{
					if (element.style.display == "none")	{
						element.style.display = "table-row";
						image.className = "t3-icon t3-icon-actions t3-icon-actions-view t3-icon-view-table-collapse";
					} else {
						element.style.display = "none";
						image.className = "t3-icon t3-icon-actions t3-icon-actions-view t3-icon-view-table-expand";
					}
				}
			}
		');
		$this->doc->form = '<form action="index.php" method="post" name="pageform">';

			// Setting up the context sensitive menu:
		$this->doc->getContextMenuCode();

			// Setting publish access permission for workspace:
		$this->publishAccess = $BE_USER->workspacePublishAccess($BE_USER->workspace);

			// Parent initialization:
		parent::init();
	}

	/**
	 * Main function for Workspace Manager module.
	 *
	 * @return	void
	 */
	function main()	{
		global $LANG, $BE_USER, $BACK_PATH;

		// See if we need to switch workspace
		$changeWorkspace = t3lib_div::_GET('changeWorkspace');
		if ($changeWorkspace != '') {
			$BE_USER->setWorkspace($changeWorkspace);
			$this->content .= $this->doc->wrapScriptTags('top.location.href="' . $BACK_PATH . t3lib_BEfunc::getBackendScript() . '";');
		} else {
				// Starting page:
			$this->content.=$this->doc->header($LANG->getLL('title'));
			$this->content.=$this->doc->spacer(5);

			// Get usernames and groupnames
			$be_group_Array = t3lib_BEfunc::getListGroupNames('title,uid');
			$groupArray = array_keys($be_group_Array);
			// Need 'admin' field for t3lib_iconWorks::getIconImage()
			$this->be_user_Array_full = $this->be_user_Array = t3lib_BEfunc::getUserNames('username,usergroup,usergroup_cached_list,uid,admin,workspace_perms');
			if (!$GLOBALS['BE_USER']->isAdmin()) {
				$this->be_user_Array = t3lib_BEfunc::blindUserNames($this->be_user_Array,$groupArray,1);
			}

			// Build top menu:
			$menuItems = array();
			$menuItems[] = array(
				'label' => $LANG->getLL('menuitem_review'),
				'content' => $this->moduleContent_publish()
			);
			$menuItems[] = array(
				'label' => $LANG->getLL('menuitem_workspaces'),
				'content' => $this->moduleContent_workspaceList()
			);

				// Add hidden fields and create tabs:
			$content = $this->doc->getDynTabMenu($menuItems, 'user_ws');
			$this->content.=$this->doc->section('',$content,0,1);

				// Setting up the buttons and markers for docheader
			$docHeaderButtons = $this->getButtons();
			// $markers['CSH'] = $docHeaderButtons['csh'];

		}
		$markers['CONTENT'] = $this->content;

			// Build the <body> for the module
		$this->content = $this->doc->startPage($LANG->getLL('title'));
		$this->content.= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		$this->content.= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);

	}

	/**
	 * Print module content. Called as last thing in the global scope.
	 *
	 * @return	void
	 */
	function printContent()	{
		echo $this->content;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return	array	all available buttons as an assoc. array
	 */
	protected function getButtons()	{
		global $LANG, $BACK_PATH;

		$buttons = array(
			'new_record' => '',
		);

		$newWkspUrl = 'workspaceforms.php?action=new';

			// workspace creation link
		if ($GLOBALS['BE_USER']->isAdmin() || 0 != ($GLOBALS['BE_USER']->groupData['workspace_perms'] & 4))	{
			$buttons['new_record'] = '<a href="' . $newWkspUrl . '">' .
						'<img ' . t3lib_iconWorks::skinImg($BACK_PATH, 'gfx/add_workspaces.gif') . ' alt="' . $LANG->getLL('img_title_create_new_workspace') . '" id="ver-wl-new-workspace-icon" />' .
						'</a>';
		}
		return $buttons;
	}











	/*********************************
	 *
	 * Module content: Publish
	 *
	 *********************************/

	/**
	 * Rendering the content for the publish and review panel in the workspace manager
	 *
	 * @return	string		HTML content
	 */
	function moduleContent_publish()	{
		global	$LANG;

			// Initialize:
		$content = '';
		$details = t3lib_div::_GP('details');

			// Create additional menus:
		$menu = '';
		if ($GLOBALS['BE_USER']->workspace===0)	{
			$menu.= t3lib_BEfunc::getFuncMenu(0,'SET[filter]',$this->MOD_SETTINGS['filter'],$this->MOD_MENU['filter']);
			$menu.= t3lib_BEfunc::getFuncMenu(0,'SET[display]',$this->MOD_SETTINGS['display'],$this->MOD_MENU['display']);
		}
		$menu.= t3lib_BEfunc::getFuncMenu(0,'SET[diff]',$this->MOD_SETTINGS['diff'],$this->MOD_MENU['diff']);
		if ($GLOBALS['BE_USER']->workspace!==0)	{
			$menu.= t3lib_BEfunc::getFuncCheck(0,'SET[expandSubElements]',$this->MOD_SETTINGS['expandSubElements'],'','','id="checkExpandSubElements"').' <label for="checkExpandSubElements">'.$LANG->getLL('label_showsubelements').'</label> ';
		}

			// Create header:
		$title = '';
		$description = '';
		switch($GLOBALS['BE_USER']->workspace)	{
			case 0:
				$title = t3lib_iconWorks::getIconImage('sys_workspace', array(), $this->doc->backPath, ' align="top"').'['.$LANG->getLL('shortcut_onlineWS').']';
				$description = $LANG->getLL('workspace_description_live');
			break;
			case -1:
				$title = t3lib_iconWorks::getIconImage('sys_workspace', array(), $this->doc->backPath, ' align="top"').'['.$LANG->getLL('shortcut_offlineWS').']';
				$description = $LANG->getLL('workspace_description_draft');
			break;
			case -99:
				$title = $this->doc->icons(3).'[' . $LANG->getLL('shortcut_noWSfound') . ']';
				$description = $LANG->getLL('workspace_description_no_access');
			break;
			default:
				$title = t3lib_iconWorks::getIconImage('sys_workspace', $GLOBALS['BE_USER']->workspaceRec, $this->doc->backPath, ' align="top"').
							'['.$GLOBALS['BE_USER']->workspace.'] '.t3lib_BEfunc::getRecordTitle('sys_workspace',$GLOBALS['BE_USER']->workspaceRec,TRUE);
				$description = $GLOBALS['BE_USER']->workspaceRec['description'];
			break;
		}

			// Buttons for publish / swap:
		$actionLinks = '';
		if ($GLOBALS['BE_USER']->workspace!==0)	{
			if ($this->publishAccess)	{
				$confirmation = $LANG->JScharCode($LANG->getLL(($GLOBALS['BE_USER']->workspaceRec['publish_access'] & 1) ? 'submit_publish_workspace_confirmation_1' :  'submit_publish_workspace_confirmation_2'));
				$actionLinks.= '<input type="submit" name="_publish" value="' . $LANG->getLL('submit_publish_workspace') . '" onclick="if (confirm(' . $confirmation . ')) window.location.href=\'publish.php?swap=0\';return false"/>';
				if ($GLOBALS['BE_USER']->workspaceSwapAccess())	{
					$confirmation = $LANG->JScharCode($LANG->getLL(($GLOBALS['BE_USER']->workspaceRec['publish_access'] & 1) ? 'submit_swap_workspace_confirmation_1' :  'submit_swap_workspace_confirmation_2'));
					$actionLinks.= '<input type="submit" name="_swap" value="' . $LANG->getLL('submit_swap_workspace') . '" onclick="if (confirm(' . $confirmation . ')) window.location.href=\'publish.php?swap=1\';return false ;" />';
				}
			} else {
				$actionLinks.= $this->doc->icons(1) . $LANG->getLL('no_publish_permission');
			}

				// Preview of workspace link
			if (t3lib_div::_POST('_previewLink'))	{
				$ttlHours = intval($GLOBALS['BE_USER']->getTSConfigVal('options.workspaces.previewLinkTTLHours'));
				$ttlHours = ($ttlHours ? $ttlHours : 24*2);
				$previewUrl = t3lib_BEfunc::getViewDomain($this->id) . '/index.php?ADMCMD_prev=' . t3lib_BEfunc::compilePreviewKeyword('', $GLOBALS['BE_USER']->user['uid'], 60*60*$ttlHours, $GLOBALS['BE_USER']->workspace) . '&id=' . intval($GLOBALS['BE_USER']->workspaceRec['db_mountpoints']);
				$actionLinks.= '<br />Any user can browse the workspace frontend using this link for the next ' . $ttlHours . ' hours (does not require backend login):<br /><br /><a target="_blank" href="' . htmlspecialchars($previewUrl) . '">' . $previewUrl . '</a>';
			} else {
				$actionLinks.= '<input type="submit" name="_previewLink" value="Generate Workspace Preview Link" />';
			}
		}

		$wsAccess = $GLOBALS['BE_USER']->checkWorkspace($GLOBALS['BE_USER']->workspaceRec);

			// Add header to content variable:
		$content = '
		<table border="0" cellpadding="0" cellspacing="0" id="t3-user-ws-wsinfotable" class="t3-table t3-table-info">
			<tr>
				<td class="t3-col-header" nowrap="nowrap">' . $LANG->getLL('label_workspace') . '&nbsp;</th>
				<td nowrap="nowrap">' . $title . '</td>
			</tr>
			<tr>' . ($description ? '
				<td class="t3-col-header" nowrap="nowrap">' . $LANG->getLL('label_description') . '&nbsp;</td>
				<td>' . $description . '</td>
			</tr>' : '') . ($GLOBALS['BE_USER']->workspace!=-99 && !$details ? '
			<tr>
				<td class="t3-col-header" nowrap="nowrap">' . $LANG->getLL('label_options') . '&nbsp;</td>
				<td>' . $menu . $actionLinks . '</td>
			</tr>
			<tr>
				<td class="t3-col-header" nowrap="nowrap">' . $LANG->getLL('label_status') . '&nbsp;</td>
				<td>' . $LANG->getLL('label_access_level') . ' ' . $GLOBALS['LANG']->getLL('workspace_list_access_' . $wsAccess['_ACCESS']) . '</td>
			</tr>' : '').'
		</table>
		<br />
		';

			// Add publishing and review overview:
		if ($GLOBALS['BE_USER']->workspace!=-99)	{
			if ($details)	{
				$content.= $this->displayVersionDetails($details);
			} else {
				$content.= $this->displayWorkspaceOverview();
			}
			$content .= '<br />';
		}

			// Return content:
		return $content;
	}

	/**
	 * Display details for a single version from workspace
	 *
	 * @param	string		Version identification, made of table and uid
	 * @return	string		HTML string
	 */
	function displayVersionDetails($details)	{
		return 'TODO: Show details for version "'.$details.'"<hr/><a href="index.php">BACK</a>';
	}

	/**
	 * Rendering the overview of versions in the current workspace
	 *
	 * @return	string		HTML (table)
	 */
	function displayWorkspaceOverview()	{

			// Initialize Workspace ID and filter-value:
		if ($GLOBALS['BE_USER']->workspace===0)	{
			$wsid = $this->MOD_SETTINGS['display'];		// Set wsid to the value from the menu (displaying content of other workspaces)
			$filter = $this->MOD_SETTINGS['filter'];
		} else {
			$wsid = $GLOBALS['BE_USER']->workspace;
			$filter = 0;
		}

			// Instantiate workspace GUI library and generate workspace overview
		$wslibGuiObj = t3lib_div::makeInstance('wslib_gui');
		$wslibGuiObj->diff = $this->MOD_SETTINGS['diff'];
		$wslibGuiObj->expandSubElements = $this->MOD_SETTINGS['expandSubElements'];
		$wslibGuiObj->alwaysDisplayHeader = true;
		return $wslibGuiObj->getWorkspaceOverview($this->doc, $wsid, $filter);
	}













	/********************************
	 *
	 * Module content: Workspace list
	 *
	 ********************************/

	/**
	 * Rendering of the workspace list
	 *
	 * @return	string		HTML
	 */
	function moduleContent_workspaceList()	{
		// Original Kasper's TODO: Workspace listing
		//
		//	- LISTING: Shows list of available workspaces for user. Used can see title, description, publication time, freeze-state, db-mount, member users/groups etc. Current workspace is indicated.
		//	- SWITCHING: Switching between available workspaces is done by a button shown for each in the list
		//	- ADMIN: Administrator of a workspace can click an edit-button linking to a form where he can edit the workspace. Users and groups should be selected based on some filtering so he cannot select groups he is not a member off himself (or some other rule... like for permission display with blinded users/groups)
		//	- CREATE: If allowed, the user can create a new workspace which brings up a form where he can enter basic data. This is saved by a local instance of tcemain with forced admin-rights (creation in pid=0!).
		return $this->workspaceList_displayUserWorkspaceList();
	}

	/**
	 * Generates HTML to display a list of workspaces.
	 *
	 * @return	string		Generated HTML code
	 */
	function workspaceList_displayUserWorkspaceList()	{
		global	$BACK_PATH, $LANG;

			// table header
		$content = $this->workspaceList_displayUserWorkspaceListHeader();

			// get & walk workspace list generating content
		$wkspList = $this->workspaceList_getUserWorkspaceList();
		$rowNum = 1;
		foreach ($wkspList as $wksp)	{
			$currentWksp = ($GLOBALS['BE_USER']->workspace == $wksp['uid']);

			// Each workspace data occupies two rows:
			// (1) Folding + Icons + Title + Description
			// (2) Information about workspace (initially hidden)

			$cssClass = ($currentWksp ? 't3-row t3-row-active bgColor3' : 't3-row bgColor4');
				// Start first row
			$content .= '<tr class="' . $cssClass . '">';

				// row #1, column #1: expand icon
			$content .= '<td>' .
						'<a href="javascript:expandCollapse(' . $rowNum . ')">' .
						t3lib_iconWorks::getSpriteIcon('actions-view-table-expand', array(
							'title' => $LANG->getLL('img_title_show_more'),
							'id' => 'spanw1_' . $rowNum
						)) .
						'</a></td>';

				// row #1, column #2: icon panel
			$content .= '<td nowrap="nowrap">';	// Mozilla Firefox will attempt wrap due to `width="1"` on topmost column
			$content .= $this->workspaceList_displayIcons($currentWksp, $wksp);
			$content .= '</td>';

				// row #1, column #3: current workspace indicator
			$content .= '<td nowrap="nowrap" style="text-align: center">';	// Mozilla Firefox will attempt wrap due to `width="1"` on topmost column
			$content .= (!$currentWksp ? '&nbsp;' : '<img ' . t3lib_iconWorks::skinImg($BACK_PATH, 'gfx/icon_ok.gif', 'width="18" height="16"') . ' id="wl_' . $rowNum . 'i" border="0" hspace="1" alt="' . $LANG->getLL('img_title_current_workspace') . '" />');
			$content .= '</td>';

				// row #1, column #4 and 5: title and description
			$content .= '<td nowrap="nowrap">' . htmlspecialchars($wksp['title']) . '</td>' .
						'<td>' . nl2br(htmlspecialchars($wksp['description'])) . '</td>';
			$content .= '</tr>';

				// row #2, column #1 and #2
			$content .= '<tr id="wl_' . $rowNum . '" class="bgColor" style="display: none">';
			$content .= '<td colspan="2" style="border-right: none;">&nbsp;</td>';

				// row #2, column #3, #4 and #4
			$content .= '<td colspan="3" style="border-left: none;">' .
						$this->workspaceList_formatWorkspaceData($wksp) .
						'</td>';

			$content .= '</tr>';
			$rowNum++;
		}
		$content .= '</table>';

		return $content;
	}





	/**
	 * Retrieves a list of workspaces where user has access.
	 *
	 * @return	array		A list of workspaces available to the current BE user
	 */
	function workspaceList_getUserWorkspaceList()	{

			// Get list of all workspaces. Note: system workspaces will be always displayed before custom ones!
		$workspaces = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*','sys_workspace','pid=0'.t3lib_BEfunc::deleteClause('sys_workspace'),'','title');
		$availableWorkspaces = array();

			// Live
		$wksp = $this->workspaceList_createFakeWorkspaceRecord(0);
		$wksp = $GLOBALS['BE_USER']->checkWorkspace($wksp);
		if (FALSE !== $wksp) {
			$availableWorkspaces[] = $wksp;
		}

			// Draft
		$wksp = $this->workspaceList_createFakeWorkspaceRecord(-1);
		$wksp = $GLOBALS['BE_USER']->checkWorkspace($wksp);
		if (FALSE !== $wksp) {
			$availableWorkspaces[] = $wksp;
		}

			// Custom
		foreach($workspaces as $rec)	{
				// see if user can access this workspace in any way
			if (FALSE !== ($result = $GLOBALS['BE_USER']->checkWorkspace($rec)))	{
				$availableWorkspaces[] = $result;	// `$result` contains `$rec` plus access type through '_ACCESS' key
			}
		}
		return $availableWorkspaces;
	}

	/**
	 * Create inner information panel for workspace list. This panel is
	 * initially hidden and becomes visible when user click on the expand
	 * icon on the very left of workspace list against the workspace he
	 * wants to explore.
	 *
	 * @param	array		Workspace information
	 * @return	string		Formatted workspace information
	 */
	function workspaceList_formatWorkspaceData(&$wksp)	{
		global $LANG;

		$content = '<table cellspacing="0" cellpadding="0" width="100%" class="ver-wl-details-table">' .
				'<tr><td class="ver-wl-details-label"><strong>' . $LANG->getLL('workspace_list_label_file_mountpoints') . '</strong></td>' .
				'<td class="ver-wl-details">' . $this->workspaceList_getFileMountPoints($wksp) . '</td></tr>' .
				'<tr><td class="ver-wl-details-label"><strong>' . $LANG->getLL('workspace_list_label_db_mountpoints') . '</strong></td>' .
				'<td class="ver-wl-details">' . $this->workspaceList_getWebMountPoints($wksp) . '</td></tr>';
		if ($wksp['uid'] > 0) {
			// Displaying information below makes sence only for custom workspaces
			$content .=
				'<tr><td class="ver-wl-details-label"><strong>' . $LANG->getLL('workspace_list_label_frozen') . '</strong></td>' .
				'<td class="ver-wl-details">' . $LANG->getLL($wksp['freeze'] ? 'workspace_list_label_frozen_yes' : 'workspace_list_label_frozen_no') . '</td></tr>' .
				'<tr><td class="ver-wl-details-label"><strong>' . $LANG->getLL('workspace_list_label_publish_date') . '</strong></td>' .
				'<td class="ver-wl-details">' . ($wksp['publish_time'] == 0 ? '&nbsp;&ndash;' : t3lib_BEfunc::datetime($wksp['publish_time'])) . '</td></tr>' .
				'<tr><td class="ver-wl-details-label"><strong>' . $LANG->getLL('workspace_list_label_unpublish_date') . '</strong></td>' .
				'<td class="ver-wl-details">' . ($wksp['unpublish_time'] == 0 ? '&nbsp;&ndash;' : t3lib_BEfunc::datetime($wksp['unpublish_time'])) . '</td></tr>' .
				'<tr><td class="ver-wl-details-label"><strong>' . $LANG->getLL('workspace_list_label_your_access') . '</strong></td>' .
				'<td class="ver-wl-details">' . $LANG->getLL('workspace_list_access_' . $wksp['_ACCESS']) . '</td></tr>' .
				'<tr><td class="ver-wl-details-label"><strong>' . $LANG->getLL('workspace_list_label_workspace_users') . '</strong></td>' .
				'<td class="ver-wl-details">' . $this->workspaceList_getUserList($wksp) . '</td></tr>';
		}
		elseif ($GLOBALS['BE_USER']->isAdmin()) {
			// show users for draft/live workspace only to admin users
			$content .=	'<tr><td class="ver-wl-details-label"><strong>' . $LANG->getLL('workspace_list_label_workspace_users') . '</strong></td>' .
				'<td class="ver-wl-details">' . $this->workspaceList_getUserList($wksp) . '</td></tr>';
		}
		$content .= '</table>';

		return $content;
	}





	/**
	 * Retrieves and formats database mount points lists.
	 *
	 * @param	array		&$wksp	Workspace record
	 * @return	string		Generated HTML
	 */
	function workspaceList_getWebMountPoints(&$wksp)	{
		if ($wksp['uid'] == -1) {
				// draft workspace
			return $GLOBALS['LANG']->getLL('workspace_list_db_mount_point_draft');
		} elseif ($wksp['uid'] == 0) {
				// live workspace
			return $GLOBALS['LANG']->getLL('workspace_list_db_mount_point_live');
		}
		// -- here only if obtaining mount points for custom workspaces

			// We need to fetch user's mount point list (including MPS mounted from groups).
			// This list must not be affects by current user's workspace. It means we cannot use
			// $BE_USER->isInWebMount() to check mount points.
		$mountpointList = $GLOBALS['BE_USER']->groupData['webmounts'];
			// If there are DB mountpoints in the workspace record,
			// then only show the ones that are allowed there (and that are in the users' webmounts) 
		if (trim($wksp['db_mountpoints'])) {
			$userMountpoints = explode(',', $mountpointList);
				// now filter the users' to only keep the mountpoints 
				// that are also in the workspaces' db_mountpoints
			$workspaceMountpoints = explode(',', $wksp['db_mountpoints']);
			$filteredMountpoints = array_intersect($userMountpoints, $workspaceMountpoints);
			$mountpointList = implode(',', $filteredMountpoints);
		}

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',	// All fields needed for t3lib_iconWorks::getSpriteIconForRecord()
			'pages',
			'deleted = 0 AND uid IN (' . $GLOBALS['TYPO3_DB']->cleanIntList($mountpointList) . ')',
			'',
			'title'
		);

		$content = array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				// will show UID on hover. Just convinient to user.
			$content[] = t3lib_iconWorks::getSpriteIconForRecord('pages', $row) . '<span title="UID: ' . $row['uid'] . '">' . $row['title'] . '</span>';
		}

		if (count($content)) {
			return implode('<br />', $content);
		} else {
				// no mount points
			return $GLOBALS['LANG']->getLL('workspace_list_db_mount_point_custom');
		}
	}


	/**
	 * Retrieves and formats file mount points lists.
	 *
	 * @param	array		&$wksp	Workspace record
	 * @return	string		Generated HTML
	 */
	function workspaceList_getFileMountPoints(&$wksp)	{
		if ($wksp['uid'] == -1) {
				// draft workspace - none!
			return $GLOBALS['LANG']->getLL('workspace_list_file_mount_point_draft');
		} elseif ($wksp['uid'] == 0) {
				// live workspace
			return $GLOBALS['LANG']->getLL('workspace_list_file_mount_point_live');
		}
		// -- here only if displaying information for custom workspace

			// We need to fetch user's mount point list (including MPS mounted from groups).
			// This list must not be affects by current user's workspace. It means we cannot use
			// $BE_USER->isInWebMount() to check mount points.
		$mountpointList = implode(',', $GLOBALS['BE_USER']->groupData['filemounts']);
			// If there are file mountpoints in the workspace record,
			// then only show the ones that are allowed there (and that are in the users' file mounts) 
		if (trim($wksp['file_mountpoints'])) {
			$userMountpoints = explode(',', $mountpointList);
				// now filter the users' to only keep the mountpoints 
				// that are also in the workspaces' file_mountpoints
			$workspaceMountpoints = explode(',', $wksp['file_mountpoints']);
			$filteredMountpoints = array_intersect($userMountpoints, $workspaceMountpoints);
			$mountpointList = implode(',', $filteredMountpoints);
		}

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',	// All fields needed for t3lib_iconWorks::getSpriteIconForRecord()
			'sys_filemounts',
			'deleted = 0 AND hidden=0 AND uid IN (' . $GLOBALS['TYPO3_DB']->cleanIntList($mountpointList) . ')',
			'',
			'title'
		);

		$content = array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				// will show UID on hover. Just convinient to user.
			$content[] = t3lib_iconWorks::getSpriteIconForRecord('sys_filemounts', $row) . '<span title="UID: ' . $row['uid'] . '">' . $row['title'] . '</span>';
		}

		if (count($content)) {
			return implode('<br />', $content);
		} else {
				// no mount points
			return $GLOBALS['LANG']->getLL('workspace_list_file_mount_point_custom');
		}
	}

	/**
	 * Creates a header for the workspace list table. This function only makes
	 * <code>workspaceList_displayUserWorkspaceList()</code> smaller.
	 *
	 * @return	string		Generated content
	 */
	function workspaceList_displayUserWorkspaceListHeader()	{
		global	$LANG;
		// TODO CSH lables?
		return '<table border="0" cellpadding="0" cellspacing="0" class="workspace-overview">
			<tr class="t3-row-header">
				<td width="1">&nbsp;</td>
				<td width="1">&nbsp;</td>
				<td nowrap="nowrap">' . $LANG->getLL('workspace_list_label_current_workspace') . '</td>
				<td nowrap="nowrap">' . $LANG->getLL('workspace_list_label_workspace_title') . '</td>
				<td nowrap="nowrap">' . $LANG->getLL('workspace_list_label_workspace_description') . '</td>
			</tr>';
	}


	/**
	 * Generates a list of <code>&lt;option&gt;</code> tags with user names.
	 *
	 * @param	array		Workspace record
	 * @return	string		Generated content
	 */
	function workspaceList_getUserList(&$wksp) {
		global	$LANG;

		if ($wksp['uid'] > 0) {
			// custom workspaces
			$content = $this->workspaceList_getUserListWithAccess($wksp['adminusers'], $LANG->getLL('workspace_list_label_owners')); // owners
			$content .= $this->workspaceList_getUserListWithAccess($wksp['members'], $LANG->getLL('workspace_list_label_members')); // members
			$content .= $this->workspaceList_getUserListWithAccess($wksp['reviewers'], $LANG->getLL('workspace_list_label_reviewers')); // reviewers
			if ($content != '')	{
				$content = '<table cellpadding="0" cellspacing="1" width="100%" class="lrPadding workspace-overview">' . $content . '</table>';
			} else {
				$content = $LANG->getLL($wksp['uid'] > 0 ? 'workspace_list_access_admins_only' : 'workspace_list_access_anyone');
			}
		}
		else {
			// live and draft workspace
			$content = $this->workspaceList_getUserListForSysWorkspace($wksp);
		}
		return $content;
	}

	/**
	 * Generates a list of user names that has access to the system workspace.
	 *
	 * @param	array		&$wksp	Workspace record
	 * @return	string		Generated content
	 */
	function workspaceList_getUserListForSysWorkspace(&$wksp) {
		$option = ($wksp['uid'] == 0 ? 1 : 2);
		$content_array = array();
		foreach ($this->be_user_Array_full as $uid => $user) {
			if ($user['admin'] != 0 || 0 != ($user['workspace_perms'] & $option)) {
				if ($uid == $GLOBALS['BE_USER']->user['uid']) {
					// highlight current user
					$tag0 = '<span class="ver-wl-current-user">';
					$tag1 = '</span>';
				}
				else {
					$tag0 = $tag1 = '';
				}
				$content_array[] = $this->doc->wrapClickMenuOnIcon(t3lib_iconWorks::getIconImage('be_users', $uid, $GLOBALS['BACK_PATH'], ' align="middle" alt="UID: ' . $uid . '"'), 'be_users', $uid, 2).
					$tag0 . htmlspecialchars($user['username']) . $tag1;
			}
		}
		return implode('<br />', $content_array);
	}

	/**
	 * Generates a list of user names that has access to the workspace.
	 *
	 * @param	array		A list of user IDs separated by comma
	 * @param	string		Access string
	 * @return	string		Generated content
	 */
	function workspaceList_getUserListWithAccess(&$list, $access)	{
		$content_array = array();
		if ($list != '')	{
			$userIDs = explode(',', $list);

				// get user names and sort
			$regExp = '/^(be_[^_]+)_(\d+)$/';
			$groups = FALSE;
			foreach ($userIDs as $userUID)	{
				$id = $userUID;

				if (preg_match($regExp, $userUID)) {
					$table = preg_replace($regExp, '\1', $userUID);
					$id = intval(preg_replace($regExp, '\2', $userUID));
					if ($table == 'be_users') {
						// user
						$icon = $GLOBALS['TCA']['be_users']['typeicons'][$this->be_user_Array[$id]['admin']];
						if ($id == $GLOBALS['BE_USER']->user['uid']) {
							// highlight current user
							$tag0 = '<span class="ver-wl-current-user">';
							$tag1 = '</span>';
						}
						else {
							$tag0 = $tag1 = '';
						}
						$content_array[] = $this->doc->wrapClickMenuOnIcon(t3lib_iconWorks::getIconImage($table, $this->be_user_Array[$id], $GLOBALS['BACK_PATH'], ' align="middle" alt="UID: ' . $id . '"'), $table, $id, 2) .
											$tag0 . htmlspecialchars($this->be_user_Array_full[$id]['username']) . $tag1;
					}
					else {
						// group
						if (FALSE === $groups) {
							$groups = t3lib_BEfunc::getGroupNames();
						}
						$content_array[] = $this->doc->wrapClickMenuOnIcon(t3lib_iconWorks::getIconImage($table, $groups[$id], $GLOBALS['BACK_PATH'], ' align="middle" alt="UID: ' . $id . '"'), $table, $id, 2) .
											$groups[$id]['title'];
					}
				}
				else {
					// user id
					if ($userUID == $GLOBALS['BE_USER']->user['uid']) {
						// highlight current user
						$tag0 = '<span class="ver-wl-current-user">';
						$tag1 = '</span>';
					}
					else {
						$tag0 = $tag1 = '';
					}
					$content_array[] = t3lib_iconWorks::getIconImage('be_users', $this->be_user_Array[$id], $GLOBALS['BACK_PATH'], ' align="middle" alt="UID: ' . $id . '"') .
										$tag0 . htmlspecialchars($this->be_user_Array_full[$userUID]['username']) . $tag1;
				}
			}
			sort($content_array);
		}
		else {
			$content_array[] = '&nbsp;&ndash;';
		}

		$content = '<tr><td class="ver-wl-details-label ver-wl-details-user-list-label">';
		// TODO CSH lable explaining access here?
		$content .= '<strong>' . $access . '</strong></td>';
		$content .= '<td class="ver-wl-details">' . implode('<br />', $content_array) . '</td></tr>';
		return $content;
	}



	/**
	 * Creates a list of icons for workspace.
	 *
	 * @param	boolean		<code>true</code> if current workspace
	 * @param	array		Workspace record
	 * @return	string		Generated content
	 */
	function workspaceList_displayIcons($currentWorkspace, &$wksp)	{
		global	$BACK_PATH, $LANG;

		$content = '';
			// `edit workspace` button
		if ($this->workspaceList_hasEditAccess($wksp))	{
				// User can modify workspace parameters, display corresponding link and icon
			$editUrl = 'workspaceforms.php?action=edit&amp;wkspId=' . $wksp['uid'];

			$content .= '<a href="' . $editUrl . '" title="'. $LANG->getLL('workspace_list_icon_title_edit_workspace') .'"/>' .
					t3lib_iconWorks::getSpriteIcon('actions-document-open') .
			'</a>';
		} else {
				// User can NOT modify workspace parameters, display space
				// Get only withdth and height from skinning API
			$content .= '<img src="clear.gif" ' .
					t3lib_iconWorks::skinImg($BACK_PATH, 'gfx/edit2.gif', 'width="11" height="12"', 2) .
					' border="0" alt="" hspace="1" align="middle" />';
		}
			// `switch workspace` button
		if (!$currentWorkspace)	{
				// Workspace switching button
			$content .= '<a href="' .
					t3lib_div::getIndpEnv('SCRIPT_NAME') .
					'?changeWorkspace=' . $wksp['uid'] . '" title="'. $LANG->getLL('workspace_list_icon_title_switch_workspace') . '"/>' .
					t3lib_iconWorks::getSpriteIcon('actions-version-swap-workspace') .
					'</a>';
		} else {
				// Current workspace: empty space instead of workspace switching button
				//
				// Here get only width and height from skinning API
			$content .= '<img src="clear.gif" ' .
					t3lib_iconWorks::skinImg($BACK_PATH, 'gfx/switch.png', 'width="18" height="16"', 2) .
					' border="0" alt="" hspace="1" align="middle" alt="" />';
		}
		return $content;
	}

	/**
	 * Checks if user has edit access to workspace. Access is granted if
	 * workspace is custom and user is admin or the the owner of the workspace.
	 * This function assumes that <code>$wksp</code> were passed through
	 * <code>$GLOBALS['BE_USER']->checkWorkspace()</code> function to obtain
	 * <code>_ACCESS</code> attribute of the workspace.
	 *
	 * @param	array		Workspace record
	 * @return	boolean		<code>true</code> if user can modify workspace parameters
	 */
	function workspaceList_hasEditAccess(&$wksp)	{
		$access = &$wksp['_ACCESS'];
		return ($wksp['uid'] > 0 && ($access == 'admin' || $access == 'owner'));
	}

	/**
	 * Creates a fake workspace record for system workspaces. Record contains
	 * all fields found in <code>sys_workspaces</code>.
	 *
	 * @param	integer		System workspace ID. Currently <code>0</code> and <code>-1</code> are accepted.
	 * @return	array		Generated record (see <code>sys_workspaces</code> for structure)
	 */
	function workspaceList_createFakeWorkspaceRecord($uid)	{
		global	$BE_USER, $LANG;

		$record = array(
			'uid' => $uid,
			'pid' => 0,				// always 0!
			'tstamp' => 0,			// does not really matter
			'deleted' => 0,
			'title' => ($uid == 0 ? '['.$LANG->getLL('shortcut_onlineWS').']' : '['.$LANG->getLL('shortcut_offlineWS').']'),
			'description' => ($uid == 0 ? $LANG->getLL('shortcut_onlineWS') : $LANG->getLL('shortcut_offlineWS')),
			'adminusers' => '',
			'members' => '',
			'reviewers' => '',
			'db_mountpoints' => '',		// TODO get mount points from user profile
			'file_mountpoints' => '',	// TODO get mount points from user profile for live workspace only (uid == 0)
			'publish_time' => 0,
			'unpublish_time' => 0,
			'freeze' => 0,
			'live_edit' => ($uid == 0),
			'vtypes' => 0,
			'disable_autocreate' => 0,
			'swap_modes' => 0,
			'publish_access' => 0,
			'stagechg_notification' => 0
		);
		return $record;
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/mod/user/ws/index.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/mod/user/ws/index.php']);
}



// Make instance:
$SOBE = t3lib_div::makeInstance('SC_mod_user_ws_index');
$SOBE->execute();
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

?>