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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Mark columns that are common to many tables for further processing
 */
class TcaColumnsProcessCommon implements FormDataProviderInterface
{
    /**
     * Determine which common fields are in use and add those to the list of
     * columns that must be processed by the next data providers. Common fields
     * are for example uid, transOrigPointerField or transOrigDiffSourceField.
     *
     * @param array $result
     *
     * @return array
     */
    public function addData(array $result)
    {
        // enables the backend to display a visual comparison between a new version and its original
        $tableProperties = $result['processedTca']['ctrl'];
        if (!empty($tableProperties['origUid'])) {
            $result['columnsToProcess'][] = $tableProperties['origUid'];
        }

        // determines which one of the 'types' configurations are used for displaying the fields in the backend
        if (!empty($tableProperties['type'])) {
            // Allow for relation_field:foreign_type_field syntax
            $fieldName = GeneralUtility::trimExplode(':', $tableProperties['type'], true, 2);
            $result['columnsToProcess'][] = $fieldName[0];
        }

        // field that contains the language of the record
        if (!empty($tableProperties['languageField'])) {
            $result['columnsToProcess'][] = $tableProperties['languageField'];
        }

        // field that contains the pointer to the original record
        if (!empty($tableProperties['transOrigPointerField'])) {
            $result['columnsToProcess'][] = $tableProperties['transOrigPointerField'];
        }

        // field that contains the value of the original language record
        if (!empty($tableProperties['transOrigDiffSourceField'])) {
            $result['columnsToProcess'][] = $tableProperties['transOrigDiffSourceField'];
        }

        // fields added to subtypes_addlist (can be pi_flexform)
        $recordTypeValue = $result['recordTypeValue'];
        if (!empty($result['processedTca']['types'][$recordTypeValue]['subtype_value_field'])) {
            $subtypeFieldName = $result['processedTca']['types'][$recordTypeValue]['subtype_value_field'];
            if (!empty($result['processedTca']['types'][$recordTypeValue]['subtypes_addlist'][$result['databaseRow'][$subtypeFieldName]])) {
                $fields = GeneralUtility::trimExplode(
                    ',',
                    $result['processedTca']['types'][$recordTypeValue]['subtypes_addlist'][$result['databaseRow'][$subtypeFieldName]],
                    true
                );
                foreach ($fields as $field) {
                    $result['columnsToProcess'][] = $field;
                }
            }
        }

        return $result;
    }
}
