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
use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Backend\Form\ElementConditionMatcher;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Abstract container has various methods used by the container classes
 */
abstract class AbstractContainer extends AbstractNode {

	/**
	 * Array where records in the default language are stored. (processed by transferdata)
	 *
	 * @var array
	 */
	protected $defaultLanguageData = array();

	/**
	 * Array where records in the default language are stored (raw without any processing. used for making diff).
	 * This is the unserialized content of configured TCA ['ctrl']['transOrigDiffSourceField'] field, typically l18n_diffsource
	 *
	 * @var array
	 */
	protected $defaultLanguageDataDiff = array();

	/**
	 * Contains row data of "additional" language overlays
	 * array(
	 *   $table:$uid => array(
	 *     $additionalPreviewLanguageUid => $rowData
	 *   )
	 * )
	 *
	 * @var array
	 */
	protected $additionalPreviewLanguageData = array();

	/**
	 * Calculate and return the current type value of a record
	 *
	 * @param string $table The table name. MUST be in $GLOBALS['TCA']
	 * @param array $row The row from the table, should contain at least the "type" field, if applicable.
	 * @return string Return the "type" value for this record, ready to pick a "types" configuration from the $GLOBALS['TCA'] array.
	 * @throws \RuntimeException
	 */
	protected function getRecordTypeValue($table, array $row) {
		$typeNum = 0;
		$field = $GLOBALS['TCA'][$table]['ctrl']['type'];
		if ($field) {
			if (strpos($field, ':') !== FALSE) {
				list($pointerField, $foreignTypeField) = explode(':', $field);
				$fieldConfig = $GLOBALS['TCA'][$table]['columns'][$pointerField]['config'];
				$relationType = $fieldConfig['type'];
				if ($relationType === 'select') {
					$foreignUid = $row[$pointerField];
					$foreignTable = $fieldConfig['foreign_table'];
				} elseif ($relationType === 'group') {
					$values = FormEngineUtility::extractValuesOnlyFromValueLabelList($row[$pointerField]);
					list(, $foreignUid) = GeneralUtility::revExplode('_', $values[0], 2);
					$allowedTables = explode(',', $fieldConfig['allowed']);
					// Always take the first configured table.
					$foreignTable = $allowedTables[0];
				} else {
					throw new \RuntimeException('TCA Foreign field pointer fields are only allowed to be used with group or select field types.', 1325861239);
				}
				if ($foreignUid) {
					$foreignRow = BackendUtility::getRecord($foreignTable, $foreignUid, $foreignTypeField);
					$this->registerDefaultLanguageData($foreignTable, $foreignRow);
					if ($foreignRow[$foreignTypeField]) {
						$foreignTypeFieldConfig = $GLOBALS['TCA'][$table]['columns'][$field];
						$typeNum = $this->overrideTypeWithValueFromDefaultLanguageRecord($foreignTable, $foreignRow, $foreignTypeField, $foreignTypeFieldConfig);
					}
				}
			} else {
				$typeFieldConfig = $GLOBALS['TCA'][$table]['columns'][$field];
				$typeNum = $this->overrideTypeWithValueFromDefaultLanguageRecord($table, $row, $field, $typeFieldConfig);
			}
		}
		if (empty($typeNum)) {
			// If that value is an empty string, set it to "0" (zero)
			$typeNum = 0;
		}
		// If current typeNum doesn't exist, set it to 0 (or to 1 for historical reasons, if 0 doesn't exist)
		if (!$GLOBALS['TCA'][$table]['types'][$typeNum]) {
			$typeNum = $GLOBALS['TCA'][$table]['types']['0'] ? 0 : 1;
		}
		// Force to string. Necessary for eg '-1' to be recognized as a type value.
		return (string)$typeNum;
	}

