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

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Form\DataPreprocessor;
use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;

/**
 * Generation of TCEform elements of the type "select"
 */
class SelectElement extends AbstractFormElement {

	/**
	 * If this value is set during traversal and the traversal chain can
	 * not be walked to the end this value will be returned instead.
	 *
	 * @var string
	 */
	protected $alternativeFieldValue;

	/**
	 * If this is TRUE the alternative field value will be used even if
	 * the detected field value is not empty.
	 *
	 * @var bool
	 */
	protected $forceAlternativeFieldValueUse = FALSE;

	/**
	 * The row data of the record that is currently traversed.
	 *
	 * @var array
	 */
	protected $currentRow;

	/**
	 * Name of the table that is currently traversed.
	 *
	 * @var string
	 */
	protected $currentTable;

	/**
	 * This will render a selector box element, or possibly a special construction with two selector boxes.
	 * That depends on configuration.
	 *
	 * @param string $table The table name of the record
	 * @param string $field The field name which this element is supposed to edit
	 * @param array $row The record data array where the value(s) for the field can be found
	 * @param array $additionalInformation An array with additional configuration options.
	 * @return string The HTML code for the TCEform field
	 */
	public function render($table, $field, $row, &$additionalInformation) {
		// Field configuration from TCA:
		$config = $additionalInformation['fieldConf']['config'];
		$disabled = '';
		if ($this->isGlobalReadonly() || $config['readOnly']) {
			$disabled = ' disabled="disabled"';
		}
		// "Extra" configuration; Returns configuration for the field based on settings found in the "types" fieldlist.
		$specConf = BackendUtility::getSpecConfParts($additionalInformation['extra'], $additionalInformation['fieldConf']['defaultExtras']);
		$selItems = $this->getSelectItems($table, $field, $row, $additionalInformation);

		// Creating the label for the "No Matching Value" entry.
		$nMV_label = isset($additionalInformation['fieldTSConfig']['noMatchingValue_label'])
			? $this->getLanguageService()->sL($additionalInformation['fieldTSConfig']['noMatchingValue_label'])
			: '[ ' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.noMatchingValue') . ' ]';
		// Prepare some values:
		$maxitems = (int)$config['maxitems'];
		// If a SINGLE selector box...
		if ($maxitems <= 1 && $config['renderMode'] !== 'tree') {
			$item = $this->getSingleField_typeSelect_single($table, $field, $row, $additionalInformation, $config, $selItems, $nMV_label);
		} elseif ($config['renderMode'] === 'checkbox') {
			// Checkbox renderMode
			$item = $this->getSingleField_typeSelect_checkbox($table, $field, $row, $additionalInformation, $config, $selItems, $nMV_label);
		} elseif ($config['renderMode'] === 'singlebox') {
			// Single selector box renderMode
			$item = $this->getSingleField_typeSelect_singlebox($table, $field, $row, $additionalInformation, $config, $selItems, $nMV_label);
		} elseif ($config['renderMode'] === 'tree') {
			// Tree renderMode
			$treeClass = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Form\Element\TreeElement::class, $this->formEngine);
			$item = $treeClass->renderField($table, $field, $row, $additionalInformation, $config, $selItems, $nMV_label);
			// Register the required number of elements
			$minitems = MathUtility::forceIntegerInRange($config['minitems'], 0);
			$this->formEngine->registerRequiredProperty('range', $additionalInformation['itemFormElName'], array($minitems, $maxitems, 'imgName' => $table . '_' . $row['uid'] . '_' . $field));
		} else {
			// Traditional multiple selector box:
			$item = $this->getSingleField_typeSelect_multiple($table, $field, $row, $additionalInformation, $config, $selItems, $nMV_label);
		}
		// Wizards:
		if (!$disabled) {
			$altItem = '<input type="hidden" name="' . $additionalInformation['itemFormElName'] . '" value="' . htmlspecialchars($additionalInformation['itemFormElValue']) . '" />';
			$item = $this->renderWizards(array($item, $altItem), $config['wizards'], $table, $row, $field, $additionalInformation, $additionalInformation['itemFormElName'], $specConf);
		}
		return $item;
	}

	/**
	 * Creates a multiple-selector box (two boxes, side-by-side)
	 * (Render function for getSingleField_typeSelect())
	 *
	 * @param string $table See getSingleField_typeSelect()
	 * @param string $field See getSingleField_typeSelect()
	 * @param array $row See getSingleField_typeSelect()
	 * @param array $PA See getSingleField_typeSelect()
	 * @param array $config (Redundant) content of $PA['fieldConf']['config'] (for convenience)
	 * @param array $selItems Items available for selection
	 * @param string $nMV_label Label for no-matching-value
	 * @return string The HTML code for the item
	 * @see getSingleField_typeSelect()
	 */
	public function getSingleField_typeSelect_multiple($table, $field, $row, &$PA, $config, $selItems, $nMV_label) {
		$languageService = $this->getLanguageService();
		$item = '';
		$disabled = '';
		if ($this->isGlobalReadonly() || $config['readOnly']) {
			$disabled = ' disabled="disabled"';
		}
		// Setting this hidden field (as a flag that JavaScript can read out)
		if (!$disabled) {
			$item .= '<input type="hidden" name="' . $PA['itemFormElName'] . '_mul" value="' . ($config['multiple'] ? 1 : 0) . '" />';
		}
		// Set max and min items:
		$maxitems = MathUtility::forceIntegerInRange($config['maxitems'], 0);
		if (!$maxitems) {
			$maxitems = 100000;
		}
		$minitems = MathUtility::forceIntegerInRange($config['minitems'], 0);
		// Register the required number of elements:
		$this->formEngine->registerRequiredProperty('range', $PA['itemFormElName'], array($minitems, $maxitems, 'imgName' => $table . '_' . $row['uid'] . '_' . $field));
		// Get "removeItems":
		$removeItems = GeneralUtility::trimExplode(',', $PA['fieldTSConfig']['removeItems'], TRUE);
		// Get the array with selected items:
		$itemArray = GeneralUtility::trimExplode(',', $PA['itemFormElValue'], TRUE);

		// Possibly filter some items:
		$itemArray = ArrayUtility::keepItemsInArray(
			$itemArray,
			$PA['fieldTSConfig']['keepItems'],
			function ($value) {
				$parts = explode('|', $value, 2);
				return rawurldecode($parts[0]);
			}
		);

		// Perform modification of the selected items array:
		foreach ($itemArray as $tk => $tv) {
			$tvP = explode('|', $tv, 2);
			$evalValue = $tvP[0];
			$isRemoved = in_array($evalValue, $removeItems)
				|| $config['form_type'] == 'select' && $config['authMode']
				&& !$this->getBackendUserAuthentication()->checkAuthMode($table, $field, $evalValue, $config['authMode']);
			if ($isRemoved && !$PA['fieldTSConfig']['disableNoMatchingValueElement'] && !$config['disableNoMatchingValueElement']) {
				$tvP[1] = rawurlencode(@sprintf($nMV_label, $evalValue));
			} else {
				if (isset($PA['fieldTSConfig']['altLabels.'][$evalValue])) {
					$tvP[1] = rawurlencode($languageService->sL($PA['fieldTSConfig']['altLabels.'][$evalValue]));
				}
				if (isset($PA['fieldTSConfig']['altIcons.'][$evalValue])) {
					$tvP[2] = $PA['fieldTSConfig']['altIcons.'][$evalValue];
				}
			}
			if ($tvP[1] == '') {
				// Case: flexform, default values supplied, no label provided (bug #9795)
				foreach ($selItems as $selItem) {
					if ($selItem[1] == $tvP[0]) {
						$tvP[1] = html_entity_decode($selItem[0]);
						break;
					}
				}
			}
			$itemArray[$tk] = implode('|', $tvP);
		}
		$itemsToSelect = '';
		$filterTextfield = '';
		$filterSelectbox = '';
		$size = 0;
		if (!$disabled) {
			// Create option tags:
			$opt = array();
			$styleAttrValue = '';
			foreach ($selItems as $p) {
				if ($config['iconsInOptionTags']) {
					$styleAttrValue = FormEngineUtility::optionTagStyle($p[2]);
				}
				$opt[] = '<option value="' . htmlspecialchars($p[1]) . '"'
					. ($styleAttrValue ? ' style="' . htmlspecialchars($styleAttrValue) . '"' : '')
					. ' title="' . $p[0] . '">' . $p[0] . '</option>';
			}
			// Put together the selector box:
			$selector_itemListStyle = isset($config['itemListStyle'])
				? ' style="' . htmlspecialchars($config['itemListStyle']) . '"'
				: '';
			$size = (int)$config['size'];
			$size = $config['autoSizeMax']
				? MathUtility::forceIntegerInRange(count($itemArray) + 1, MathUtility::forceIntegerInRange($size, 1), $config['autoSizeMax'])
				: $size;
			$sOnChange = implode('', $PA['fieldChangeFunc']);

			$multiSelectId = str_replace('.', '', uniqid('tceforms-multiselect-', TRUE));
			$itemsToSelect = '
				<select data-relatedfieldname="' . htmlspecialchars($PA['itemFormElName']) . '" data-exclusivevalues="'
				. htmlspecialchars($config['exclusiveKeys']) . '" id="' . $multiSelectId . '" name="' . $PA['itemFormElName'] . '_sel" '
				. ' class="form-control t3js-formengine-select-itemstoselect" '
				. ($size ? ' size="' . $size . '"' : '') . ' onchange="' . htmlspecialchars($sOnChange) . '"'
				. $PA['onFocus'] . $selector_itemListStyle . '>
					' . implode('
					', $opt) . '
				</select>';

			// enable filter functionality via a text field
			if ($config['enableMultiSelectFilterTextfield']) {
				$filterTextfield = '
					<span class="input-group input-group-sm">
						<span class="input-group-addon">
							<span class="fa fa-filter"></span>
						</span>
						<input class="t3js-formengine-multiselect-filter-textfield form-control" value="" />
					</span>';
			}

			// enable filter functionality via a select
			if (isset($config['multiSelectFilterItems']) && is_array($config['multiSelectFilterItems']) && count($config['multiSelectFilterItems']) > 1) {
				$filterDropDownOptions = array();
				foreach ($config['multiSelectFilterItems'] as $optionElement) {
					$optionValue = $languageService->sL(isset($optionElement[1]) && $optionElement[1] != '' ? $optionElement[1]
						: $optionElement[0]);
					$filterDropDownOptions[] = '<option value="' . htmlspecialchars($languageService->sL($optionElement[0])) . '">'
						. htmlspecialchars($optionValue) . '</option>';
				}
				$filterSelectbox = '<select class="form-control input-sm t3js-formengine-multiselect-filter-dropdown">
						' . implode('
						', $filterDropDownOptions) . '
					</select>';
			}
		}

		if (!empty(trim($filterSelectbox)) && !empty(trim($filterTextfield))) {
			$filterSelectbox = '<div class="form-multigroup-item form-multigroup-element">' . $filterSelectbox . '</div>';
			$filterTextfield = '<div class="form-multigroup-item form-multigroup-element">' . $filterTextfield . '</div>';
			$selectBoxFilterContents = '<div class="t3js-formengine-multiselect-filter-container form-multigroup-wrap">' . $filterSelectbox . $filterTextfield . '</div>';
		} else {
			$selectBoxFilterContents = trim($filterSelectbox . ' ' . $filterTextfield);
		}

		// Pass to "dbFileIcons" function:
		$params = array(
			'size' => $size,
			'autoSizeMax' => MathUtility::forceIntegerInRange($config['autoSizeMax'], 0),
			'style' => isset($config['selectedListStyle'])
				? ' style="' . htmlspecialchars($config['selectedListStyle']) . '"'
				: '',
			'dontShowMoveIcons' => $maxitems <= 1,
			'maxitems' => $maxitems,
			'info' => '',
			'headers' => array(
				'selector' => $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.selected'),
				'items' => $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.items'),
				'selectorbox' => $selectBoxFilterContents,
			),
			'noBrowser' => 1,
			'rightbox' => $itemsToSelect,
			'readOnly' => $disabled
		);
		$item .= $this->dbFileIcons($PA['itemFormElName'], '', '', $itemArray, '', $params, $PA['onFocus']);
		return $item;
	}

	/**
	 * Collects the items for a select field by reading the configured
	 * select items from the configuration and / or by collecting them
	 * from a foreign table.
	 *
	 * @param string $table The table name of the record
	 * @param string $fieldName The select field name
	 * @param array $row The record data array where the value(s) for the field can be found
	 * @param array $PA An array with additional configuration options.
	 * @return array
	 */
	protected function getSelectItems($table, $fieldName, array $row, array $PA) {
		$config = $PA['fieldConf']['config'];

		// Getting the selector box items from the system
		$selectItems = FormEngineUtility::addSelectOptionsToItemArray(
			FormEngineUtility::initItemArray($PA['fieldConf']),
			$PA['fieldConf'],
			FormEngineUtility::getTSconfigForTableRow($table, $row),
			$fieldName
		);

		// Possibly filter some items:
		$selectItems = ArrayUtility::keepItemsInArray(
			$selectItems,
			$PA['fieldTSConfig']['keepItems'],
			function ($value) {
				return $value[1];
			}
		);

		// Possibly add some items:
		$selectItems = FormEngineUtility::addItems($selectItems, $PA['fieldTSConfig']['addItems.']);

		// Process items by a user function:
		if (isset($config['itemsProcFunc']) && $config['itemsProcFunc']) {
			$dataPreprocessor = GeneralUtility::makeInstance(DataPreprocessor::class);
			$selectItems = $dataPreprocessor->procItems($selectItems, $PA['fieldTSConfig']['itemsProcFunc.'], $config, $table, $row, $fieldName);
		}

		// Possibly remove some items:
		$removeItems = GeneralUtility::trimExplode(',', $PA['fieldTSConfig']['removeItems'], TRUE);
		foreach ($selectItems as $selectItemIndex => $selectItem) {

			// Checking languages and authMode:
			$languageDeny = FALSE;
			$beUserAuth = $this->getBackendUserAuthentication();
			if (
				!empty($GLOBALS['TCA'][$table]['ctrl']['languageField'])
				&& $GLOBALS['TCA'][$table]['ctrl']['languageField'] === $fieldName
				&& !$beUserAuth->checkLanguageAccess($selectItem[1])
			) {
				$languageDeny = TRUE;
			}

			$authModeDeny = FALSE;
			if (
				($config['form_type'] === 'select')
				&& $config['authMode']
				&& !$beUserAuth->checkAuthMode($table, $fieldName, $selectItem[1], $config['authMode'])
			) {
				$authModeDeny = TRUE;
			}

			if (in_array($selectItem[1], $removeItems) || $languageDeny || $authModeDeny) {
				unset($selectItems[$selectItemIndex]);
			} elseif (isset($PA['fieldTSConfig']['altLabels.'][$selectItem[1]])) {
				$selectItems[$selectItemIndex][0] = htmlspecialchars($this->getLanguageService()->sL($PA['fieldTSConfig']['altLabels.'][$selectItem[1]]));
			}

			// Removing doktypes with no access:
			if (($table === 'pages' || $table === 'pages_language_overlay') && $fieldName === 'doktype') {
				if (!($beUserAuth->isAdmin() || GeneralUtility::inList($beUserAuth->groupData['pagetypes_select'], $selectItem[1]))) {
					unset($selectItems[$selectItemIndex]);
				}
			}
		}

		return $selectItems;
	}

	/**
	 * Creates a single-selector box
	 * (Render function for getSingleField_typeSelect())
	 *
	 * @param string $table See getSingleField_typeSelect()
	 * @param string $field See getSingleField_typeSelect()
	 * @param array $row See getSingleField_typeSelect()
	 * @param array $PA See getSingleField_typeSelect()
	 * @param array $config (Redundant) content of $PA['fieldConf']['config'] (for convenience)
	 * @param array $selectItems Items available for selection
	 * @param string $noMatchingLabel Label for no-matching-value
	 * @return string The HTML code for the item
	 * @see getSingleField_typeSelect()
	 */
	public function getSingleField_typeSelect_single($table, $field, $row, &$PA, $config, $selectItems, $noMatchingLabel) {

		// check against inline uniqueness
		$inlineParent = $this->formEngine->inline->getStructureLevel(-1);
		$uniqueIds = NULL;
		if (is_array($inlineParent) && $inlineParent['uid']) {
			if ($inlineParent['config']['foreign_table'] == $table && $inlineParent['config']['foreign_unique'] == $field) {
				$uniqueIds = $this->formEngine->inline->inlineData['unique'][$this->formEngine->inline->inlineNames['object']
				. InlineElement::Structure_Separator . $table]['used'];
				$PA['fieldChangeFunc']['inlineUnique'] = 'inline.updateUnique(this,\'' . $this->formEngine->inline->inlineNames['object']
					. InlineElement::Structure_Separator . $table . '\',\'' . $this->formEngine->inline->inlineNames['form']
					. '\',\'' . $row['uid'] . '\');';
			}
			// hide uid of parent record for symmetric relations
			if (
				$inlineParent['config']['foreign_table'] == $table
				&& ($inlineParent['config']['foreign_field'] == $field || $inlineParent['config']['symmetric_field'] == $field)
			) {
				$uniqueIds[] = $inlineParent['uid'];
			}
		}

		// Initialization:
		$selectId = str_replace('.', '', uniqid('tceforms-select-', TRUE));
		$selectedIndex = 0;
		$selectedIcon = '';
		$noMatchingValue = 1;
		$onlySelectedIconShown = 0;
		$size = (int)$config['size'];

		// Style set on <select/>
		$out = '';
		$options = '';
		$disabled = FALSE;
		if ($this->isGlobalReadonly() || $config['readOnly']) {
			$disabled = TRUE;
			$onlySelectedIconShown = 1;
		}
		// Register as required if minitems is greater than zero
		if (($minItems = MathUtility::forceIntegerInRange($config['minitems'], 0)) > 0) {
			$this->formEngine->registerRequiredProperty('field', $table . '_' . $row['uid'] . '_' . $field, $PA['itemFormElName']);
		}

		// Icon configuration:
		if ($config['suppress_icons'] == 'IF_VALUE_FALSE') {
			$suppressIcons = !$PA['itemFormElValue'] ? 1 : 0;
		} elseif ($config['suppress_icons'] == 'ONLY_SELECTED') {
			$suppressIcons = 0;
			$onlySelectedIconShown = 1;
		} elseif ($config['suppress_icons']) {
			$suppressIcons = 1;
		} else {
			$suppressIcons = 0;
		}

		// Prepare groups
		$selectItemCounter = 0;
		$selectItemGroupCount = 0;
		$selectItemGroups = array();
		$selectIcons = array();
		$selectedValue = '';
		foreach ($selectItems as $item) {
			if ($item[1] === '--div--') {
				// IS OPTGROUP
				if ($selectItemCounter !== 0) {
					$selectItemGroupCount++;
				}
				$selectItemGroups[$selectItemGroupCount]['header'] = array(
					'title' => $item[0],
					'icon' => (!empty($item[2]) ? FormEngineUtility::getIconHtml($item[2]) : ''),
				);
			} else {
				// IS ITEM
				$title = htmlspecialchars($item['0'], ENT_COMPAT, 'UTF-8', FALSE);
				$icon = !empty($item[2]) ? FormEngineUtility::getIconHtml($item[2], $title, $title) : '';
				$selected = ((string)$PA['itemFormElValue'] === (string)$item[1] ? 1 : 0);
				if ($selected) {
					$selectedIndex = $selectItemCounter;
					$selectedValue = $item[1];
					$selectedIcon = $icon;
					$noMatchingValue = 0;
				}
				$selectItemGroups[$selectItemGroupCount]['items'][] = array(
					'title' => $title,
					'value' => $item[1],
					'icon' => $icon,
					'selected' => $selected,
					'index' => $selectItemCounter
				);
				// ICON
				if ($icon && !$suppressIcons && (!$onlySelectedIconShown || $selected)) {
					$onClick = 'document.editform[' . GeneralUtility::quoteJSvalue($PA['itemFormElName']) . '].selectedIndex=' . $selectItemCounter . ';';
					if ($config['iconsInOptionTags']) {
						$onClick .= 'document.getElementById(\'' . $selectId . '_icon\').innerHTML = '
							. 'document.editform[' . GeneralUtility::quoteJSvalue($PA['itemFormElName']) . ']'
							. '.options[' . $selectItemCounter . '].getAttribute(\'data-icon\'); ';
					}
					$onClick .= implode('', $PA['fieldChangeFunc']);
					$onClick .= 'this.blur();return false;';
					$selectIcons[] = array(
						'title' => $title,
						'icon' => $icon,
						'index' => $selectItemCounter,
						'onClick' => $onClick
					);
				}
				$selectItemCounter++;
			}

		}

		// No-matching-value:
		if ($PA['itemFormElValue'] && $noMatchingValue && !$PA['fieldTSConfig']['disableNoMatchingValueElement'] && !$config['disableNoMatchingValueElement']) {
			$noMatchingLabel = @sprintf($noMatchingLabel, $PA['itemFormElValue']);
			$options = '<option value="' . htmlspecialchars($PA['itemFormElValue']) . '" selected="selected">' . htmlspecialchars($noMatchingLabel) . '</option>';
		} elseif (!$selectedIcon && $selectItemGroups[0]['items'][0]['icon']) {
			$selectedIcon = $selectItemGroups[0]['items'][0]['icon'];
		}

		// Process groups
		foreach ($selectItemGroups as $selectItemGroup) {
			$optionGroup = is_array($selectItemGroup['header']);
			$options .= ($optionGroup ? '<optgroup label="' . htmlspecialchars($selectItemGroup['header']['title'], ENT_COMPAT, 'UTF-8', FALSE) . '">' : '');
			if (is_array($selectItemGroup['items'])) {
				foreach ($selectItemGroup['items'] as $item) {
					$options .= '<option value="' . htmlspecialchars($item['value']) . '" data-icon="' .
						htmlspecialchars($item['icon']) . '"'
						. ($item['selected'] ? ' selected="selected"' : '') . '>' . $item['title'] . '</option>';
				}
			}
			$options .= ($optionGroup ? '</optgroup>' : '');
		}

		// Create item form fields:
		$sOnChange = 'if (this.options[this.selectedIndex].value==\'--div--\') {this.selectedIndex=' . $selectedIndex . ';} ';
		if ($config['iconsInOptionTags']) {
			$sOnChange .= 'document.getElementById(\'' . $selectId . '_icon\').innerHTML = this.options[this.selectedIndex].getAttribute(\'data-icon\'); ';
		}
		$sOnChange .= implode('', $PA['fieldChangeFunc']);

		// Add icons in option tags
		$prepend = '';
		$append = '';
		if ($config['iconsInOptionTags']) {
			$prepend = '<div class="input-group"><div id="' . $selectId . '_icon" class="input-group-addon input-group-icon t3js-formengine-select-prepend">' . $selectedIcon . '</div>';
			$append = '</div>';
		}

		// Build the element
		$out .= '
			<div class="form-control-wrap">
				' . $prepend . '
				<select'
					. ' id="' . $selectId . '"'
					. ' name="' . $PA['itemFormElName'] . '"'
					. ' class="form-control form-control-adapt"'
					. ($size ? ' size="' . $size . '"' : '')
					. ' onchange="' . htmlspecialchars($sOnChange) . '"'
					. $PA['onFocus']
					. ($disabled ? ' disabled="disabled"' : '')
					. '>
					' . $options . '
				</select>
				' . $append . '
			</div>';

		// Create icon table:
		if (count($selectIcons) && !$config['noIconsBelowSelect']) {
			$selectIconColumns = (int)$config['selicon_cols'];
			if (!$selectIconColumns) {
				$selectIconColumns = count($selectIcons);
			}
			$selectIconColumns = ($selectIconColumns > 12 ? 12 : $selectIconColumns);
			$selectIconRows = ceil(count($selectIcons) / $selectIconColumns);
			$selectIcons = array_pad($selectIcons, $selectIconRows * $selectIconColumns, '');
			$out .= '<div class="table-fit table-fit-inline-block"><table class="table table-condensed table-white table-center"><tbody><tr>';
			for ($selectIconCount = 0; $selectIconCount < count($selectIcons); $selectIconCount++) {
				if ($selectIconCount % $selectIconColumns === 0 && $selectIconCount !== 0) {
					$out .= '</tr><tr>';
				}
				$out .= '<td>';
				if (is_array($selectIcons[$selectIconCount])) {
					$out .= (!$onlySelectedIconShown ? '<a href="#" title="' . $selectIcons[$selectIconCount]['title'] . '" onClick="' . htmlspecialchars($selectIcons[$selectIconCount]['onClick']) . '">' : '');
					$out .= $selectIcons[$selectIconCount]['icon'];
					$out .= (!$onlySelectedIconShown ? '</a>' : '');
				}
				$out . '</td>';
			}
			$out .= '</tr></tbody></table></div>';
		}

		return $out;
	}

	/**
	 * Creates a checkbox list (renderMode = "checkbox")
	 * (Render function for getSingleField_typeSelect())
	 *
	 * @param string $table See getSingleField_typeSelect()
	 * @param string $field See getSingleField_typeSelect()
	 * @param array $row See getSingleField_typeSelect()
	 * @param array $PA See getSingleField_typeSelect()
	 * @param array $config (Redundant) content of $PA['fieldConf']['config'] (for convenience)
	 * @param array $selItems Items available for selection
	 * @param string $nMV_label Label for no-matching-value
	 * @return string The HTML code for the item
	 * @see getSingleField_typeSelect()
	 */
	public function getSingleField_typeSelect_checkbox($table, $field, $row, &$PA, $config, $selItems, $nMV_label) {
		if (empty($selItems)) {
			return '';
		}
		// Get values in an array (and make unique, which is fine because there can be no duplicates anyway):
		$itemArray = array_flip(FormEngineUtility::extractValuesOnlyFromValueLabelList($PA['itemFormElValue']));
		$output = '';

		// Disabled
		$disabled = 0;
		if ($this->isGlobalReadonly() || $config['readOnly']) {
			$disabled = 1;
		}
		// Traverse the Array of selector box items:
		$groups = array();
		$currentGroup = 0;
		$c = 0;
		$sOnChange = '';
		if (!$disabled) {
			$sOnChange = implode('', $PA['fieldChangeFunc']);
			// Used to accumulate the JS needed to restore the original selection.
			foreach ($selItems as $p) {
				// Non-selectable element:
				if ($p[1] === '--div--') {
					$selIcon = '';
					if (isset($p[2]) && $p[2] != 'empty-emtpy') {
						$selIcon = FormEngineUtility::getIconHtml($p[2]);
					}
					$currentGroup++;
					$groups[$currentGroup]['header'] = array(
						'icon' => $selIcon,
						'title' => htmlspecialchars($p[0])
					);
				} else {

					// Check if some help text is available
					// Since TYPO3 4.5 help text is expected to be an associative array
					// with two key, "title" and "description"
					// For the sake of backwards compatibility, we test if the help text
					// is a string and use it as a description (this could happen if items
					// are modified with an itemProcFunc)
					$hasHelp = FALSE;
					$help = '';
					$helpArray = array();
					if (is_array($p[3]) && count($p[3]) > 0 || !empty($p[3])) {
						$hasHelp = TRUE;
						if (is_array($p[3])) {
							$helpArray = $p[3];
						} else {
							$helpArray['description'] = $p[3];
						}
					}
					if ($hasHelp) {
						$help = BackendUtility::wrapInHelp('', '', '', $helpArray);
					}

					// Selected or not by default:
					$checked = 0;
					if (isset($itemArray[$p[1]])) {
						$checked = 1;
						unset($itemArray[$p[1]]);
					}

					// Build item array
					$groups[$currentGroup]['items'][] = array(
						'id' => str_replace('.', '', uniqid('select_checkbox_row_', TRUE)),
						'name' => $PA['itemFormElName'] . '[' . $c . ']',
						'value' => $p[1],
						'checked' => $checked,
						'disabled' => $disabled,
						'class' => '',
						'icon' => (!empty($p[2]) ? FormEngineUtility::getIconHtml($p[2]) : IconUtility::getSpriteIcon('empty-empty')),
						'title' => htmlspecialchars($p[0], ENT_COMPAT, 'UTF-8', FALSE),
						'help' => $help
					);
					$c++;
				}
			}
		}
		// Remaining values (invalid):
		if (count($itemArray) && !$PA['fieldTSConfig']['disableNoMatchingValueElement'] && !$config['disableNoMatchingValueElement']) {
			$currentGroup++;
			foreach ($itemArray as $theNoMatchValue => $temp) {
				// Build item array
				$groups[$currentGroup]['items'][] = array(
					'id' => str_replace('.', '', uniqid('select_checkbox_row_', TRUE)),
					'name' => $PA['itemFormElName'] . '[' . $c . ']',
					'value' => $theNoMatchValue,
					'checked' => 1,
					'disabled' => $disabled,
					'class' => 'danger',
					'icon' => '',
					'title' => htmlspecialchars(@sprintf($nMV_label, $theNoMatchValue), ENT_COMPAT, 'UTF-8', FALSE),
					'help' => ''
				);
				$c++;
			}
		}
		// Add an empty hidden field which will send a blank value if all items are unselected.
		$output .= '<input type="hidden" class="select-checkbox" name="' . htmlspecialchars($PA['itemFormElName']) . '" value="" />';

		// Building the checkboxes
		foreach($groups as $groupKey => $group){
			$groupId = htmlspecialchars($PA['itemFormElID']) . '-group-' . $groupKey;
			$output .= '<div class="panel panel-default">';
			if(is_array($group['header'])){
				$output .= '
					<div class="panel-heading">
						<a data-toggle="collapse" href="#' . $groupId . '" aria-expanded="true" aria-controls="' . $groupId . '">
							' . $group['header']['icon'] . '
							' . $group['header']['title'] . '
						</a>
					</div>
					';
			}
			if(is_array($group['items']) && count($group['items']) >= 1){
				$tableRows = '';
				$checkGroup = array();
				$uncheckGroup = array();
				$resetGroup = array();

				// Render rows
				foreach($group['items'] as $item){
					$tableRows .= '
						<tr class="' . $item['class'] . '">
							<td class="col-checkbox">
								<input type="checkbox"
									id="' . $item['id'] . '"
									name="' . htmlspecialchars($item['name']) . '"
									value="' . htmlspecialchars($item['value']) . '"
									onclick="' . htmlspecialchars($sOnChange) . '"
									' . ($item['checked'] ? ' checked=checked' : '') . '
									' . ($item['disabled'] ? ' disabled=disabled' : '') . '
									' . $PA['onFocus'] . ' />
							</td>
							<td class="col-icon">
								<label class="label-block" for="' . $item['id'] . '">' . $item['icon'] . '</label>
							</td>
							<td class="col-title">
								<label class="label-block" for="' . $item['id'] . '">' . $item['title'] . '</label>
							</td>
							<td>' . $item['help'] . '</td>
						</tr>
						';
					$checkGroup[] = 'document.editform[' . GeneralUtility::quoteJSvalue($item['name']) . '].checked=1;';
					$uncheckGroup[] = 'document.editform[' . GeneralUtility::quoteJSvalue($item['name']) . '].checked=0;';
					$resetGroup[] = 'document.editform[' . GeneralUtility::quoteJSvalue($item['name']) . '].checked='.$item['checked'] . ';';
				}

				// Build toggle group checkbox
				$toggleGroupCheckbox = '';
				if(count($resetGroup)){
					$toggleGroupCheckbox = '
						<input type="checkbox" class="checkbox" onclick="if (checked) {' . htmlspecialchars(implode('', $checkGroup) . '} else {' . implode('', $uncheckGroup)) . '}">
						';
				}

				// Build reset group button
				$resetGroupBtn = '';
				if(count($resetGroup)){
					$resetGroupBtn = '
						<a href="#" class="btn btn-default" onclick="' . implode('', $resetGroup) . ' return false;' . '">
							' . IconUtility::getSpriteIcon('actions-edit-undo', array('title' => htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.revertSelection')))) . '
							' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.revertSelection') . '
						</a>
						';
				}

				$output .= '
					<div id="' . $groupId . '" class="panel-collapse collapse in" role="tabpanel">
						<div class="table-fit">
							<table class="table table-transparent table-hover">
								<thead>
									<tr>
										<th class="col-checkbox">' . $toggleGroupCheckbox . '</th>
										<th class="col-icon"></th>
										<th class="text-right" colspan="2">' . $resetGroupBtn . '</th>
									</tr>
								</thead>
								<tbody>' . $tableRows . '</tbody>
							</table>
						</div>
					</div>
					';
			}
			$output .= '</div>';
		}

		return $output;
	}

	/**
	 * Creates a selectorbox list (renderMode = "singlebox")
	 * (Render function for getSingleField_typeSelect())
	 *
	 * @param string $table See getSingleField_typeSelect()
	 * @param string $field See getSingleField_typeSelect()
	 * @param array $row See getSingleField_typeSelect()
	 * @param array $PA See getSingleField_typeSelect()
	 * @param array $config (Redundant) content of $PA['fieldConf']['config'] (for convenience)
	 * @param array $selItems Items available for selection
	 * @param string $nMV_label Label for no-matching-value
	 * @return string The HTML code for the item
	 * @see getSingleField_typeSelect()
	 */
	public function getSingleField_typeSelect_singlebox($table, $field, $row, &$PA, $config, $selItems, $nMV_label) {
		$languageService = $this->getLanguageService();
		// Get values in an array (and make unique, which is fine because there can be no duplicates anyway):
		$itemArray = array_flip(FormEngineUtility::extractValuesOnlyFromValueLabelList($PA['itemFormElValue']));
		$item = '';
		$disabled = '';
		if ($this->isGlobalReadonly() || $config['readOnly']) {
			$disabled = ' disabled="disabled"';
		}
		// Traverse the Array of selector box items:
		$opt = array();
		// Used to accumulate the JS needed to restore the original selection.
		$restoreCmd = array();
		$c = 0;
		foreach ($selItems as $p) {
			// Selected or not by default:
			$sM = '';
			if (isset($itemArray[$p[1]])) {
				$sM = ' selected="selected"';
				$restoreCmd[] = 'document.editform[' . GeneralUtility::quoteJSvalue($PA['itemFormElName'] . '[]') . '].options[' . $c . '].selected=1;';
				unset($itemArray[$p[1]]);
			}
			// Non-selectable element:
			$nonSel = '';
			if ((string)$p[1] === '--div--') {
				$nonSel = ' onclick="this.selected=0;" class="formcontrol-select-divider"';
			}
			// Icon style for option tag:
			$styleAttrValue = '';
			if ($config['iconsInOptionTags']) {
				$styleAttrValue = FormEngineUtility::optionTagStyle($p[2]);
			}
			// Compile <option> tag:
			$opt[] = '<option value="' . htmlspecialchars($p[1]) . '"' . $sM . $nonSel
				. ($styleAttrValue ? ' style="' . htmlspecialchars($styleAttrValue) . '"' : '') . '>'
				. htmlspecialchars($p[0], ENT_COMPAT, 'UTF-8', FALSE) . '</option>';
			$c++;
		}
		// Remaining values:
		if (count($itemArray) && !$PA['fieldTSConfig']['disableNoMatchingValueElement'] && !$config['disableNoMatchingValueElement']) {
			foreach ($itemArray as $theNoMatchValue => $temp) {
				// Compile <option> tag:
				array_unshift($opt, '<option value="' . htmlspecialchars($theNoMatchValue) . '" selected="selected">'
					. htmlspecialchars(@sprintf($nMV_label, $theNoMatchValue), ENT_COMPAT, 'UTF-8', FALSE) . '</option>');
			}
		}
		// Compile selector box:
		$sOnChange = implode('', $PA['fieldChangeFunc']);
		$selector_itemListStyle = isset($config['itemListStyle'])
			? ' style="' . htmlspecialchars($config['itemListStyle']) . '"'
			: '';
		$size = (int)$config['size'];
		$cssPrefix = $size === 1 ? 'tceforms-select' : 'tceforms-multiselect';
		$size = $config['autoSizeMax']
			? MathUtility::forceIntegerInRange(count($selItems) + 1, MathUtility::forceIntegerInRange($size, 1), $config['autoSizeMax'])
			: $size;
		$selectBox = '<select id="' . str_replace('.', '', uniqid($cssPrefix, TRUE)) . '" name="' . $PA['itemFormElName'] . '[]" '
			. 'class="form-control ' . $cssPrefix . '"' . ($size ? ' size="' . $size . '" ' : '')
			. ' multiple="multiple" onchange="' . htmlspecialchars($sOnChange) . '"' . $PA['onFocus']
			. ' ' . $selector_itemListStyle . $disabled . '>
						' . implode('
						', $opt) . '
					</select>';
		// Add an empty hidden field which will send a blank value if all items are unselected.
		if (!$disabled) {
			$item .= '<input type="hidden" name="' . htmlspecialchars($PA['itemFormElName']) . '" value="" />';
		}
		// Put it all into a table:
		$onClick = htmlspecialchars('document.editform[' . GeneralUtility::quoteJSvalue($PA['itemFormElName'] . '[]') . '].selectedIndex=-1;' . implode('', $restoreCmd) . ' return false;');
		$width = $this->formMaxWidth($this->defaultInputWidth);
		$item .= '
			<div class="form-control-wrap" ' . ($width ? ' style="max-width: ' . $width . 'px"' : '') . '>
				<div class="form-wizards-wrap form-wizards-aside">
					<div class="form-wizards-element">
						' . $selectBox . '
					</div>
					<div class="form-wizards-items">
						<a href="#" class="btn btn-default" onclick="' . $onClick . '" title="' . htmlspecialchars($languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.revertSelection')) . '">'
							. IconUtility::getSpriteIcon('actions-edit-undo') . '</a>
					</div>
				</div>
			</div>
			<p>
				<em>' . htmlspecialchars($languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.holdDownCTRL')) . '</em>
			</p>
			';
		return $item;
	}

	/**
	 * If the select field is build by a foreign_table the related UIDs
	 * will be returned.
	 *
	 * Otherwise the label of the currently selected value will be written
	 * to the alternativeFieldValue class property.
	 *
	 * @param array $fieldConfig The "config" section of the TCA for the current select field.
	 * @param string $fieldName The name of the select field.
	 * @param string $value The current value in the local record, usually a comma separated list of selected values.
	 * @return array Array of related UIDs.
	 */
	public function getRelatedSelectFieldUids(array $fieldConfig, $fieldName, $value) {
		$relatedUids = array();

		$isTraversable = FALSE;
		if (isset($fieldConfig['foreign_table'])) {
			$isTraversable = TRUE;
			// if a foreign_table is used we pre-filter the records for performance
			$fieldConfig['foreign_table_where'] .= ' AND ' . $fieldConfig['foreign_table'] . '.uid IN (' . $value . ')';
		}

		$PA = array();
		$PA['fieldConf']['config'] = $fieldConfig;
		$PA['fieldConf']['config']['form_type'] = $PA['fieldConf']['config']['form_type'] ? $PA['fieldConf']['config']['form_type'] : $PA['fieldConf']['config']['type'];
		$PA['fieldTSConfig'] = FormEngineUtility::getTSconfigForTableRow($this->currentTable, $this->currentRow, $fieldName);
		$PA['fieldConf']['config'] = FormEngineUtility::overrideFieldConf($PA['fieldConf']['config'], $PA['fieldTSConfig']);
		$selectItemArray = $this->getSelectItems($this->currentTable, $fieldName, $this->currentRow, $PA);

		if ($isTraversable && count($selectItemArray)) {
			$this->currentTable = $fieldConfig['foreign_table'];
			$relatedUids = $this->getSelectedValuesFromSelectItemArray($selectItemArray, $value);
		} else {
			$selectedLabels = $this->getSelectedValuesFromSelectItemArray($selectItemArray, $value, 1, TRUE);
			if (count($selectedLabels) === 1) {
				$this->alternativeFieldValue = $selectedLabels[0];
				$this->forceAlternativeFieldValueUse = TRUE;
			}
		}

		return $relatedUids;
	}

	/**
	 * Extracts the selected values from a given array of select items.
	 *
	 * @param array $selectItemArray The select item array generated by \TYPO3\CMS\Backend\Form\FormEngine->getSelectItems.
	 * @param string $value The currently selected value(s) as comma separated list.
	 * @param int|NULL $maxItems Optional value, if set processing is skipped and an empty array will be returned when the number of selected values is larger than the provided value.
	 * @param bool $returnLabels If TRUE the select labels will be returned instead of the values.
	 * @return array
	 */
	protected function getSelectedValuesFromSelectItemArray(array $selectItemArray, $value, $maxItems = NULL, $returnLabels = FALSE) {
		$values = GeneralUtility::trimExplode(',', $value);
		$selectedValues = array();

		if ($maxItems !== NULL && (count($values) > (int)$maxItems)) {
			return $selectedValues;
		}

		foreach ($selectItemArray as $selectItem) {
			$selectItemValue = $selectItem[1];
			if (in_array($selectItemValue, $values)) {
				if ($returnLabels) {
					$selectedValues[] = $selectItem[0];
				} else {
					$selectedValues[] = $selectItemValue;
				}
			}
		}

		return $selectedValues;
	}

	/**
	 * @param string $alternativeFieldValue
	 */
	public function setAlternativeFieldValue($alternativeFieldValue) {
		$this->alternativeFieldValue = $alternativeFieldValue;
	}

	/**
	 * @param array $currentRow
	 */
	public function setCurrentRow($currentRow) {
		$this->currentRow = $currentRow;
	}

	/**
	 * @param string $currentTable
	 */
	public function setCurrentTable($currentTable) {
		$this->currentTable = $currentTable;
	}

	/**
	 * @param bool $forceAlternativeFieldValueUse
	 */
	public function setForceAlternativeFieldValueUse($forceAlternativeFieldValueUse) {
		$this->forceAlternativeFieldValueUse = $forceAlternativeFieldValueUse;
	}

	/**
	 * @return string
	 */
	public function getAlternativeFieldValue() {
		return $this->alternativeFieldValue;
	}

	/**
	 * @return array
	 */
	public function getCurrentRow() {
		return $this->currentRow;
	}

	/**
	 * @return string
	 */
	public function getCurrentTable() {
		return $this->currentTable;
	}

	/**
	 * @return boolean
	 */
	public function isForceAlternativeFieldValueUse() {
		return $this->forceAlternativeFieldValueUse;
	}

}
