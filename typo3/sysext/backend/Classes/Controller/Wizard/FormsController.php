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
 * API comments:
 *
 * The form wizard can help you to create forms - it allows you to create almost any kind of HTML form elements and in any order and amount.
 *
 * The format for the resulting configuration code can be either a line-based configuration. That can look like this:
 *
 * Your name: | *name=input | (input your name here!)
 * Your Email: | *email=input
 * Your address: | address=textarea,40,10
 * Your Haircolor: | hair=radio |
 * upload | attachment=file
 * | quoted_printable=hidden | 0
 * | formtype_mail=submit | Send form
 * | html_enabled=hidden
 * | subject=hidden | This is the subject
 *
 *
 * Alternatively it can be XML. The same configuration from above looks like this in XML:
 *
 * <T3FormWizard>
 * <n2>
 * <type>input</type>
 * <label>Your name:</label>
 * <required>1</required>
 * <fieldname>name</fieldname>
 * <size></size>
 * <max></max>
 * <default>(input your name here!)</default>
 * </n2>
 * <n4>
 * <type>input</type>
 * <label>Your Email:</label>
 * <required>1</required>
 * <fieldname>email</fieldname>
 * <size></size>
 * <max></max>
 * <default></default>
 * </n4>
 * <n6>
 * <type>textarea</type>
 * <label>Your address:</label>
 * <fieldname>address</fieldname>
 * <cols>40</cols>
 * <rows>10</rows>
 * <default></default>
 * </n6>
 * <n8>
 * <type>radio</type>
 * <label>Your Haircolor:</label>
 * <fieldname>hair</fieldname>
 * <options></options>
 * </n8>
 * <n10>
 * <type>file</type>
 * <label>upload</label>
 * <fieldname>attachment</fieldname>
 * <size></size>
 * </n10>
 * <n12>
 * <type>hidden</type>
 * <label></label>
 * <fieldname>quoted_printable</fieldname>
 * <default>0</default>
 * </n12>
 * <n2000>
 * <fieldname>formtype_mail</fieldname>
 * <type>submit</type>
 * <default>Send form</default>
 * </n2000>
 * <n2002>
 * <fieldname>html_enabled</fieldname>
 * <type>hidden</type>
 * </n2002>
 * <n2004>
 * <fieldname>subject</fieldname>
 * <type>hidden</type>
 * <default>This is the subject</default>
 * </n2004>
 * <n20>
 * <content></content>
 * </n20>
 * </T3FormWizard>
 *
 *
 * The XML/phpArray structure is the internal format of the wizard.
 */
