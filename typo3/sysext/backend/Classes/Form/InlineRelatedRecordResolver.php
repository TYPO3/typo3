<?php
namespace TYPO3\CMS\Backend\Form;

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
use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * Resolve inline relations.
 *
 * This class contains various methods to fetch inline child records based on configurations
 * and to prepare new child records.
 */
class InlineRelatedRecordResolver {

	/**
	 * Get the related records of the embedding item, this could be 1:n, m:n.
	 * Returns an associative array with the keys records and count. 'count' contains only real existing records on the current parent record.
	 *
	 * @param string $table The table name of the record
	 * @param string $field The field name which this element is supposed to edit
	 * @param array $row The record data array where the value(s) for the field can be found
	 * @param array $PA An array with additional configuration options.
	 * @param array $config (Redundant) content of $PA['fieldConf']['config'] (for convenience)
	 * @param integer $inlineFirstPid Inline first pid
	 * @return array The records related to the parent item as associative array.
	 */
	public function getRelatedRecords($table, $field, $row, $PA, $config, $inlineFirstPid) {
		$language = 0;
		$elements = $PA['itemFormElValue'];
		$foreignTable = $config['foreign_table'];
		$localizationMode = BackendUtility::getInlineLocalizationMode($table, $config);
		if ($localizationMode !== FALSE) {
			$language = (int)$row[$GLOBALS['TCA'][$table]['ctrl']['languageField']];
			$transOrigPointer = (int)$row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']];
			$transOrigTable = BackendUtility::getOriginalTranslationTable($table);

			if ($language > 0 && $transOrigPointer) {
				// Localization in mode 'keep', isn't a real localization, but keeps the children of the original parent record:
				if ($localizationMode === 'keep') {
					$transOrigRec = $this->getRecord($transOrigTable, $transOrigPointer);
					$elements = $transOrigRec[$field];
				} elseif ($localizationMode === 'select') {
					$transOrigRec = $this->getRecord($transOrigTable, $transOrigPointer);
					$fieldValue = $transOrigRec[$field];

					// Checks if it is a flexform field
					if ($GLOBALS['TCA'][$table]['columns'][$field]['config']['type'] === 'flex') {
						$flexFormParts = FormEngineUtility::extractFlexFormParts($PA['itemFormElName']);
						$flexData = GeneralUtility::xml2array($fieldValue);
						/** @var  $flexFormTools  FlexFormTools */
						$flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
						$flexFormFieldValue = $flexFormTools->getArrayValueByPath($flexFormParts, $flexData);

						if ($flexFormFieldValue !== NULL) {
							$fieldValue = $flexFormFieldValue;
						}
					}

					$recordsOriginal = $this->getRelatedRecordsArray($foreignTable, $fieldValue);
				}
			}
		}
		$records = $this->getRelatedRecordsArray($foreignTable, $elements);
		$relatedRecords = array('records' => $records, 'count' => count($records));
		// Merge original language with current localization and show differences:
		if (!empty($recordsOriginal)) {
			$options = array(
				'showPossible' => isset($config['appearance']['showPossibleLocalizationRecords']) && $config['appearance']['showPossibleLocalizationRecords'],
				'showRemoved' => isset($config['appearance']['showRemovedLocalizationRecords']) && $config['appearance']['showRemovedLocalizationRecords']
			);
			// Either show records that possibly can localized or removed
			if ($options['showPossible'] || $options['showRemoved']) {
				$relatedRecords['records'] = $this->getLocalizationDifferences($foreignTable, $options, $recordsOriginal, $records);
				// Otherwise simulate localizeChildrenAtParentLocalization behaviour when creating a new record
				// (which has language and translation pointer values set)
			} elseif (!empty($config['behaviour']['localizeChildrenAtParentLocalization']) && !MathUtility::canBeInterpretedAsInteger($row['uid'])) {
				if (!empty($GLOBALS['TCA'][$foreignTable]['ctrl']['transOrigPointerField'])) {
					$foreignLanguageField = $GLOBALS['TCA'][$foreignTable]['ctrl']['languageField'];
				}
				if (!empty($GLOBALS['TCA'][$foreignTable]['ctrl']['transOrigPointerField'])) {
					$foreignTranslationPointerField = $GLOBALS['TCA'][$foreignTable]['ctrl']['transOrigPointerField'];
				}
				// Duplicate child records of default language in form
				foreach ($recordsOriginal as $record) {
					if (!empty($foreignLanguageField)) {
						$record[$foreignLanguageField] = $language;
					}
					if (!empty($foreignTranslationPointerField)) {
						$record[$foreignTranslationPointerField] = $record['uid'];
					}
					$newId = uniqid('NEW', TRUE);
					$record['uid'] = $newId;
					$record['pid'] = $$inlineFirstPid;
					$relatedRecords['records'][$newId] = $record;
				}
			}
		}
		return $relatedRecords;
	}

