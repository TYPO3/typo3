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
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Backend\Form\NodeFactory;

/**
 * An entry container to render just a single field.
 *
 * The container operates on $this->globalOptions['singleFieldToRender'] to render
 * this field. It initializes language stuff and prepares data in globalOptions for
 * processing of the single field in SingleFieldContainer.
 */
class SoloFieldContainer extends AbstractContainer {

	/**
	 * Entry method
	 *
	 * @return array As defined in initializeResultArray() of AbstractNode
	 */
	public function render() {
		$table = $this->globalOptions['table'];
		$row = $this->globalOptions['databaseRow'];
		$fieldToRender = $this->globalOptions['singleFieldToRender'];

		if (!$GLOBALS['TCA'][$table]) {
			return $this->initializeResultArray();
		}

		$languageService = $this->getLanguageService();

		// Load the description content for the table if requested
		if ($GLOBALS['TCA'][$table]['interface']['always_description']) {
			$languageService->loadSingleTableDescription($table);
		}

		// If this is a localized record, stuff data from original record to local registry, will then be given to child elements
		$this->registerDefaultLanguageData($table, $row);

		// Current type value of the record.
		$recordTypeValue = $this->getRecordTypeValue($table, $row);

		$excludeElements = $this->getExcludeElements($table, $row, $recordTypeValue);

		$resultArray = $this->initializeResultArray();
		if (isset($GLOBALS['TCA'][$table]['types'][$recordTypeValue])) {
			$itemList = $GLOBALS['TCA'][$table]['types'][$recordTypeValue]['showitem'];
			if ($itemList) {
				$fields = GeneralUtility::trimExplode(',', $itemList, TRUE);
				foreach ($fields as $fieldString) {
					$fieldConfiguration = $this->explodeSingleFieldShowItemConfiguration($fieldString);
					$fieldName = $fieldConfiguration['fieldName'];
					if (!in_array($fieldName, $excludeElements, TRUE) && (string)$fieldName === (string)$fieldToRender) {
						if ($GLOBALS['TCA'][$table]['columns'][$fieldName]) {
							$options = $this->globalOptions;
							$options['fieldName'] = $fieldName;
							$options['recordTypeValue'] = $recordTypeValue;

							$options['renderType'] = 'singleFieldContainer';
							/** @var NodeFactory $nodeFactory */
							$nodeFactory = $this->globalOptions['nodeFactory'];
							$resultArray = $nodeFactory->create($options)->render();
						}
					}
				}
			}
		}
		return $resultArray;
	}

	/**
	 * @return LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

}