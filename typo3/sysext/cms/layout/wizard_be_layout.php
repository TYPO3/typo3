<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2009 Kasper Sk?rh?j (kasperYYYY@typo3.com)
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
 * Grid wizard
 *
 * $Id $
 */

require_once('conf.php');
require($BACK_PATH . 'init.php');
require($BACK_PATH . 'template.php');
$LANG->includeLLFile('EXT:lang/locallang_wizards.xml');

/**
 * Script Class for grid wizard
 *
 * @author	T3UXW09 Team1 <modernbe@cybercraft.de>
 * @package TYPO3
 * @subpackage core
 */
class SC_wizard_be_layout {

	// GET vars:
	var $P; // Wizard parameters, coming from TCEforms linking to the wizard.

	/**
	 * document template object
	 *
	 * @var smallDoc
	 */
	var $doc;
	var $content; // Accumulated content.


	/**
	 * Initialises the Class
	 *
	 * @return	void
	 */
	function init() {


		// Setting GET vars (used in frameset script):
		$this->P = t3lib_div::_GP('P', 1);

		//data[layouts][2][config]
		$this->formName = $this->P['formName'];
		$this->fieldName = $this->P['itemName'];
		$this->md5ID = $this->P['md5ID'];
		$uid = intval($this->P['uid']);

		// Initialize document object:
		$this->doc = t3lib_div::makeInstance('noDoc');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];

		$pageRenderer = $this->doc->getPageRenderer();
		$pageRenderer->addJsFile($GLOBALS['BACK_PATH'] . TYPO3_MOD_PATH . 'res/grideditor.js');
		$pageRenderer->addJsInlineCode('storeData', '
			function storeData(data)	{
				if (parent.opener && parent.opener.document && parent.opener.document.' . $this->formName . ' && parent.opener.document.' . $this->formName . '["' . $this->fieldName . '"])	{
					parent.opener.document.' . $this->formName . '["' . $this->fieldName . '"].value = data;
					parent.opener.TBE_EDITOR.fieldChanged("be_layouts","' . $uid . '","config","data[be_layouts][' . $uid . '][config]");
				}
			}
		');

		$languageLabels = array(
			'save' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_wizards.xml:grid_labelSave', 1),
			'title' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_wizards.xml:grid_windowTitle', 1),
			'name' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_wizards.xml:grid_labelName', 1),
			'column' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_wizards.xml:grid_labelColumn', 1)
		);
		$pageRenderer->addInlineLanguageLabelArray($languageLabels);

		// select record
		$record = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($this->P['field'], $this->P['table'], 'uid=' . intval($this->P['uid']));
		if (trim($record[0][$this->P['field']]) == '') {
			$t3GridData = "[[{colspan:1,rowspan:1,spanned:false,name:''}]]";
			$colCount = 1;
			$rowCount = 1;
		} else {

			// load TS parser
			$parser = t3lib_div::makeInstance('t3lib_TSparser');
			$parser->parse($record[0][$this->P['field']]);
			$data = $parser->setup['be_layout.'];
			$t3GridData = '[';
			$colCount = $data['colCount'];
			$rowCount = $data['rowCount'];
			$dataRows = $data['rows.'];
			$spannedMatrix = array();

			for ($i = 1; $i <= $rowCount; $i++) {
				$rowString = '';
				for ($j = 1; $j <= $colCount; $j++) {
					if ($j == 1) {
						$row = array_shift($dataRows);
						$columns = $row['columns.'];
						$rowString = '[';
						$cells = array();
					}
					if (!$spannedMatrix[$i][$j]) {
						if (is_array($columns) && count($columns)) {
							$column = array_shift($columns);
							$cellString = '{';
							$cellData = array();
							if (isset($column['colspan'])) {
								$cellData[] = 'colspan:' . intval($column['colspan']);
								if (isset($column['rowspan'])) {
									for ($spanRow = 0; $spanRow < intval($column['rowspan']); $spanRow++) {
										for ($spanColumn = 0; $spanColumn < intval($column['colspan']); $spanColumn++) {
											$spannedMatrix[$i + $spanRow][$j + $spanColumn] = 1;
										}
									}
								} else {
									for ($spanColumn = 0; $spanColumn < intval($column['colspan']); $spanColumn++) {
										$spannedMatrix[$i][$j + $spanColumn] = 1;
									}
								}
							} else {
								$cellData[] = 'colspan:1';
								if (isset($column['rowspan'])) {
									for ($spanRow = 0; $spanRow < intval($column['rowspan']); $spanRow++) {
										$spannedMatrix[$i + $spanRow][$j] = 1;
									}
								}
							}
							if (isset($column['rowspan'])) {
								$cellData[] = 'rowspan:' . intval($column['rowspan']);
							} else {
								$cellData[] = 'rowspan:1';
							}
							if (isset($column['name'])) {
								$cellData[] = 'name:\'' . $column['name'] . '\'';
							}
							if (isset($column['colPos'])) {
								$cellData[] = 'column:' . $column['colPos'];
							}

							$cellString .= implode(',', $cellData) . '}';
							$cells[] = $cellString;

						}
					} else {
						$cells[] = '{colspan:1,rowspan:1,spanned:1}';
					}
				}
				$rowString .= implode(',', $cells);
				if ($rowString) {
					$rowString .= ']';
				}
				$rows[] = $rowString;
				ksort($spannedMatrix[$i]);
			}

			$t3GridData .= implode(',', $rows) . ']';


		}

		$this->doc->JScode .= '
 			<script type="text/javascript">
			Ext.onReady(function() {
				t3Grid = new TYPO3.Backend.t3Grid({
					data: ' . $t3GridData . ',
					colCount: ' . $colCount . ',
					rowCount: ' . $rowCount . ',
					targetElement: \'editor\'
				});

				t3Grid.drawTable();
			});
			</script>
		';


		$this->doc->styleSheetFile_post = TYPO3_MOD_PATH . 'res/grideditor.css';
		$this->doc->styleSheetFile2 = 'sysext/t3skin/stylesheets/extjs/xtheme-t3skin.css';
	}

