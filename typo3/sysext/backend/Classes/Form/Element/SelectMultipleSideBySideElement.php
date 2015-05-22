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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * Render a widget with two boxes side by side.
 *
 * This is rendered for config type=select, maxitems > 1, no other renderMode set
 */
class SelectMultipleSideBySideElement extends AbstractFormElement {

	/**
	 * @var array Result array given returned by render() - This property is a helper until class is properly refactored
	 */
	protected $resultArray = array();

	/**
	 * Render side by side element.
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
		$this->resultArray = $this->initializeResultArray();
		// "Extra" configuration; Returns configuration for the field based on settings found in the "types" fieldlist.
		$specConf = BackendUtility::getSpecConfParts($parameterArray['fieldConf']['defaultExtras']);
		$selItems = FormEngineUtility::getSelectItems($table, $field, $row, $parameterArray);

		// Creating the label for the "No Matching Value" entry.
		$noMatchingLabel = isset($parameterArray['fieldTSConfig']['noMatchingValue_label'])
			? $this->getLanguageService()->sL($parameterArray['fieldTSConfig']['noMatchingValue_label'])
			: '[ ' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.noMatchingValue') . ' ]';

		$html = $this->getSingleField_typeSelect_multiple($table, $field, $row, $parameterArray, $config, $selItems, $noMatchingLabel);

		// Wizards:
		if (!$disabled) {
			$altItem = '<input type="hidden" name="' . $parameterArray['itemFormElName'] . '" value="' . htmlspecialchars($parameterArray['itemFormElValue']) . '" />';
			$html = $this->renderWizards(array($html, $altItem), $config['wizards'], $table, $row, $field, $parameterArray, $parameterArray['itemFormElName'], $specConf);
		}
		$this->resultArray['html'] = $html;
		return $this->resultArray;
	}

	/**
	 * Creates a multiple-selector box (two boxes, side-by-side)
	 *
	 * @param string $table See getSingleField_typeSelect()
	 * @param string $field See getSingleField_typeSelect()
	 * @param array $row See getSingleField_typeSelect()
	 * @param array $parameterArray See getSingleField_typeSelect()
	 * @param array $config (Redundant) content of $PA['fieldConf']['config'] (for convenience)
	 * @param array $selItems Items available for selection
	 * @param string $noMatchingLabel Label for no-matching-value
	 * @return string The HTML code for the item
	 */
	protected function getSingleField_typeSelect_multiple($table, $field, $row, $parameterArray, $config, $selItems, $noMatchingLabel) {
		$languageService = $this->getLanguageService();
		$item = '';
		$disabled = '';
		if ($this->isGlobalReadonly() || $config['readOnly']) {
			$disabled = ' disabled="disabled"';
		}
		// Setting this hidden field (as a flag that JavaScript can read out)
		if (!$disabled) {
			$item .= '<input type="hidden" name="' . $parameterArray['itemFormElName'] . '_mul" value="' . ($config['multiple'] ? 1 : 0) . '" />';
		}
		// Set max and min items:
		$maxitems = MathUtility::forceIntegerInRange($config['maxitems'], 0);
		if (!$maxitems) {
			$maxitems = 100000;
		}
		$minitems = MathUtility::forceIntegerInRange($config['minitems'], 0);
		// Register the required number of elements:
		$this->resultArray['requiredElements'][$parameterArray['itemFormElName']] = array(
			$minitems,
			$maxitems,
			'imgName' => $table . '_' . $row['uid'] . '_' . $field
		);
		$tabAndInlineStack = $this->globalOptions['tabAndInlineStack'];
		if (!empty($tabAndInlineStack) && preg_match('/^(.+\\])\\[(\\w+)\\]$/', $parameterArray['itemFormElName'], $match)) {
			array_shift($match);
			$this->resultArray['requiredNested'][$parameterArray['itemFormElName']] = array(
				'parts' => $match,
				'level' => $tabAndInlineStack,
			);
		}
		// Get "removeItems":
		$removeItems = GeneralUtility::trimExplode(',', $parameterArray['fieldTSConfig']['removeItems'], TRUE);
		// Get the array with selected items:
		$itemArray = GeneralUtility::trimExplode(',', $parameterArray['itemFormElValue'], TRUE);

		// Possibly filter some items:
		$itemArray = ArrayUtility::keepItemsInArray(
			$itemArray,
			$parameterArray['fieldTSConfig']['keepItems'],
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
				|| $config['type'] == 'select' && $config['authMode']
				&& !$this->getBackendUserAuthentication()->checkAuthMode($table, $field, $evalValue, $config['authMode']);
			if ($isRemoved && !$parameterArray['fieldTSConfig']['disableNoMatchingValueElement'] && !$config['disableNoMatchingValueElement']) {
				$tvP[1] = rawurlencode(@sprintf($noMatchingLabel, $evalValue));
			} else {
				if (isset($parameterArray['fieldTSConfig']['altLabels.'][$evalValue])) {
					$tvP[1] = rawurlencode($languageService->sL($parameterArray['fieldTSConfig']['altLabels.'][$evalValue]));
				}
				if (isset($parameterArray['fieldTSConfig']['altIcons.'][$evalValue])) {
					$tvP[2] = $parameterArray['fieldTSConfig']['altIcons.'][$evalValue];
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
			$sOnChange = implode('', $parameterArray['fieldChangeFunc']);

			$multiSelectId = str_replace('.', '', uniqid('tceforms-multiselect-', TRUE));
			$itemsToSelect = '
				<select data-relatedfieldname="' . htmlspecialchars($parameterArray['itemFormElName']) . '" data-exclusivevalues="'
				. htmlspecialchars($config['exclusiveKeys']) . '" id="' . $multiSelectId . '" name="' . htmlspecialchars($parameterArray['itemFormElName']) . '_sel" '
				. ' class="form-control t3js-formengine-select-itemstoselect" '
				. ($size ? ' size="' . $size . '"' : '') . ' onchange="' . htmlspecialchars($sOnChange) . '"'
				. $parameterArray['onFocus'] . $selector_itemListStyle . '>
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
		$item .= $this->dbFileIcons($parameterArray['itemFormElName'], '', '', $itemArray, '', $params, $parameterArray['onFocus']);
		return $item;
	}

	/**
	 * @return BackendUserAuthentication
	 */
	protected function getBackendUserAuthentication() {
		return $GLOBALS['BE_USER'];
	}

}
