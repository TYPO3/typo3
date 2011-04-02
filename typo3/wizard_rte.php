<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Wizard to display the RTE in "full screen" mode
 *
 * Revised for TYPO3 3.6 November/2003 by Kasper Skårhøj
 * XHTML compliant
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   81: class SC_wizard_rte
 *   99:     function init()
 *  123:     function main()
 *  285:     function printContent()
 *  298:     function checkEditAccess($table,$uid)
 *
 * TOTAL FUNCTIONS: 4
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */



$BACK_PATH='';
require ('init.php');
require ('template.php');
$LANG->includeLLFile('EXT:lang/locallang_wizards.xml');

t3lib_BEfunc::lockRecords();











/**
 * Script Class for rendering the full screen RTE display
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_wizard_rte {

		// Internal, dynamic:
	/**
	 * document template object
	 *
	 * @var mediumDoc
	 */
	var $doc;
	var $content;				// Content accumulation for the module.

		// Internal, static: GPvars
	var $P;						// Wizard parameters, coming from TCEforms linking to the wizard.
	var $popView;				// If set, launch a new window with the current records pid.
	var $R_URI;					// Set to the URL of this script including variables which is needed to re-display the form. See main()




	/**
	 * Initialization of the class
	 *
	 * @return	void
	 */
	function init()	{
			// Setting GPvars:
		$this->P = t3lib_div::_GP('P');
		$this->popView = t3lib_div::_GP('popView');
		$this->R_URI = t3lib_div::linkThisScript(array('popView' => ''));

			// "Module name":
		$this->MCONF['name']='xMOD_wizard_rte.php';

			// Starting the document template object:
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate('templates/wizard_rte.html');
		$this->doc->divClass = '';	// Need to NOT have the page wrapped in DIV since if we do that we destroy the feature that the RTE spans the whole height of the page!!!
		$this->doc->form='<form action="tce_db.php" method="post" enctype="'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'].'" name="editform" onsubmit="return TBE_EDITOR.checkSubmit(1);">';
	}

	/**
	 * Main function, rendering the document with the iframe with the RTE in.
	 *
	 * @return	void
	 */
	function main()	{
		global $LANG;

			// translate id to the workspace version:
		if ($versionRec = t3lib_BEfunc::getWorkspaceVersionOfRecord($GLOBALS['BE_USER']->workspace, $this->P['table'], $this->P['uid'], 'uid'))	{
			$this->P['uid'] = $versionRec['uid'];
		}

			// If all parameters are available:
		if ($this->P['table'] && $this->P['field'] && $this->P['uid'] && $this->checkEditAccess($this->P['table'],$this->P['uid']))	{

				// Getting the raw record (we need only the pid-value from here...)
			$rawRec = t3lib_BEfunc::getRecord($this->P['table'],$this->P['uid']);
			t3lib_BEfunc::fixVersioningPid($this->P['table'], $rawRec);

				// Setting JavaScript, including the pid value for viewing:
			$this->doc->JScode = $this->doc->wrapScriptTags('
					function jumpToUrl(URL,formEl)	{	//
						if (document.editform)	{
							if (!TBE_EDITOR.isFormChanged())	{
								window.location.href = URL;
							} else if (formEl) {
								if (formEl.type=="checkbox") formEl.checked = formEl.checked ? 0 : 1;
							}
						} else window.location.href = URL;
					}
				'.($this->popView ? t3lib_BEfunc::viewOnClick($rawRec['pid'],'',t3lib_BEfunc::BEgetRootLine($rawRec['pid'])) : '').'
			');

				// Initialize TCeforms - for rendering the field:
			$tceforms = t3lib_div::makeInstance('t3lib_TCEforms');
			$tceforms->initDefaultBEMode();	// Init...
			$tceforms->disableWizards = 1;	// SPECIAL: Disables all wizards - we are NOT going to need them.
			$tceforms->colorScheme[0]=$this->doc->bgColor;	// SPECIAL: Setting background color of the RTE to ordinary background

				// Initialize style for RTE object:
			$RTEobj = t3lib_BEfunc::RTEgetObj();	// Getting reference to the RTE object used to render the field!
			if ($RTEobj->ID == 'rte')	{
				$RTEobj->RTEdivStyle = 'position:relative; left:0px; top:0px; height:100%; width:100%; border:solid 0px;';	// SPECIAL: Setting style for the RTE <DIV> layer containing the IFRAME
			}

				// Fetching content of record:
			$trData = t3lib_div::makeInstance('t3lib_transferData');
			$trData->lockRecords=1;
			$trData->fetchRecord($this->P['table'],$this->P['uid'],'');

				// Getting the processed record content out:
			reset($trData->regTableItems_data);
			$rec = current($trData->regTableItems_data);
			$rec['uid'] = $this->P['uid'];
			$rec['pid'] = $rawRec['pid'];

				// TSconfig, setting width:
			$fieldTSConfig = $tceforms->setTSconfig($this->P['table'],$rec,$this->P['field']);
			if (strcmp($fieldTSConfig['RTEfullScreenWidth'],''))	{
				$width=$fieldTSConfig['RTEfullScreenWidth'];
			} else {
				$width='100%';
			}

				// Get the form field and wrap it in the table with the buttons:
			$formContent = $tceforms->getSoloField($this->P['table'],$rec,$this->P['field']);
			$formContent = '


			<!--
				RTE wizard:
			-->
				<table border="0" cellpadding="0" cellspacing="0" width="'.$width.'" id="typo3-rtewizard">
					<tr>
						<td width="'.$width.'" colspan="2" id="c-formContent">'.$formContent.'</td>
						<td></td>
					</tr>
				</table>';

				// Adding hidden fields:
			$formContent.= '<input type="hidden" name="redirect" value="'.htmlspecialchars($this->R_URI).'" />
						<input type="hidden" name="_serialNumber" value="'.md5(microtime()).'" />' .
						t3lib_TCEforms::getHiddenTokenField('tceAction');


				// Finally, add the whole setup:
			$this->content.=
				$tceforms->printNeededJSFunctions_top().
				$formContent.
				$tceforms->printNeededJSFunctions();
		} else {
				// ERROR:
			$this->content.=$this->doc->section($LANG->getLL('forms_title'),'<span class="typo3-red">'.$LANG->getLL('table_noData',1).'</span>',0,1);
		}

		// Setting up the buttons and markers for docheader
		$docHeaderButtons = $this->getButtons();
		$markers['CONTENT'] = $this->content;

		// Build the <body> for the module
		$this->content = $this->doc->startPage('');
		$this->content.= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		$this->content.= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);

	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return	void
	 */
	function printContent()	{
		$this->content.= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
		echo $this->content;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return array all available buttons as an assoc. array
	 */
	protected function getButtons() {
		$buttons = array(
			'close' => '',
			'save' => '',
			'save_view' => '',
			'save_close' => '',
			'shortcut' => '',
			'undo' => '',
		);

		if ($this->P['table'] && $this->P['field'] && $this->P['uid'] && $this->checkEditAccess($this->P['table'],$this->P['uid'])) {
			$closeUrl = t3lib_div::sanitizeLocalUrl($this->P['returnUrl']);

			// Getting settings for the undo button:
			$undoButton = 0;
			$undoRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tstamp', 'sys_history', 'tablename=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->P['table'], 'sys_history') . ' AND recuid=' . intval($this->P['uid']), '', 'tstamp DESC', '1');
			if ($undoButtonR = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($undoRes))	{
				$undoButton = 1;
			}

			// Close
			$buttons['close'] = '<a href="#" onclick="' . htmlspecialchars('jumpToUrl(unescape(\'' . rawurlencode($closeUrl) . '\')); return false;') . '">' .
					'<img' . t3lib_iconWorks::skinImg($this->doc->backPath, 'gfx/closedok.gif') . ' class="c-inputButton" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.closeDoc', 1) . '" alt="" />' .
					'</a>';

			// Save
			$buttons['save'] = '<a href="#" onclick="TBE_EDITOR.checkAndDoSubmit(1); return false;">' .
				'<img' . t3lib_iconWorks::skinImg($this->doc->backPath, 'gfx/savedok.gif') . ' class="c-inputButton" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.saveDoc', 1) . '" alt="" />' .
				'</a>';

			// Save & View
			if (t3lib_extMgm::isLoaded('cms')) {
				$buttons['save_view'] = '<a href="#" onclick="' . htmlspecialchars('document.editform.redirect.value+=\'&popView=1\'; TBE_EDITOR.checkAndDoSubmit(1); return false;') . '">' .
					'<img' . t3lib_iconWorks::skinImg($this->doc->backPath, 'gfx/savedokshow.gif') . ' class="c-inputButton" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.saveDocShow', 1) . '" alt="" />' .
					'</a>';
			}

			// Save & Close
			$buttons['save_close'] = '<input type="image" class="c-inputButton" onclick="' . htmlspecialchars('document.editform.redirect.value=\'' . $closeUrl . '\'; TBE_EDITOR.checkAndDoSubmit(1); return false;') . '" name="_saveandclosedok"' . t3lib_iconWorks::skinImg($this->doc->backPath, 'gfx/saveandclosedok.gif', '') . ' title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.saveCloseDoc', 1) . '" />';

			// Undo/Revert:
			if ($undoButton)	{
				$buttons['undo'] = '<a href="#" onclick="' . htmlspecialchars('window.location.href=\'show_rechis.php?element=' . rawurlencode($this->P['table'] . ':' . $this->P['uid']) . '&revert=' . rawurlencode('field:' . $this->P['field']) . '&sumUp=-1&returnUrl=' . rawurlencode($this->R_URI) . '\'; return false;') . '">' .
					'<img' . t3lib_iconWorks::skinImg($this->doc->backPath, 'gfx/undo.gif') . ' class="c-inputButton" title="' . htmlspecialchars(sprintf($GLOBALS['LANG']->getLL('rte_undoLastChange'), t3lib_BEfunc::calcAge($GLOBALS['EXEC_TIME'] - $undoButtonR['tstamp'], $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.minutesHoursDaysYears')))) . '" alt="" />' .
					'</a>';
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
	 * @param	string		Table name
	 * @param	integer		Record uid
	 * @return	void
	 */
	function checkEditAccess($table,$uid)	{
		$calcPRec = t3lib_BEfunc::getRecord($table,$uid);
		t3lib_BEfunc::fixVersioningPid($table,$calcPRec);
		if (is_array($calcPRec))	{
			if ($table=='pages')	{	// If pages:
				$CALC_PERMS = $GLOBALS['BE_USER']->calcPerms($calcPRec);
				$hasAccess = $CALC_PERMS&2 ? TRUE : FALSE;
			} else {
				$CALC_PERMS = $GLOBALS['BE_USER']->calcPerms(t3lib_BEfunc::getRecord('pages',$calcPRec['pid']));	// Fetching pid-record first.
				$hasAccess = $CALC_PERMS&16 ? TRUE : FALSE;
			}

				// Check internals regarding access:
			if ($hasAccess)	{
				$hasAccess = $GLOBALS['BE_USER']->recordEditAccessInternals($table, $calcPRec);
			}
		} else $hasAccess = FALSE;

		return $hasAccess;
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/wizard_rte.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/wizard_rte.php']);
}



// Make instance:
$SOBE = t3lib_div::makeInstance('SC_wizard_rte');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

?>
