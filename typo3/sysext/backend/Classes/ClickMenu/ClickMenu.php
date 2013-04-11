<?php
namespace TYPO3\CMS\Backend\ClickMenu;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class for generating the click menu
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @internal
 */
class ClickMenu {

	// Internal, static: GPvar:
	// Defines if the click menu is first level or second. Second means the click menu is triggered from another menu.
	/**
	 * @todo Define visibility
	 */
	public $cmLevel = 0;

	// Clipboard array (submitted by eg. pressing the paste button)
	/**
	 * @todo Define visibility
	 */
	public $CB;

	// Internal, static:
	// Backpath for scripts/images.
	/**
	 * @todo Define visibility
	 */
	public $backPath = '';

	// BackPath place holder: We need different backPath set whether the clickmenu is written back to a frame which is not in typo3/ dir or if the clickmenu is shown in the top frame (no backpath)
	/**
	 * @todo Define visibility
	 */
	public $PH_backPath = '###BACK_PATH###';

	// If set, the calling document should be in the listframe of a frameset.
	/**
	 * @todo Define visibility
	 */
	public $listFrame = 0;

	// If set, the menu is about database records, not files. (set if part 2 [1] of the item-var is NOT blank)
	/**
	 * @todo Define visibility
	 */
	public $isDBmenu = 0;

	// If TRUE, the "content" frame is always used for reference (when condensed mode is enabled)
	/**
	 * @todo Define visibility
	 */
	public $alwaysContentFrame = 0;

	// Stores the parts of the input $item string, splitted by "|":
	// [0] = table/file, [1] = uid/blank, [2] = flag: If set, listFrame,
	// If "2" then "content frame" is forced  [3] = ("+" prefix = disable
	// all by default, enable these. Default is to disable) Items key list
	/**
	 * @todo Define visibility
	 */
	public $iParts = array();

	// Contains list of keywords of items to disable in the menu
	/**
	 * @todo Define visibility
	 */
	public $disabledItems = array();

	// If TRUE, Show icons on the left.
	/**
	 * @todo Define visibility
	 */
	public $leftIcons = 0;

	// Array of classes to be used for user processing of the menu content. This is for the API of adding items to the menu from outside.
	/**
	 * @todo Define visibility
	 */
	public $extClassArray = array();

	// Enable/disable ajax behavior
	/**
	 * @todo Define visibility
	 */
	public $ajax = 0;

	// Internal, dynamic:
	// Counter for elements in the menu. Used to number the name / id of the mouse-over icon.
	/**
	 * @todo Define visibility
	 */
	public $elCount = 0;

	// Set, when edit icon is drawn.
	/**
	 * @todo Define visibility
	 */
	public $editPageIconSet = 0;

	// Set to TRUE, if editing of the element is OK.
	/**
	 * @todo Define visibility
	 */
	public $editOK = 0;

	/**
	 * @todo Define visibility
	 */
	public $rec = array();

	/**
	 * Initialize click menu
	 *
	 * @return string The clickmenu HTML content
	 * @todo Define visibility
	 */
	public function init() {
		// Setting GPvars:
		$this->cmLevel = intval(GeneralUtility::_GP('cmLevel'));
		$this->CB = GeneralUtility::_GP('CB');
		if (GeneralUtility::_GP('ajax')) {
			$this->ajax = 1;
			// XML has to be parsed, no parse errors allowed
			@ini_set('display_errors', 0);
		}
		// Deal with Drag&Drop context menus
		if (strcmp(GeneralUtility::_GP('dragDrop'), '')) {
			$CMcontent = $this->printDragDropClickMenu(GeneralUtility::_GP('dragDrop'), GeneralUtility::_GP('srcId'), GeneralUtility::_GP('dstId'));
			return $CMcontent;
		}
		// Can be set differently as well
		$this->iParts[0] = GeneralUtility::_GP('table');
		$this->iParts[1] = GeneralUtility::_GP('uid');
		$this->iParts[2] = GeneralUtility::_GP('listFr');
		$this->iParts[3] = GeneralUtility::_GP('enDisItems');
		// Setting flags:
		if ($this->iParts[2]) {
			$this->listFrame = 1;
		}
		if ($GLOBALS['BE_USER']->uc['condensedMode'] || $this->iParts[2] == 2) {
			$this->alwaysContentFrame = 1;
		}
		if (strcmp($this->iParts[1], '')) {
			$this->isDBmenu = 1;
		}
		$TSkey = ($this->isDBmenu ? 'page' : 'folder') . ($this->listFrame ? 'List' : 'Tree');
		$this->disabledItems = GeneralUtility::trimExplode(',', $GLOBALS['BE_USER']->getTSConfigVal('options.contextMenu.' . $TSkey . '.disableItems'), 1);
		$this->leftIcons = $GLOBALS['BE_USER']->getTSConfigVal('options.contextMenu.options.leftIcons');
		// &cmLevel flag detected (2nd level menu)
		if (!$this->cmLevel) {
			// Make 1st level clickmenu:
			if ($this->isDBmenu) {
				$CMcontent = $this->printDBClickMenu($this->iParts[0], $this->iParts[1]);
			} else {
				$CMcontent = $this->printFileClickMenu($this->iParts[0]);
			}
		} else {
			// Make 2nd level clickmenu (only for DBmenus)
			if ($this->isDBmenu) {
				$CMcontent = $this->printNewDBLevel($this->iParts[0], $this->iParts[1]);
			}
		}
		// Return clickmenu content:
		return $CMcontent;
	}

