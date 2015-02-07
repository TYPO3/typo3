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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Generation of TCEform elements of the type "text"
 */
class TextElement extends AbstractFormElement {

	/**
	 * The number of chars expected per row when the height of a text area field is
	 * automatically calculated based on the number of characters found in the field content.
	 *
	 * @var int
	 */
	protected $charactersPerRow = 40;

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
		$backendUser = $this->getBackendUserAuthentication();

		$config = $additionalInformation['fieldConf']['config'];

		// Setting columns number
		$cols = MathUtility::forceIntegerInRange($config['cols'] ?: $this->defaultInputWidth, $this->minimumInputWidth, $this->maxInputWidth);

		// Setting number of rows
		$rows = MathUtility::forceIntegerInRange($config['rows'] ?: 5, 1, 20);
		$originalRows = $rows;

		$itemFormElementValueLength = strlen($additionalInformation['itemFormElValue']);
		if ($itemFormElementValueLength > $this->charactersPerRow * 2) {
			$cols = $this->maxInputWidth;
			$rows = MathUtility::forceIntegerInRange(
				round($itemFormElementValueLength / $this->charactersPerRow),
				count(explode(LF, $additionalInformation['itemFormElValue'])),
				20
			);
			if ($rows < $originalRows) {
				$rows = $originalRows;
			}
		}

		// must be called after the cols and rows calculation, so the parameters are applied
		// to read-only fields as well.
		if ($this->isGlobalReadonly() || $config['readOnly']) {
			$config['cols'] = $cols;
			$config['rows'] = $rows;
			$noneElement = GeneralUtility::makeInstance(NoneElement::class, $this->formEngine);
			$elementConfiguration = array(
				'fieldConf' => array(
					'config' => $config,
				),
				'itemFormElValue' => $additionalInformation['itemFormElValue'],
			);
			return $noneElement->render('', '', '', $elementConfiguration);
		}

