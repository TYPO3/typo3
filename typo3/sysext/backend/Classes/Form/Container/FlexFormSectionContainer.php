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

use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Backend\Form\NodeFactory;

/**
 * Handle flex form sections.
 *
 * This container is created by FlexFormElementContainer if a "single" element is in
 * fact a sections. For each existing section container it creates as FlexFormContainerContainer
 * to render its inner fields, additionally for each possible container a "template" of this
 * container type is rendered and added - to be added by JS to DOM on click on "new xy container".
 */
class FlexFormSectionContainer extends AbstractContainer {

	/**
	 * Entry method
	 *
	 * @return array As defined in initializeResultArray() of AbstractNode
	 */
	public function render() {
		$languageService = $this->getLanguageService();

		$flexFormFieldsArray = $this->globalOptions['flexFormDataStructureArray'];
		$flexFormRowData = $this->globalOptions['flexFormRowData'];
		$flexFormFieldIdentifierPrefix = $this->globalOptions['flexFormFieldIdentifierPrefix'];
		$flexFormSectionType = $this->globalOptions['flexFormSectionType'];
		$flexFormSectionTitle = $this->globalOptions['flexFormSectionTitle'];

		$userHasAccessToDefaultLanguage = $this->getBackendUserAuthentication()->checkLanguageAccess(0);

		$resultArray = $this->initializeResultArray();

		// Creating IDs for form fields:
		// It's important that the IDs "cascade" - otherwise we can't dynamically expand the flex form
		// because this relies on simple string substitution of the first parts of the id values.
		$flexFormFieldIdentifierPrefix = $flexFormFieldIdentifierPrefix . '-' . GeneralUtility::shortMd5(uniqid('id', TRUE));

		// Render each existing container
		foreach ($flexFormRowData as $flexFormContainerCounter => $existingSectionContainerData) {
			// @todo: This relies on the fact that "_TOGGLE" is *below* the real data in the saved xml structure
			if (is_array($existingSectionContainerData)) {
				$existingSectionContainerDataStructureType = key($existingSectionContainerData);
				$existingSectionContainerData = $existingSectionContainerData[$existingSectionContainerDataStructureType];
				$containerDataStructure = $flexFormFieldsArray[$existingSectionContainerDataStructureType];
				// There may be cases where a field is still in DB but does not exist in definition
				if (is_array($containerDataStructure)) {
					$sectionTitle = '';
					if (!empty($containerDataStructure['title'])) {
						$sectionTitle = $languageService->sL($containerDataStructure['title']);
					}

					$options = $this->globalOptions;
					$options['flexFormRowData'] = $existingSectionContainerData['el'];
					$options['flexFormDataStructureArray'] = $containerDataStructure['el'];
					$options['flexFormFieldIdentifierPrefix'] = $flexFormFieldIdentifierPrefix;
					$options['flexFormFormPrefix'] = $this->globalOptions['flexFormFormPrefix'] . '[' . $flexFormSectionType . ']' . '[el]';
					$options['flexFormContainerName'] = $existingSectionContainerDataStructureType;
					$options['flexFormContainerCounter'] = $flexFormContainerCounter;
					$options['flexFormContainerTitle'] = $sectionTitle;
					$options['flexFormContainerElementCollapsed'] = (bool)$existingSectionContainerData['el']['_TOGGLE'];
					$options['renderType'] = 'flexFormContainerContainer';
					/** @var NodeFactory $nodeFactory */
					$nodeFactory = $this->globalOptions['nodeFactory'];
					$flexFormContainerContainerResult = $nodeFactory->create($options)->render();
					$resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $flexFormContainerContainerResult);
				}
			}
		}