	/**
	 * Returns TRUE if the menu should (also?) be displayed in topframe, not just <div>-layers
	 *
	 * @return boolean
	 * @todo Define visibility
	 * @deprecated since TYPO3 6.0, will be removed in 6.2 as there is no click menu in the topframe anymore (no topframe at all actually)
	 */
	public function doDisplayTopFrameCM() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		return FALSE;
	}

	/***************************************
	 *
	 * DATABASE
	 *
	 ***************************************/
	/**
	 * Make 1st level clickmenu:
	 *
	 * @param string $table Table name
	 * @param integer $uid UID for the current record.
	 * @return string HTML content
	 * @todo Define visibility
	 */
	public function printDBClickMenu($table, $uid) {
		// Get record:
		$this->rec = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL($table, $uid);
		$menuItems = array();
		$root = 0;
		$DBmount = FALSE;
		// Rootlevel
		if ($table == 'pages' && !strcmp($uid, '0')) {
			$root = 1;
		}
		// DB mount
		if ($table == 'pages' && in_array($uid, $GLOBALS['BE_USER']->returnWebmounts())) {
			$DBmount = TRUE;
		}
		// Used to hide cut,copy icons for l10n-records
		$l10nOverlay = FALSE;
		// Should only be performed for overlay-records within the same table
		if (\TYPO3\CMS\Backend\Utility\BackendUtility::isTableLocalizable($table) && !isset($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerTable'])) {
			$l10nOverlay = intval($this->rec[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']]) != 0;
		}
		// If record found (or root), go ahead and fill the $menuItems array which will contain data for the elements to render.
		if (is_array($this->rec) || $root) {
			// Get permissions
			$lCP = $GLOBALS['BE_USER']->calcPerms(\TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', $table == 'pages' ? $this->rec['uid'] : $this->rec['pid']));
			// View
			if (!in_array('view', $this->disabledItems)) {
				if ($table == 'pages') {
					$menuItems['view'] = $this->DB_view($uid);
				}
				if ($table == $GLOBALS['TYPO3_CONF_VARS']['SYS']['contentTable']) {
					$ws_rec = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL($table, $this->rec['uid']);
					$menuItems['view'] = $this->DB_view($ws_rec['pid']);
				}
			}
			// Edit:
			if (!$root && ($GLOBALS['BE_USER']->isPSet($lCP, $table, 'edit') || $GLOBALS['BE_USER']->isPSet($lCP, $table, 'editcontent'))) {
				if (!in_array('edit', $this->disabledItems)) {
					$menuItems['edit'] = $this->DB_edit($table, $uid);
				}
				$this->editOK = 1;
			}
			// New:
			if (!in_array('new', $this->disabledItems) && $GLOBALS['BE_USER']->isPSet($lCP, $table, 'new')) {
				$menuItems['new'] = $this->DB_new($table, $uid);
			}
			// Info:
			if (!in_array('info', $this->disabledItems) && !$root) {
				$menuItems['info'] = $this->DB_info($table, $uid);
			}
			$menuItems['spacer1'] = 'spacer';
			// Copy:
			if (!in_array('copy', $this->disabledItems) && !$root && !$DBmount && !$l10nOverlay) {
				$menuItems['copy'] = $this->DB_copycut($table, $uid, 'copy');
			}
			// Cut:
			if (!in_array('cut', $this->disabledItems) && !$root && !$DBmount && !$l10nOverlay) {
				$menuItems['cut'] = $this->DB_copycut($table, $uid, 'cut');
			}
			// Paste:
			$elFromAllTables = count($this->clipObj->elFromTable(''));
			if (!in_array('paste', $this->disabledItems) && $elFromAllTables) {
				$selItem = $this->clipObj->getSelectedRecord();
				$elInfo = array(
					GeneralUtility::fixed_lgd_cs($selItem['_RECORD_TITLE'], $GLOBALS['BE_USER']->uc['titleLen']),
					$root ? $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] : GeneralUtility::fixed_lgd_cs(\TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle($table, $this->rec), $GLOBALS['BE_USER']->uc['titleLen']),
					$this->clipObj->currentMode()
				);
				if ($table == 'pages' && $lCP & 8) {
					if ($elFromAllTables) {
						$menuItems['pasteinto'] = $this->DB_paste('', $uid, 'into', $elInfo);
					}
				}
				$elFromTable = count($this->clipObj->elFromTable($table));
				if (!$root && !$DBmount && $elFromTable && $GLOBALS['TCA'][$table]['ctrl']['sortby']) {
					$menuItems['pasteafter'] = $this->DB_paste($table, -$uid, 'after', $elInfo);
				}
			}
			// Delete:
			$elInfo = array(GeneralUtility::fixed_lgd_cs(\TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle($table, $this->rec), $GLOBALS['BE_USER']->uc['titleLen']));
			if (!in_array('delete', $this->disabledItems) && !$root && !$DBmount && $GLOBALS['BE_USER']->isPSet($lCP, $table, 'delete')) {
				$menuItems['spacer2'] = 'spacer';
				$menuItems['delete'] = $this->DB_delete($table, $uid, $elInfo);
			}
			if (!in_array('history', $this->disabledItems)) {
				$menuItems['history'] = $this->DB_history($table, $uid, $elInfo);
			}
		}
		// Adding external elements to the menuItems array
		$menuItems = $this->processingByExtClassArray($menuItems, $table, $uid);
		// Processing by external functions?
		$menuItems = $this->externalProcessingOfDBMenuItems($menuItems);
		if (!is_array($this->rec)) {
			$this->rec = array();
		}
		// Return the printed elements:
		return $this->printItems($menuItems, $root ? \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('apps-pagetree-root') . htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']) : \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord($table, $this->rec, array('title' => htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::getRecordIconAltText($this->rec, $table)))) . \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle($table, $this->rec, TRUE));
	}

	/**
	 * Make 2nd level clickmenu (only for DBmenus)
	 *
	 * @param string $table Table name
	 * @param integer $uid UID for the current record.
	 * @return string HTML content
	 * @todo Define visibility
	 */
	public function printNewDBLevel($table, $uid) {
		// Setting internal record to the table/uid :
		$this->rec = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL($table, $uid);
		$menuItems = array();
		$root = 0;
		// Rootlevel
		if ($table == 'pages' && !strcmp($uid, '0')) {
			$root = 1;
		}
		// If record was found, check permissions and get menu items.
		if (is_array($this->rec) || $root) {
			$lCP = $GLOBALS['BE_USER']->calcPerms(\TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', $table == 'pages' ? $this->rec['uid'] : $this->rec['pid']));
			// Edit:
			if (!$root && ($GLOBALS['BE_USER']->isPSet($lCP, $table, 'edit') || $GLOBALS['BE_USER']->isPSet($lCP, $table, 'editcontent'))) {
				$this->editOK = 1;
			}
			$menuItems = $this->processingByExtClassArray($menuItems, $table, $uid);
		}
		// Return the printed elements:
		if (!is_array($menuItems)) {
			$menuItems = array();
		}
		return $this->printItems($menuItems, $root ? \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('apps-pagetree-root') . htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']) : \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord($table, $this->rec, array('title' => htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::getRecordIconAltText($this->rec, $table)))) . \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle($table, $this->rec, TRUE));
	}

	/**
	 * Processing the $menuItems array (for extension classes) (DATABASE RECORDS)
	 *
	 * @param array $menuItems Array for manipulation.
	 * @return array Processed $menuItems array
	 * @todo Define visibility
	 */
	public function externalProcessingOfDBMenuItems($menuItems) {
		return $menuItems;
	}

	/**
	 * Processing the $menuItems array by external classes (typ. adding items)
	 *
	 * @param array $menuItems Array for manipulation.
	 * @param string $table Table name
	 * @param integer $uid UID for the current record.
	 * @return array Processed $menuItems array
	 * @todo Define visibility
	 */
	public function processingByExtClassArray($menuItems, $table, $uid) {
		if (is_array($this->extClassArray)) {
			foreach ($this->extClassArray as $conf) {
				$obj = GeneralUtility::makeInstance($conf['name']);
				$menuItems = $obj->main($this, $menuItems, $table, $uid);
			}
		}
		return $menuItems;
	}

	/**
	 * Returning JavaScript for the onClick event linking to the input URL.
	 *
	 * @param string $url The URL relative to TYPO3_mainDir
	 * @param string $retUrl The return_url-parameter
	 * @param boolean $hideCM If set, the "hideCM()" will be called
	 * @param string $overrideLoc If set, gives alternative location to load in (for example top frame or somewhere else)
	 * @return string JavaScript for an onClick event.
	 * @todo Define visibility
	 */
	public function urlRefForCM($url, $retUrl = '', $hideCM = 1, $overrideLoc = '') {
		$loc = 'top.content.list_frame';
		$editOnClick = ($overrideLoc ? 'var docRef=' . $overrideLoc : 'var docRef=(top.content.list_frame)?top.content.list_frame:' . $loc) . '; docRef.location.href=top.TS.PATH_typo3+\'' . $url . '\'' . ($retUrl ? '+\'&' . $retUrl . '=\'+top.rawurlencode(' . $this->frameLocation('docRef.document') . '.pathname+' . $this->frameLocation('docRef.document') . '.search)' : '') . ';' . ($hideCM ? 'return hideCM();' : '');
		return $editOnClick;
	}

	/**
	 * Adding CM element for Clipboard "copy" and "cut"
	 *
	 * @param string $table Table name
	 * @param integer $uid UID for the current record.
	 * @param string $type Type: "copy" or "cut
	 * @return array Item array, element in $menuItems
	 * @internal
	 * @todo Define visibility
	 */
	public function DB_copycut($table, $uid, $type) {
		if ($this->clipObj->current == 'normal') {
			$isSel = $this->clipObj->isSelected($table, $uid);
		}
		$addParam = array();
		if ($this->listFrame) {
			$addParam['reloadListFrame'] = $this->alwaysContentFrame ? 2 : 1;
		}
		return $this->linkItem($this->label($type), $this->excludeIcon(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-edit-' . $type . ($isSel === $type ? '-release' : ''))), 'top.loadTopMenu(\'' . $this->clipObj->selUrlDB($table, $uid, ($type == 'copy' ? 1 : 0), ($isSel == $type), $addParam) . '\');return false;');
	}

	/**
	 * Adding CM element for Clipboard "paste into"/"paste after"
	 * NOTICE: $table and $uid should follow the special syntax for paste, see clipboard-class :: pasteUrl();
	 *
	 * @param string $table Table name
	 * @param integer $uid UID for the current record. NOTICE: Special syntax!
	 * @param string $type Type: "into" or "after
	 * @param array $elInfo Contains instructions about whether to copy or cut an element.
	 * @return array Item array, element in $menuItems
	 * @see \TYPO3\CMS\Backend\Clipboard\Clipboard::pasteUrl()
	 * @internal
	 * @todo Define visibility
	 */
	public function DB_paste($table, $uid, $type, $elInfo) {
		$editOnClick = '';
		$loc = 'top.content.list_frame';
		if ($GLOBALS['BE_USER']->jsConfirmation(2)) {
			$conf = $loc . ' && confirm(' . $GLOBALS['LANG']->JScharCode(sprintf($GLOBALS['LANG']->sL(('LLL:EXT:lang/locallang_core.xlf:mess.' . ($elInfo[2] == 'copy' ? 'copy' : 'move') . '_' . $type)), $elInfo[0], $elInfo[1])) . ')';
		} else {
			$conf = $loc;
		}
		$editOnClick = 'if(' . $conf . '){' . $loc . '.location.href=top.TS.PATH_typo3+\'' . $this->clipObj->pasteUrl($table, $uid, 0) . '&redirect=\'+top.rawurlencode(' . $this->frameLocation(($loc . '.document')) . '.pathname+' . $this->frameLocation(($loc . '.document')) . '.search); hideCM();}';
		return $this->linkItem($this->label('paste' . $type), $this->excludeIcon(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-paste-' . $type)), $editOnClick . 'return false;');
	}

	/**
	 * Adding CM element for Info
	 *
	 * @param string $table Table name
	 * @param integer $uid UID for the current record.
	 * @return array Item array, element in $menuItems
	 * @internal
	 * @todo Define visibility
	 */
	public function DB_info($table, $uid) {
		return $this->linkItem($this->label('info'), $this->excludeIcon(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-info')), 'top.launchView(\'' . $table . '\', \'' . $uid . '\'); return hideCM();');
	}

	/**
	 * Adding CM element for History
	 *
	 * @param string $table Table name
	 * @param integer $uid UID for the current record.
	 * @return array Item array, element in $menuItems
	 * @internal
	 * @todo Define visibility
	 */
	public function DB_history($table, $uid) {
		$url = 'show_rechis.php?element=' . rawurlencode(($table . ':' . $uid));
		return $this->linkItem($GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->getLL('CM_history')), $this->excludeIcon(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-history-open')), $this->urlRefForCM($url, 'returnUrl'), 0);
	}

	/**
	 * Adding CM element for Permission setting
	 *
	 * @param string $table Table name
	 * @param integer $uid UID for the current record.
	 * @param array $rec The "pages" record with "perms_*" fields inside.
	 * @return array Item array, element in $menuItems
	 * @internal
	 * @todo Define visibility
	 */
	public function DB_perms($table, $uid, $rec) {
		if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('perm')) {
			return '';
		}
		$url = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('perm') . 'mod1/index.php?id=' . $uid . ($rec['perms_userid'] == $GLOBALS['BE_USER']->user['uid'] || $GLOBALS['BE_USER']->isAdmin() ? '&return_id=' . $uid . '&edit=1' : '');
		return $this->linkItem($GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->getLL('CM_perms')), $this->excludeIcon(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-status-locked')), $this->urlRefForCM($url), 0);
	}

	/**
	 * Adding CM element for DBlist
	 *
	 * @param string $table Table name
	 * @param integer $uid UID for the current record.
	 * @param array $rec Record of the element (needs "pid" field if not pages-record)
	 * @return array Item array, element in $menuItems
	 * @internal
	 * @todo Define visibility
	 */
	public function DB_db_list($table, $uid, $rec) {
		$urlParams = array();
		$urlParams['id'] = $table == 'pages' ? $uid : $rec['pid'];
		$urlParams['table'] = $table == 'pages' ? '' : $table;
		$url = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('web_list', $urlParams, '', TRUE);
		return $this->linkItem($GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->getLL('CM_db_list')), $this->excludeIcon(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-system-list-open')), 'top.nextLoadModuleUrl=\'' . $url . '\';top.goToModule(\'web_list\', 1);', 0);
	}

	/**
	 * Adding CM element for Moving wizard
	 *
	 * @param string $table Table name
	 * @param integer $uid UID for the current record.
	 * @param array $rec Record. Needed for tt-content elements which will have the sys_language_uid sent
	 * @return array Item array, element in $menuItems
	 * @internal
	 * @todo Define visibility
	 */
	public function DB_moveWizard($table, $uid, $rec) {
		// Hardcoded field for tt_content elements.
		$url = 'move_el.php?table=' . $table . '&uid=' . $uid . ($table == 'tt_content' ? '&sys_language_uid=' . intval($rec['sys_language_uid']) : '');
		return $this->linkItem($GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->getLL('CM_moveWizard' . ($table == 'pages' ? '_page' : ''))), $this->excludeIcon(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-' . ($table === 'pages' ? 'page' : 'document') . '-move')), $this->urlRefForCM($url, 'returnUrl'), 0);
	}

	/**
	 * Adding CM element for Create new wizard (either db_new.php or sysext/cms/layout/db_new_content_el.php or custom wizard)
	 *
	 * @param string $table Table name
	 * @param integer $uid UID for the current record.
	 * @param array $rec Record.
	 * @return array Item array, element in $menuItems
	 * @internal
	 * @todo Define visibility
	 */
	public function DB_newWizard($table, $uid, $rec) {
		//  If mod.web_list.newContentWiz.overrideWithExtension is set, use that extension's create new content wizard instead:
		$tmpTSc = \TYPO3\CMS\Backend\Utility\BackendUtility::getModTSconfig($this->pageinfo['uid'], 'mod.web_list');
		$tmpTSc = $tmpTSc['properties']['newContentWiz.']['overrideWithExtension'];
		$newContentWizScriptPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($tmpTSc) ? \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($tmpTSc) . 'mod1/db_new_content_el.php' : 'sysext/cms/layout/db_new_content_el.php';
		$url = $table == 'pages' || !\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('cms') ? 'db_new.php?id=' . $uid . '&pagesOnly=1' : $newContentWizScriptPath . '?id=' . $rec['pid'] . '&sys_language_uid=' . intval($rec['sys_language_uid']);
		return $this->linkItem($GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->getLL('CM_newWizard')), $this->excludeIcon(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-' . ($table === 'pages' ? 'page' : 'document') . '-new')), $this->urlRefForCM($url, 'returnUrl'), 0);
	}

	/**
	 * Adding CM element for Editing of the access related fields of a table (disable, starttime, endtime, fe_groups)
	 *
	 * @param string $table Table name
	 * @param integer $uid UID for the current record.
	 * @return array Item array, element in $menuItems
	 * @internal
	 * @todo Define visibility
	 */
	public function DB_editAccess($table, $uid) {
		$addParam = '&columnsOnly=' . rawurlencode((implode(',', $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']) . ($table == 'pages' ? ',extendToSubpages' : '')));
		$url = 'alt_doc.php?edit[' . $table . '][' . $uid . ']=edit' . $addParam;
		return $this->linkItem($GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->getLL('CM_editAccess')), $this->excludeIcon(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-edit-access')), $this->urlRefForCM($url, 'returnUrl'), 1);
	}

	/**
	 * Adding CM element for edit page properties
	 *
	 * @param integer $uid page uid to edit (PID)
	 * @return array Item array, element in $menuItems
	 * @internal
	 * @todo Define visibility
	 */
	public function DB_editPageProperties($uid) {
		$url = 'alt_doc.php?edit[pages][' . $uid . ']=edit';
		return $this->linkItem($GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->getLL('CM_editPageProperties')), $this->excludeIcon(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-page-open')), $this->urlRefForCM($url, 'returnUrl'), 1);
	}

	/**
	 * Adding CM element for regular editing of the element!
	 *
	 * @param string $table Table name
	 * @param integer $uid UID for the current record.
	 * @return array Item array, element in $menuItems
	 * @internal
	 * @todo Define visibility
	 */
	public function DB_edit($table, $uid) {
		// If another module was specified, replace the default Page module with the new one
		$newPageModule = trim($GLOBALS['BE_USER']->getTSConfigVal('options.overridePageModule'));
		$pageModule = \TYPO3\CMS\Backend\Utility\BackendUtility::isModuleSetInTBE_MODULES($newPageModule) ? $newPageModule : 'web_layout';
		$editOnClick = '';
		$loc = 'top.content.list_frame';
		$addParam = '';
		$theIcon = 'actions-document-open';
		if ($this->iParts[0] == 'pages' && $this->iParts[1] && $GLOBALS['BE_USER']->check('modules', $pageModule)) {
			$theIcon = 'actions-page-open';
			$this->editPageIconSet = 1;
			if ($GLOBALS['BE_USER']->uc['classicPageEditMode'] || !\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('cms')) {
				$addParam = '&editRegularContentFromId=' . intval($this->iParts[1]);
			} else {
				$editOnClick = 'if(' . $loc . '){' . $loc . '.location.href=top.TS.PATH_typo3+\'alt_doc.php?returnUrl=\'+top.rawurlencode(' . $this->frameLocation(($loc . '.document')) . '.pathname+' . $this->frameLocation(($loc . '.document')) . '.search)+\'&edit[' . $table . '][' . $uid . ']=edit' . $addParam . '\';}';
			}
		}
		if (!$editOnClick) {
			$editOnClick = 'if(' . $loc . '){' . $loc . '.location.href=top.TS.PATH_typo3+\'alt_doc.php?returnUrl=\'+top.rawurlencode(' . $this->frameLocation(($loc . '.document')) . '.pathname+' . $this->frameLocation(($loc . '.document')) . '.search)+\'&edit[' . $table . '][' . $uid . ']=edit' . $addParam . '\';}';
		}
		return $this->linkItem($this->label('edit'), $this->excludeIcon(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon($theIcon)), $editOnClick . 'return hideCM();');
	}

	/**
	 * Adding CM element for regular Create new element
	 *
	 * @param string $table Table name
	 * @param integer $uid UID for the current record.
	 * @return array Item array, element in $menuItems
	 * @internal
	 * @todo Define visibility
	 */
	public function DB_new($table, $uid) {
		$editOnClick = '';
		$loc = 'top.content.list_frame';
		$editOnClick = 'if(' . $loc . '){' . $loc . '.location.href=top.TS.PATH_typo3+\'' . ($this->listFrame ? 'alt_doc.php?returnUrl=\'+top.rawurlencode(' . $this->frameLocation(($loc . '.document')) . '.pathname+' . $this->frameLocation(($loc . '.document')) . '.search)+\'&edit[' . $table . '][-' . $uid . ']=new\'' : 'db_new.php?id=' . intval($uid) . '\'') . ';}';
		return $this->linkItem($this->label('new'), $this->excludeIcon(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-' . ($table === 'pages' ? 'page' : 'document') . '-new')), $editOnClick . 'return hideCM();');
	}

	/**
	 * Adding CM element for Delete
	 *
	 * @param string $table Table name
	 * @param integer $uid UID for the current record.
	 * @param array $elInfo Label for including in the confirmation message, EXT:lang/locallang_core.xlf:mess.delete
	 * @return array Item array, element in $menuItems
	 * @internal
	 * @todo Define visibility
	 */
	public function DB_delete($table, $uid, $elInfo) {
		$editOnClick = '';
		$loc = 'top.content.list_frame';
		if ($GLOBALS['BE_USER']->jsConfirmation(4)) {
			$conf = 'confirm(' . $GLOBALS['LANG']->JScharCode((sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:mess.delete'), $elInfo[0]) . \TYPO3\CMS\Backend\Utility\BackendUtility::referenceCount($table, $uid, ' (There are %s reference(s) to this record!)') . \TYPO3\CMS\Backend\Utility\BackendUtility::translationCount($table, $uid, (' ' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.translationsOfRecord'))))) . ')';
		} else {
			$conf = '1==1';
		}
		$editOnClick = 'if(' . $loc . ' && ' . $conf . ' ){' . $loc . '.location.href=top.TS.PATH_typo3+\'tce_db.php?redirect=\'+top.rawurlencode(' . $this->frameLocation(($loc . '.document')) . '.pathname+' . $this->frameLocation(($loc . '.document')) . '.search)+\'' . '&cmd[' . $table . '][' . $uid . '][delete]=1&prErr=1&vC=' . $GLOBALS['BE_USER']->veriCode() . \TYPO3\CMS\Backend\Utility\BackendUtility::getUrlToken('tceAction') . '\';}hideCM();top.nav.refresh.defer(500, top.nav);';
		return $this->linkItem($this->label('delete'), $this->excludeIcon(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-edit-delete')), $editOnClick . 'return false;');
	}

	/**
	 * Adding CM element for View Page
	 *
	 * @param integer $id Page uid (PID)
	 * @param string $anchor Anchor, if any
	 * @return array Item array, element in $menuItems
	 * @internal
	 * @todo Define visibility
	 */
	public function DB_view($id, $anchor = '') {
		return $this->linkItem($this->label('view'), $this->excludeIcon(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-view')), \TYPO3\CMS\Backend\Utility\BackendUtility::viewOnClick($id, $this->PH_backPath, \TYPO3\CMS\Backend\Utility\BackendUtility::BEgetRootLine($id), $anchor) . 'return hideCM();');
	}

	/**
	 * Adding element for setting temporary mount point.
	 *
	 * @param integer $page_id Page uid (PID)
	 * @return array Item array, element in $menuItems
	 * @internal
	 * @todo Define visibility
	 */
	public function DB_tempMountPoint($page_id) {
		return $this->linkItem($this->label('tempMountPoint'), $this->excludeIcon(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('apps-pagetree-page-mountpoint')), 'if (top.content.nav_frame) {
				var node = top.TYPO3.Backend.NavigationContainer.PageTree.getSelected();
				if (node === null) {
					return false;
				}

				var useNode = {
					attributes: {
						nodeData: {
							id: ' . intval($page_id) . '
						}
					}
				};

				node.ownerTree.commandProvider.mountAsTreeRoot(useNode, node.ownerTree);
			}
			return hideCM();
			');
	}

	/**
	 * Adding CM element for hide/unhide of the input record
	 *
	 * @param string $table Table name
	 * @param array $rec Record array
	 * @param string $hideField Name of the hide field
	 * @return array Item array, element in $menuItems
	 * @internal
	 * @todo Define visibility
	 */
	public function DB_hideUnhide($table, $rec, $hideField) {
		return $this->DB_changeFlag($table, $rec, $hideField, $this->label(($rec[$hideField] ? 'un' : '') . 'hide'), 'hide');
	}

	/**
	 * Adding CM element for a flag field of the input record
	 *
	 * @param string $table Table name
	 * @param array $rec Record array
	 * @param string $flagField Name of the flag field
	 * @param string $title Menu item Title
	 * @param string $name Name of the item used for icons and labels
	 * @param string $iconRelPath Icon path relative to typo3/ folder
	 * @return array Item array, element in $menuItems
	 * @todo Define visibility
	 */
	public function DB_changeFlag($table, $rec, $flagField, $title, $name, $iconRelPath = 'gfx/') {
		$uid = $rec['_ORIG_uid'] ? $rec['_ORIG_uid'] : $rec['uid'];
		$editOnClick = '';
		$loc = 'top.content.list_frame';
		$editOnClick = 'if(' . $loc . '){' . $loc . '.location.href=top.TS.PATH_typo3+\'tce_db.php?redirect=\'' . '+top.rawurlencode(' . $this->frameLocation(($loc . '.document')) . '.pathname+' . $this->frameLocation(($loc . '.document')) . '.search)+\'' . '&data[' . $table . '][' . $uid . '][' . $flagField . ']=' . ($rec[$flagField] ? 0 : 1) . '&prErr=1&vC=' . $GLOBALS['BE_USER']->veriCode() . \TYPO3\CMS\Backend\Utility\BackendUtility::getUrlToken('tceAction') . '\';}hideCM();top.nav.refresh.defer(500, top.nav);';
		return $this->linkItem($title, $this->excludeIcon(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-edit-' . ($rec[$flagField] ? 'un' : '') . 'hide')), $editOnClick . 'return false;', 1);
	}

	/***************************************
	 *
	 * FILE
	 *
	 ***************************************/
	/**
	 * Make 1st level clickmenu:
	 *
	 * @param string $combinedIdentifier The combined identifier
	 * @return string HTML content
	 * @see \TYPO3\CMS\Core\Resource\ResourceFactory::retrieveFileOrFolderObject()
	 * @todo Define visibility
	 */
	public function printFileClickMenu($combinedIdentifier) {
		$menuItems = array();
		$combinedIdentifier = rawurldecode($combinedIdentifier);
		$fileObject = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()
				->retrieveFileOrFolderObject($combinedIdentifier);
		if ($fileObject) {
			$folder = FALSE;
			$isStorageRoot = FALSE;
			$isOnline = TRUE;
			$userMayViewStorage = FALSE;
			$userMayEditStorage = FALSE;
			$identifier = $fileObject->getCombinedIdentifier();
			if ($fileObject instanceof \TYPO3\CMS\Core\Resource\Folder) {
				$icon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForFile('folder', array(
					'class' => 'absmiddle',
					'title' => htmlspecialchars($fileObject->getName())
				));
				$folder = TRUE;
				if ($fileObject->getIdentifier() === $fileObject->getStorage()->getRootLevelFolder()->getIdentifier()) {
					$isStorageRoot = TRUE;
					if ($GLOBALS['BE_USER']->check('tables_select', 'sys_file_storage')) {
						$userMayViewStorage = TRUE;
					}
					if ($GLOBALS['BE_USER']->check('tables_modify', 'sys_file_storage')) {
						$userMayEditStorage = TRUE;
					}
				}
				if (!$fileObject->getStorage()->isOnline()) {
					$isOnline = FALSE;
				}
			} else {
				$icon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForFile($fileObject->getExtension(), array(
					'class' => 'absmiddle',
					'title' => htmlspecialchars($fileObject->getName() . ' (' . GeneralUtility::formatSize($fileObject->getSize()) . ')')
				));
			}
			// Hide
			if (!in_array('hide', $this->disabledItems) && $isStorageRoot && $userMayEditStorage) {
				$record = BackendUtility::getRecord('sys_file_storage', $fileObject->getStorage()->getUid());
				$menuItems['hide'] = $this->DB_changeFlag(
					'sys_file_storage',
					$record,
					'is_online',
					$this->label($record['is_online'] ? 'offline' : 'online'),
					'hide'
				);
			}
			// Edit
			if (!in_array('edit', $this->disabledItems)) {
				if (!$folder && GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'], $fileObject->getExtension())) {
					$menuItems['edit'] = $this->FILE_launch($identifier, 'file_edit.php', 'edit', 'edit_file.gif');
				} elseif ($isStorageRoot && $userMayEditStorage) {
					$menuItems['edit'] = $this->DB_edit('sys_file_storage', $fileObject->getStorage()->getUid());
				}
			}
			// Rename
			if (!in_array('rename', $this->disabledItems) && !$isStorageRoot) {
				$menuItems['rename'] = $this->FILE_launch($identifier, 'file_rename.php', 'rename', 'rename.gif');
			}
			// Upload
			if (!in_array('upload', $this->disabledItems) && $folder && $isOnline) {
				$menuItems['upload'] = $this->FILE_upload($identifier);
			}
			// New
			if (!in_array('new', $this->disabledItems) && $folder && $isOnline) {
				$menuItems['new'] = $this->FILE_launch($identifier, 'file_newfolder.php', 'new', 'new_file.gif');
			}
			// Info
			if (!in_array('info', $this->disabledItems)) {
				if ($isStorageRoot && $userMayViewStorage) {
					$menuItems['info'] = $this->DB_info('sys_file_storage', $fileObject->getStorage()->getUid());
				} elseif (!$folder) {
					$menuItems['info'] = $this->fileInfo($identifier);
				}
			}
			$menuItems[] = 'spacer';
			// Copy:
			if (!in_array('copy', $this->disabledItems) && !$isStorageRoot) {
				$menuItems['copy'] = $this->FILE_copycut($identifier, 'copy');
			}
			// Cut:
			if (!in_array('cut', $this->disabledItems) && !$isStorageRoot) {
				$menuItems['cut'] = $this->FILE_copycut($identifier, 'cut');
			}
			// Paste:
			$elFromAllTables = count($this->clipObj->elFromTable('_FILE'));
			if (!in_array('paste', $this->disabledItems) && $elFromAllTables && $folder) {
				$elArr = $this->clipObj->elFromTable('_FILE');
				$selItem = reset($elArr);
				$elInfo = array(
					basename($selItem),
					basename($path),
					$this->clipObj->currentMode()
				);
				$menuItems['pasteinto'] = $this->FILE_paste($identifier, $selItem, $elInfo);
			}
			$menuItems[] = 'spacer';
			// Delete:
			if (!in_array('delete', $this->disabledItems)) {
				if ($isStorageRoot && $userMayEditStorage) {
					$elInfo = array(GeneralUtility::fixed_lgd_cs($fileObject->getStorage()->getName(), $GLOBALS['BE_USER']->uc['titleLen']));
					$menuItems['delete'] = $this->DB_delete('sys_file_storage', $fileObject->getStorage()->getUid(), $elInfo);
				} elseif (!$isStorageRoot) {
					$menuItems['delete'] = $this->FILE_delete($identifier);
				}
			}
		}
		// Adding external elements to the menuItems array
		$menuItems = $this->processingByExtClassArray($menuItems, $identifier, 0);
		// Processing by external functions?
		$menuItems = $this->externalProcessingOfFileMenuItems($menuItems);
		// Return the printed elements:
		return $this->printItems($menuItems, $icon . $fileObject->getName());
	}

	/**
	 * Processing the $menuItems array (for extension classes) (FILES)
	 *
	 * @param array $menuItems Array for manipulation.
	 * @return array Processed $menuItems array
	 * @todo Define visibility
	 */
	public function externalProcessingOfFileMenuItems($menuItems) {
		return $menuItems;
	}

	/**
	 * Multi-function for adding an entry to the $menuItems array
	 *
	 * @param string $path Path to the file/directory (target)
	 * @param string $script Script (eg. file_edit.php) to pass &target= to
	 * @param string $type "type" is the code which fetches the correct label for the element from "cm.
	 * @param string $image icon image-filename from "gfx/" (12x12 icon)
	 * @param boolean $noReturnUrl If set, the return URL parameter will not be set in the link
	 * @return array Item array, element in $menuItems
	 * @internal
	 * @todo Define visibility
	 */
	public function FILE_launch($path, $script, $type, $image, $noReturnUrl = FALSE) {
		$loc = 'top.content.list_frame';
		$editOnClick = 'if(' . $loc . '){' . $loc . '.location.href=top.TS.PATH_typo3+\'' . $script . '?target=' . rawurlencode($path) . ($noReturnUrl ? '\'' : '&returnUrl=\'+top.rawurlencode(' . $this->frameLocation(($loc . '.document')) . '.pathname+' . $this->frameLocation(($loc . '.document')) . '.search)') . ';}';
		return $this->linkItem($this->label($type), $this->excludeIcon('<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->PH_backPath, ('gfx/' . $image), 'width="12" height="12"') . ' alt="" />'), $editOnClick . 'top.nav.refresh();return hideCM();');
	}

	/**
	 * function for adding an upload entry to the $menuItems array
	 *
	 * @param string $path Path to the file/directory (target)
	 * @return array Item array, element in $menuItems
	 * @internal
	 * @todo Define visibility
	 */
	public function FILE_upload($path) {
		$script = 'file_upload.php';
		$type = 'upload';
		$image = 'upload.gif';
		return $this->FILE_launch($path, $script, $type, $image, TRUE);
	}

	/**
	 * Returns element for copy or cut of files.
	 *
	 * @param string $path Path to the file/directory (target)
	 * @param string $type Type: "copy" or "cut
	 * @return array Item array, element in $menuItems
	 * @internal
	 * @todo Define visibility
	 */
	public function FILE_copycut($path, $type) {
		// Pseudo table name for use in the clipboard.
		$table = '_FILE';
		$uid = GeneralUtility::shortmd5($path);
		if ($this->clipObj->current == 'normal') {
			$isSel = $this->clipObj->isSelected($table, $uid);
		}
		$addParam = array();
		if ($this->listFrame) {
			$addParam['reloadListFrame'] = $this->alwaysContentFrame ? 2 : 1;
		}
		return $this->linkItem($this->label($type), $this->excludeIcon(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-edit-' . $type . ($isSel === $type ? '-release' : ''))), 'top.loadTopMenu(\'' . $this->clipObj->selUrlFile($path, ($type == 'copy' ? 1 : 0), ($isSel == $type), $addParam) . '\');return false;');
	}

	/**
	 * Creates element for deleting of target
	 *
	 * @param string $path Path to the file/directory (target)
	 * @return array Item array, element in $menuItems
	 * @internal
	 * @todo Define visibility
	 */
	public function FILE_delete($path) {
		$editOnClick = '';
		$loc = 'top.content.list_frame';
		if ($GLOBALS['BE_USER']->jsConfirmation(4)) {
			$conf = 'confirm(' . $GLOBALS['LANG']->JScharCode((sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:mess.delete'), basename($path)) . \TYPO3\CMS\Backend\Utility\BackendUtility::referenceCount('_FILE', $path, ' (There are %s reference(s) to this file!)'))) . ')';
		} else {
			$conf = '1==1';
		}
		$editOnClick = 'if(' . $loc . ' && ' . $conf . ' ){' . $loc . '.location.href=top.TS.PATH_typo3+\'tce_file.php?redirect=\'+top.rawurlencode(' . $this->frameLocation(($loc . '.document')) . '.pathname+' . $this->frameLocation(($loc . '.document')) . '.search)+\'' . '&file[delete][0][data]=' . rawurlencode($path) . '&vC=' . $GLOBALS['BE_USER']->veriCode() . '\';}hideCM();';
		return $this->linkItem($this->label('delete'), $this->excludeIcon(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-edit-delete')), $editOnClick . 'return false;');
	}

	/**
	 * Creates element for pasting files.
	 *
	 * @param string $path Path to the file/directory (target)
	 * @param string $target target - NOT USED.
	 * @param array $elInfo Various values for the labels.
	 * @return array Item array, element in $menuItems
	 * @internal
	 * @todo Define visibility
	 */
	public function FILE_paste($path, $target, $elInfo) {
		$editOnClick = '';
		$loc = 'top.content.list_frame';
		if ($GLOBALS['BE_USER']->jsConfirmation(2)) {
			$conf = $loc . ' && confirm(' . $GLOBALS['LANG']->JScharCode(sprintf($GLOBALS['LANG']->sL(('LLL:EXT:lang/locallang_core.xlf:mess.' . ($elInfo[2] == 'copy' ? 'copy' : 'move') . '_into')), $elInfo[0], $elInfo[1])) . ')';
		} else {
			$conf = $loc;
		}
		$editOnClick = 'if(' . $conf . '){' . $loc . '.location.href=top.TS.PATH_typo3+\'' . $this->clipObj->pasteUrl('_FILE', $path, 0) . '&redirect=\'+top.rawurlencode(' . $this->frameLocation(($loc . '.document')) . '.pathname+' . $this->frameLocation(($loc . '.document')) . '.search);  }hideCM();top.nav.refresh();';
		return $this->linkItem($this->label('pasteinto'), $this->excludeIcon(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-paste-into')), $editOnClick . 'return false;');
	}

	/**
	 * Adding ClickMenu element for file info
	 *
	 * @param string $identifier The combined identifier of the file.
	 * @return array Item array, element in $menuItems
	 */
	protected function fileInfo($identifier) {
		return $this->DB_info('_FILE', $identifier);
	}

	/***************************************
	 *
	 * DRAG AND DROP
	 *
	 ***************************************/
	/**
	 * Make 1st level clickmenu:
	 *
	 * @param string $table The absolute path
	 * @param integer $srcId UID for the current record.
	 * @param integer $dstId Destination ID
	 * @return string HTML content
	 * @todo Define visibility
	 */
	public function printDragDropClickMenu($table, $srcId, $dstId) {
		$menuItems = array();
		// If the drag and drop menu should apply to PAGES use this set of menu items
		if ($table == 'pages') {
			// Move Into:
			$menuItems['movePage_into'] = $this->dragDrop_copymovepage($srcId, $dstId, 'move', 'into');
			// Move After:
			$menuItems['movePage_after'] = $this->dragDrop_copymovepage($srcId, $dstId, 'move', 'after');
			// Copy Into:
			$menuItems['copyPage_into'] = $this->dragDrop_copymovepage($srcId, $dstId, 'copy', 'into');
			// Copy After:
			$menuItems['copyPage_after'] = $this->dragDrop_copymovepage($srcId, $dstId, 'copy', 'after');
		}
		// If the drag and drop menu should apply to FOLDERS use this set of menu items
		if ($table == 'folders') {
			// Move Into:
			$menuItems['moveFolder_into'] = $this->dragDrop_copymovefolder($srcId, $dstId, 'move');
			// Copy Into:
			$menuItems['copyFolder_into'] = $this->dragDrop_copymovefolder($srcId, $dstId, 'copy');
		}
		// Adding external elements to the menuItems array
		$menuItems = $this->processingByExtClassArray($menuItems, 'dragDrop_' . $table, $srcId);
		// to extend this, you need to apply a Context Menu to a "virtual" table called "dragDrop_pages" or similar
		// Processing by external functions?
		$menuItems = $this->externalProcessingOfDBMenuItems($menuItems);
		// Return the printed elements:
		return $this->printItems($menuItems, \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord($table, $this->rec, array('title' => \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle($table, $this->rec, TRUE))));
	}

	/**
	 * Processing the $menuItems array (for extension classes) (DRAG'N DROP)
	 *
	 * @param array $menuItems Array for manipulation.
	 * @return array Processed $menuItems array
	 * @todo Define visibility
	 */
	public function externalProcessingOfDragDropMenuItems($menuItems) {
		return $menuItems;
	}

	/**
	 * Adding CM element for Copying/Moving a Page Into/After from a drag & drop action
	 *
	 * @param integer $srcUid source UID code for the record to modify
	 * @param integer $dstUid destination UID code for the record to modify
	 * @param string $action Action code: either "move" or "copy
	 * @param string $into Parameter code: either "into" or "after
	 * @return array Item array, element in $menuItems
	 * @internal
	 * @todo Define visibility
	 */
	public function dragDrop_copymovepage($srcUid, $dstUid, $action, $into) {
		$negativeSign = $into == 'into' ? '' : '-';
		$editOnClick = '';
		$loc = 'top.content.list_frame';
		$editOnClick = 'if(' . $loc . '){' . $loc . '.document.location=top.TS.PATH_typo3+"tce_db.php?redirect="+top.rawurlencode(' . $this->frameLocation(($loc . '.document')) . '.pathname+' . $this->frameLocation(($loc . '.document')) . '.search)+"' . '&cmd[pages][' . $srcUid . '][' . $action . ']=' . $negativeSign . $dstUid . '&prErr=1&vC=' . $GLOBALS['BE_USER']->veriCode() . \TYPO3\CMS\Backend\Utility\BackendUtility::getUrlToken('tceAction') . '";}hideCM();top.nav.refresh();';
		return $this->linkItem($this->label($action . 'Page_' . $into), $this->excludeIcon(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-paste-' . $into)), $editOnClick . 'return false;', 0);
	}

	/**
	 * Adding CM element for Copying/Moving a Folder Into from a drag & drop action
	 *
	 * @param string $srcPath source path for the record to modify
	 * @param string $dstPath destination path for the records to modify
	 * @param string $action Action code: either "move" or "copy
	 * @return array Item array, element in $menuItems
	 * @internal
	 * @todo Define visibility
	 */
	public function dragDrop_copymovefolder($srcPath, $dstPath, $action) {
		$editOnClick = '';
		$loc = 'top.content.list_frame';
		$editOnClick = 'if(' . $loc . '){' . $loc . '.document.location=top.TS.PATH_typo3+"tce_file.php?redirect="+top.rawurlencode(' . $this->frameLocation(($loc . '.document')) . '.pathname+' . $this->frameLocation(($loc . '.document')) . '.search)+"' . '&file[' . $action . '][0][data]=' . $srcPath . '&file[' . $action . '][0][target]=' . $dstPath . '&prErr=1&vC=' . $GLOBALS['BE_USER']->veriCode() . '";}hideCM();top.nav.refresh();';
		return $this->linkItem($this->label($action . 'Folder_into'), $this->excludeIcon(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('apps-pagetree-drag-move-into')), $editOnClick . 'return false;', 0);
	}

	/***************************************
	 *
	 * COMMON
	 *
	 **************************************/
	/**
	 * Prints the items from input $menuItems array - as JS section for writing to the div-layers.
	 *
	 * @param array $menuItems Array
	 * @param string $item HTML code for the element which was clicked - shown in the end of the horizontal menu in topframe after the close-button.
	 * @return string HTML code
	 * @todo Define visibility
	 */
	public function printItems($menuItems, $item) {
		$out = '';
		// Enable/Disable items:
		$menuItems = $this->enableDisableItems($menuItems);
		// Clean up spacers:
		$menuItems = $this->cleanUpSpacers($menuItems);
		// Adding JS part:
		$out .= $this->printLayerJScode($menuItems);
		// Return the content
		return $out;
	}

	/**
	 * Create the JavaScript section
	 *
	 * @param array $menuItems The $menuItems array to print
	 * @return string The JavaScript section which will print the content of the CM to the div-layer in the target frame.
	 * @todo Define visibility
	 */
	public function printLayerJScode($menuItems) {
		$script = '';
		// Clipboard must not be submitted - then it's probably a copy/cut situation.
		if ($this->isCMlayers()) {
			$frameName = '.' . ($this->listFrame ? 'list_frame' : 'nav_frame');
			if ($this->alwaysContentFrame) {
				$frameName = '';
			}
			// Create the table displayed in the clickmenu layer:
			$CMtable = '
				<table border="0" cellpadding="0" cellspacing="0" class="typo3-CSM">
					' . implode('', $this->menuItemsForClickMenu($menuItems)) . '
				</table>';
			// Wrap the inner table in another table to create outer border:
			$CMtable = $this->wrapColorTableCM($CMtable);
			// Set back path place holder to real back path
			$CMtable = str_replace($this->PH_backPath, $this->backPath, $CMtable);
			if ($this->ajax) {
				$innerXML = '<data><clickmenu><htmltable><![CDATA[' . $CMtable . ']]></htmltable><cmlevel>' . $this->cmLevel . '</cmlevel></clickmenu></data>';
				return $innerXML;
			} else {
				// Create JavaScript section:
				$script = $GLOBALS['TBE_TEMPLATE']->wrapScriptTags('

				if (top.content && top.content' . $frameName . ' && top.content' . $frameName . '.Clickmenu) {
					top.content' . $frameName . '.Clickmenu.populateData(unescape("' . GeneralUtility::rawurlencodeJS($CMtable) . '"),' . $this->cmLevel . ');
				}
				hideCM();
				');
				return $script;
			}
		}
	}

	/**
	 * Wrapping the input string in a table with background color 4 and a black border style.
	 * For the pop-up menu
	 *
	 * @param string $str HTML content to wrap in table.
	 * @return string
	 * @todo Define visibility
	 */
	public function wrapColorTableCM($str) {
		return '<div class="typo3-CSM-wrapperCM">
			' . $str . '
			</div>';
	}

	/**
	 * Traverses the menuItems and generates an output array for implosion in the topframe horizontal menu
	 *
	 * @param array $menuItems Array
	 * @return array Array of menu items for top frame.
	 * @todo Define visibility
	 */
	public function menuItemsForTopFrame($menuItems) {
		$out = array();
		foreach ($menuItems as $i) {
			// IF the topbar is the ONLY means of the click menu, then items normally disabled from
			// the top menu will appear anyways IF they are disabled with a "1" (2+ will still disallow
			// them in the topbar)
			if ($i[4] == 1 && !$GLOBALS['SOBE']->doc->isCMlayers()) {
				$i[4] = 0;
			}
			if (is_array($i) && !$i[4]) {
				$out[] = $i[0];
			}
		}
		return $out;
	}

	/**
	 * Traverses the menuItems and generates an output array for implosion in the CM div-layers table.
	 *
	 * @param array $menuItems Array
	 * @return array array for implosion in the CM div-layers table.
	 * @todo Define visibility
	 */
	public function menuItemsForClickMenu($menuItems) {
		$out = array();
		foreach ($menuItems as $cc => $i) {
			// MAKE horizontal spacer
			if (is_string($i) && $i == 'spacer') {
				$out[] = '
					<tr class="bgColor2">
						<td colspan="2"><img src="clear.gif" width="1" height="1" alt="" /></td>
					</tr>';
			} else {
				// Just make normal element:
				$onClick = $i[3];
				$onClick = preg_replace('/return[[:space:]]+hideCM\\(\\)[[:space:]]*;/i', '', $onClick);
				$onClick = preg_replace('/return[[:space:]]+false[[:space:]]*;/i', '', $onClick);
				$onClick = preg_replace('/hideCM\\(\\);/i', '', $onClick);
				if (!$i[5]) {
					$onClick .= 'Clickmenu.hideAll();';
				}
				$CSM = ' oncontextmenu="' . htmlspecialchars($onClick) . ';return false;"';
				$out[] = '
					<tr class="typo3-CSM-itemRow" onclick="' . htmlspecialchars($onClick) . '" onmouseover="this.bgColor=\'' . $GLOBALS['TBE_TEMPLATE']->bgColor5 . '\';" onmouseout="this.bgColor=\'\';"' . $CSM . '>
						' . (!$this->leftIcons ? '<td class="typo3-CSM-item">' . $i[1] . '</td><td align="center">' . $i[2] . '</td>' : '<td align="center">' . $i[2] . '</td><td class="typo3-CSM-item">' . $i[1] . '</td>') . '
					</tr>';
			}
		}
		return $out;
	}

	/**
	 * Adds or inserts a menu item
	 * Can be used to set the position of new menu entries within the list of existing menu entries. Has this syntax: [cmd]:[menu entry key],[cmd].... cmd can be "after", "before" or "top" (or blank/"bottom" which is default). If "after"/"before" then menu items will be inserted after/before the existing entry with [menu entry key] if found. "after-spacer" and "before-spacer" do the same, but inserts before or after an item and a spacer. If not found, the bottom of list. If "top" the items are inserted in the top of the list.
	 *
	 * @param array $menuItems Menu items array
	 * @param array $newMenuItems Menu items array to insert
	 * @param string $position Position command string. Has this syntax: [cmd]:[menu entry key],[cmd].... cmd can be "after", "before" or "top" (or blank/"bottom" which is default). If "after"/"before" then menu items will be inserted after/before the existing entry with [menu entry key] if found. "after-spacer" and "before-spacer" do the same, but inserts before or after an item and a spacer. If not found, the bottom of list. If "top" the items are inserted in the top of the list.
	 * @return array Menu items array, processed.
	 * @todo Define visibility
	 */
	public function addMenuItems($menuItems, $newMenuItems, $position = '') {
		if (is_array($newMenuItems)) {
			if ($position) {
				$posArr = GeneralUtility::trimExplode(',', $position, 1);
				foreach ($posArr as $pos) {
					list($place, $menuEntry) = GeneralUtility::trimExplode(':', $pos, 1);
					list($place, $placeExtra) = GeneralUtility::trimExplode('-', $place, 1);
					// Bottom
					$pointer = count($menuItems);
					$found = FALSE;
					if ($place) {
						switch (strtolower($place)) {
							case 'after':

							case 'before':
								if ($menuEntry) {
									$p = 1;
									reset($menuItems);
									while (TRUE) {
										if (!strcmp(key($menuItems), $menuEntry)) {
											$pointer = $p;
											$found = TRUE;
											break;
										}
										if (!next($menuItems)) {
											break;
										}
										$p++;
									}
									if (!$found) {
										break;
									}
									if ($place == 'before') {
										$pointer--;
										if ($placeExtra == 'spacer' and prev($menuItems) == 'spacer') {
											$pointer--;
										}
									} elseif ($place == 'after') {
										if ($placeExtra == 'spacer' and next($menuItems) == 'spacer') {
											$pointer++;
										}
									}
								}
								break;
							default:
								if (strtolower($place) == 'top') {
									$pointer = 0;
								} else {
									$pointer = count($menuItems);
								}
								$found = TRUE;
								break;
						}
					}
					if ($found) {
						break;
					}
				}
			}
			$pointer = max(0, $pointer);
			$menuItemsBefore = array_slice($menuItems, 0, $pointer ? $pointer : 0);
			$menuItemsAfter = array_slice($menuItems, $pointer);
			$menuItems = $menuItemsBefore + $newMenuItems + $menuItemsAfter;
		}
		return $menuItems;
	}

	/**
	 * Creating an array with various elements for the clickmenu entry
	 *
	 * @param string $str The label, htmlspecialchar'ed already
	 * @param string $icon <img>-tag for the icon
	 * @param string $onClick JavaScript onclick event for label/icon
	 * @param boolean $onlyCM ==1 and the element will NOT appear in clickmenus in the topframe (unless clickmenu is totally unavailable)! ==2 and the item will NEVER appear in top frame. (This is mostly for "less important" options since the top frame is not capable of holding so many elements horizontally)
	 * @param boolean $dontHide If set, the clickmenu layer will not hide itself onclick - used for secondary menus to appear...
	 * @return array $menuItem entry with 6 numerical entries: [0] is the HTML for display of the element with link and icon an mouseover etc., [1]-[5] is simply the input params passed through!
	 * @todo Define visibility
	 */
	public function linkItem($str, $icon, $onClick, $onlyCM = 0, $dontHide = 0) {
		$this->elCount++;
		if ($this->ajax) {
			$onClick = str_replace('top.loadTopMenu', 'showClickmenu_raw', $onClick);
		}
		return array(
			\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('empty-empty', array(
				'class' => 'c-roimg',
				'id' => ('roimg_' . $this->elCount)
			)) . '<a href="#" onclick="' . htmlspecialchars($onClick) . '" onmouseover="mo(' . $this->elCount . ');" onmouseout="mout(' . $this->elCount . ');">' . $str . $icon . '</a>',
			$str,
			$icon,
			$onClick,
			$onlyCM,
			$dontHide
		);
	}

	/**
	 * Returns the input string IF not a user setting has disabled display of icons.
	 *
	 * @param string $iconCode The icon-image tag
	 * @return string The icon-image tag prefixed with space char IF the icon should be printed at all due to user settings
	 * @todo Define visibility
	 */
	public function excludeIcon($iconCode) {
		return $GLOBALS['BE_USER']->uc['noMenuMode'] && strcmp($GLOBALS['BE_USER']->uc['noMenuMode'], 'icons') ? '' : ' ' . $iconCode;
	}

	/**
	 * Enabling / Disabling items based on list provided from GET var ($this->iParts[3])
	 *
	 * @param array $menuItems Menu items array
	 * @return array Menu items array, processed.
	 * @todo Define visibility
	 */
	public function enableDisableItems($menuItems) {
		if ($this->iParts[3]) {
			// Detect "only" mode: (only showing listed items)
			if (substr($this->iParts[3], 0, 1) == '+') {
				$this->iParts[3] = substr($this->iParts[3], 1);
				$only = TRUE;
			} else {
				$only = FALSE;
			}
			// Do filtering:
			// Transfer ONLY elements which are mentioned (or are spacers)
			if ($only) {
				$newMenuArray = array();
				foreach ($menuItems as $key => $value) {
					if (GeneralUtility::inList($this->iParts[3], $key) || is_string($value) && $value == 'spacer') {
						$newMenuArray[$key] = $value;
					}
				}
				$menuItems = $newMenuArray;
			} else {
				// Traverse all elements except those listed (just unsetting them):
				$elements = GeneralUtility::trimExplode(',', $this->iParts[3], 1);
				foreach ($elements as $value) {
					unset($menuItems[$value]);
				}
			}
		}
		// Return processed menu items:
		return $menuItems;
	}

	/**
	 * Clean up spacers; Will remove any spacers in the start/end of menu items array plus any duplicates.
	 *
	 * @param array $menuItems Menu items array
	 * @return array Menu items array, processed.
	 * @todo Define visibility
	 */
	public function cleanUpSpacers($menuItems) {
		// Remove doubles:
		$prevItemWasSpacer = FALSE;
		foreach ($menuItems as $key => $value) {
			if (is_string($value) && $value == 'spacer') {
				if ($prevItemWasSpacer) {
					unset($menuItems[$key]);
				}
				$prevItemWasSpacer = TRUE;
			} else {
				$prevItemWasSpacer = FALSE;
			}
		}
		// Remove first:
		reset($menuItems);
		$key = key($menuItems);
		$value = current($menuItems);
		if (is_string($value) && $value == 'spacer') {
			unset($menuItems[$key]);
		}
		// Remove last:
		end($menuItems);
		$key = key($menuItems);
		$value = current($menuItems);
		if (is_string($value) && $value == 'spacer') {
			unset($menuItems[$key]);
		}
		// Return processed menu items:
		return $menuItems;
	}

	/**
	 * Get label from locallang_core.xlf:cm.*
	 *
	 * @param string $label The "cm."-suffix to get.
	 * @return string
	 * @todo Define visibility
	 */
	public function label($label) {
		return $GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:cm.' . $label, 1));
	}

	/**
	 * Returns TRUE if there should be writing to the div-layers (commands sent to clipboard MUST NOT write to div-layers)
	 *
	 * @return boolean
	 * @todo Define visibility
	 */
	public function isCMlayers() {
		return !$this->CB;
	}

	/**
	 * Appends ".location" to input string
	 *
	 * @param string $str Input string, probably a JavaScript document reference
	 * @return string
	 * @todo Define visibility
	 */
	public function frameLocation($str) {
		return $str . '.location';
	}

}


?>