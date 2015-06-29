<?php
namespace TYPO3\CMS\Backend\Controller\Wizard;

/*
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

use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Script Class for rendering the Table Wizard
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class TableController extends AbstractWizardController {

	/**
	 * Document template object
	 *
	 * @var DocumentTemplate
	 */
	public $doc;

	/**
	 * Content accumulation for the module.
	 *
	 * @var string
	 */
	public $content;

	/**
	 * If TRUE, <input> fields are shown instead of textareas.
	 *
	 * @var bool
	 */
	public $inputStyle = FALSE;

	/**
	 * If set, the string version of the content is interpreted/written as XML
	 * instead of the original line-based kind. This variable still needs binding
	 * to the wizard parameters - but support is ready!
	 *
	 * @var int
	 */
	public $xmlStorage = 0;

	/**
	 * Number of new rows to add in bottom of wizard
	 *
	 * @var int
	 */
	public $numNewRows = 1;

	/**
	 * Name of field in parent record which MAY contain the number of columns for the table
	 * here hardcoded to the value of tt_content. Should be set by FormEngine parameters (from P)
	 *
	 * @var string
	 */
	public $colsFieldName = 'cols';

	/**
	 * Wizard parameters, coming from FormEngine linking to the wizard.
	 *
	 * @var array
	 */
	public $P;

	/**
	 * The array which is constantly submitted by the multidimensional form of this wizard.
	 *
	 * @var array
	 */
	public $TABLECFG;

	/**
	 * Table parsing
	 * quoting of table cells
	 *
	 * @var string
	 */
	public $tableParsing_quote;

	/**
	 * delimiter between table cells
	 *
	 * @var string
	 */
	public $tableParsing_delimiter;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->getLanguageService()->includeLLFile('EXT:lang/locallang_wizards.xlf');
		$GLOBALS['SOBE'] = $this;

		$this->init();
	}

	/**
	 * Initialization of the class
	 *
	 * @return void
	 */
	protected function init() {
		// GPvars:
		$this->P = GeneralUtility::_GP('P');
		$this->TABLECFG = GeneralUtility::_GP('TABLE');
		// Setting options:
		$this->xmlStorage = $this->P['params']['xmlOutput'];
		$this->numNewRows = MathUtility::forceIntegerInRange($this->P['params']['numNewRows'], 1, 50, 5);
		// Textareas or input fields:
		$this->inputStyle = isset($this->TABLECFG['textFields']) ? (bool)$this->TABLECFG['textFields'] : TRUE;
		// Document template object:
		$this->doc = GeneralUtility::makeInstance(DocumentTemplate::class);
		$this->doc->backPath = $this->getBackPath();
		$this->doc->setModuleTemplate('EXT:backend/Resources/Private/Templates/wizard_table.html');
		// Setting form tag:
		list($rUri) = explode('#', GeneralUtility::getIndpEnv('REQUEST_URI'));
		$this->doc->form = '<form action="' . htmlspecialchars($rUri) . '" method="post" name="wizardForm">';
		$this->tableParsing_delimiter = '|';
		$this->tableParsing_quote = '';
	}

	/**
	 * Main function, rendering the table wizard
	 *
	 * @return void
	 */
	public function main() {
		if ($this->P['table'] && $this->P['field'] && $this->P['uid']) {
			$this->content .= $this->doc->section($this->getLanguageService()->getLL('table_title'), $this->tableWizard(), 0, 1);
		} else {
			$this->content .= $this->doc->section($this->getLanguageService()->getLL('table_title'), '<span class="typo3-red">' . $this->getLanguageService()->getLL('table_noData', TRUE) . '</span>', 0, 1);
		}
		// Setting up the buttons and markers for docHeader
		$docHeaderButtons = $this->getButtons();
		$markers['CSH'] = $docHeaderButtons['csh'];
		$markers['CONTENT'] = $this->content;
		// Build the <body> for the module
		$this->content = $this->doc->startPage('Table');
		$this->content .= $this->doc->moduleBody(array(), $docHeaderButtons, $markers);
		$this->content .= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return void
	 */
	public function printContent() {
		echo $this->content;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return array All available buttons as an associative array
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
			$buttons['csh'] = BackendUtility::cshItem('xMOD_csh_corebe', 'wizard_table_wiz');
			// CSH Buttons
			$buttons['csh_buttons'] = BackendUtility::cshItem('xMOD_csh_corebe', 'wizard_table_wiz_buttons');
			// Close
			$buttons['close'] = '<a href="#" onclick="' . htmlspecialchars(('jumpToUrl(' . GeneralUtility::quoteJSvalue(GeneralUtility::sanitizeLocalUrl($this->P['returnUrl'])) . '); return false;')) . '">' . IconUtility::getSpriteIcon('actions-document-close', array('title' => $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:rm.closeDoc', TRUE))) . '</a>';
			// Save
			$buttons['save'] = '<button class="c-inputButton" name="savedok">' . IconUtility::getSpriteIcon('actions-document-save', array('title' => $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveDoc', TRUE))) . '</button>';
			// Save & Close
			$buttons['save_close'] = '<button class="c-inputButton" name="saveandclosedok">' . IconUtility::getSpriteIcon('actions-document-save-close', array('title' => $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveCloseDoc', TRUE))) . '</button>';
			// Reload
			$buttons['reload'] = '<button class="c-inputButton" name="_refresh">' . IconUtility::getSpriteIcon('actions-system-refresh', array('title' => $this->getLanguageService()->getLL('forms_refresh', TRUE))) . '</button>';
		}
		return $buttons;
	}

	/**
	 * Draws the table wizard content
	 *
	 * @return string HTML content for the form.
	 * @throws \RuntimeException
	 */
	public function tableWizard() {
		if (!$this->checkEditAccess($this->P['table'], $this->P['uid'])) {
			throw new \RuntimeException('Wizard Error: No access', 1349692692);
		}
		// First, check the references by selecting the record:
		$row = BackendUtility::getRecord($this->P['table'], $this->P['uid']);
		if (!is_array($row)) {
			throw new \RuntimeException('Wizard Error: No reference to record', 1294587125);
		}
		// This will get the content of the form configuration code field to us - possibly cleaned up,
		// saved to database etc. if the form has been submitted in the meantime.
		$tableCfgArray = $this->getConfigCode($row);
		// Generation of the Table Wizards HTML code:
		$content = $this->getTableHTML($tableCfgArray);
		// Return content:
		return $content;
	}

	/*
	 *
	 * Helper functions
	 *
	 */

	/**
	 * Will get and return the configuration code string
	 * Will also save (and possibly redirect/exit) the content if a save button has been pressed
	 *
	 * @param array $row Current parent record row
	 * @return array Table config code in an array
	 * @internal
	 */
	public function getConfigCode($row) {
		// Get delimiter settings
		$flexForm = GeneralUtility::xml2array($row['pi_flexform']);
		if (is_array($flexForm)) {
			$this->tableParsing_quote = $flexForm['data']['s_parsing']['lDEF']['tableparsing_quote']['vDEF'] ? chr((int)$flexForm['data']['s_parsing']['lDEF']['tableparsing_quote']['vDEF']) : '';
			$this->tableParsing_delimiter = $flexForm['data']['s_parsing']['lDEF']['tableparsing_delimiter']['vDEF'] ? chr((int)$flexForm['data']['s_parsing']['lDEF']['tableparsing_delimiter']['vDEF']) : '|';
		}
		// If some data has been submitted, then construct
		if (isset($this->TABLECFG['c'])) {
			// Process incoming:
			$this->changeFunc();
			// Convert to string (either line based or XML):
			if ($this->xmlStorage) {
				// Convert the input array to XML:
				$bodyText = GeneralUtility::array2xml_cs($this->TABLECFG['c'], 'T3TableWizard');
				// Setting cfgArr directly from the input:
				$configuration = $this->TABLECFG['c'];
			} else {
				// Convert the input array to a string of configuration code:
				$bodyText = $this->cfgArray2CfgString($this->TABLECFG['c']);
				// Create cfgArr from the string based configuration - that way it is cleaned up and any incompatibilities will be removed!
				$configuration = $this->cfgString2CfgArray($bodyText, $row[$this->colsFieldName]);
			}
			// If a save button has been pressed, then save the new field content:
			if ($_POST['savedok_x'] || $_POST['saveandclosedok_x']) {
				// Get DataHandler object:
				/** @var DataHandler $dataHandler */
				$dataHandler = GeneralUtility::makeInstance(DataHandler::class);
				$dataHandler->stripslashes_values = FALSE;
				// Put content into the data array:
				$data = array();
				$data[$this->P['table']][$this->P['uid']][$this->P['field']] = $bodyText;
				// Perform the update:
				$dataHandler->start($data, array());
				$dataHandler->process_datamap();
				// If the save/close button was pressed, then redirect the screen:
				if ($_POST['saveandclosedok_x']) {
					HttpUtility::redirect(GeneralUtility::sanitizeLocalUrl($this->P['returnUrl']));
				}
			}
		} else {
			// If nothing has been submitted, load the $bodyText variable from the selected database row:
			if ($this->xmlStorage) {
				$configuration = GeneralUtility::xml2array($row[$this->P['field']]);
			} else {
				// Regular line based table configuration:
				$configuration = $this->cfgString2CfgArray($row[$this->P['field']], $row[$this->colsFieldName]);
			}
			$configuration = is_array($configuration) ? $configuration : array();
		}
		return $configuration;
	}

	/**
	 * Creates the HTML for the Table Wizard:
	 *
	 * @param array $configuration Table config array
	 * @return string HTML for the table wizard
	 * @internal
	 */
	public function getTableHTML($configuration) {
		// Traverse the rows:
		$tRows = array();
		$k = 0;
		$countLines = count($configuration);
		foreach ($configuration as $cellArr) {
			if (is_array($cellArr)) {
				// Initialize:
				$cells = array();
				$a = 0;
				// Traverse the columns:
				foreach ($cellArr as $cellContent) {
					if ($this->inputStyle) {
						$cells[] = '<input class="form-control" type="text"' . $this->doc->formWidth(20) . ' name="TABLE[c][' . ($k + 1) * 2 . '][' . ($a + 1) * 2 . ']" value="' . htmlspecialchars($cellContent) . '" />';
					} else {
						$cellContent = preg_replace('/<br[ ]?[\\/]?>/i', LF, $cellContent);
						$cells[] = '<textarea class="form-control" ' . $this->doc->formWidth(20) . ' rows="6" name="TABLE[c][' . ($k + 1) * 2 . '][' . ($a + 1) * 2 . ']">' . GeneralUtility::formatForTextarea($cellContent) . '</textarea>';
					}
					// Increment counter:
					$a++;
				}
				// CTRL panel for a table row (move up/down/around):
				$onClick = 'document.wizardForm.action+=' . GeneralUtility::quoteJSvalue('#ANC_' . (($k + 1) * 2 - 2)) . ';';
				$onClick = ' onclick="' . htmlspecialchars($onClick) . '"';
				$ctrl = '';
				if ($k !== 0) {
					$ctrl .= '<button class="btn btn-default" name="TABLE[row_up][' . ($k + 1) * 2 . ']" title="' . $this->getLanguageService()->getLL('table_up', TRUE) . '"'. $onClick . '><span class="t3-icon fa fa-fw fa-angle-up"></span></button>';
				} else {
					$ctrl .= '<button class="btn btn-default" name="TABLE[row_bottom][' . ($k + 1) * 2 . ']" title="' . $this->getLanguageService()->getLL('table_bottom', TRUE) . '"'. $onClick . '><span class="t3-icon fa fa-fw fa-angle-double-down"></span></button>';
				}
				if ($k + 1 !== $countLines) {
					$ctrl .= '<button class="btn btn-default" name="TABLE[row_down][' . ($k + 1) * 2 . ']" title="' . $this->getLanguageService()->getLL('table_down', TRUE) . '"'. $onClick . '><span class="t3-icon fa fa-fw fa-angle-down"></span></button>';
				} else {
					$ctrl .= '<button class="btn btn-default" name="TABLE[row_top][' . ($k + 1) * 2 . ']" title="' . $this->getLanguageService()->getLL('table_top', TRUE) . '"'. $onClick . '><span class="t3-icon fa fa-fw fa-angle-double-up"></span></button>';
				}
				$ctrl .= '<button class="btn btn-default" name="TABLE[row_remove][' . ($k + 1) * 2 . ']" title="' . $this->getLanguageService()->getLL('table_removeRow', TRUE) . '"'. $onClick . '><span class="t3-icon fa fa-fw fa-trash"></span></button>';
				$ctrl .= '<button class="btn btn-default" name="TABLE[row_add][' . ($k + 1) * 2 . ']" title="' . $this->getLanguageService()->getLL('table_addRow', TRUE) . '"'. $onClick . '><span class="t3-icon fa fa-fw fa-plus"></span></button>';
				$tRows[] = '
					<tr>
						<td>
							<a name="ANC_' . ($k + 1) * 2 . '"></a>
							<span class="btn-group' . ($this->inputStyle ? '' : '-vertical') . '">' . $ctrl . '</span>
						</td>
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
		$firstRow = reset($configuration);
		if (is_array($firstRow)) {
			$cols = count($firstRow);
			for ($a = 1; $a <= $cols; $a++) {
				$b = $a * 2;
				$ctrl = '';
				if ($a !== 1) {
					$ctrl .= '<button class="btn btn-default" name="TABLE[col_left][' . $b . ']" title="' . $this->getLanguageService()->getLL('table_left', TRUE) . '"><span class="t3-icon fa fa-fw fa-angle-left"></span></button>';
				} else {
					$ctrl .= '<button class="btn btn-default" name="TABLE[col_end][' . $b . ']" title="' . $this->getLanguageService()->getLL('table_end', TRUE) . '"><span class="t3-icon fa fa-fw fa-angle-double-right"></span></button>';
				}
				if ($a != $cols) {
					$ctrl .= '<button class="btn btn-default" name="TABLE[col_right][' . $b . ']" title="' . $this->getLanguageService()->getLL('table_right', TRUE) . '"><span class="t3-icon fa fa-fw fa-angle-right"></span></button>';
				} else {
					$ctrl .= '<button class="btn btn-default" name="TABLE[col_start][' . $b . ']" title="' . $this->getLanguageService()->getLL('table_start', TRUE) . '"><span class="t3-icon fa fa-fw fa-angle-double-left"></span></button>';
				}
				$ctrl .= '<button class="btn btn-default" name="TABLE[col_remove][' . $b . ']" title="' . $this->getLanguageService()->getLL('table_removeColumn', TRUE) . '"><span class="t3-icon fa fa-fw fa-trash"></span></button>';
				$ctrl .= '<button class="btn btn-default" name="TABLE[col_add][' . $b . ']" title="' . $this->getLanguageService()->getLL('table_addColumn', TRUE) . '"><span class="t3-icon fa fa-fw fa-plus"></span></button>';
				$cells[] = '<span class="btn-group">' . $ctrl . '</span>';
			}
			$tRows[] = '
				<tfoot>
					<tr>
						<td>' . implode('</td>
						<td>', $cells) . '</td>
					</tr>
				</tfoot>';
		}
		$content = '';
		// Implode all table rows into a string, wrapped in table tags.
		$content .= '

			<!-- Table wizard -->
			<div class="table-fit table-fit-inline-block">
				<table id="typo3-tablewizard" class="table table-center">
					' . implode('', $tRows) . '
				</table>
			</div>';
		// Input type checkbox:
		$content .= '

			<!-- Input mode check box: -->
			<div class="checkbox">
				<input type="hidden" name="TABLE[textFields]" value="0" />
				<label for="textFields">
					<input type="checkbox" name="TABLE[textFields]" id="textFields" value="1"' . ($this->inputStyle ? ' checked="checked"' : '') . ' />
					' . $this->getLanguageService()->getLL('table_smallFields') . '
				</label>
			</div>';
		return $content;
	}

	/**
	 * Detects if a control button (up/down/around/delete) has been pressed for an item and accordingly it will manipulate the internal TABLECFG array
	 *
	 * @return void
	 * @internal
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
		} else {
			$kk = '';
			$cmd = '';
		}
		if ($cmd && MathUtility::canBeInterpretedAsInteger($kk)) {
			if (StringUtility::beginsWith($cmd, 'row_')) {
				switch ($cmd) {
					case 'row_remove':
						unset($this->TABLECFG['c'][$kk]);
						break;
					case 'row_add':
						for ($a = 1; $a <= $this->numNewRows; $a++) {
							// Checking if set: The point is that any new row between existing rows
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
			if (StringUtility::beginsWith($cmd, 'col_')) {
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
	 */
	public function cfgArray2CfgString($cfgArr) {
		$inLines = array();
		// Traverse the elements of the table wizard and transform the settings into configuration code.
		foreach ($cfgArr as $valueA) {
			$thisLine = array();
			foreach ($valueA as $valueB) {
				$thisLine[] = $this->tableParsing_quote . str_replace($this->tableParsing_delimiter, '', $valueB) . $this->tableParsing_quote;
			}
			$inLines[] = implode($this->tableParsing_delimiter, $thisLine);
		}
		// Finally, implode the lines into a string:
		return implode(LF, $inLines);
	}

	/**
	 * Converts the input configuration code string into an array
	 *
	 * @param string $configurationCode Configuration code
	 * @param int $columns Default number of columns
	 * @return array Configuration array
	 * @see cfgArray2CfgString()
	 */
	public function cfgString2CfgArray($configurationCode, $columns) {
		// Explode lines in the configuration code - each line is a table row.
		$tableLines = explode(LF, $configurationCode);
		// Setting number of columns
		// auto...
		if (!$columns && trim($tableLines[0])) {
			$columns = count(explode($this->tableParsing_delimiter, $tableLines[0]));
		}
		$columns = $columns ?: 4;
		// Traverse the number of table elements:
		$configurationArray = array();
		foreach ($tableLines as $key => $value) {
			// Initialize:
			$valueParts = explode($this->tableParsing_delimiter, $value);
			// Traverse columns:
			for ($a = 0; $a < $columns; $a++) {
				if ($this->tableParsing_quote && $valueParts[$a][0] === $this->tableParsing_quote && substr($valueParts[$a], -1, 1) === $this->tableParsing_quote) {
					$valueParts[$a] = substr(trim($valueParts[$a]), 1, -1);
				}
				$configurationArray[$key][$a] = $valueParts[$a];
			}
		}
		return $configurationArray;
	}

}
