<?php
namespace TYPO3\CMS\Backend\Controller\Wizard;

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
	 * Constructor
	 */
	public function __construct() {
		$GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_wizards.xlf');
		$GLOBALS['SOBE'] = $this;

		$this->init();
	}

	/**
	 * Initialization of the class
	 *
	 * @return void
	 */
	protected function init() {
		// Setting GPvars:
		$this->P = GeneralUtility::_GP('P');
		$this->popView = GeneralUtility::_GP('popView');
		$this->R_URI = GeneralUtility::linkThisScript(array('popView' => ''));
		// "Module name":
		$this->MCONF['name'] = 'wizard_rte';
		// Starting the document template object:
		$this->doc = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate('EXT:backend/Resources/Private/Templates/wizard_rte.html');
		// Need to NOT have the page wrapped in DIV since if we do that we destroy
		// the feature that the RTE spans the whole height of the page!!!
		$this->doc->divClass = '';
		$this->doc->form = '<form action="tce_db.php" method="post" enctype="' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'] . '" name="editform" onsubmit="return TBE_EDITOR.checkSubmit(1);">';
	}

	/**
	 * Main function, rendering the document with the iframe with the RTE in.
	 *
	 * @return void
	 */
	public function main() {
		// Translate id to the workspace version:
		if ($versionRec = BackendUtility::getWorkspaceVersionOfRecord($GLOBALS['BE_USER']->workspace, $this->P['table'], $this->P['uid'], 'uid')) {
			$this->P['uid'] = $versionRec['uid'];
		}
		// If all parameters are available:
		if ($this->P['table'] && $this->P['field'] && $this->P['uid'] && $this->checkEditAccess($this->P['table'], $this->P['uid'])) {
			// Getting the raw record (we need only the pid-value from here...)
			$rawRec = BackendUtility::getRecord($this->P['table'], $this->P['uid']);
			BackendUtility::fixVersioningPid($this->P['table'], $rawRec);

			// override the default jumpToUrl
			$this->doc->JScodeArray['jumpToUrl'] = '
		function jumpToUrl(URL,formEl) {
			if (document.editform) {
				if (!TBE_EDITOR.isFormChanged()) {
					window.location.href = URL;
				} else if (formEl) {
					if (formEl.type=="checkbox") formEl.checked = formEl.checked ? 0 : 1;
				}
			} else {
				window.location.href = URL;
			}
		}
';

			// Setting JavaScript of the pid value for viewing:
			if ($this->popView) {
				$this->doc->JScode = $this->doc->wrapScriptTags(BackendUtility::viewOnClick($rawRec['pid'], '', BackendUtility::BEgetRootLine($rawRec['pid'])));
			}
			// Initialize TCeforms - for rendering the field:
			$tceforms = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Form\\FormEngine');
			// Init...
			$tceforms->initDefaultBEMode();
			// SPECIAL: Disables all wizards - we are NOT going to need them.
			$tceforms->disableWizards = 1;
			// SPECIAL: Setting background color of the RTE to ordinary background
			$tceforms->colorScheme[0] = $this->doc->bgColor;
			// Initialize style for RTE object:
			// Getting reference to the RTE object used to render the field!
			$RTEobj = BackendUtility::RTEgetObj();
			if ($RTEobj->ID == 'rte') {
				$RTEobj->RTEdivStyle = 'position:relative; left:0px; top:0px; height:100%; width:100%; border:solid 0px;';
			}
			// Fetching content of record:
			$trData = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Form\\DataPreprocessor');
			$trData->lockRecords = 1;
			$trData->fetchRecord($this->P['table'], $this->P['uid'], '');
			// Getting the processed record content out:
			$rec = reset($trData->regTableItems_data);
			$rec['uid'] = $this->P['uid'];
			$rec['pid'] = $rawRec['pid'];
			// TSconfig, setting width:
			$fieldTSConfig = $tceforms->setTSconfig($this->P['table'], $rec, $this->P['field']);
			if ((string)$fieldTSConfig['RTEfullScreenWidth'] !== '') {
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
			$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('forms_title'), '<span class="typo3-red">' . $GLOBALS['LANG']->getLL('table_noData', TRUE) . '</span>', 0, 1);
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
			$closeUrl = GeneralUtility::sanitizeLocalUrl($this->P['returnUrl']);
			// Getting settings for the undo button:
			$undoButton = 0;
			$undoRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tstamp', 'sys_history', 'tablename=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->P['table'], 'sys_history') . ' AND recuid=' . (int)$this->P['uid'], '', 'tstamp DESC', '1');
			if ($undoButtonR = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($undoRes)) {
				$undoButton = 1;
			}
			// Close
			$buttons['close'] = '<a href="#" onclick="' . htmlspecialchars('jumpToUrl(unescape(\'' . rawurlencode($closeUrl) . '\')); return false;') . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:rm.closeDoc', TRUE) . '">' . IconUtility::getSpriteIcon('actions-document-close') . '</a>';
			// Save
			$buttons['save'] = IconUtility::getSpriteIcon('actions-document-save', array('html' => '<input type="image" name="_savedok" class="c-inputButton" src="clear.gif" onclick="TBE_EDITOR.checkAndDoSubmit(1); return false;" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveDoc', TRUE) . '" />'));
			// Save & View
			$buttons['save_view'] = IconUtility::getSpriteIcon('actions-document-save-view', array('html' => '<input type="image" class="c-inputButton" name="_savedokview" src="clear.gif" onclick="' . htmlspecialchars('document.editform.redirect.value+=\'&popView=1\'; TBE_EDITOR.checkAndDoSubmit(1); return false;') . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveDocShow', TRUE) . '" />'));
			// Save & Close
			$buttons['save_close'] = IconUtility::getSpriteIcon('actions-document-save-close', array('html' => '<input type="image" class="c-inputButton" name="_saveandclosedok" src="clear.gif" onclick="' . htmlspecialchars('document.editform.redirect.value=\'' . $closeUrl . '\'; TBE_EDITOR.checkAndDoSubmit(1); return false;') . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveCloseDoc', TRUE) . '" />'));
			// Undo/Revert:
			if ($undoButton) {
				$aOnClick = 'window.location.href=' .
					GeneralUtility::quoteJSvalue(
						BackendUtility::getModuleUrl(
							'record_history',
							array(
								'element' => $this->P['table'] . ':' . $this->P['uid'],
								'revert' => 'field:' . $this->P['field'],
								'sumUp' => -1,
								'returnUrl' => $this->R_URI,
							)
						)
					) . '; return false;';
				$buttons['undo'] = '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '"' . ' title="' . htmlspecialchars(sprintf($GLOBALS['LANG']->getLL('undoLastChange'), BackendUtility::calcAge(($GLOBALS['EXEC_TIME'] - $undoButtonR['tstamp']), $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.minutesHoursDaysYears')))) . '">' . IconUtility::getSpriteIcon('actions-edit-undo') . '</a>';
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
	 * @return boolean
	 * @todo Define visibility
	 * @todo: Refactor to remove duplicate code (see FormsController, TableController)
	 */
	public function checkEditAccess($table, $uid) {
		$calcPRec = BackendUtility::getRecord($table, $uid);
		BackendUtility::fixVersioningPid($table, $calcPRec);
		if (is_array($calcPRec)) {
			// If pages:
			if ($table == 'pages') {
				$CALC_PERMS = $GLOBALS['BE_USER']->calcPerms($calcPRec);
				$hasAccess = $CALC_PERMS & 2 ? TRUE : FALSE;
			} else {
				// Fetching pid-record first.
				$CALC_PERMS = $GLOBALS['BE_USER']->calcPerms(BackendUtility::getRecord('pages', $calcPRec['pid']));
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