		// "New container" handling: Creates a "template" of each possible container and stuffs it
		// somewhere into DOM to be handled with JS magic.
		// Fun part: Handle the fact that such things may be set for children
		$containerTemplatesHtml = array();
		foreach ($flexFormFieldsArray as $flexFormContainerName => $flexFormFieldDefinition) {
			$containerTemplateHtml = array();
			$sectionTitle = '';
			if (!empty($flexFormFieldDefinition['title'])) {
				$sectionTitle = $languageService->sL($flexFormFieldDefinition['title']);
			}

			$options = $this->globalOptions;
			$options['flexFormRowData'] = array();
			$options['flexFormDataStructureArray'] = $flexFormFieldDefinition['el'];
			$options['flexFormFieldIdentifierPrefix'] = $flexFormFieldIdentifierPrefix;
			$options['flexFormFormPrefix'] = $this->globalOptions['flexFormFormPrefix'] . '[' . $flexFormSectionType . ']' . '[el]';
			$options['flexFormContainerName'] = $flexFormContainerName;
			$options['flexFormContainerCounter'] = $flexFormFieldIdentifierPrefix . '-form';
			$options['flexFormContainerTitle'] = $sectionTitle;
			$options['flexFormContainerElementCollapsed'] = FALSE;
			$options['renderType'] = 'flexFormContainerContainer';
			/** @var NodeFactory $nodeFactory */
			$nodeFactory = $this->globalOptions['nodeFactory'];
			$flexFormContainerContainerTemplateResult = $nodeFactory->create($options)->render();

			$uniqueId = str_replace('.', '', uniqid('idvar', TRUE));
			$identifierPrefixJs = 'replace(/' . $flexFormFieldIdentifierPrefix . '-/g,"' . $flexFormFieldIdentifierPrefix . '-"+' . $uniqueId . '+"-")';
			$identifierPrefixJs .= '.replace(/(tceforms-(datetime|date)field-)/g,"$1" + (new Date()).getTime())';

			$onClickInsert = array();
			$onClickInsert[] = 'var ' . $uniqueId . ' = "' . 'idx"+(new Date()).getTime();';
			$onClickInsert[] = 'TYPO3.jQuery(' . json_encode($flexFormContainerContainerTemplateResult['html']) . '.' . $identifierPrefixJs . ').insertAfter(TYPO3.jQuery("#' . $flexFormFieldIdentifierPrefix . '"));';
			$onClickInsert[] = 'TYPO3.jQuery("#' . $flexFormFieldIdentifierPrefix . '").t3FormEngineFlexFormElement();';
			$onClickInsert[] = 'eval(unescape("' . rawurlencode(implode(';', $flexFormContainerContainerTemplateResult['additionalJavaScriptPost'])) . '").' . $identifierPrefixJs . ');';
			$onClickInsert[] = 'TBE_EDITOR.addActionChecks("submit", unescape("' . rawurlencode(implode(';', $flexFormContainerContainerTemplateResult['additionalJavaScriptSubmit'])) . '").' . $identifierPrefixJs . ');';
			$onClickInsert[] = 'TYPO3.FormEngine.reinitialize();';
			$onClickInsert[] = 'return false;';

			$containerTemplateHtml[] = '<a href="#" onclick="' . htmlspecialchars(implode(LF, $onClickInsert)) . '">';
			$containerTemplateHtml[] = 	IconUtility::getSpriteIcon('actions-document-new');
			$containerTemplateHtml[] = 	htmlspecialchars(GeneralUtility::fixed_lgd_cs($sectionTitle, 30));
			$containerTemplateHtml[] = '</a>';
			$containerTemplatesHtml[] = implode(LF, $containerTemplateHtml);

			$flexFormContainerContainerTemplateResult['html'] = '';
			$flexFormContainerContainerTemplateResult['additionalJavaScriptPost'] = array();
			$flexFormContainerContainerTemplateResult['additionalJavaScriptSubmit'] = array();

			$resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $flexFormContainerContainerTemplateResult);
		}

		// Create new elements links
		$createElementsHtml = array();
		if ($userHasAccessToDefaultLanguage) {
			$createElementsHtml[] = '<div class="t3-form-field-add-flexsection">';
			$createElementsHtml[] = 	'<strong>';
			$createElementsHtml[] = 		$languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.addnew', TRUE) . ':';
			$createElementsHtml[] = 	'</strong>';
			$createElementsHtml[] = 	implode('|', $containerTemplatesHtml);
			$createElementsHtml[] = '</div>';
		}

		// Wrap child stuff
		$toggleAll = $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.toggleall', TRUE);
		$html = array();
		$html[] = '<div class="t3-form-field-container t3-form-flex">';
		$html[] = 	'<div class="t3-form-field-label-flexsection">';
		$html[] = 		'<strong>';
		$html[] = 			htmlspecialchars($flexFormSectionTitle);
		$html[] = 		'</strong>';
		$html[] = 	'</div>';
		$html[] = 	'<div class="t3-form-field-toggle-flexsection t3-form-flexsection-toggle">';
		$html[] = 		'<a href="#">';
		$html[] = 			IconUtility::getSpriteIcon('actions-move-right', array('title' => $toggleAll)) . $toggleAll;
		$html[] = 		'</a>';
		$html[] = 	'</div>';
		$html[] = 	'<div';
		$html[] = 		'id="' . $flexFormFieldIdentifierPrefix . '"';
		$html[] = 		'class="t3-form-field-container-flexsection t3-flex-container"';
		$html[] = 		'data-t3-flex-allow-restructure="' . ($userHasAccessToDefaultLanguage ? '1' : '0') . '"';
		$html[] = 	'>';
		$html[] = 		$resultArray['html'];
		$html[] = 	'</div>';
		$html[] = 	implode(LF, $createElementsHtml);
		$html[] = '</div>';

		$resultArray['html'] = implode(LF, $html);

		return $resultArray;
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