	/**
	 * Main Method, rendering either colorpicker or frameset depending on ->showPicker
	 *
	 * @return	void
	 */
	function main() {

		$content .= '<a href="#" title="' .
			$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.saveDoc', TRUE) . '" onclick="storeData(t3Grid.export2LayoutRecord());return true;">' .
			 t3lib_iconWorks::getSpriteIcon('actions-document-save') . '</a>';

		$content .= '<a href="#" title="' .
			$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.saveCloseDoc', TRUE) . '" onclick="storeData(t3Grid.export2LayoutRecord());window.close();return true;">' .
			t3lib_iconWorks::getSpriteIcon('actions-document-save-close') . '</a>';

		$content .= '<a href="#" title="'.
			$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.closeDoc', TRUE) . '" onclick="window.close();return true;">' .
			 t3lib_iconWorks::getSpriteIcon('actions-document-close') . '</a>';


		$content .= $this->doc->spacer(10);

		$content .= '
		<table border="0" width="100%" height="100%" id="outer_container">
			<tr>
				<td class="editor_cell">
					<div id="editor">
					</div>
				</td>
				<td width="20" valign="center">
					<a id="addCol" href="#" title="' . $GLOBALS['LANG']->getLL('grid_addColumn') . '" onclick="t3Grid.addColumn(); t3Grid.drawTable(\'editor\');">
						<img src="res/t3grid-tableright.png" border="0" />
					</a><br />
					<a id="addCol" href="#" title="' . $GLOBALS['LANG']->getLL('grid_removeColumn') . '" onclick="t3Grid.removeColumn(); t3Grid.drawTable(\'editor\');">
						<img src="res/t3grid-tableleft.png" border="0" />
					</a>
				</td>
			</tr>
			<tr>
				<td colspan="2" height="20" align="center">
					<a id="addCol" href="#" title="' . $GLOBALS['LANG']->getLL('grid_addRow') . '" onclick="t3Grid.addRow(); t3Grid.drawTable(\'editor\');">
						<img src="res/t3grid-tabledown.png" border="0" />
					</a>
					<a id="addCol" href="#" title="' . $GLOBALS['LANG']->getLL('grid_removeRow') . '" onclick="t3Grid.removeRow(); t3Grid.drawTable(\'editor\');">
						<img src="res/t3grid-tableup.png" border="0" />
					</a>
				</td>
			</tr>
		</table>
		';

		$this->content = $content;
	}

	/**
	 * Returnes the sourcecode to the browser
	 *
	 * @return	void
	 */
	function printContent() {
		echo $this->doc->render(
			'Grid wizard',
			$this->content
		);
	}

}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/wizard_be_layout.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/wizard_be_layout.php']);
}


// Make instance:
$SOBE = t3lib_div::makeInstance('SC_wizard_be_layout');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

?>