		$evalList = GeneralUtility::trimExplode(',', $config['eval'], TRUE);
		if (in_array('required', $evalList, TRUE)) {
			$this->formEngine->requiredFields[$table . '_' . $row['uid'] . '_' . $field] = $additionalInformation['itemFormElName'];
		}
		// Init RTE vars
		// Set TRUE, if the RTE is loaded; If not a normal textarea is shown.
		$rteWasLoaded = FALSE;
		// Set TRUE, if the RTE would have been loaded if it wasn't for the disable-RTE flag in the bottom of the page...
		$rteWouldHaveBeenLoaded = FALSE;
		// "Extra" configuration; Returns configuration for the field based on settings found in the "types" fieldlist. Traditionally, this is where RTE configuration has been found.
		$specialConfiguration = BackendUtility::getSpecConfParts($additionalInformation['extra'], $additionalInformation['fieldConf']['defaultExtras']);
		// Setting up the altItem form field, which is a hidden field containing the value
		$altItem = '<input type="hidden" name="' . htmlspecialchars($additionalInformation['itemFormElName']) . '" value="' . htmlspecialchars($additionalInformation['itemFormElValue']) . '" />';
		$item = '';
		// If RTE is generally enabled (TYPO3_CONF_VARS and user settings)
		if ($backendUser->isRTE()) {
			$parameters = BackendUtility::getSpecConfParametersFromArray($specialConfiguration['rte_transform']['parameters']);
			// If the field is configured for RTE and if any flag-field is not set to disable it.
			if (isset($specialConfiguration['richtext']) && (!$parameters['flag'] || !$row[$parameters['flag']])) {
				BackendUtility::fixVersioningPid($table, $row);
				list($recordPid, $tsConfigPid) = BackendUtility::getTSCpidCached($table, $row['uid'], $row['pid']);
				// If the pid-value is not negative (that is, a pid could NOT be fetched)
				if ($tsConfigPid >= 0) {
					$rteSetup = $backendUser->getTSConfig('RTE', BackendUtility::getPagesTSconfig($recordPid));
					$rteTcaTypeValue = BackendUtility::getTCAtypeValue($table, $row);
					$rteSetupConfiguration = BackendUtility::RTEsetup($rteSetup['properties'], $table, $field, $rteTcaTypeValue);
					if (!$rteSetupConfiguration['disabled']) {
						$this->formEngine->RTEcounter++;
						// Get RTE object, draw form and set flag:
						$rteObject = BackendUtility::RTEgetObj();
						$item = $rteObject->drawRTE(
							$this->formEngine,
							$table,
							$field,
							$row,
							$additionalInformation,
							$specialConfiguration,
							$rteSetupConfiguration,
							$rteTcaTypeValue,
							'',
							$tsConfigPid
						);

						// Wizard
						$item = $this->renderWizards(
							array($item, $altItem),
							$config['wizards'],
							$table,
							$row,
							$field,
							$additionalInformation,
							$additionalInformation['itemFormElName'],
							$specialConfiguration,
							TRUE
						);
						$rteWasLoaded = TRUE;
					}
				}
			}
		}
		// Display ordinary field if RTE was not loaded.
		if (!$rteWasLoaded) {
			// Show message, if no RTE (field can only be edited with RTE!)
			if ($specialConfiguration['rte_only']) {
				$item = '<p><em>' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.noRTEfound')) . '</em></p>';
			} else {
				// validation
				foreach ($evalList as $func) {
					if ($func === 'required') {
						$this->formEngine->registerRequiredProperty('field', $table . '_' . $row['uid'] . '_' . $field, $additionalInformation['itemFormElName']);
					} else {
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

				// calculate classes
				$classes = array();
				//$classes[] = 'tceforms-textarea';
				$classes[] = 'form-control';
				$classes[] = 't3js-formengine-textarea';
				if ($specialConfiguration['fixed-font']) {
					$classes[] = 'text-monospace';
				}
				if ($specialConfiguration['enable-tab']) {
					$classes[] = 'enable-tab';
				}

				// calculate inline styles
				$styles = array();
				// add the max-height from the users' preference to it
				$maximumHeight = (int)$backendUser->uc['resizeTextareas_MaxHeight'];
				if ($maximumHeight > 0) {
					$styles[] = 'max-height: ' . $maximumHeight . 'px';
				}

				// calculate attributes
				$attributes = array();
				$attributes['id'] = str_replace('.', '', uniqid('formengine-textarea-', TRUE));
				$attributes['name'] = $additionalInformation['itemFormElName'];
				if (!empty($styles)) {
					$attributes['style'] = implode(' ', $styles);
				}
				if (!empty($classes)) {
					$attributes['class'] = implode(' ', $classes);
				}
				$attributes['rows'] = $rows;
				$attributes['wrap'] = $specialConfiguration['nowrap'] ? 'off' : ($config['wrap'] ?: 'virtual');
				$attributes['onChange'] = htmlspecialchars(implode('', $additionalInformation['fieldChangeFunc']));
				if (isset($config['max']) && (int)$config['max'] > 0) {
					$attributes['maxlength'] = (int)$config['max'];
				}
				$attributeString = '';
				foreach ($attributes as $attributeName => $attributeValue) {
					$attributeString .= ' '. $attributeName . '="' . $attributeValue . '"';
				}

				// Build the textarea
				$item .= '<textarea'
							. $attributeString
							. $this->formEngine->getPlaceholderAttribute($table, $field, $config, $row)
							. $additionalInformation['onFocus']
							. '>' . GeneralUtility::formatForTextarea($additionalInformation['itemFormElValue']) . '</textarea>';

				// Wrap a wizard around the item?
				$item = $this->renderWizards(
					array($item, $altItem),
					$config['wizards'],
					$table,
					$row,
					$field,
					$additionalInformation,
					$additionalInformation['itemFormElName'],
					$specialConfiguration,
					$rteWouldHaveBeenLoaded
				);

				$maximumWidth = (int)$this->formMaxWidth($cols);
				$item = '<div class="form-control-wrap"' . ($maximumWidth ? ' style="max-width: ' . $maximumWidth . 'px"' : '') . '>' . $item . '</div>';
			}
		}
		return $item;
	}

}
