<?php
namespace TYPO3\CMS\Feedit;

/***************************************************************
 *  Copyright notice
 *  (c) 2008-2013 Jeff Segars <jeff@webempoweredchurch.org>
 *  (c) 2008-2013 David Slayback <dave@webempoweredchurch.org>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use TYPO3\CMS\Backend\Sprite\SpriteManager;
use TYPO3\CMS\Backend\Utility\IconUtility;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * View class for the edit panels in frontend editing.
 */
class FrontendEditPanel {

	/**
	 * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	protected $cObj;

	/**
	 * Constructor for the edit panel
	 */
	public function __construct() {
		$this->cObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
		$this->cObj->start(array());
	}

	/**
	 * Generates the "edit panels" which can be shown for a page or records on a page when the Admin Panel is enabled for a backend users surfing the frontend.
	 * With the "edit panel" the user will see buttons with links to editing, moving, hiding, deleting the element
	 * This function is used for the cObject EDITPANEL and the stdWrap property ".editPanel"
	 *
	 * @param string $content A content string containing the content related to the edit panel. For cObject "EDITPANEL" this is empty but not so for the stdWrap property. The edit panel is appended to this string and returned.
	 * @param array $conf TypoScript configuration properties for the editPanel
	 * @param string $currentRecord The "table:uid" of the record being shown. If empty string then $this->currentRecord is used. For new records (set by $conf['newRecordFromTable']) it's auto-generated to "[tablename]:NEW
	 * @param array $dataArr Alternative data array to use. Default is $this->data
	 * @param string $table
	 * @param string $allow
	 * @param integer $newUID
	 * @param array $hiddenFields
	 * @return string The input content string with the editPanel appended. This function returns only an edit panel appended to the content string if a backend user is logged in (and has the correct permissions). Otherwise the content string is directly returned.
	 */
	public function editPanel($content, array $conf, $currentRecord = '', array $dataArr = array(), $table = '', $allow = '', $newUID = 0, array $hiddenFields = array()) {
		$hiddenFieldString = $command = '';

		// Special content is about to be shown, so the cache must be disabled.
		$GLOBALS['TSFE']->set_no_cache('Frontend edit panel is shown', TRUE);

		$formName = 'TSFE_EDIT_FORM_' . substr($GLOBALS['TSFE']->uniqueHash(), 0, 4);
		$formTag = '<form name="' . $formName . '" id ="' . $formName . '" action="' . htmlspecialchars(GeneralUtility::getIndpEnv('REQUEST_URI')) . '" method="post" enctype="' . htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype']) . '" onsubmit="return TBE_EDITOR.checkSubmit(1);">';
		$sortField = $GLOBALS['TCA'][$table]['ctrl']['sortby'];
		$labelField = $GLOBALS['TCA'][$table]['ctrl']['label'];
		$hideField = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'];

		$TSFE_EDIT = $GLOBALS['BE_USER']->frontendEdit->TSFE_EDIT;
		if (is_array($TSFE_EDIT) && $TSFE_EDIT['record'] == $currentRecord && !$TSFE_EDIT['update_close']) {
			$command = $TSFE_EDIT['cmd'];
		}

		$panel = '';
		if (isset($allow['toolbar']) && $GLOBALS['BE_USER']->adminPanel instanceof \TYPO3\CMS\Frontend\View\AdminPanelView) {
			$panel .= $GLOBALS['BE_USER']->adminPanel->ext_makeToolBar();
		}
		if (isset($allow['edit'])) {
			$icon = IconUtility::getSpriteIcon('actions-document-open', array('title' => $GLOBALS['BE_USER']->extGetLL('p_editRecord')));
			$panel .= $this->editPanelLinkWrap($icon, $formName, 'edit', $dataArr['_LOCALIZED_UID'] ? $table . ':' . $dataArr['_LOCALIZED_UID'] : $currentRecord);
		}
		// Hiding in workspaces because implementation is incomplete
		if (isset($allow['move']) && $sortField && $GLOBALS['BE_USER']->workspace === 0) {
			$icon = IconUtility::getSpriteIcon('actions-move-up', array('title' => $GLOBALS['BE_USER']->extGetLL('p_moveUp')));
			$panel .= $this->editPanelLinkWrap($icon, $formName, 'up');
			$icon = IconUtility::getSpriteIcon('actions-move-down', array('title' => $GLOBALS['BE_USER']->extGetLL('p_moveDown')));
			$panel .= $this->editPanelLinkWrap($icon, $formName, 'down');
		}
		// Hiding in workspaces because implementation is incomplete
		// Hiding for localizations because it is unknown what should be the function in that case
		if (isset($allow['hide']) && $hideField && $GLOBALS['BE_USER']->workspace === 0 && !$dataArr['_LOCALIZED_UID']) {
			if ($dataArr[$hideField]) {
				$icon = IconUtility::getSpriteIcon('actions-edit-unhide', array('title' => $GLOBALS['BE_USER']->extGetLL('p_unhide')));
				$panel .= $this->editPanelLinkWrap($icon, $formName, 'unhide');
			} else {
				$icon = IconUtility::getSpriteIcon('actions-edit-hide', array('title' => $GLOBALS['BE_USER']->extGetLL('p_hide')));
				$panel .= $this->editPanelLinkWrap($icon, $formName, 'hide', '', $GLOBALS['BE_USER']->extGetLL('p_hideConfirm'));
			}
		}
		if (isset($allow['new'])) {
			if ($table === 'pages') {
				$icon = IconUtility::getSpriteIcon('actions-page-new', array('title' => $GLOBALS['BE_USER']->extGetLL('p_newSubpage')));
				$panel .= $this->editPanelLinkWrap($icon, $formName, 'new', $currentRecord, '');
			} else {
				$icon = IconUtility::getSpriteIcon('actions-document-new', array('title' => $GLOBALS['BE_USER']->extGetLL('p_newRecordAfter')));
				$panel .= $this->editPanelLinkWrap($icon, $formName, 'new', $currentRecord, '', $newUID);
			}
		}
		// Hiding in workspaces because implementation is incomplete
		// Hiding for localizations because it is unknown what should be the function in that case
		if (isset($allow['delete']) && $GLOBALS['BE_USER']->workspace === 0 && !$dataArr['_LOCALIZED_UID']) {
			$icon = IconUtility::getSpriteIcon('actions-edit-delete', array('title' => $GLOBALS['BE_USER']->extGetLL('p_delete')));
			$panel .= $this->editPanelLinkWrap($icon, $formName, 'delete', '', $GLOBALS['BE_USER']->extGetLL('p_deleteConfirm'));
		}
		// Final
		$labelTxt = $this->cObj->stdWrap($conf['label'], $conf['label.']);
		foreach ((array)$hiddenFields as $name => $value) {
			$hiddenFieldString .= '<input type="hidden" name="TSFE_EDIT[' . htmlspecialchars($name) . ']" value="' . htmlspecialchars($value) . '"/>' . LF;
		}

		$panel = '<!-- BE_USER Edit Panel: -->
								' . $formTag . $hiddenFieldString . '
									<input type="hidden" name="TSFE_EDIT[cmd]" value="" />
									<input type="hidden" name="TSFE_EDIT[record]" value="' . $currentRecord . '" />
									<div class="typo3-editPanel">'
										. $panel .
			($labelTxt ? '<div class="typo3-editPanel-label">' . sprintf($labelTxt, htmlspecialchars(GeneralUtility::fixed_lgd_cs($dataArr[$labelField], 50))) . '</div>' : '') . '
									</div>
								</form>';

		// Wrap the panel
		if ($conf['innerWrap']) {
			$panel = $this->cObj->wrap($panel, $conf['innerWrap']);
		}
		if ($conf['innerWrap.']) {
			$panel = $this->cObj->stdWrap($panel, $conf['innerWrap.']);
		}

		// Wrap the complete panel
		if ($conf['outerWrap']) {
			$panel = $this->cObj->wrap($panel, $conf['outerWrap']);
		}
		if ($conf['outerWrap.']) {
			$panel = $this->cObj->stdWrap($panel, $conf['outerWrap.']);
		}
		if ($conf['printBeforeContent']) {
			$finalOut = $panel . $content;
		} else {
			$finalOut = $content . $panel;
		}

		$hidden = $this->isDisabled($table, $dataArr) ? ' typo3-feedit-element-hidden' : '';
		$outerWrapConfig = isset($conf['stdWrap.'])
			? $conf['stdWrap.']
			: array('wrap' => '<div class="typo3-feedit-element' . $hidden . '">|</div>');
		$finalOut = $this->cObj->stdWrap($finalOut, $outerWrapConfig);

		return $finalOut;
	}

