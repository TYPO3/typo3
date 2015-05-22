<?php
namespace TYPO3\CMS\Backend\Form\Element;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Tree\TableConfiguration\ExtJsArrayTreeRenderer;
use TYPO3\CMS\Core\Tree\TableConfiguration\TableConfigurationTree;
use TYPO3\CMS\Core\Tree\TableConfiguration\TreeDataProviderFactory;
use TYPO3\CMS\Core\Type\Bitmask\JsConfirmation;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;

/**
 * Render data as a tree.
 *
 * Typically rendered for config [type=select, renderMode=tree
 */
class SelectTreeElement extends AbstractFormElement {

	/**
	 * Render tree widget
	 *
	 * @return array As defined in initializeResultArray() of AbstractNode
	 */
	public function render() {
		$table = $this->globalOptions['table'];
		$field = $this->globalOptions['fieldName'];
		$row = $this->globalOptions['databaseRow'];
		$parameterArray = $this->globalOptions['parameterArray'];

		// Field configuration from TCA:
		$config = $parameterArray['fieldConf']['config'];
		$disabled = '';
		if ($this->isGlobalReadonly() || $config['readOnly']) {
			$disabled = ' disabled="disabled"';
		}

		$resultArray = $this->initializeResultArray();

		// "Extra" configuration; Returns configuration for the field based on settings found in the "types" fieldlist.
		$specConf = BackendUtility::getSpecConfParts($parameterArray['fieldConf']['defaultExtras']);
		$selItems = FormEngineUtility::getSelectItems($table, $field, $row, $parameterArray);

		$maxitems = (int)$config['maxitems'];

		$html = $this->renderField($table, $field, $row, $parameterArray, $config, $selItems);

		// Register the required number of elements
		$minitems = MathUtility::forceIntegerInRange($config['minitems'], 0);
		$resultArray['requiredElements'][$parameterArray['itemFormElName']] = array(
			$minitems,
			$maxitems,
			'imgName' => $table . '_' . $row['uid'] . '_' . $field
		);
		$tabAndInlineStack = $this->globalOptions['tabAndInlineStack'];
		if (!empty($tabAndInlineStack) && preg_match('/^(.+\\])\\[(\\w+)\\]$/', $parameterArray['itemFormElName'], $match)) {
			array_shift($match);
			$resultArray['requiredNested'][$parameterArray['itemFormElName']] = array(
				'parts' => $match,
				'level' => $tabAndInlineStack,
			);
		}

		// Wizards:
		if (!$disabled) {
			$altItem = '<input type="hidden" name="' . $parameterArray['itemFormElName'] . '" value="' . htmlspecialchars($parameterArray['itemFormElValue']) . '" />';
			$html = $this->renderWizards(array($html, $altItem), $config['wizards'], $table, $row, $field, $parameterArray, $parameterArray['itemFormElName'], $specConf);
		}
		$resultArray['html'] = $html;
		return $resultArray;
	}

