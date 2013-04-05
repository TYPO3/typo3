<?php
namespace TYPO3\CMS\Backend\Controller\Wizard;

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
 * Script Class for rendering the full screen RTE display
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class RteController {

	// Internal, dynamic:
	/**
	 * document template object
	 *
	 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
	 * @todo Define visibility
	 */
	public $doc;

	// Content accumulation for the module.
	/**
	 * @todo Define visibility
	 */
	public $content;

	// Internal, static: GPvars
	// Wizard parameters, coming from TCEforms linking to the wizard.
	/**
	 * @todo Define visibility
	 */
	public $P;

	// If set, launch a new window with the current records pid.
	/**
	 * @todo Define visibility
	 */
	public $popView;

	// Set to the URL of this script including variables which is needed to re-display the form. See main()
	/**
	 * @todo Define visibility
	 */
	public $R_URI;

	/**
	 * Initialization of the class
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function init() {
		// Setting GPvars:
		$this->P = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('P');
		$this->popView = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('popView');
		$this->R_URI = \TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(array('popView' => ''));
		// "Module name":
		$this->MCONF['name'] = 'xMOD_wizard_rte.php';
		// Starting the document template object:
		$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate('templates/wizard_rte.html');
		// Need to NOT have the page wrapped in DIV since if we do that we destroy
		// the feature that the RTE spans the whole height of the page!!!
		$this->doc->divClass = '';
		$this->doc->form = '<form action="tce_db.php" method="post" enctype="' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'] . '" name="editform" onsubmit="return TBE_EDITOR.checkSubmit(1);">';
	}

	/**
	 * Main function, rendering the document with the iframe with the RTE in.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function main() {
		// Translate id to the workspace version:
		if ($versionRec = \TYPO3\CMS\Backend\Utility\BackendUtility::getWorkspaceVersionOfRecord($GLOBALS['BE_USER']->workspace, $this->P['table'], $this->P['uid'], 'uid')) {
			$this->P['uid'] = $versionRec['uid'];
		}
		// If all parameters are available:
		if ($this->P['table'] && $this->P['field'] && $this->P['uid'] && $this->checkEditAccess($this->P['table'], $this->P['uid'])) {
			// Getting the raw record (we need only the pid-value from here...)
			$rawRec = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($this->P['table'], $this->P['uid']);
			\TYPO3\CMS\Backend\Utility\BackendUtility::fixVersioningPid($this->P['table'], $rawRec);
			// Setting JavaScript, including the pid value for viewing:
			$this->doc->JScode = $this->doc->wrapScriptTags('
					function jumpToUrl(URL,formEl) {	//
						if (document.editform) {
							if (!TBE_EDITOR.isFormChanged()) {
								window.location.href = URL;
							} else if (formEl) {
								if (formEl.type=="checkbox") formEl.checked = formEl.checked ? 0 : 1;
							}
						} else window.location.href = URL;
					}
				' . ($this->popView ? \TYPO3\CMS\Backend\Utility\BackendUtility::viewOnClick($rawRec['pid'], '', \TYPO3\CMS\Backend\Utility\BackendUtility::BEgetRootLine($rawRec['pid'])) : '') . '
			');
			// Initialize TCeforms - for rendering the field:
			$tceforms = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Form\\FormEngine');
			// Init...
			$tceforms->initDefaultBEMode();
			// SPECIAL: Disables all wizards - we are NOT going to need them.
			$tceforms->disableWizards = 1;
			// SPECIAL: Setting background color of the RTE to ordinary background
			$tceforms->colorScheme[0] = $this->doc->bgColor;
			// Initialize style for RTE object:
			// Getting reference to the RTE object used to render the field!
			$RTEobj = \TYPO3\CMS\Backend\Utility\BackendUtility::RTEgetObj();
			if ($RTEobj->ID == 'rte') {
				$RTEobj->RTEdivStyle = 'position:relative; left:0px; top:0px; height:100%; width:100%; border:solid 0px;';
			}
			// Fetching content of record:
			$trData = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Form\\DataPreprocessor');
			$trData->lockRecords = 1;
			$trData->fetchRecord($this->P['table'], $this->P['uid'], '');
			// Getting the processed record content out:
			$rec = reset($trData->regTableItems_data);
			$rec['uid'] = $this->P['uid'];
			$rec['pid'] = $rawRec['pid'];
			// TSconfig, setting width:
			$fieldTSConfig = $tceforms->setTSconfig($this->P['table'], $rec, $this->P['field']);
			if (strcmp($fieldTSConfig['RTEfullScreenWidth'], '')) {
				$width = $fieldTSConfig['RTEfullScreenWidth'];
			} else {
				$width = '100%';
			}
			// Get the form field and wrap it in the table with the buttons:
			$formContent = $tceforms->getSoloField($this->P['table'], $rec, $this->P['field']);
			$formContent = '


			<!--
				RTE wizard:
			-->
				<table border="0" cellpadding="0" cellspacing="0" width="' . $width . '" id="typo3-rtewizard">
					<tr>
						<td width="' . $width . '" colspan="2" id="c-formContent">' . $formContent . '</td>
						<td></td>
					</tr>
				</table>';
			// Adding hidden fields:
			$formContent .= '<input type="hidden" name="redirect" value="' . htmlspecialchars($this->R_URI) . '" />
						<input type="hidden" name="_serialNumber" value="' . md5(microtime()) . '" />' . \TYPO3\CMS\Backend\Form\FormEngine::getHiddenTokenField('tceAction');
			// Finally, add the whole setup:
			$this->content .= $tceforms->printNeededJSFunctions_top() . $formContent . $tceforms->printNeededJSFunctions();
		} else {
			// ERROR:
			$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('forms_title'), '<span class="typo3-red">' . $GLOBALS['LANG']->getLL('table_noData', 1) . '</span>', 0, 1);
		}
		// Setting up the buttons and markers for docheader
		$docHeaderButtons = $this->getButtons();
		$markers['CONTENT'] = $this->content;
		// Build the <body> for the module
		$this->content = $this->doc->startPage('');
		$this->content .= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		$this->content .= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function printContent() {
		$this->content .= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
		echo $this->content;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return array All available buttons as an assoc. array
	 */
	protected function getButtons() {
		$buttons = array(
			'close' => '',
			'save' => '',
			'save_view' => '',
			'save_close' => '',
			'shortcut' => '',
			'undo' => ''
		);
		if ($this->P['table'] && $this->P['field'] && $this->P['uid'] && $this->checkEditAccess($this->P['table'], $this->P['uid'])) {
			$closeUrl = \TYPO3\CMS\Core\Utility\GeneralUtility::sanitizeLocalUrl($this->P['returnUrl']);
			// Getting settings for the undo button:
			$undoButton = 0;
			$undoRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tstamp', 'sys_history', 'tablename=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->P['table'], 'sys_history') . ' AND recuid=' . intval($this->P['uid']), '', 'tstamp DESC', '1');
			if ($undoButtonR = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($undoRes)) {
				$undoButton = 1;
			}
			// Close
			$buttons['close'] = '<a href="#" onclick="' . htmlspecialchars(('jumpToUrl(unescape(\'' . rawurlencode($closeUrl) . '\')); return false;')) . '">' . '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->doc->backPath, 'gfx/closedok.gif') . ' class="c-inputButton" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:rm.closeDoc', 1) . '" alt="" />' . '</a>';
			// Save
			$buttons['save'] = '<a href="#" onclick="TBE_EDITOR.checkAndDoSubmit(1); return false;">' . '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->doc->backPath, 'gfx/savedok.gif') . ' class="c-inputButton" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveDoc', 1) . '" alt="" />' . '</a>';
			// Save & View
			if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('cms')) {
				$buttons['save_view'] = '<a href="#" onclick="' . htmlspecialchars('document.editform.redirect.value+=\'&popView=1\'; TBE_EDITOR.checkAndDoSubmit(1); return false;') . '">' . '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->doc->backPath, 'gfx/savedokshow.gif') . ' class="c-inputButton" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveDocShow', 1) . '" alt="" />' . '</a>';
			}
			// Save & Close
			$buttons['save_close'] = '<input type="image" class="c-inputButton" onclick="' . htmlspecialchars(('document.editform.redirect.value=\'' . $closeUrl . '\'; TBE_EDITOR.checkAndDoSubmit(1); return false;')) . '" name="_saveandclosedok"' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->doc->backPath, 'gfx/saveandclosedok.gif', '') . ' title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveCloseDoc', 1) . '" />';
			// Undo/Revert:
			if ($undoButton) {
				$buttons['undo'] = '<a href="#" onclick="' . htmlspecialchars(('window.location.href=\'show_rechis.php?element=' . rawurlencode(($this->P['table'] . ':' . $this->P['uid'])) . '&revert=' . rawurlencode(('field:' . $this->P['field'])) . '&sumUp=-1&returnUrl=' . rawurlencode($this->R_URI) . '\'; return false;')) . '">' . '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->doc->backPath, 'gfx/undo.gif') . ' class="c-inputButton" title="' . htmlspecialchars(sprintf($GLOBALS['LANG']->getLL('rte_undoLastChange'), \TYPO3\CMS\Backend\Utility\BackendUtility::calcAge(($GLOBALS['EXEC_TIME'] - $undoButtonR['tstamp']), $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.minutesHoursDaysYears')))) . '" alt="" />' . '</a>';
			}
			// Shortcut
			if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
				$buttons['shortcut'] = $this->doc->makeShortcutIcon('P', '', $this->MCONF['name'], 1);
			}
		}
		return $buttons;
	}

	/**
	 * Checks access for element
	 *
	 * @param string $table Table name
	 * @param integer $uid Record uid
	 * @return void
	 * @todo Define visibility
	 */
	public function checkEditAccess($table, $uid) {
		$calcPRec = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($table, $uid);
		\TYPO3\CMS\Backend\Utility\BackendUtility::fixVersioningPid($table, $calcPRec);
		if (is_array($calcPRec)) {
			// If pages:
			if ($table == 'pages') {
				$CALC_PERMS = $GLOBALS['BE_USER']->calcPerms($calcPRec);
				$hasAccess = $CALC_PERMS & 2 ? TRUE : FALSE;
			} else {
				// Fetching pid-record first.
				$CALC_PERMS = $GLOBALS['BE_USER']->calcPerms(\TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', $calcPRec['pid']));
				$hasAccess = $CALC_PERMS & 16 ? TRUE : FALSE;
			}
			// Check internals regarding access:
			if ($hasAccess) {
				$hasAccess = $GLOBALS['BE_USER']->recordEditAccessInternals($table, $calcPRec);
			}
		} else {
			$hasAccess = FALSE;
		}
		return $hasAccess;
	}

}


?>