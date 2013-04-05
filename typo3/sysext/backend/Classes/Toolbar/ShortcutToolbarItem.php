<?php
namespace TYPO3\CMS\Backend\Toolbar;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2013 Ingo Renner <ingo@typo3.org>
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
 * Class to render the shortcut menu
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class ShortcutToolbarItem implements \TYPO3\CMS\Backend\Toolbar\ToolbarItemHookInterface {

	protected $shortcutGroups;

	/**
	 * All available shortcuts
	 *
	 * @var array
	 */
	protected $shortcuts;

	/**
	 * Labels of all groups.
	 * If value is 1, the system will try to find a label in the locallang array.
	 *
	 * @var array
	 */
	protected $groupLabels;

	/**
	 * Reference back to the backend object
	 *
	 * @var 	TYPO3backend
	 */
	protected $backendReference;

	/**
	 * Constructor
	 *
	 * @param \TYPO3\CMS\Backend\Controller\BackendController $backendReference TYPO3 backend object reference
	 */
	public function __construct(\TYPO3\CMS\Backend\Controller\BackendController &$backendReference = NULL) {
		$this->backendReference = $backendReference;
		$this->shortcuts = array();
		// By default, 5 groups are set
		$this->shortcutGroups = array(
			1 => '1',
			2 => '1',
			3 => '1',
			4 => '1',
			5 => '1'
		);
		$this->shortcutGroups = $this->initShortcutGroups();
		$this->shortcuts = $this->initShortcuts();
	}

	/**
	 * Checks whether the user has access to this toolbar item
	 *
	 * @return boolean TRUE if user has access, FALSE if not
	 */
	public function checkAccess() {
		return (bool) $GLOBALS['BE_USER']->getTSConfigVal('options.enableBookmarks');
	}

	/**
	 * Creates the shortcut menu (default renderer)
	 *
	 * @return string Workspace selector as HTML select
	 */
	public function render() {
		$title = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:toolbarItems.bookmarks', TRUE);
		$this->addJavascriptToBackend();
		$shortcutMenu = array();
		$shortcutMenu[] = '<a href="#" class="toolbar-item">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('apps-toolbar-menu-shortcut', array('title' => $title)) . '</a>';
		$shortcutMenu[] = '<div class="toolbar-item-menu" style="display: none;">';
		$shortcutMenu[] = $this->renderMenu();
		$shortcutMenu[] = '</div>';
		return implode(LF, $shortcutMenu);
	}

	/**
	 * Renders the pure contents of the menu
	 *
	 * @return string The menu's content
	 */
	public function renderMenu() {
		$shortcutGroup = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:toolbarItems.bookmarksGroup', TRUE);
		$shortcutEdit = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:toolbarItems.bookmarksEdit', TRUE);
		$shortcutDelete = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:toolbarItems.bookmarksDelete', TRUE);
		$groupIcon = '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->backPath, 'gfx/i/sysf.gif', 'width="18" height="16"') . ' title="' . $shortcutGroup . '" alt="' . $shortcutGroup . '" />';
		$editIcon = '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->backPath, 'gfx/edit2.gif', 'width="11" height="12"') . ' title="' . $shortcutEdit . '" alt="' . $shortcutEdit . '"';
		$deleteIcon = '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->backPath, 'gfx/garbage.gif', 'width="11" height="12"') . ' title="' . $shortcutDelete . '" alt="' . $shortcutDelete . '" />';
		$shortcutMenu[] = '<table border="0" cellspacing="0" cellpadding="0" class="shortcut-list">';
		// Render shortcuts with no group (group id = 0) first
		$noGroupShortcuts = $this->getShortcutsByGroup(0);
		foreach ($noGroupShortcuts as $shortcut) {
			$shortcutMenu[] = '
			<tr id="shortcut-' . $shortcut['raw']['uid'] . '" class="shortcut">
				<td class="shortcut-icon">' . $shortcut['icon'] . '</td>
				<td class="shortcut-label">
					<a id="shortcut-label-' . $shortcut['raw']['uid'] . '" href="#" onclick="' . $shortcut['action'] . '; return false;">' . htmlspecialchars($shortcut['label']) . '</a>
				</td>
				<td class="shortcut-edit">' . $editIcon . ' id="shortcut-edit-' . $shortcut['raw']['uid'] . '" /></td>
				<td class="shortcut-delete">' . $deleteIcon . '</td>
			</tr>';
		}
		// Now render groups and the contained shortcuts
		$groups = $this->getGroupsFromShortcuts();
		krsort($groups, SORT_NUMERIC);
		foreach ($groups as $groupId => $groupLabel) {
			if ($groupId != 0) {
				$shortcutGroup = '
				<tr class="shortcut-group" id="shortcut-group-' . $groupId . '">
					<td class="shortcut-group-icon">' . $groupIcon . '</td>
					<td class="shortcut-group-label">' . $groupLabel . '</td>
					<td colspan="2">&nbsp;</td>
				</tr>';
				$shortcuts = $this->getShortcutsByGroup($groupId);
				$i = 0;
				foreach ($shortcuts as $shortcut) {
					$i++;
					$firstRow = '';
					if ($i == 1) {
						$firstRow = ' first-row';
					}
					$shortcutGroup .= '
					<tr id="shortcut-' . $shortcut['raw']['uid'] . '" class="shortcut' . $firstRow . '">
						<td class="shortcut-icon">' . $shortcut['icon'] . '</td>
						<td class="shortcut-label">
							<a id="shortcut-label-' . $shortcut['raw']['uid'] . '" href="#" onclick="' . $shortcut['action'] . '; return false;">' . htmlspecialchars($shortcut['label']) . '</a>
						</td>
						<td class="shortcut-edit">' . $editIcon . ' id="shortcut-edit-' . $shortcut['raw']['uid'] . '" /></td>
						<td class="shortcut-delete">' . $deleteIcon . '</td>
					</tr>';
				}
				$shortcutMenu[] = $shortcutGroup;
			}
		}
		if (count($shortcutMenu) == 1) {
			// No shortcuts added yet, show a small help message how to add shortcuts
			$title = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:toolbarItems.bookmarks', TRUE);
			$icon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-system-shortcut-new', array(
				'title' => $title
			));
			$label = str_replace('%icon%', $icon, $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_misc.xlf:bookmarkDescription'));
			$shortcutMenu[] = '<tr><td style="padding:1px 2px; color: #838383;">' . $label . '</td></tr>';
		}
		$shortcutMenu[] = '</table>';
		$compiledShortcutMenu = implode(LF, $shortcutMenu);
		return $compiledShortcutMenu;
	}

	/**
	 * Renders the menu so that it can be returned as response to an AJAX call
	 *
	 * @param array $params Array of parameters from the AJAX interface, currently unused
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj Object of type TYPO3AJAX
	 * @return void
	 */
	public function renderAjax($params = array(), \TYPO3\CMS\Core\Http\AjaxRequestHandler &$ajaxObj = NULL) {
		$menuContent = $this->renderMenu();
		$ajaxObj->addContent('shortcutMenu', $menuContent);
	}

	/**
	 * Adds the necessary JavaScript to the backend
	 *
	 * @return void
	 */
	protected function addJavascriptToBackend() {
		$this->backendReference->addJavascriptFile('js/shortcutmenu.js');
	}

	/**
	 * Returns additional attributes for the list item in the toolbar
	 *
	 * @return string List item HTML attibutes
	 */
	public function getAdditionalAttributes() {
		return ' id="shortcut-menu"';
	}

	/**
	 * Retrieves the shortcuts for the current user
	 *
	 * @return array Array of shortcuts
	 */
	protected function initShortcuts() {
		$shortcuts = array();
		$globalGroups = $this->getGlobalShortcutGroups();
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_be_shortcuts', '((userid = ' . $GLOBALS['BE_USER']->user['uid'] . ' AND sc_group>=0) OR sc_group IN (' . implode(',', array_keys($globalGroups)) . '))', '', 'sc_group,sorting');
		// Traverse shortcuts
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$shortcut = array('raw' => $row);
			$moduleParts = explode('|', $row['module_name']);
			$row['module_name'] = $moduleParts[0];
			$row['M_module_name'] = $moduleParts[1];
			$moduleParts = explode('_', $row['M_module_name'] ? $row['M_module_name'] : $row['module_name']);
			$queryParts = parse_url($row['url']);
			$queryParameters = \TYPO3\CMS\Core\Utility\GeneralUtility::explodeUrl2Array($queryParts['query'], 1);
			if ($row['module_name'] == 'xMOD_alt_doc.php' && is_array($queryParameters['edit'])) {
				$shortcut['table'] = key($queryParameters['edit']);
				$shortcut['recordid'] = key($queryParameters['edit'][$shortcut['table']]);
				if ($queryParameters['edit'][$shortcut['table']][$shortcut['recordid']] == 'edit') {
					$shortcut['type'] = 'edit';
				} elseif ($queryParameters['edit'][$shortcut['table']][$shortcut['recordid']] == 'new') {
					$shortcut['type'] = 'new';
				}
				if (substr($shortcut['recordid'], -1) == ',') {
					$shortcut['recordid'] = substr($shortcut['recordid'], 0, -1);
				}
			} else {
				$shortcut['type'] = 'other';
			}
			// Check for module access
			$pageId = $this->getLinkedPageId($row['url']);
			if (!$GLOBALS['BE_USER']->isAdmin()) {
				if (!isset($GLOBALS['LANG']->moduleLabels['tabs_images'][(implode('_', $moduleParts) . '_tab')])) {
					// Nice hack to check if the user has access to this module
					// - otherwise the translation label would not have been loaded :-)
					continue;
				}
				if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($pageId)) {
					// Check for webmount access
					if (!$GLOBALS['BE_USER']->isInWebMount($pageId)) {
						continue;
					}
					// Check for record access
					$pageRow = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', $pageId);
					if (!$GLOBALS['BE_USER']->doesUserHaveAccess($pageRow, ($perms = 1))) {
						continue;
					}
				}
			}
			$shortcutGroup = $row['sc_group'];
			if ($shortcutGroup && strcmp($lastGroup, $shortcutGroup) && $shortcutGroup != -100) {
				$shortcut['groupLabel'] = $this->getShortcutGroupLabel($shortcutGroup);
			}
			if ($row['description']) {
				$shortcut['label'] = $row['description'];
			} else {
				$shortcut['label'] = \TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs(rawurldecode($queryParts['query']), 150);
			}
			$shortcut['group'] = $shortcutGroup;
			$shortcut['icon'] = $this->getShortcutIcon($row, $shortcut);
			$shortcut['iconTitle'] = $this->getShortcutIconTitle($shortcutLabel, $row['module_name'], $row['M_module_name']);
			$shortcut['action'] = 'jump(unescape(\'' . rawurlencode($row['url']) . '\'),\'' . implode('_', $moduleParts) . '\',\'' . $moduleParts[0] . '\', ' . intval($pageId) . ');';
			$lastGroup = $row['sc_group'];
			$shortcuts[] = $shortcut;
		}
		return $shortcuts;
	}

	/**
	 * Gets shortcuts for a specific group
	 *
	 * @param integer $groupId Group Id
	 * @return array Array of shortcuts that matched the group
	 */
	protected function getShortcutsByGroup($groupId) {
		$shortcuts = array();
		foreach ($this->shortcuts as $shortcut) {
			if ($shortcut['group'] == $groupId) {
				$shortcuts[] = $shortcut;
			}
		}
		return $shortcuts;
	}

	/**
	 * Gets a shortcut by its uid
	 *
	 * @param integer $shortcutId Shortcut id to get the complete shortcut for
	 * @return mixed An array containing the shortcut's data on success or FALSE on failure
	 */
	protected function getShortcutById($shortcutId) {
		$returnShortcut = FALSE;
		foreach ($this->shortcuts as $shortcut) {
			if ($shortcut['raw']['uid'] == (int) $shortcutId) {
				$returnShortcut = $shortcut;
				continue;
			}
		}
		return $returnShortcut;
	}

	/**
	 * Gets the available shortcut groups from default gropups, user TSConfig,
	 * and global groups
	 *
	 * @param array $params Array of parameters from the AJAX interface, currently unused
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj Object of type TYPO3AJAX
	 * @return array
	 */
	protected function initShortcutGroups($params = array(), \TYPO3\CMS\Core\Http\AjaxRequestHandler &$ajaxObj = NULL) {
		// Groups from TSConfig
		$bookmarkGroups = $GLOBALS['BE_USER']->getTSConfigProp('options.bookmarkGroups');
		if (is_array($bookmarkGroups) && count($bookmarkGroups)) {
			foreach ($bookmarkGroups as $groupId => $label) {
				if (strcmp('', $label) && strcmp('0', $label)) {
					$this->shortcutGroups[$groupId] = (string) $label;
				} elseif ($GLOBALS['BE_USER']->isAdmin()) {
					unset($this->shortcutGroups[$groupId]);
				}
			}
		}
		// Generate global groups, all global groups have negative IDs.
		if (count($this->shortcutGroups)) {
			$groups = $this->shortcutGroups;
			foreach ($groups as $groupId => $groupLabel) {
				$this->shortcutGroups[$groupId * -1] = $groupLabel;
			}
		}
		// Group -100 is kind of superglobal and can't be changed.
		$this->shortcutGroups[-100] = 1;
		// Add labels
		foreach ($this->shortcutGroups as $groupId => $groupLabel) {
			$label = $groupLabel;
			if ($groupLabel == '1') {
				$label = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_misc.xlf:bookmark_group_' . abs($groupId), 1);
				if (empty($label)) {
					// Fallback label
					$label = $GLOBALS['LANG']->getLL('bookmark_group', 1) . ' ' . abs($groupId);
				}
			}
			if ($groupId < 0) {
				// Global group
				$label = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_misc.xlf:bookmark_global', 1) . ': ' . (!empty($label) ? $label : abs($groupId));
				if ($groupId == -100) {
					$label = $GLOBALS['LANG']->getLL('bookmark_global', 1) . ': ' . $GLOBALS['LANG']->getLL('bookmark_all', 1);
				}
			}
			$this->shortcutGroups[$groupId] = $label;
		}
		return $this->shortcutGroups;
	}

	/**
	 * gets the available shortcut groups
	 *
	 * @param array $params Array of parameters from the AJAX interface, currently unused
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj Object of type TYPO3AJAX
	 * @return void
	 */
	public function getAjaxShortcutGroups($params = array(), \TYPO3\CMS\Core\Http\AjaxRequestHandler &$ajaxObj = NULL) {
		$shortcutGroups = $this->shortcutGroups;
		if (!$GLOBALS['BE_USER']->isAdmin()) {
			foreach ($shortcutGroups as $groupId => $groupName) {
				if (intval($groupId) < 0) {
					unset($shortcutGroups[$groupId]);
				}
			}
		}
		$ajaxObj->addContent('shortcutGroups', $shortcutGroups);
		$ajaxObj->setContentFormat('json');
	}

	/**
	 * Deletes a shortcut through an AJAX call
	 *
	 * @param array $params Array of parameters from the AJAX interface, currently unused
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj Object of type TYPO3AJAX
	 * @return void
	 */
	public function deleteAjaxShortcut($params = array(), \TYPO3\CMS\Core\Http\AjaxRequestHandler &$ajaxObj = NULL) {
		$shortcutId = (int) \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('shortcutId');
		$fullShortcut = $this->getShortcutById($shortcutId);
		$ajaxReturn = 'failed';
		if ($fullShortcut['raw']['userid'] == $GLOBALS['BE_USER']->user['uid']) {
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('sys_be_shortcuts', 'uid = ' . $shortcutId);
			if ($GLOBALS['TYPO3_DB']->sql_affected_rows() == 1) {
				$ajaxReturn = 'deleted';
			}
		}
		$ajaxObj->addContent('delete', $ajaxReturn);
	}

	/**
	 * Creates a shortcut through an AJAX call
	 *
	 * @param array $params Array of parameters from the AJAX interface, currently unused
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj Oject of type TYPO3AJAX
	 * @return void
	 */
	public function createAjaxShortcut($params = array(), \TYPO3\CMS\Core\Http\AjaxRequestHandler &$ajaxObj = NULL) {
		$shortcutCreated = 'failed';
		// Default name
		$shortcutName = 'Shortcut';
		$shortcutNamePrepend = '';
		$url = \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('url');
		$module = \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('module');
		$motherModule = \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('motherModName');
		// Determine shortcut type
		$queryParts = parse_url($url);
		$queryParameters = \TYPO3\CMS\Core\Utility\GeneralUtility::explodeUrl2Array($queryParts['query'], 1);
		// Proceed only if no scheme is defined, as URL is expected to be relative
		if (empty($queryParts['scheme'])) {
			if (is_array($queryParameters['edit'])) {
				$shortcut['table'] = key($queryParameters['edit']);
				$shortcut['recordid'] = key($queryParameters['edit'][$shortcut['table']]);
				if ($queryParameters['edit'][$shortcut['table']][$shortcut['recordid']] == 'edit') {
					$shortcut['type'] = 'edit';
					$shortcutNamePrepend = $GLOBALS['LANG']->getLL('shortcut_edit', 1);
				} elseif ($queryParameters['edit'][$shortcut['table']][$shortcut['recordid']] == 'new') {
					$shortcut['type'] = 'new';
					$shortcutNamePrepend = $GLOBALS['LANG']->getLL('shortcut_create', 1);
				}
			} else {
				$shortcut['type'] = 'other';
			}
			// Lookup the title of this page and use it as default description
			$pageId = $shortcut['recordid'] ? $shortcut['recordid'] : $this->getLinkedPageId($url);
			if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($pageId)) {
				$page = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', $pageId);
				if (count($page)) {
					// Set the name to the title of the page
					if ($shortcut['type'] == 'other') {
						$shortcutName = $page['title'];
					} else {
						$shortcutName = $shortcutNamePrepend . ' ' . $GLOBALS['LANG']->sL($GLOBALS['TCA'][$shortcut['table']]['ctrl']['title']) . ' (' . $page['title'] . ')';
					}
				}
			} else {
				$dirName = urldecode($pageId);
				if (preg_match('/\\/$/', $dirName)) {
					// If $pageId is a string and ends with a slash,
					// assume it is a fileadmin reference and set
					// the description to the basename of that path
					$shortcutName .= ' ' . basename($dirName);
				}
			}
			// adding the shortcut
			if ($module && $url) {
				$fieldValues = array(
					'userid' => $GLOBALS['BE_USER']->user['uid'],
					'module_name' => $module . '|' . $motherModule,
					'url' => $url,
					'description' => $shortcutName,
					'sorting' => $GLOBALS['EXEC_TIME']
				);
				$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_be_shortcuts', $fieldValues);
				if ($GLOBALS['TYPO3_DB']->sql_affected_rows() == 1) {
					$shortcutCreated = 'success';
				}
			}
			$ajaxObj->addContent('create', $shortcutCreated);
		}
	}

	/**
	 * Gets called when a shortcut is changed, checks whether the user has
	 * permissions to do so and saves the changes if everything is ok
	 *
	 * @param array $params Array of parameters from the AJAX interface, currently unused
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj Object of type TYPO3AJAX
	 * @return void
	 */
	public function setAjaxShortcut($params = array(), \TYPO3\CMS\Core\Http\AjaxRequestHandler &$ajaxObj = NULL) {
		$shortcutId = (int) \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('shortcutId');
		$shortcutName = strip_tags(\TYPO3\CMS\Core\Utility\GeneralUtility::_POST('value'));
		$shortcutGroupId = (int) \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('shortcut-group');
		if ($shortcutGroupId > 0 || $GLOBALS['BE_USER']->isAdmin()) {
			// Users can delete only their own shortcuts (except admins)
			$addUserWhere = !$GLOBALS['BE_USER']->isAdmin() ? ' AND userid=' . intval($GLOBALS['BE_USER']->user['uid']) : '';
			$fieldValues = array(
				'description' => $shortcutName,
				'sc_group' => $shortcutGroupId
			);
			if ($fieldValues['sc_group'] < 0 && !$GLOBALS['BE_USER']->isAdmin()) {
				$fieldValues['sc_group'] = 0;
			}
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_be_shortcuts', 'uid=' . $shortcutId . $addUserWhere, $fieldValues);
			$affectedRows = $GLOBALS['TYPO3_DB']->sql_affected_rows();
			if ($affectedRows == 1) {
				$ajaxObj->addContent('shortcut', $shortcutName);
			} else {
				$ajaxObj->addContent('shortcut', 'failed');
			}
		}
		$ajaxObj->setContentFormat('plain');
	}

	/**
	 * Gets the label for a shortcut group
	 *
	 * @param integer $groupId A shortcut group id
	 * @return string The shortcut group label, can be an empty string if no group was found for the id
	 */
	protected function getShortcutGroupLabel($groupId) {
		$label = '';
		if ($this->shortcutGroups[$groupId]) {
			$label = $this->shortcutGroups[$groupId];
		}
		return $label;
	}

	/**
	 * Gets a list of global groups, shortcuts in these groups are available to all users
	 *
	 * @return array Array of global groups
	 */
	protected function getGlobalShortcutGroups() {
		$globalGroups = array();
		foreach ($this->shortcutGroups as $groupId => $groupLabel) {
			if ($groupId < 0) {
				$globalGroups[$groupId] = $groupLabel;
			}
		}
		return $globalGroups;
	}

	/**
	 * runs through the available shortcuts an collects their groups
	 *
	 * @return array Array of groups which have shortcuts
	 */
	protected function getGroupsFromShortcuts() {
		$groups = array();
		foreach ($this->shortcuts as $shortcut) {
			$groups[$shortcut['group']] = $this->shortcutGroups[$shortcut['group']];
		}
		return array_unique($groups);
	}

	/**
	 * Gets the icon for the shortcut
	 *
	 * @param array $row
	 * @param array $shortcut
	 * @return string Shortcut icon as img tag
	 */
	protected function getShortcutIcon($row, $shortcut) {
		switch ($row['module_name']) {
		case 'xMOD_alt_doc.php':
			$table = $shortcut['table'];
			$recordid = $shortcut['recordid'];
			if ($shortcut['type'] == 'edit') {
				// Creating the list of fields to include in the SQL query:
				$selectFields = $this->fieldArray;
				$selectFields[] = 'uid';
				$selectFields[] = 'pid';
				if ($table == 'pages') {
					if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('cms')) {
						$selectFields[] = 'module';
						$selectFields[] = 'extendToSubpages';
					}
					$selectFields[] = 'doktype';
				}
				if (is_array($GLOBALS['TCA'][$table]['ctrl']['enablecolumns'])) {
					$selectFields = array_merge($selectFields, $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']);
				}
				if ($GLOBALS['TCA'][$table]['ctrl']['type']) {
					$selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['type'];
				}
				if ($GLOBALS['TCA'][$table]['ctrl']['typeicon_column']) {
					$selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['typeicon_column'];
				}
				if ($GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
					$selectFields[] = 't3ver_state';
				}
				// Unique list!
				$selectFields = array_unique($selectFields);
				$permissionClause = $table == 'pages' && $this->perms_clause ? ' AND ' . $this->perms_clause : '';
				$sqlQueryParts = array(
					'SELECT' => implode(',', $selectFields),
					'FROM' => $table,
					'WHERE' => 'uid IN (' . $recordid . ') ' . $permissionClause . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table) . \TYPO3\CMS\Backend\Utility\BackendUtility::versioningPlaceholderClause($table)
				);
				$result = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($sqlQueryParts);
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
				$icon = \TYPO3\CMS\Backend\Utility\IconUtility::getIcon($table, $row, $this->backPath);
			} elseif ($shortcut['type'] == 'new') {
				$icon = \TYPO3\CMS\Backend\Utility\IconUtility::getIcon($table, '', $this->backPath);
			}
			$icon = \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->backPath, $icon, '', 1);
			break;
		case 'xMOD_file_edit.php':
			$icon = 'gfx/edit_file.gif';
			break;
		case 'xMOD_wizard_rte.php':
			$icon = 'gfx/edit_rtewiz.gif';
			break;
		default:
			if ($GLOBALS['LANG']->moduleLabels['tabs_images'][$row['module_name'] . '_tab']) {
				$icon = $GLOBALS['LANG']->moduleLabels['tabs_images'][$row['module_name'] . '_tab'];
				// Change icon of fileadmin references - otherwise it doesn't differ with Web->List
				$icon = str_replace('mod/file/list/list.gif', 'mod/file/file.gif', $icon);
				if (\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($icon)) {
					$icon = '../' . substr($icon, strlen(PATH_site));
				}
			} else {
				$icon = 'gfx/dummy_module.gif';
			}
		}
		return '<img src="' . $icon . '" alt="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:toolbarItems.shortcut', TRUE) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:toolbarItems.shortcut', TRUE) . '" />';
	}

	/**
	 * Returns title for the shortcut icon
	 *
	 * @param string $shortcutLabel Shortcut label
	 * @param string $moduleName Backend module name (key)
	 * @param string $parentModuleName Parent module label
	 * @return string Title for the shortcut icon
	 */
	protected function getShortcutIconTitle($shortcutLabel, $moduleName, $parentModuleName = '') {
		$title = '';
		if (substr($moduleName, 0, 5) == 'xMOD_') {
			$title = substr($moduleName, 5);
		} else {
			$splitModuleName = explode('_', $moduleName);
			$title = $GLOBALS['LANG']->moduleLabels['tabs'][$splitModuleName[0] . '_tab'];
			if (count($splitModuleName) > 1) {
				$title .= '>' . $GLOBALS['LANG']->moduleLabels['tabs'][($moduleName . '_tab')];
			}
		}
		if ($parentModuleName) {
			$title .= ' (' . $parentModuleName . ')';
		}
		$title .= ': ' . $shortcutLabel;
		return $title;
	}

	/**
	 * Return the ID of the page in the URL if found.
	 *
	 * @param string $url The URL of the current shortcut link
	 * @return string If a page ID was found, it is returned. Otherwise: 0
	 */
	protected function getLinkedPageId($url) {
		return preg_replace('/.*[\\?&]id=([^&]+).*/', '$1', $url);
	}

}


?>