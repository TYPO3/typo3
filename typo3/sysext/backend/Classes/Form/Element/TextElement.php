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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Html\RteHtmlParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Generation of TCEform elements of the type "text"
 */
class TextElement extends AbstractFormElement {

	/**
	 * This will render a <textarea> OR RTE area form field,
	 * possibly with various control/validation features
	 *
	 * @param string $table The table name of the record
	 * @param string $field The field name which this element is supposed to edit
	 * @param array $row The record data array where the value(s) for the field can be found
	 * @param array $additionalInformation An array with additional configuration options.
	 * @return string The HTML code for the TCEform field
	 */
	public function render($table, $field, $row, &$additionalInformation) {
		// Init config:
		$config = $additionalInformation['fieldConf']['config'];
		$evalList = GeneralUtility::trimExplode(',', $config['eval'], TRUE);
		if ($this->formEngine->renderReadonly || $config['readOnly']) {
			return $this->formEngine->getSingleField_typeNone_render($config, $additionalInformation['itemFormElValue']);
		}
		// Setting columns number:
		$cols = MathUtility::forceIntegerInRange($config['cols'] ? $config['cols'] : 30, 5, $this->formEngine->maxTextareaWidth);
		// Setting number of rows:
		$origRows = ($rows = MathUtility::forceIntegerInRange($config['rows'] ? $config['rows'] : 5, 1, 20));
		if (strlen($additionalInformation['itemFormElValue']) > $this->formEngine->charsPerRow * 2) {
			$cols = $this->formEngine->maxTextareaWidth;
			$rows = MathUtility::forceIntegerInRange(round(strlen($additionalInformation['itemFormElValue']) / $this->formEngine->charsPerRow), count(explode(LF, $additionalInformation['itemFormElValue'])), 20);
			if ($rows < $origRows) {
				$rows = $origRows;
			}
		}
		if (in_array('required', $evalList)) {
			$this->formEngine->requiredFields[$table . '_' . $row['uid'] . '_' . $field] = $additionalInformation['itemFormElName'];
		}
		// Init RTE vars:
		// Set TRUE, if the RTE is loaded; If not a normal textarea is shown.
		$RTEwasLoaded = 0;
		// Set TRUE, if the RTE would have been loaded if it wasn't for the disable-RTE flag in the bottom of the page...
		$RTEwouldHaveBeenLoaded = 0;
		// "Extra" configuration; Returns configuration for the field based on settings found in the "types" fieldlist. Traditionally, this is where RTE configuration has been found.
		$specConf = $this->formEngine->getSpecConfFromString($additionalInformation['extra'], $additionalInformation['fieldConf']['defaultExtras']);
		// Setting up the altItem form field, which is a hidden field containing the value
		$altItem = '<input type="hidden" name="' . htmlspecialchars($additionalInformation['itemFormElName']) . '" value="' . htmlspecialchars($additionalInformation['itemFormElValue']) . '" />';
		$item = '';
		// If RTE is generally enabled (TYPO3_CONF_VARS and user settings)
		if ($this->formEngine->RTEenabled) {
			$p = BackendUtility::getSpecConfParametersFromArray($specConf['rte_transform']['parameters']);
			// If the field is configured for RTE and if any flag-field is not set to disable it.
			if (isset($specConf['richtext']) && (!$p['flag'] || !$row[$p['flag']])) {
				BackendUtility::fixVersioningPid($table, $row);
				list($tscPID, $thePidValue) = $this->formEngine->getTSCpid($table, $row['uid'], $row['pid']);
				// If the pid-value is not negative (that is, a pid could NOT be fetched)
				if ($thePidValue >= 0) {
					$RTEsetup = $this->getBackendUserAuthentication()->getTSConfig('RTE', BackendUtility::getPagesTSconfig($tscPID));
					$RTEtypeVal = BackendUtility::getTCAtypeValue($table, $row);
					$thisConfig = BackendUtility::RTEsetup($RTEsetup['properties'], $table, $field, $RTEtypeVal);
					if (!$thisConfig['disabled']) {
						if (!$this->formEngine->disableRTE) {
							$this->formEngine->RTEcounter++;
							// Find alternative relative path for RTE images/links:
							$eFile = RteHtmlParser::evalWriteFile($specConf['static_write'], $row);
							$RTErelPath = is_array($eFile) ? dirname($eFile['relEditFile']) : '';
							// Get RTE object, draw form and set flag:
							$RTEobj = BackendUtility::RTEgetObj();
							$item = $RTEobj->drawRTE($this->formEngine, $table, $field, $row, $additionalInformation, $specConf, $thisConfig, $RTEtypeVal, $RTErelPath, $thePidValue);
							// Wizard:
							$item = $this->formEngine->renderWizards(array($item, $altItem), $config['wizards'], $table, $row, $field, $additionalInformation, $additionalInformation['itemFormElName'], $specConf, 1);
							$RTEwasLoaded = 1;
						} else {
							$RTEwouldHaveBeenLoaded = 1;
							$this->formEngine->commentMessages[] = $additionalInformation['itemFormElName'] . ': RTE is disabled by the on-page RTE-flag (probably you can enable it by the check-box in the bottom of this page!)';
						}
					} else {
						$this->formEngine->commentMessages[] = $additionalInformation['itemFormElName'] . ': RTE is disabled by the Page TSconfig, "RTE"-key (eg. by RTE.default.disabled=0 or such)';
					}
				} else {
					$this->formEngine->commentMessages[] = $additionalInformation['itemFormElName'] . ': PID value could NOT be fetched. Rare error, normally with new records.';
				}
			} else {
				if (!isset($specConf['richtext'])) {
					$this->formEngine->commentMessages[] = $additionalInformation['itemFormElName'] . ': RTE was not configured for this field in TCA-types';
				}
				if (!(!$p['flag'] || !$row[$p['flag']])) {
					$this->formEngine->commentMessages[] = $additionalInformation['itemFormElName'] . ': Field-flag (' . $additionalInformation['flag'] . ') has been set to disable RTE!';
				}
			}
		}
		// Display ordinary field if RTE was not loaded.
		if (!$RTEwasLoaded) {
			// Show message, if no RTE (field can only be edited with RTE!)
			if ($specConf['rte_only']) {
				$item = '<p><em>' . htmlspecialchars($this->formEngine->getLL('l_noRTEfound')) . '</em></p>';
			} else {
				if ($specConf['nowrap']) {
					$wrap = 'off';
				} else {
					$wrap = $config['wrap'] ?: 'virtual';
				}
				$classes = array();
				if ($specConf['fixed-font']) {
					$classes[] = 'fixed-font';
				}
				if ($specConf['enable-tab']) {
					$classes[] = 'enable-tab';
				}
				$formWidthText = $this->formWidthText($cols, $wrap);
				// Extract class attributes from $formWidthText (otherwise it would be added twice to the output)
				$res = array();
				if (preg_match('/ class="(.+?)"/', $formWidthText, $res)) {
					$formWidthText = str_replace(' class="' . $res[1] . '"', '', $formWidthText);
					$classes = array_merge($classes, explode(' ', $res[1]));
				}
				if (count($classes)) {
					$class = ' class="tceforms-textarea ' . implode(' ', $classes) . '"';
				} else {
					$class = 'tceforms-textarea';
				}
				$evalList = GeneralUtility::trimExplode(',', $config['eval'], TRUE);
				foreach ($evalList as $func) {
					switch ($func) {
						case 'required':
							$this->formEngine->registerRequiredProperty('field', $table . '_' . $row['uid'] . '_' . $field, $additionalInformation['itemFormElName']);
							break;
						default:
							// Pair hook to the one in \TYPO3\CMS\Core\DataHandling\DataHandler::checkValue_input_Eval()
							// and \TYPO3\CMS\Core\DataHandling\DataHandler::checkValue_text_Eval()
							$evalObj = GeneralUtility::getUserObj($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][$func] . ':&' . $func);
							if (is_object($evalObj) && method_exists($evalObj, 'deevaluateFieldValue')) {
								$_params = array(
									'value' => $additionalInformation['itemFormElValue']
								);
								$additionalInformation['itemFormElValue'] = $evalObj->deevaluateFieldValue($_params);
							}
					}
				}
				$iOnChange = implode('', $additionalInformation['fieldChangeFunc']);
				$item .= '
							<textarea ' . 'id="' . uniqid('tceforms-textarea-') . '" ' . 'name="' . $additionalInformation['itemFormElName']
					. '"' . $formWidthText . $class . ' ' . 'rows="' . $rows . '" ' . 'wrap="' . $wrap . '" ' . 'onchange="'
					. htmlspecialchars($iOnChange) . '"' . $this->formEngine->getPlaceholderAttribute($table, $field, $config, $row)
					. $additionalInformation['onFocus'] . '>' . GeneralUtility::formatForTextarea($additionalInformation['itemFormElValue']) . '</textarea>';
				$item = $this->formEngine->renderWizards(array($item, $altItem), $config['wizards'], $table, $row, $field, $additionalInformation,
					$additionalInformation['itemFormElName'], $specConf, $RTEwouldHaveBeenLoaded);
			}
		}
		// Return field HTML:
		return $item;
	}

	/**
	 * Returns parameters to set with for a textarea field
	 *
	 * @param integer $size The abstract width (1-48)
	 * @param string $wrap Empty or "off" (text wrapping in the field or not)
	 * @return string The "cols" attribute string (or style from formWidth())
	 * @see formWidth()
	 */
	protected function formWidthText($size = 48, $wrap = '') {
		$wTags = $this->formEngine->formWidth($size, TRUE);
		// Netscape 6+ seems to have this ODD problem where there WILL ALWAYS be wrapping
		// with the cols-attribute set and NEVER without the col-attribute...
		if (strtolower(trim($wrap)) != 'off' && $GLOBALS['CLIENT']['BROWSER'] == 'net' && $GLOBALS['CLIENT']['VERSION'] >= 5) {
			$wTags .= ' cols="' . $size . '"';
		}
		return $wTags;
	}
}
