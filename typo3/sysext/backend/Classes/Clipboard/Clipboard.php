<?php
namespace TYPO3\CMS\Backend\Clipboard;

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
 * Contains class for TYPO3 clipboard for records and files
 *
 * Revised for TYPO3 3.6 July/2003 by Kasper Skårhøj
 * XHTML compliant
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * TYPO3 clipboard for records and files
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class Clipboard {

	/**
	 * @todo Define visibility
	 */
	public $numberTabs = 3;

	/**
	 * Clipboard data kept here
	 *
	 * Keys:
	 * 'normal'
	 * 'tab_[x]' where x is >=1 and denotes the pad-number
	 * 'mode'	:	'copy' means copy-mode, default = moving ('cut')
	 * 'el'	:	Array of elements:
	 * DB: keys = '[tablename]|[uid]'	eg. 'tt_content:123'
	 * DB: values = 1 (basically insignificant)
	 * FILE: keys = '_FILE|[shortmd5 of path]'	eg. '_FILE|9ebc7e5c74'
	 * FILE: values = The full filepath, eg. '/www/htdocs/typo3/32/dummy/fileadmin/sem1_3_examples/alternative_index.php'
	 * or 'C:/www/htdocs/typo3/32/dummy/fileadmin/sem1_3_examples/alternative_index.php'
	 *
	 * 'current' pointer to current tab (among the above...)
	 * '_setThumb'	boolean: If set, file thumbnails are shown.
	 *
	 * The virtual tablename '_FILE' will always indicate files/folders. When checking for elements from eg. 'all tables'
	 * (by using an empty string) '_FILE' entries are excluded (so in effect only DB elements are counted)
	 *
	 * @todo Define visibility
	 */
	public $clipData = array();

	/**
	 * @todo Define visibility
	 */
	public $changed = 0;

	/**
	 * @todo Define visibility
	 */
	public $current = '';

	/**
	 * @todo Define visibility
	 */
	public $backPath = '';

	/**
	 * @todo Define visibility
	 */
	public $lockToNormal = 0;

	// If set, clipboard is displaying files.
	/**
	 * @todo Define visibility
	 */
	public $fileMode = 0;

	/*****************************************
	 *
	 * Initialize
	 *
	 ****************************************/
	/**
	 * Initialize the clipboard from the be_user session
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function initializeClipboard() {
		$this->backPath = $GLOBALS['BACK_PATH'];
		// Get data
		$clipData = $GLOBALS['BE_USER']->getModuleData('clipboard', $GLOBALS['BE_USER']->getTSConfigVal('options.saveClipboard') ? '' : 'ses');
		// NumberTabs
		$clNP = $GLOBALS['BE_USER']->getTSConfigVal('options.clipboardNumberPads');
		if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($clNP) && $clNP >= 0) {
			$this->numberTabs = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($clNP, 0, 20);
		}
		// Resets/reinstates the clipboard pads
		$this->clipData['normal'] = is_array($clipData['normal']) ? $clipData['normal'] : array();
		for ($a = 1; $a <= $this->numberTabs; $a++) {
			$this->clipData['tab_' . $a] = is_array($clipData['tab_' . $a]) ? $clipData['tab_' . $a] : array();
		}
		// Setting the current pad pointer ($this->current) and _setThumb (which determines whether or not do show file thumbnails)
		$this->clipData['current'] = ($this->current = isset($this->clipData[$clipData['current']]) ? $clipData['current'] : 'normal');
		$this->clipData['_setThumb'] = $clipData['_setThumb'];
	}

	/**
	 * Call this method after initialization if you want to lock the clipboard to operate on the normal pad only.
	 * Trying to switch pad through ->setCmd will not work.
	 * This is used by the clickmenu since it only allows operation on single elements at a time (that is the "normal" pad)
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function lockToNormal() {
		$this->lockToNormal = 1;
		$this->current = 'normal';
	}

	/**
	 * The array $cmd may hold various keys which notes some action to take.
	 * Normally perform only one action at a time.
	 * In scripts like db_list.php / filelist/mod1/index.php the GET-var CB is used to control the clipboard.
	 *
	 * Selecting / Deselecting elements
	 * Array $cmd['el'] has keys = element-ident, value = element value (see description of clipData array in header)
	 * Selecting elements for 'copy' should be done by simultaneously setting setCopyMode.
	 *
	 * @param array $cmd Array of actions, see function description
	 * @return void
	 * @todo Define visibility
	 */
	public function setCmd($cmd) {
		if (is_array($cmd['el'])) {
			foreach ($cmd['el'] as $k => $v) {
				if ($this->current == 'normal') {
					unset($this->clipData['normal']);
				}
				if ($v) {
					$this->clipData[$this->current]['el'][$k] = $v;
				} else {
					$this->removeElement($k);
				}
				$this->changed = 1;
			}
		}
		// Change clipboard pad (if not locked to normal)
		if ($cmd['setP']) {
			$this->setCurrentPad($cmd['setP']);
		}
		// Remove element	(value = item ident: DB; '[tablename]|[uid]'    FILE: '_FILE|[shortmd5 hash of path]'
		if ($cmd['remove']) {
			$this->removeElement($cmd['remove']);
			$this->changed = 1;
		}
		// Remove all on current pad (value = pad-ident)
		if ($cmd['removeAll']) {
			$this->clipData[$cmd['removeAll']] = array();
			$this->changed = 1;
		}
		// Set copy mode of the tab
		if (isset($cmd['setCopyMode'])) {
			$this->clipData[$this->current]['mode'] = $this->isElements() ? ($cmd['setCopyMode'] ? 'copy' : '') : '';
			$this->changed = 1;
		}
		// Toggle thumbnail display for files on/off
		if (isset($cmd['setThumb'])) {
			$this->clipData['_setThumb'] = $cmd['setThumb'];
			$this->changed = 1;
		}
	}

	/**
	 * Setting the current pad on clipboard
	 *
	 * @param string $padIdent Key in the array $this->clipData
	 * @return void
	 * @todo Define visibility
	 */
	public function setCurrentPad($padIdent) {
		// Change clipboard pad (if not locked to normal)
		if (!$this->lockToNormal && $this->current != $padIdent) {
			if (isset($this->clipData[$padIdent])) {
				$this->clipData['current'] = ($this->current = $padIdent);
			}
			if ($this->current != 'normal' || !$this->isElements()) {
				$this->clipData[$this->current]['mode'] = '';
			}
			// Setting mode to default (move) if no items on it or if not 'normal'
			$this->changed = 1;
		}
	}

	/**
	 * Call this after initialization and setCmd in order to save the clipboard to the user session.
	 * The function will check if the internal flag ->changed has been set and if so, save the clipboard. Else not.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function endClipboard() {
		if ($this->changed) {
			$this->saveClipboard();
		}
		$this->changed = 0;
	}

	/**
	 * Cleans up an incoming element array $CBarr (Array selecting/deselecting elements)
	 *
	 * @param array $CBarr Element array from outside ("key" => "selected/deselected")
	 * @param string $table The 'table which is allowed'. Must be set.
	 * @param boolean $removeDeselected Can be set in order to remove entries which are marked for deselection.
	 * @return array Processed input $CBarr
	 * @todo Define visibility
	 */
	public function cleanUpCBC($CBarr, $table, $removeDeselected = 0) {
		if (is_array($CBarr)) {
			foreach ($CBarr as $k => $v) {
				$p = explode('|', $k);
				if ((string) $p[0] != (string) $table || $removeDeselected && !$v) {
					unset($CBarr[$k]);
				}
			}
		}
		return $CBarr;
	}

	/*****************************************
	 *
	 * Clipboard HTML renderings
	 *
	 ****************************************/
	/**
	 * Prints the clipboard
	 *
	 * @return string HTML output
	 * @todo Define visibility
	 */
	public function printClipboard() {
		$out = array();
		$elCount = count($this->elFromTable($this->fileMode ? '_FILE' : ''));
		// Upper header
		$out[] = '
			<tr class="t3-row-header">
				<td colspan="3">' . \TYPO3\CMS\Backend\Utility\BackendUtility::wrapInHelp('xMOD_csh_corebe', 'list_clipboard', $this->clLabel('clipboard', 'buttons')) . '</td>
			</tr>';
		// Button/menu header:
		$thumb_url = \TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(array('CB' => array('setThumb' => $this->clipData['_setThumb'] ? 0 : 1)));
		$rmall_url = \TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(array('CB' => array('removeAll' => $this->current)));
		// Copymode Selector menu
		$copymode_url = \TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript();
		$moveLabel = htmlspecialchars($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_misc.xlf:moveElements'));
		$copyLabel = htmlspecialchars($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_misc.xlf:copyElements'));
		$opt = array();
		$opt[] = '<option style="padding-left: 20px; background-image: url(\'' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->backPath, 'gfx/clip_cut.gif', '', 1) . '\'); background-repeat: no-repeat;" value="" ' . ($this->currentMode() == 'copy' ? '' : 'selected="selected"') . '>' . $moveLabel . '</option>';
		$opt[] = '<option style="padding-left: 20px; background-image: url(\'' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->backPath, 'gfx/clip_copy.gif', '', 1) . '\'); background-repeat: no-repeat;" value="1" ' . ($this->currentMode() == 'copy' ? 'selected="selected"' : '') . '>' . $copyLabel . '</option>';
		$copymode_selector = ' <select name="CB[setCopyMode]" onchange="this.form.method=\'POST\'; this.form.action=\'' . htmlspecialchars(($copymode_url . '&CB[setCopyMode]=')) . '\'+(this.options[this.selectedIndex].value); this.form.submit(); return true;" >' . implode('', $opt) . '</select>';
		// Selector menu + clear button
		$opt = array();
		$opt[] = '<option value="" selected="selected">' . $this->clLabel('menu', 'rm') . '</option>';
		// Import / Export link:
		if ($elCount && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('impexp')) {
			$opt[] = '<option value="' . htmlspecialchars(('window.location.href=\'' . $this->backPath . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('impexp') . 'app/index.php' . $this->exportClipElementParameters() . '\';')) . '">' . $this->clLabel('export', 'rm') . '</option>';
		}
		// Edit:
		if (!$this->fileMode && $elCount) {
			$opt[] = '<option value="' . htmlspecialchars(('window.location.href=\'' . $this->editUrl() . '&returnUrl=\'+top.rawurlencode(window.location.href);')) . '">' . $this->clLabel('edit', 'rm') . '</option>';
		}
		// Delete:
		if ($elCount) {
			if ($GLOBALS['BE_USER']->jsConfirmation(4)) {
				$js = '
			if (confirm(' . $GLOBALS['LANG']->JScharCode(sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:mess.deleteClip'), $elCount)) . ')){
				window.location.href=\'' . $this->deleteUrl(0, ($this->fileMode ? 1 : 0)) . '&redirect=\'+top.rawurlencode(window.location.href);
			}
					';
			} else {
				$js = ' window.location.href=\'' . $this->deleteUrl(0, ($this->fileMode ? 1 : 0)) . '&redirect=\'+top.rawurlencode(window.location.href); ';
			}
			$opt[] = '<option value="' . htmlspecialchars($js) . '">' . $this->clLabel('delete', 'rm') . '</option>';
		}
		$selector_menu = '<select name="_clipMenu" onchange="eval(this.options[this.selectedIndex].value);this.selectedIndex=0;">' . implode('', $opt) . '</select>';
		$out[] = '
			<tr class="typo3-clipboard-head">
				<td nowrap="nowrap">' . '<a href="' . htmlspecialchars($thumb_url) . '#clip_head">' . '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->backPath, ('gfx/thumb_' . ($this->clipData['_setThumb'] ? 's' : 'n') . '.gif'), 'width="21" height="16"') . ' vspace="2" border="0" title="' . $this->clLabel('thumbmode_clip') . '" alt="" />' . '</a>' . '</td>
				<td width="95%" nowrap="nowrap">' . $copymode_selector . ' ' . $selector_menu . '</td>
				<td>' . '<a href="' . htmlspecialchars($rmall_url) . '#clip_head">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-close', array('title' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:buttons.clear', TRUE))) . '</a></td>
			</tr>';
		// Print header and content for the NORMAL tab:
		$out[] = '
			<tr class="bgColor5">
				<td colspan="3"><a href="' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(array('CB' => array('setP' => 'normal')))) . '#clip_head">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon(('actions-view-table-' . ($this->current == 'normal' ? 'collapse' : 'expand'))) . $this->padTitleWrap('Normal', 'normal') . '</a></td>
			</tr>';
		if ($this->current == 'normal') {
			$out = array_merge($out, $this->printContentFromTab('normal'));
		}
		// Print header and content for the NUMERIC tabs:
		for ($a = 1; $a <= $this->numberTabs; $a++) {
			$out[] = '
				<tr class="bgColor5">
					<td colspan="3"><a href="' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(array('CB' => array('setP' => ('tab_' . $a))))) . '#clip_head">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon(('actions-view-table-' . ($this->current == 'tab_' . $a ? 'collapse' : 'expand'))) . $this->padTitleWrap(($this->clLabel('cliptabs') . $a), ('tab_' . $a)) . '</a></td>
				</tr>';
			if ($this->current == 'tab_' . $a) {
				$out = array_merge($out, $this->printContentFromTab('tab_' . $a));
			}
		}
		// Wrap accumulated rows in a table:
		$output = '<a name="clip_head"></a>

			<!--
				TYPO3 Clipboard:
			-->
			<table cellpadding="0" cellspacing="1" border="0" width="290" id="typo3-clipboard">
				' . implode('', $out) . '
			</table>';
		// Wrap in form tag:
		$output = '<form action="">' . $output . '</form>';
		// Return the accumulated content:
		return $output;
	}

	/**
	 * Print the content on a pad. Called from ->printClipboard()
	 *
	 * @access private
	 * @param string $pad Pad reference
	 * @return array Array with table rows for the clipboard.
	 * @todo Define visibility
	 */
	public function printContentFromTab($pad) {
		$lines = array();
		if (is_array($this->clipData[$pad]['el'])) {
			foreach ($this->clipData[$pad]['el'] as $k => $v) {
				if ($v) {
					list($table, $uid) = explode('|', $k);
					$bgColClass = $table == '_FILE' && $this->fileMode || $table != '_FILE' && !$this->fileMode ? 'bgColor4-20' : 'bgColor4';
					// Rendering files/directories on the clipboard
					if ($table == '_FILE') {
						$fileObject = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->retrieveFileOrFolderObject($v);
						if ($fileObject) {
							$thumb = '';
							$folder = $fileObject instanceof \TYPO3\CMS\Core\Resource\Folder;
							$size = $folder ? '' : '(' . \TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($fileObject->getSize()) . 'bytes)';
							$icon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForFile($folder ? 'folder' : strtolower($fileObject->getExtension()), array('style' => 'margin: 0 20px;', 'title' => $fileObject->getName() . ' ' . $size));
							if ($this->clipData['_setThumb'] && \TYPO3\CMS\Core\Utility\GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $fileObject->getExtension())) {
								$thumb = '<br />' . \TYPO3\CMS\Backend\Utility\BackendUtility::getThumbNail(($this->backPath . 'thumbs.php'), $v, ' vspace="4"');
							}
							$lines[] = '
								<tr>
									<td class="' . $bgColClass . '">' . $icon . '</td>
									<td class="' . $bgColClass . '" nowrap="nowrap" width="95%">&nbsp;' . $this->linkItemText(htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($fileObject->getName(), $GLOBALS['BE_USER']->uc['titleLen'])), $fileObject->getName()) . ($pad == 'normal' ? ' <strong>(' . ($this->clipData['normal']['mode'] == 'copy' ? $this->clLabel('copy', 'cm') : $this->clLabel('cut', 'cm')) . ')</strong>' : '') . '&nbsp;' . $thumb . '</td>
									<td class="' . $bgColClass . '" align="center" nowrap="nowrap">' . '<a href="#" onclick="' . htmlspecialchars(('top.launchView(\'' . $table . '\', \'' . $v . '\'); return false;')) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-info', array('title' => $this->clLabel('info', 'cm'))) . '</a>' . '<a href="' . htmlspecialchars($this->removeUrl('_FILE', \TYPO3\CMS\Core\Utility\GeneralUtility::shortmd5($v))) . '#clip_head">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-selection-delete', array('title' => $this->clLabel('removeItem'))) . '</a>' . '</td>
								</tr>';
						} else {
							// If the file did not exist (or is illegal) then it is removed from the clipboard immediately:
							unset($this->clipData[$pad]['el'][$k]);
							$this->changed = 1;
						}
					} else {
						// Rendering records:
						$rec = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL($table, $uid);
						if (is_array($rec)) {
							$lines[] = '
								<tr>
									<td class="' . $bgColClass . '">' . $this->linkItemText(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord($table, $rec, array('style' => 'margin: 0 20px;', 'title' => htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::getRecordIconAltText($rec, $table)))), $rec, $table) . '</td>
									<td class="' . $bgColClass . '" nowrap="nowrap" width="95%">&nbsp;' . $this->linkItemText(htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs(\TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle($table, $rec), $GLOBALS['BE_USER']->uc['titleLen'])), $rec, $table) . ($pad == 'normal' ? ' <strong>(' . ($this->clipData['normal']['mode'] == 'copy' ? $this->clLabel('copy', 'cm') : $this->clLabel('cut', 'cm')) . ')</strong>' : '') . '&nbsp;</td>
									<td class="' . $bgColClass . '" align="center" nowrap="nowrap">' . '<a href="#" onclick="' . htmlspecialchars(('top.launchView(\'' . $table . '\', \'' . intval($uid) . '\'); return false;')) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-info', array('title' => $this->clLabel('info', 'cm'))) . '</a>' . '<a href="' . htmlspecialchars($this->removeUrl($table, $uid)) . '#clip_head">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-selection-delete', array('title' => $this->clLabel('removeItem'))) . '</a>' . '</td>
								</tr>';
							$localizationData = $this->getLocalizations($table, $rec, $bgColClass, $pad);
							if ($localizationData) {
								$lines[] = $localizationData;
							}
						} else {
							unset($this->clipData[$pad]['el'][$k]);
							$this->changed = 1;
						}
					}
				}
			}
		}
		if (!count($lines)) {
			$lines[] = '
								<tr>
									<td class="bgColor4"><img src="clear.gif" width="56" height="1" alt="" /></td>
									<td colspan="2" class="bgColor4" nowrap="nowrap" width="95%">&nbsp;<em>(' . $this->clLabel('clipNoEl') . ')</em>&nbsp;</td>
								</tr>';
		}
		$this->endClipboard();
		return $lines;
	}

	/**
	 * Gets all localizations of the current record.
	 *
	 * @param string $table The table
	 * @param array $parentRec The current record
	 * @param string $bgColClass Class for the background color of a column
	 * @param string $pad Pad reference
	 * @return string HTML table rows
	 * @todo Define visibility
	 */
	public function getLocalizations($table, $parentRec, $bgColClass, $pad) {
		$lines = array();
		$tcaCtrl = $GLOBALS['TCA'][$table]['ctrl'];
		if ($table != 'pages' && \TYPO3\CMS\Backend\Utility\BackendUtility::isTableLocalizable($table) && !$tcaCtrl['transOrigPointerTable']) {
			$where = array();
			$where[] = $tcaCtrl['transOrigPointerField'] . '=' . intval($parentRec['uid']);
			$where[] = $tcaCtrl['languageField'] . '<>0';
			if (isset($tcaCtrl['delete']) && $tcaCtrl['delete']) {
				$where[] = $tcaCtrl['delete'] . '=0';
			}
			if (isset($tcaCtrl['versioningWS']) && $tcaCtrl['versioningWS']) {
				$where[] = 't3ver_wsid=' . $parentRec['t3ver_wsid'];
			}
			$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', $table, implode(' AND ', $where));
			if (is_array($rows)) {
				$modeData = '';
				if ($pad == 'normal') {
					$mode = $this->clipData['normal']['mode'] == 'copy' ? 'copy' : 'cut';
					$modeData = ' <strong>(' . $this->clLabel($mode, 'cm') . ')</strong>';
				}
				foreach ($rows as $rec) {
					$lines[] = '
					<tr>
						<td class="' . $bgColClass . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord($table, $rec, array('style' => 'margin-left: 38px;')) . '</td>
						<td class="' . $bgColClass . '" nowrap="nowrap" width="95%">&nbsp;' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs(\TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle($table, $rec), $GLOBALS['BE_USER']->uc['titleLen'])) . $modeData . '&nbsp;</td>
						<td class="' . $bgColClass . '" align="center" nowrap="nowrap">&nbsp;</td>
					</tr>';
				}
			}
		}
		return implode('', $lines);
	}

	/**
	 * Wraps title of pad in bold-tags and maybe the number of elements if any.
	 *
	 * @param string $str String (already htmlspecialchars()'ed)
	 * @param string $pad Pad reference
	 * @return string HTML output (htmlspecialchar'ed content inside of tags.)
	 * @todo Define visibility
	 */
	public function padTitleWrap($str, $pad) {
		$el = count($this->elFromTable($this->fileMode ? '_FILE' : '', $pad));
		if ($el) {
			return '<strong>' . $str . '</strong> (' . ($pad == 'normal' ? ($this->clipData['normal']['mode'] == 'copy' ? $this->clLabel('copy', 'cm') : $this->clLabel('cut', 'cm')) : htmlspecialchars($el)) . ')';
		} else {
			return $GLOBALS['TBE_TEMPLATE']->dfw($str);
		}
	}

	/**
	 * Wraps the title of the items listed in link-tags. The items will link to the page/folder where they originate from
	 *
	 * @param string $str Title of element - must be htmlspecialchar'ed on beforehand.
	 * @param mixed $rec If array, a record is expected. If string, its a path
	 * @param string $table Table name
	 * @return string
	 * @todo Define visibility
	 */
	public function linkItemText($str, $rec, $table = '') {
		if (is_array($rec) && $table) {
			if ($this->fileMode) {
				$str = $GLOBALS['TBE_TEMPLATE']->dfw($str);
			} else {
				if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('recordlist')) {
					$str = '<a href="' . htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('web_list', array('id' => $rec['pid']), $this->backPath)) . '">' . $str . '</a>';
				}
			}
		} elseif (file_exists($rec)) {
			if (!$this->fileMode) {
				$str = $GLOBALS['TBE_TEMPLATE']->dfw($str);
			} else {
				if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('filelist')) {
					$str = '<a href="' . htmlspecialchars(($this->backPath . \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('file_list') . '&id=' . dirname($rec))) . '">' . $str . '</a>';
				}
			}
		}
		return $str;
	}

	/**
	 * Returns the select-url for database elements
	 *
	 * @param string $table Table name
	 * @param integer $uid Uid of record
	 * @param boolean $copy If set, copymode will be enabled
	 * @param boolean $deselect If set, the link will deselect, otherwise select.
	 * @param array $baseArray The base array of GET vars to be sent in addition. Notice that current GET vars WILL automatically be included.
	 * @return string URL linking to the current script but with the CB array set to select the element with table/uid
	 * @todo Define visibility
	 */
	public function selUrlDB($table, $uid, $copy = 0, $deselect = 0, $baseArray = array()) {
		$CB = array('el' => array(rawurlencode($table . '|' . $uid) => $deselect ? 0 : 1));
		if ($copy) {
			$CB['setCopyMode'] = 1;
		}
		$baseArray['CB'] = $CB;
		return \TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript($baseArray);
	}

	/**
	 * Returns the select-url for files
	 *
	 * @param string $path Filepath
	 * @param boolean $copy If set, copymode will be enabled
	 * @param boolean $deselect If set, the link will deselect, otherwise select.
	 * @param array $baseArray The base array of GET vars to be sent in addition. Notice that current GET vars WILL automatically be included.
	 * @return string URL linking to the current script but with the CB array set to select the path
	 * @todo Define visibility
	 */
	public function selUrlFile($path, $copy = 0, $deselect = 0, $baseArray = array()) {
		$CB = array('el' => array(rawurlencode('_FILE|' . \TYPO3\CMS\Core\Utility\GeneralUtility::shortmd5($path)) => $deselect ? '' : $path));
		if ($copy) {
			$CB['setCopyMode'] = 1;
		}
		$baseArray['CB'] = $CB;
		return \TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript($baseArray);
	}

	/**
	 * pasteUrl of the element (database and file)
	 * For the meaning of $table and $uid, please read from ->makePasteCmdArray!!!
	 * The URL will point to tce_file or tce_db depending in $table
	 *
	 * @param string $table Tablename (_FILE for files)
	 * @param mixed $uid "destination": can be positive or negative indicating how the paste is done (paste into / paste after)
	 * @param boolean $setRedirect If set, then the redirect URL will point back to the current script, but with CB reset.
	 * @return string
	 * @todo Define visibility
	 */
	public function pasteUrl($table, $uid, $setRedirect = 1) {
		$rU = $this->backPath . ($table == '_FILE' ? 'tce_file.php' : 'tce_db.php') . '?' . ($setRedirect ? 'redirect=' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(array('CB' => ''))) : '') . '&vC=' . $GLOBALS['BE_USER']->veriCode() . '&prErr=1&uPT=1' . '&CB[paste]=' . rawurlencode(($table . '|' . $uid)) . '&CB[pad]=' . $this->current . \TYPO3\CMS\Backend\Utility\BackendUtility::getUrlToken('tceAction');
		return $rU;
	}

	/**
	 * deleteUrl for current pad
	 *
	 * @param boolean $setRedirect If set, then the redirect URL will point back to the current script, but with CB reset.
	 * @param boolean $file If set, then the URL will link to the tce_file.php script in the typo3/ dir.
	 * @return string
	 * @todo Define visibility
	 */
	public function deleteUrl($setRedirect = 1, $file = 0) {
		$rU = $this->backPath . ($file ? 'tce_file.php' : 'tce_db.php') . '?' . ($setRedirect ? 'redirect=' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(array('CB' => ''))) : '') . '&vC=' . $GLOBALS['BE_USER']->veriCode() . '&prErr=1&uPT=1' . '&CB[delete]=1' . '&CB[pad]=' . $this->current . \TYPO3\CMS\Backend\Utility\BackendUtility::getUrlToken('tceAction');
		return $rU;
	}

	/**
	 * editUrl of all current elements
	 * ONLY database
	 * Links to alt_doc.php
	 *
	 * @return string The URL to alt_doc.php with parameters.
	 * @todo Define visibility
	 */
	public function editUrl() {
		// All records
		$elements = $this->elFromTable('');
		$editCMDArray = array();
		foreach ($elements as $tP => $value) {
			list($table, $uid) = explode('|', $tP);
			$editCMDArray[] = '&edit[' . $table . '][' . $uid . ']=edit';
		}
		$rU = $this->backPath . 'alt_doc.php?' . implode('', $editCMDArray);
		return $rU;
	}

	/**
	 * Returns the remove-url (file and db)
	 * for file $table='_FILE' and $uid = shortmd5 hash of path
	 *
	 * @param string $table Tablename
	 * @param string $uid Uid integer/shortmd5 hash
	 * @return string URL
	 * @todo Define visibility
	 */
	public function removeUrl($table, $uid) {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(array('CB' => array('remove' => $table . '|' . $uid)));
	}

	/**
	 * Returns confirm JavaScript message
	 *
	 * @param string $table Table name
	 * @param mixed $rec For records its an array, for files its a string (path)
	 * @param string $type Type-code
	 * @param array $clElements Array of selected elements
	 * @return string JavaScript "confirm" message
	 * @todo Define visibility
	 */
	public function confirmMsg($table, $rec, $type, $clElements) {
		if ($GLOBALS['BE_USER']->jsConfirmation(2)) {
			$labelKey = 'LLL:EXT:lang/locallang_core.xlf:mess.' . ($this->currentMode() == 'copy' ? 'copy' : 'move') . ($this->current == 'normal' ? '' : 'cb') . '_' . $type;
			$msg = $GLOBALS['LANG']->sL($labelKey);
			if ($table == '_FILE') {
				$thisRecTitle = basename($rec);
				if ($this->current == 'normal') {
					$selItem = reset($clElements);
					$selRecTitle = basename($selItem);
				} else {
					$selRecTitle = count($clElements);
				}
			} else {
				$thisRecTitle = $table == 'pages' && !is_array($rec) ? $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] : \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle($table, $rec);
				if ($this->current == 'normal') {
					$selItem = $this->getSelectedRecord();
					$selRecTitle = $selItem['_RECORD_TITLE'];
				} else {
					$selRecTitle = count($clElements);
				}
			}
			// Message
			$conf = 'confirm(' . $GLOBALS['LANG']->JScharCode(sprintf($msg, \TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($selRecTitle, 30), \TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($thisRecTitle, 30))) . ')';
		} else {
			$conf = '';
		}
		return $conf;
	}

	/**
	 * Clipboard label - getting from "EXT:lang/locallang_core.xlf:"
	 *
	 * @param string $key Label Key
	 * @param string $Akey Alternative key to "labels
	 * @return string
	 * @todo Define visibility
	 */
	public function clLabel($key, $Akey = 'labels') {
		return htmlspecialchars($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:' . $Akey . '.' . $key));
	}

	/**
	 * Creates GET parameters for linking to the export module.
	 *
	 * @return string GET parameters for current clipboard content to be exported.
	 * @todo Define visibility
	 */
	public function exportClipElementParameters() {
		// Init
		$pad = $this->current;
		$params = array();
		$params[] = 'tx_impexp[action]=export';
		// Traverse items:
		if (is_array($this->clipData[$pad]['el'])) {
			foreach ($this->clipData[$pad]['el'] as $k => $v) {
				if ($v) {
					list($table, $uid) = explode('|', $k);
					// Rendering files/directories on the clipboard
					if ($table == '_FILE') {
						if (file_exists($v) && \TYPO3\CMS\Core\Utility\GeneralUtility::isAllowedAbsPath($v)) {
							$params[] = 'tx_impexp[' . (is_dir($v) ? 'dir' : 'file') . '][]=' . rawurlencode($v);
						}
					} else {
						// Rendering records:
						$rec = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($table, $uid);
						if (is_array($rec)) {
							$params[] = 'tx_impexp[record][]=' . rawurlencode(($table . ':' . $uid));
						}
					}
				}
			}
		}
		return '?' . implode('&', $params);
	}

	/*****************************************
	 *
	 * Helper functions
	 *
	 ****************************************/
	/**
	 * Removes element on clipboard
	 *
	 * @param string $el Key of element in ->clipData array
	 * @return void
	 * @todo Define visibility
	 */
	public function removeElement($el) {
		unset($this->clipData[$this->current]['el'][$el]);
		$this->changed = 1;
	}

	/**
	 * Saves the clipboard, no questions asked.
	 * Use ->endClipboard normally (as it checks if changes has been done so saving is necessary)
	 *
	 * @access private
	 * @return void
	 * @todo Define visibility
	 */
	public function saveClipboard() {
		$GLOBALS['BE_USER']->pushModuleData('clipboard', $this->clipData);
	}

	/**
	 * Returns the current mode, 'copy' or 'cut'
	 *
	 * @return string "copy" or "cut
	 * @todo Define visibility
	 */
	public function currentMode() {
		return $this->clipData[$this->current]['mode'] == 'copy' ? 'copy' : 'cut';
	}

	/**
	 * This traverses the elements on the current clipboard pane
	 * and unsets elements which does not exist anymore or are disabled.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function cleanCurrent() {
		if (is_array($this->clipData[$this->current]['el'])) {
			foreach ($this->clipData[$this->current]['el'] as $k => $v) {
				list($table, $uid) = explode('|', $k);
				if ($table != '_FILE') {
					if (!$v || !is_array(\TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($table, $uid, 'uid'))) {
						unset($this->clipData[$this->current]['el'][$k]);
						$this->changed = 1;
					}
				} else {
					if (!$v) {
						unset($this->clipData[$this->current]['el'][$k]);
						$this->changed = 1;
					} else {
						try {
							\TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->retrieveFileOrFolderObject($v);
						} catch (\TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException $e) {
							// The file has been deleted in the meantime, so just remove it silently
							unset($this->clipData[$this->current]['el'][$k]);
						}
					}
				}
			}
		}
	}

	/**
	 * Counts the number of elements from the table $matchTable. If $matchTable is blank, all tables (except '_FILE' of course) is counted.
	 *
	 * @param string $matchTable Table to match/count for.
	 * @param string $pad Can optionally be used to set another pad than the current.
	 * @return array Array with keys from the CB.
	 * @todo Define visibility
	 */
	public function elFromTable($matchTable = '', $pad = '') {
		$pad = $pad ? $pad : $this->current;
		$list = array();
		if (is_array($this->clipData[$pad]['el'])) {
			foreach ($this->clipData[$pad]['el'] as $k => $v) {
				if ($v) {
					list($table, $uid) = explode('|', $k);
					if ($table != '_FILE') {
						if ((!$matchTable || (string) $table == (string) $matchTable) && $GLOBALS['TCA'][$table]) {
							$list[$k] = $pad == 'normal' ? $v : $uid;
						}
					} else {
						if ((string) $table == (string) $matchTable) {
							$list[$k] = $v;
						}
					}
				}
			}
		}
		return $list;
	}

	/**
	 * Verifies if the item $table/$uid is on the current pad.
	 * If the pad is "normal", the mode value is returned if the element existed. Thus you'll know if the item was copy or cut moded...
	 *
	 * @param string $table Table name, (_FILE for files...)
	 * @param integer $uid Element uid (path for files)
	 * @return string
	 * @todo Define visibility
	 */
	public function isSelected($table, $uid) {
		$k = $table . '|' . $uid;
		return $this->clipData[$this->current]['el'][$k] ? ($this->current == 'normal' ? $this->currentMode() : 1) : '';
	}

	/**
	 * Returns item record $table,$uid if selected on current clipboard
	 * If table and uid is blank, the first element is returned.
	 * Makes sense only for DB records - not files!
	 *
	 * @param string $table Table name
	 * @param integer $uid Element uid
	 * @return array Element record with extra field _RECORD_TITLE set to the title of the record
	 * @todo Define visibility
	 */
	public function getSelectedRecord($table = '', $uid = '') {
		if (!$table && !$uid) {
			$elArr = $this->elFromTable('');
			reset($elArr);
			list($table, $uid) = explode('|', key($elArr));
		}
		if ($this->isSelected($table, $uid)) {
			$selRec = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL($table, $uid);
			$selRec['_RECORD_TITLE'] = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle($table, $selRec);
			return $selRec;
		}
	}

	/**
	 * Reports if the current pad has elements (does not check file/DB type OR if file/DBrecord exists or not. Only counting array)
	 *
	 * @return boolean TRUE if elements exist.
	 * @todo Define visibility
	 */
	public function isElements() {
		return is_array($this->clipData[$this->current]['el']) && count($this->clipData[$this->current]['el']);
	}

	/*****************************************
	 *
	 * FOR USE IN tce_db.php:
	 *
	 ****************************************/
	/**
	 * Applies the proper paste configuration in the $cmd array send to tce_db.php.
	 * $ref is the target, see description below.
	 * The current pad is pasted
	 *
	 * $ref: [tablename]:[paste-uid].
	 * Tablename is the name of the table from which elements *on the current clipboard* is pasted with the 'pid' paste-uid.
	 * No tablename means that all items on the clipboard (non-files) are pasted. This requires paste-uid to be positive though.
	 * so 'tt_content:-3'	means 'paste tt_content elements on the clipboard to AFTER tt_content:3 record
	 * 'tt_content:30'	means 'paste tt_content elements on the clipboard into page with id 30
	 * ':30'	means 'paste ALL database elements on the clipboard into page with id 30
	 * ':-30'	not valid.
	 *
	 * @param string $ref [tablename]:[paste-uid], see description
	 * @param array $CMD Command-array
	 * @return array Modified Command-array
	 * @todo Define visibility
	 */
	public function makePasteCmdArray($ref, $CMD) {
		list($pTable, $pUid) = explode('|', $ref);
		$pUid = intval($pUid);
		// pUid must be set and if pTable is not set (that means paste ALL elements)
		// the uid MUST be positive/zero (pointing to page id)
		if ($pTable || $pUid >= 0) {
			$elements = $this->elFromTable($pTable);
			// So the order is preserved.
			$elements = array_reverse($elements);
			$mode = $this->currentMode() == 'copy' ? 'copy' : 'move';
			// Traverse elements and make CMD array
			foreach ($elements as $tP => $value) {
				list($table, $uid) = explode('|', $tP);
				if (!is_array($CMD[$table])) {
					$CMD[$table] = array();
				}
				$CMD[$table][$uid][$mode] = $pUid;
				if ($mode == 'move') {
					$this->removeElement($tP);
				}
			}
			$this->endClipboard();
		}
		return $CMD;
	}

	/**
	 * Delete record entries in CMD array
	 *
	 * @param array $CMD Command-array
	 * @return array Modified Command-array
	 * @todo Define visibility
	 */
	public function makeDeleteCmdArray($CMD) {
		// all records
		$elements = $this->elFromTable('');
		foreach ($elements as $tP => $value) {
			list($table, $uid) = explode('|', $tP);
			if (!is_array($CMD[$table])) {
				$CMD[$table] = array();
			}
			$CMD[$table][$uid]['delete'] = 1;
			$this->removeElement($tP);
		}
		$this->endClipboard();
		return $CMD;
	}

	/*****************************************
	 *
	 * FOR USE IN tce_file.php:
	 *
	 ****************************************/
	/**
	 * Applies the proper paste configuration in the $file array send to tce_file.php.
	 * The current pad is pasted
	 *
	 * @param string $ref Reference to element (splitted by "|")
	 * @param array $FILE Command-array
	 * @return array Modified Command-array
	 * @todo Define visibility
	 */
	public function makePasteCmdArray_file($ref, $FILE) {
		list($pTable, $pUid) = explode('|', $ref);
		$elements = $this->elFromTable('_FILE');
		$mode = $this->currentMode() == 'copy' ? 'copy' : 'move';
		// Traverse elements and make CMD array
		foreach ($elements as $tP => $path) {
			$FILE[$mode][] = array('data' => $path, 'target' => $pUid);
			if ($mode == 'move') {
				$this->removeElement($tP);
			}
		}
		$this->endClipboard();
		return $FILE;
	}

	/**
	 * Delete files in CMD array
	 *
	 * @param array $FILE Command-array
	 * @return array Modified Command-array
	 * @todo Define visibility
	 */
	public function makeDeleteCmdArray_file($FILE) {
		$elements = $this->elFromTable('_FILE');
		// Traverse elements and make CMD array
		foreach ($elements as $tP => $path) {
			$FILE['delete'][] = array('data' => $path);
			$this->removeElement($tP);
		}
		$this->endClipboard();
		return $FILE;
	}

}


?>