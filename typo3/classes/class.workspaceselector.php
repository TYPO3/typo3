<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2008 Ingo Renner <ingo@typo3.org>
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
 * class to render the workspace selector
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage core
 */
class WorkspaceSelector implements backend_toolbarItem {

	protected $changeWorkspace;
	protected $changeWorkspacePreview;

	/**
	 * reference back to the backend object
	 *
	 * @var	TYPO3backend
	 */
	protected $backendReference;

	/**
	 * constructor
	 *
	 * @param	TYPO3backend	TYPO3 backend object reference
	 */
	public function __construct(TYPO3backend &$backendReference = null) {
		$this->backendReference       = $backendReference;

		$this->changeWorkspace        = t3lib_div::_GP('changeWorkspace');
		$this->changeWorkspacePreview = t3lib_div::_GP('changeWorkspacePreview');
	}

	/**
	 * checks whether the user has access to this toolbar item
	 *
	 * @see		typo3/alt_shortcut.php
	 * @return  boolean  true if user has access, false if not
	 */
	public function checkAccess() {
		$MCONF = array();
		include('mod/user/ws/conf.php');

		return ($GLOBALS['BE_USER']->modAccess(array('name' => 'user', 'access' => 'user,group'), false) && $GLOBALS['BE_USER']->modAccess($MCONF, false));
	}

	/**
	 * changes workspace if needed and then reloads the backend
	 *
	 * @return	void
	 */
	public function changeWorkspace() {
		$reloadBackend = false;

			// Changing workspace and if so, reloading entire backend:
		if (strlen($this->changeWorkspace)) {
			$GLOBALS['BE_USER']->setWorkspace($this->changeWorkspace);
			$reloadBackend = true;
		}

			// Changing workspace preview and if so, reloading entire backend:
		if (strlen($this->changeWorkspacePreview)) {
			$GLOBALS['BE_USER']->setWorkspacePreview($this->changeWorkspacePreview);
			$reloadBackend = true;
		}

		if($reloadBackend) {
			$this->backendReference->addJavascript(
				'top.location.href=\'backend.php\';'
			);
		}
	}

	/**
	 * retrieves the available workspaces from the database and checks whether
	 * they're available to the current BE user
	 *
	 * @return	array	array of worspaces available to the current user
	 */
	protected function getAvailableWorkspaces() {
		$availableWorkspaces = array();

			// add default workspaces
		if($GLOBALS['BE_USER']->checkWorkspace(array('uid' => 0))) {
			$availableWorkspaces[0] = '['.$GLOBALS['LANG']->getLL('shortcut_onlineWS').']';
		}
		if ($GLOBALS['BE_USER']->checkWorkspace(array('uid' => -1))) {
			$availableWorkspaces[-1] = '['.$GLOBALS['LANG']->getLL('shortcut_offlineWS').']';
		}

			// add custom workspaces (selecting all, filtering by BE_USER check):
		$customWorkspaces = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'uid, title, adminusers, members, reviewers',
			'sys_workspace',
			'pid = 0'.t3lib_BEfunc::deleteClause('sys_workspace'),
			'',
			'title'
		);
		if(count($customWorkspaces)) {
			foreach($customWorkspaces as $workspace) {
				if($GLOBALS['BE_USER']->checkWorkspace($workspace)) {
					$availableWorkspaces[$workspace['uid']] = $workspace['uid'].': '.$workspace['title'];
				}
			}
		}

		return $availableWorkspaces;
	}

	/**
	 * Creates the selector for workspaces
	 *
	 * @return	string		workspace selector as HTML select
	 */
	public function render() {
		$this->addJavascriptToBackend();
		$this->changeWorkspace();

		$options             = array();
		$workspaceSelector   = '<span class="toolbar-item">';
		$availableWorkspaces = $this->getAvailableWorkspaces();

			// build selector box options
		if(count($availableWorkspaces)) {
			foreach($availableWorkspaces as $workspaceId => $label) {

				$selected = '';
				if((int) $GLOBALS['BE_USER']->workspace === $workspaceId) {
					$selected = ' selected="selected"';
				}

				$options[$workspaceId] = '<option value="'.htmlspecialchars($workspaceId).'"'.$selected.'>'.htmlspecialchars($label).'</option>';
			}
		} else {
			$options[] = '<option value="-99">'.$GLOBALS['LANG']->getLL('shortcut_noWSfound',1).'</option>';
		}

			// build selector box
		if(count($options) > 1) {
			$workspaceSelector .=
				'<select name="_workspaceSelector" onchange="changeWorkspace(this.options[this.selectedIndex].value);">'
				.implode("\n", $options)
				.'</select>';
		}

			// preview
		if($GLOBALS['BE_USER']->workspace !== 0) {
			$workspaceSelector.= ' <label for="workspacePreview">Frontend Preview:</label> <input type="checkbox" name="workspacePreview" id="workspacePreview" onclick="changeWorkspacePreview('.($GLOBALS['BE_USER']->user['workspace_preview'] ? 0 : 1).')"; '.($GLOBALS['BE_USER']->user['workspace_preview'] ? 'checked="checked"' : '').'/>';
		}

		$workspaceSelector.= ' <a href="mod/user/ws/index.php" target="content">'.
					t3lib_iconWorks::getIconImage(
						'sys_workspace',
						array(),
						$this->doc->backPath,
						'align="top"'
					).'</a>';

		return $workspaceSelector.'</span>';
	}

	/**
	 * adds the necessary JavaScript to the backend
	 *
	 * @return	void
	 */
	protected function addJavascriptToBackend() {
		$this->backendReference->addJavascriptFile('js/workspaces.js');
	}

	/**
	 * returns additional attributes for the list item in the toolbar
	 *
	 * @return	string		list item HTML attibutes
	 */
	public function getAdditionalAttributes() {
		return ' id="workspace-selector"';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/classes/class.workspaceselector.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/classes/class.workspaceselector.php']);
}

?>