	/**
	 * Wrapper. Calls getRecord in case of a new record should be created.
	 *
	 * @param int $pid The pid of the page the record should be stored (only relevant for NEW records)
	 * @param string $table The table to fetch data from (= foreign_table)
	 * @return array A record row from the database post-processed by \TYPO3\CMS\Backend\Form\DataPreprocessor
	 */
	public function getNewRecord($pid, $table) {
		$record = $this->getRecord($table, $pid, 'new');
		$record['uid'] = uniqid('NEW', TRUE);

		$newRecordPid = $pid;
		$pageTS = BackendUtility::getPagesTSconfig($pid);
		if (isset($pageTS['TCAdefaults.'][$table . '.']['pid']) && MathUtility::canBeInterpretedAsInteger($pageTS['TCAdefaults.'][$table . '.']['pid'])) {
			$newRecordPid = $pageTS['TCAdefaults.'][$table . '.']['pid'];
		}

		$record['pid'] = $newRecordPid;

		return $record;
	}

	/**
	 * Get a single record row for a TCA table from the database.
	 * \TYPO3\CMS\Backend\Form\DataPreprocessor is used for "upgrading" the
	 * values, especially the relations.
	 * Used in inline context
	 *
	 * @param string $table The table to fetch data from (= foreign_table)
	 * @param string $uid The uid of the record to fetch, or the pid if a new record should be created
	 * @param string $cmd The command to perform, empty or 'new'
	 * @return array A record row from the database post-processed by \TYPO3\CMS\Backend\Form\DataPreprocessor
	 * @internal
	 */
	public function getRecord($table, $uid, $cmd = '') {
		$backendUser = $this->getBackendUserAuthentication();
		// Fetch workspace version of a record (if any):
		if ($cmd !== 'new' && $backendUser->workspace !== 0 && BackendUtility::isTableWorkspaceEnabled($table)) {
			$workspaceVersion = BackendUtility::getWorkspaceVersionOfRecord($backendUser->workspace, $table, $uid, 'uid,t3ver_state');
			if ($workspaceVersion !== FALSE) {
				$versionState = VersionState::cast($workspaceVersion['t3ver_state']);
				if ($versionState->equals(VersionState::DELETE_PLACEHOLDER)) {
					return FALSE;
				}
				$uid = $workspaceVersion['uid'];
			}
		}
		/** @var $trData DataPreprocessor */
		$trData = GeneralUtility::makeInstance(DataPreprocessor::class);
		$trData->addRawData = TRUE;
		$trData->lockRecords = 1;
		// If a new record should be created
		$trData->fetchRecord($table, $uid, $cmd === 'new' ? 'new' : '');
		$rec = reset($trData->regTableItems_data);
		return $rec;
	}

	/**
	 * Gets the related records of the embedding item, this could be 1:n, m:n.
	 *
	 * @param string $table The table name of the record
	 * @param string $itemList The list of related child records
	 * @return array The records related to the parent item
	 */
	protected function getRelatedRecordsArray($table, $itemList) {
		$records = array();
		$itemArray = FormEngineUtility::getInlineRelatedRecordsUidArray($itemList);
		// Perform modification of the selected items array:
		foreach ($itemArray as $uid) {
			// Get the records for this uid using \TYPO3\CMS\Backend\Form\DataPreprocessor
			if ($record = $this->getRecord($table, $uid)) {
				$records[$uid] = $record;
			}
		}
		return $records;
	}

	/**
	 * Gets the difference between current localized structure and the original language structure.
	 * If there are records which once were localized but don't exist in the original version anymore, the record row is marked with '__remove'.
	 * If there are records which can be localized and exist only in the original version, the record row is marked with '__create' and '__virtual'.
	 *
	 * @param string $table The table name of the parent records
	 * @param array $options Options defining what kind of records to display
	 * @param array $recordsOriginal The uids of the child records of the original language
	 * @param array $recordsLocalization The uids of the child records of the current localization
	 * @return array Merged array of uids of the child records of both versions
	 */
	protected function getLocalizationDifferences($table, array $options, array $recordsOriginal, array $recordsLocalization) {
		$records = array();
		$transOrigPointerField = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];
		// Compare original to localized version of the records:
		foreach ($recordsLocalization as $uid => $row) {
			// If the record points to a original translation which doesn't exist anymore, it could be removed:
			if (isset($row[$transOrigPointerField]) && $row[$transOrigPointerField] > 0) {
				$transOrigPointer = $row[$transOrigPointerField];
				if (isset($recordsOriginal[$transOrigPointer])) {
					unset($recordsOriginal[$transOrigPointer]);
				} elseif ($options['showRemoved']) {
					$row['__remove'] = TRUE;
				}
			}
			$records[$uid] = $row;
		}
		// Process the remaining records in the original unlocalized parent:
		if ($options['showPossible']) {
			foreach ($recordsOriginal as $uid => $row) {
				$row['__create'] = TRUE;
				$row['__virtual'] = TRUE;
				$records[$uid] = $row;
			}
		}
		return $records;
	}

	/**
	 * @return BackendUserAuthentication
	 */
	protected function getBackendUserAuthentication() {
		return $GLOBALS['BE_USER'];
	}

}