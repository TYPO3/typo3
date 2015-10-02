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
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;
use TYPO3\CMS\Backend\Form\InlineStackProcessor;

/**
 * Creates a widget where only one item can be selected.
 * This is either a select drop-down if no size config is given or set to 1, or a select box.
 *
 * This is rendered for type=select, maxitems=1
 */
class SelectSingleElement extends AbstractFormElement {

	/**
	 * Render single element
	 *
	 * @return array As defined in initializeResultArray() of AbstractNode
	 */
	public function render() {
		$table = $this->data['tableName'];
		$field = $this->data['fieldName'];
		$row = $this->data['databaseRow'];
		$parameterArray = $this->data['parameterArray'];
		$config = $parameterArray['fieldConf']['config'];

		$selectItems = $parameterArray['fieldConf']['config']['items'];

		// Creating the label for the "No Matching Value" entry.
		$noMatchingLabel = isset($parameterArray['fieldTSConfig']['noMatchingValue_label'])
			? $this->getLanguageService()->sL($parameterArray['fieldTSConfig']['noMatchingValue_label'])
			: '[ ' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.noMatchingValue') . ' ]';

		// Check against inline uniqueness
		/** @var InlineStackProcessor $inlineStackProcessor */
		$inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
		$inlineStackProcessor->initializeByGivenStructure($this->data['inlineStructure']);
		$inlineParent = $inlineStackProcessor->getStructureLevel(-1);
		$uniqueIds = NULL;
		if (is_array($inlineParent) && $inlineParent['uid']) {
			$inlineObjectName = $inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->data['inlineFirstPid']);
			$inlineFormName = $inlineStackProcessor->getCurrentStructureFormPrefix();
			if ($inlineParent['config']['foreign_table'] == $table && $inlineParent['config']['foreign_unique'] == $field) {
				$uniqueIds = $this->data['inlineData']['unique'][$inlineObjectName . '-' . $table]['used'];
				$parameterArray['fieldChangeFunc']['inlineUnique'] = 'inline.updateUnique(this,'
					. GeneralUtility::quoteJSvalue($inlineObjectName . '-' . $table) . ','
					. GeneralUtility::quoteJSvalue($inlineFormName) . ','
					. GeneralUtility::quoteJSvalue($row['uid']) . ');';
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
		$selectId = StringUtility::getUniqueId('tceforms-select-');
		$selectedIndex = 0;
		$selectedIcon = '';
		$selectedValueFound = FALSE;
		$onlySelectedIconShown = FALSE;
		$size = (int)$config['size'];

		// Style set on <select/>
		$options = '';
		$disabled = FALSE;
		if (!empty($config['readOnly'])) {
			$disabled = TRUE;
			$onlySelectedIconShown = TRUE;
		}

		// Icon configuration:
		if ($config['suppress_icons'] === 'IF_VALUE_FALSE') {
			$suppressIcons = empty($parameterArray['itemFormElValue']);
		} elseif ($config['suppress_icons'] === 'ONLY_SELECTED') {
			$suppressIcons = FALSE;
			$onlySelectedIconShown = TRUE;
		} elseif ($config['suppress_icons']) {
			$suppressIcons = TRUE;
		} else {
			$suppressIcons = FALSE;
		}

		// Prepare groups
		$selectItemCounter = 0;
		$selectItemGroupCount = 0;
		$selectItemGroups = array();
		$selectIcons = array();
		$selectedValue = '';
		$hasIcons = FALSE;

		if (!empty($parameterArray['itemFormElValue'])) {
			$selectedValue = (string)$parameterArray['itemFormElValue'][0];
		}

		foreach ($selectItems as $item) {
			if ($item[1] === '--div--') {
				// IS OPTGROUP
				if ($selectItemCounter !== 0) {
					$selectItemGroupCount++;
				}
				$selectItemGroups[$selectItemGroupCount]['header'] = array(
					'title' => $item[0],
				);
			} else {
				// IS ITEM
				$title = htmlspecialchars($item['0'], ENT_COMPAT, 'UTF-8', FALSE);
				$icon = !empty($item[2]) ? FormEngineUtility::getIconHtml($item[2], $title, $title) : '';
				$selected = $selectedValue === (string)$item[1];

				if ($selected) {
					$selectedIndex = $selectItemCounter;
					$selectedIcon = $icon;
					$selectedValueFound = TRUE;
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
					$selectIcons[] = array(
						'title' => $title,
						'icon' => $icon,
						'index' => $selectItemCounter,
					);
				}

				$selectItemCounter++;
			}

		}

		// No-matching-value:
		if ($selectedValue && !$selectedValueFound && !$parameterArray['fieldTSConfig']['disableNoMatchingValueElement'] && !$config['disableNoMatchingValueElement']) {
			$noMatchingLabel = @sprintf($noMatchingLabel, $selectedValue);
			$options = '<option value="' . htmlspecialchars($selectedValue) . '" selected="selected">' . htmlspecialchars($noMatchingLabel) . '</option>';
		} elseif (!$selectedIcon && $selectItemGroups[0]['items'][0]['icon']) {
			$selectedIcon = $selectItemGroups[0]['items'][0]['icon'];
		}

		// Process groups
		foreach ($selectItemGroups as $selectItemGroup) {
			// suppress groups without items
			if (empty($selectItemGroup['items'])) {
				continue;
			}

			$optionGroup = is_array($selectItemGroup['header']);
			$options .= ($optionGroup ? '<optgroup label="' . htmlspecialchars($selectItemGroup['header']['title'], ENT_COMPAT, 'UTF-8', FALSE) . '">' : '');

			if (is_array($selectItemGroup['items'])) {
				foreach ($selectItemGroup['items'] as $item) {
					$options .= '<option value="' . htmlspecialchars($item['value']) . '" data-icon="' .
						htmlspecialchars($item['icon']) . '"'
						. ($item['selected'] ? ' selected="selected"' : '') . '>' . $item['title'] . '</option>';
				}
				$hasIcons = !empty($item['icon']);
			}

			$options .= ($optionGroup ? '</optgroup>' : '');
		}

		// Build the element
		$html = ['<div class="form-control-wrap">'];

		if ($hasIcons) {
			$html[] = '<div class="input-group">';
			$html[] = 	'<span class="input-group-addon input-group-icon">';
			$html[] = 		$selectedIcon;
			$html[] = 	'</span>';
		}

		$html[] = '<select'
					. ' id="' . $selectId . '"'
					. ' name="' . htmlspecialchars($parameterArray['itemFormElName']) . '"'
					. $this->getValidationDataAsDataAttribute($config)
					. ' class="form-control form-control-adapt"'
					. ($size ? ' size="' . $size . '"' : '')
					. ($disabled ? ' disabled="disabled"' : '')
					. '>';
		$html[] = 	$options;
		$html[] = '</select>';

		if ($hasIcons) {
			$html[] = '</div>';
		}

		$html[] = '</div>';

		// Create icon table:
		if (!empty($selectIcons) && !$config['noIconsBelowSelect']) {
			$selectIconColumns = (int)$config['selicon_cols'];

			if (!$selectIconColumns) {
				$selectIconColumns = count($selectIcons);
			}

			$selectIconColumns = ($selectIconColumns > 12 ? 12 : $selectIconColumns);
			$selectIconRows = ceil(count($selectIcons) / $selectIconColumns);
			$selectIcons = array_pad($selectIcons, $selectIconRows * $selectIconColumns, '');

			$html[] = '<div class="t3js-forms-select-single-icons table-icons table-fit table-fit-inline-block">';
			$html[] = 	'<table class="table table-condensed table-white table-center">';
			$html[] = 		'<tbody>';
			$html[] = 			'<tr>';

			foreach ($selectIcons as $i => $selectIcon) {
				if ($i % $selectIconColumns === 0 && $i !== 0) {
					$html[] = 	'</tr>';
					$html[] = 	'<tr>';
				}

				$html[] = 			'<td>';

				if (is_array($selectIcon)) {
					$html[] = (!$onlySelectedIconShown ? '<a href="#" title="' . $selectIcon['title'] . '" data-select-index="' . $selectIcon['index'] . '">' : '');
					$html[] = $selectIcon['icon'];
					$html[] = (!$onlySelectedIconShown ? '</a>' : '');
				}

				$html[] = 			'</td>';
			}

			$html[] = 			'</tr>';
			$html[] = 		'</tbody>';
			$html[] = 	'</table>';
			$html[] = '</div>';
		}

		$html = implode(LF, $html);

		// Wizards:
		if (!$disabled) {
			$html = $this->renderWizards(
				array($html),
				$config['wizards'],
				$table,
				$row,
				$field,
				$parameterArray,
				$parameterArray['itemFormElName'],
				BackendUtility::getSpecConfParts($parameterArray['fieldConf']['defaultExtras'])
			);
		}

		$resultArray = $this->initializeResultArray();
		$resultArray['html'] = $html;
		$resultArray['requireJsModules'][] = ['TYPO3/CMS/Backend/FormEngine/Element/SelectSingleElement' => implode(LF, [
			'function(SelectSingleElement) {',
				'SelectSingleElement.initialize(',
					GeneralUtility::quoteJSvalue('#' . $selectId) . ',',
					'{',
						'onChange: function() {',
							implode('', $parameterArray['fieldChangeFunc']),
						'},',
						'onFocus: function() {',
							$parameterArray['onFocus'],
						'},',
					'}',
				');',
			'}',
		])];

		return $resultArray;
	}
}
