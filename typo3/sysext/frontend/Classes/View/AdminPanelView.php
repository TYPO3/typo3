<?php
namespace TYPO3\CMS\Frontend\View;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2013 Jeff Segars <jeff@webempoweredchurch.org>
 *  (c) 2008-2013 David Slayback <dave@webempoweredchurch.org>
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
 *  This copyright notice MUST APPEAR in all copies of the scri.pt!
 ***************************************************************/
/**
 * View class for the admin panel in frontend editing.
 *
 * @author Jeff Segars <jeff@webempoweredchurch.org>
 * @author David Slayback <dave@webempoweredchurch.org>
 * @author Dmitry Dulepov <dmitry@typo3.org>
 */
class AdminPanelView {

	/**
	 * Determines whether the update button should be shown.
	 *
	 * @var 	boolean
	 */
	protected $extNeedUpdate = FALSE;

	/**
	 * Force preview?
	 *
	 * @var boolean
	 */
	protected $ext_forcePreview = FALSE;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->initialize();
	}

	/*****************************************************
	 *
	 * Admin Panel Configuration/Initialization
	 *
	 ****************************************************/
	/**
	 * Initializes settings for the admin panel.
	 *
	 * @return void
	 */
	public function initialize() {
		$this->saveConfigOptions();
		// Setting some values based on the admin panel
		$GLOBALS['TSFE']->forceTemplateParsing = $this->extGetFeAdminValue('tsdebug', 'forceTemplateParsing');
		$GLOBALS['TSFE']->displayEditIcons = $this->extGetFeAdminValue('edit', 'displayIcons');
		$GLOBALS['TSFE']->displayFieldEditIcons = $this->extGetFeAdminValue('edit', 'displayFieldIcons');
		if ($this->extGetFeAdminValue('tsdebug', 'displayQueries')) {
			// Do not override if the value is already set in \TYPO3\CMS\Core\Database\DatabaseConnection
			if ($GLOBALS['TYPO3_DB']->explainOutput == 0) {
				// Enable execution of EXPLAIN SELECT queries
				$GLOBALS['TYPO3_DB']->explainOutput = 3;
			}
		}
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('ADMCMD_editIcons')) {
			$GLOBALS['TSFE']->displayFieldEditIcons = 1;
			$GLOBALS['BE_USER']->uc['TSFE_adminConfig']['edit_editNoPopup'] = 1;
		}
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('ADMCMD_simUser')) {
			$GLOBALS['BE_USER']->uc['TSFE_adminConfig']['preview_simulateUserGroup'] = intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('ADMCMD_simUser'));
			$this->ext_forcePreview = TRUE;
		}
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('ADMCMD_simTime')) {
			$GLOBALS['BE_USER']->uc['TSFE_adminConfig']['preview_simulateDate'] = intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('ADMCMD_simTime'));
			$this->ext_forcePreview = TRUE;
		}
		if ($GLOBALS['TSFE']->forceTemplateParsing) {
			$GLOBALS['TSFE']->set_no_cache('Admin Panel: Force template parsing', TRUE);
		} elseif ($GLOBALS['TSFE']->displayEditIcons) {
			$GLOBALS['TSFE']->set_no_cache('Admin Panel: Display edit icons', TRUE);
		} elseif ($GLOBALS['TSFE']->displayFieldEditIcons) {
			$GLOBALS['TSFE']->set_no_cache('Admin Panel: Display field edit icons', TRUE);
		}
	}

	/**
	 * Obtains header data for the admin panel.
	 *
	 * @return string
	 */
	public function getAdminPanelHeaderData() {
		$result = '';
		// Include BE styles
		if (!empty($GLOBALS['TBE_STYLES']['stylesheets']['admPanel'])) {
			$result = '<link rel="stylesheet" type="text/css" href="' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl($GLOBALS['TBE_STYLES']['stylesheets']['admPanel'])) . '" />';
		}
		return $result;
	}

	/**
	 * Checks if a Admin Panel section ("module") is available for the user. If so, TRUE is returned.
	 *
	 * @param string $key The module key, eg. "edit", "preview", "info" etc.
	 * @return boolean
	 */
	public function isAdminModuleEnabled($key) {
		$result = FALSE;
		// Returns TRUE if the module checked is "preview" and the forcePreview flag is set.
		if ($key == 'preview' && $this->ext_forcePreview) {
			$result = TRUE;
		}
		// If key is not set, only "all" is checked
		if ($GLOBALS['BE_USER']->extAdminConfig['enable.']['all']) {
			$result = TRUE;
		}
		if ($GLOBALS['BE_USER']->extAdminConfig['enable.'][$key]) {
			$result = TRUE;
		}
		return $result;
	}

	/**
	 * Saves any change in settings made in the Admin Panel.
	 * Called from index_ts.php right after access check for the Admin Panel
	 *
	 * @return void
	 */
	public function saveConfigOptions() {
		$input = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('TSFE_ADMIN_PANEL');
		if (is_array($input)) {
			// Setting
			$GLOBALS['BE_USER']->uc['TSFE_adminConfig'] = array_merge(!is_array($GLOBALS['BE_USER']->uc['TSFE_adminConfig']) ? array() : $GLOBALS['BE_USER']->uc['TSFE_adminConfig'], $input);
			// Candidate for \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge() if integer-keys will some day make trouble...
			unset($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['action']);
			// Actions:
			if ($input['action']['clearCache'] && $this->isAdminModuleEnabled('cache')) {
				$GLOBALS['BE_USER']->extPageInTreeInfo = array();
				$theStartId = intval($input['cache_clearCacheId']);
				$GLOBALS['TSFE']->clearPageCacheContent_pidList($GLOBALS['BE_USER']->extGetTreeList($theStartId, $this->extGetFeAdminValue('cache', 'clearCacheLevels'), 0, $GLOBALS['BE_USER']->getPagePermsClause(1)) . $theStartId);
			}
			// Saving
			$GLOBALS['BE_USER']->writeUC();
		}
		$GLOBALS['TT']->LR = $this->extGetFeAdminValue('tsdebug', 'LR');
		if ($this->extGetFeAdminValue('cache', 'noCache')) {
			$GLOBALS['TSFE']->set_no_cache('Admin Panel: No Caching', TRUE);
		}
	}

	/**
	 * Returns the value for a Admin Panel setting. You must specify both the module-key and the internal setting key.
	 *
	 * @param string $sectionName Module key
	 * @param string $val Setting key
	 * @return string The setting value
	 */
	public function extGetFeAdminValue($sectionName, $val = '') {
		// Check if module is enabled.
		if ($this->isAdminModuleEnabled($sectionName)) {
			// Exceptions where the values can be overridden from backend:
			// deprecated
			if ($sectionName . '_' . $val == 'edit_displayIcons' && $GLOBALS['BE_USER']->extAdminConfig['module.']['edit.']['forceDisplayIcons']) {
				return TRUE;
			}
			if ($sectionName . '_' . $val == 'edit_displayFieldIcons' && $GLOBALS['BE_USER']->extAdminConfig['module.']['edit.']['forceDisplayFieldIcons']) {
				return TRUE;
			}
			// Override all settings with user TSconfig
			if ($val && $GLOBALS['BE_USER']->extAdminConfig['override.'][$sectionName . '.'][$val]) {
				return $GLOBALS['BE_USER']->extAdminConfig['override.'][$sectionName . '.'][$val];
			}
			if ($GLOBALS['BE_USER']->extAdminConfig['override.'][$sectionName]) {
				return $GLOBALS['BE_USER']->extAdminConfig['override.'][$sectionName];
			}
			$retVal = $val ? $GLOBALS['BE_USER']->uc['TSFE_adminConfig'][$sectionName . '_' . $val] : 1;
			if ($sectionName == 'preview' && $this->ext_forcePreview) {
				return !$val ? TRUE : $retVal;
			}
			// Regular check:
			// See if the menu is expanded!
			if ($this->isAdminModuleOpen($sectionName)) {
				return $retVal;
			}
		}
	}

	/**
	 * Enables the force preview option.
	 *
	 * @return void
	 */
	public function forcePreview() {
		$this->ext_forcePreview = TRUE;
	}

	/**
	 * Returns TRUE if admin panel module is open
	 *
	 * @param string $pre Module key
	 * @return boolean TRUE, if the admin panel is open for the specified admin panel module key.
	 */
	public function isAdminModuleOpen($pre) {
		return $GLOBALS['BE_USER']->uc['TSFE_adminConfig']['display_top'] && $GLOBALS['BE_USER']->uc['TSFE_adminConfig']['display_' . $pre];
	}

	/*****************************************************
	 *
	 * Admin Panel Rendering
	 *
	 ****************************************************/
	/**
	 * Creates and returns the HTML code for the Admin Panel in the TSFE frontend.
	 *
	 * @return string HTML for the Admin Panel
	 */
	public function display() {
		$GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_tsfe.xlf');
		$moduleContent = '';
		if ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['display_top']) {
			if ($this->isAdminModuleEnabled('preview')) {
				$moduleContent .= $this->getPreviewModule();
			}
			if ($this->isAdminModuleEnabled('cache')) {
				$moduleContent .= $this->getCacheModule();
			}
			if ($this->isAdminModuleEnabled('edit')) {
				$moduleContent .= $this->getEditModule();
			}
			if ($this->isAdminModuleEnabled('tsdebug')) {
				$moduleContent .= $this->getTSDebugModule();
			}
			if ($this->isAdminModuleEnabled('info')) {
				$moduleContent .= $this->getInfoModule();
			}
		}
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_adminpanel.php']['extendAdminPanel'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_adminpanel.php']['extendAdminPanel'] as $classRef) {
				$hookObject = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
				if (!$hookObject instanceof \TYPO3\CMS\Frontend\View\AdminPanelViewHookInterface) {
					throw new \UnexpectedValueException('$hookObject must implement interface TYPO3\\CMS\\Frontend\\View\\AdminPanelViewHookInterface', 1311942539);
				}
				$moduleContent .= $hookObject->extendAdminPanel($moduleContent, $this);
			}
		}
		$row = $this->extGetLL('adminPanelTitle') . ': <span class="typo3-adminPanel-beuser">' . htmlspecialchars($GLOBALS['BE_USER']->user['username']) . '</span>';
		$isVisible = $GLOBALS['BE_USER']->uc['TSFE_adminConfig']['display_top'];
		$cssClassName = 'typo3-adminPanel-panel-' . ($isVisible ? 'open' : 'closed');
		$header = '<tr class="typo3-adminPanel-hRow">' . '<td colspan="2" id="typo3-adminPanel-header" class="' . $cssClassName . '">' . '<span class="typo3-adminPanel-header-title">' . $row . '</span>' . $this->linkSectionHeader('top', '<span class="typo3-adminPanel-header-button"></span>', 'typo3-adminPanel-header-buttonWrapper') . '<input type="hidden" name="TSFE_ADMIN_PANEL[display_top]" value="' . $GLOBALS['BE_USER']->uc['TSFE_adminConfig']['display_top'] . '" /></td>' . '</tr>';
		if ($moduleContent) {
			$footer = '<tr class="typo3-adminPanel-fRow">' . '<td colspan="2" id="typo3-adminPanel-footer">' . ($this->extNeedUpdate ? ' <input class="typo3-adminPanel-update" type="submit" value="' . $this->extGetLL('update') . '" />' : '') . '</td>' . '</tr>';
		} else {
			$footer = '';
		}
		$query = !\TYPO3\CMS\Core\Utility\GeneralUtility::_GET('id') ? '<input type="hidden" name="id" value="' . $GLOBALS['TSFE']->id . '" />' : '';
		// The dummy field is needed for Firefox: to force a page reload on submit
		// with must change the form value with JavaScript (see "onsubmit" attribute of the "form" element")
		$query .= '<input type="hidden" name="TSFE_ADMIN_PANEL[DUMMY]" value="" />';
		foreach (\TYPO3\CMS\Core\Utility\GeneralUtility::_GET() as $key => $value) {
			if ($key != 'TSFE_ADMIN_PANEL') {
				if (is_array($value)) {
					$query .= $this->getHiddenFields($key, $value);
				} else {
					$query .= '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '" />';
				}
			}
		}
		$out = '
