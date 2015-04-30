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
use TYPO3\CMS\Backend\Form\FormEngine;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

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
	 * @return array As defined in initializeResultArray() of AbstractNode
	 */
	public function render() {
		$languageService = $this->getLanguageService();

		$table = $this->globalOptions['table'];
		$fieldName = $this->globalOptions['fieldName'];
		$row = $this->globalOptions['databaseRow'];
		$parameterArray = $this->globalOptions['parameterArray'];
		$resultArray = $this->initializeResultArray();
		$backendUser = $this->getBackendUserAuthentication();

		$config = $parameterArray['fieldConf']['config'];

		// Setting columns number
		$cols = MathUtility::forceIntegerInRange($config['cols'] ?: $this->defaultInputWidth, $this->minimumInputWidth, $this->maxInputWidth);

		// Setting number of rows
		$rows = MathUtility::forceIntegerInRange($config['rows'] ?: 5, 1, 20);
		$originalRows = $rows;

		$itemFormElementValueLength = strlen($parameterArray['itemFormElValue']);
		if ($itemFormElementValueLength > $this->charactersPerRow * 2) {
			$cols = $this->maxInputWidth;
			$rows = MathUtility::forceIntegerInRange(
				round($itemFormElementValueLength / $this->charactersPerRow),
				count(explode(LF, $parameterArray['itemFormElValue'])),
				20
			);
			if ($rows < $originalRows) {
				$rows = $originalRows;
			}
		}

		// must be called after the cols and rows calculation, so the parameters are applied
		// to read-only fields as well.
		// @todo: Same as in InputElement ...
		if ($this->isGlobalReadonly() || $config['readOnly']) {
			$config['cols'] = $cols;
			$config['rows'] = $rows;
			/** @var NoneElement $noneElement */
			$noneElement = GeneralUtility::makeInstance(NoneElement::class);
			$noneElementOptions = $this->globalOptions;
			$noneElementOptions['parameterArray'] = array(
				'fieldConf' => array(
					'config' => $config,
				),
				'itemFormElValue' => $parameterArray['itemFormElValue'],
			);
			return $noneElement->setGlobalOptions($noneElementOptions)->render();
		}

		$evalList = GeneralUtility::trimExplode(',', $config['eval'], TRUE);
		if (in_array('required', $evalList, TRUE)) {
			$resultArray['requiredFields'][$table . '_' . $row['uid'] . '_' . $fieldName] = $parameterArray['itemFormElName'];
			$tabAndInlineStack = $this->globalOptions['tabAndInlineStack'];
			if (!empty($tabAndInlineStack) && preg_match('/^(.+\\])\\[(\\w+)\\]$/', $parameterArray['itemFormElName'], $match)) {
				array_shift($match);
				$resultArray['requiredNested'][$parameterArray['itemFormElName']] = array(
					'parts' => $match,
					'level' => $tabAndInlineStack,
				);
			}
		}
		// Init RTE vars
		// Set TRUE, if the RTE is loaded; If not a normal textarea is shown.
		$rteWasLoaded = FALSE;
		// Set TRUE, if the RTE would have been loaded if it wasn't for the disable-RTE flag in the bottom of the page...
		$rteWouldHaveBeenLoaded = FALSE;
		// "Extra" configuration; Returns configuration for the field based on settings found in the "types" fieldlist. Traditionally, this is where RTE configuration has been found.
		$specialConfiguration = BackendUtility::getSpecConfParts($parameterArray['extra'], $parameterArray['fieldConf']['defaultExtras']);
		// Setting up the altItem form field, which is a hidden field containing the value
		$altItem = '<input type="hidden" name="' . htmlspecialchars($parameterArray['itemFormElName']) . '" value="' . htmlspecialchars($parameterArray['itemFormElValue']) . '" />';
		$html = '';
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
					$rteSetupConfiguration = BackendUtility::RTEsetup($rteSetup['properties'], $table, $fieldName, $rteTcaTypeValue);
					if (!$rteSetupConfiguration['disabled']) {
						// Get RTE object, draw form and set flag:
						$rteObject = BackendUtility::RTEgetObj();
						$dummyFormEngine = new FormEngine();
						$rteResult = $rteObject->drawRTE(
							$dummyFormEngine,
							$table,
							$fieldName,
							$row,
							$parameterArray,
							$specialConfiguration,
							$rteSetupConfiguration,
							$rteTcaTypeValue,
							'',
							$tsConfigPid,
							$this->globalOptions,
							$this->initializeResultArray()
						);
						// This is a compat layer for "other" RTE's: If the result is not an array, it is the html string,
						// otherwise it is a structure similar to our casual return array
						// @todo: This interface needs a full re-definition, RTE should probably be its own type in the
						// @todo: end, and other RTE implementations could then just override this.
						if (is_array($rteResult)) {
							$html = $rteResult['html'];
							$rteResult['html'] = '';
							$resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $rteResult);
						} else {
							$html = $rteResult;
						}

						// Wizard
						$html = $this->renderWizards(
							array($html, $altItem),
							$config['wizards'],
							$table,
							$row,
							$fieldName,
							$parameterArray,
							$parameterArray['itemFormElName'],
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
				$html = '<p><em>' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.noRTEfound')) . '</em></p>';
			} else {
				// validation
				foreach ($evalList as $func) {
					if ($func === 'required') {
						$resultArray['requiredFields'][$table . '_' . $row['uid'] . '_' . $fieldName] = $parameterArray['itemFormElName'];
					} else {
						// Pair hook to the one in \TYPO3\CMS\Core\DataHandling\DataHandler::checkValue_input_Eval()
						// and \TYPO3\CMS\Core\DataHandling\DataHandler::checkValue_text_Eval()
						$evalObj = GeneralUtility::getUserObj($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][$func] . ':&' . $func);
						if (is_object($evalObj) && method_exists($evalObj, 'deevaluateFieldValue')) {
							$_params = array(
								'value' => $parameterArray['itemFormElValue']
							);
							$parameterArray['itemFormElValue'] = $evalObj->deevaluateFieldValue($_params);
						}
					}
				}

				// calculate classes
				$classes = array();
				$classes[] = 'form-control';
				$classes[] = 't3js-formengine-textarea';
				if ($specialConfiguration['fixed-font']) {
					$classes[] = 'text-monospace';
				}
				if ($specialConfiguration['enable-tab']) {
					$classes[] = 'enable-tab';
				}

				// calculate styles
				$styles = array();
				// add the max-height from the users' preference to it
				$maximumHeight = (int)$backendUser->uc['resizeTextareas_MaxHeight'];
				if ($maximumHeight > 0) {
					$styles[] = 'max-height: ' . $maximumHeight . 'px';
				}

				// calculate attributes
				$attributes = array();
				$attributes['id'] = str_replace('.', '', uniqid('formengine-textarea-', TRUE));
				$attributes['name'] = $parameterArray['itemFormElName'];
				if (!empty($styles)) {
					$attributes['style'] = implode(' ', $styles);
				}
				if (!empty($classes)) {
					$attributes['class'] = implode(' ', $classes);
				}
				$attributes['rows'] = $rows;
				$attributes['wrap'] = $specialConfiguration['nowrap'] ? 'off' : ($config['wrap'] ?: 'virtual');
				$attributes['onChange'] = implode('', $parameterArray['fieldChangeFunc']);
				if (isset($config['max']) && (int)$config['max'] > 0) {
					$attributes['maxlength'] = (int)$config['max'];
				}
				$attributeString = '';
				foreach ($attributes as $attributeName => $attributeValue) {
					$attributeString .= ' '. $attributeName . '="' . htmlspecialchars($attributeValue) . '"';
				}

				// Build the textarea
				$placeholderValue = $this->getPlaceholderValue($table, $config, $row);
				$placeholderAttribute = '';
				if (!empty($placeholderValue)) {
					$placeholderAttribute = ' placeholder="' . htmlspecialchars(trim($languageService->sL($placeholderValue))) . '" ';
				}

				$html .= '<textarea'
					. $attributeString
					. $placeholderAttribute
					. $parameterArray['onFocus']
					. '>' . GeneralUtility::formatForTextarea($parameterArray['itemFormElValue']) . '</textarea>';

				// Wrap a wizard around the item?
				$html = $this->renderWizards(
					array($html, $altItem),
					$config['wizards'],
					$table,
					$row,
					$fieldName,
					$parameterArray,
					$parameterArray['itemFormElName'],
					$specialConfiguration,
					$rteWouldHaveBeenLoaded
				);

				$maximumWidth = (int)$this->formMaxWidth($cols);
				$html = '<div class="form-control-wrap"' . ($maximumWidth ? ' style="max-width: ' . $maximumWidth . 'px"' : '') . '>' . $html . '</div>';
			}
		}
		$resultArray['html'] = $html;
		return $resultArray;
	}

	/**
	 * @return BackendUserAuthentication
	 */
	protected function getBackendUserAuthentication() {
		return $GLOBALS['BE_USER'];
	}

}
