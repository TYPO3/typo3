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
 * Script Class for rendering the Table Wizard
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class TableController {

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

	// TRUE, then <input> fields are shown, not textareas.
	/**
	 * @todo Define visibility
	 */
	public $inputStyle = 0;

	// Internal, static:
	// If set, the string version of the content is interpreted/written as XML
	// instead of the original linebased kind. This variable still needs binding
	// to the wizard parameters - but support is ready!
	/**
	 * @todo Define visibility
	 */
	public $xmlStorage = 0;

	// Number of new rows to add in bottom of wizard
	/**
	 * @todo Define visibility
	 */
	public $numNewRows = 1;

	// Name of field in parent record which MAY contain the number of columns for the table
	// - here hardcoded to the value of tt_content. Should be set by TCEform parameters (from P)
	/**
	 * @todo Define visibility
	 */
	public $colsFieldName = 'cols';

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
	public $TABLECFG;

	// Table parsing
	// quoting of table cells
	/**
	 * @todo Define visibility
	 */
	public $tableParsing_quote;

	// delimiter between table cells
	/**
	 * @todo Define visibility
	 */
	public $tableParsing_delimiter;

	/**
	 * Initialization of the class
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function init() {
		// GPvars:
		$this->P = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('P');
		$this->TABLECFG = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('TABLE');
		// Setting options:
		$this->xmlStorage = $this->P['params']['xmlOutput'];
		$this->numNewRows = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->P['params']['numNewRows'], 1, 50, 5);
		// Textareas or input fields:
		$this->inputStyle = isset($this->TABLECFG['textFields']) ? $this->TABLECFG['textFields'] : 1;
		// Document template object:
		$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate('templates/wizard_table.html');
		$this->doc->JScode = $this->doc->wrapScriptTags('
			function jumpToUrl(URL,formEl) {	//
				window.location.href = URL;
			}
		');
		// Setting form tag:
		list($rUri) = explode('#', \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI'));
		$this->doc->form = '<form action="' . htmlspecialchars($rUri) . '" method="post" name="wizardForm">';
		$this->tableParsing_delimiter = '|';
		$this->tableParsing_quote = '';
	}

	/**
	 * Main function, rendering the table wizard
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function main() {
		if ($this->P['table'] && $this->P['field'] && $this->P['uid']) {
			$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('table_title'), $this->tableWizard(), 0, 1);
		} else {
			$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('table_title'), '<span class="typo3-red">' . $GLOBALS['LANG']->getLL('table_noData', 1) . '</span>', 0, 1);
		}
		// Setting up the buttons and markers for docheader
		$docHeaderButtons = $this->getButtons();
		$markers['CSH'] = $docHeaderButtons['csh'];
		$markers['CONTENT'] = $this->content;
		// Build the <body> for the module
		$this->content = $this->doc->startPage('Table');
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
			$buttons['csh'] = \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('xMOD_csh_corebe', 'wizard_table_wiz', $GLOBALS['BACK_PATH'], '');
			// CSH Buttons
			$buttons['csh_buttons'] = \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('xMOD_csh_corebe', 'wizard_table_wiz_buttons', $GLOBALS['BACK_PATH'], '');
			// Close
			$buttons['close'] = '<a href="#" onclick="' . htmlspecialchars(('jumpToUrl(unescape(\'' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::sanitizeLocalUrl($this->P['returnUrl'])) . '\')); return false;')) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-close', array('title' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:rm.closeDoc', TRUE))) . '</a>';
			// Save
			$buttons['save'] = '<input type="image" class="c-inputButton" name="savedok"' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->doc->backPath, 'gfx/savedok.gif') . ' title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveDoc', 1) . '" />';
			// Save & Close
			$buttons['save_close'] = '<input type="image" class="c-inputButton" name="saveandclosedok"' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->doc->backPath, 'gfx/saveandclosedok.gif') . ' title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveCloseDoc', 1) . '" />';
			// Reload
			$buttons['reload'] = '<input type="image" class="c-inputButton" name="_refresh"' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->doc->backPath, 'gfx/refresh_n.gif') . ' title="' . $GLOBALS['LANG']->getLL('forms_refresh', 1) . '" />';
		}
		return $buttons;
	}

	/**
	 * Draws the table wizard content
	 *
	 * @return string HTML content for the form.
	 * @todo Define visibility
	 */
	public function tableWizard() {
		// First, check the references by selecting the record:
		$row = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($this->P['table'], $this->P['uid']);
		if (!is_array($row)) {
			throw new \RuntimeException('Wizard Error: No reference to record', 1294587125);
		}
		// This will get the content of the form configuration code field to us - possibly cleaned up,
		// saved to database etc. if the form has been submitted in the meantime.
		$tableCfgArray = $this->getConfigCode($row);
		// Generation of the Table Wizards HTML code:
		$content = $this->getTableHTML($tableCfgArray, $row);
		// Return content:
		return $content;
	}

	/***************************
	 *
	 * Helper functions
	 *
	 ***************************/
	/**
	 * Will get and return the configuration code string
	 * Will also save (and possibly redirect/exit) the content if a save button has been pressed
	 *
	 * @param array $row Current parent record row
	 * @return array Table config code in an array
	 * @access private
	 * @todo Define visibility
	 */
	public function getConfigCode($row) {
		// Get delimiter settings
		$flexForm = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($row['pi_flexform']);
		if (is_array($flexForm)) {
			$this->tableParsing_quote = $flexForm['data']['s_parsing']['lDEF']['tableparsing_quote']['vDEF'] ? chr(intval($flexForm['data']['s_parsing']['lDEF']['tableparsing_quote']['vDEF'])) : '';
			$this->tableParsing_delimiter = $flexForm['data']['s_parsing']['lDEF']['tableparsing_delimiter']['vDEF'] ? chr(intval($flexForm['data']['s_parsing']['lDEF']['tableparsing_delimiter']['vDEF'])) : '|';
		}
		// If some data has been submitted, then construct
		if (isset($this->TABLECFG['c'])) {
			// Process incoming:
			$this->changeFunc();
			// Convert to string (either line based or XML):
			if ($this->xmlStorage) {
				// Convert the input array to XML:
				$bodyText = \TYPO3\CMS\Core\Utility\GeneralUtility::array2xml_cs($this->TABLECFG['c'], 'T3TableWizard');
				// Setting cfgArr directly from the input:
				$cfgArr = $this->TABLECFG['c'];
			} else {
				// Convert the input array to a string of configuration code:
				$bodyText = $this->cfgArray2CfgString($this->TABLECFG['c']);
				// Create cfgArr from the string based configuration - that way it is cleaned up and any incompatibilities will be removed!
				$cfgArr = $this->cfgString2CfgArray($bodyText, $row[$this->colsFieldName]);
			}
			// If a save button has been pressed, then save the new field content:
			if ($_POST['savedok_x'] || $_POST['saveandclosedok_x']) {
				// Make TCEmain object:
				$tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
				$tce->stripslashes_values = 0;
				// Put content into the data array:
				$data = array();
				$data[$this->P['table']][$this->P['uid']][$this->P['field']] = $bodyText;
				// Perform the update:
				$tce->start($data, array());
				$tce->process_datamap();
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
				// Regular linebased table configuration:
				$cfgArr = $this->cfgString2CfgArray($row[$this->P['field']], $row[$this->colsFieldName]);
			}
			$cfgArr = is_array($cfgArr) ? $cfgArr : array();
		}
		return $cfgArr;
	}

	/**
	 * Creates the HTML for the Table Wizard:
	 *
	 * @param array $cfgArr Table config array
	 * @param array $row Current parent record array
	 * @return string HTML for the table wizard
	 * @access private
	 * @todo Define visibility
	 */
	public function getTableHTML($cfgArr, $row) {
		// Traverse the rows:
		$tRows = array();
		$k = 0;
		$countLines = count($cfgArr);
		foreach ($cfgArr as $cellArr) {
			if (is_array($cellArr)) {
				// Initialize:
				$cells = array();
				$a = 0;
				// Traverse the columns:
				foreach ($cellArr as $cellContent) {
					if ($this->inputStyle) {
						$cells[] = '<input type="text"' . $this->doc->formWidth(20) . ' name="TABLE[c][' . ($k + 1) * 2 . '][' . ($a + 1) * 2 . ']" value="' . htmlspecialchars($cellContent) . '" />';
					} else {
						$cellContent = preg_replace('/<br[ ]?[\\/]?>/i', LF, $cellContent);
						$cells[] = '<textarea ' . $this->doc->formWidth(20) . ' rows="5" name="TABLE[c][' . ($k + 1) * 2 . '][' . ($a + 1) * 2 . ']">' . \TYPO3\CMS\Core\Utility\GeneralUtility::formatForTextarea($cellContent) . '</textarea>';
					}
					// Increment counter:
					$a++;
				}
				// CTRL panel for a table row (move up/down/around):
				$onClick = 'document.wizardForm.action+=\'#ANC_' . (($k + 1) * 2 - 2) . '\';';
				$onClick = ' onclick="' . htmlspecialchars($onClick) . '"';
				$ctrl = '';
				$brTag = $this->inputStyle ? '' : '<br />';
				if ($k != 0) {
					$ctrl .= '<input type="image" name="TABLE[row_up][' . ($k + 1) * 2 . ']"' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->doc->backPath, 'gfx/pil2up.gif', '') . $onClick . ' title="' . $GLOBALS['LANG']->getLL('table_up', 1) . '" />' . $brTag;
				} else {
					$ctrl .= '<input type="image" name="TABLE[row_bottom][' . ($k + 1) * 2 . ']"' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->doc->backPath, 'gfx/turn_up.gif', '') . $onClick . ' title="' . $GLOBALS['LANG']->getLL('table_bottom', 1) . '" />' . $brTag;
				}
				$ctrl .= '<input type="image" name="TABLE[row_remove][' . ($k + 1) * 2 . ']"' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->doc->backPath, 'gfx/garbage.gif', '') . $onClick . ' title="' . $GLOBALS['LANG']->getLL('table_removeRow', 1) . '" />' . $brTag;
				if ($k + 1 != $countLines) {
					$ctrl .= '<input type="image" name="TABLE[row_down][' . ($k + 1) * 2 . ']"' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->doc->backPath, 'gfx/pil2down.gif', '') . $onClick . ' title="' . $GLOBALS['LANG']->getLL('table_down', 1) . '" />' . $brTag;
				} else {
					$ctrl .= '<input type="image" name="TABLE[row_top][' . ($k + 1) * 2 . ']"' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->doc->backPath, 'gfx/turn_down.gif', '') . $onClick . ' title="' . $GLOBALS['LANG']->getLL('table_top', 1) . '" />' . $brTag;
				}
				$ctrl .= '<input type="image" name="TABLE[row_add][' . ($k + 1) * 2 . ']"' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->doc->backPath, 'gfx/add.gif', '') . $onClick . ' title="' . $GLOBALS['LANG']->getLL('table_addRow', 1) . '" />' . $brTag;
				$tRows[] = '
					<tr class="bgColor4">
						<td class="bgColor5"><a name="ANC_' . ($k + 1) * 2 . '"></a><span class="c-wizButtonsV">' . $ctrl . '</span></td>
						<td>' . implode('</td>
						<td>', $cells) . '</td>
					</tr>';
				// Increment counter:
				$k++;
			}
		}
		// CTRL panel for a table column (move left/right/around/delete)
		$cells = array();
		$cells[] = '';
		// Finding first row:
		$firstRow = reset($cfgArr);
		if (is_array($firstRow)) {
			// Init:
			$a = 0;
			$cols = count($firstRow);
			// Traverse first row:
			foreach ($firstRow as $temp) {
				$ctrl = '';
				if ($a != 0) {
					$ctrl .= '<input type="image" name="TABLE[col_left][' . ($a + 1) * 2 . ']"' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->doc->backPath, 'gfx/pil2left.gif', '') . ' title="' . $GLOBALS['LANG']->getLL('table_left', 1) . '" />';
				} else {
					$ctrl .= '<input type="image" name="TABLE[col_end][' . ($a + 1) * 2 . ']"' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->doc->backPath, 'gfx/turn_left.gif', '') . ' title="' . $GLOBALS['LANG']->getLL('table_end', 1) . '" />';
				}
				$ctrl .= '<input type="image" name="TABLE[col_remove][' . ($a + 1) * 2 . ']"' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->doc->backPath, 'gfx/garbage.gif', '') . ' title="' . $GLOBALS['LANG']->getLL('table_removeColumn', 1) . '" />';
				if ($a + 1 != $cols) {
					$ctrl .= '<input type="image" name="TABLE[col_right][' . ($a + 1) * 2 . ']"' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->doc->backPath, 'gfx/pil2right.gif', '') . ' title="' . $GLOBALS['LANG']->getLL('table_right', 1) . '" />';
				} else {
					$ctrl .= '<input type="image" name="TABLE[col_start][' . ($a + 1) * 2 . ']"' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->doc->backPath, 'gfx/turn_right.gif', '') . ' title="' . $GLOBALS['LANG']->getLL('table_start', 1) . '" />';
				}
				$ctrl .= '<input type="image" name="TABLE[col_add][' . ($a + 1) * 2 . ']"' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->doc->backPath, 'gfx/add.gif', '') . ' title="' . $GLOBALS['LANG']->getLL('table_addColumn', 1) . '" />';
				$cells[] = '<span class="c-wizButtonsH">' . $ctrl . '</span>';
				// Incr. counter:
				$a++;
			}
			$tRows[] = '
				<tr class="bgColor5">
					<td align="center">' . implode('</td>
					<td align="center">', $cells) . '</td>
				</tr>';
		}
		$content = '';
		// Implode all table rows into a string, wrapped in table tags.
		$content .= '


			<!--
				Table wizard
			-->
			<table border="0" cellpadding="0" cellspacing="1" id="typo3-tablewizard">
				' . implode('', $tRows) . '
			</table>';
		// Input type checkbox:
		$content .= '

			<!--
				Input mode check box:
			-->
			<div id="c-inputMode">
				' . '<input type="hidden" name="TABLE[textFields]" value="0" />' . '<input type="checkbox" name="TABLE[textFields]" id="textFields" value="1"' . ($this->inputStyle ? ' checked="checked"' : '') . ' /> <label for="textFields">' . $GLOBALS['LANG']->getLL('table_smallFields') . '</label>
			</div>

			<br /><br />
			';
		// Return content:
		return $content;
	}

	/**
	 * Detects if a control button (up/down/around/delete) has been pressed for an item and accordingly it will manipulate the internal TABLECFG array
	 *
	 * @return void
	 * @access private
	 * @todo Define visibility
	 */
	public function changeFunc() {
		if ($this->TABLECFG['col_remove']) {
			$kk = key($this->TABLECFG['col_remove']);
			$cmd = 'col_remove';
		} elseif ($this->TABLECFG['col_add']) {
			$kk = key($this->TABLECFG['col_add']);
			$cmd = 'col_add';
		} elseif ($this->TABLECFG['col_start']) {
			$kk = key($this->TABLECFG['col_start']);
			$cmd = 'col_start';
		} elseif ($this->TABLECFG['col_end']) {
			$kk = key($this->TABLECFG['col_end']);
			$cmd = 'col_end';
		} elseif ($this->TABLECFG['col_left']) {
			$kk = key($this->TABLECFG['col_left']);
			$cmd = 'col_left';
		} elseif ($this->TABLECFG['col_right']) {
			$kk = key($this->TABLECFG['col_right']);
			$cmd = 'col_right';
		} elseif ($this->TABLECFG['row_remove']) {
			$kk = key($this->TABLECFG['row_remove']);
			$cmd = 'row_remove';
		} elseif ($this->TABLECFG['row_add']) {
			$kk = key($this->TABLECFG['row_add']);
			$cmd = 'row_add';
		} elseif ($this->TABLECFG['row_top']) {
			$kk = key($this->TABLECFG['row_top']);
			$cmd = 'row_top';
		} elseif ($this->TABLECFG['row_bottom']) {
			$kk = key($this->TABLECFG['row_bottom']);
			$cmd = 'row_bottom';
		} elseif ($this->TABLECFG['row_up']) {
			$kk = key($this->TABLECFG['row_up']);
			$cmd = 'row_up';
		} elseif ($this->TABLECFG['row_down']) {
			$kk = key($this->TABLECFG['row_down']);
			$cmd = 'row_down';
		}
		if ($cmd && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($kk)) {
			if (substr($cmd, 0, 4) == 'row_') {
				switch ($cmd) {
				case 'row_remove':
					unset($this->TABLECFG['c'][$kk]);
					break;
				case 'row_add':
					for ($a = 1; $a <= $this->numNewRows; $a++) {
						// Checking if set: The point is that any new row inbetween existing rows
						// will be TRUE after one row is added while if rows are added in the bottom
						// of the table there will be no existing rows to stop the addition of new rows
						// which means it will add up to $this->numNewRows rows then.
						if (!isset($this->TABLECFG['c'][($kk + $a)])) {
							$this->TABLECFG['c'][$kk + $a] = array();
						} else {
							break;
						}
					}
					break;
				case 'row_top':
					$this->TABLECFG['c'][1] = $this->TABLECFG['c'][$kk];
					unset($this->TABLECFG['c'][$kk]);
					break;
				case 'row_bottom':
					$this->TABLECFG['c'][10000000] = $this->TABLECFG['c'][$kk];
					unset($this->TABLECFG['c'][$kk]);
					break;
				case 'row_up':
					$this->TABLECFG['c'][$kk - 3] = $this->TABLECFG['c'][$kk];
					unset($this->TABLECFG['c'][$kk]);
					break;
				case 'row_down':
					$this->TABLECFG['c'][$kk + 3] = $this->TABLECFG['c'][$kk];
					unset($this->TABLECFG['c'][$kk]);
					break;
				}
				ksort($this->TABLECFG['c']);
			}
			if (substr($cmd, 0, 4) == 'col_') {
				foreach ($this->TABLECFG['c'] as $cAK => $value) {
					switch ($cmd) {
					case 'col_remove':
						unset($this->TABLECFG['c'][$cAK][$kk]);
						break;
					case 'col_add':
						$this->TABLECFG['c'][$cAK][$kk + 1] = '';
						break;
					case 'col_start':
						$this->TABLECFG['c'][$cAK][1] = $this->TABLECFG['c'][$cAK][$kk];
						unset($this->TABLECFG['c'][$cAK][$kk]);
						break;
					case 'col_end':
						$this->TABLECFG['c'][$cAK][1000000] = $this->TABLECFG['c'][$cAK][$kk];
						unset($this->TABLECFG['c'][$cAK][$kk]);
						break;
					case 'col_left':
						$this->TABLECFG['c'][$cAK][$kk - 3] = $this->TABLECFG['c'][$cAK][$kk];
						unset($this->TABLECFG['c'][$cAK][$kk]);
						break;
					case 'col_right':
						$this->TABLECFG['c'][$cAK][$kk + 3] = $this->TABLECFG['c'][$cAK][$kk];
						unset($this->TABLECFG['c'][$cAK][$kk]);
						break;
					}
					ksort($this->TABLECFG['c'][$cAK]);
				}
			}
		}
		// Convert line breaks to <br /> tags:
		foreach ($this->TABLECFG['c'] as $a => $value) {
			foreach ($this->TABLECFG['c'][$a] as $b => $value2) {
				$this->TABLECFG['c'][$a][$b] = str_replace(LF, '<br />', str_replace(CR, '', $this->TABLECFG['c'][$a][$b]));
			}
		}
	}

	/**
	 * Converts the input array to a configuration code string
	 *
	 * @param array $cfgArr Array of table configuration (follows the input structure from the table wizard POST form)
	 * @return string The array converted into a string with line-based configuration.
	 * @see cfgString2CfgArray()
	 * @todo Define visibility
	 */
	public function cfgArray2CfgString($cfgArr) {
		// Initialize:
		$inLines = array();
		// Traverse the elements of the table wizard and transform the settings into configuration code.
		foreach ($this->TABLECFG['c'] as $a => $value) {
			$thisLine = array();
			foreach ($this->TABLECFG['c'][$a] as $b => $value) {
				$thisLine[] = $this->tableParsing_quote . str_replace($this->tableParsing_delimiter, '', $this->TABLECFG['c'][$a][$b]) . $this->tableParsing_quote;
			}
			$inLines[] = implode($this->tableParsing_delimiter, $thisLine);
		}
		// Finally, implode the lines into a string:
		$bodyText = implode(LF, $inLines);
		// Return the configuration code:
		return $bodyText;
	}

	/**
	 * Converts the input configuration code string into an array
	 *
	 * @param string $cfgStr Configuration code
	 * @param integer $cols Default number of columns
	 * @return array Configuration array
	 * @see cfgArray2CfgString()
	 * @todo Define visibility
	 */
	public function cfgString2CfgArray($cfgStr, $cols) {
		// Explode lines in the configuration code - each line is a table row.
		$tLines = explode(LF, $cfgStr);
		// Setting number of columns
		// auto...
		if (!$cols && trim($tLines[0])) {
			$cols = count(explode($this->tableParsing_delimiter, $tLines[0]));
		}
		$cols = $cols ? $cols : 4;
		// Traverse the number of table elements:
		$cfgArr = array();
		foreach ($tLines as $k => $v) {
			// Initialize:
			$vParts = explode($this->tableParsing_delimiter, $v);
			// Traverse columns:
			for ($a = 0; $a < $cols; $a++) {
				if ($this->tableParsing_quote && substr($vParts[$a], 0, 1) == $this->tableParsing_quote && substr($vParts[$a], -1, 1) == $this->tableParsing_quote) {
					$vParts[$a] = substr(trim($vParts[$a]), 1, -1);
				}
				$cfgArr[$k][$a] = $vParts[$a];
			}
		}
		// Return configuration array:
		return $cfgArr;
	}

}


?>