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

use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;

/**
 * Handle flex form language overlays.
 *
 * Entry container to a flex form element. This container is created by
 * SingleFieldContainer if a type='flexform' field is rendered.
 *
 * For each existing language overlay it forks a FlexFormTabsContainer or a
 * FlexFormNoTabsContainer for rendering a full flex form record of the specific language.
 */
class FlexFormLanguageContainer extends AbstractContainer {

	/**
	 * Entry method
	 *
	 * @return array As defined in initializeResultArray() of AbstractNode
	 * @todo: Implement langChildren=1 case where each single element is localized and not the whole thing.
	 */
	public function render() {
		$table = $this->data['tableName'];
		$row = $this->data['databaseRow'];
		$flexFormDataStructureArray = $this->data['parameterArray']['fieldConf']['config']['ds'];
		$flexFormRowData = $this->data['parameterArray']['itemFormElValue'];

		// Tabs or no tabs - that's the question
		$hasTabs = FALSE;
		if (count($flexFormDataStructureArray['sheets']) > 1) {
			$hasTabs = TRUE;
		}

		$resultArray = $this->initializeResultArray();

		foreach ($flexFormDataStructureArray['meta']['languagesOnSheetLevel'] as $lKey) {
			// Add language as header
			if (!$flexFormDataStructureArray['meta']['langChildren'] && !$flexFormDataStructureArray['meta']['langDisable']) {
				$resultArray['html'] .= LF . '<strong>' . FormEngineUtility::getLanguageIcon($table, $row, ('v' . $lKey)) . $lKey . ':</strong>';
			}

			// Default language "lDEF", other options are "lUK" or whatever country code
			$flexFormCurrentLanguage = 'l' . $lKey;

			$options = $this->data;
			$options['flexFormCurrentLanguage'] = $flexFormCurrentLanguage;
			$options['flexFormDataStructureArray'] = $flexFormDataStructureArray;
			$options['flexFormRowData'] = $flexFormRowData;
			if (!$hasTabs) {
				$options['renderType'] = 'flexFormNoTabsContainer';
				$flexFormNoTabsResult = $this->nodeFactory->create($options)->render();
				$resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $flexFormNoTabsResult);
			} else {
				$options['renderType'] = 'flexFormTabsContainer';
				$flexFormTabsContainerResult = $this->nodeFactory->create($options)->render();
				$resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $flexFormTabsContainerResult);
			}
		}
		$resultArray['requireJsModules'][] = 'TYPO3/CMS/Backend/FormEngineFlexForm';

		return $resultArray;
	}

}