	/**
	 * Adds an edit icon to the content string. The edit icon links to alt_doc.php with proper parameters for editing the table/fields of the context.
	 * This implements TYPO3 context sensitive editing facilities. Only backend users will have access (if properly configured as well).
	 *
	 * @param string $content The content to which the edit icons should be appended
	 * @param string $params The parameters defining which table and fields to edit. Syntax is [tablename]:[fieldname],[fieldname],[fieldname],... OR [fieldname],[fieldname],[fieldname],... (basically "[tablename]:" is optional, default table is the one of the "current record" used in the function). The fieldlist is sent as "&columnsOnly=" parameter to alt_doc.php
	 * @param array $conf TypoScript properties for configuring the edit icons.
	 * @param string $currentRecord The "table:uid" of the record being shown. If empty string then $this->currentRecord is used. For new records (set by $conf['newRecordFromTable']) it's auto-generated to "[tablename]:NEW
	 * @param array $dataArr Alternative data array to use. Default is $this->data
	 * @param string $addUrlParamStr Additional URL parameters for the link pointing to alt_doc.php
	 * @param string $table
	 * @param integer $editUid
	 * @param string $fieldList
	 * @return string The input content string, possibly with edit icons added (not necessarily in the end but just after the last string of normal content.
	 */
	public function editIcons($content, $params, array $conf = array(), $currentRecord = '', array $dataArr = array(), $addUrlParamStr = '', $table, $editUid, $fieldList) {
		// Special content is about to be shown, so the cache must be disabled.
		$GLOBALS['TSFE']->set_no_cache('Display frontend edit icons', TRUE);
		$style = $conf['styleAttribute'] ? ' style="' . htmlspecialchars($conf['styleAttribute']) . '"' : '';
		$iconTitle = $this->cObj->stdWrap($conf['iconTitle'], $conf['iconTitle.']);
		$iconImg = $conf['iconImg'] ? $conf['iconImg'] : '<img  ' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg(TYPO3_mainDir, 'gfx/edit_fe.gif', 'width="11" height="12" border="0" align="top" ') . ' title="' . htmlspecialchars($iconTitle, ENT_COMPAT, 'UTF-8', FALSE) . '"' . $style . ' class="frontEndEditIcons" alt="" />';
		$nV = GeneralUtility::_GP('ADMCMD_view') ? 1 : 0;
		$adminURL = GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . TYPO3_mainDir;
		$icon = $this->editPanelLinkWrap_doWrap($iconImg, $adminURL . 'alt_doc.php?edit[' . $table . '][' . $editUid . ']=edit&columnsOnly=' . rawurlencode($fieldList) . '&noView=' . $nV . $addUrlParamStr);
		if ($conf['beforeLastTag'] < 0) {
			$content = $icon . $content;
		} elseif ($conf['beforeLastTag'] > 0) {
			$cBuf = rtrim($content);
			$secureCount = 30;
			while ($secureCount && substr($cBuf, -1) == '>' && substr($cBuf, -4) != '</a>') {
				$cBuf = rtrim(preg_replace('/<[^<]*>$/', '', $cBuf));
				$secureCount--;
			}
			$content = strlen($cBuf) && $secureCount ? substr($content, 0, strlen($cBuf)) . $icon . substr($content, strlen($cBuf)) : ($content = $icon . $content);
		} else {
			$content .= $icon;
		}
		return $content;
	}

