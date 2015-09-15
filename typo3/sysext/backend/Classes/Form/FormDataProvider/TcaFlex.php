<?php
namespace TYPO3\CMS\Backend\Form\FormDataProvider;

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

use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\FlexFormSegment;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Migrations\TcaMigration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Resolve and prepare flex structure data and add resolved data structure to TCA in
 * $fieldName['config']['ds'] as well as resolved value array in $databaseRow
 */
class TcaFlex extends AbstractItemProvider implements FormDataProviderInterface {

	/**
	 * Resolve flex data structures and prepare flex data values.
	 *
	 * First, resolve ds pointer stuff and parse both ds and dv. Then normalize some
	 * details to have aligned array nesting for the rest of the method and the render
	 * engine. Next determine possible pageTsConfig overrides and apply them to ds.
	 * Determine available languages and sanitize dv for further processing. Then kick
	 * and validate further details like excluded fields. Finally for each possible
	 * value and ds call FormDataCompiler with set FlexFormSegment group to resolve
	 * single field stuff like item processor functions.
	 *
	 * @param array $result
	 * @return array
	 */
	public function addData(array $result) {
		foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
			if (empty($fieldConfig['config']['type']) || $fieldConfig['config']['type'] !== 'flex') {
				continue;
			}

			// @todo: It would probably be better to split this provider into multiple provider. At least it
			// @todo: would be nice to have the data structure and value fetching and parsing available as
			// @todo: stand alone provider.
			$result = $this->initializeDataStructure($result, $fieldName);
			$result = $this->initializeDataValues($result, $fieldName);
			$result = $this->createDefaultSheetInDataStructureIfNotGiven($result, $fieldName);
			$result = $this->removeTceFormsArrayKeyFromDataStructureElements($result, $fieldName);
			$flexIdentifier = $this->getFlexIdentifier($result, $fieldName);
			$pageTsConfigOfFlex = $this->getPageTsOfFlex($result, $fieldName, $flexIdentifier);
			$result = $this->modifyOuterDataStructure($result, $fieldName, $pageTsConfigOfFlex);
			$result = $this->removeExcludeFieldsFromDataStructure($result, $fieldName, $flexIdentifier);
			$result = $this->removeDisabledFieldsFromDataStructure($result, $fieldName, $pageTsConfigOfFlex);
			$result = $this->prepareLanguageHandlingInDataValues($result, $fieldName);
			$result = $this->migrateFlexformTcaDataStructureElements($result, $fieldName);
			$result = $this->modifyDataStructureAndDataValuesByFlexFormSegmentGroup($result, $fieldName, $pageTsConfigOfFlex);
		}

