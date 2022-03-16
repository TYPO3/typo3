<?php

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

namespace TYPO3\CMS\Backend\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Determine the title of a record and write it to $result['recordTitle'].
 *
 * TCA ctrl fields like label and label_alt are evaluated and their
 * current values from databaseRow used to create the title.
 */
class TcaRecordTitle implements FormDataProviderInterface
{
    /**
     * Enrich the processed record information with the resolved title
     *
     * @param array $result Incoming result array
     * @return array Modified array
     */
    public function addData(array $result)
    {
        if (!isset($result['processedTca']['ctrl']['label'])) {
            throw new \UnexpectedValueException(
                'TCA of table ' . $result['tableName'] . ' misses required [\'ctrl\'][\'label\'] definition.',
                1443706103
            );
        }

        if ($result['isInlineChild'] && isset($result['processedTca']['ctrl']['formattedLabel_userFunc'])) {
            // inline child with formatted user func is first
            $parameters = [
                'table' => $result['tableName'],
                'row' => $result['databaseRow'],
                'title' => '',
                'isOnSymmetricSide' => $result['isOnSymmetricSide'],
                'options' => $result['processedTca']['ctrl']['formattedLabel_userFunc_options'] ?? [],
                'parent' => [
                    'uid' => $result['databaseRow']['uid'],
                    'config' => $result['inlineParentConfig'],
                ],
            ];
            // callUserFunction requires a third parameter, but we don't want to give $this as reference!
            $null = null;
            GeneralUtility::callUserFunction($result['processedTca']['ctrl']['formattedLabel_userFunc'], $parameters, $null);
            $result['recordTitle'] = $parameters['title'];
        } elseif ($result['isInlineChild'] && (isset($result['inlineParentConfig']['foreign_label'])
                || isset($result['inlineParentConfig']['symmetric_label']))
        ) {
            // inline child with foreign label or symmetric inline child with symmetric_label
            $fieldName = $result['isOnSymmetricSide']
                ? $result['inlineParentConfig']['symmetric_label']
                : $result['inlineParentConfig']['foreign_label'];
            $result['recordTitle'] = $this->getRecordTitleForField($fieldName, $result);
        } elseif (isset($result['processedTca']['ctrl']['label_userFunc'])) {
            // userFunc takes precedence over everything else
            $parameters = [
                'table' => $result['tableName'],
                'row' => $result['databaseRow'],
                'title' => '',
                'options' => $result['processedTca']['ctrl']['label_userFunc_options'] ?? [],
            ];
            $null = null;
            GeneralUtility::callUserFunction($result['processedTca']['ctrl']['label_userFunc'], $parameters, $null);
            $result['recordTitle'] = $parameters['title'];
        } else {
            // standard record
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
    protected function getRecordTitleByLabelProperties(array $result)
    {
        $titles = [];
        $titleByLabel = $this->getRecordTitleForField($result['processedTca']['ctrl']['label'], $result);
        if (!empty($titleByLabel)) {
            $titles[] = $titleByLabel;
        }

        $labelAltForce = isset($result['processedTca']['ctrl']['label_alt_force'])
            ? (bool)$result['processedTca']['ctrl']['label_alt_force']
            : false;
        if (!empty($result['processedTca']['ctrl']['label_alt']) && ($labelAltForce || empty($titleByLabel))) {
            // Dive into label_alt evaluation if label_alt_force is set or if label did not came up with a title yet
            $labelAltFields = GeneralUtility::trimExplode(',', $result['processedTca']['ctrl']['label_alt'], true);
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
    protected function getRecordTitleForField($fieldName, $result)
    {
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
        $rawValue = null;
        if (array_key_exists($fieldName, $result['databaseRow'])) {
            $rawValue = $result['databaseRow'][$fieldName];
        }
        $fieldConfig = $result['processedTca']['columns'][$fieldName]['config'];
        switch ($fieldConfig['type']) {
            case 'radio':
                $recordTitle = $this->getRecordTitleForRadioType($rawValue, $fieldConfig);
                break;
            case 'inline':
                $recordTitle = $this->getRecordTitleForInlineType(
                    $rawValue,
                    $result['processedTca']['columns'][$fieldName]['children']
                );
                break;
            case 'select':
            case 'category':
                $recordTitle = $this->getRecordTitleForSelectType($rawValue, $fieldConfig);
                break;
            case 'group':
                $recordTitle = $this->getRecordTitleForGroupType($rawValue);
                break;
            case 'check':
                $recordTitle = $this->getRecordTitleForCheckboxType($rawValue, $fieldConfig);
                break;
            case 'input':
                $recordTitle = $this->getRecordTitleForInputType($rawValue, $fieldConfig);
                break;
            case 'text':
                $recordTitle = $this->getRecordTitleForTextType($rawValue);
                break;
            case 'flex':
                // @todo: Check if and how a label could be generated from flex field data
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
    protected function getRecordTitleForRadioType($value, $fieldConfig)
    {
        if (!isset($fieldConfig['items']) || !is_array($fieldConfig['items'])) {
            return '';
        }
        foreach ($fieldConfig['items'] as $item) {
            [$itemLabel, $itemValue] = $item;
            if ((string)$value === (string)$itemValue) {
                return $itemLabel;
            }
        }
        return '';
    }

    /**
     * @param int $value
     * @param array $children
     * @return string
     */
    protected function getRecordTitleForInlineType($value, array $children)
    {
        foreach ($children as $child) {
            if ((int)$value === $child['vanillaUid']) {
                return $child['recordTitle'];
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
    protected function getRecordTitleForSelectType($value, $fieldConfig)
    {
        if (!is_array($value)) {
            return '';
        }
        $labelParts = [];
        if (!empty($fieldConfig['items'])) {
            $listOfValues = array_column($fieldConfig['items'], 1);
            foreach ($value as $itemValue) {
                $itemKey = array_search($itemValue, $listOfValues);
                if ($itemKey !== false) {
                    $labelParts[] = $fieldConfig['items'][$itemKey][0];
                }
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
     * @return string
     */
    protected function getRecordTitleForGroupType($value)
    {
        $labelParts = [];
        foreach ($value as $singleValue) {
            if (isset($singleValue['folder'])) {
                $labelParts[] = $singleValue['folder'];
            } else {
                $labelParts[] = $singleValue['title'];
            }
        }
        return implode(', ', $labelParts);
    }

    /**
     * Returns the record title for checkbox fields
     *
     * @param mixed $value Current database value of this field
     * @param array $fieldConfig TCA field configuration
     * @return string
     */
    protected function getRecordTitleForCheckboxType($value, $fieldConfig)
    {
        $languageService = $this->getLanguageService();
        if (empty($fieldConfig['items']) || !is_array($fieldConfig['items'])) {
            $title = $value
                ? $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:yes')
                : $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:no');
        } else {
            $labelParts = [];
            foreach ($fieldConfig['items'] as $key => $val) {
                if ((int)$value & 2 ** $key) {
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
    protected function getRecordTitleForInputType($value, $fieldConfig)
    {
        if (!isset($value)) {
            return '';
        }
        if (!isset($fieldConfig['eval'])) {
            return $value;
        }
        $title = $value;
        $dateTimeFormats = QueryHelper::getDateTimeFormats();
        if (GeneralUtility::inList($fieldConfig['eval'], 'date')) {
            // Handle native date field
            if (isset($fieldConfig['dbType']) && $fieldConfig['dbType'] === 'date') {
                $value = $value === $dateTimeFormats['date']['empty'] ? 0 : (int)strtotime($value);
            } else {
                $value = (int)$value;
            }
            if (!empty($value)) {
                $ageSuffix = '';
                // Generate age suffix as long as not explicitly suppressed
                if (!isset($fieldConfig['disableAgeDisplay']) || (bool)$fieldConfig['disableAgeDisplay'] === false) {
                    $ageDelta = $GLOBALS['EXEC_TIME'] - $value;
                    $calculatedAge = BackendUtility::calcAge(
                        (int)abs($ageDelta),
                        $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.minutesHoursDaysYears')
                    );
                    $ageSuffix = ' (' . ($ageDelta > 0 ? '-' : '') . $calculatedAge . ')';
                }
                $title = BackendUtility::date($value) . $ageSuffix;
            }
        } elseif (GeneralUtility::inList($fieldConfig['eval'], 'time')) {
            // Handle native time field
            if (isset($fieldConfig['dbType']) && $fieldConfig['dbType'] === 'time') {
                $value = $value === $dateTimeFormats['time']['empty'] ? 0 : (int)strtotime('1970-01-01 ' . $value . ' UTC');
            } else {
                $value = (int)$value;
            }
            if (!empty($value)) {
                $title = gmdate('H:i', (int)$value);
            }
        } elseif (GeneralUtility::inList($fieldConfig['eval'], 'timesec')) {
            // Handle native time field
            if (isset($fieldConfig['dbType']) && $fieldConfig['dbType'] === 'time') {
                $value = $value === $dateTimeFormats['time']['empty'] ? 0 : (int)strtotime('1970-01-01 ' . $value . ' UTC');
            } else {
                $value = (int)$value;
            }
            if (!empty($value)) {
                $title = gmdate('H:i:s', (int)$value);
            }
        } elseif (GeneralUtility::inList($fieldConfig['eval'], 'datetime')) {
            // Handle native datetime field
            if (isset($fieldConfig['dbType']) && $fieldConfig['dbType'] === 'datetime') {
                $value = $value === $dateTimeFormats['datetime']['empty'] ? 0 : (int)strtotime($value);
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
    protected function getRecordTitleForTextType($value)
    {
        return trim(strip_tags($value));
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
