<?php
namespace TYPO3\CMS\Backend\Form\Element;

/**
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

use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Generation of TCEform elements of the type "input"
 */
class InputElement extends AbstractFormElement {

	/**
	 * This will render a single-line input form field, possibly with various control/validation features
	 *
	 * @param string $table The table name of the record
	 * @param string $field The field name which this element is supposed to edit
	 * @param array $row The record data array where the value(s) for the field can be found
	 * @param array $additionalInformation An array with additional configuration options.
	 * @return string The HTML code for the TCEform field
	 */
	public function render($table, $field, $row, &$additionalInformation) {
		$config = $additionalInformation['fieldConf']['config'];
		$specConf = $this->formEngine->getSpecConfFromString($additionalInformation['extra'], $additionalInformation['fieldConf']['defaultExtras']);
		$size = MathUtility::forceIntegerInRange($config['size'] ? $config['size'] : 30, 5, $this->formEngine->maxInputWidth);
		$evalList = GeneralUtility::trimExplode(',', $config['eval'], TRUE);
		$classAndStyleAttributes = $this->formEngine->formWidthAsArray($size);
		$fieldAppendix = '';
		$item = '';
		$cssClasses = array($classAndStyleAttributes['class']);
		$cssStyle = $classAndStyleAttributes['style'];
		if (!isset($config['checkbox'])) {
			$config['checkbox'] = '0';
			$checkboxIsset = FALSE;
		} else {
			$checkboxIsset = TRUE;
		}
		if (in_array('date', $evalList) || in_array('datetime', $evalList)) {
			if (in_array('datetime', $evalList)) {
				$class = 'datetime';
			} else {
				$class = 'date';
			}
			$dateRange = '';
			if (isset($config['range']['lower'])) {
				$dateRange .= ' lower-' . (int)$config['range']['lower'];
			}
			if (isset($config['range']['upper'])) {
				$dateRange .= ' upper-' . (int)$config['range']['upper'];
			}
			$inputId = uniqid('tceforms-' . $class . 'field-', TRUE);
			$cssClasses[] = 'tceforms-textfield tceforms-' . $class . 'field' . $dateRange;
			$fieldAppendix = IconUtility::getSpriteIcon('actions-edit-pick-date', array(
				'style' => 'cursor:pointer;',
				'id' => 'picker-' . $inputId
			));
		} elseif (in_array('timesec', $evalList)) {
			$inputId = uniqid('tceforms-timesecfield-', TRUE);
			$cssClasses[] = 'tceforms-textfield tceforms-timesecfield';
		} elseif (in_array('year', $evalList)) {
			$inputId = uniqid('tceforms-yearfield-', TRUE);
			$cssClasses[] = 'tceforms-textfield tceforms-yearfield';
		} elseif (in_array('time', $evalList)) {
			$inputId = uniqid('tceforms-timefield-', TRUE);
			$cssClasses[] = 'tceforms-textfield tceforms-timefield';
		} elseif (in_array('int', $evalList)) {
			$inputId = uniqid('tceforms-intfield-', TRUE);
			$cssClasses[] = 'tceforms-textfield tceforms-intfield';
		} elseif (in_array('double2', $evalList)) {
			$inputId = uniqid('tceforms-double2field-', TRUE);
			$cssClasses[] = 'tceforms-textfield tceforms-double2field';
		} else {
			$inputId = uniqid('tceforms-textfield-', TRUE);
			$cssClasses[] = 'tceforms-textfield';
			if ($checkboxIsset === FALSE) {
				$config['checkbox'] = '';
			}
		}
		if (isset($config['wizards']['link'])) {
			$inputId = uniqid('tceforms-linkfield-', TRUE);
			$cssClasses[] = 'tceforms-textfield tceforms-linkfield';
		} elseif (isset($config['wizards']['color'])) {
			$inputId = uniqid('tceforms-colorfield-', TRUE);
			$cssClasses[] = 'tceforms-textfield tceforms-colorfield';
		}
		$inputId = str_replace('.', '', $inputId);
		if ($this->formEngine->renderReadonly || $config['readOnly']) {
			$itemFormElValue = $additionalInformation['itemFormElValue'];
			if (in_array('date', $evalList)) {
				$config['format'] = 'date';
			} elseif (in_array('datetime', $evalList)) {
				$config['format'] = 'datetime';
			} elseif (in_array('time', $evalList)) {
				$config['format'] = 'time';
			}
			if (in_array('password', $evalList)) {
				$itemFormElValue = $itemFormElValue ? '*********' : '';
			}
			return $this->formEngine->getSingleField_typeNone_render($config, $itemFormElValue);
		}
		foreach ($evalList as $func) {
			switch ($func) {
				case 'required':
					$this->formEngine->registerRequiredProperty('field', $table . '_' . $row['uid'] . '_' . $field, $additionalInformation['itemFormElName']);
					// Mark this field for date/time disposal:
					if (array_intersect($evalList, array('date', 'datetime', 'time'))) {
						$this->formEngine->requiredAdditional[$additionalInformation['itemFormElName']]['isPositiveNumber'] = TRUE;
					}
					break;
				default:
					// Pair hook to the one in \TYPO3\CMS\Core\DataHandling\DataHandler::checkValue_input_Eval()
					$evalObj = GeneralUtility::getUserObj($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][$func] . ':&' . $func);
					if (is_object($evalObj) && method_exists($evalObj, 'deevaluateFieldValue')) {
						$_params = array(
							'value' => $additionalInformation['itemFormElValue']
						);
						$additionalInformation['itemFormElValue'] = $evalObj->deevaluateFieldValue($_params);
					}
			}
		}
		$paramsList = '\'' . $additionalInformation['itemFormElName'] . '\',\'' . implode(',', $evalList) . '\',\'' . trim($config['is_in']) . '\',' . (isset($config['checkbox']) ? 1 : 0) . ',\'' . $config['checkbox'] . '\'';
		if (in_array('date', $evalList) || in_array('datetime', $evalList)) {
			$item .= '<span class="t3-tceforms-input-wrapper-datetime">';
			// Add server timezone offset to UTC to our stored date
			if ($additionalInformation['itemFormElValue'] > 0) {
				$additionalInformation['itemFormElValue'] += date('Z', $additionalInformation['itemFormElValue']);
			}
		} else {
			$item .= '<span class="t3-tceforms-input-wrapper">';
		}
		$additionalInformation['fieldChangeFunc'] = array_merge(array('typo3form.fieldGet' => 'typo3form.fieldGet(' . $paramsList . ');'), $additionalInformation['fieldChangeFunc']);

