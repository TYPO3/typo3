<?php
namespace TYPO3\CMS\Backend\Form\Container;

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

use TYPO3\CMS\Backend\Form\ElementConditionMatcher;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Utility\DiffUtility;
use TYPO3\CMS\Backend\Form\InlineStackProcessor;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Backend\Form\Element\NoneElement;

/**
 * Container around a "single field".
 *
 * This container is the last one in the chain before processing is hand over to single element classes.
 * If a single field is of type flex or inline, it however creates FlexFormContainer or InlineControlContainer.
 *
 * The container does various checks and processing for a given single fields, for example it resolves
 * display conditions and the HTML to compare compare different languages.
 */
class SingleFieldContainer extends AbstractContainer {

	/**
	 * Entry method
	 *
	 * @return array As defined in initializeResultArray() of AbstractNode
	 */
	public function render() {
		$backendUser = $this->getBackendUserAuthentication();
		$languageService = $this->getLanguageService();
		$resultArray = $this->initializeResultArray();

		$table = $this->globalOptions['table'];
		$row = $this->globalOptions['databaseRow'];
		$fieldName = $this->globalOptions['fieldName'];

		if (!is_array($GLOBALS['TCA'][$table]['columns'][$fieldName])) {
			return $resultArray;
		}

		$parameterArray = array();
		$parameterArray['extra'] = $this->globalOptions['fieldExtra'];
		$parameterArray['fieldConf'] = $GLOBALS['TCA'][$table]['columns'][$fieldName];

		// A couple of early returns in case the field should not be rendered
		// Check if this field is configured and editable according to exclude fields and other configuration
		if (
			$parameterArray['fieldConf']['exclude'] && !$backendUser->check('non_exclude_fields', $table . ':' . $fieldName)
			|| $parameterArray['fieldConf']['config']['type'] === 'passthrough'
			|| !$backendUser->isRTE() && $parameterArray['fieldConf']['config']['showIfRTE']
			|| $GLOBALS['TCA'][$table]['ctrl']['languageField'] && !$parameterArray['fieldConf']['l10n_display'] && $parameterArray['fieldConf']['l10n_mode'] === 'exclude' && ($row[$GLOBALS['TCA'][$table]['ctrl']['languageField']] > 0)
			|| $GLOBALS['TCA'][$table]['ctrl']['languageField'] && $this->globalOptions['localizationMode'] && $this->globalOptions['localizationMode'] !== $parameterArray['fieldConf']['l10n_cat']
			|| $this->inlineFieldShouldBeSkipped()
		) {
			return $resultArray;
		}
		// Evaluate display condition
		if ($parameterArray['fieldConf']['displayCond'] && is_array($row)) {
			// @todo: isn't $row = array() safe somewhere above already?
			/** @var $elementConditionMatcher ElementConditionMatcher */
			$elementConditionMatcher = GeneralUtility::makeInstance(ElementConditionMatcher::class);
			if (!$elementConditionMatcher->match($parameterArray['fieldConf']['displayCond'], $row)) {
				return $resultArray;
			}
		}
		// Fetching the TSconfig for the current table/field. This includes the $row which means that
		$parameterArray['fieldTSConfig'] = FormEngineUtility::getTSconfigForTableRow($table, $row, $fieldName);
		if ($parameterArray['fieldTSConfig']['disabled']) {
			return $resultArray;
		}

		// Override fieldConf by fieldTSconfig:
		$parameterArray['fieldConf']['config'] = FormEngineUtility::overrideFieldConf($parameterArray['fieldConf']['config'], $parameterArray['fieldTSConfig']);
		$parameterArray['itemFormElName'] = $this->globalOptions['prependFormFieldNames'] . '[' . $table . '][' . $row['uid'] . '][' . $fieldName . ']';
		// Form field name, in case of file uploads
		$parameterArray['itemFormElName_file'] = $this->globalOptions['prependFormFieldNames_file'] . '[' . $table . '][' . $row['uid'] . '][' . $fieldName . ']';
		// Form field name, to activate elements
		// If the "eval" list contains "null", elements can be deactivated which results in storing NULL to database
		$parameterArray['itemFormElNameActive'] = $this->globalOptions['prependFormFieldNamesActive'] . '[' . $table . '][' . $row['uid'] . '][' . $fieldName . ']';
		$parameterArray['itemFormElID'] = $this->globalOptions['prependFormFieldNames'] . '_' . $table . '_' . $row['uid'] . '_' . $fieldName;

		// The value to show in the form field.
		$parameterArray['itemFormElValue'] = $row[$fieldName];
		// Set field to read-only if configured for translated records to show default language content as readonly
		if ($parameterArray['fieldConf']['l10n_display']
			&& GeneralUtility::inList($parameterArray['fieldConf']['l10n_display'], 'defaultAsReadonly')
			&& $row[$GLOBALS['TCA'][$table]['ctrl']['languageField']] > 0
		) {
			$parameterArray['fieldConf']['config']['readOnly'] = TRUE;
			$parameterArray['itemFormElValue'] = $this->globalOptions['defaultLanguageData'][$table . ':' . $row['uid']][$fieldName];
		}

		if (strpos($GLOBALS['TCA'][$table]['ctrl']['type'], ':') === FALSE) {
			$typeField = $GLOBALS['TCA'][$table]['ctrl']['type'];
		} else {
			$typeField = substr($GLOBALS['TCA'][$table]['ctrl']['type'], 0, strpos($GLOBALS['TCA'][$table]['ctrl']['type'], ':'));
		}
		// Create a JavaScript code line which will ask the user to save/update the form due to changing the element.
		// This is used for eg. "type" fields and others configured with "requestUpdate"
		if (
			!empty($GLOBALS['TCA'][$table]['ctrl']['type'])
			&& $fieldName === $typeField
			|| !empty($GLOBALS['TCA'][$table]['ctrl']['requestUpdate'])
			&& GeneralUtility::inList(str_replace(' ', '', $GLOBALS['TCA'][$table]['ctrl']['requestUpdate']), $fieldName)
		) {
			if ($backendUser->jsConfirmation(1)) {
				$alertMsgOnChange = 'if (confirm(TBE_EDITOR.labels.onChangeAlert) && TBE_EDITOR.checkSubmit(-1)){ TBE_EDITOR.submitForm() };';
			} else {
				$alertMsgOnChange = 'if (TBE_EDITOR.checkSubmit(-1)){ TBE_EDITOR.submitForm() };';
			}
		} else {
			$alertMsgOnChange = '';
		}


		if (in_array($fieldName, $this->globalOptions['hiddenFieldListArray'], TRUE)) {
			// Render as a hidden field if this field had a forced value in overrideVals
			// @todo: This is an ugly concept ... search for overrideVals and defVals for a full picture of this madness
			$resultArray = $this->initializeResultArray();
			// This hidden field can not just be returned as casual html since upper containers will then render a label and wrapping stuff - this is not wanted here
			$resultArray['additionalHiddenFields'][] = '<input type="hidden" name="' . $parameterArray['itemFormElName'] . '" value="' . htmlspecialchars($parameterArray['itemFormElValue']) . '" />';
		} else {
			// JavaScript code for event handlers:
			$parameterArray['fieldChangeFunc'] = array();
			$parameterArray['fieldChangeFunc']['TBE_EDITOR_fieldChanged'] = 'TBE_EDITOR.fieldChanged(' . GeneralUtility::quoteJSvalue($table) . ',' . GeneralUtility::quoteJSvalue($row['uid']) . ',' . GeneralUtility::quoteJSvalue($fieldName) . ',' . GeneralUtility::quoteJSvalue($parameterArray['itemFormElName']) . ');';
			$parameterArray['fieldChangeFunc']['alert'] = $alertMsgOnChange;

			// If this is the child of an inline type and it is the field creating the label
			if ($this->isInlineChildAndLabelField($table, $fieldName)) {
				/** @var InlineStackProcessor $inlineStackProcessor */
				$inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
				$inlineStackProcessor->initializeByGivenStructure($this->globalOptions['inlineStructure']);
				$inlineDomObjectId = $inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->globalOptions['inlineFirstPid']);
				$inlineObjectId = implode(
					'-',
					array(
						$inlineDomObjectId,
						$table,
						$row['uid']
					)
				);
				$parameterArray['fieldChangeFunc']['inline'] = 'inline.handleChangedField(\'' . $parameterArray['itemFormElName'] . '\',\'' . $inlineObjectId . '\');';
			}

			// Based on the type of the item, call a render function on a child element
			$options = $this->globalOptions;
			$options['parameterArray'] = $parameterArray;
			/** @var NodeFactory $childFactory */
			$childFactory = GeneralUtility::makeInstance(NodeFactory::class);
			$childElement = $childFactory->create($parameterArray['fieldConf']['config']['type']);
			$resultArray = $childElement->setGlobalOptions($options)->render();
			$html = $resultArray['html'];

			// Add language + diff
			$renderLanguageDiff = TRUE;
			if (
				$parameterArray['fieldConf']['l10n_display'] && (GeneralUtility::inList($parameterArray['fieldConf']['l10n_display'], 'hideDiff')
				|| GeneralUtility::inList($parameterArray['fieldConf']['l10n_display'], 'defaultAsReadonly'))
			) {
				$renderLanguageDiff = FALSE;
			}
			if ($renderLanguageDiff) {
				$html = $this->renderDefaultLanguageContent($table, $fieldName, $row, $html);
				$html = $this->renderDefaultLanguageDiff($table, $fieldName, $row, $html);
			}

			if (isset($parameterArray['fieldConf']['config']['mode']) && $parameterArray['fieldConf']['config']['mode'] === 'useOrOverridePlaceholder') {
				$placeholder = $this->getPlaceholderValue($table, $parameterArray['fieldConf']['config'], $row);
				$onChange = 'typo3form.fieldTogglePlaceholder(' . GeneralUtility::quoteJSvalue($parameterArray['itemFormElName']) . ', !this.checked)';
				$checked = $parameterArray['itemFormElValue'] === NULL ? '' : ' checked="checked"';

				$resultArray['additionalJavaScriptPost'][] = 'typo3form.fieldTogglePlaceholder('
					. GeneralUtility::quoteJSvalue($parameterArray['itemFormElName']) . ', ' . ($checked ? 'false' : 'true') . ');';

				// Renders a input or textarea field depending on type of "parent"
				$options = array();
				$options['databaseRow'] = array();
				$options['table'] = '';
				$options['parameterArray'] = $parameterArray;
				$options['parameterArray']['itemFormElValue'] = GeneralUtility::fixed_lgd_cs($placeholder, 30);
				/** @var NoneElement $noneElement */
				$noneElement = GeneralUtility::makeInstance(NoneElement::class);
				$noneElementResult = $noneElement->setGlobalOptions($options)->render();
				$noneElementHtml = $noneElementResult['html'];

				$html = '
				<input type="hidden" name="' . htmlspecialchars($parameterArray['itemFormElNameActive']) . '" value="0" />
				<div class="checkbox">
					<label>
						<input type="checkbox" name="' . htmlspecialchars($parameterArray['itemFormElNameActive']) . '" value="1" id="tce-forms-textfield-use-override-' . $fieldName . '-' . $row['uid'] . '" onchange="' . htmlspecialchars($onChange) . '"' . $checked . ' />
						' . sprintf($languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.placeholder.override'), BackendUtility::getRecordTitlePrep($placeholder, 20)) . '
					</label>
				</div>
				<div class="t3js-formengine-placeholder-placeholder">
					' . $noneElementHtml . '
				</div>
				<div class="t3js-formengine-placeholder-formfield">' . $html . '</div>';
			}

			$resultArray['html'] = $html;
		}
		return $resultArray;
	}

	/**
	 * Renders the display of default language record content around current field.
	 * Will render content if any is found in the internal array, $this->defaultLanguageData,
	 * depending on registerDefaultLanguageData() being called prior to this.
	 *
	 * @param string $table Table name of the record being edited
	 * @param string $field Field name represented by $item
	 * @param array $row Record array of the record being edited
	 * @param string $item HTML of the form field. This is what we add the content to.
	 * @return string Item string returned again, possibly with the original value added to.
	 */
	protected function renderDefaultLanguageContent($table, $field, $row, $item) {
		if (is_array($this->globalOptions['defaultLanguageData'][$table . ':' . $row['uid']])) {
			$defaultLanguageValue = BackendUtility::getProcessedValue(
				$table,
				$field,
				$this->globalOptions['defaultLanguageData'][$table . ':' . $row['uid']][$field],
				0,
				1,
				FALSE,
				$this->globalOptions['defaultLanguageData'][$table . ':' . $row['uid']]['uid']
			);
			$fieldConfig = $GLOBALS['TCA'][$table]['columns'][$field];
			// Don't show content if it's for IRRE child records:
			if ($fieldConfig['config']['type'] !== 'inline') {
				if ($defaultLanguageValue !== '') {
					$item .= '<div class="t3-form-original-language">' . FormEngineUtility::getLanguageIcon($table, $row, 0)
						. $this->getMergeBehaviourIcon($fieldConfig['l10n_mode'])
						. $this->previewFieldValue($defaultLanguageValue, $fieldConfig, $field) . '</div>';
				}
				$additionalPreviewLanguages = $this->globalOptions['additionalPreviewLanguages'];
				foreach ($additionalPreviewLanguages as $previewLanguage) {
					$defaultLanguageValue = BackendUtility::getProcessedValue(
						$table,
						$field,
						$this->globalOptions['additionalPreviewLanguageData'][$table . ':' . $row['uid']][$previewLanguage['uid']][$field],
						0,
						1
					);
					if ($defaultLanguageValue !== '') {
						$item .= '<div class="t3-form-original-language">'
							. FormEngineUtility::getLanguageIcon($table, $row, ('v' . $previewLanguage['ISOcode']))
							. $this->getMergeBehaviourIcon($fieldConfig['l10n_mode'])
							. $this->previewFieldValue($defaultLanguageValue, $fieldConfig, $field) . '</div>';
					}
				}
			}
		}
		return $item;
	}

	/**
	 * Renders an icon to indicate the way the translation and the original is merged (if this is relevant).
	 *
	 * If a field is defined as 'mergeIfNotBlank' this is useful information for an editor. He/she can leave the field blank and
	 * the original value will be used. Without this hint editors are likely to copy the contents even if it is not necessary.
	 *
	 * @param string $l10nMode Localization mode from TCA
	 * @return string
	 */
	protected function getMergeBehaviourIcon($l10nMode) {
		$icon = '';
		if ($l10nMode === 'mergeIfNotBlank') {
			$icon = IconUtility::getSpriteIcon(
				'actions-edit-merge-localization',
				array('title' => $this->getLanguageService()->sL('LLL:EXT:lang/locallang_misc.xlf:localizeMergeIfNotBlank'))
			);
		}
		return $icon;
	}

	/**
	 * Renders the diff-view of default language record content compared with what the record was originally translated from.
	 * Will render content if any is found in the internal array, $this->defaultLanguageData,
	 * depending on registerDefaultLanguageData() being called prior to this.
	 *
	 * @param string $table Table name of the record being edited
	 * @param string $field Field name represented by $item
	 * @param array $row Record array of the record being edited
	 * @param string  $item HTML of the form field. This is what we add the content to.
	 * @return string Item string returned again, possibly with the original value added to.
	 */
	protected function renderDefaultLanguageDiff($table, $field, $row, $item) {
		if (is_array($this->globalOptions['defaultLanguageDataDiff'][$table . ':' . $row['uid']])) {
			// Initialize:
			$dLVal = array(
				'old' => $this->globalOptions['defaultLanguageDataDiff'][$table . ':' . $row['uid']],
				'new' => $this->globalOptions['defaultLanguageData'][$table . ':' . $row['uid']]
			);
			// There must be diff-data:
			if (isset($dLVal['old'][$field])) {
				if ((string)$dLVal['old'][$field] !== (string)$dLVal['new'][$field]) {
					// Create diff-result:
					$diffUtility = GeneralUtility::makeInstance(DiffUtility::class);
					$diffres = $diffUtility->makeDiffDisplay(
						BackendUtility::getProcessedValue($table, $field, $dLVal['old'][$field], 0, 1),
						BackendUtility::getProcessedValue($table, $field, $dLVal['new'][$field], 0, 1)
					);
					$item .= '<div class="t3-form-original-language-diff">
						<div class="t3-form-original-language-diffheader">' .
							htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.changeInOrig')) .
						'</div>
						<div class="t3-form-original-language-diffcontent">' . $diffres . '</div>
					</div>';
				}
			}
		}
		return $item;
	}

	/**
	 * Checks if the $table is the child of a inline type AND the $field is the label field of this table.
	 * This function is used to dynamically update the label while editing. This has no effect on labels,
	 * that were processed by a FormEngine-hook on saving.
	 *
	 * @param string $table The table to check
	 * @param string $field The field on this table to check
	 * @return bool Is inline child and field is responsible for the label
	 */
	protected function isInlineChildAndLabelField($table, $field) {
		/** @var InlineStackProcessor $inlineStackProcessor */
		$inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
		$inlineStackProcessor->initializeByGivenStructure($this->globalOptions['inlineStructure']);
		$level = $inlineStackProcessor->getStructureLevel(-1);
		if ($level['config']['foreign_label']) {
			$label = $level['config']['foreign_label'];
		} else {
			$label = $GLOBALS['TCA'][$table]['ctrl']['label'];
		}
		return $level['config']['foreign_table'] === $table && $label == $field ? TRUE : FALSE;
	}

	/**
	 * Rendering of inline fields should be skipped under certain circumstances
	 *
	 * @return boolean TRUE if field should be skipped based on inline configuration
	 */
	protected function inlineFieldShouldBeSkipped() {
		$table = $this->globalOptions['table'];
		$row = $this->globalOptions['databaseRow'];
		$fieldName = $this->globalOptions['fieldName'];
		$fieldConfig = $GLOBALS['TCA'][$table]['columns'][$fieldName]['config'];

		/** @var InlineStackProcessor $inlineStackProcessor */
		$inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
		$inlineStackProcessor->initializeByGivenStructure($this->globalOptions['inlineStructure']);
		$structureDepth = $inlineStackProcessor->getStructureDepth();

		$skipThisField = FALSE;
		if ($structureDepth > 0) {
			$searchArray = array(
				'%OR' => array(
					'config' => array(
						0 => array(
							'%AND' => array(
								'foreign_table' => $table,
								'%OR' => array(
									'%AND' => array(
										'appearance' => array('useCombination' => TRUE),
										'foreign_selector' => $fieldName
									),
									'MM' => $fieldConfig['MM']
								)
							)
						),
						1 => array(
							'%AND' => array(
								'foreign_table' => $fieldConfig['foreign_table'],
								'foreign_selector' => $fieldConfig['foreign_field']
							)
						)
					)
				)
			);
			// Get the parent record from structure stack
			$level = $inlineStackProcessor->getStructureLevel(-1);
			// If we have symmetric fields, check on which side we are and hide fields, that are set automatically:
			if (RelationHandler::isOnSymmetricSide($level['uid'], $level['config'], $row)) {
				$searchArray['%OR']['config'][0]['%AND']['%OR']['symmetric_field'] = $fieldName;
				$searchArray['%OR']['config'][0]['%AND']['%OR']['symmetric_sortby'] = $fieldName;
			} else {
				$searchArray['%OR']['config'][0]['%AND']['%OR']['foreign_field'] = $fieldName;
				$searchArray['%OR']['config'][0]['%AND']['%OR']['foreign_sortby'] = $fieldName;
			}
			$skipThisField = $this->arrayCompareComplex($level, $searchArray);
		}
		return $skipThisField;
	}

	/**
	 * Handles complex comparison requests on an array.
	 * A request could look like the following:
	 *
	 * $searchArray = array(
	 *   '%AND'	=> array(
	 *     'key1'	=> 'value1',
	 *     'key2'	=> 'value2',
	 *     '%OR'	=> array(
	 *       'subarray' => array(
	 *         'subkey' => 'subvalue'
	 *       ),
	 *       'key3'	=> 'value3',
	 *       'key4'	=> 'value4'
	 *     )
	 *   )
	 * );
	 *
	 * It is possible to use the array keys '%AND.1', '%AND.2', etc. to prevent
	 * overwriting the sub-array. It could be necessary, if you use complex comparisons.
	 *
	 * The example above means, key1 *AND* key2 (and their values) have to match with
	 * the $subjectArray and additional one *OR* key3 or key4 have to meet the same
	 * condition.
	 * It is also possible to compare parts of a sub-array (e.g. "subarray"), so this
	 * function recurses down one level in that sub-array.
	 *
	 * @param array $subjectArray The array to search in
	 * @param array $searchArray The array with keys and values to search for
	 * @param string $type Use '%AND' or '%OR' for comparison
	 * @return bool The result of the comparison
	 */
	protected function arrayCompareComplex($subjectArray, $searchArray, $type = '') {
		$localMatches = 0;
		$localEntries = 0;
		if (is_array($searchArray) && count($searchArray)) {
			// If no type was passed, try to determine
			if (!$type) {
				reset($searchArray);
				$type = key($searchArray);
				$searchArray = current($searchArray);
			}
			// We use '%AND' and '%OR' in uppercase
			$type = strtoupper($type);
			// Split regular elements from sub elements
			foreach ($searchArray as $key => $value) {
				$localEntries++;
				// Process a sub-group of OR-conditions
				if ($key === '%OR') {
					$localMatches += $this->arrayCompareComplex($subjectArray, $value, '%OR') ? 1 : 0;
				} elseif ($key === '%AND') {
					$localMatches += $this->arrayCompareComplex($subjectArray, $value, '%AND') ? 1 : 0;
				} elseif (is_array($value) && $this->isAssociativeArray($searchArray)) {
					$localMatches += $this->arrayCompareComplex($subjectArray[$key], $value, $type) ? 1 : 0;
				} elseif (is_array($value)) {
					$localMatches += $this->arrayCompareComplex($subjectArray, $value, $type) ? 1 : 0;
				} else {
					if (isset($subjectArray[$key]) && isset($value)) {
						// Boolean match:
						if (is_bool($value)) {
							$localMatches += !($subjectArray[$key] xor $value) ? 1 : 0;
						} elseif (is_numeric($subjectArray[$key]) && is_numeric($value)) {
							$localMatches += $subjectArray[$key] == $value ? 1 : 0;
						} else {
							$localMatches += $subjectArray[$key] === $value ? 1 : 0;
						}
					}
				}
				// If one or more matches are required ('OR'), return TRUE after the first successful match
				if ($type === '%OR' && $localMatches > 0) {
					return TRUE;
				}
				// If all matches are required ('AND') and we have no result after the first run, return FALSE
				if ($type === '%AND' && $localMatches == 0) {
					return FALSE;
				}
			}
		}
		// Return the result for '%AND' (if nothing was checked, TRUE is returned)
		return $localEntries == $localMatches ? TRUE : FALSE;
	}

	/**
	 * Checks whether an object is an associative array.
	 *
	 * @param mixed $object The object to be checked
	 * @return bool Returns TRUE, if the object is an associative array
	 */
	protected function isAssociativeArray($object) {
		return is_array($object) && count($object) && array_keys($object) !== range(0, sizeof($object) - 1) ? TRUE : FALSE;
	}

	/**
	 * @return BackendUserAuthentication
	 */
	protected function getBackendUserAuthentication() {
		return $GLOBALS['BE_USER'];
	}

	/**
	 * @return LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

}