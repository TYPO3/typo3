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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Form\NodeFactory;

/**
 * Handle a flex form that has no tabs.
 *
 * This container is called by FlexFormLanguageContainer if only a default sheet
 * exists. It evaluates the display condition and hands over rendering of single
 * fields to FlexFormElementContainer.
 */
class FlexFormNoTabsContainer extends AbstractContainer {

	/**
	 * Entry method
	 *
	 * @return array As defined in initializeResultArray() of AbstractNode
	 */
	public function render() {
		$table = $this->globalOptions['table'];
		$row = $this->globalOptions['databaseRow'];
		$fieldName = $this->globalOptions['fieldName']; // field name of the flex form field in DB
		$parameterArray = $this->globalOptions['parameterArray'];
		$flexFormDataStructureArray = $this->globalOptions['flexFormDataStructureArray'];
		$flexFormSheetNameInRowData = 'sDEF';
		$flexFormCurrentLanguage = $this->globalOptions['flexFormCurrentLanguage'];
		$flexFormRowData = $this->globalOptions['flexFormRowData'];
		$flexFormRowDataSubPart = $flexFormRowData['data'][$flexFormSheetNameInRowData][$flexFormCurrentLanguage];
		$resultArray = $this->initializeResultArray();

		// That was taken from GeneralUtility::resolveSheetDefInDS - no idea if it is important
		unset($flexFormDataStructureArray['meta']);

		// Evaluate display condition for this "sheet" if there is one
		$displayConditionResult = TRUE;
		if (!empty($flexFormDataStructureArray['ROOT']['TCEforms']['displayCond'])) {
			$displayConditionDefinition = $flexFormDataStructureArray['ROOT']['TCEforms']['displayCond'];
			$displayConditionResult = $this->evaluateFlexFormDisplayCondition(
				$displayConditionDefinition,
				$flexFormRowDataSubPart
			);
		}
		if (!$displayConditionResult) {
			return $resultArray;
		}

		if (!is_array($flexFormDataStructureArray['ROOT']['el'])) {
			$resultArray['html'] = 'Data Structure ERROR: No [\'ROOT\'][\'el\'] element found in flex form definition.';
			return $resultArray;
		}

		// Assemble key for loading the correct CSH file
		// @todo: what is that good for? That is for the title of single elements ... see FlexFormElementContainer!
		$dsPointerFields = GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$table]['columns'][$fieldName]['config']['ds_pointerField'], TRUE);
		$parameterArray['_cshKey'] = $table . '.' . $fieldName;
		foreach ($dsPointerFields as $key) {
			$parameterArray['_cshKey'] .= '.' . $row[$key];
		}

		$options = $this->globalOptions;
		$options['flexFormDataStructureArray'] = $flexFormDataStructureArray['ROOT']['el'];
		$options['flexFormRowData'] = $flexFormRowDataSubPart;
		$options['flexFormFormPrefix'] = '[data][' . $flexFormSheetNameInRowData . '][' . $flexFormCurrentLanguage . ']';
		$options['parameterArray'] = $parameterArray;

		$options['renderType'] = 'flexFormElementContainer';
		/** @var NodeFactory $nodeFactory */
		$nodeFactory = $this->globalOptions['nodeFactory'];
		return $nodeFactory->create($options)->render();
	}

}