	/**
	 * Producing an array of field names NOT to display in the form,
	 * based on settings from subtype_value_field, bitmask_excludelist_bits etc.
	 * Notice, this list is in NO way related to the "excludeField" flag
	 *
	 * @param string $table Table name, MUST be in $GLOBALS['TCA']
	 * @param array $row A record from table.
	 * @param string $typeNum A "type" pointer value, probably the one calculated based on the record array.
	 * @return array Array with field names as values. The field names are those which should NOT be displayed "anyways
	 */
	protected function getExcludeElements($table, $row, $typeNum) {
		$excludeElements = array();
		// If a subtype field is defined for the type
		if ($GLOBALS['TCA'][$table]['types'][$typeNum]['subtype_value_field']) {
			$subTypeField = $GLOBALS['TCA'][$table]['types'][$typeNum]['subtype_value_field'];
			if (trim($GLOBALS['TCA'][$table]['types'][$typeNum]['subtypes_excludelist'][$row[$subTypeField]])) {
				$excludeElements = GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$table]['types'][$typeNum]['subtypes_excludelist'][$row[$subTypeField]], TRUE);
			}
		}
		// If a bitmask-value field has been configured, then find possible fields to exclude based on that:
		if ($GLOBALS['TCA'][$table]['types'][$typeNum]['bitmask_value_field']) {
			$subTypeField = $GLOBALS['TCA'][$table]['types'][$typeNum]['bitmask_value_field'];
			$sTValue = MathUtility::forceIntegerInRange($row[$subTypeField], 0);
			if (is_array($GLOBALS['TCA'][$table]['types'][$typeNum]['bitmask_excludelist_bits'])) {
				foreach ($GLOBALS['TCA'][$table]['types'][$typeNum]['bitmask_excludelist_bits'] as $bitKey => $eList) {
					$bit = substr($bitKey, 1);
					if (MathUtility::canBeInterpretedAsInteger($bit)) {
						$bit = MathUtility::forceIntegerInRange($bit, 0, 30);
						if ($bitKey[0] === '-' && !($sTValue & pow(2, $bit)) || $bitKey[0] === '+' && $sTValue & pow(2, $bit)) {
							$excludeElements = array_merge($excludeElements, GeneralUtility::trimExplode(',', $eList, TRUE));
						}
					}
				}
			}
		}
		return $excludeElements;
	}

	/**
	 * The requested field value will be overridden with the data from the default
	 * language if the field is configured accordingly.
	 *
	 * @param string $table Table name of the record being edited
	 * @param array $row Record array of the record being edited in current language
	 * @param string $field Field name represented by $item
	 * @param array $fieldConf Content of $PA['fieldConf']
	 * @return string Unprocessed field value merged with default language data if needed
	 */
	protected function overrideTypeWithValueFromDefaultLanguageRecord($table, array $row, $field, $fieldConf) {
		$value = $row[$field];
		if (is_array($this->defaultLanguageData[$table . ':' . $row['uid']])) {
			if (
				$fieldConf['l10n_mode'] === 'exclude'
				|| ($fieldConf['l10n_mode'] === 'mergeIfNotBlank' && trim($row[$field] === ''))
			) {
				$value = $this->defaultLanguageData[$table . ':' . $row['uid']][$field];
			}
		}
		return $value;
	}

	/**
	 * Return a list without excluded elements.
	 *
	 * @param array $fieldsArray Typically coming from types show item
	 * @param array $excludeElements Field names to be excluded
	 * @return array $fieldsArray without excluded elements
	 */
	protected function removeExcludeElementsFromFieldArray(array $fieldsArray, array $excludeElements) {
		$newFieldArray = array();
		foreach ($fieldsArray as $fieldString) {
			$fieldArray = $this->explodeSingleFieldShowItemConfiguration($fieldString);
			$fieldName = $fieldArray['fieldName'];
			// It doesn't make sense to exclude palettes and tabs
			if (!in_array($fieldName, $excludeElements, TRUE) || $fieldName === '--palette--' || $fieldName === '--div--') {
				$newFieldArray[] = $fieldString;
			}
		}
		return $newFieldArray;
	}


	/**
	 * A single field of TCA 'types' 'showitem' can have four semicolon separated configuration options:
	 *   fieldName: Name of the field to be found in TCA 'columns' section
	 *   fieldLabel: An alternative field label
	 *   paletteName: Name of a palette to be found in TCA 'palettes' section that is rendered after this field
	 *   extra: Special configuration options of this field
	 *
	 * @param string $field Semicolon separated field configuration
	 * @throws \RuntimeException
	 * @return array
	 */
	protected function explodeSingleFieldShowItemConfiguration($field) {
		$fieldArray = GeneralUtility::trimExplode(';', $field);
		if (empty($fieldArray[0])) {
			throw new \RuntimeException('Field must not be empty', 1426448465);
		}
		return array(
			'fieldName' => $fieldArray[0],
			'fieldLabel' => $fieldArray[1] ?: NULL,
			'paletteName' => $fieldArray[2] ?: NULL,
		);
	}

	/**
	 * Will register data from original language records if the current record is a translation of another.
	 * The original data is shown with the edited record in the form.
	 * The information also includes possibly diff-views of what changed in the original record.
	 * Function called from outside (see alt_doc.php + quick edit) before rendering a form for a record
	 *
	 * @param string $table Table name of the record being edited
	 * @param array $rec Record array of the record being edited
	 * @return void
	 */
	protected function registerDefaultLanguageData($table, $rec) {
		// @todo: early return here if the arrays are already filled?

		// Add default language:
		if (
			$GLOBALS['TCA'][$table]['ctrl']['languageField'] && $rec[$GLOBALS['TCA'][$table]['ctrl']['languageField']] > 0
			&& $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']
			&& (int)$rec[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] > 0
		) {
			$lookUpTable = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerTable']
				? $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerTable']
				: $table;
			// Get data formatted:
			$this->defaultLanguageData[$table . ':' . $rec['uid']] = BackendUtility::getRecordWSOL(
				$lookUpTable,
				(int)$rec[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']]
			);
			// Get data for diff:
			if ($GLOBALS['TCA'][$table]['ctrl']['transOrigDiffSourceField']) {
				$this->defaultLanguageDataDiff[$table . ':' . $rec['uid']] = unserialize($rec[$GLOBALS['TCA'][$table]['ctrl']['transOrigDiffSourceField']]);
			}
			// If there are additional preview languages, load information for them also:
			foreach ($this->globalOptions['additionalPreviewLanguages'] as $prL) {
				/** @var $translationConfigurationProvider TranslationConfigurationProvider */
				$translationConfigurationProvider = GeneralUtility::makeInstance(TranslationConfigurationProvider::class);
				$translationInfo = $translationConfigurationProvider->translationInfo($lookUpTable, (int)$rec[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']], $prL['uid']);
				if (is_array($translationInfo['translations']) && is_array($translationInfo['translations'][$prL['uid']])) {
					$this->additionalPreviewLanguageData[$table . ':' . $rec['uid']][$prL['uid']] = BackendUtility::getRecordWSOL($table, (int)$translationInfo['translations'][$prL['uid']]['uid']);
				}
			}
		}
	}

	/**
	 * Evaluate condition of flex forms
	 *
	 * @param string $displayCondition The condition to evaluate
	 * @param array $flexFormData Given data the condition is based on
	 * @param string $flexFormLanguage Flex form language key
	 * @return bool TRUE if condition matched
	 */
	protected function evaluateFlexFormDisplayCondition($displayCondition, $flexFormData, $flexFormLanguage) {
		$elementConditionMatcher = GeneralUtility::makeInstance(ElementConditionMatcher::class);

		$splitCondition = GeneralUtility::trimExplode(':', $displayCondition);
		$skipCondition = FALSE;
		$fakeRow = array();
		switch ($splitCondition[0]) {
			case 'FIELD':
				list($sheetName, $fieldName) = GeneralUtility::trimExplode('.', $splitCondition[1], FALSE, 2);
				$fieldValue = $flexFormData[$sheetName][$flexFormLanguage][$fieldName];
				$splitCondition[1] = $fieldName;
				$displayCondition = join(':', $splitCondition);
				$fakeRow = array($fieldName => $fieldValue);
				break;
			case 'HIDE_FOR_NON_ADMINS':

			case 'VERSION':

			case 'HIDE_L10N_SIBLINGS':

			case 'EXT':
				break;
			case 'REC':
				$fakeRow = array('uid' => $this->globalOptions['databaseRow']['uid']);
				break;
			default:
				$skipCondition = TRUE;
		}
		if ($skipCondition) {
			return TRUE;
		} else {
			return $elementConditionMatcher->match($displayCondition, $fakeRow, 'vDEF');
		}
	}

	/**
	 * Rendering preview output of a field value which is not shown as a form field but just outputted.
	 *
	 * @param string $value The value to output
	 * @param array $config Configuration for field.
	 * @param string $field Name of field.
	 * @return string HTML formatted output
	 */
	protected function previewFieldValue($value, $config, $field = '') {
		if ($config['config']['type'] === 'group' && ($config['config']['internal_type'] === 'file' || $config['config']['internal_type'] === 'file_reference')) {
			// Ignore upload folder if internal_type is file_reference
			if ($config['config']['internal_type'] === 'file_reference') {
				$config['config']['uploadfolder'] = '';
			}
			$table = 'tt_content';
			// Making the array of file items:
			$itemArray = GeneralUtility::trimExplode(',', $value, TRUE);
			// Showing thumbnails:
			$thumbnail = '';
			$imgs = array();
			foreach ($itemArray as $imgRead) {
				$imgParts = explode('|', $imgRead);
				$imgPath = rawurldecode($imgParts[0]);
				$rowCopy = array();
				$rowCopy[$field] = $imgPath;
				// Icon + click menu:
				$absFilePath = GeneralUtility::getFileAbsFileName($config['config']['uploadfolder'] ? $config['config']['uploadfolder'] . '/' . $imgPath : $imgPath);
				$fileInformation = pathinfo($imgPath);
				$fileIcon = IconUtility::getSpriteIconForFile(
					$imgPath,
					array(
						'title' => htmlspecialchars($fileInformation['basename'] . ($absFilePath && @is_file($absFilePath) ? ' (' . GeneralUtility::formatSize(filesize($absFilePath)) . 'bytes)' : ' - FILE NOT FOUND!'))
					)
				);
				$imgs[] =
					'<span class="text-nowrap">' .
					BackendUtility::thumbCode(
						$rowCopy,
						$table,
						$field,
						'',
						'thumbs.php',
						$config['config']['uploadfolder'], 0, ' align="middle"'
					) .
					($absFilePath ? $this->getControllerDocumentTemplate()->wrapClickMenuOnIcon($fileIcon, $absFilePath, 0, 1, '', '+copy,info,edit,view') : $fileIcon) .
					$imgPath .
					'</span>';
			}
			return implode('<br />', $imgs);
		} else {
			return nl2br(htmlspecialchars($value));
		}
	}

	/**
	 * @return DocumentTemplate
	 */
	protected function getControllerDocumentTemplate() {
		return $GLOBALS['SOBE']->doc;
	}

}
