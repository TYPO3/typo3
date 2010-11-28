<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Steffen Ritter <info@steffen-ritter.net>
 *  (c) 2010 Steffen Kamper <steffen@typo3.org>
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
 * TCEforms wizard for rendering an AJAX selector for records
 *
 * $Id: class.t3lib_tceforms_suggest.php 7905 2010-06-13 14:42:33Z ohader $
 *
 * @author Steffen Ritter <info@steffen-ritter.net>
 * @author Steffen Kamper <steffen@typo3.org>
 */

class t3lib_TCEforms_Tree {

	/**
	 * @param string The table name of the record
	 * @param string The field name which this element is supposed to edit
	 * @param array The record data array where the value(s) for the field can be found
	 * @param array An array with additional configuration options.
	 * @return string The HTML code for the TCEform field
	 */
	public function renderField($table, $field, $row, &$PA, &$tceForms) {
		$valueArray = explode(',', $row[$field]);
		$selectedNodes = array();
		if (count($valueArray)) {
			foreach ($valueArray as $selectedValue) {
				$temp = explode('|', $selectedValue);
				$selectedNodes[] = $temp[0];
			}
		}

		$treeDataProvider = t3lib_tree_Tca_DataProviderFactory::getDataProvider(
			$GLOBALS['TCA'][$table]['columns'][$field]['config'],
			$table,
			$field,
			$row
		);
		$treeDataProvider->setSelectedList(implode(',', $selectedNodes));
		$treeDataProvider->initializeTreeData();
		$treeDataProvider->setGeneratedTSConfig($tceForms->setTSconfig($table, $row));

		$treeRenderer = t3lib_div::makeInstance('t3lib_tree_Tca_ExtJsArrayRenderer');
		$tree = t3lib_div::makeInstance('t3lib_tree_Tca_TcaTree');
		$tree->setDataProvider($treeDataProvider);
		$tree->setNodeRenderer($treeRenderer);

		$treeData = $tree->render();

		$itemArray = array();
		if (is_array($PA['fieldConf']['config']['items'])) {
			foreach ($PA['fieldConf']['config']['items'] as $additionalItem) {
				if ($additionalItem[1] !== '--div--') {
					$item = new stdClass();
					$item->uid = $additionalItem[1];
					$item->text = $GLOBALS['LANG']->sL($additionalItem[0]);
					$item->selectable = TRUE;
					$item->leaf = TRUE;
					$item->checked = in_array($additionalItem[1], $selectedNodes);
					if (file_exists(PATH_typo3 . $additionalItem[3])) {
						$item->icon = $additionalItem[3];
					} elseif (strlen(trim($additionalItem[3]))) {
						$item->iconCls = t3lib_iconWorks::getSpriteIconClasses($additionalItem[3]);
					}

					$itemArray[] = $item;
				}
			}
		}
		$itemArray[] = $treeData;
		$treeData = json_encode($itemArray);

		$id = md5($PA['itemFormElName']);

		if (isset($PA['fieldConf']['config']['size']) && intval($PA['fieldConf']['config']['size']) > 0) {
			$height = intval($PA['fieldConf']['config']['size']) * 20;
		} else {
			$height = 280;
		}
		if (isset($PA['fieldConf']['config']['autoSizeMax']) && intval($PA['fieldConf']['config']['autoSizeMax']) > 0) {
			$autoSizeMax = intval($PA['fieldConf']['config']['autoSizeMax']) * 20;
		}


		$header = FALSE;
		$expanded = FALSE;
		$appearance = $PA['fieldConf']['config']['treeConfig']['appearance'];
		if (is_array($appearance)) {
			$header = $appearance['showHeader'] ? TRUE : FALSE;
			$expanded = ($appearance['expandAll'] === TRUE);
		}

		$onChange = '';
		if ($PA['fieldChangeFunc']['TBE_EDITOR_fieldChanged']) {
			$onChange = substr($PA['fieldChangeFunc']['TBE_EDITOR_fieldChanged'], 0, -1);
		}

		/** @var $pageRenderer t3lib_PageRenderer */
		$pageRenderer = $GLOBALS['SOBE']->doc->getPageRenderer();
		$pageRenderer->loadExtJs();
		$pageRenderer->addJsFile('../t3lib/js/extjs/tree/tree.js');
		$pageRenderer->addExtOnReadyCode('
			TYPO3.Components.Tree.StandardTreeItemData["' . $id . '"] = ' . $treeData . ';
			var tree' . $id . ' = new TYPO3.Components.Tree.StandardTree({
				checkChangeHandler: TYPO3.Components.Tree.TcaCheckChangeHandler,
				id: "' . $id . '",
				showHeader: ' . intval($header) . ',
				onChange: "' . $onChange . '",
				tcaMaxItems: ' . ($PA['fieldConf']['config']['maxitems'] ? intval($PA['fieldConf']['config']['maxitems']) : 99999) . ',
				tcaExclusiveKeys: "' . (
		$PA['fieldConf']['config']['exclusiveKeys']
				? $PA['fieldConf']['config']['exclusiveKeys'] : '') . '",
				ucId: "' . md5($table . '|' . $field) . '",
				selModel: TYPO3.Components.Tree.EmptySelectionModel
			});
			tree' . $id . '.' . ($autoSizeMax
				? 'bodyStyle = "max-height: ' . $autoSizeMax . 'px;"'
				: 'height = ' . $height
		) . ';
			tree' . $id . '.render("tree_' . $id . '");' .
										 ($expanded ? 'tree' . $id . '.expandAll();' : '') . '
		');

		$formField = '
			<div class="typo3-tceforms-tree">
				<input type="hidden" name="' . $PA['itemFormElName'] . '" id="treeinput' . $id . '" value="' . $row[$field] . '" />
			</div>
			<div id="tree_' . $id . '">

			</div>';

		return $formField;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['classes/t3lib/tceforms/class.t3lib_tceforms_tree.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['classes/t3lib/tceforms/class.t3lib_tceforms_tree.php']);
}

?>