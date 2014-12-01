<?php
namespace TYPO3\CMS\Backend\Backend\ToolbarItems;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Backend\Module\ModuleLoader;

/**
 * Class to render the shortcut menu
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class ShortcutToolbarItem implements ToolbarItemInterface {

	/**
	 * @const integer Number of super global group
	 */
	const SUPERGLOBAL_GROUP = -100;

	/**
	 * @var string
	 */
	public $perms_clause;

	/**
	 * @var array
	 */
	public $fieldArray;

	/**
	 * All available shortcuts
	 *
	 * @var array
	 */
	protected $shortcuts;

	/**
	 * @var array
	 */
	protected $shortcutGroups;

	/**
	 * Labels of all groups.
	 * If value is 1, the system will try to find a label in the locallang array.
	 *
	 * @var array
	 */
	protected $groupLabels;

	/**
	 * Constructor
	 */
	public function __construct() {
		if (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX) {
			$this->getLanguageService()->includeLLFile('EXT:lang/locallang_misc.xlf');
			// Needed to get the correct icons when reloading the menu after saving it
			$loadModules = GeneralUtility::makeInstance(ModuleLoader::class);
			$loadModules->load($GLOBALS['TBE_MODULES']);
		}

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

		$this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/Toolbar/ShortcutMenu');
	}

	/**
	 * Checks whether the user has access to this toolbar item
	 *
	 * @return bool TRUE if user has access, FALSE if not
	 */
	public function checkAccess() {
		return (bool)$this->getBackendUser()->getTSConfigVal('options.enableBookmarks');
	}

	/**
	 * Render shortcut icon
	 *
	 * @return string HTML
	 */
	public function getItem() {
		return IconUtility::getSpriteIcon(
			'apps-toolbar-menu-shortcut',
			array(
				'title' => $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:toolbarItems.bookmarks', TRUE),
			)
		);
	}

	/**
	 * Render drop down content
	 *
	 * @return string HTML
	 */
	public function getDropDown() {
		$languageService = $this->getLanguageService();
		$shortcutGroup = $languageService->sL('LLL:EXT:lang/locallang_core.xlf:toolbarItems.bookmarksGroup', TRUE);
		$shortcutEdit = $languageService->sL('LLL:EXT:lang/locallang_core.xlf:toolbarItems.bookmarksEdit', TRUE);
		$shortcutDelete = $languageService->sL('LLL:EXT:lang/locallang_core.xlf:toolbarItems.bookmarksDelete', TRUE);
		$editIcon = '<a href="#" class="dropdown-list-link-edit shortcut-edit">' . IconUtility::getSpriteIcon('actions-document-open', array('title' => $shortcutEdit)) . '</a>';
		$deleteIcon = '<a href="#" class="dropdown-list-link-delete shortcut-delete">' . IconUtility::getSpriteIcon('actions-edit-delete', array('title' => $shortcutDelete)) . '</a>';

		$shortcutMenu[] = '<ul class="dropdown-list">';

		// Render shortcuts with no group (group id = 0) first
		$noGroupShortcuts = $this->getShortcutsByGroup(0);
		foreach ($noGroupShortcuts as $shortcut) {

			$shortcutMenu[] = '
				<li class="shortcut" data-shortcutid="' . (int)$shortcut['raw']['uid'] . '">
					<a class="dropdown-list-link dropdown-link-list-add-editdelete" href="#" onclick="' . htmlspecialchars($shortcut['action']) . ' return false;">' .
						$shortcut['icon'] . ' ' .
						htmlspecialchars($shortcut['label']) .
					'</a>
					' . $editIcon . $deleteIcon . '
				</li>';
		}
		// Now render groups and the contained shortcuts
		$groups = $this->getGroupsFromShortcuts();
		krsort($groups, SORT_NUMERIC);
		foreach ($groups as $groupId => $groupLabel) {
			if ($groupId != 0) {
				$shortcutGroup = '';
				if (count($shortcutMenu) > 1) {
					$shortcutGroup .= '<li class="divider"></li>';
				}
				$shortcutGroup .= '
					<li class="dropdown-header" id="shortcut-group-' . (int)$groupId . '">
						' . $groupLabel . '
					</li>';
				$shortcuts = $this->getShortcutsByGroup($groupId);
				$i = 0;
				foreach ($shortcuts as $shortcut) {
					$i++;
					$shortcutGroup .= '
					<li class="shortcut" data-shortcutid="' . (int)$shortcut['raw']['uid'] . '" data-shortcutgroup="' . (int)$groupId . '">
						<a class="dropdown-list-link dropdown-link-list-add-editdelete" href="#" onclick="' . htmlspecialchars($shortcut['action']) . ' return false;">' .
							$shortcut['icon'] . ' ' .
							htmlspecialchars($shortcut['label']) .
						'</a>
						' . $editIcon . $deleteIcon . '
					</li>';
				}
				$shortcutMenu[] = $shortcutGroup;
			}
		}
		$shortcutMenu[] = '</ul>';

		if (count($shortcutMenu) == 2) {
			// No shortcuts added yet, show a small help message how to add shortcuts
			$title = $languageService->sL('LLL:EXT:lang/locallang_core.xlf:toolbarItems.bookmarks', TRUE);
			$icon = IconUtility::getSpriteIcon('actions-system-shortcut-new', array(
				'title' => $title
			));
			$label = str_replace('%icon%', $icon, $languageService->sL('LLL:EXT:lang/locallang_misc.xlf:bookmarkDescription'));
			$compiledShortcutMenu = '<p>' . $label . '</p>';
		} else {
			$compiledShortcutMenu = implode(LF, $shortcutMenu);
		}

		return $compiledShortcutMenu;
	}

	/**
	 * Renders the menu so that it can be returned as response to an AJAX call
	 *
	 * @param array $params Array of parameters from the AJAX interface, currently unused
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj Object of type AjaxRequestHandler
	 * @return void
	 */
	public function renderAjaxMenu($params = array(), \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj = NULL) {
		$menuContent = $this->getDropDown();
		$ajaxObj->addContent('shortcutMenu', $menuContent);
	}

	/**
	 * This toolbar item needs no additional attributes
	 *
	 * @return array
	 */
	public function getAdditionalAttributes() {
		return array();
	}

	/**
	 * This item has a drop down
	 *
	 * @return bool
	 */
	public function hasDropDown() {
		return TRUE;
	}

	/**
	 * Retrieves the shortcuts for the current user
	 *
	 * @return array Array of shortcuts
	 */
	protected function initShortcuts() {
		$databaseConnection = $this->getDatabaseConnection();
		$globalGroupIdList = implode(',', array_keys($this->getGlobalShortcutGroups()));
		$backendUser = $this->getBackendUser();
		$res = $databaseConnection->exec_SELECTquery(
			'*',
			'sys_be_shortcuts',
			'(userid = ' . (int)$backendUser->user['uid'] . ' AND sc_group>=0) OR sc_group IN (' . $globalGroupIdList . ')',
			'',
			'sc_group,sorting'
		);
		// Traverse shortcuts
		$lastGroup = 0;
		$shortcuts = array();
		while ($row = $databaseConnection->sql_fetch_assoc($res)) {
			$shortcut = array('raw' => $row);

			list($row['module_name'], $row['M_module_name']) = explode('|', $row['module_name']);

			$queryParts = parse_url($row['url']);
			$queryParameters = GeneralUtility::explodeUrl2Array($queryParts['query'], 1);
			if ($row['module_name'] === 'xMOD_alt_doc.php' && is_array($queryParameters['edit'])) {
				$shortcut['table'] = key($queryParameters['edit']);
				$shortcut['recordid'] = key($queryParameters['edit'][$shortcut['table']]);
				if ($queryParameters['edit'][$shortcut['table']][$shortcut['recordid']] === 'edit') {
					$shortcut['type'] = 'edit';
				} elseif ($queryParameters['edit'][$shortcut['table']][$shortcut['recordid']] === 'new') {
					$shortcut['type'] = 'new';
				}
				if (substr($shortcut['recordid'], -1) === ',') {
					$shortcut['recordid'] = substr($shortcut['recordid'], 0, -1);
				}
			} else {
				$shortcut['type'] = 'other';
			}
			// Check for module access
			$moduleName = $row['M_module_name'] ?: $row['module_name'];
			$pageId = $this->getLinkedPageId($row['url']);
			if (!$backendUser->isAdmin()) {
				if (!isset($this->getLanguageService()->moduleLabels['tabs_images'][$moduleName . '_tab'])) {
					// Nice hack to check if the user has access to this module
					// - otherwise the translation label would not have been loaded :-)
					continue;
				}
				if (MathUtility::canBeInterpretedAsInteger($pageId)) {
					// Check for webmount access
					if (!$backendUser->isInWebMount($pageId)) {
						continue;
					}
					// Check for record access
					$pageRow = BackendUtility::getRecord('pages', $pageId);
					if (!$backendUser->doesUserHaveAccess($pageRow, ($perms = 1))) {
						continue;
					}
				}
			}
			$moduleParts = explode('_', $moduleName);
			$shortcutGroup = (int)$row['sc_group'];
			if ($shortcutGroup && $lastGroup !== $shortcutGroup && $shortcutGroup !== self::SUPERGLOBAL_GROUP) {
				$shortcut['groupLabel'] = $this->getShortcutGroupLabel($shortcutGroup);
			}
			$lastGroup = $shortcutGroup;

			if ($row['description']) {
				$shortcut['label'] = $row['description'];
			} else {
				$shortcut['label'] = GeneralUtility::fixed_lgd_cs(rawurldecode($queryParts['query']), 150);
			}
			$shortcut['group'] = $shortcutGroup;
			$shortcut['icon'] = $this->getShortcutIcon($row, $shortcut);
			$shortcut['iconTitle'] = $this->getShortcutIconTitle($shortcut['label'], $row['module_name'], $row['M_module_name']);
			$shortcut['action'] = 'jump(' . GeneralUtility::quoteJSvalue($this->getTokenUrl($row['url'])) . ',' . GeneralUtility::quoteJSvalue($moduleName) . ',' . GeneralUtility::quoteJSvalue($moduleParts[0]) . ', ' . (int)$pageId . ');';

			$shortcuts[] = $shortcut;
		}
		return $shortcuts;
	}

	/**
	 * Adds the correct token, if the url is a mod.php script
	 *
	 * @param string $url
	 * @return string
	 */
	protected function getTokenUrl($url) {
		$parsedUrl = parse_url($url);
		parse_str($parsedUrl['query'], $parameters);

		// parse the returnUrl and replace the module token of it
		if (isset($parameters['returnUrl'])) {
			$parsedReturnUrl = parse_url($parameters['returnUrl']);
			parse_str($parsedReturnUrl['query'], $returnUrlParameters);
			if (strpos($parsedReturnUrl['path'], 'mod.php') !== FALSE && isset($returnUrlParameters['M'])) {
				$module = $returnUrlParameters['M'];
				$returnUrl = BackendUtility::getModuleUrl($module, $returnUrlParameters);
				$parameters['returnUrl'] = $returnUrl;
				$url = $parsedUrl['path'] . '?' . http_build_query($parameters);
			}
		}

		if (strpos($parsedUrl['path'], 'mod.php') !== FALSE && isset($parameters['M'])) {
			$module = $parameters['M'];
			$url = str_replace('mod.php', '', $parsedUrl['path']) . BackendUtility::getModuleUrl($module, $parameters);
		}
		return $url;
	}

	/**
	 * Gets shortcuts for a specific group
	 *
	 * @param int $groupId Group Id
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
	 * @param int $shortcutId Shortcut id to get the complete shortcut for
	 * @return mixed An array containing the shortcut's data on success or FALSE on failure
	 */
	protected function getShortcutById($shortcutId) {
		$returnShortcut = FALSE;
		foreach ($this->shortcuts as $shortcut) {
			if ($shortcut['raw']['uid'] == (int)$shortcutId) {
				$returnShortcut = $shortcut;
				continue;
			}
		}
		return $returnShortcut;
	}

	/**
	 * Gets the available shortcut groups from default groups, user TSConfig, and global groups
	 *
	 * @return array
	 */
	protected function initShortcutGroups() {
		$languageService = $this->getLanguageService();
		$backendUser = $this->getBackendUser();
		// Groups from TSConfig
		$bookmarkGroups = $backendUser->getTSConfigProp('options.bookmarkGroups');
		if (is_array($bookmarkGroups) && count($bookmarkGroups)) {
			foreach ($bookmarkGroups as $groupId => $label) {
				if (!empty($label)) {
					$this->shortcutGroups[$groupId] = (string)$label;
				} elseif ($backendUser->isAdmin()) {
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
		$this->shortcutGroups[self::SUPERGLOBAL_GROUP] = 1;
		// Add labels
		foreach ($this->shortcutGroups as $groupId => $groupLabel) {
			$groupId = (int)$groupId;
			$label = $groupLabel;
			if ($groupLabel == '1') {
				$label = $languageService->sL('LLL:EXT:lang/locallang_misc.xlf:bookmark_group_' . abs($groupId), TRUE);
				if (empty($label)) {
					// Fallback label
					$label = $languageService->getLL('bookmark_group', TRUE) . ' ' . abs($groupId);
				}
			}
			if ($groupId < 0) {
				// Global group
				$label = $languageService->sL('LLL:EXT:lang/locallang_misc.xlf:bookmark_global', TRUE) . ': ' . (!empty($label) ? $label : abs($groupId));
				if ($groupId === self::SUPERGLOBAL_GROUP) {
					$label = $languageService->getLL('bookmark_global', TRUE) . ': ' . $languageService->getLL('bookmark_all', TRUE);
				}
			}
			$this->shortcutGroups[$groupId] = $label;
		}
		return $this->shortcutGroups;
	}

	/**
	 * gets the available shortcut groups, renders a form so it can be saved lateron
	 *
	 * @param array $params Array of parameters from the AJAX interface, currently unused
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj Object of type AjaxRequestHandler
	 * @return void
	 */
	public function getAjaxShortcutEditForm($params = array(), \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj = NULL) {
		$selectedShortcutId = (int)GeneralUtility::_GP('shortcutId');
		$selectedShortcutGroupId = (int)GeneralUtility::_GP('shortcutGroup');
		$selectedShortcut = $this->getShortcutById($selectedShortcutId);

		$shortcutGroups = $this->shortcutGroups;
		if (!$this->getBackendUser()->isAdmin()) {
			foreach ($shortcutGroups as $groupId => $groupName) {
				if ((int)$groupId < 0) {
					unset($shortcutGroups[$groupId]);
				}
			}
		}

		// build the form
		$content = '
			<form class="shortcut-form" role="form">
				<div class="form-group">
					<input type="text" class="form-control" name="shortcut-title" value="' . htmlspecialchars($selectedShortcut['label']) . '">
				</div>';

		$content .= '
				<div class="form-group">
					<select class="form-control" name="shortcut-group">';
		foreach ($shortcutGroups as $shortcutGroupId => $shortcutGroupTitle) {
			$content .= '<option value="' . (int)$shortcutGroupId . '"' . ($selectedShortcutGroupId == $shortcutGroupId ? ' selected="selected"' : '') . '>' . htmlspecialchars($shortcutGroupTitle) . '</option>';
		}
		$content .= '
					</select>
				</div>
				<input type="button" class="btn btn-default shortcut-form-cancel" value="Cancel">
				<input type="button" class="btn btn-success shortcut-form-save" value="Save">
			</form>';

		$ajaxObj->addContent('data', $content);
	}

	/**
	 * Deletes a shortcut through an AJAX call
	 *
	 * @param array $params Array of parameters from the AJAX interface, currently unused
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj Object of type AjaxRequestHandler
	 * @return void
	 */
	public function deleteAjaxShortcut($params = array(), \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj = NULL) {
		$databaseConnection = $this->getDatabaseConnection();
		$shortcutId = (int)GeneralUtility::_POST('shortcutId');
		$fullShortcut = $this->getShortcutById($shortcutId);
		$ajaxReturn = 'failed';
		if ($fullShortcut['raw']['userid'] == $this->getBackendUser()->user['uid']) {
			$databaseConnection->exec_DELETEquery('sys_be_shortcuts', 'uid = ' . $shortcutId);
			if ($databaseConnection->sql_affected_rows() == 1) {
				$ajaxReturn = 'deleted';
			}
		}
		$ajaxObj->addContent('delete', $ajaxReturn);
	}

	/**
	 * Creates a shortcut through an AJAX call
	 *
	 * @param array $params Array of parameters from the AJAX interface, currently unused
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj Oject of type AjaxRequestHandler
	 * @return void
	 */
	public function createAjaxShortcut($params = array(), \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj = NULL) {
		$databaseConnection = $this->getDatabaseConnection();
		$languageService = $this->getLanguageService();
		$shortcutCreated = 'failed';
		// Default name
		$shortcutName = 'Shortcut';
		$shortcutNamePrepend = '';
		$url = GeneralUtility::_POST('url');
		$module = GeneralUtility::_POST('module');
		$motherModule = GeneralUtility::_POST('motherModName');
		// Determine shortcut type
		$url = rawurldecode($url);
		$queryParts = parse_url($url);
		$queryParameters = GeneralUtility::explodeUrl2Array($queryParts['query'], TRUE);
		// Proceed only if no scheme is defined, as URL is expected to be relative
		if (empty($queryParts['scheme'])) {
			if (is_array($queryParameters['edit'])) {
				$shortcut['table'] = key($queryParameters['edit']);
				$shortcut['recordid'] = key($queryParameters['edit'][$shortcut['table']]);
				if ($queryParameters['edit'][$shortcut['table']][$shortcut['recordid']] == 'edit') {
					$shortcut['type'] = 'edit';
					$shortcutNamePrepend = $languageService->getLL('shortcut_edit', TRUE);
				} elseif ($queryParameters['edit'][$shortcut['table']][$shortcut['recordid']] == 'new') {
					$shortcut['type'] = 'new';
					$shortcutNamePrepend = $languageService->getLL('shortcut_create', TRUE);
				}
			} else {
				$shortcut['type'] = 'other';
			}
			// Lookup the title of this page and use it as default description
			$pageId = $shortcut['recordid'] ? $shortcut['recordid'] : $this->getLinkedPageId($url);
			if (MathUtility::canBeInterpretedAsInteger($pageId)) {
				$page = BackendUtility::getRecord('pages', $pageId);
				if (count($page)) {
					// Set the name to the title of the page
					if ($shortcut['type'] == 'other') {
						$shortcutName = $page['title'];
					} else {
						$shortcutName = $shortcutNamePrepend . ' ' . $languageService->sL($GLOBALS['TCA'][$shortcut['table']]['ctrl']['title']) . ' (' . $page['title'] . ')';
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
					'userid' => $this->getBackendUser()->user['uid'],
					'module_name' => $module . '|' . $motherModule,
					'url' => $url,
					'description' => $shortcutName,
					'sorting' => $GLOBALS['EXEC_TIME']
				);
				$databaseConnection->exec_INSERTquery('sys_be_shortcuts', $fieldValues);
				if ($databaseConnection->sql_affected_rows() == 1) {
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
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj Object of type AjaxRequestHandler
	 * @return void
	 */
	public function setAjaxShortcut($params = array(), \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj = NULL) {
		$databaseConnection = $this->getDatabaseConnection();
		$backendUser = $this->getBackendUser();
		$shortcutId = (int)GeneralUtility::_POST('shortcutId');
		$shortcutName = strip_tags(GeneralUtility::_POST('shortcutTitle'));
		$shortcutGroupId = (int)GeneralUtility::_POST('shortcutGroup');
		if ($shortcutGroupId > 0 || $backendUser->isAdmin()) {
			// Users can delete only their own shortcuts (except admins)
			$addUserWhere = !$backendUser->isAdmin() ? ' AND userid=' . (int)$backendUser->user['uid'] : '';
			$fieldValues = array(
				'description' => $shortcutName,
				'sc_group' => $shortcutGroupId
			);
			if ($fieldValues['sc_group'] < 0 && !$backendUser->isAdmin()) {
				$fieldValues['sc_group'] = 0;
			}
			$databaseConnection->exec_UPDATEquery('sys_be_shortcuts', 'uid=' . $shortcutId . $addUserWhere, $fieldValues);
			$affectedRows = $databaseConnection->sql_affected_rows();
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
	 * @param int $groupId A shortcut group id
	 * @return string The shortcut group label, can be an empty string if no group was found for the id
	 */
	protected function getShortcutGroupLabel($groupId) {
		return isset($this->shortcutGroups[$groupId]) ? $this->shortcutGroups[$groupId] : '';
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
		$databaseConnection = $this->getDatabaseConnection();
		$languageService = $this->getLanguageService();
		$titleAttribute = $languageService->sL('LLL:EXT:lang/locallang_core.xlf:toolbarItems.shortcut', TRUE);
		switch ($row['module_name']) {
			case 'xMOD_alt_doc.php':
				$table = $shortcut['table'];
				$recordid = $shortcut['recordid'];
				$icon = '';
				if ($shortcut['type'] == 'edit') {
					// Creating the list of fields to include in the SQL query:
					$selectFields = $this->fieldArray;
					$selectFields[] = 'uid';
					$selectFields[] = 'pid';
					if ($table == 'pages') {
						$selectFields[] = 'module';
						$selectFields[] = 'extendToSubpages';
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
					$permissionClause = $table === 'pages' && $this->perms_clause ? ' AND ' . $this->perms_clause : '';
					$sqlQueryParts = array(
						'SELECT' => implode(',', $selectFields),
						'FROM' => $table,
						'WHERE' => 'uid IN (' . $recordid . ') ' . $permissionClause . BackendUtility::deleteClause($table) . BackendUtility::versioningPlaceholderClause($table)
					);
					$result = $databaseConnection->exec_SELECT_queryArray($sqlQueryParts);
					$row = $databaseConnection->sql_fetch_assoc($result);
					$icon = IconUtility::getSpriteIconForRecord($table, (array)$row, array('title' => $titleAttribute));
				} elseif ($shortcut['type'] == 'new') {
					$icon = IconUtility::getSpriteIconForRecord($table, array(), array('title' => $titleAttribute));
				}
				break;
			case 'file_edit':
				$icon = IconUtility::getSpriteIcon('mimetypes-text-html', array('title' => $titleAttribute));
				break;
			case 'wizard_rte':
				$icon = IconUtility::getSpriteIcon('mimetypes-word', array('title' => $titleAttribute));
				break;
			default:
				if ($languageService->moduleLabels['tabs_images'][$row['module_name'] . '_tab']) {
					$icon = $languageService->moduleLabels['tabs_images'][$row['module_name'] . '_tab'];
					// Change icon of fileadmin references - otherwise it doesn't differ with Web->List
					$icon = str_replace('mod/file/list/list.gif', 'mod/file/file.gif', $icon);
					if (GeneralUtility::isAbsPath($icon)) {
						$icon = '../' . \TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix($icon);
					}
					// @todo: hardcoded width as we don't have a way to address module icons with an API yet.
					$icon = '<img src="' . htmlspecialchars($icon) . '" alt="' . $titleAttribute . '" width="16">';
				} else {
					$icon = IconUtility::getSpriteIcon('empty-empty', array('title' => $titleAttribute));
				}
		}
		return $icon;
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
		$languageService = $this->getLanguageService();
		if (substr($moduleName, 0, 5) == 'xMOD_') {
			$title = substr($moduleName, 5);
		} else {
			$splitModuleName = explode('_', $moduleName);
			$title = $languageService->moduleLabels['tabs'][$splitModuleName[0] . '_tab'];
			if (count($splitModuleName) > 1) {
				$title .= '>' . $languageService->moduleLabels['tabs'][($moduleName . '_tab')];
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

	/**
	 * Position relative to others, live search should be very right
	 *
	 * @return int
	 */
	public function getIndex() {
		return 20;
	}

	/**
	 * Returns the current BE user.
	 *
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}

	/**
	 * Returns current PageRenderer
	 *
	 * @return \TYPO3\CMS\Core\Page\PageRenderer
	 */
	protected function getPageRenderer() {
		/** @var  \TYPO3\CMS\Backend\Template\DocumentTemplate $documentTemplate */
		$documentTemplate = $GLOBALS['TBE_TEMPLATE'];
		return $documentTemplate->getPageRenderer();
	}

	/**
	 * Returns LanguageService
	 *
	 * @return \TYPO3\CMS\Lang\LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

	/**
	 * Return DatabaseConnection
	 *
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

}
