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

use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Generation of TCEform elements where no rendering could be found
 */
class NoneElement extends AbstractFormElement {

	/**
	 * This will render a non-editable display of the content of the field.
	 *
	 * @param string $table The table name of the record
	 * @param string $field The field name which this element is supposed to edit
	 * @param array $row The record data array where the value(s) for the field can be found
	 * @param array $additionalInformation An array with additional configuration options.
	 * @return string The HTML code for the TCEform field
	 */
	public function render($table, $field, $row, &$additionalInformation) {
		$config = $additionalInformation['fieldConf']['config'];
		$itemValue = $additionalInformation['itemFormElValue'];

		if ($config['format']) {
			$itemValue = $this->formatValue($config, $itemValue);
		}
		if (!$config['pass_content']) {
			$itemValue = htmlspecialchars($itemValue);
		}

		$rows = (int)$config['rows'];
		// Render as textarea
		if ($rows > 1 || $config['type'] === 'text') {
			if (!$config['pass_content']) {
				$itemValue = nl2br($itemValue);
			}
			$cols = MathUtility::forceIntegerInRange($config['cols'] ?: $this->defaultInputWidth, 5, $this->maxInputWidth);
			$width = $this->formMaxWidth($cols);
			$item = '
				<div class="form-control-wrap"' . ($width ? ' style="max-width: ' . $width . 'px"' : '') . '>
					<textarea class="form-control" rows="' . $rows . '" disabled>' . $itemValue . '</textarea>
				</div>';
		} else {
			$cols = $config['cols'] ?: ($config['size'] ?: $this->defaultInputWidth);
			$size = MathUtility::forceIntegerInRange($cols ?: $this->defaultInputWidth, 5, $this->maxInputWidth);
			$width = $this->formMaxWidth($size);
			$item = '
				<div class="form-control-wrap"' . ($width ? ' style="max-width: ' . $width . 'px"' : '') . '>
					<input class="form-control" value="'. $itemValue .'" type="text" disabled>
				</div>
				' . ((string)$itemValue !== '' ? '<p class="help-block">' . $itemValue . '</p>' : '');
		}
		return $item;
	}

	/**
	 * Format field content if $config['format'] is set to date, filesize, ..., user
	 *
	 * @param array $config Configuration for the display
	 * @param string $itemValue The value to display
	 * @return string Formatted field value
	 */
	protected function formatValue($config, $itemValue) {
		$format = trim($config['format']);
		switch ($format) {
			case 'date':
				if ($itemValue) {
					$option = trim($config['format.']['option']);
					if ($option) {
						if ($config['format.']['strftime']) {
							$value = strftime($option, $itemValue);
						} else {
							$value = date($option, $itemValue);
						}
					} else {
						$value = date('d-m-Y', $itemValue);
					}
				} else {
					$value = '';
				}
				if ($config['format.']['appendAge']) {
					$age = BackendUtility::calcAge(
						$GLOBALS['EXEC_TIME'] - $itemValue,
						$this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.minutesHoursDaysYears')
					);
					$value .= ' (' . $age . ')';
				}
				$itemValue = $value;
				break;
			case 'datetime':
				// compatibility with "eval" (type "input")
				if ($itemValue !== '') {
					$itemValue = date('H:i d-m-Y', (int)$itemValue);
				}
				break;
			case 'time':
				// compatibility with "eval" (type "input")
				if ($itemValue !== '') {
					$itemValue = date('H:i', (int)$itemValue);
				}
				break;
			case 'timesec':
				// compatibility with "eval" (type "input")
				if ($itemValue !== '') {
					$itemValue = date('H:i:s', (int)$itemValue);
				}
				break;
			case 'year':
				// compatibility with "eval" (type "input")
				if ($itemValue !== '') {
					$itemValue = date('Y', (int)$itemValue);
				}
				break;
			case 'int':
				$baseArr = array('dec' => 'd', 'hex' => 'x', 'HEX' => 'X', 'oct' => 'o', 'bin' => 'b');
				$base = trim($config['format.']['base']);
				$format = $baseArr[$base] ?: 'd';
				$itemValue = sprintf('%' . $format, $itemValue);
				break;
			case 'float':
				$precision = MathUtility::forceIntegerInRange($config['format.']['precision'], 1, 10, 2);
				$itemValue = sprintf('%.' . $precision . 'f', $itemValue);
				break;
			case 'number':
				$format = trim($config['format.']['option']);
				$itemValue = sprintf('%' . $format, $itemValue);
				break;
			case 'md5':
				$itemValue = md5($itemValue);
				break;
			case 'filesize':
				// We need to cast to int here, otherwise empty values result in empty output,
				// but we expect zero.
				$value = GeneralUtility::formatSize((int)$itemValue);
				if ($config['format.']['appendByteSize']) {
					$value .= ' (' . $itemValue . ')';
				}
				$itemValue = $value;
				break;
			case 'user':
				$func = trim($config['format.']['userFunc']);
				if ($func) {
					$params = array(
						'value' => $itemValue,
						'args' => $config['format.']['userFunc'],
						'config' => $config,
						'pObj' => &$this
					);
					$itemValue = GeneralUtility::callUserFunction($func, $params, $this);
				}
				break;
			default:
				// Do nothing e.g. when $format === ''
		}
		return $itemValue;
	}

}