<!--
	TYPO3 admin panel start
-->
<a id="TSFE_ADMIN"></a>
<form id="TSFE_ADMIN_PANEL_FORM" name="TSFE_ADMIN_PANEL_FORM" action="' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_SCRIPT')) . '#TSFE_ADMIN" method="get" onsubmit="document.forms.TSFE_ADMIN_PANEL_FORM[\'TSFE_ADMIN_PANEL[DUMMY]\'].value=Math.random().toString().substring(2,8)">' . $query . '<table class="typo3-adminPanel">' . $header . $moduleContent . $footer . '</table></form>';
		if ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['display_top']) {
			$out .= '<script type="text/javascript" src="t3lib/jsfunc.evalfield.js"></script>';
			$out .= '<script type="text/javascript">/*<![CDATA[*/' . \TYPO3\CMS\Core\Utility\GeneralUtility::minifyJavaScript('
				var evalFunc = new evalFunc();
					// TSFEtypo3FormFieldSet()
				function TSFEtypo3FormFieldSet(theField, evallist, is_in, checkbox, checkboxValue) {	//
					var theFObj = new evalFunc_dummy (evallist,is_in, checkbox, checkboxValue);
					var theValue = document.TSFE_ADMIN_PANEL_FORM[theField].value;
					if (checkbox && theValue==checkboxValue) {
						document.TSFE_ADMIN_PANEL_FORM[theField+"_hr"].value="";
						alert(theField);
						document.TSFE_ADMIN_PANEL_FORM[theField+"_cb"].checked = "";
					} else {
						document.TSFE_ADMIN_PANEL_FORM[theField+"_hr"].value = evalFunc.outputObjValue(theFObj, theValue);
						if (document.TSFE_ADMIN_PANEL_FORM[theField+"_cb"]) {
							document.TSFE_ADMIN_PANEL_FORM[theField+"_cb"].checked = "on";
						}
					}
				}
					// TSFEtypo3FormFieldGet()
				function TSFEtypo3FormFieldGet(theField, evallist, is_in, checkbox, checkboxValue, checkbox_off) {	//
					var theFObj = new evalFunc_dummy (evallist,is_in, checkbox, checkboxValue);
					if (checkbox_off) {
						document.TSFE_ADMIN_PANEL_FORM[theField].value=checkboxValue;
					}else{
						document.TSFE_ADMIN_PANEL_FORM[theField].value = evalFunc.evalObjValue(theFObj, document.TSFE_ADMIN_PANEL_FORM[theField+"_hr"].value);
					}
					TSFEtypo3FormFieldSet(theField, evallist, is_in, checkbox, checkboxValue);
				}') . '/*]]>*/</script><script language="javascript" type="text/javascript">' . $this->extJSCODE . '</script>';
		}
		$out .= '<script src="' . \TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl('t3lib/js/adminpanel.js') . '" type="text/javascript"></script><script type="text/javascript">/*<![CDATA[*/' . 'typo3AdminPanel = new TYPO3AdminPanel();typo3AdminPanel.init("typo3-adminPanel-header", "TSFE_ADMIN_PANEL_FORM");' . '/*]]>*/</script>
<!--
	TYPO3 admin panel end
-->
';
		return $out;
	}

	/**
	 * Fetches recursively all GET parameters as hidden fields.
	 * Called from display()
	 *
	 * @param string $key Current key
	 * @param array $val Current value
	 * @return string Hidden fields
	 * @see display()
	 */
	protected function getHiddenFields($key, array $val) {
		$out = '';
		foreach ($val as $k => $v) {
			if (is_array($v)) {
				$out .= $this->getHiddenFields($key . '[' . $k . ']', $v);
			} else {
				$out .= '<input type="hidden" name="' . htmlspecialchars($key) . '[' . htmlspecialchars($k) . ']" value="' . htmlspecialchars($v) . '">' . LF;
			}
		}
		return $out;
	}

	/*****************************************************
	 *
	 * Creating sections of the Admin Panel
	 *
	 ****************************************************/
	/**
	 * Creates the content for the "preview" section ("module") of the Admin Panel
	 *
	 * @return string HTML content for the section. Consists of a string with table-rows with four columns.
	 * @see display()
	 */
	protected function getPreviewModule() {
		$out = $this->extGetHead('preview');
		if ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['display_preview']) {
			$this->extNeedUpdate = TRUE;
			$out .= $this->extGetItem('preview_showHiddenPages', '', '<input type="hidden" name="TSFE_ADMIN_PANEL[preview_showHiddenPages]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[preview_showHiddenPages]" value="1"' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['preview_showHiddenPages'] ? ' checked="checked"' : '') . ' />');
			$out .= $this->extGetItem('preview_showHiddenRecords', '', '<input type="hidden" name="TSFE_ADMIN_PANEL[preview_showHiddenRecords]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[preview_showHiddenRecords]" value="1"' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['preview_showHiddenRecords'] ? ' checked="checked"' : '') . ' />');
			// Simulate date
			$out .= $this->extGetItem('preview_simulateDate', '<input type="text" name="TSFE_ADMIN_PANEL[preview_simulateDate]_hr" onchange="TSFEtypo3FormFieldGet(\'TSFE_ADMIN_PANEL[preview_simulateDate]\', \'datetime\', \'\', 1,0);" /><input type="hidden" name="TSFE_ADMIN_PANEL[preview_simulateDate]" value="' . $GLOBALS['BE_USER']->uc['TSFE_adminConfig']['preview_simulateDate'] . '" />');
			$this->extJSCODE .= 'TSFEtypo3FormFieldSet("TSFE_ADMIN_PANEL[preview_simulateDate]", "datetime", "", 0, 0);';
			// Simulate fe_user:
			$options = '<option value="0">&nbsp;</option>';
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('fe_groups.uid, fe_groups.title', 'fe_groups,pages', 'pages.uid=fe_groups.pid AND pages.deleted=0 ' . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('fe_groups') . ' AND ' . $GLOBALS['BE_USER']->getPagePermsClause(1));
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$options .= '<option value="' . $row['uid'] . '"' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['preview_simulateUserGroup'] == $row['uid'] ? ' selected="selected"' : '') . '>' . htmlspecialchars(('[' . $row['uid'] . '] ' . $row['title'])) . '</option>';
			}
			$out .= $this->extGetItem('preview_simulateUserGroup', '<select name="TSFE_ADMIN_PANEL[preview_simulateUserGroup]">' . $options . '</select>');
		}
		return $out;
	}

	/**
	 * Creates the content for the "cache" section ("module") of the Admin Panel
	 *
	 * @return string HTML content for the section. Consists of a string with table-rows with four columns.
	 * @see display()
	 */
	protected function getCacheModule() {
		$out = $this->extGetHead('cache');
		if ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['display_cache']) {
			$this->extNeedUpdate = TRUE;
			$out .= $this->extGetItem('cache_noCache', '', '<input type="hidden" name="TSFE_ADMIN_PANEL[cache_noCache]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[cache_noCache]" value="1"' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['cache_noCache'] ? ' checked="checked"' : '') . ' />');
			$levels = $GLOBALS['BE_USER']->uc['TSFE_adminConfig']['cache_clearCacheLevels'];
			$options = '';
			$options .= '<option value="0"' . ($levels == 0 ? ' selected="selected"' : '') . '>' . $this->extGetLL('div_Levels_0') . '</option>';
			$options .= '<option value="1"' . ($levels == 1 ? ' selected="selected"' : '') . '>' . $this->extGetLL('div_Levels_1') . '</option>';
			$options .= '<option value="2"' . ($levels == 2 ? ' selected="selected"' : '') . '>' . $this->extGetLL('div_Levels_2') . '</option>';
			$out .= $this->extGetItem('cache_clearLevels', '<select name="TSFE_ADMIN_PANEL[cache_clearCacheLevels]">' . $options . '</select>' . '<input type="hidden" name="TSFE_ADMIN_PANEL[cache_clearCacheId]" value="' . $GLOBALS['TSFE']->id . '" /> <input type="submit" value="' . $this->extGetLL('update') . '" />');
			// Generating tree:
			$depth = $this->extGetFeAdminValue('cache', 'clearCacheLevels');
			$outTable = '';
			$GLOBALS['BE_USER']->extPageInTreeInfo = array();
			$GLOBALS['BE_USER']->extPageInTreeInfo[] = array($GLOBALS['TSFE']->page['uid'], htmlspecialchars($GLOBALS['TSFE']->page['title']), $depth + 1);
			$GLOBALS['BE_USER']->extGetTreeList($GLOBALS['TSFE']->id, $depth, 0, $GLOBALS['BE_USER']->getPagePermsClause(1));
			foreach ($GLOBALS['BE_USER']->extPageInTreeInfo as $row) {
				$outTable .= '<tr>' . '<td><img src="typo3/gfx/clear.gif" width="' . ($depth + 1 - $row[2]) * 18 . '" height="1" alt="" /><img ' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg(TYPO3_mainDir, 'gfx/i/pages.gif', 'width="18" height="16"') . ' align="top" alt="" /> ' . htmlspecialchars($row[1]) . '</td><td>' . $GLOBALS['BE_USER']->extGetNumberOfCachedPages($row[0]) . '</td></tr>';
			}
			$outTable = '<br /><table>' . $outTable . '</table>';
			$outTable .= '<input type="submit" name="TSFE_ADMIN_PANEL[action][clearCache]" value="' . $this->extGetLL('cache_doit') . '" />';
			$out .= $this->extGetItem('cache_cacheEntries', $outTable);
		}
		return $out;
	}

	/**
	 * Creates the content for the "edit" section ("module") of the Admin Panel
	 *
	 * @return string HTML content for the section. Consists of a string with table-rows with four columns.
	 * @see display()
	 */
	protected function getEditModule() {
		$out = $this->extGetHead('edit');
		if ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['display_edit']) {
			// If another page module was specified, replace the default Page module with the new one
			$newPageModule = trim($GLOBALS['BE_USER']->getTSConfigVal('options.overridePageModule'));
			$pageModule = \TYPO3\CMS\Backend\Utility\BackendUtility::isModuleSetInTBE_MODULES($newPageModule) ? $newPageModule : 'web_layout';
			$this->extNeedUpdate = TRUE;
			$out .= $this->extGetItem('edit_displayFieldIcons', '', '<input type="hidden" name="TSFE_ADMIN_PANEL[edit_displayFieldIcons]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[edit_displayFieldIcons]" value="1"' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['edit_displayFieldIcons'] ? ' checked="checked"' : '') . ' />');
			$out .= $this->extGetItem('edit_displayIcons', '', '<input type="hidden" name="TSFE_ADMIN_PANEL[edit_displayIcons]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[edit_displayIcons]" value="1"' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['edit_displayIcons'] ? ' checked="checked"' : '') . ' />');
			$out .= $this->extGetItem('edit_editFormsOnPage', '', '<input type="hidden" name="TSFE_ADMIN_PANEL[edit_editFormsOnPage]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[edit_editFormsOnPage]" value="1"' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['edit_editFormsOnPage'] ? ' checked="checked"' : '') . ' />');
			$out .= $this->extGetItem('edit_editNoPopup', '', '<input type="hidden" name="TSFE_ADMIN_PANEL[edit_editNoPopup]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[edit_editNoPopup]" value="1"' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['edit_editNoPopup'] ? ' checked="checked"' : '') . ' />');
			$out .= $this->extGetItem('', $this->ext_makeToolBar());
			if (!\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('ADMCMD_view')) {
				$out .= $this->extGetItem('', '<a href="#" onclick="' . htmlspecialchars(('
						if (parent.opener && parent.opener.top && parent.opener.top.TS) {
							parent.opener.top.fsMod.recentIds["web"]=' . intval($GLOBALS['TSFE']->page['uid']) . ';
							if (parent.opener.top.content && parent.opener.top.content.nav_frame && parent.opener.top.content.nav_frame.refresh_nav) {
								parent.opener.top.content.nav_frame.refresh_nav();
							}
							parent.opener.top.goToModule("' . $pageModule . '");
							parent.opener.top.focus();
						} else {
							vHWin=window.open(\'' . TYPO3_mainDir . \TYPO3\CMS\Backend\Utility\BackendUtility::getBackendScript() . '\',\'' . md5(('Typo3Backend-' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'])) . '\',\'status=1,menubar=1,scrollbars=1,resizable=1\');
							vHWin.focus();
						}
						return false;
						')) . '">' . $this->extGetLL('edit_openAB') . '</a>');
			}
		}
		return $out;
	}

	/**
	 * Creates the content for the "tsdebug" section ("module") of the Admin Panel
	 *
	 * @return string HTML content for the section. Consists of a string with table-rows with four columns.
	 * @see display()
	 */
	protected function getTSDebugModule() {
		$out = $this->extGetHead('tsdebug');
		if ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['display_tsdebug']) {
			$this->extNeedUpdate = TRUE;
			$out .= $this->extGetItem('tsdebug_tree', '', '<input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_tree]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[tsdebug_tree]" value="1"' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['tsdebug_tree'] ? ' checked="checked"' : '') . ' />');
			$out .= $this->extGetItem('tsdebug_displayTimes', '', '<input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_displayTimes]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[tsdebug_displayTimes]" value="1"' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['tsdebug_displayTimes'] ? ' checked="checked"' : '') . ' />');
			$out .= $this->extGetItem('tsdebug_displayMessages', '', '<input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_displayMessages]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[tsdebug_displayMessages]" value="1"' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['tsdebug_displayMessages'] ? ' checked="checked"' : '') . ' />');
			$out .= $this->extGetItem('tsdebug_LR', '', '<input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_LR]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[tsdebug_LR]" value="1"' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['tsdebug_LR'] ? ' checked="checked"' : '') . ' />');
			$out .= $this->extGetItem('tsdebug_displayContent', '', '<input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_displayContent]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[tsdebug_displayContent]" value="1"' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['tsdebug_displayContent'] ? ' checked="checked"' : '') . ' />');
			$out .= $this->extGetItem('tsdebug_displayQueries', '', '<input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_displayQueries]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[tsdebug_displayQueries]" value="1"' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['tsdebug_displayQueries'] ? ' checked="checked"' : '') . ' />');
			$out .= $this->extGetItem('tsdebug_forceTemplateParsing', '', '<input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_forceTemplateParsing]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[tsdebug_forceTemplateParsing]" value="1"' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['tsdebug_forceTemplateParsing'] ? ' checked="checked"' : '') . ' />');
			$GLOBALS['TT']->printConf['flag_tree'] = $this->extGetFeAdminValue('tsdebug', 'tree');
			$GLOBALS['TT']->printConf['allTime'] = $this->extGetFeAdminValue('tsdebug', 'displayTimes');
			$GLOBALS['TT']->printConf['flag_messages'] = $this->extGetFeAdminValue('tsdebug', 'displayMessages');
			$GLOBALS['TT']->printConf['flag_content'] = $this->extGetFeAdminValue('tsdebug', 'displayContent');
			$GLOBALS['TT']->printConf['flag_queries'] = $this->extGetFeAdminValue('tsdebug', 'displayQueries');
			$out .= '<tr><td colspan="2">' . $GLOBALS['TT']->printTSlog() . '</td></tr>';
		}
		return $out;
	}

	/**
	 * Creates the content for the "info" section ("module") of the Admin Panel
	 *
	 * @return string HTML content for the section. Consists of a string with table-rows with four columns.
	 * @see display()
	 */
	protected function getInfoModule() {
		$out = $this->extGetHead('info');
		if ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['display_info']) {
			$tableArr = array();
			if ($this->extGetFeAdminValue('cache', 'noCache')) {
				$theBytes = 0;
				$count = 0;
				if (count($GLOBALS['TSFE']->imagesOnPage)) {
					$tableArr[] = array('*Images on this page:*', '');
					foreach ($GLOBALS['TSFE']->imagesOnPage as $file) {
						$fs = @filesize($file);
						$tableArr[] = array('&ndash; ' . $file, \TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($fs));
						$theBytes += $fs;
						$count++;
					}
				}
				// Add an empty line
				$tableArr[] = array('', '');
				$tableArr[] = array('*Total number of images:*', $count);
				$tableArr[] = array('*Total image file sizes:*', \TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($theBytes));
				$tableArr[] = array('*Document size:*', \TYPO3\CMS\Core\Utility\GeneralUtility::formatSize(strlen($GLOBALS['TSFE']->content)));
				$tableArr[] = array('*Total page load:*', \TYPO3\CMS\Core\Utility\GeneralUtility::formatSize(strlen($GLOBALS['TSFE']->content) + $theBytes));
				$tableArr[] = array('', '');
			}
			$tableArr[] = array('id:', $GLOBALS['TSFE']->id);
			$tableArr[] = array('type:', $GLOBALS['TSFE']->type);
			$tableArr[] = array('gr_list:', $GLOBALS['TSFE']->gr_list);
			$tableArr[] = array('no_cache:', $GLOBALS['TSFE']->no_cache ? 1 : 0);
			$tableArr[] = array('USER_INT objects:', count($GLOBALS['TSFE']->config['INTincScript']));
			$tableArr[] = array('fe_user, name:', $GLOBALS['TSFE']->fe_user->user['username']);
			$tableArr[] = array('fe_user, uid:', $GLOBALS['TSFE']->fe_user->user['uid']);
			// Add an empty line
			$tableArr[] = array('', '');
			// Parsetime:
			$tableArr[] = array('*Total parsetime:*', $GLOBALS['TSFE']->scriptParseTime . ' ms');
			$table = '';
			foreach ($tableArr as $arr) {
				// Put text wrapped by "*" between <strong> tags
				if (strlen($arr[0])) {
					$value1 = preg_replace('/^\\*(.*)\\*$/', '$1', $arr[0], -1, $count);
					$value1 = ($count ? '<strong>' : '') . $value1 . ($count ? '</strong>' : '');
				} else {
					$value1 = '&nbsp;';
				}
				$value2 = strlen($arr[1]) ? $arr[1] : '&nbsp;';
				$value2 = $value2;
				$table .= '<tr class="typo3-adminPanel-itemRow">' . '<td class="typo3-adminPanel-section-content-title">' . $value1 . '</td>' . '<td class="typo3-adminPanel-section-content">' . $value2 . '</td>' . '</tr>';
			}
			$out .= $table;
		}
		return $out;
	}

	/*****************************************************
	 *
	 * Admin Panel Layout Helper functions
	 *
	 ****************************************************/
	/**
	 * Returns a row (with colspan=4) which is a header for a section in the Admin Panel.
	 * It will have a plus/minus icon and a label which is linked so that it submits the form which surrounds the whole Admin Panel when clicked, alterting the TSFE_ADMIN_PANEL[display_' . $pre . '] value
	 * See the functions get*Module
	 *
	 * @param string $sectionSuffix The suffix to the display_ label. Also selects the label from the LOCAL_LANG array.
	 * @return string HTML table row.
	 * @see extGetItem()
	 */
	public function extGetHead($sectionSuffix) {
		$settingName = 'display_' . $sectionSuffix;
		$isVisible = $GLOBALS['BE_USER']->uc['TSFE_adminConfig'][$settingName];
		$cssClassName = 'typo3-adminPanel-section-' . ($isVisible ? 'open' : 'closed');
		return '<tr class="typo3-adminPanel-section-title"><td colspan="2">' . $this->linkSectionHeader($sectionSuffix, $this->extGetLL($sectionSuffix), $cssClassName) . '<input type="hidden" name="TSFE_ADMIN_PANEL[' . $settingName . ']" value="' . $isVisible . '" /></td></tr>';
	}

	/**
	 * Wraps a string in a link which will open/close a certain part of the Admin Panel
	 *
	 * @param string $sectionSuffix The code for the display_ label/key
	 * @param string $sectionTitle Input string
	 * @param string $className The classname for the <a> tag
	 * @return string $className Linked input string
	 * @see extGetHead()
	 */
	public function linkSectionHeader($sectionSuffix, $sectionTitle, $className = '') {
		return '<div class="typo3-adminPanel-label"><a href="javascript:void(0)" onclick="' . htmlspecialchars(('document.TSFE_ADMIN_PANEL_FORM[\'TSFE_ADMIN_PANEL[display_' . $sectionSuffix . ']\'].value=' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['display_' . $sectionSuffix] ? '0' : '1') . ';document.TSFE_ADMIN_PANEL_FORM.submit();return false;')) . '"' . ($className ? ' class="' . $className . '"' : '') . '>' . $sectionTitle . '</a></div>';
	}

	/**
	 * Returns a row (with 4 columns) for content in a section of the Admin Panel.
	 * It will take $pre as a key to a label to display and $element as the content to put into the forth cell.
	 *
	 * @param string $title Key to label
	 * @param string $content The HTML content for the forth table cell.
	 * @param string $checkboxContent The HTML for a checkbox or hidden fields
	 * @return string HTML table row.
	 * @see extGetHead()
	 */
	public function extGetItem($title, $content = '', $checkboxContent = '') {
		$out = '<tr class="typo3-adminPanel-itemRow">' . '<td class="typo3-adminPanel-section-content">' . $checkboxContent . ($title ? $this->extGetLL($title) : '&nbsp;') . $content . '</td></tr>';
		return $out;
	}

	/**
	 * Creates the tool bar links for the "edit" section of the Admin Panel.
	 *
	 * @return string A string containing images wrapped in <a>-tags linking them to proper functions.
	 */
	public function ext_makeToolBar() {
		//  If mod.web_list.newContentWiz.overrideWithExtension is set, use that extension's create new content wizard instead:
		$tsConfig = \TYPO3\CMS\Backend\Utility\BackendUtility::getModTSconfig($this->pageinfo['uid'], 'mod.web_list');
		$tsConfig = $tsConfig['properties']['newContentWiz.']['overrideWithExtension'];
		$newContentWizScriptPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($tsConfig) ? \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($tsConfig) . 'mod1/db_new_content_el.php' : TYPO3_mainDir . 'sysext/cms/layout/db_new_content_el.php';
		$perms = $GLOBALS['BE_USER']->calcPerms($GLOBALS['TSFE']->page);
		$langAllowed = $GLOBALS['BE_USER']->checkLanguageAccess($GLOBALS['TSFE']->sys_language_uid);
		$id = $GLOBALS['TSFE']->id;
		$toolBar = '<a href="' . htmlspecialchars((TYPO3_mainDir . 'show_rechis.php?element=' . rawurlencode(('pages:' . $id)) . '&returnUrl=' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI')))) . '#latest">' . '<img ' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg(TYPO3_mainDir, 'gfx/history2.gif', 'width="13" height="12"') . ' hspace="2" border="0" align="top" title="' . $this->extGetLL('edit_recordHistory') . '" alt="" /></a>';
		if ($perms & 16 && $langAllowed) {
			$params = '';
			if ($GLOBALS['TSFE']->sys_language_uid) {
				$params = '&sys_language_uid=' . $GLOBALS['TSFE']->sys_language_uid;
			}
			$toolBar .= '<a href="' . htmlspecialchars(($newContentWizScriptPath . '?id=' . $id . $params . '&returnUrl=' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI')))) . '">' . '<img ' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg(TYPO3_mainDir, 'gfx/new_record.gif', 'width="16" height="12"') . ' hspace="1" border="0" align="top" title="' . $this->extGetLL('edit_newContentElement') . '" alt="" /></a>';
		}
		if ($perms & 2) {
			$toolBar .= '<a href="' . htmlspecialchars((TYPO3_mainDir . 'move_el.php?table=pages&uid=' . $id . '&returnUrl=' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI')))) . '">' . '<img ' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg(TYPO3_mainDir, 'gfx/move_page.gif', 'width="11" height="12') . ' hspace="2" border="0" align="top" title="' . $this->extGetLL('edit_move_page') . '" alt="" /></a>';
		}
		if ($perms & 8) {
			$toolBar .= '<a href="' . htmlspecialchars((TYPO3_mainDir . 'db_new.php?id=' . $id . '&pagesOnly=1&returnUrl=' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI')))) . '">' . '<img ' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg(TYPO3_mainDir, 'gfx/new_page.gif', 'width="13" height="12"') . ' hspace="0" border="0" align="top" title="' . $this->extGetLL('edit_newPage') . '" alt="" /></a>';
		}
		if ($perms & 2) {
			$params = '&edit[pages][' . $id . ']=edit';
			$toolBar .= '<a href="' . htmlspecialchars((TYPO3_mainDir . 'alt_doc.php?' . $params . '&noView=1&returnUrl=' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI')))) . '">' . '<img ' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg(TYPO3_mainDir, 'gfx/edit2.gif', 'width="11" height="12"') . 'hspace="2" border="0" align="top" title="' . $this->extGetLL('edit_editPageProperties') . '" alt="" /></a>';
			if ($GLOBALS['TSFE']->sys_language_uid && $langAllowed) {
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'uid,pid,t3ver_state',
					'pages_language_overlay',
					'pid=' . intval($id) .
						' AND sys_language_uid=' . $GLOBALS['TSFE']->sys_language_uid .
						$GLOBALS['TSFE']->sys_page->enableFields('pages_language_overlay'),
					'',
					'',
					'1'
				);
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
				$GLOBALS['TSFE']->sys_page->versionOL('pages_language_overlay', $row);
				if (is_array($row)) {
					$params = '&edit[pages_language_overlay][' . $row['uid'] . ']=edit';
					$toolBar .= '<a href="' . htmlspecialchars((TYPO3_mainDir . 'alt_doc.php?' . $params . '&noView=1&returnUrl=' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI')))) . '">' . '<img ' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg(TYPO3_mainDir, 'gfx/edit3.gif', 'width="11" height="12"') . ' hspace="2" border="0" align="top" title="' . $this->extGetLL('edit_editPageOverlay') . '" alt="" /></a>';
				}
			}
		}
		if ($GLOBALS['BE_USER']->check('modules', 'web_list')) {
			$urlParams = array();
			$urlParams['id'] = $id;
			$urlParams['returnUrl'] = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI');
			$toolBar .= '<a href="' . htmlspecialchars((TYPO3_mainDir . \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('web_list', $urlParams))) . '">' . '<img ' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg(TYPO3_mainDir, 'gfx/list.gif', 'width="11" height="11"') . ' hspace="2" border="0" align="top" title="' . $this->extGetLL('edit_db_list') . '" alt="" /></a>';
		}
		return $toolBar;
	}

	/**
	 * Returns the label for key, $key. If a translation for the language set in $GLOBALS['BE_USER']->uc['lang'] is found that is returned, otherwise the default value.
	 * IF the global variable $LOCAL_LANG is NOT an array (yet) then this function loads the global $LOCAL_LANG array with the content of "sysext/lang/locallang_tsfe.xlf" so that the values therein can be used for labels in the Admin Panel
	 *
	 * FIXME The function should convert to $TSFE->renderCharset, not to UTF8!
	 *
	 * @param string $key Key for a label in the $LOCAL_LANG array of "sysext/lang/locallang_tsfe.xlf
	 * @return string The value for the $key
	 */
	public function extGetLL($key) {
		// Label string in the default backend output charset.
		$labelStr = htmlspecialchars($GLOBALS['LANG']->getLL($key));
		$labelStr = $GLOBALS['LANG']->csConvObj->utf8_to_entities($labelStr);
		return $labelStr;
	}

}


?>
