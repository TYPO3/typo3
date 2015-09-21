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

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Determine the title of a record and write it to $result['recordTitle'].
 *
 * TCA ctrl fields like label and label_alt are evaluated and their
 * current values from databaseRow used to create the title.
 */
class TcaRecordTitle implements FormDataProviderInterface {

	/**
	 * Enrich the processed record information with the resolved title
	 *
	 * @param array $result Incoming result array
	 * @return array Modified array
	 */
	public function addData(array $result) {
		if (!isset($result['processedTca']['ctrl']['label'])) {
			throw new \UnexpectedValueException(
				'TCA of table ' . $result['tableName'] . ' misses required [\'ctrl\'][\'label\'] definition.',
				1443706103
			);
		}

		if (isset($result['processedTca']['ctrl']['label_userFunc'])) {
			// userFunc takes precedence over everything
			$parameters = [
				'table' => $result['tableName'],
				'row' => $result['databaseRow'],
				'title' => '',
				'options' => isset($result['processedTca']['ctrl']['label_userFunc_options'])
					? $result['processedTca']['ctrl']['label_userFunc_options']
					: [],
			];
			$null = NULL;
			GeneralUtility::callUserFunction($result['processedTca']['ctrl']['label_userFunc'], $parameters, $null);
			$result['recordTitle'] = $parameters['title'];
		} else {
			$result = $this->getRecordTitleByLabelProperties($result);
		}

		return $result;
	}

	/**
	 * Build the record title from label, label_alt and label_alt_force properties
	 *
	 * @param array $result Incoming result array
	 * @return array Modified result array
	 */
	protected function getRecordTitleByLabelProperties(array $result) {
		$titles = [];
		$titleByLabel = $this->getRecordTitleForField($result['processedTca']['ctrl']['label'], $result);
		if (!empty($titleByLabel)) {
			$titles[] = $titleByLabel;
		}

		$labelAltForce = isset($result['processedTca']['ctrl']['label_alt_force'])
			? (bool)$result['processedTca']['ctrl']['label_alt_force']
			: FALSE;
		if (!empty($result['processedTca']['ctrl']['label_alt']) && ($labelAltForce || empty($titleByLabel))) {
			// Dive into label_alt evaluation if label_alt_force is set or if label did not came up with a title yet
			$labelAltFields = GeneralUtility::trimExplode(',', $result['processedTca']['ctrl']['label_alt'], TRUE);
			foreach ($labelAltFields as $fieldName) {
				$titleByLabelAlt = $this->getRecordTitleForField($fieldName, $result);
				if (!empty($titleByLabelAlt)) {
					$titles[] = $titleByLabelAlt;
				}
				if (!$labelAltForce && !empty($titleByLabelAlt)) {
					// label_alt_force creates a comma separated list of multiple fields.
					// If not set, one found field with content is enough
					break;
				}
			}
		}

		$result['recordTitle'] = implode(', ', $titles);
		return $result;
	}

	/**
	 * Record title of a single field
	 *
	 * @param string $fieldName Field to handle
	 * @param array $result Incoming result array
	 * @return string
	 */
	protected function getRecordTitleForField($fieldName, $result) {
		if ($fieldName === 'uid') {
			// uid return field content directly since it usually has not TCA definition
			return $result['databaseRow']['uid'];
		}

		if (!isset($result['processedTca']['columns'][$fieldName]['config']['type'])
			|| !is_string($result['processedTca']['columns'][$fieldName]['config']['type'])
		) {
			return '';
		}

		$recordTitle = '';
		$rawValue = NULL;
		if (array_key_exists($fieldName, $result['databaseRow'])) {
			$rawValue = $result['databaseRow'][$fieldName];
		}
		$fieldConfig = $result['processedTca']['columns'][$fieldName]['config'];
		switch ($fieldConfig['type']) {
			case 'radio':
				$recordTitle = $this->getRecordTitleForRadioType($rawValue, $fieldConfig);
				break;
			case 'inline':
				// intentional fall-through
			case 'select':
				$recordTitle = $this->getRecordTitleForSelectType($rawValue, $fieldConfig);
				break;
			case 'group':
				$recordTitle = $this->getRecordTitleForGroupType($rawValue, $fieldConfig);
				break;
			case 'check':
				$recordTitle = $this->getRecordTitleForCheckboxType($rawValue, $fieldConfig);
				break;
			case 'input':
				$recordTitle = $this->getRecordTitleForInputType($rawValue, $fieldConfig);
				break;
			case 'text':
				$recordTitle = $this->getRecordTitleForTextType($rawValue);
			case 'flex':
				// TODO: Check if and how a label could be generated from flex field data
			default:

		}

		return $recordTitle;
	}

	/**
	 * Return the record title for radio fields
	 *
	 * @param mixed $value Current database value of this field
	 * @param array $fieldConfig TCA field configuration
	 * @return string
	 */
	protected function getRecordTitleForRadioType($value, $fieldConfig) {
		if (!isset($fieldConfig['items']) || !is_array($fieldConfig['items'])) {
			return '';
		}
		foreach ($fieldConfig['items'] as $item) {
			list($itemLabel, $itemValue) = $item;
			if ((string)$value === (string)$itemValue) {
				return $itemLabel;
			}
		}
		return '';
	}