		return $result;
	}

	/**
	 * Fetch / initialize data structure
	 *
	 * @param array $result Result array
	 * @param $fieldName string Currently handled field name
	 * @return array Modified result
	 * @throws \UnexpectedValueException
	 */
	protected function initializeDataStructure(array $result, $fieldName) {
		// Fetch / initialize data structure
		$dataStructureArray = BackendUtility::getFlexFormDS(
			$result['processedTca']['columns'][$fieldName]['config'],
			$result['databaseRow'],
			$result['tableName'],
			$fieldName
		);
		// If data structure can't be parsed, this is a developer error, so throw a non catchable exception
		// @todo: It might be ok to have a non parsable ds if field is not displayed anyway
		// @todo: Parse flex only for showitem validated fields and after displayCondition evaluation?
		if (!is_array($dataStructureArray)) {
			throw new \UnexpectedValueException(
				'Data structure error: ' . $dataStructureArray,
				1440506893
			);
		}
		if (!isset($dataStructureArray['meta']) || !is_array($dataStructureArray['meta'])) {
			$dataStructureArray['meta'] = array();
		}
		// This kicks one array depth:  config['ds']['matchingIdentifier'] becomes config['ds']
		$result['processedTca']['columns'][$fieldName]['config']['ds'] = $dataStructureArray;
		return $result;
	}

	/**
	 * Parse / initialize value from xml string to array
	 *
	 * @param array $result Result array
	 * @param $fieldName string Currently handled field name
	 * @return array Modified result
	 */
	protected function initializeDataValues(array $result, $fieldName) {
		if (!array_key_exists($fieldName, $result['databaseRow'])) {
			$result['databaseRow'][$fieldName] = '';
		}
		$valueArray = [];
		if (isset($result['databaseRow'][$fieldName])) {
			$valueArray = $result['databaseRow'][$fieldName];
		}
		if (!is_array($result['databaseRow'][$fieldName])) {
			$valueArray = GeneralUtility::xml2array($result['databaseRow'][$fieldName]);
		}
		if (!is_array($valueArray)) {
			$valueArray = [];
		}
		if (!isset($valueArray['data'])) {
			$valueArray['data'] = [];
		}
		if (!isset($valueArray['meta'])) {
			$valueArray['meta'] = [];
		}
		$result['databaseRow'][$fieldName] = $valueArray;
		return $result;
	}

	/**
	 * Add a sheet structure if data structure has none yet to simplify further handling.
	 *
	 * Example TCA field config:
	 * ['config']['ds']['ROOT'] becomes
	 * ['config']['ds']['sheets']['sDEF']['ROOT']
	 *
	 * @param array $result Result array
	 * @param string $fieldName Currently handled field name
	 * @return array Modified result
	 * @throws \UnexpectedValueException
	 */
	protected function createDefaultSheetInDataStructureIfNotGiven(array $result, $fieldName) {
		$modifiedDataStructure = $result['processedTca']['columns'][$fieldName]['config']['ds'];
		if (isset($modifiedDataStructure['ROOT']) && isset($modifiedDataStructure['sheets'])) {
			throw new \UnexpectedValueException(
				'Parsed data structure has both ROOT and sheets on top level',
				1440676540
			);
		}
		if (isset($modifiedDataStructure['ROOT']) && is_array($modifiedDataStructure['ROOT'])) {
			$modifiedDataStructure['sheets']['sDEF']['ROOT'] = $modifiedDataStructure['ROOT'];
			unset($modifiedDataStructure['ROOT']);
		}
		$result['processedTca']['columns'][$fieldName]['config']['ds'] = $modifiedDataStructure;
		return $result;
	}

	/**
	 * Remove "TCEforms" key from all elements in data structure to simplify further parsing.
	 *
	 * Example config:
	 * ['config']['ds']['sheets']['sDEF']['ROOT']['el']['anElement']['TCEforms']['label'] becomes
	 * ['config']['ds']['sheets']['sDEF']['ROOT']['el']['anElement']['label']
	 *
	 * @param array $result Result array
	 * @param string $fieldName Currently handled field name
	 * @return array Modified result
	 */
	protected function removeTceFormsArrayKeyFromDataStructureElements(array $result, $fieldName) {
		$modifiedDataStructure = $result['processedTca']['columns'][$fieldName]['config']['ds'];
		$modifiedDataStructure = $this->removeElementTceFormsRecursive($modifiedDataStructure);
		$result['processedTca']['columns'][$fieldName]['config']['ds'] = $modifiedDataStructure;
		return $result;
	}

	/**
	 * Moves ['el']['something']['TCEforms'] to ['el']['something'] and ['ROOT']['TCEforms'] to ['ROOT'] recursive
	 *
	 * @param array $structure Given hierarchy
	 * @return array Modified hierarchy
	 */
	protected function removeElementTceFormsRecursive(array $structure) {
		$newStructure = [];
		foreach ($structure as $key => $value) {
			if ($key === 'ROOT' && is_array($value) && isset($value['TCEforms'])) {
				$value = array_merge($value, $value['TCEforms']);
				unset($value['TCEforms']);
			}
			if ($key === 'el' && is_array($value)) {
				$newSubStructure = [];
				foreach ($value as $subKey => $subValue) {
					if (is_array($subValue) && count($subValue) === 1 && isset($subValue['TCEforms'])) {
						$newSubStructure[$subKey] = $subValue['TCEforms'];
					} else {
						$newSubStructure[$subKey] = $subValue;
					}
				}
				$value = $newSubStructure;
			}
			if (is_array($value)) {
				$value = $this->removeElementTceFormsRecursive($value);
			}
			$newStructure[$key] = $value;
		}
		return $newStructure;
	}

	/**
	 * Take care of ds_pointerField and friends to determine the correct sub array within
	 * TCA config ds.
	 *
	 * Gets extension identifier. Use second pointer field if it's value is not empty, "list" or "*",
	 * else it must be a plugin and first one will be used.
	 * This code basically determines the sub key of ds field:
	 * config = array(
	 *  ds => array(
	 *    'aFlexConfig' => '<flexXml ...
	 *     ^^^^^^^^^^^
	 * $flexformIdentifier contains "aFlexConfig" after this operation.
	 *
	 * @todo: This method is only implemented half. It basically should do all the
	 * @todo: pointer handling that is done within BackendUtility::getFlexFormDS() to $srcPointer.
	 *
	 * @param array $result Result array
	 * @param string $fieldName Current handle field name
	 * @return string Pointer
	 */
	protected function getFlexIdentifier(array $result, $fieldName) {
		// @todo: Current implementation with the "list_type, CType" fallback is rather limited and customized for
		// @todo: tt_content, also it forces a ds_pointerField to be defined and a casual "default" sub array does not work
		$pointerFields = !empty($result['processedTca']['columns'][$fieldName]['config']['ds_pointerField'])
			? $result['processedTca']['columns'][$fieldName]['config']['ds_pointerField']
			: 'list_type,CType';
		$pointerFields = GeneralUtility::trimExplode(',', $pointerFields);
		$flexformIdentifier = !empty($result['databaseRow'][$pointerFields[0]]) ? $result['databaseRow'][$pointerFields[0]] : '';
		if (!empty($result['databaseRow'][$pointerFields[1]])
			&& $result['databaseRow'][$pointerFields[1]] !== 'list'
			&& $result['databaseRow'][$pointerFields[1]] !== '*'
		) {
			$flexformIdentifier = $result['databaseRow'][$pointerFields[1]];
		}
		if (empty($flexformIdentifier)) {
			$flexformIdentifier = 'default';
		}

		return $flexformIdentifier;
	}

	/**
	 * Determine TCEFORM.aTable.aField.matchingIdentifier
	 *
	 * @param array $result Result array
	 * @param string $fieldName Handled field name
	 * @param string $flexIdentifier Determined identifier
	 * @return array PageTsConfig for this flex
	 */
	protected function getPageTsOfFlex(array $result, $fieldName, $flexIdentifier) {
		$table = $result['tableName'];
		$pageTs = [];
		if (!empty($result['pageTsConfigMerged']['TCEFORM.'][$table . '.'][$fieldName . '.'][$flexIdentifier . '.'])
			&& is_array($result['pageTsConfigMerged']['TCEFORM.'][$table . '.'][$fieldName . '.'][$flexIdentifier . '.'])) {
			$pageTs = $result['pageTsConfigMerged']['TCEFORM.'][$table . '.'][$fieldName . '.'][$flexIdentifier . '.'];
		}
		return $pageTs;
	}

	/**
	 * Handle "outer" flex data structure changes like language and sheet
	 * description. Does not change "TCA" or values of single elements
	 *
	 * @param array $result Result array
	 * @param string $fieldName Current handle field name
	 * @param array $pageTsConfig Given pageTsConfig of this flex form
	 * @return array Modified item array
	 */
	protected function modifyOuterDataStructure(array $result, $fieldName, $pageTsConfig) {
		$modifiedDataStructure = $result['processedTca']['columns'][$fieldName]['config']['ds'];

		if (isset($pageTsConfig['langDisable'])) {
			$modifiedDataStructure['meta']['langDisable'] = $pageTsConfig['langDisable'];
		}
		if (isset($pageTsConfig['langChildren'])) {
			$modifiedDataStructure['meta']['langChildren'] = $pageTsConfig['langChildren'];
		}

		if (isset($modifiedDataStructure['sheets']) && is_array($modifiedDataStructure['sheets'])) {
			// Handling multiple sheets
			foreach ($modifiedDataStructure['sheets'] as $sheetName => $sheetStructure) {
				$modifiedDataStructure['sheets'][$sheetName] = $this->resolvePossibleExternalFile($sheetStructure);

				if (isset($pageTsConfig[$sheetName . '.']) && is_array($pageTsConfig[$sheetName . '.'])) {
					$pageTsOfSheet = $pageTsConfig[$sheetName . '.'];

					// Remove whole sheet if disabled
					if (!empty($pageTsOfSheet['disabled'])) {
						unset($modifiedDataStructure['sheets'][$sheetName]);
						continue;
					}

					// sheetTitle, sheetDescription, sheetShortDescr
					$modifiedDataStructure['sheets'][$sheetName] = $this->modifySingleSheetInformation($sheetStructure, $pageTsOfSheet);
				}
			}
		}

		$modifiedDataStructure['meta']['langDisable'] = isset($modifiedDataStructure['meta']['langDisable'])
			? (bool)$modifiedDataStructure['meta']['langDisable']
			: FALSE;
		$modifiedDataStructure['meta']['langChildren'] = isset($modifiedDataStructure['meta']['langChildren'])
			? (bool)$modifiedDataStructure['meta']['langChildren']
			: FALSE;

		$result['processedTca']['columns'][$fieldName]['config']['ds'] = $modifiedDataStructure;

		return $result;
	}

	/**
	 * Removes fields from data structure the user has no access to
	 *
	 * @param array $result Result array
	 * @param string $fieldName Current handle field name
	 * @param string $flexIdentifier Determined identifier
	 * @return array Modified result
	 */
	protected function removeExcludeFieldsFromDataStructure(array $result, $fieldName, $flexIdentifier) {
		$dataStructure = $result['processedTca']['columns'][$fieldName]['config']['ds'];
		$backendUser = $this->getBackendUser();
		if ($backendUser->isAdmin() || !isset($dataStructure['sheets']) || !is_array($dataStructure['sheets'])) {
			return $result;
		}

		$userNonExcludeFields = GeneralUtility::trimExplode(',', $backendUser->groupData['non_exclude_fields']);
		$excludeFieldsPrefix = $result['tableName'] . ':' . $fieldName . ';' . $flexIdentifier . ';';
		$nonExcludeFields = [];
		foreach ($userNonExcludeFields as $userNonExcludeField) {
			if (strpos($userNonExcludeField, $excludeFieldsPrefix) !== FALSE) {
				$exploded = explode(';', $userNonExcludeField);
				$sheetName = $exploded[2];
				$fieldName = $exploded[3];
				$nonExcludeFields[$sheetName] = $fieldName;
			}
		}

		foreach ($dataStructure['sheets'] as $sheetName => $sheetDefinition) {
			if (!isset($sheetDefinition['ROOT']['el']) || !is_array($sheetDefinition['ROOT']['el'])) {
				continue;
			}
			foreach ($sheetDefinition['ROOT']['el'] as $flexFieldName => $fieldDefinition) {
				if (!empty($fieldDefinition['exclude']) && empty($nonExcludeFields[$sheetName])) {
					unset($result['processedTca']['columns'][$fieldName]['config']['ds']['sheets'][$sheetName]['ROOT']['el'][$flexFieldName]);
				}
			}
		}

		return $result;
	}

	/**
	 * Handle "outer" flex data structure changes like language and sheet
	 * description. Does not change "TCA" or values of single elements
	 *
	 * @param array $result Result array
	 * @param string $fieldName Current handle field name
	 * @param array $pageTsConfig Given pageTsConfig of this flex form
	 * @return array Modified item array
	 */
	protected function removeDisabledFieldsFromDataStructure(array $result, $fieldName, $pageTsConfig) {
		$dataStructure = $result['processedTca']['columns'][$fieldName]['config']['ds'];
		if (!isset($dataStructure['sheets']) || !is_array($dataStructure['sheets'])) {
			return $result;
		}
		foreach ($dataStructure['sheets'] as $sheetName => $sheetDefinition) {
			if (!isset($sheetDefinition['ROOT']['el']) || !is_array($sheetDefinition['ROOT']['el'])
				|| !isset($pageTsConfig[$sheetName . '.'])) {
				continue;
			}
			foreach ($sheetDefinition['ROOT']['el'] as $flexFieldName => $fieldDefinition) {
				if (!empty($pageTsConfig[$sheetName . '.'][$flexFieldName . '.']['disabled'])) {
					unset($result['processedTca']['columns'][$fieldName]['config']['ds']['sheets'][$sheetName]['ROOT']['el'][$flexFieldName]);
				}
			}
		}

		return $result;
	}

	/**
	 * Remove data values in languages the user has no access to and add dummy entries
	 * for languages that are available but do not exist in data values yet.
	 *
	 * @param array $result Result array
	 * @param string $fieldName Current handle field name
	 * @return array Modified item array
	 */
	protected function prepareLanguageHandlingInDataValues(array $result, $fieldName) {
		$backendUser = $this->getBackendUser();
		$dataStructure = $result['processedTca']['columns'][$fieldName]['config']['ds'];

		$langDisabled = $dataStructure['meta']['langDisable'];
		$langChildren = $dataStructure['meta']['langChildren'];

		// Existing page language overlays are only considered if options.checkPageLanguageOverlay is set in userTs
		$checkPageLanguageOverlay = FALSE;
		if (isset($result['userTsConfig']['options.']) && is_array($result['userTsConfig']['options.'])
			&& array_key_exists('checkPageLanguageOverlay', $result['userTsConfig']['options.'])
		) {
			$checkPageLanguageOverlay = (bool)$result['userTsConfig']['options.']['checkPageLanguageOverlay'];
		}

		$systemLanguageRows = $result['systemLanguageRows'];

		// Contains all language iso code that are valid and user has access to
		$availableLanguageCodes = [];
		foreach ($systemLanguageRows as $systemLanguageRow) {
			$isoCode = $systemLanguageRow['iso'];
			$isAvailable = TRUE;
			if ($langDisabled && $isoCode !== 'DEF') {
				$isAvailable = FALSE;
			}
			// @todo: Is it possible a user has no write access to default lang? If so, what to do?
			if (!$backendUser->checkLanguageAccess($systemLanguageRow['uid'])) {
				$isAvailable = FALSE;
			}
			if ($checkPageLanguageOverlay && $systemLanguageRow['uid'] > 0) {
				$found = FALSE;
				foreach ($result['pageLanguageOverlayRows'] as $overlayRow) {
					if ((int)$overlayRow['sys_language_uid'] === (int)$systemLanguageRow['uid']) {
						$found = TRUE;
						break;
					}
				}
				if (!$found) {
					$isAvailable = FALSE;
				}
			}
			if ($isAvailable) {
				$availableLanguageCodes[] = $isoCode;
			}
		}
		// Set the list of available languages in the data structure "meta" section to have it
		// available for the render engine to iterate over it.
		$result['processedTca']['columns'][$fieldName]['config']['ds']['meta']['availableLanguageCodes'] = $availableLanguageCodes;

		if (!$langChildren) {
			$allowedLanguageSheetKeys = [];
			foreach ($availableLanguageCodes as $isoCode) {
				$allowedLanguageSheetKeys['l' . $isoCode] = [];
			}
			$result = $this->setLanguageSheetsInDataValues($result, $fieldName, $allowedLanguageSheetKeys);

			// With $langChildren = 0, values must only contain vDEF prefixed keys
			$allowedValueLevelLanguageKeys = [];
			$allowedValueLevelLanguageKeys['vDEF'] = [];
			$allowedValueLevelLanguageKeys['vDEF.vDEFbase'] = [];
			// A richtext special
			$allowedValueLevelLanguageKeys['_TRANSFORM_vDEF.vDEFbase'] = [];
			$result = $this->setLanguageValueLevelValues($result, $fieldName, $allowedValueLevelLanguageKeys);
		} else {
			// langChildren is set - only lDEF as sheet language is allowed, but more fields on value field level
			$allowedLanguageSheetKeys = [
				'lDEF' => [],
			];
			$result = $this->setLanguageSheetsInDataValues($result, $fieldName, $allowedLanguageSheetKeys);

			$allowedValueLevelLanguageKeys = [];
			foreach ($availableLanguageCodes as $isoCode) {
				$allowedValueLevelLanguageKeys['v' . $isoCode] = [];
				$allowedValueLevelLanguageKeys['v' . $isoCode . '.vDEFbase'] = [];
				$allowedValueLevelLanguageKeys['_TRANSFORM_v' . $isoCode . '.vDEFbase'] = [];
			}
			$result = $this->setLanguageValueLevelValues($result, $fieldName, $allowedValueLevelLanguageKeys);
		}

		return $result;
	}

	/**
	 * Feed single flex field and data to FlexFormSegment FormData compiler and merge result.
	 * This one is nasty. Goal is to have processed TCA stuff in DS and also have validated / processed data values.
	 *
	 * Three main parts in this method:
	 * * Process values of existing section container for default values
	 * * Process values and TCA of possible section container and create a default value row for each
	 * * Process TCA of "normal" fields and have default values in data ['templateRows']['containerName'] parallel to section ['el']
	 *
	 * @param array $result Result array
	 * @param string $fieldName Current handle field name
	 * @param array $pageTsConfig Given pageTsConfig of this flex form
	 * @return array Modified item array
	 */
	protected function modifyDataStructureAndDataValuesByFlexFormSegmentGroup(array $result, $fieldName, $pageTsConfig) {
		$dataStructure = $result['processedTca']['columns'][$fieldName]['config']['ds'];
		$dataValues = $result['databaseRow'][$fieldName];

		$availableLanguageCodes = $result['processedTca']['columns'][$fieldName]['config']['ds']['meta']['availableLanguageCodes'];
		if ($dataStructure['meta']['langChildren']) {
			$languagesOnSheetLevel = [ 'DEF' ];
			$languagesOnElementLevel = $availableLanguageCodes;
		} else {
			$languagesOnSheetLevel = $availableLanguageCodes;
			$languagesOnElementLevel = [ 'DEF' ];
		}

		$result['processedTca']['columns'][$fieldName]['config']['ds']['meta']['languagesOnSheetLevel'] = $languagesOnSheetLevel;
		$result['processedTca']['columns'][$fieldName]['config']['ds']['meta']['languagesOnElement'] = $languagesOnElementLevel;

		if (!isset($dataStructure['sheets']) || !is_array($dataStructure['sheets'])) {
			return $result;
		}

		/** @var FlexFormSegment $formDataGroup */
		$formDataGroup = GeneralUtility::makeInstance(FlexFormSegment::class);
		/** @var FormDataCompiler $formDataCompiler */
		$formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);

		foreach ($dataStructure['sheets'] as $dataStructureSheetName => $dataStructureSheetDefinition) {
			if (!isset($dataStructureSheetDefinition['ROOT']['el']) || !is_array($dataStructureSheetDefinition['ROOT']['el'])) {
				continue;
			}
			$dataStructureSheetElements = $dataStructureSheetDefinition['ROOT']['el'];

			// Prepare pageTsConfig of this sheet
			$pageTsConfigMerged['TCEFORM.']['flexDummyTable.'] = [];
			if (isset($pageTsConfig[$dataStructureSheetName . '.']) && is_array($pageTsConfig[$dataStructureSheetName . '.'])) {
				$pageTsConfigMerged['TCEFORM.']['flexDummyTable.'] = $pageTsConfig[$dataStructureSheetName . '.'];
			}

			foreach ($languagesOnSheetLevel as $isoSheetLevel) {
				$langSheetLevel = 'l' . $isoSheetLevel;
				foreach ($dataStructureSheetElements as $dataStructureSheetElementName => $dataStructureSheetElementDefinition) {
					if (isset($dataStructureSheetElementDefinition['type']) && $dataStructureSheetElementDefinition['type'] === 'array'
						&& isset($dataStructureSheetElementDefinition['section']) && $dataStructureSheetElementDefinition['section'] === '1'
					) {
						// A section

						// Existing section container elements
						if (isset($dataValues['data'][$dataStructureSheetName][$langSheetLevel][$dataStructureSheetElementName]['el'])
							&& is_array($dataValues['data'][$dataStructureSheetName][$langSheetLevel][$dataStructureSheetElementName]['el'])
						) {
							$containerArray = $dataValues['data'][$dataStructureSheetName][$langSheetLevel][$dataStructureSheetElementName]['el'];
							foreach ($containerArray as $aContainerNumber => $aContainerArray) {
								if (is_array($aContainerArray)) {
									foreach ($aContainerArray as $aContainerName => $aContainerElementArray) {
										if ($aContainerName === '_TOGGLE') {
											// Don't handle internal toggle state field
											continue;
										}
										if (!isset($dataStructureSheetElements[$dataStructureSheetElementName]['el'][$aContainerName])) {
											// Container not defined in ds
											continue;
										}
										foreach ($dataStructureSheetElements[$dataStructureSheetElementName]['el'][$aContainerName]['el'] as $singleFieldName => $singleFieldConfiguration) {
											// $singleFieldValueArray = ['data']['sSections']['lDEF']['section_1']['el']['1']['container_1']['el']['element_1']
											$singleFieldValueArray = [];
											if (isset($aContainerElementArray['el'][$singleFieldName])
												&& is_array($aContainerElementArray['el'][$singleFieldName])
											) {
												$singleFieldValueArray = $aContainerElementArray['el'][$singleFieldName];
											}
											foreach ($languagesOnElementLevel as $isoElementLevel) {
												$langElementLevel = 'v' . $isoElementLevel;
												$valueArray = [];
												$command = 'new';
												if (array_key_exists($langElementLevel, $singleFieldValueArray)) {
													$command = 'edit';
													$valueArray[$singleFieldName] = $singleFieldValueArray[$langElementLevel];
												}
												$inputToFlexFormSegment = [
													'tableName' => 'flexDummyTable',
													'command' => $command,
													// It is currently not possible to have pageTsConfig for section container
													'pageTsConfigMerged' => [],
													'databaseRow' => $valueArray,
													'vanillaTableTca' => [
														'ctrl' => [],
														'columns' => [
															$singleFieldName => $singleFieldConfiguration,
														],
													],
													'processedTca' => [
														'ctrl' => [],
														'columns' => [
															$singleFieldName => $singleFieldConfiguration,
														],
													],
												];
												$flexSegmentResult = $formDataCompiler->compile($inputToFlexFormSegment);
												// Set data value result
												if (array_key_exists($singleFieldName, $flexSegmentResult['databaseRow'])) {
													$result['databaseRow'][$fieldName]
													['data'][$dataStructureSheetName][$langSheetLevel][$dataStructureSheetElementName]['el']
													[$aContainerNumber][$aContainerName]['el']
													[$singleFieldName][$langElementLevel]
														= $flexSegmentResult['databaseRow'][$singleFieldName];
												}
												// Set TCA structure result, actually, this call *might* be obsolete since the "dummy"
												// handling below will set it again.
												$result['processedTca']['columns'][$fieldName]['config']['ds']
												['sheets'][$dataStructureSheetName]['ROOT']['el'][$dataStructureSheetElementName]['el']
												[$aContainerName]['el'][$singleFieldName]
													= $flexSegmentResult['processedTca']['columns'][$singleFieldName];
											}
										}
									}
								}
							}
						} // End of existing data value handling

						// Prepare "fresh" row for every possible container
						if (isset($dataStructureSheetElements[$dataStructureSheetElementName]['el']) && is_array($dataStructureSheetElements[$dataStructureSheetElementName]['el'])) {
							foreach ($dataStructureSheetElements[$dataStructureSheetElementName]['el'] as $possibleContainerName => $possibleContainerConfiguration) {
								if (isset($possibleContainerConfiguration['el']) && is_array($possibleContainerConfiguration['el'])) {
									// Initialize result data array templateRows
									$result['databaseRow'][$fieldName]
									['data'][$dataStructureSheetName][$langSheetLevel][$dataStructureSheetElementName]['templateRows']
									[$possibleContainerName]['el']
										= [];
									foreach ($possibleContainerConfiguration['el'] as $singleFieldName => $singleFieldConfiguration) {
										foreach ($languagesOnElementLevel as $isoElementLevel) {
											$langElementLevel = 'v' . $isoElementLevel;
											$inputToFlexFormSegment = [
												'tableName' => 'flexDummyTable',
												'command' => 'new',
												'pageTsConfigMerged' => [],
												'databaseRow' => [],
												'vanillaTableTca' => [
													'ctrl' => [],
													'columns' => [
														$singleFieldName => $singleFieldConfiguration,
													],
												],
												'processedTca' => [
													'ctrl' => [],
													'columns' => [
														$singleFieldName => $singleFieldConfiguration,
													],
												],
											];
											$flexSegmentResult = $formDataCompiler->compile($inputToFlexFormSegment);
											if (array_key_exists($singleFieldName, $flexSegmentResult['databaseRow'])) {
												$result['databaseRow'][$fieldName]
												['data'][$dataStructureSheetName][$langSheetLevel][$dataStructureSheetElementName]['templateRows']
												[$possibleContainerName]['el'][$singleFieldName][$langElementLevel]
												 = $flexSegmentResult['databaseRow'][$singleFieldName];
											}
											$result['processedTca']['columns'][$fieldName]['config']['ds']
											['sheets'][$dataStructureSheetName]['ROOT']['el'][$dataStructureSheetElementName]['el']
											[$possibleContainerName]['el'][$singleFieldName]
												= $flexSegmentResult['processedTca']['columns'][$singleFieldName];
										}
									}
								}
							}
						} // End of preparation for each possible container

					// type without section is not ok
					} elseif (isset($dataStructureSheetElementDefinition['type']) || isset($dataStructureSheetElementDefinition['section'])) {
						throw new \UnexpectedValueException(
							'Broken data structure on field name ' . $fieldName . '. section without type or vice versa is not allowed',
							1440685208
						);

					// A "normal" TCA element
					} else {
						foreach ($languagesOnElementLevel as $isoElementLevel) {
							$langElementLevel = 'v' . $isoElementLevel;
							$valueArray = [];
							$command = 'new';
							if (isset($dataValues['data'][$dataStructureSheetName][$langSheetLevel][$dataStructureSheetElementName])
								&& array_key_exists($langElementLevel, $dataValues['data'][$dataStructureSheetName][$langSheetLevel][$dataStructureSheetElementName])
							) {
								$command = 'edit';
								$valueArray[$dataStructureSheetElementName] = $dataValues['data'][$dataStructureSheetName][$langSheetLevel][$dataStructureSheetElementName][$langElementLevel];
							}
							$inputToFlexFormSegment = [
								'tableName' => 'flexDummyTable',
								'command' => $command,
								'pageTsConfigMerged' => $pageTsConfigMerged,
								'databaseRow' => $valueArray,
								'vanillaTableTca' => [
									'ctrl' => [],
									'columns' => [
										$dataStructureSheetElementName => $dataStructureSheetElementDefinition,
									],
								],
								'processedTca' => [
									'ctrl' => [],
									'columns' => [
										$dataStructureSheetElementName => $dataStructureSheetElementDefinition,
									],
								],
							];
							$flexSegmentResult = $formDataCompiler->compile($inputToFlexFormSegment);
							// Set data value result
							if (array_key_exists($dataStructureSheetElementName, $flexSegmentResult['databaseRow'])) {
								$result['databaseRow'][$fieldName]
								['data'][$dataStructureSheetName][$langSheetLevel][$dataStructureSheetElementName][$langElementLevel]
									= $flexSegmentResult['databaseRow'][$dataStructureSheetElementName];
							}
							// Set TCA structure result
							$result['processedTca']['columns'][$fieldName]['config']['ds']
							['sheets'][$dataStructureSheetName]['ROOT']['el'][$dataStructureSheetElementName]
								= $flexSegmentResult['processedTca']['columns'][$dataStructureSheetElementName];
						}
					} // End of single element handling

				}
			}
		}

		return $result;
	}

	/**
	 * Single fields can be extracted to files again. This is resolved here.
	 *
	 * @todo: Why is this not done in BackendUtility::getFlexFormDS() directly? If done there, the two methods
	 * @todo: GeneralUtility::resolveSheetDefInDS() and GeneralUtility::resolveAllSheetsInDS() could be killed
	 * @todo: since this resolving is basically the only really useful thing they actually do.
	 *
	 * @param string|array $sheetStructure Not resolved structure
	 * @return array Parsed sheet structure
	 */
	protected function resolvePossibleExternalFile($sheetStructure) {
		if (!is_array($sheetStructure)) {
			$file = GeneralUtility::getFileAbsFileName($sheetStructure);
			if ($file && @is_file($file)) {
				$sheetStructure = GeneralUtility::xml2array(GeneralUtility::getUrl($file));
			}
		}
		return $sheetStructure;
	}

	/**
	 * Modify data structure of a single "sheet"
	 * Sets "secondary" data like sheet names and so on, but does NOT modify single elements
	 *
	 * @param array $dataStructure Given data structure
	 * @param array $pageTsOfSheet Page Ts config of given field
	 * @return array Modified data structure
	 */
	protected function modifySingleSheetInformation(array $dataStructure, array $pageTsOfSheet) {
		// Return if no elements defined
		if (!isset($dataStructure['ROOT']['el']) || !is_array($dataStructure['ROOT']['el'])) {
			return $dataStructure;
		}

		// Rename sheet (tab)
		if (!empty($pageTsOfSheet['sheetTitle'])) {
			$dataStructure['ROOT']['sheetTitle'] = $pageTsOfSheet['sheetTitle'];
		}
		// Set sheet description (tab)
		if (!empty($pageTsOfSheet['sheetDescription'])) {
			$dataStructure['ROOT']['sheetDescription'] = $pageTsOfSheet['sheetDescription'];
		}
		// Set sheet short description (tab)
		if (!empty($pageTsOfSheet['sheetShortDescr'])) {
			$dataStructure['ROOT']['sheetShortDescr'] = $pageTsOfSheet['sheetShortDescr'];
		}

		return $dataStructure;
	}

	/**
	 * Add new sheet languages not yet in data values and remove invalid ones
	 *
	 * databaseRow['aFlex']['data']['sDEF'] = array('lDEF', 'lNotAllowed');
	 * allowedLanguageKeys = array('lDEF', 'lNEW')
	 * -> databaseRow['aFlex']['data']['sDEF'] = array('lDEF', 'lNEW');
	 *
	 * @param array $result Result array
	 * @param string $fieldName Current handle field name
	 * @param array $allowedKeys List of allowed keys
	 * @return array Modified result
	 */
	protected function setLanguageSheetsInDataValues(array $result, $fieldName, array $allowedKeys) {
		$availableLanguageCodes = $result['processedTca']['columns'][$fieldName]['config']['ds']['meta']['availableLanguageCodes'];
		$valueArray = [];
		if (isset($result['databaseRow'][$fieldName]['data']) && is_array($result['databaseRow'][$fieldName]['data'])) {
			$valueArray = $result['databaseRow'][$fieldName]['data'];
		}
		foreach ($valueArray as $sheetName => $sheetLanguages) {
			// Add iso code with empty array if it does not yet exist in data
			// and remove codes from data that do not exist in $allowed
			$result['databaseRow'][$fieldName]['data'][$sheetName]
				= array_intersect_key(array_merge($allowedKeys, $sheetLanguages), $allowedKeys);
		}
		return $result;
	}

	/**
	 * Remove invalid keys from data value array the user has no access to
	 * or that were removed or similar to prevent any rendering of this stuff
	 *
	 * Handles this for "normal" fields and also for section container element values.
	 *
	 * @param array $result Result array
	 * @param string $fieldName Current handle field name
	 * @param array $allowedKeys List of allowed keys
	 * @return array Modified result
	 */
	protected function setLanguageValueLevelValues(array $result, $fieldName, $allowedKeys) {
		$valueArray = [];
		if (isset($result['databaseRow'][$fieldName]['data']) && is_array($result['databaseRow'][$fieldName]['data'])) {
			$valueArray = $result['databaseRow'][$fieldName]['data'];
		}
		foreach ($valueArray as $sheetName => $sheetLanguages) {
			if (!is_array($sheetLanguages)) {
				continue;
			}
			foreach ($sheetLanguages as $languageName => $languageFields) {
				if (!is_array($languageFields)) {
					continue;
				}
				foreach ($languageFields as $flexFieldName => $fieldValues) {
					if (!is_array($fieldValues)) {
						continue;
					}
					$allowedSingleValues = [];
					foreach ($fieldValues as $fieldValueName => $fieldValueValue) {
						if (is_array($fieldValueValue) && $fieldValueName === 'el') {
							// A section container
							foreach ($fieldValueValue as $sectionNumber => $sectionElementArray) {
								if (is_array($sectionElementArray)) {
									$allowedSingleValues['el'][$sectionNumber] = [];
									foreach ($sectionElementArray as $sectionElementName => $containerElementArray) {
										if (isset($containerElementArray['el']) && is_array($containerElementArray['el']) && !empty($containerElementArray['el'])) {
											foreach ($containerElementArray['el'] as $aContainerElementName => $aContainerElementValues) {
												if (is_array($aContainerElementValues)) {
													foreach ($aContainerElementValues as $aContainerElementValueKey => $aContainerElementValueValue) {
														if (array_key_exists($aContainerElementValueKey, $allowedKeys)) {
															$allowedSingleValues['el'][$sectionNumber][$sectionElementName]
															['el'][$aContainerElementName][$aContainerElementValueKey] = $aContainerElementValueValue;
														}
													}
												} else {
													$allowedSingleValues['el'][$sectionNumber][$sectionElementName]['el']
													[$aContainerElementName] = $aContainerElementValues;
												}
											}
										} else {
											$allowedSingleValues['el'][$sectionNumber][$sectionElementName] = $containerElementArray;
										}
									}
								} else {
									$allowedSingleValues = $sectionElementArray;
								}
							}
						} else {
							// "normal" value field
							if (array_key_exists($fieldValueName, $allowedKeys)) {
								$allowedSingleValues[$fieldValueName] = $fieldValueValue;
							}
						}
					}
					$result['databaseRow'][$fieldName]['data'][$sheetName][$languageName][$flexFieldName] = $allowedSingleValues;
				}
			}
		}
		return $result;
	}

	/**
	 * On-the-fly migration for flex form "TCA"
	 *
	 * @param array $result Result array
	 * @param string $fieldName Currently handled field name
	 * @return array Modified result
	 * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8. This can be removed *if* no additional TCA migration is added with CMS 8, see class TcaMigration
	 */
	protected function migrateFlexformTcaDataStructureElements(array $result, $fieldName) {
		$modifiedDataStructure = $result['processedTca']['columns'][$fieldName]['config']['ds'];
		$modifiedDataStructure = $this->migrateFlexformTcaRecursive($modifiedDataStructure, $result['tableName'], $fieldName);
		$result['processedTca']['columns'][$fieldName]['config']['ds'] = $modifiedDataStructure;
		return $result;
	}

	/**
	 * Recursively migrate flex form TCA
	 *
	 * @param array $structure Given hierarchy
	 * @param string $table
	 * @param string $fieldName
	 * @return array Modified hierarchy
	 */

	protected function migrateFlexformTcaRecursive($structure, $table, $fieldName) {
		$newStructure = [];
		foreach ($structure as $key => $value) {
			if ($key === 'el' && is_array($value)) {
				$newSubStructure = [];
				$tcaMigration = GeneralUtility::makeInstance(TcaMigration::class);
				foreach ($value as $subKey => $subValue) {
					// On-the-fly migration for flex form "TCA"
					// @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8. This can be removed *if* no additional TCA migration is added with CMS 8, see class TcaMigration
					$dummyTca = array(
						'dummyTable' => array(
							'columns' => array(
								'dummyField' => $subValue,
							),
						),
					);
					$migratedTca = $tcaMigration->migrate($dummyTca);
					$messages = $tcaMigration->getMessages();
					if (!empty($messages)) {
						$context = 'FormEngine did an on-the-fly migration of a flex form data structure. This is deprecated and will be removed'
							. ' with TYPO3 CMS 8. Merge the following changes into the flex form definition of table "' . $table . '"" in field "' . $fieldName . '"":';
						array_unshift($messages, $context);
						GeneralUtility::deprecationLog(implode(LF, $messages));
					}
					$newSubStructure[$subKey] = $migratedTca['dummyTable']['columns']['dummyField'];
				}
				$value = $newSubStructure;
			}
			if (is_array($value)) {
				$value = $this->migrateFlexformTcaRecursive($value, $table, $fieldName);
			}
			$newStructure[$key] = $value;
		}
		return $newStructure;
	}

	/**
	 * @return BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}

}