	/**
	 * Helper function for editPanel() which wraps icons in the panel in a link with the action of the panel.
	 * The links are for some of them not simple hyperlinks but onclick-actions which submits a little form which the panel is wrapped in.
	 *
	 * @param string $string The string to wrap in a link, typ. and image used as button in the edit panel.
	 * @param string $formName The name of the form wrapping the edit panel.
	 * @param string $cmd The command of the link. There is a predefined list available: edit, new, up, down etc.
	 * @param string $currentRecord The "table:uid" of the record being processed by the panel.
	 * @param string $confirm Text string with confirmation message; If set a confirm box will be displayed before carrying out the action (if Yes is pressed)
	 * @param int|string $nPid "New pid" - for new records
	 * @return string A <a> tag wrapped string.
	 */
	protected function editPanelLinkWrap($string, $formName, $cmd, $currentRecord = '', $confirm = '', $nPid = '') {
		$nV = GeneralUtility::_GP('ADMCMD_view') ? 1 : 0;
		$adminURL = GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . TYPO3_mainDir;
		if ($cmd == 'edit') {
			$rParts = explode(':', $currentRecord);
			$out = $this->editPanelLinkWrap_doWrap($string, $adminURL . 'alt_doc.php?edit[' . $rParts[0] . '][' . $rParts[1] . ']=edit&noView=' . $nV, $currentRecord);
		} elseif ($cmd == 'new') {
			$rParts = explode(':', $currentRecord);
			if ($rParts[0] == 'pages') {
				$out = $this->editPanelLinkWrap_doWrap($string, $adminURL . 'db_new.php?id=' . $rParts[1] . '&pagesOnly=1', $currentRecord);
			} else {
				if (!(int)$nPid) {
					$nPid = \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($rParts[1]) ? -$rParts[1] : $GLOBALS['TSFE']->id;
				}
				$out = $this->editPanelLinkWrap_doWrap($string, $adminURL . 'alt_doc.php?edit[' . $rParts[0] . '][' . $nPid . ']=new&noView=' . $nV, $currentRecord);
			}
		} else {
			if ($confirm && $GLOBALS['BE_USER']->jsConfirmation(8)) {
				// Gets htmlspecialchared later
				$cf1 = 'if (confirm(' . GeneralUtility::quoteJSvalue($confirm, TRUE) . ')) {';
				$cf2 = '}';
			} else {
				$cf1 = ($cf2 = '');
			}
			$out = '<a href="#" onclick="' . htmlspecialchars(($cf1 . 'document.' . $formName . '[\'TSFE_EDIT[cmd]\'].value=\'' . $cmd . '\'; document.' . $formName . '.submit();' . $cf2 . ' return false;')) . '">' . $string . '</a>';
		}
		return $out;
	}