/**
 * Script Class for rendering the Form Wizard
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class FormsController {

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

	// List of files to include.
	/**
	 * @todo Define visibility
	 */
	public $include_once = array();

	// Used to numerate attachments automatically.
	/**
	 * @todo Define visibility
	 */
	public $attachmentCounter = 0;

	// Internal, static:
	// If set, the string version of the content is interpreted/written as XML instead of
	// the original linebased kind. This variable still needs binding to the wizard parameters
	// - but support is ready!
	/**
	 * @todo Define visibility
	 */
	public $xmlStorage = 0;

	// Internal, static: GPvars
	// Wizard parameters, coming from TCEforms linking to the wizard.
	/**
	 * @todo Define visibility
	 */
	public $P;

	// The array which is constantly submitted by the multidimensional form of this wizard.
	/**
	 * @todo Define visibility
	 */
	public $FORMCFG;

	// Indicates if the form is of a dedicated type, like "formtype_mail" (for tt_content element "Form")
	/**
	 * @todo Define visibility
	 */
	public $special;

	/**
	 * Initialization the class
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function init() {
		// GPvars:
		$this->P = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('P');
		$this->special = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('special');
		$this->FORMCFG = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('FORMCFG');
		// Setting options:
		$this->xmlStorage = $this->P['params']['xmlOutput'];
		// Document template object:
		$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate('templates/wizard_forms.html');
		$this->doc->JScode = $this->doc->wrapScriptTags('
			function jumpToUrl(URL,formEl) {	//
				window.location.href = URL;
			}
		');
		// Setting form tag:
		list($rUri) = explode('#', \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI'));
		$this->doc->form = '<form action="' . htmlspecialchars($rUri) . '" method="post" name="wizardForm">';
	}

	/**
	 * Main function for rendering the form wizard HTML
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function main() {
		if ($this->P['table'] && $this->P['field'] && $this->P['uid']) {
			$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('forms_title'), $this->formsWizard(), 0, 1);
		} else {
			$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('forms_title'), '<span class="typo3-red">' . $GLOBALS['LANG']->getLL('table_noData', 1) . '</span>', 0, 1);
		}
		// Setting up the buttons and markers for docheader
		$docHeaderButtons = $this->getButtons();
		$markers['CSH'] = $docHeaderButtons['csh'];
		$markers['CONTENT'] = $this->content;
		// Build the <body> for the module
		$this->content = $this->doc->startPage('Form Wizard');
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
		echo $this->content;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return array All available buttons as an assoc. array
	 */
	protected function getButtons() {
		$buttons = array(
			'csh' => '',
			'csh_buttons' => '',
			'close' => '',
			'save' => '',
			'save_close' => '',
			'reload' => ''
		);
		if ($this->P['table'] && $this->P['field'] && $this->P['uid']) {
			// CSH
			$buttons['csh'] = \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('xMOD_csh_corebe', 'wizard_forms_wiz', $GLOBALS['BACK_PATH'], '');
			// CSH Buttons
			$buttons['csh_buttons'] = \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('xMOD_csh_corebe', 'wizard_forms_wiz_buttons', $GLOBALS['BACK_PATH'], '');
			// Close
			$buttons['close'] = '<a href="#" onclick="' . htmlspecialchars(('jumpToUrl(unescape(\'' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::sanitizeLocalUrl($this->P['returnUrl'])) . '\')); return false;')) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-close', array('title' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:rm.closeDoc', TRUE))) . '</a>';
			// Save
			$buttons['save'] = '<input type="image" class="c-inputButton" name="savedok"' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->doc->backPath, 'gfx/savedok.gif') . ' title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveDoc', 1) . '" />';
			// Save & Close
			$buttons['save_close'] = '<input type="image" class="c-inputButton" name="saveandclosedok"' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->doc->backPath, 'gfx/saveandclosedok.gif') . ' title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveCloseDoc', 1) . '" />';
			// Reload
			$buttons['reload'] = '<input type="image" class="c-inputButton" name="_refresh"' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg('', 'gfx/refresh_n.gif') . ' title="' . $GLOBALS['LANG']->getLL('forms_refresh', 1) . '" />';
		}
		return $buttons;
	}

	/**
	 * Draws the form wizard content
	 *
	 * @return string HTML content for the form.
	 * @todo Define visibility
	 */
	public function formsWizard() {
		// First, check the references by selecting the record:
		$row = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($this->P['table'], $this->P['uid']);
		if (!is_array($row)) {
			throw new \RuntimeException('Wizard Error: No reference to record', 1294587124);
		}
		// This will get the content of the form configuration code field to us - possibly
		// cleaned up, saved to database etc. if the form has been submitted in the meantime.
		$formCfgArray = $this->getConfigCode($row);
		// Generation of the Form Wizards HTML code:
		$content = $this->getFormHTML($formCfgArray, $row);
		// Return content:
		return $content;
	}

	/****************************
	 *
	 * Helper functions
	 *
	 ***************************/
	/**
	 * Will get and return the configuration code string
	 * Will also save (and possibly redirect/exit) the content if a save button has been pressed
	 *
	 * @param array $row Current parent record row (passed by value!)
	 * @return array Configuration Array
	 * @access private
	 * @todo Define visibility
	 */
	public function getConfigCode(&$row) {
		// If some data has been submitted, then construct
		if (isset($this->FORMCFG['c'])) {
			// Process incoming:
			$this->changeFunc();
			// Convert to string (either line based or XML):
			if ($this->xmlStorage) {
				// Convert the input array to XML:
				$bodyText = \TYPO3\CMS\Core\Utility\GeneralUtility::array2xml_cs($this->FORMCFG['c'], 'T3FormWizard');
				// Setting cfgArr directly from the input:
				$cfgArr = $this->FORMCFG['c'];
			} else {
				// Convert the input array to a string of configuration code:
				$bodyText = $this->cfgArray2CfgString($this->FORMCFG['c']);
				// Create cfgArr from the string based configuration - that way it is cleaned
				// up and any incompatibilities will be removed!
				$cfgArr = $this->cfgString2CfgArray($bodyText);
			}
			// If a save button has been pressed, then save the new field content:
			if ($_POST['savedok_x'] || $_POST['saveandclosedok_x']) {
				// Make TCEmain object:
				$tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
				$tce->stripslashes_values = 0;
				// Put content into the data array:
				$data = array();
				$data[$this->P['table']][$this->P['uid']][$this->P['field']] = $bodyText;
				if ($this->special == 'formtype_mail') {
					$data[$this->P['table']][$this->P['uid']]['subheader'] = $this->FORMCFG['recipient'];
				}
				// Perform the update:
				$tce->start($data, array());
				$tce->process_datamap();
				// Re-load the record content:
				$row = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($this->P['table'], $this->P['uid']);
				// If the save/close button was pressed, then redirect the screen:
				if ($_POST['saveandclosedok_x']) {
					\TYPO3\CMS\Core\Utility\HttpUtility::redirect(\TYPO3\CMS\Core\Utility\GeneralUtility::sanitizeLocalUrl($this->P['returnUrl']));
				}
			}
		} else {
			// If nothing has been submitted, load the $bodyText variable from the selected database row:
			if ($this->xmlStorage) {
				$cfgArr = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($row[$this->P['field']]);
			} else {
				// Regular linebased form configuration:
				$cfgArr = $this->cfgString2CfgArray($row[$this->P['field']]);
			}
			$cfgArr = is_array($cfgArr) ? $cfgArr : array();
		}
		// Return configuration code:
		return $cfgArr;
	}

	/**
	 * Creates the HTML for the Form Wizard:
	 *
	 * @param string $formCfgArray Form config array
	 * @param array $row Current parent record array
	 * @return string HTML for the form wizard
	 * @access private
	 * @todo Define visibility
	 */
	public function getFormHTML($formCfgArray, $row) {
		// Initialize variables:
		$specParts = array();
		$hiddenFields = array();
		$tRows = array();
		// Set header row:
		$cells = array(
			$GLOBALS['LANG']->getLL('forms_preview', 1) . ':',
			$GLOBALS['LANG']->getLL('forms_element', 1) . ':',
			$GLOBALS['LANG']->getLL('forms_config', 1) . ':'
		);
		$tRows[] = '
			<tr class="bgColor2" id="typo3-formWizardHeader">
				<td>&nbsp;</td>
				<td>' . implode('</td>
				<td>', $cells) . '</td>
			</tr>';
		// Traverse the number of form elements:
		$k = 0;
		foreach ($formCfgArray as $confData) {
			// Initialize:
			$cells = array();
			// If there is a configuration line which is active, then render it:
			if (!isset($confData['comment'])) {
				// Special parts:
				if ($this->special == 'formtype_mail' && \TYPO3\CMS\Core\Utility\GeneralUtility::inList('formtype_mail,subject,html_enabled', $confData['fieldname'])) {
					$specParts[$confData['fieldname']] = $confData['default'];
				} else {
					// Render title/field preview COLUMN
					$cells[] = $confData['type'] != 'hidden' ? '<strong>' . htmlspecialchars($confData['label']) . '</strong>' : '';
					// Render general type/title COLUMN:
					$temp_cells = array();
					// Field type selector:
					$opt = array();
					$opt[] = '<option value=""></option>';
					$types = explode(',', 'input,textarea,select,check,radio,password,file,hidden,submit,property,label');
					foreach ($types as $t) {
						$opt[] = '
								<option value="' . $t . '"' . ($confData['type'] == $t ? ' selected="selected"' : '') . '>' . $GLOBALS['LANG']->getLL(('forms_type_' . $t), 1) . '</option>';
					}
					$temp_cells[$GLOBALS['LANG']->getLL('forms_type')] = '
							<select name="FORMCFG[c][' . ($k + 1) * 2 . '][type]">
								' . implode('
								', $opt) . '
							</select>';
					// Title field:
					if (!\TYPO3\CMS\Core\Utility\GeneralUtility::inList('hidden,submit', $confData['type'])) {
						$temp_cells[$GLOBALS['LANG']->getLL('forms_label')] = '<input type="text"' . $this->doc->formWidth(15) . ' name="FORMCFG[c][' . ($k + 1) * 2 . '][label]" value="' . htmlspecialchars($confData['label']) . '" />';
					}
					// Required checkbox:
					if (!\TYPO3\CMS\Core\Utility\GeneralUtility::inList('check,hidden,submit,label', $confData['type'])) {
						$temp_cells[$GLOBALS['LANG']->getLL('forms_required')] = '<input type="checkbox" name="FORMCFG[c][' . ($k + 1) * 2 . '][required]" value="1"' . ($confData['required'] ? ' checked="checked"' : '') . ' title="' . $GLOBALS['LANG']->getLL('forms_required', 1) . '" />';
					}
					// Put sub-items together into table cell:
					$cells[] = $this->formatCells($temp_cells);
					// Render specific field configuration COLUMN:
					$temp_cells = array();
					// Fieldname
					if ($this->special == 'formtype_mail' && $confData['type'] == 'file') {
						$confData['fieldname'] = 'attachment' . ++$this->attachmentCounter;
					}
					if (!\TYPO3\CMS\Core\Utility\GeneralUtility::inList('label', $confData['type'])) {
						$temp_cells[$GLOBALS['LANG']->getLL('forms_fieldName')] = '<input type="text"' . $this->doc->formWidth(10) . ' name="FORMCFG[c][' . ($k + 1) * 2 . '][fieldname]" value="' . htmlspecialchars($confData['fieldname']) . '" title="' . $GLOBALS['LANG']->getLL('forms_fieldName', 1) . '" />';
					}
					// Field configuration depending on the fields type:
					switch ((string) $confData['type']) {
					case 'textarea':
						$temp_cells[$GLOBALS['LANG']->getLL('forms_cols')] = '<input type="text"' . $this->doc->formWidth(5) . ' name="FORMCFG[c][' . ($k + 1) * 2 . '][cols]" value="' . htmlspecialchars($confData['cols']) . '" title="' . $GLOBALS['LANG']->getLL('forms_cols', 1) . '" />';
						$temp_cells[$GLOBALS['LANG']->getLL('forms_rows')] = '<input type="text"' . $this->doc->formWidth(5) . ' name="FORMCFG[c][' . ($k + 1) * 2 . '][rows]" value="' . htmlspecialchars($confData['rows']) . '" title="' . $GLOBALS['LANG']->getLL('forms_rows', 1) . '" />';
						$temp_cells[$GLOBALS['LANG']->getLL('forms_extra')] = '<input type="checkbox" name="FORMCFG[c][' . ($k + 1) * 2 . '][extra]" value="OFF"' . ($confData['extra'] == 'OFF' ? ' checked="checked"' : '') . ' title="' . $GLOBALS['LANG']->getLL('forms_extra', 1) . '" />';
						break;
					case 'input':

					case 'password':
						$temp_cells[$GLOBALS['LANG']->getLL('forms_size')] = '<input type="text"' . $this->doc->formWidth(5) . ' name="FORMCFG[c][' . ($k + 1) * 2 . '][size]" value="' . htmlspecialchars($confData['size']) . '" title="' . $GLOBALS['LANG']->getLL('forms_size', 1) . '" />';
						$temp_cells[$GLOBALS['LANG']->getLL('forms_max')] = '<input type="text"' . $this->doc->formWidth(5) . ' name="FORMCFG[c][' . ($k + 1) * 2 . '][max]" value="' . htmlspecialchars($confData['max']) . '" title="' . $GLOBALS['LANG']->getLL('forms_max', 1) . '" />';
						break;
					case 'file':
						$temp_cells[$GLOBALS['LANG']->getLL('forms_size')] = '<input type="text"' . $this->doc->formWidth(5) . ' name="FORMCFG[c][' . ($k + 1) * 2 . '][size]" value="' . htmlspecialchars($confData['size']) . '" title="' . $GLOBALS['LANG']->getLL('forms_size', 1) . '" />';
						break;
					case 'select':
						$temp_cells[$GLOBALS['LANG']->getLL('forms_size')] = '<input type="text"' . $this->doc->formWidth(5) . ' name="FORMCFG[c][' . ($k + 1) * 2 . '][size]" value="' . htmlspecialchars($confData['size']) . '" title="' . $GLOBALS['LANG']->getLL('forms_size', 1) . '" />';
						$temp_cells[$GLOBALS['LANG']->getLL('forms_autosize')] = '<input type="checkbox" name="FORMCFG[c][' . ($k + 1) * 2 . '][autosize]" value="1"' . ($confData['autosize'] ? ' checked="checked"' : '') . ' title="' . $GLOBALS['LANG']->getLL('forms_autosize', 1) . '" />';
						$temp_cells[$GLOBALS['LANG']->getLL('forms_multiple')] = '<input type="checkbox" name="FORMCFG[c][' . ($k + 1) * 2 . '][multiple]" value="1"' . ($confData['multiple'] ? ' checked="checked"' : '') . ' title="' . $GLOBALS['LANG']->getLL('forms_multiple', 1) . '" />';
						break;
					}
					// Field configuration depending on the fields type:
					switch ((string) $confData['type']) {
					case 'textarea':

					case 'input':

					case 'password':
						if (strlen(trim($confData['specialEval']))) {
							$hiddenFields[] = '<input type="hidden" name="FORMCFG[c][' . ($k + 1) * 2 . '][specialEval]" value="' . htmlspecialchars($confData['specialEval']) . '" />';
						}
						break;
					}
					// Default data
					if ($confData['type'] == 'select' || $confData['type'] == 'radio') {
						$temp_cells[$GLOBALS['LANG']->getLL('forms_options')] = '<textarea ' . $this->doc->formWidthText(15) . ' rows="4" name="FORMCFG[c][' . ($k + 1) * 2 . '][options]" title="' . $GLOBALS['LANG']->getLL('forms_options', 1) . '">' . \TYPO3\CMS\Core\Utility\GeneralUtility::formatForTextarea($confData['default']) . '</textarea>';
					} elseif ($confData['type'] == 'check') {
						$temp_cells[$GLOBALS['LANG']->getLL('forms_checked')] = '<input type="checkbox" name="FORMCFG[c][' . ($k + 1) * 2 . '][default]" value="1"' . (trim($confData['default']) ? ' checked="checked"' : '') . ' title="' . $GLOBALS['LANG']->getLL('forms_checked', 1) . '" />';
					} elseif ($confData['type'] && $confData['type'] != 'file') {
						$temp_cells[$GLOBALS['LANG']->getLL('forms_default')] = '<input type="text"' . $this->doc->formWidth(15) . ' name="FORMCFG[c][' . ($k + 1) * 2 . '][default]" value="' . htmlspecialchars($confData['default']) . '" title="' . $GLOBALS['LANG']->getLL('forms_default', 1) . '" />';
					}
					$cells[] = $confData['type'] ? $this->formatCells($temp_cells) : '';
					// CTRL panel for an item (move up/down/around):
					$ctrl = '';
					$onClick = 'document.wizardForm.action+=\'#ANC_' . (($k + 1) * 2 - 2) . '\';';
					$onClick = ' onclick="' . htmlspecialchars($onClick) . '"';
					// FIXME $inputStyle undefined
					$brTag = $inputStyle ? '' : '<br />';
					if ($k != 0) {
						$ctrl .= '<input type="image" name="FORMCFG[row_up][' . ($k + 1) * 2 . ']"' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->doc->backPath, 'gfx/pil2up.gif', '') . $onClick . ' title="' . $GLOBALS['LANG']->getLL('table_up', 1) . '" />' . $brTag;
					} else {
						$ctrl .= '<input type="image" name="FORMCFG[row_bottom][' . ($k + 1) * 2 . ']"' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->doc->backPath, 'gfx/turn_up.gif', '') . $onClick . ' title="' . $GLOBALS['LANG']->getLL('table_bottom', 1) . '" />' . $brTag;
					}
					$ctrl .= '<input type="image" name="FORMCFG[row_remove][' . ($k + 1) * 2 . ']"' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->doc->backPath, 'gfx/garbage.gif', '') . $onClick . ' title="' . $GLOBALS['LANG']->getLL('table_removeRow', 1) . '" />' . $brTag;
					// FIXME $tLines undefined
					if ($k + 1 != count($tLines)) {
						$ctrl .= '<input type="image" name="FORMCFG[row_down][' . ($k + 1) * 2 . ']"' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->doc->backPath, 'gfx/pil2down.gif', '') . $onClick . ' title="' . $GLOBALS['LANG']->getLL('table_down', 1) . '" />' . $brTag;
					} else {
						$ctrl .= '<input type="image" name="FORMCFG[row_top][' . ($k + 1) * 2 . ']"' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->doc->backPath, 'gfx/turn_down.gif', '') . $onClick . ' title="' . $GLOBALS['LANG']->getLL('table_top', 1) . '" />' . $brTag;
					}
					$ctrl .= '<input type="image" name="FORMCFG[row_add][' . ($k + 1) * 2 . ']"' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->doc->backPath, 'gfx/add.gif', '') . $onClick . ' title="' . $GLOBALS['LANG']->getLL('table_addRow', 1) . '" />' . $brTag;
					$ctrl = '<span class="c-wizButtonsV">' . $ctrl . '</span>';
					// Finally, put together the full row from the generated content above:
					$bgC = $confData['type'] ? ' class="bgColor5"' : '';
					$tRows[] = '
						<tr' . $bgC . '>
							<td><a name="ANC_' . ($k + 1) * 2 . '"></a>' . $ctrl . '</td>
							<td class="bgColor4">' . implode('</td>
							<td valign="top">', $cells) . '</td>
						</tr>';
				}
			} else {
				$hiddenFields[] = '<input type="hidden" name="FORMCFG[c][' . ($k + 1) * 2 . '][comment]" value="' . htmlspecialchars($confData['comment']) . '" />';
			}
			// Increment counter:
			$k++;
		}
		// If the form is of the special type "formtype_mail" (used for tt_content elements):
		if ($this->special == 'formtype_mail') {
			// Blank spacer:
			$tRows[] = '
				<tr>
					<td colspan="4">&nbsp;</td>
				</tr>';
			// Header:
			$tRows[] = '
				<tr>
					<td colspan="2" class="bgColor2">&nbsp;</td>
					<td colspan="2" class="bgColor2"><strong>' . $GLOBALS['LANG']->getLL('forms_special_eform', 1) . ':</strong>' . \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('xMOD_csh_corebe', 'wizard_forms_wiz_formmail_info', $GLOBALS['BACK_PATH'], '') . '</td>
				</tr>';
			// "FORM type":
			$tRows[] = '
				<tr class="bgColor5">
					<td>&nbsp;</td>
					<td class="bgColor4">&nbsp;</td>
					<td>' . $GLOBALS['LANG']->getLL('forms_eform_formtype_mail', 1) . ':</td>
					<td>
						<input type="hidden" name="FORMCFG[c][' . 1000 * 2 . '][fieldname]" value="formtype_mail" />
						<input type="hidden" name="FORMCFG[c][' . 1000 * 2 . '][type]" value="submit" />
						<input type="text"' . $this->doc->formWidth(15) . ' name="FORMCFG[c][' . 1000 * 2 . '][default]" value="' . htmlspecialchars($specParts['formtype_mail']) . '" />
					</td>
				</tr>';
			// "Send HTML mail":
			$tRows[] = '
				<tr class="bgColor5">
					<td>&nbsp;</td>
					<td class="bgColor4">&nbsp;</td>
					<td>' . $GLOBALS['LANG']->getLL('forms_eform_html_enabled', 1) . ':</td>
					<td>
						<input type="hidden" name="FORMCFG[c][' . 1001 * 2 . '][fieldname]" value="html_enabled" />
						<input type="hidden" name="FORMCFG[c][' . 1001 * 2 . '][type]" value="hidden" />
						<input type="checkbox" name="FORMCFG[c][' . 1001 * 2 . '][default]" value="1"' . ($specParts['html_enabled'] ? ' checked="checked"' : '') . ' />
					</td>
				</tr>';
			// "Subject":
			$tRows[] = '
				<tr class="bgColor5">
					<td>&nbsp;</td>
					<td class="bgColor4">&nbsp;</td>
					<td>' . $GLOBALS['LANG']->getLL('forms_eform_subject', 1) . ':</td>
					<td>
						<input type="hidden" name="FORMCFG[c][' . 1002 * 2 . '][fieldname]" value="subject" />
						<input type="hidden" name="FORMCFG[c][' . 1002 * 2 . '][type]" value="hidden" />
						<input type="text"' . $this->doc->formWidth(15) . ' name="FORMCFG[c][' . 1002 * 2 . '][default]" value="' . htmlspecialchars($specParts['subject']) . '" />
					</td>
				</tr>';
			// Recipient:
			$tRows[] = '
				<tr class="bgColor5">
					<td>&nbsp;</td>
					<td class="bgColor4">&nbsp;</td>
					<td>' . $GLOBALS['LANG']->getLL('forms_eform_recipient', 1) . ':</td>
					<td>
						<input type="text"' . $this->doc->formWidth(15) . ' name="FORMCFG[recipient]" value="' . htmlspecialchars($row['subheader']) . '" />
					</td>
				</tr>';
		}
		$content = '';
		// Implode all table rows into a string, wrapped in table tags.
		$content .= '

			<!--
				Form wizard
			-->
			<table border="0" cellpadding="1" cellspacing="1" id="typo3-formwizard">
				' . implode('', $tRows) . '
			</table>';
		// Add hidden fields:
		$content .= implode('', $hiddenFields);
		// Return content:
		return $content;
	}

	/**
	 * Detects if a control button (up/down/around/delete) has been pressed for an item and accordingly it will manipulate the internal FORMCFG array
	 *
	 * @return void
	 * @access private
	 * @todo Define visibility
	 */
	public function changeFunc() {
		if ($this->FORMCFG['row_remove']) {
			$kk = key($this->FORMCFG['row_remove']);
			$cmd = 'row_remove';
		} elseif ($this->FORMCFG['row_add']) {
			$kk = key($this->FORMCFG['row_add']);
			$cmd = 'row_add';
		} elseif ($this->FORMCFG['row_top']) {
			$kk = key($this->FORMCFG['row_top']);
			$cmd = 'row_top';
		} elseif ($this->FORMCFG['row_bottom']) {
			$kk = key($this->FORMCFG['row_bottom']);
			$cmd = 'row_bottom';
		} elseif ($this->FORMCFG['row_up']) {
			$kk = key($this->FORMCFG['row_up']);
			$cmd = 'row_up';
		} elseif ($this->FORMCFG['row_down']) {
			$kk = key($this->FORMCFG['row_down']);
			$cmd = 'row_down';
		}
		if ($cmd && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($kk)) {
			if (substr($cmd, 0, 4) == 'row_') {
				switch ($cmd) {
				case 'row_remove':
					unset($this->FORMCFG['c'][$kk]);
					break;
				case 'row_add':
					$this->FORMCFG['c'][$kk + 1] = array();
					break;
				case 'row_top':
					$this->FORMCFG['c'][1] = $this->FORMCFG['c'][$kk];
					unset($this->FORMCFG['c'][$kk]);
					break;
				case 'row_bottom':
					$this->FORMCFG['c'][1000000] = $this->FORMCFG['c'][$kk];
					unset($this->FORMCFG['c'][$kk]);
					break;
				case 'row_up':
					$this->FORMCFG['c'][$kk - 3] = $this->FORMCFG['c'][$kk];
					unset($this->FORMCFG['c'][$kk]);
					break;
				case 'row_down':
					$this->FORMCFG['c'][$kk + 3] = $this->FORMCFG['c'][$kk];
					unset($this->FORMCFG['c'][$kk]);
					break;
				}
				ksort($this->FORMCFG['c']);
			}
		}
	}

	/**
	 * Converts the input array to a configuration code string
	 *
	 * @param array $cfgArr Array of form configuration (follows the input structure from the form wizard POST form)
	 * @return string The array converted into a string with line-based configuration.
	 * @see cfgString2CfgArray()
	 * @todo Define visibility
	 */
	public function cfgArray2CfgString($cfgArr) {
		// Initialize:
		$inLines = array();
		// Traverse the elements of the form wizard and transform the settings into configuration code.
		foreach ($cfgArr as $vv) {
			// If "content" is found, then just pass it over.
			if ($vv['comment']) {
				$inLines[] = trim($vv['comment']);
			} else {
				// Begin to put together the single-line configuration code of this field:
				// Reset:
				$thisLine = array();
				// Set Label:
				$thisLine[0] = str_replace('|', '', $vv['label']);
				// Set Type:
				if ($vv['type']) {
					$thisLine[1] = ($vv['required'] ? '*' : '') . str_replace(',', '', (($vv['fieldname'] ? $vv['fieldname'] . '=' : '') . $vv['type']));
					// Default:
					$tArr = array('', '', '', '', '', '');
					switch ((string) $vv['type']) {
					case 'textarea':
						if (intval($vv['cols'])) {
							$tArr[0] = intval($vv['cols']);
						}
						if (intval($vv['rows'])) {
							$tArr[1] = intval($vv['rows']);
						}
						if (trim($vv['extra'])) {
							$tArr[2] = trim($vv['extra']);
						}
						if (strlen($vv['specialEval'])) {
							// Preset blank default value so position 3 can get a value...
							$thisLine[2] = '';
							$thisLine[3] = $vv['specialEval'];
						}
						break;
					case 'input':

					case 'password':
						if (intval($vv['size'])) {
							$tArr[0] = intval($vv['size']);
						}
						if (intval($vv['max'])) {
							$tArr[1] = intval($vv['max']);
						}
						if (strlen($vv['specialEval'])) {
							// Preset blank default value so position 3 can get a value...
							$thisLine[2] = '';
							$thisLine[3] = $vv['specialEval'];
						}
						break;
					case 'file':
						if (intval($vv['size'])) {
							$tArr[0] = intval($vv['size']);
						}
						break;
					case 'select':
						if (intval($vv['size'])) {
							$tArr[0] = intval($vv['size']);
						}
						if ($vv['autosize']) {
							$tArr[0] = 'auto';
						}
						if ($vv['multiple']) {
							$tArr[1] = 'm';
						}
						break;
					}
					$tArr = $this->cleanT($tArr);
					if (count($tArr)) {
						$thisLine[1] .= ',' . implode(',', $tArr);
					}
					$thisLine[1] = str_replace('|', '', $thisLine[1]);
					// Default:
					if ($vv['type'] == 'select' || $vv['type'] == 'radio') {
						$thisLine[2] = str_replace(LF, ', ', str_replace(',', '', $vv['options']));
					} elseif ($vv['type'] == 'check') {
						if ($vv['default']) {
							$thisLine[2] = 1;
						}
					} elseif (strcmp(trim($vv['default']), '')) {
						$thisLine[2] = $vv['default'];
					}
					if (isset($thisLine[2])) {
						$thisLine[2] = str_replace('|', '', $thisLine[2]);
					}
				}
				// Compile the final line:
				$inLines[] = preg_replace('/[

]*/', '', implode(' | ', $thisLine));
			}
		}
		// Finally, implode the lines into a string, and return it:
		return implode(LF, $inLines);
	}

	/**
	 * Converts the input configuration code string into an array
	 *
	 * @param string $cfgStr Configuration code
	 * @return array Configuration array
	 * @see cfgArray2CfgString()
	 * @todo Define visibility
	 */
	public function cfgString2CfgArray($cfgStr) {
		// Traverse the number of form elements:
		$tLines = explode(LF, $cfgStr);
		foreach ($tLines as $k => $v) {
			// Initialize:
			$confData = array();
			$val = trim($v);
			// Accept a line as configuration if a) it is blank(! - because blank lines indicates new,
			// unconfigured fields) or b) it is NOT a comment.
			if (!$val || strcspn($val, '#/')) {
				// Split:
				$parts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('|', $val);
				// Label:
				$confData['label'] = trim($parts[0]);
				// Field:
				$fParts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $parts[1]);
				$fParts[0] = trim($fParts[0]);
				if (substr($fParts[0], 0, 1) == '*') {
					$confData['required'] = 1;
					$fParts[0] = substr($fParts[0], 1);
				}
				$typeParts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('=', $fParts[0]);
				$confData['type'] = trim(strtolower(end($typeParts)));
				if ($confData['type']) {
					if (count($typeParts) == 1) {
						$confData['fieldname'] = substr(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', trim($parts[0]))), 0, 30);
						// Attachment names...
						if ($confData['type'] == 'file') {
							$confData['fieldname'] = 'attachment' . $attachmentCounter;
							$attachmentCounter = intval($attachmentCounter) + 1;
						}
					} else {
						$confData['fieldname'] = str_replace(' ', '_', trim($typeParts[0]));
					}
					switch ((string) $confData['type']) {
					case 'select':

					case 'radio':
						$confData['default'] = implode(LF, \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $parts[2]));
						break;
					default:
						$confData['default'] = trim($parts[2]);
						break;
					}
					// Field configuration depending on the fields type:
					switch ((string) $confData['type']) {
					case 'textarea':
						$confData['cols'] = $fParts[1];
						$confData['rows'] = $fParts[2];
						$confData['extra'] = strtoupper($fParts[3]) == 'OFF' ? 'OFF' : '';
						$confData['specialEval'] = trim($parts[3]);
						break;
					case 'input':

					case 'password':
						$confData['size'] = $fParts[1];
						$confData['max'] = $fParts[2];
						$confData['specialEval'] = trim($parts[3]);
						break;
					case 'file':
						$confData['size'] = $fParts[1];
						break;
					case 'select':
						$confData['size'] = intval($fParts[1]) ? $fParts[1] : '';
						$confData['autosize'] = strtolower(trim($fParts[1])) == 'auto' ? 1 : 0;
						$confData['multiple'] = strtolower(trim($fParts[2])) == 'm' ? 1 : 0;
						break;
					}
				}
			} else {
				// No configuration, only a comment:
				$confData = array(
					'comment' => $val
				);
			}
			// Adding config array:
			$cfgArr[] = $confData;
		}
		// Return cfgArr
		return $cfgArr;
	}

	/**
	 * Removes any "trailing elements" in the array which consists of whitespace (little like trim() does for strings, so this does for arrays)
	 *
	 * @param array $tArr Single dim array
	 * @return array Processed array
	 * @access private
	 * @todo Define visibility
	 */
	public function cleanT($tArr) {
		for ($a = count($tArr); $a > 0; $a--) {
			if (strcmp($tArr[$a - 1], '')) {
				break;
			} else {
				unset($tArr[$a - 1]);
			}
		}
		return $tArr;
	}

	/**
	 * Wraps items in $fArr in table cells/rows, displaying them vertically.
	 *
	 * @param array $fArr Array of label/HTML pairs.
	 * @return string HTML table
	 * @access private
	 * @todo Define visibility
	 */
	public function formatCells($fArr) {
		// Traverse the elements in $fArr and wrap them in table cells:
		$lines = array();
		foreach ($fArr as $l => $c) {
			$lines[] = '
				<tr>
					<td nowrap="nowrap">' . htmlspecialchars(($l . ':')) . '&nbsp;</td>
					<td>' . $c . '</td>
				</tr>';
		}
		// Add a cell which will set a minimum width:
		$lines[] = '
			<tr>
				<td nowrap="nowrap"><img src="clear.gif" width="70" height="1" alt="" /></td>
				<td></td>
			</tr>';
		// Wrap in table and return:
		return '
			<table border="0" cellpadding="0" cellspacing="0">
				' . implode('', $lines) . '
			</table>';
	}

}


?>