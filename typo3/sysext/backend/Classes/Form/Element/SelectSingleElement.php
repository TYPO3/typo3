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
use TYPO3\CMS\Core\Utility\MathUtility;
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
	 * @var array Result array given returned by render() - This property is a helper until class is properly refactored
	 */
	protected $resultArray = array();

	/**
	 * Render single element
	 *
	 * @return array As defined in initializeResultArray() of AbstractNode
	 */
	public function render() {
		$table = $this->globalOptions['table'];
		$field = $this->globalOptions['fieldName'];
		$row = $this->globalOptions['databaseRow'];
		$parameterArray = $this->globalOptions['parameterArray'];
		$config = $parameterArray['fieldConf']['config'];

		$disabled = '';
		if ($this->isGlobalReadonly() || $config['readOnly']) {
			$disabled = ' disabled="disabled"';
		}

		$this->resultArray = $this->initializeResultArray();

		// "Extra" configuration; Returns configuration for the field based on settings found in the "types" fieldlist.
		$specConf = BackendUtility::getSpecConfParts($parameterArray['extra'], $parameterArray['fieldConf']['defaultExtras']);
		$selItems = FormEngineUtility::getSelectItems($table, $field, $row, $parameterArray);

		// Creating the label for the "No Matching Value" entry.
		$noMatchingLabel = isset($parameterArray['fieldTSConfig']['noMatchingValue_label'])
			? $this->getLanguageService()->sL($parameterArray['fieldTSConfig']['noMatchingValue_label'])
			: '[ ' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.noMatchingValue') . ' ]';

		$html = $this->getSingleField_typeSelect_single($table, $field, $row, $parameterArray, $config, $selItems, $noMatchingLabel);

		// Wizards:
		if (!$disabled) {
			$altItem = '<input type="hidden" name="' . $parameterArray['itemFormElName'] . '" value="' . htmlspecialchars($parameterArray['itemFormElValue']) . '" />';
			$html = $this->renderWizards(array($html, $altItem), $config['wizards'], $table, $row, $field, $parameterArray, $parameterArray['itemFormElName'], $specConf);
		}
		$this->resultArray['html'] = $html;
		return $this->resultArray;
	}

	/**
	 * Creates a single-selector box
	 *
	 * @param string $table See getSingleField_typeSelect()
	 * @param string $field See getSingleField_typeSelect()
	 * @param array $row See getSingleField_typeSelect()
	 * @param array $parameterArray See getSingleField_typeSelect()
	 * @param array $config (Redundant) content of $PA['fieldConf']['config'] (for convenience)
	 * @param array $selectItems Items available for selection
	 * @param string $noMatchingLabel Label for no-matching-value
	 * @return string The HTML code for the item
	 */
	protected function getSingleField_typeSelect_single($table, $field, $row, $parameterArray, $config, $selectItems, $noMatchingLabel) {
		// Check against inline uniqueness
		/** @var InlineStackProcessor $inlineStackProcessor */
		$inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
		$inlineStackProcessor->initializeByGivenStructure($this->globalOptions['inlineStructure']);
		$inlineParent = $inlineStackProcessor->getStructureLevel(-1);
		$uniqueIds = NULL;
		if (is_array($inlineParent) && $inlineParent['uid']) {
			$inlineObjectName = $inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->globalOptions['inlineFirstPid']);
			$inlineFormName = $inlineStackProcessor->getCurrentStructureFormPrefix();
			if ($inlineParent['config']['foreign_table'] == $table && $inlineParent['config']['foreign_unique'] == $field) {
				$uniqueIds = $this->globalOptions['inlineData']['unique'][$inlineObjectName . '-' . $table]['used'];
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
			$this->resultArray['requiredFields'][$table . '_' . $row['uid'] . '_' . $field] = $parameterArray['itemFormElName'];
			$tabAndInlineStack = $this->globalOptions['tabAndInlineStack'];
			if (!empty($tabAndInlineStack) && preg_match('/^(.+\\])\\[(\\w+)\\]$/', $parameterArray['itemFormElName'], $match)) {
				array_shift($match);
				$this->resultArray['requiredNested'][$parameterArray['itemFormElName']] = array(
					'parts' => $match,
					'level' => $tabAndInlineStack,
				);
			}
		}

		// Icon configuration:
		if ($config['suppress_icons'] === 'IF_VALUE_FALSE') {
			$suppressIcons = !$parameterArray['itemFormElValue'] ? 1 : 0;
		} elseif ($config['suppress_icons'] === 'ONLY_SELECTED') {
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
				$selected = ((string)$parameterArray['itemFormElValue'] === (string)$item[1] ? 1 : 0);
				if ($selected) {
					$selectedIndex = $selectItemCounter;
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
					$onClick = 'document.editform[' . GeneralUtility::quoteJSvalue($parameterArray['itemFormElName']) . '].selectedIndex=' . $selectItemCounter . ';';
					if ($config['iconsInOptionTags']) {
						$onClick .= 'document.getElementById(\'' . $selectId . '_icon\').innerHTML = '
							. 'document.editform[' . GeneralUtility::quoteJSvalue($parameterArray['itemFormElName']) . ']'
							. '.options[' . $selectItemCounter . '].getAttribute(\'data-icon\'); ';
					}
					$onClick .= implode('', $parameterArray['fieldChangeFunc']);
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
		if ($parameterArray['itemFormElValue'] && $noMatchingValue && !$parameterArray['fieldTSConfig']['disableNoMatchingValueElement'] && !$config['disableNoMatchingValueElement']) {
			$noMatchingLabel = @sprintf($noMatchingLabel, $parameterArray['itemFormElValue']);
			$options = '<option value="' . htmlspecialchars($parameterArray['itemFormElValue']) . '" selected="selected">' . htmlspecialchars($noMatchingLabel) . '</option>';
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
		$sOnChange .= implode('', $parameterArray['fieldChangeFunc']);

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
					. ' name="' . htmlspecialchars($parameterArray['itemFormElName']) . '"'
					. ' class="form-control form-control-adapt"'
					. ($size ? ' size="' . $size . '"' : '')
					. ' onchange="' . htmlspecialchars($sOnChange) . '"'
					. $parameterArray['onFocus']
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
				$out .= '</td>';
			}
			$out .= '</tr></tbody></table></div>';
		}

		return $out;
	}

}
