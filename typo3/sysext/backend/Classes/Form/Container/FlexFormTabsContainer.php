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

use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Handle flex forms that have tabs (multiple "sheets").
 *
 * This container is called by FlexFormLanguageContainer. It resolves each
 * sheet and hands rendering of single sheet content over to FlexFormElementContainer.
 */
class FlexFormTabsContainer extends AbstractContainer {

	/**
	 * Entry method
	 *
	 * @return array As defined in initializeResultArray() of AbstractNode
	 */
	public function render() {
		$languageService = $this->getLanguageService();
		$docTemplate = $this->getDocumentTemplate();

		$table = $this->globalOptions['table'];
		$row = $this->globalOptions['databaseRow'];
		$fieldName = $this->globalOptions['fieldName']; // field name of the flex form field in DB
		$parameterArray = $this->globalOptions['parameterArray'];
		$flexFormDataStructureArray = $this->globalOptions['flexFormDataStructureArray'];
		$flexFormCurrentLanguage = $this->globalOptions['flexFormCurrentLanguage'];
		$flexFormRowData = $this->globalOptions['flexFormRowData'];

		$tabId = 'TCEFORMS:flexform:' . $this->globalOptions['parameterArray']['itemFormElName'] . $flexFormCurrentLanguage;
		$tabIdString = $docTemplate->getDynTabMenuId($tabId);
		$tabCounter = 0;

		$resultArray = $this->initializeResultArray();
		$tabsContent = array();
		foreach ($flexFormDataStructureArray['sheets'] as $sheetName => $sheetDataStructure) {
			$flexFormRowSheetDataSubPart = $flexFormRowData['data'][$sheetName][$flexFormCurrentLanguage];

			// Evaluate display condition for this sheet if there is one
			$displayConditionResult = TRUE;
			if (!empty($sheetDataStructure['ROOT']['TCEforms']['displayCond'])) {
				$displayConditionDefinition = $sheetDataStructure['ROOT']['TCEforms']['displayCond'];
				$displayConditionResult = $this->evaluateFlexFormDisplayCondition(
					$displayConditionDefinition,
					$flexFormRowSheetDataSubPart
				);
			}
			if (!$displayConditionResult) {
				continue;
			}

			if (!is_array($sheetDataStructure['ROOT']['el'])) {
				$resultArray['html'] .= LF . 'No Data Structure ERROR: No [\'ROOT\'][\'el\'] found for sheet "' . $sheetName . '".';
				continue;
			}

			$tabCounter ++;

			// Assemble key for loading the correct CSH file
			// @todo: what is that good for? That is for the title of single elements ... see FlexFormElementContainer!
			$dsPointerFields = GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$table]['columns'][$fieldName]['config']['ds_pointerField'], TRUE);
			$parameterArray['_cshKey'] = $table . '.' . $fieldName;
			foreach ($dsPointerFields as $key) {
				$parameterArray['_cshKey'] .= '.' . $row[$key];
			}

			$options = $this->globalOptions;
			$options['flexFormDataStructureArray'] = $sheetDataStructure['ROOT']['el'];
			$options['flexFormRowData'] = $flexFormRowSheetDataSubPart;
			$options['flexFormFormPrefix'] = '[data][' . $sheetName . '][' . $flexFormCurrentLanguage . ']';
			$options['parameterArray'] = $parameterArray;
			// Merge elements of this tab into a single list again and hand over to
			// palette and single field container to render this group
			$options['tabAndInlineStack'][] = array(
				'tab',
				$tabIdString . '-' . $tabCounter,
			);
			/** @var FlexFormElementContainer $flexFormElementContainer */
			$flexFormElementContainer = GeneralUtility::makeInstance(FlexFormElementContainer::class);
			$childReturn = $flexFormElementContainer->setGlobalOptions($options)->render();

			$tabsContent[] = array(
				'label' => !empty($sheetDataStructure['ROOT']['TCEforms']['sheetTitle']) ? $languageService->sL($sheetDataStructure['ROOT']['TCEforms']['sheetTitle']) : $sheetName,
				'content' => $childReturn['html'],
				'description' => $sheetDataStructure['ROOT']['TCEforms']['sheetDescription'] ? $languageService->sL($sheetDataStructure['ROOT']['TCEforms']['sheetDescription']) : '',
				'linkTitle' => $sheetDataStructure['ROOT']['TCEforms']['sheetShortDescr'] ? $languageService->sL($sheetDataStructure['ROOT']['TCEforms']['sheetShortDescr']) : '',
			);

			$childReturn['html'] = '';
			$resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $childReturn);
		}

		// Feed everything to document template for tab rendering
		$resultArray['html'] = $docTemplate->getDynamicTabMenu($tabsContent, $tabId, 1, FALSE, FALSE);
		return $resultArray;
	}

	/**
	 * @throws \RuntimeException
	 * @return DocumentTemplate
	 */
	protected function getDocumentTemplate() {
		$docTemplate = $GLOBALS['TBE_TEMPLATE'];
		if (!is_object($docTemplate)) {
			throw new \RuntimeException('No instance of DocumentTemplate found', 1427143328);
		}
		return $docTemplate;
	}

	/**
	 * @return LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

}
