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

use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A container rendering a "full record". This is an entry container used as first
 * step into the rendering tree..
 *
 * This container determines the to be rendered fields depending on the record type,
 * initializes possible language base data, finds out if tabs should be rendered and
 * then calls either TabsContainer or a NoTabsContainer for further processing.
 */
class FullRecordContainer extends AbstractContainer {

	/**
	 * Entry method
	 *
	 * @return array As defined in initializeResultArray() of AbstractNode
	 */
	public function render() {
		$table = $this->globalOptions['table'];
		$row = $this->globalOptions['databaseRow'];

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

		// List of items to be rendered
		$itemList = '';
		if ($GLOBALS['TCA'][$table]['types'][$recordTypeValue]) {
			$itemList = $GLOBALS['TCA'][$table]['types'][$recordTypeValue]['showitem'];
			// Inline may override the type value - setting is given down from InlineRecordContainer if so - used primarily for FAL
			$overruleTypesArray = $this->globalOptions['overruleTypesArray'];
			if (isset($overruleTypesArray[$recordTypeValue]['showitem'])) {
				$itemList = $overruleTypesArray[$recordTypeValue]['showitem'];
			}
		}

		$fieldsArray = GeneralUtility::trimExplode(',', $itemList, TRUE);
		// Add fields and remove excluded fields
		$fieldsArray = $this->mergeFieldsWithAddedFields($fieldsArray, $this->getFieldsToAdd($table, $row, $recordTypeValue), $table);
		$excludeElements = $this->getExcludeElements($table, $row, $recordTypeValue);
		$fieldsArray = $this->removeExcludeElementsFromFieldArray($fieldsArray, $excludeElements);

		// Streamline the fields array
		// First, make sure there is always a --div-- definition for the first element
		if (substr($fieldsArray[0], 0, 7) !== '--div--') {
			array_unshift($fieldsArray, '--div--;LLL:EXT:lang/locallang_core.xlf:labels.generalTab');
		}
		// If first tab has no label definition, add "general" label
		$firstTabHasLabel = count(GeneralUtility::trimExplode(';',  $fieldsArray[0])) > 1 ? TRUE : FALSE;
		if (!$firstTabHasLabel) {
			$fieldsArray[0] = '--div--;LLL:EXT:lang/locallang_core.xlf:labels.generalTab';
		}
		// If there are at least two --div-- definitions, inner container will be a TabContainer, else a NoTabContainer
		$tabCount = 0;
		foreach ($fieldsArray as $field) {
			if (substr($field, 0, 7) === '--div--') {
				$tabCount++;
			}
		}
		$hasTabs = TRUE;
		if ($tabCount < 2) {
			// Remove first tab definition again if there is only one tab defined
			array_shift($fieldsArray);
			$hasTabs = FALSE;
		}

		$options = $this->globalOptions;
		$options['fieldsArray'] = $fieldsArray;
		// Palettes may contain elements that should be excluded, resolved in PaletteContainer
		$options['excludeElements'] = $excludeElements;
		$options['defaultLanguageData'] = $this->defaultLanguageData;
		$options['defaultLanguageDataDiff'] = $this->defaultLanguageDataDiff;
		$options['additionalPreviewLanguageData'] = $this->additionalPreviewLanguageData;

		if ($hasTabs) {
			/** @var TabsContainer $TabsContainer */
			$container = GeneralUtility::makeInstance(TabsContainer::class);
			$container->setGlobalOptions($options);
			$resultArray = $container->render();
		} else {
			/** @var NoTabsContainer $NoTabsContainer */
			$container = GeneralUtility::makeInstance(NoTabsContainer::class);
			$container->setGlobalOptions($options);
			$resultArray = $container->render();
		}

		return $resultArray;
	}

	/**
	 * Finds possible field to add to the form, based on subtype fields.
	 *
	 * @param string $table Table name, MUST be in $GLOBALS['TCA']
	 * @param array $row A record from table.
	 * @param string $typeNum A "type" pointer value, probably the one calculated based on the record array.
	 * @return array An array containing two values: 1) Another array containing field names to add and 2) the subtype value field.
	 */
	protected function getFieldsToAdd($table, $row, $typeNum) {
		$addElements = array();
		$subTypeField = '';
		if ($GLOBALS['TCA'][$table]['types'][$typeNum]['subtype_value_field']) {
			$subTypeField = $GLOBALS['TCA'][$table]['types'][$typeNum]['subtype_value_field'];
			if (trim($GLOBALS['TCA'][$table]['types'][$typeNum]['subtypes_addlist'][$row[$subTypeField]])) {
				$addElements = GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$table]['types'][$typeNum]['subtypes_addlist'][$row[$subTypeField]], TRUE);
			}
		}
		return array($addElements, $subTypeField);
	}

	/**
	 * Merges the current [types][showitem] array with the array of fields to add for the current subtype field of the "type" value.
	 *
	 * @param array $fields A [types][showitem] list of fields, exploded by ",
	 * @param array $fieldsToAdd The output from getFieldsToAdd()
	 * @param string $table The table name, if we want to consider its palettes when positioning the new elements
	 * @return array Return the modified $fields array.
	 */
	protected function mergeFieldsWithAddedFields($fields, $fieldsToAdd, $table = '') {
		if (!empty($fieldsToAdd[0])) {
			$c = 0;
			$found = FALSE;
			foreach ($fields as $fieldInfo) {
				list($fieldName, $label, $paletteName) = GeneralUtility::trimExplode(';', $fieldInfo);
				if ($fieldName === $fieldsToAdd[1]) {
					$found = TRUE;
				} elseif ($fieldName === '--palette--' && $paletteName && $table !== '') {
					// Look inside the palette
					if (is_array($GLOBALS['TCA'][$table]['palettes'][$paletteName])) {
						$itemList = $GLOBALS['TCA'][$table]['palettes'][$paletteName]['showitem'];
						if ($itemList) {
							$paletteFields = GeneralUtility::trimExplode(',', $itemList, TRUE);
							foreach ($paletteFields as $info) {
								$fieldParts = GeneralUtility::trimExplode(';', $info);
								$theField = $fieldParts[0];
								if ($theField === $fieldsToAdd[1]) {
									$found = TRUE;
									break 1;
								}
							}
						}
					}
				}
				if ($found) {
					array_splice($fields, $c + 1, 0, $fieldsToAdd[0]);
					break;
				}
				$c++;
			}
		}
		return $fields;
	}

	/**
	 * @return LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

}
