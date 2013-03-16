<?php
namespace TYPO3\CMS\Backend\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Script Class for the shortcut frame, bottom frame of the backend frameset
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class ShortcutFrameController {

	// Internal, static: GPvar
	/**
	 * @todo Define visibility
	 */
	public $modName;

	/**
	 * @todo Define visibility
	 */
	public $M_modName;

	/**
	 * @todo Define visibility
	 */
	public $URL;

	/**
	 * @todo Define visibility
	 */
	public $editSC;

	/**
	 * @todo Define visibility
	 */
	public $deleteCategory;

	/**
	 * @todo Define visibility
	 */
	public $editName;

	/**
	 * @todo Define visibility
	 */
	public $editGroup;

	/**
	 * @todo Define visibility
	 */
	public $whichItem;

	// Internal, static:
	/**
	 * Object for backend modules, load modules-object
	 *
	 * @var \TYPO3\CMS\Backend\Module\ModuleLoader
	 * @todo Define visibility
	 */
	public $loadModules;

	protected $isAjaxCall;

	/**
	 * Document template object
	 *
	 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
	 * @todo Define visibility
	 */
	public $doc;

	// Internal, dynamic:
	// Accumulation of output HTML (string)
	/**
	 * @todo Define visibility
	 */
	public $content;

	// Accumulation of table cells (array)
	/**
	 * @todo Define visibility
	 */
	public $lines;

	// Flag for defining whether we are editing
	/**
	 * @todo Define visibility
	 */
	public $editLoaded;

	// Can contain edit error message
	/**
	 * @todo Define visibility
	 */
	public $editError;

	// Set to the record path of the record being edited.
	/**
	 * @todo Define visibility
	 */
	public $editPath;

	// Holds the shortcut record when editing
	/**
	 * @todo Define visibility
	 */
	public $editSC_rec;

	// Page record to be edited
	/**
	 * @todo Define visibility
	 */
	public $theEditRec;

	// Page alias or id to be edited
	/**
	 * @todo Define visibility
	 */
	public $editPage;

	// Select options.
	/**
	 * @todo Define visibility
	 */
	public $selOpt;

	// Text to search for...
	/**
	 * @todo Define visibility
	 */
	public $searchFor;

	// Labels of all groups. If value is 1, the system will try to find a label in the locallang array.
	/**
	 * @todo Define visibility
	 */
	public $groupLabels = array();

	// Array with key 0/1 being table/uid of record to edit. Internally set.
	/**
	 * @todo Define visibility
	 */
	public $alternativeTableUid = array();

	/**
	 * Pre-initialization - setting input variables for storing shortcuts etc.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function preinit() {
		// Setting GPvars:
		$this->isAjaxCall = (bool) \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('ajax');
		$this->modName = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('modName');
		$this->M_modName = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('motherModName');
		$this->URL = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('URL');
		$this->editSC = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('editShortcut');
		$this->deleteCategory = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('deleteCategory');
		$this->editPage = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('editPage');
		$this->changeWorkspace = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('changeWorkspace');
		$this->changeWorkspacePreview = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('changeWorkspacePreview');
		$this->editName = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('editName');
		$this->editGroup = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('editGroup');
		$this->whichItem = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('whichItem');
		// Creating modules object
		$this->loadModules = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Module\\ModuleLoader');
		$this->loadModules->load($GLOBALS['TBE_MODULES']);
	}

	/**
	 * Adding shortcuts, editing shortcuts etc.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function preprocess() {
		// Default description
		$description = '';
		$url = urldecode($this->URL);
		$queryParts = parse_url($url);
		// Lookup the title of this page and use it as default description
		$page_id = $this->getLinkedPageId($url);
		if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($page_id)) {
			if (preg_match('/\\&edit\\[(.*)\\]\\[(.*)\\]=edit/', $url, $matches)) {
				// Edit record
				// TODO: Set something useful
				$description = '';
			} else {
				// Page listing
				$pageRow = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', $page_id);
				if (count($pageRow)) {
					// If $page_id is an integer, set the description to the title of that page
					$description = $pageRow['title'];
				}
			}
		} else {
			if (preg_match('/\\/$/', $page_id)) {
				// If $page_id is a string and ends with a slash, assume it is a fileadmin reference and set the description to the basename of that path
				$description = basename($page_id);
			}
		}
		// Adding a shortcut being set from another frame,
		// but only if it's a relative URL (i.e. scheme part is not defined)
		if ($this->modName && $this->URL && empty($queryParts['scheme'])) {
			$fields_values = array(
				'userid' => $GLOBALS['BE_USER']->user['uid'],
				'module_name' => $this->modName . '|' . $this->M_modName,
				'url' => $this->URL,
				'description' => $description,
				'sorting' => $GLOBALS['EXEC_TIME']
			);
			$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_be_shortcuts', $fields_values);
		}
		// Selection-clause for users - so users can deleted only their own shortcuts (except admins)
		$addUSERWhere = !$GLOBALS['BE_USER']->isAdmin() ? ' AND userid=' . intval($GLOBALS['BE_USER']->user['uid']) : '';
		// Deleting shortcuts:
		if (strcmp($this->deleteCategory, '')) {
			if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($this->deleteCategory)) {
				$GLOBALS['TYPO3_DB']->exec_DELETEquery('sys_be_shortcuts', 'sc_group=' . intval($this->deleteCategory) . $addUSERWhere);
			}
		}
		// If other changes in post-vars:
		if (is_array($_POST)) {
			// Saving:
			if (isset($_POST['_savedok_x']) || isset($_POST['_saveclosedok_x'])) {
				$fields_values = array(
					'description' => $this->editName,
					'sc_group' => intval($this->editGroup)
				);
				if ($fields_values['sc_group'] < 0 && !$GLOBALS['BE_USER']->isAdmin()) {
					$fields_values['sc_group'] = 0;
				}
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_be_shortcuts', 'uid=' . intval($this->whichItem) . $addUSERWhere, $fields_values);
			}
			// If save without close, keep the session going...
			if (isset($_POST['_savedok_x'])) {
				$this->editSC = $this->whichItem;
			}
			// Deleting a single shortcut ?
			if (isset($_POST['_deletedok_x'])) {
				$GLOBALS['TYPO3_DB']->exec_DELETEquery('sys_be_shortcuts', 'uid=' . intval($this->whichItem) . $addUSERWhere);
				// Just to have the checkbox set...
				if (!$this->editSC) {
					$this->editSC = -1;
				}
			}
		}
	}

	/**
	 * Initialize (page output)
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function init() {
		$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->form = '<form action="alt_shortcut.php" name="shForm" method="post">';
		$this->doc->divClass = 'typo3-shortcut';
		$this->doc->JScode .= $this->doc->wrapScriptTags('
			function jump(url,modName,mainModName) {	//
					// Clear information about which entry in nav. tree that might have been highlighted.
				top.fsMod.navFrameHighlightedID = new Array();
				if (top.content && top.content.nav_frame && top.content.nav_frame.refresh_nav) {
					top.content.nav_frame.refresh_nav();
				}

				top.nextLoadModuleUrl = url;
				top.goToModule(modName);
			}
			function editSh(uid) {	//
				window.location.href="alt_shortcut.php?editShortcut="+uid;
			}
			function submitEditPage(id) {	//
				window.location.href="alt_shortcut.php?editPage="+top.rawurlencodeAndRemoveSiteUrl(id);
			}
			function changeWorkspace(workspaceId) {	//
				window.location.href="alt_shortcut.php?changeWorkspace="+top.rawurlencodeAndRemoveSiteUrl(workspaceId);
			}
			function changeWorkspacePreview(newstate) {	//
				window.location.href="alt_shortcut.php?changeWorkspacePreview="+newstate;
			}
			function refreshShortcuts() {
				window.location.href = document.URL;
			}

			');
		$this->content .= $this->doc->startPage('Shortcut frame');
	}

	/**
	 * Main function, creating content in the frame
	 *
	 * @return 	void
	 * @todo Define visibility
	 */
	public function main() {
		// By default, 5 groups are set
		$this->groupLabels = array(
			1 => 1,
			2 => 1,
			3 => 1,
			4 => 1,
			5 => 1
		);
		$bookmarkGroups = $GLOBALS['BE_USER']->getTSConfigProp('options.bookmarkGroups');
		if (is_array($bookmarkGroups) && count($bookmarkGroups)) {
			foreach ($bookmarkGroups as $k => $v) {
				if (strcmp('', $v) && strcmp('0', $v)) {
					$this->groupLabels[$k] = (string) $v;
				} elseif ($GLOBALS['BE_USER']->isAdmin()) {
					unset($this->groupLabels[$k]);
				}
			}
		}
		// List of global groups that will be loaded. All global groups have negative IDs.
		$globalGroups = -100;
		// Group -100 is kind of superglobal and can't be changed.
		if (count($this->groupLabels)) {
			$globalGroups .= ',' . implode(',', array_keys($this->groupLabels));
			$globalGroups = str_replace(',', ',-', $globalGroups);
		}
		// Fetching shortcuts to display for this user:
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_be_shortcuts', '((userid=' . $GLOBALS['BE_USER']->user['uid'] . ' AND sc_group>=0) OR sc_group IN (' . $globalGroups . '))', '', 'sc_group,sorting');
		// Init vars:
		$this->lines = array();
		$this->linesPre = array();
		$this->editSC_rec = '';
		$this->selOpt = array();
		$formerGr = '';
		// Traverse shortcuts
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$mParts = explode('|', $row['module_name']);
			$row['module_name'] = $mParts[0];
			$row['M_module_name'] = $mParts[1];
			$mParts = explode('_', $row['M_module_name'] ? $row['M_module_name'] : $row['module_name']);
			$qParts = parse_url($row['url']);
			if (!$GLOBALS['BE_USER']->isAdmin()) {
				// Check for module access
				if (!isset($GLOBALS['LANG']->moduleLabels['tabs_images'][(implode('_', $mParts) . '_tab')])) {
					// Nice hack to check if the user has access to this module - otherwise the translation label would not have been loaded :-)
					continue;
				}
				$page_id = $this->getLinkedPageId($row['url']);
				if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($page_id)) {
					// Check for webmount access
					if (!$GLOBALS['BE_USER']->isInWebMount($page_id)) {
						continue;
					}
					// Check for record access
					$pageRow = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', $page_id);
					if (!$GLOBALS['BE_USER']->doesUserHaveAccess($pageRow, ($perms = 1))) {
						continue;
					}
				}
			}
			if ($this->editSC && $row['uid'] == $this->editSC) {
				$this->editSC_rec = $row;
			}
			$sc_group = $row['sc_group'];
			if ($sc_group && strcmp($formerGr, $sc_group)) {
				if ($sc_group != -100) {
					if ($this->groupLabels[abs($sc_group)] && strcmp('1', $this->groupLabels[abs($sc_group)])) {
						$label = $this->groupLabels[abs($sc_group)];
					} else {
						$label = $GLOBALS['LANG']->getLL('shortcut_group_' . abs($sc_group), 1);
						// Fallback label
						if (!$label) {
							$label = $GLOBALS['LANG']->getLL('shortcut_group', 1) . ' ' . abs($sc_group);
						}
					}
					if ($sc_group >= 0) {
						$onC = 'if (confirm(' . $GLOBALS['LANG']->JScharCode($GLOBALS['LANG']->getLL('bookmark_delAllInCat')) . ')){window.location.href=\'alt_shortcut.php?deleteCategory=' . $sc_group . '\';}return false;';
						$this->linesPre[] = '<td>&nbsp;</td><td class="bgColor5"><a href="#" onclick="' . htmlspecialchars($onC) . '" title="' . $GLOBALS['LANG']->getLL('bookmark_delAllInCat', 1) . '">' . $label . '</a></td>';
					} else {
						// Fallback label
						$label = $GLOBALS['LANG']->getLL('bookmark_global', 1) . ': ' . ($label ? $label : abs($sc_group));
						$this->lines[] = '<td>&nbsp;</td><td class="bgColor5">' . $label . '</td>';
					}
					unset($label);
				}
			}
			$bgColorClass = $row['uid'] == $this->editSC ? 'bgColor5' : ($row['sc_group'] < 0 ? 'bgColor6' : 'bgColor4');
			if ($row['description'] && $row['uid'] != $this->editSC) {
				$label = $row['description'];
			} else {
				$label = \TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs(rawurldecode($qParts['query']), 150);
			}
			$titleA = $this->itemLabel($label, $row['module_name'], $row['M_module_name']);
			$editSH = $row['sc_group'] >= 0 || $GLOBALS['BE_USER']->isAdmin() ? 'editSh(' . intval($row['uid']) . ');' : 'alert(\'' . $GLOBALS['LANG']->getLL('bookmark_onlyAdmin') . '\')';
			$jumpSC = 'jump(unescape(\'' . rawurlencode($row['url']) . '\'),\'' . implode('_', $mParts) . '\',\'' . $mParts[0] . '\');';
			$onC = 'if (document.shForm.editShortcut_check && document.shForm.editShortcut_check.checked){' . $editSH . '}else{' . $jumpSC . '}return false;';
			// user defined groups show up first
			if ($sc_group >= 0) {
				$this->linesPre[] = '<td class="' . $bgColorClass . '"><a href="#" onclick="' . htmlspecialchars($onC) . '"><img src="' . $this->getIcon($row['module_name']) . '" title="' . htmlspecialchars($titleA) . '" alt="" /></a></td>';
			} else {
				$this->lines[] = '<td class="' . $bgColorClass . '"><a href="#" onclick="' . htmlspecialchars($onC) . '"><img src="' . $this->getIcon($row['module_name']) . '" title="' . htmlspecialchars($titleA) . '" alt="" /></a></td>';
			}
			if (trim($row['description'])) {
				$kkey = strtolower(substr($row['description'], 0, 20)) . '_' . $row['uid'];
				$this->selOpt[$kkey] = '<option value="' . htmlspecialchars($jumpSC) . '">' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($row['description'], 50)) . '</option>';
			}
			$formerGr = $row['sc_group'];
		}
		ksort($this->selOpt);
		array_unshift($this->selOpt, '<option>[' . $GLOBALS['LANG']->getLL('bookmark_selSC', 1) . ']</option>');
		$this->editLoadedFunc();
		$this->editPageIdFunc();
		if (!$this->editLoaded && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('cms')) {
			$editIdCode = '<td nowrap="nowrap">' . $GLOBALS['LANG']->getLL('bookmark_editID', 1) . ': <input type="text" value="' . ($this->editError ? htmlspecialchars($this->editPage) : '') . '" name="editPage"' . $this->doc->formWidth(15) . ' onchange="submitEditPage(this.value);" />' . ($this->editError ? '&nbsp;<strong><span class="typo3-red">' . htmlspecialchars($this->editError) . '</span></strong>' : '') . (is_array($this->theEditRec) ? '&nbsp;<strong>' . $GLOBALS['LANG']->getLL('bookmark_loadEdit', 1) . ' \'' . \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle('pages', $this->theEditRec, TRUE) . '\'</strong> (' . htmlspecialchars($this->editPath) . ')' : '') . ($this->searchFor ? '&nbsp;' . $GLOBALS['LANG']->getLL('bookmark_searchFor', 1) . ' <strong>\'' . htmlspecialchars($this->searchFor) . '\'</strong>' : '') . '</td>';
		} else {
			$editIdCode = '';
		}
		// Adding CSH:
		$editIdCode .= '<td>&nbsp;' . \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('xMOD_csh_corebe', 'bookmarks', $GLOBALS['BACK_PATH'], '', TRUE) . '</td>';
		// Compile it all:
		$this->content .= '

			<table border="0" cellpadding="0" cellspacing="0" width="99%">
				<tr>
					<td>
						<!--
							Shortcut Display Table:
						-->
						<table border="0" cellpadding="0" cellspacing="2" id="typo3-shortcuts">
							<tr>
							';
		if ($GLOBALS['BE_USER']->getTSConfigVal('options.enableBookmarks')) {
			$this->content .= implode('
								', $this->lines);
		}
		$this->content .= $editIdCode . '
							</tr>
						</table>
					</td>
					<td align="right">';
		if ($this->hasWorkspaceAccess()) {
			$this->content .= $this->workspaceSelector() . \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('xMOD_csh_corebe', 'workspaceSelector', $GLOBALS['BACK_PATH'], '', TRUE);
		}
		$this->content .= '
					</td>
				</tr>
			</table>
			';
		// Launch Edit page:
		if ($this->theEditRec['uid']) {
			$this->content .= $this->doc->wrapScriptTags('top.loadEditId(' . $this->theEditRec['uid'] . ');');
		}
		// Load alternative table/uid into editing form.
		if (count($this->alternativeTableUid) == 2 && isset($GLOBALS['TCA'][$this->alternativeTableUid[0]]) && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($this->alternativeTableUid[1])) {
			$JSaction = \TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick('&edit[' . $this->alternativeTableUid[0] . '][' . $this->alternativeTableUid[1] . ']=edit', '', 'dummy.php');
			$this->content .= $this->doc->wrapScriptTags('function editArbitraryElement() { top.content.' . $JSaction . '; } editArbitraryElement();');
		}
		// Load search for something.
		if ($this->searchFor) {
			$urlParameters = array();
			$urlParameters['id'] = intval($GLOBALS['WEBMOUNTS'][0]);
			$urlParameters['search_field'] = $this->searchFor;
			$urlParameters['search_levels'] = 4;
			$this->content .= $this->doc->wrapScriptTags('jump(unescape("' . rawurlencode(\TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('web_list', $urlParameters, '')) . '"), "web_list", "web");');
		}
	}

	/**
	 * Creates lines for the editing form.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function editLoadedFunc() {
		$this->editLoaded = 0;
		// sc_group numbers below 0 requires admin to edit those. sc_group
		// numbers above zero must always be owned by the user himself.
		if (is_array($this->editSC_rec) && ($this->editSC_rec['sc_group'] >= 0 || $GLOBALS['BE_USER']->isAdmin())) {
			$this->editLoaded = 1;
			$opt = array();
			$opt[] = '<option value="0"></option>';
			foreach ($this->groupLabels as $k => $v) {
				if ($v && strcmp('1', $v)) {
					$label = $v;
				} else {
					$label = $GLOBALS['LANG']->getLL('bookmark_group_' . $k, 1);
					// Fallback label
					if (!$label) {
						$label = $GLOBALS['LANG']->getLL('bookmark_group', 1) . ' ' . $k;
					}
				}
				$opt[] = '<option value="' . $k . '"' . (!strcmp($this->editSC_rec['sc_group'], $k) ? ' selected="selected"' : '') . '>' . $label . '</option>';
			}
			if ($GLOBALS['BE_USER']->isAdmin()) {
				foreach ($this->groupLabels as $k => $v) {
					if ($v && strcmp('1', $v)) {
						$label = $v;
					} else {
						$label = $GLOBALS['LANG']->getLL('bookmark_group_' . $k, 1);
						// Fallback label
						if (!$label) {
							$label = $GLOBALS['LANG']->getLL('bookmark_group', 1) . ' ' . $k;
						}
					}
					// Add a prefix for global groups
					$label = $GLOBALS['LANG']->getLL('bookmark_global', 1) . ': ' . $label;
					$opt[] = '<option value="-' . $k . '"' . (!strcmp($this->editSC_rec['sc_group'], ('-' . $k)) ? ' selected="selected"' : '') . '>' . $label . '</option>';
				}
				$opt[] = '<option value="-100"' . (!strcmp($this->editSC_rec['sc_group'], '-100') ? ' selected="selected"' : '') . '>' . $GLOBALS['LANG']->getLL('bookmark_global', 1) . ': ' . $GLOBALS['LANG']->getLL('bookmark_all', 1) . '</option>';
			}
			// border="0" hspace="2" width="21" height="16" - not XHTML compliant in <input type="image" ...>
			$manageForm = '

				<!--
					Shortcut Editing Form:
				-->
				<table border="0" cellpadding="0" cellspacing="0" id="typo3-shortcuts-editing">
					<tr>
						<td>&nbsp;&nbsp;</td>
						<td><input type="image" class="c-inputButton" name="_savedok"' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->doc->backPath, 'gfx/savedok.gif', '') . ' title="' . $GLOBALS['LANG']->getLL('shortcut_save', 1) . '" /></td>
						<td><input type="image" class="c-inputButton" name="_saveclosedok"' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->doc->backPath, 'gfx/saveandclosedok.gif', '') . ' title="' . $GLOBALS['LANG']->getLL('bookmark_saveClose', 1) . '" /></td>
						<td><input type="image" class="c-inputButton" name="_closedok"' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->doc->backPath, 'gfx/closedok.gif', '') . ' title="' . $GLOBALS['LANG']->getLL('bookmark_close', 1) . '" /></td>
						<td><input type="image" class="c-inputButton" name="_deletedok"' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->doc->backPath, 'gfx/deletedok.gif', '') . ' title="' . $GLOBALS['LANG']->getLL('bookmark_delete', 1) . '" /></td>
						<td><input name="editName" type="text" value="' . htmlspecialchars($this->editSC_rec['description']) . '"' . $this->doc->formWidth(15) . ' /></td>
						<td><select name="editGroup">' . implode('', $opt) . '</select></td>
					</tr>
				</table>
				<input type="hidden" name="whichItem" value="' . $this->editSC_rec['uid'] . '" />

				';
		} else {
			$manageForm = '';
		}
		if (!$this->editLoaded && count($this->selOpt) > 1) {
			$this->lines[] = '<td>&nbsp;</td>';
			$this->lines[] = '<td><select name="_selSC" onchange="eval(this.options[this.selectedIndex].value);this.selectedIndex=0;">' . implode('', $this->selOpt) . '</select></td>';
		}
		// $this->linesPre contains elements with sc_group>=0
		$this->lines = array_merge($this->linesPre, $this->lines);
		if (count($this->lines)) {
			if (!$GLOBALS['BE_USER']->getTSConfigVal('options.mayNotCreateEditBookmarks')) {
				$this->lines = array_merge(array(
					'<td><input type="checkbox" id="editShortcut_check" name="editShortcut_check" value="1"' . ($this->editSC ? ' checked="checked"' : '') . ' />' . ' <label for="editShortcut_check">' . $GLOBALS['LANG']->getLL('bookmark_edit', 1) . '</label>&nbsp;</td>'
				), $this->lines);
				$this->lines[] = '<td>' . $manageForm . '</td>';
			}
			$this->lines[] = '<td><img src="clear.gif" width="10" height="1" alt="" /></td>';
		}
	}

	/**
	 * If "editPage" value is sent to script and it points to an accessible page, the internal var $this->theEditRec is set to the page record which should be loaded.
	 * Returns void
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function editPageIdFunc() {
		if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('cms')) {
			return;
		}
		// EDIT page:
		$this->editPage = trim($GLOBALS['LANG']->csConvObj->conv_case($GLOBALS['LANG']->charSet, $this->editPage, 'toLower'));
		$this->editError = '';
		$this->theEditRec = '';
		$this->searchFor = '';
		if ($this->editPage) {
			// First, test alternative value consisting of [table]:[uid] and if not found, proceed with traditional page ID resolve:
			$this->alternativeTableUid = explode(':', $this->editPage);
			// We restrict it to admins only just because I'm not really sure if alt_doc.php properly
			// checks permissions of passed records for editing. If alt_doc.php does that, then we can remove this.
			if (!(count($this->alternativeTableUid) == 2 && $GLOBALS['BE_USER']->isAdmin())) {
				$where = ' AND (' . $GLOBALS['BE_USER']->getPagePermsClause(2) . ' OR ' . $GLOBALS['BE_USER']->getPagePermsClause(16) . ')';
				if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($this->editPage)) {
					$this->theEditRec = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL('pages', $this->editPage, '*', $where);
				} else {
					$records = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordsByField('pages', 'alias', $this->editPage, $where);
					if (is_array($records)) {
						$this->theEditRec = reset($records);
						\TYPO3\CMS\Backend\Utility\BackendUtility::workspaceOL('pages', $this->theEditRec);
					}
				}
				if (!is_array($this->theEditRec)) {
					unset($this->theEditRec);
					$this->searchFor = $this->editPage;
				} elseif (!$GLOBALS['BE_USER']->isInWebMount($this->theEditRec['uid'])) {
					unset($this->theEditRec);
					$this->editError = $GLOBALS['LANG']->getLL('bookmark_notEditable');
				} else {
					// Visual path set:
					$perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
					$this->editPath = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordPath($this->theEditRec['pid'], $perms_clause, 30);
					if (!$GLOBALS['BE_USER']->getTSConfigVal('options.bookmark_onEditId_dontSetPageTree')) {
						$bookmarkKeepExpanded = $GLOBALS['BE_USER']->getTSConfigVal('options.bookmark_onEditId_keepExistingExpanded');
						// Expanding page tree:
						\TYPO3\CMS\Backend\Utility\BackendUtility::openPageTree($this->theEditRec['pid'], !$bookmarkKeepExpanded);
					}
				}
			}
		}
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function printContent() {
		$content = '';
		$this->content .= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
		if ($this->editPage && $this->isAjaxCall) {
			$data = array();
			// edit page
			if ($this->theEditRec['uid']) {
				$data['type'] = 'page';
				$data['editRecord'] = $this->theEditRec['uid'];
			}
			// edit alternative table/uid
			if (count($this->alternativeTableUid) == 2 && isset($GLOBALS['TCA'][$this->alternativeTableUid[0]]) && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($this->alternativeTableUid[1])) {
				$data['type'] = 'alternative';
				$data['alternativeTable'] = $this->alternativeTableUid[0];
				$data['alternativeUid'] = $this->alternativeTableUid[1];
			}
			// search for something else
			if ($this->searchFor) {
				$data['type'] = 'search';
				$data['firstMountPoint'] = intval($GLOBALS['WEBMOUNTS'][0]);
				$data['searchFor'] = $this->searchFor;
			}
			$content = json_encode($data);
			header('Content-type: application/json; charset=utf-8');
			header('X-JSON: ' . $content);
		} else {
			$content = $this->content;
		}
		echo $content;
	}

	/***************************
	 *
	 * WORKSPACE FUNCTIONS:
	 *
	 ***************************/
	/**
	 * Create selector for workspaces and change workspace if command is given to do that.
	 *
	 * @return string HTML
	 * @todo Define visibility
	 */
	public function workspaceSelector() {
		// Changing workspace and if so, reloading entire backend:
		if (strlen($this->changeWorkspace)) {
			$GLOBALS['BE_USER']->setWorkspace($this->changeWorkspace);
			return $this->doc->wrapScriptTags('top.location.href="' . \TYPO3\CMS\Backend\Utility\BackendUtility::getBackendScript() . '";');
		}
		// Changing workspace and if so, reloading entire backend:
		if (strlen($this->changeWorkspacePreview)) {
			$GLOBALS['BE_USER']->setWorkspacePreview($this->changeWorkspacePreview);
		}
		// Create options array:
		$options = array();
		if ($GLOBALS['BE_USER']->checkWorkspace(array('uid' => 0))) {
			$options[0] = '[' . $GLOBALS['LANG']->getLL('bookmark_onlineWS') . ']';
		}
		if ($GLOBALS['BE_USER']->checkWorkspace(array('uid' => -1))) {
			$options[-1] = '[' . $GLOBALS['LANG']->getLL('bookmark_offlineWS') . ']';
		}
		// Add custom workspaces (selecting all, filtering by BE_USER check):
		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('workspaces')) {
			$workspaces = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,title,adminusers,members,reviewers', 'sys_workspace', 'pid=0' . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('sys_workspace'), '', 'title');
			if (count($workspaces)) {
				foreach ($workspaces as $rec) {
					if ($GLOBALS['BE_USER']->checkWorkspace($rec)) {
						$options[$rec['uid']] = $rec['uid'] . ': ' . $rec['title'];
					}
				}
			}
		}
		// Build selector box:
		if (count($options)) {
			foreach ($options as $value => $label) {
				$selected = (int) $GLOBALS['BE_USER']->workspace === $value ? ' selected="selected"' : '';
				$options[$value] = '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
			}
		} else {
			$options[] = '<option value="-99">' . $GLOBALS['LANG']->getLL('bookmark_noWSfound', 1) . '</option>';
		}
		$selector = '';
		// Preview:
		if ($GLOBALS['BE_USER']->workspace !== 0) {
			$selector .= '<label for="workspacePreview">Frontend Preview:</label> <input type="checkbox" name="workspacePreview" id="workspacePreview" onclick="changeWorkspacePreview(' . ($GLOBALS['BE_USER']->user['workspace_preview'] ? 0 : 1) . ')"; ' . ($GLOBALS['BE_USER']->user['workspace_preview'] ? 'checked="checked"' : '') . '/>&nbsp;';
		}
		$selector .= '<a href="mod/user/ws/index.php" target="content">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('sys_workspace', array()) . '</a>';
		if (count($options) > 1) {
			$selector .= '<select name="_workspaceSelector" onchange="changeWorkspace(this.options[this.selectedIndex].value);">' . implode('', $options) . '</select>';
		}
		return $selector;
	}

	/***************************
	 *
	 * OTHER FUNCTIONS:
	 *
	 ***************************/
	/**
	 * Returns relative filename for icon.
	 *
	 * @param string $Ifilename Absolute filename of the icon
	 * @param string $backPath Backpath string to prepend the icon after made relative
	 * @return void
	 * @todo Define visibility
	 */
	public function mIconFilename($Ifilename, $backPath) {
		// Change icon of fileadmin references - otherwise it doesn't differ with Web->List
		$Ifilename = str_replace('mod/file/list/list.gif', 'mod/file/file.gif', $Ifilename);
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($Ifilename)) {
			$Ifilename = '../' . substr($Ifilename, strlen(PATH_site));
		}
		return $backPath . $Ifilename;
	}

	/**
	 * Returns icon for shortcut display
	 *
	 * @param string $modName Backend module name
	 * @return string Icon file name
	 * @todo Define visibility
	 */
	public function getIcon($modName) {
		if ($GLOBALS['LANG']->moduleLabels['tabs_images'][$modName . '_tab']) {
			$icon = $this->mIconFilename($GLOBALS['LANG']->moduleLabels['tabs_images'][$modName . '_tab'], '');
		} elseif ($modName == 'xMOD_alt_doc.php') {
			$icon = 'gfx/edit2.gif';
		} elseif ($modName == 'xMOD_file_edit.php') {
			$icon = 'gfx/edit_file.gif';
		} elseif ($modName == 'xMOD_wizard_rte.php') {
			$icon = 'gfx/edit_rtewiz.gif';
		} else {
			$icon = 'gfx/dummy_module.gif';
		}
		return $icon;
	}

	/**
	 * Returns title-label for icon
	 *
	 * @param string $inlabel In-label
	 * @param string $modName Backend module name (key)
	 * @param string $M_modName Backend module label (user defined?)
	 * @return string Label for the shortcut item
	 * @todo Define visibility
	 */
	public function itemLabel($inlabel, $modName, $M_modName = '') {
		if (substr($modName, 0, 5) == 'xMOD_') {
			$label = substr($modName, 5);
		} else {
			$split = explode('_', $modName);
			$label = $GLOBALS['LANG']->moduleLabels['tabs'][$split[0] . '_tab'];
			if (count($split) > 1) {
				$label .= '>' . $GLOBALS['LANG']->moduleLabels['tabs'][($modName . '_tab')];
			}
		}
		if ($M_modName) {
			$label .= ' (' . $M_modName . ')';
		}
		$label .= ': ' . $inlabel;
		return $label;
	}

	/**
	 * Return the ID of the page in the URL if found.
	 *
	 * @param string $url The URL of the current shortcut link
	 * @return string If a page ID was found, it is returned. Otherwise: 0
	 * @todo Define visibility
	 */
	public function getLinkedPageId($url) {
		return preg_replace('/.*[\\?&]id=([^&]+).*/', '$1', $url);
	}

	/**
	 * Checks if user has access to Workspace module.
	 *
	 * @return boolean Returns TRUE if user has access to workspace module.
	 * @todo Define visibility
	 */
	public function hasWorkspaceAccess() {
		$MCONF = array();
		include 'mod/user/ws/conf.php';
		return $GLOBALS['BE_USER']->modAccess(array('name' => 'user', 'access' => 'user,group'), FALSE) && $GLOBALS['BE_USER']->modAccess($MCONF, FALSE);
	}

}


?>