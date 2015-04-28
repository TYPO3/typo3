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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Type\Bitmask\JsConfirmation;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * The container handles single elements.
 *
 * This one is called by FlexFormTabsContainer, FlexFormNoTabsContainer or FlexFormContainerContainer.
 * For single fields, the code is similar to SingleFieldContainer, processing will end up in single
 * element classes depending on specific type of an element. Additionally, it determines if a
 * section is handled and hands over to FlexFormSectionContainer in this case.
 */
class FlexFormElementContainer extends AbstractContainer {

	/**
	 * Entry method
	 *
	 * @return array As defined in initializeResultArray() of AbstractNode
	 */
	public function render() {
		$table = $this->globalOptions['table'];
		$row = $this->globalOptions['databaseRow'];
		$fieldName = $this->globalOptions['fieldName'];
		$flexFormDataStructureArray = $this->globalOptions['flexFormDataStructureArray'];
		$flexFormRowData = $this->globalOptions['flexFormRowData'];
		$flexFormCurrentLanguage = $this->globalOptions['flexFormCurrentLanguage'];
		$flexFormNoEditDefaultLanguage = $this->globalOptions['flexFormNoEditDefaultLanguage'];
		$flexFormFormPrefix = $this->globalOptions['flexFormFormPrefix'];
		$parameterArray = $this->globalOptions['parameterArray'];

		$languageService = $this->getLanguageService();
		$resultArray = $this->initializeResultArray();
		foreach ($flexFormDataStructureArray as $flexFormFieldName => $flexFormFieldArray) {
			if (
				// No item array found at all
				!is_array($flexFormFieldArray)
				// Not a section or container and not a list of single items
				|| (!isset($flexFormFieldArray['type']) && !is_array($flexFormFieldArray['TCEforms']['config']))
			) {
				continue;
			}

			if ($flexFormFieldArray['type'] === 'array') {
				// Section
				if (empty($flexFormFieldArray['section'])) {
					$resultArray['html'] = LF . 'Section expected at ' . $flexFormFieldName . ' but not found';
					continue;
				}

				$sectionTitle = '';
				if (!empty($flexFormFieldArray['title'])) {
					$sectionTitle = $languageService->sL($flexFormFieldArray['title']);
				}

				$options = $this->globalOptions;
				$options['flexFormDataStructureArray'] = $flexFormFieldArray['el'];
				$options['flexFormRowData'] = is_array($flexFormRowData[$flexFormFieldName]['el']) ? $flexFormRowData[$flexFormFieldName]['el'] : array();
				$options['flexFormSectionType'] = $flexFormFieldName;
				$options['flexFormSectionTitle'] = $sectionTitle;
				/** @var FlexFormSectionContainer $sectionContainer */
				$sectionContainer = GeneralUtility::makeInstance(FlexFormSectionContainer::class);
				$sectionContainerResult = $sectionContainer->setGlobalOptions($options)->render();
				$resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $sectionContainerResult);
			} else {
				// Single element
				$vDEFkey = 'vDEF';

				$displayConditionResult = TRUE;
				if (!empty($flexFormFieldArray['TCEforms']['displayCond'])) {
					$conditionData = is_array($flexFormRowData) ? $flexFormRowData : array();
					$conditionData['parentRec'] = $row;
					/** @var $elementConditionMatcher ElementConditionMatcher */
					$elementConditionMatcher = GeneralUtility::makeInstance(ElementConditionMatcher::class);
					$displayConditionResult = $elementConditionMatcher->match($flexFormFieldArray['TCEforms']['displayCond'], $conditionData, $vDEFkey);
				}
				if (!$displayConditionResult) {
					continue;
				}

				// Set up options for single element
				$fakeParameterArray = array(
					'fieldConf' => array(
						'label' => $languageService->sL(trim($flexFormFieldArray['TCEforms']['label'])),
						'config' => $flexFormFieldArray['TCEforms']['config'],
						'defaultExtras' => $flexFormFieldArray['TCEforms']['defaultExtras'],
						'onChange' => $flexFormFieldArray['TCEforms']['onChange'],
					),
				);

				// Force a none field if default language can not be edited
				if ($flexFormNoEditDefaultLanguage && $flexFormCurrentLanguage === 'lDEF') {
					$fakeParameterArray['fieldConf']['config'] = array(
						'type' => 'none',
						'rows' => 2
					);
				}

				$alertMsgOnChange = '';
				if (
					$fakeParameterArray['fieldConf']['onChange'] === 'reload'
					|| !empty($GLOBALS['TCA'][$table]['ctrl']['type']) && $GLOBALS['TCA'][$table]['ctrl']['type'] === $flexFormFieldName
					|| !empty($GLOBALS['TCA'][$table]['ctrl']['requestUpdate']) && GeneralUtility::inList($GLOBALS['TCA'][$table]['ctrl']['requestUpdate'], $flexFormFieldName)
				) {
					if ($this->getBackendUserAuthentication()->jsConfirmation(JsConfirmation::TYPE_CHANGE)) {
						$alertMsgOnChange = 'if (confirm(TBE_EDITOR.labels.onChangeAlert) && TBE_EDITOR.checkSubmit(-1)){ TBE_EDITOR.submitForm() };';
					} else {
						$alertMsgOnChange = 'if (TBE_EDITOR.checkSubmit(-1)){ TBE_EDITOR.submitForm();}';
					}
				}
				$fakeParameterArray['fieldChangeFunc'] = $parameterArray['fieldChangeFunc'];
				if ($alertMsgOnChange) {
					$fakeParameterArray['fieldChangeFunc']['alert'] = $alertMsgOnChange;
				}

				$fakeParameterArray['onFocus'] = $parameterArray['onFocus'];
				$fakeParameterArray['label'] = $parameterArray['label'];
				$fakeParameterArray['itemFormElName'] = $parameterArray['itemFormElName'] . $flexFormFormPrefix . '[' . $flexFormFieldName . '][' . $vDEFkey . ']';
				$fakeParameterArray['itemFormElName_file'] = $parameterArray['itemFormElName_file'] . $flexFormFormPrefix . '[' . $flexFormFieldName . '][' . $vDEFkey . ']';
				$fakeParameterArray['itemFormElID'] = $fakeParameterArray['itemFormElName'];
				if (isset($flexFormRowData[$flexFormFieldName][$vDEFkey])) {
					$fakeParameterArray['itemFormElValue'] = $flexFormRowData[$flexFormFieldName][$vDEFkey];
				} else {
					$fakeParameterArray['itemFormElValue'] = $fakeParameterArray['fieldConf']['config']['default'];
				}

				$options = $this->globalOptions;
				$options['parameterArray'] = $fakeParameterArray;
				/** @var NodeFactory $nodeFactory */
				$nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);
				$child = $nodeFactory->create($flexFormFieldArray['TCEforms']['config']['type']);
				$childResult = $child->setGlobalOptions($options)->render();

				$theTitle = htmlspecialchars($fakeParameterArray['fieldConf']['label']);
				$defInfo = array();
				if (!$flexFormNoEditDefaultLanguage) {
					$previewLanguages = $this->globalOptions['additionalPreviewLanguages'];
					foreach ($previewLanguages as $previewLanguage) {
						$defInfo[] = '<div class="t3-form-original-language">';
						$defInfo[] = 	FormEngineUtility::getLanguageIcon($table, $row, ('v' . $previewLanguage['ISOcode']));
						$defInfo[] = 	$this->previewFieldValue($flexFormRowData[$flexFormFieldName][('v' . $previewLanguage['ISOcode'])], $fakeParameterArray['fieldConf'], $fieldName);
						$defInfo[] = '</div>';
					}
				}

				$languageIcon = '';
				if ($vDEFkey !== 'vDEF') {
					$languageIcon = FormEngineUtility::getLanguageIcon($table, $row, $vDEFkey);
				}
				// Possible line breaks in the label through xml: \n => <br/>, usage of nl2br() not possible, so it's done through str_replace (?!)
				$processedTitle = str_replace('\\n', '<br />', $theTitle);
				// @todo: Similar to the processing within SingleElementContainer ... use it from there?!
				$html = array();
				$html[] = '<div class="form-section">';
				$html[] = 	'<div class="form-group t3js-formengine-palette-field">';
				$html[] = 		'<label class="t3js-formengine-label">';
				$html[] = 			$languageIcon;
				$html[] = 			BackendUtility::wrapInHelp($parameterArray['_cshKey'], $flexFormFieldName, $processedTitle);
				$html[] = 		'</label>';
				$html[] = 		'<div class="t3js-formengine-field-item">';
				$html[] = 			$childResult['html'];
				$html[] = 			implode(LF, $defInfo);
				$html[] = 			$this->renderVDEFDiff($flexFormRowData[$flexFormFieldName], $vDEFkey);
				$html[]	= 		'</div>';
				$html[] = 	'</div>';
				$html[] = '</div>';

				$resultArray['html'] .= implode(LF, $html);
				$childResult['html'] = '';
				$resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $childResult);
			}
		}

		return $resultArray;
	}

	/**
	 * Renders the diff-view of vDEF fields in flex forms
	 *
	 * @param array $vArray Record array of the record being edited
	 * @param string $vDEFkey HTML of the form field. This is what we add the content to.
	 * @return string Item string returned again, possibly with the original value added to.
	 */
	protected function renderVDEFDiff($vArray, $vDEFkey) {
		$item = NULL;
		if (
			$GLOBALS['TYPO3_CONF_VARS']['BE']['flexFormXMLincludeDiffBase'] && isset($vArray[$vDEFkey . '.vDEFbase'])
			&& (string)$vArray[$vDEFkey . '.vDEFbase'] !== (string)$vArray['vDEF']
		) {
			// Create diff-result:
			$diffUtility = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Utility\DiffUtility::class);
			$diffres = $diffUtility->makeDiffDisplay($vArray[$vDEFkey . '.vDEFbase'], $vArray['vDEF']);
			$item = '<div class="typo3-TCEforms-diffBox">' . '<div class="typo3-TCEforms-diffBox-header">'
				. htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.changeInOrig')) . ':</div>' . $diffres . '</div>';
		}
		return $item;
	}

	/**
	 * @return LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

	/**
	 * @return BackendUserAuthentication
	 */
	protected function getBackendUserAuthentication() {
		return $GLOBALS['BE_USER'];
	}

}