	/**
	 * Renders the tree
	 *
	 * @param string $table The table name of the record
	 * @param string $field The field name which this element is supposed to edit
	 * @param array $row The record data array where the value(s) for the field can be found
	 * @param array $PA An array with additional configuration options.
	 * @param array $config (Redundant) content of $PA['fieldConf']['config'] (for convenience)
	 * @param array $possibleSelectboxItems Items available for selection
	 * @return string The HTML code for the TCEform field
	 */
	protected function renderField($table, $field, $row, &$PA, $config, $possibleSelectboxItems) {
		$backendUserAuthentication = $this->getBackendUserAuthentication();
		$valueArray = array();
		$selectedNodes = array();
		if (!empty($PA['itemFormElValue'])) {
			$valueArray = explode(',', $PA['itemFormElValue']);
		}
		if (count($valueArray)) {
			foreach ($valueArray as $selectedValue) {
				$temp = explode('|', $selectedValue);
				$selectedNodes[] = $temp[0];
			}
		}
		$allowedUids = array();
		foreach ($possibleSelectboxItems as $item) {
			if ((int)$item[1] > 0) {
				$allowedUids[] = $item[1];
			}
		}
		$treeDataProvider = TreeDataProviderFactory::getDataProvider($config, $table, $field, $row);
		$treeDataProvider->setSelectedList(implode(',', $selectedNodes));
		$treeDataProvider->setItemWhiteList($allowedUids);
		$treeDataProvider->initializeTreeData();
		$treeRenderer = GeneralUtility::makeInstance(ExtJsArrayTreeRenderer::class);
		$tree = GeneralUtility::makeInstance(TableConfigurationTree::class);
		$tree->setDataProvider($treeDataProvider);
		$tree->setNodeRenderer($treeRenderer);
		$treeData = $tree->render();
		$itemArray = array();
		if (is_array($PA['fieldConf']['config']['items'])) {
			foreach ($PA['fieldConf']['config']['items'] as $additionalItem) {
				if ($additionalItem[1] !== '--div--') {
					$item = new \stdClass();
					$item->uid = $additionalItem[1];
					$item->text = $this->getLanguageService()->sL($additionalItem[0]);
					$item->selectable = TRUE;
					$item->leaf = TRUE;
					$item->checked = in_array($additionalItem[1], $selectedNodes);
					if (file_exists(PATH_typo3 . $additionalItem[3])) {
						$item->icon = $additionalItem[3];
					} elseif (trim($additionalItem[3]) !== '') {
						$item->iconCls = IconUtility::getSpriteIconClasses($additionalItem[3]);
					}
					$itemArray[] = $item;
				}
			}
		}
		$itemArray[] = $treeData;
		$treeData = json_encode($itemArray);
		$id = md5($PA['itemFormElName']);
		if (isset($PA['fieldConf']['config']['size']) && (int)$PA['fieldConf']['config']['size'] > 0) {
			$height = (int)$PA['fieldConf']['config']['size'] * 20;
		} else {
			$height = 280;
		}
		$autoSizeMax = NULL;
		if (isset($PA['fieldConf']['config']['autoSizeMax']) && (int)$PA['fieldConf']['config']['autoSizeMax'] > 0) {
			$autoSizeMax = (int)$PA['fieldConf']['config']['autoSizeMax'] * 20;
		}
		$header = FALSE;
		$expanded = FALSE;
		$width = 280;
		$appearance = $PA['fieldConf']['config']['treeConfig']['appearance'];
		if (is_array($appearance)) {
			$header = $appearance['showHeader'] ? TRUE : FALSE;
			$expanded = $appearance['expandAll'] === TRUE;
			if (isset($appearance['width'])) {
				$width = (int)$appearance['width'];
			}
		}
		$onChange = '';
		if ($PA['fieldChangeFunc']['TBE_EDITOR_fieldChanged']) {
			$onChange = $PA['fieldChangeFunc']['TBE_EDITOR_fieldChanged'];
		}
		// Create a JavaScript code line which will ask the user to save/update the form due to changing the element.
		// This is used for eg. "type" fields and others configured with "requestUpdate"
		if (
			!empty($GLOBALS['TCA'][$table]['ctrl']['type'])
			&& $field === $GLOBALS['TCA'][$table]['ctrl']['type']
			|| !empty($GLOBALS['TCA'][$table]['ctrl']['requestUpdate'])
			&& GeneralUtility::inList(str_replace(' ', '', $GLOBALS['TCA'][$table]['ctrl']['requestUpdate']), $field)
		) {
			if ($backendUserAuthentication->jsConfirmation(JsConfirmation::TYPE_CHANGE)) {
				$onChange .= 'if (confirm(TBE_EDITOR.labels.onChangeAlert) && ' . 'TBE_EDITOR.checkSubmit(-1)){ TBE_EDITOR.submitForm() };';
			} else {
				$onChange .= 'if (TBE_EDITOR.checkSubmit(-1)){ TBE_EDITOR.submitForm() };';
			}
		}
		/** @var $pageRenderer \TYPO3\CMS\Core\Page\PageRenderer */
		$pageRenderer = $GLOBALS['SOBE']->doc->getPageRenderer();
		$pageRenderer->loadExtJs();
		$pageRenderer->addJsFile('sysext/backend/Resources/Public/JavaScript/tree.js');
		$pageRenderer->addInlineLanguageLabelFile(ExtensionManagementUtility::extPath('lang') . 'locallang_csh_corebe.xlf', 'tcatree');
		$pageRenderer->addExtOnReadyCode('
			TYPO3.Components.Tree.StandardTreeItemData["' . $id . '"] = ' . $treeData . ';
			var tree' . $id . ' = new TYPO3.Components.Tree.StandardTree({
				id: "' . $id . '",
				showHeader: ' . (int)$header . ',
				onChange: "' . $onChange . '",
				countSelectedNodes: ' . count($selectedNodes) . ',
				width: ' . $width . ',
				listeners: {
					click: function(node, event) {
						if (typeof(node.attributes.checked) == "boolean") {
							node.attributes.checked = ! node.attributes.checked;
							node.getUI().toggleCheck(node.attributes.checked);
						}
					},
					dblclick: function(node, event) {
						if (typeof(node.attributes.checked) == "boolean") {
							node.attributes.checked = ! node.attributes.checked;
							node.getUI().toggleCheck(node.attributes.checked);
						}
					},
					checkchange: TYPO3.Components.Tree.TcaCheckChangeHandler,
					collapsenode: function(node) {
						if (node.id !== "root") {
							top.TYPO3.Storage.Persistent.removeFromList("tcaTrees." + this.ucId, node.attributes.uid);
						}
					},
					expandnode: function(node) {
						if (node.id !== "root") {
							top.TYPO3.Storage.Persistent.addToList("tcaTrees." + this.ucId, node.attributes.uid);
						}
					},
					beforerender: function(treeCmp) {
						// Check if that tree element is already rendered. It is appended on the first tceforms_inline call.
						if (Ext.fly(treeCmp.getId())) {
							return false;
						}
					}' . ($expanded ? ',
					afterrender: function(treeCmp) {
						treeCmp.expandAll();
					}' : '') . '
				},
				tcaMaxItems: ' . ($PA['fieldConf']['config']['maxitems'] ? (int)$PA['fieldConf']['config']['maxitems'] : 99999) . ',
				tcaSelectRecursiveAllowed: ' . ($appearance['allowRecursiveMode'] ? 'true' : 'false') . ',
				tcaSelectRecursive: false,
				tcaExclusiveKeys: "' . ($PA['fieldConf']['config']['exclusiveKeys'] ? $PA['fieldConf']['config']['exclusiveKeys'] : '') . '",
				ucId: "' . md5(($table . '|' . $field)) . '",
				selModel: TYPO3.Components.Tree.EmptySelectionModel,
				disabled: ' . ($PA['fieldConf']['config']['readOnly'] || $this->isGlobalReadonly() ? 'true' : 'false') . '
			});' . LF .
			($autoSizeMax
				? 'tree' . $id . '.bodyStyle = "max-height: ' . $autoSizeMax . 'px;min-height: ' . $height . 'px;";'
				: 'tree' . $id . '.height = ' . $height . ';'
			) . LF .
			'(function() {
					tree' . $id . '.render("tree_' . $id . '");
				}).defer(20);
		');
		$formField = '
			<div class="typo3-tceforms-tree">
				<input class="treeRecord" type="hidden" name="' . htmlspecialchars($PA['itemFormElName']) . '" id="treeinput' . $id . '" value="' . htmlspecialchars($PA['itemFormElValue']) . '" />
			</div>
			<div id="tree_' . $id . '">

			</div>';
		return $formField;
	}

	/**
	 * @return LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

	/**
	 * @return BackendUserAuthentication
	 */
	protected function getBackendUserAuthentication() {
		return $GLOBALS['BE_USER'];
	}

}
