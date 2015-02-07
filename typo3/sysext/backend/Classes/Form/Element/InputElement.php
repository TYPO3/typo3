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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Backend\Form\FormEngine;

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

		$isDateField = FALSE;

		$config = $additionalInformation['fieldConf']['config'];
		$specConf = BackendUtility::getSpecConfParts($additionalInformation['extra'], $additionalInformation['fieldConf']['defaultExtras']);
		$size = MathUtility::forceIntegerInRange($config['size'] ?: $this->defaultInputWidth, $this->minimumInputWidth, $this->maxInputWidth);
		$evalList = GeneralUtility::trimExplode(',', $config['eval'], TRUE);
		$classes = array();
		$attributes = array();

		if (!isset($config['checkbox'])) {
			$config['checkbox'] = '0';
			$checkboxIsset = FALSE;
		} else {
			$checkboxIsset = TRUE;
		}

		// set all date times available
		$dateFormats = array(
			'date' => '%d-%m-%Y',
			'year' => '%Y',
			'time' => '%H:%M',
			'timesec' => '%H:%M:%S'
		);
		if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat']) {
			$dateFormats['date'] = '%m-%d-%Y';
		}
		$dateFormats['datetime'] = $dateFormats['time'] . ' ' . $dateFormats['date'];
		$dateFormats['datetimesec'] = $dateFormats['timesec'] . ' ' . $dateFormats['date'];

		// readonly
		if ($this->isGlobalReadonly() || $config['readOnly']) {
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
			$formEngineDummy = new FormEngine;
			$noneElement = GeneralUtility::makeInstance(NoneElement::class, $formEngineDummy);
			$elementConfiguration = array(
				'fieldConf' => array(
					'config' => $config,
				),
				'itemFormElValue' => $itemFormElValue,
			);
			return $noneElement->render('', '', '', $elementConfiguration);
		}


		if (in_array('datetime', $evalList, TRUE)
			|| in_array('date', $evalList)
			|| in_array('time', $evalList)) {

			$classes[] = 't3js-datetimepicker';
			$isDateField = TRUE;
			if (in_array('datetime', $evalList)) {
				$attributes['data-date-type'] = 'datetime';
				$dateFormat = $dateFormats['datetime'];
			} elseif (in_array('date', $evalList)) {
				$attributes['data-date-type'] = 'date';
				$dateFormat = $dateFormats['date'];
			} else {
				$attributes['data-date-type'] = 'time';
				$dateFormat = $dateFormats['time'];
			}
			if ($additionalInformation['itemFormElValue'] > 0) {
				$additionalInformation['itemFormElValue'] += date('Z', $additionalInformation['itemFormElValue']);
			}
			if (isset($config['range']['lower'])) {
				$attributes['data-date-minDate'] = (int)$config['range']['lower'];
			}
			if (isset($config['range']['upper'])) {
				$attributes['data-date-maxDate'] = (int)$config['range']['upper'];
			}
		} elseif (in_array('timesec', $evalList)) {
			$classes[] = 't3js-datetimepicker';
			$attributes['data-date-type'] = 'timesec';
		} else {
			if ($checkboxIsset === FALSE) {
				$config['checkbox'] = '';
			}
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
		$additionalInformation['fieldChangeFunc'] = array_merge(array('typo3form.fieldGet' => 'typo3form.fieldGet(' . $paramsList . ');'), $additionalInformation['fieldChangeFunc']);

		// set classes
		$classes[] = 'form-control';
		$classes[] = 't3js-clearable';
		$classes[] = 'hasDefaultValue';

		// calculate attributes
		$attributes['id'] = str_replace('.', '', uniqid('formengine-input-', TRUE));
		$attributes['name'] = $additionalInformation['itemFormElName'] . '_hr';
		$attributes['value'] = '';
		$attributes['maxlength'] = $config['max'] ?: 256;
		$attributes['onchange'] = htmlspecialchars(implode('', $additionalInformation['fieldChangeFunc']));

		if (!empty($styles)) {
			$attributes['style'] = implode(' ', $styles);
		}
		if (!empty($classes)) {
			$attributes['class'] = implode(' ', $classes);
		}
		if (isset($config['max']) && (int)$config['max'] > 0) {
			$attributes['maxlength'] = (int)$config['max'];
		}

		// Build the attribute string
		$attributeString = '';
		foreach ($attributes as $attributeName => $attributeValue) {
			$attributeString .= ' '. $attributeName . '="' . $attributeValue . '"';
		}

		// This is the EDITABLE form field.
		$item = '
			<input type="text"'
				. $attributeString
				. $this->formEngine->getPlaceholderAttribute($table, $field, $config, $row)
				. 'style="' . $cssStyle . '" '
				. $additionalInformation['onFocus'] . ' />';

		// This is the ACTUAL form field - values from the EDITABLE field must be transferred to this field which is the one that is written to the database.
		$item .= '<input type="hidden" name="' . $additionalInformation['itemFormElName'] . '" value="' . htmlspecialchars($additionalInformation['itemFormElValue']) . '" />';

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

		// add HTML wrapper
		if ($isDateField) {
			$item = '
				<div class="input-group">
					' . $item . '
					<span class="input-group-btn">
						<label class="btn btn-default" for="' . $attributes['id'] . '">
							' . IconUtility::getSpriteIcon('actions-edit-pick-date') . '
						</label>
					</span>
				</div>';
		}

		// Creating an alternative item without the JavaScript handlers.
		$altItem = '
			<input type="hidden" name="' . $additionalInformation['itemFormElName'] . '_hr" value="" />
			<input type="hidden" name="' . $additionalInformation['itemFormElName'] . '" value="' . htmlspecialchars($additionalInformation['itemFormElValue']) . '" />';

		// Wrap a wizard around the item?
		$item = $this->renderWizards(
			array($item, $altItem),
			$config['wizards'],
			$table,
			$row,
			$field,
			$additionalInformation,
			$additionalInformation['itemFormElName'] . '_hr', $specConf
		);

		// Add a wrapper to remain maximum width
		$width = (int)$this->formMaxWidth($size);
		$item = '<div class="form-control-wrap"' . ($width ? ' style="max-width: ' . $width . 'px"' : '') . '>' . $item . '</div>';
		return $item;
	}

}