	/**
	 * Return the record title for database records
	 *
	 * @param mixed $value Current database value of this field
	 * @param array $fieldConfig TCA field configuration
	 * @return string
	 */
	protected function getRecordTitleForSelectType($value, $fieldConfig) {
		if (!is_array($value)) {
			return '';
		}
		$labelParts = [];
		foreach ($value as $itemValue) {
			$itemKey = array_search($itemValue, array_column($fieldConfig['items'], 1));
			if ($itemKey !== FALSE) {
				$labelParts[] = $fieldConfig['items'][$itemKey][0];
			}
		}
		$title = implode(', ', $labelParts);
		if (empty($title) && !empty($value)) {
			$title = implode(', ', $value);
		}
		return $title;
	}

	/**
	 * Return the record title for database records
	 *
	 * @param mixed $value Current database value of this field
	 * @param array $fieldConfig TCA field configuration
	 * @return string
	 */
	protected function getRecordTitleForGroupType($value, $fieldConfig) {
		if ($fieldConfig['internal_type'] !== 'db') {
			return implode(', ', GeneralUtility::trimExplode(',', $value, TRUE));
		}
		$labelParts = array_map(
			function($rawLabelItem) {
				return array_pop(GeneralUtility::trimExplode('|', $rawLabelItem, TRUE, 2));
			},
			GeneralUtility::trimExplode(',', $value, TRUE)
		);
		if (!empty($labelParts)) {
			sort($labelParts);
			return implode(', ', $labelParts);
		}
		return '';
	}

	/**
	 * Returns the record title for checkbox fields
	 *
	 * @param mixed $value Current database value of this field
	 * @param array $fieldConfig TCA field configuration
	 * @return string
	 */
	protected function getRecordTitleForCheckboxType($value, $fieldConfig) {
		$languageService = $this->getLanguageService();
		if (empty($fieldConfig['items']) || !is_array($fieldConfig['items'])) {
			$title = (bool)$value
				? $languageService->sL('LLL:EXT:lang/locallang_common.xlf:yes')
				: $languageService->sL('LLL:EXT:lang/locallang_common.xlf:no');
		} else {
			$labelParts = [];
			foreach ($fieldConfig['items'] as $key => $val) {
				if ($value & pow(2, $key)) {
					$labelParts[] = $val[0];
				}
			}
			$title = implode(', ', $labelParts);
		}
		return $title;
	}

	/**
	 * Returns the record title for input fields
	 *
	 * @param mixed $value Current database value of this field
	 * @param array $fieldConfig TCA field configuration
	 * @return string
	 */
	protected function getRecordTitleForInputType($value, $fieldConfig) {
		if (!isset($value)) {
			return '';
		}
		$title = $value;
		if (GeneralUtility::inList($fieldConfig['eval'], 'date')) {
			if (isset($fieldConfig['dbType']) && $fieldConfig['dbType'] === 'date') {
				$value = $value === '0000-00-00' ? 0 : (int)strtotime($value);
			} else {
				$value = (int)$value;
			}
			if (!empty($value)) {
				$ageSuffix = '';
				// Generate age suffix as long as not explicitly suppressed
				if (!isset($fieldConfig['disableAgeDisplay']) || (bool)$fieldConfig['disableAgeDisplay'] === FALSE) {
					$ageDelta = $GLOBALS['EXEC_TIME'] - $value;
					$calculatedAge = BackendUtility::calcAge(
						abs($ageDelta),
						$this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.minutesHoursDaysYears')
					);
					$ageSuffix = ' (' . ($ageDelta > 0 ? '-' : '') . $calculatedAge . ')';
				}
				$title = BackendUtility::date($value) . $ageSuffix;
			}
		} elseif (GeneralUtility::inList($fieldConfig['eval'], 'time')) {
			if (!empty($value)) {
				$title = BackendUtility::time((int)$value, FALSE);
			}
		} elseif (GeneralUtility::inList($fieldConfig['eval'], 'timesec')) {
			if (!empty($value)) {
				$title = BackendUtility::time((int)$value);
			}
		} elseif (GeneralUtility::inList($fieldConfig['eval'], 'datetime')) {
			// Handle native date/time field
			if (isset($fieldConfig['dbType']) && $fieldConfig['dbType'] === 'datetime') {
				$value = $value === '0000-00-00 00:00:00' ? 0 : (int)strtotime($value);
			} else {
				$value = (int)$value;
			}
			if (!empty($value)) {
				$title = BackendUtility::datetime($value);
			}
		}
		return $title;
	}

	/**
	 * Returns the record title for text fields
	 *
	 * @param mixed $value Current database value of this field
	 * @return string
	 */
	protected function getRecordTitleForTextType($value) {
		return trim(strip_tags($value));
	}

	/**
	 * @return DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

	/**
	 * @return LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

}
