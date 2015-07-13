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
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;

/**
 * Create a widget with a select box where multiple items can be selected
 *
 * This is rendered for config type=select, maxitems > 1, renderMode=singlebox
 */
class SelectSingleBoxElement extends AbstractFormElement {

	/**
	 * @var array Result array given returned by render() - This property is a helper until class is properly refactored
	 */
	protected $resultArray = array();

	/**
	 * This will render a selector box element, or possibly a special construction with two selector boxes.
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

		$html = $this->getSingleField_typeSelect_singlebox($table, $field, $row, $parameterArray, $config, $selItems, $noMatchingLabel);

		// Wizards:
		if (!$disabled) {
			$html = $this->renderWizards(array($html), $config['wizards'], $table, $row, $field, $parameterArray, $parameterArray['itemFormElName'], $specConf);
		}
		$this->resultArray['html'] = $html;
		return $this->resultArray;
	}

	/**
	 * Creates a selectorbox list (renderMode = "singlebox")
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
	protected function getSingleField_typeSelect_singlebox($table, $field, $row, $parameterArray, $config, $selItems, $noMatchingLabel) {
		$languageService = $this->getLanguageService();
		// Get values in an array (and make unique, which is fine because there can be no duplicates anyway):
		$itemArray = array_flip(FormEngineUtility::extractValuesOnlyFromValueLabelList($parameterArray['itemFormElValue']));
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
				$restoreCmd[] = 'document.editform[' . GeneralUtility::quoteJSvalue($parameterArray['itemFormElName'] . '[]') . '].options[' . $c . '].selected=1;';
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
		if (!empty($itemArray) && !$parameterArray['fieldTSConfig']['disableNoMatchingValueElement'] && !$config['disableNoMatchingValueElement']) {
			foreach ($itemArray as $theNoMatchValue => $temp) {
				// Compile <option> tag:
				array_unshift($opt, '<option value="' . htmlspecialchars($theNoMatchValue) . '" selected="selected">'
					. htmlspecialchars(@sprintf($noMatchingLabel, $theNoMatchValue), ENT_COMPAT, 'UTF-8', FALSE) . '</option>');
			}
		}
		// Compile selector box:
		$sOnChange = implode('', $parameterArray['fieldChangeFunc']);
		$selector_itemListStyle = isset($config['itemListStyle'])
			? ' style="' . htmlspecialchars($config['itemListStyle']) . '"'
			: '';
		$size = (int)$config['size'];
		$cssPrefix = $size === 1 ? 'tceforms-select' : 'tceforms-multiselect';
		$size = $config['autoSizeMax']
			? MathUtility::forceIntegerInRange(count($selItems) + 1, MathUtility::forceIntegerInRange($size, 1), $config['autoSizeMax'])
			: $size;
		$selectBox = '<select id="' . str_replace('.', '', uniqid($cssPrefix, TRUE)) . '" name="' . htmlspecialchars($parameterArray['itemFormElName']) . '[]" '
			. 'class="form-control ' . $cssPrefix . '"' . ($size ? ' size="' . $size . '" ' : '')
			. ' multiple="multiple" onchange="' . htmlspecialchars($sOnChange) . '"' . $parameterArray['onFocus']
			. ' ' . $this->getValidationDataAsDataAttribute($config) . $selector_itemListStyle . $disabled . '>
						' . implode('
						', $opt) . '
					</select>';
		// Add an empty hidden field which will send a blank value if all items are unselected.
		if (!$disabled) {
			$item .= '<input type="hidden" name="' . htmlspecialchars($parameterArray['itemFormElName']) . '" value="" />';
		}
		// Put it all into a table:
		$onClick = htmlspecialchars('document.editform[' . GeneralUtility::quoteJSvalue($parameterArray['itemFormElName'] . '[]') . '].selectedIndex=-1;' . implode('', $restoreCmd) . ' return false;');
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

}