	/**
	 * Creates a link to a script (eg. typo3/alt_doc.php or typo3/db_new.php) which either opens in the current frame OR in a pop-up window.
	 *
	 * @param string $string The string to wrap in a link, typ. and image used as button in the edit panel.
	 * @param string $url The URL of the link. Should be absolute if supposed to work with <base> path set.
	 * @return string A <a> tag wrapped string.
	 * @see    editPanelLinkWrap()
	 */
	protected function editPanelLinkWrap_doWrap($string, $url) {
		$onclick = 'vHWin=window.open(' . GeneralUtility::quoteJSvalue($url . '&returnUrl=close.html') . ',\'FEquickEditWindow\',\'width=690,height=500,status=0,menubar=0,scrollbars=1,resizable=1\');vHWin.focus();return false;';
		return '<a href="#" onclick="' . htmlspecialchars($onclick) . '" class="frontEndEditIconLinks">' . $string . '</a>';
	}

	/**
	 * Returns TRUE if the input table/row would be hidden in the frontend, according to the current time and simulate user group
	 *
	 * @param string $table The table name
	 * @param array $row The data record
	 * @return boolean
	 */
	protected function isDisabled($table, array $row) {
		$status = FALSE;
		if (
			$GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'] &&
			$row[$GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled']] ||
			$GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['fe_group'] &&
			$GLOBALS['TSFE']->simUserGroup &&
			$row[$GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['fe_group']] == $GLOBALS['TSFE']->simUserGroup ||
			$GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['starttime'] &&
			$row[$GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['starttime']] > $GLOBALS['EXEC_TIME'] ||
			$GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['endtime'] &&
			$row[$GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['endtime']] &&
			$row[$GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['endtime']] < $GLOBALS['EXEC_TIME']
		) {
			$status = TRUE;
		}

		return $status;
	}

}