		$mLgd = $config['max'] ?: 256;
		$iOnChange = implode('', $additionalInformation['fieldChangeFunc']);
		$cssClasses[] = 'hasDefaultValue';
		$item .= '<input type="text" ' . $this->formEngine->getPlaceholderAttribute($table, $field, $config, $row) . 'id="' . $inputId . '" ' . 'class="' . implode(' ', $cssClasses) . '" ' . 'name="' . $additionalInformation['itemFormElName'] . '_hr" ' . 'value=""' . 'style="' . $cssStyle . '" ' . 'maxlength="' . $mLgd . '" ' . 'onchange="' . htmlspecialchars($iOnChange) . '"' . $additionalInformation['onFocus'] . ' />';
		// This is the EDITABLE form field.
		$item .= '<input type="hidden" name="' . $additionalInformation['itemFormElName'] . '" value="' . htmlspecialchars($additionalInformation['itemFormElValue']) . '" />';
		// This is the ACTUAL form field - values from the EDITABLE field must be transferred to this field which is the one that is written to the database.
		$item .= $fieldAppendix . '</span><div style="clear:both;"></div>';
		$this->formEngine->extJSCODE .= 'typo3form.fieldSet(' . $paramsList . ');';
		// Going through all custom evaluations configured for this field
		foreach ($evalList as $evalData) {
			$evalObj = GeneralUtility::getUserObj($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][$evalData] . ':&' . $evalData);
			if (is_object($evalObj) && method_exists($evalObj, 'returnFieldJS')) {
				$this->formEngine->extJSCODE .= '
TBE_EDITOR.customEvalFunctions[\'' . $evalData . '\'] = function(value) {
' . $evalObj->returnFieldJS() . '
}
';
			}
		}
		// Creating an alternative item without the JavaScript handlers.
		$altItem = '<input type="hidden" name="' . $additionalInformation['itemFormElName'] . '_hr" value="" />';
		$altItem .= '<input type="hidden" name="' . $additionalInformation['itemFormElName'] . '" value="' . htmlspecialchars($additionalInformation['itemFormElValue']) . '" />';
		// Wrap a wizard around the item?
		$item = $this->formEngine->renderWizards(array($item, $altItem), $config['wizards'], $table, $row, $field, $additionalInformation, $additionalInformation['itemFormElName'] . '_hr', $specConf);

		return $item;
	}
}
