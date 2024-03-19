<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Domain;

use TYPO3\CMS\Core\Domain\Record\ComputedProperties;
use TYPO3\CMS\Core\Domain\Record\LanguageInfo;
use TYPO3\CMS\Core\Domain\Record\SystemProperties;
use TYPO3\CMS\Core\Domain\Record\VersionInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Creates record objects out of TCA-based database rows,
 * by evaluating the TCA columns, and splits everything which is not a declared column
 * for a TCA type. This is usually the case when a TCA table has a 'typeField' defined,
 * such as "pages", "be_users" and "tt_content".
 *
 * @internal not part of TYPO3 Core API yet.
 */
class RecordFactory
{
    /**
     * Takes a full database record (the whole row), and creates a Record object out of it, based on the type
     * of the record.
     */
    public function createFromDatabaseRow(string $table, array $record): Record
    {
        $tcaConfig = $GLOBALS['TCA'][$table] ?? null;
        if ($tcaConfig === null) {
            throw new \InvalidArgumentException(
                'Unable to create Record from non-TCA table "' . $table . '".',
                1715266929
            );
        }
        $fullType = $table;
        $typeField = $tcaConfig['ctrl']['type'] ?? null;
        $allFieldNames = array_keys($tcaConfig['columns']);
        $properties = [];
        if ($typeField !== null) {
            if (!isset($record[$typeField])) {
                throw new \InvalidArgumentException(
                    'Missing typeField "' . $typeField . '" in record of requested table "' . $table . '".',
                    1715267513,
                );
            }
            $recordType = (string)$record[$typeField];
            $fullType .= '.' . $recordType;
        }
        $computedProperties = $this->extractComputedProperties($record);
        $rawRecord = new RawRecord((int)$record['uid'], (int)$record['pid'], $record, $computedProperties, $fullType);
        $relevantFieldNames = $this->findRelevantFieldsForSubSchema($tcaConfig, $rawRecord->getRecordType());
        // This removes columns, which are defined for the sub-type, but have no definition.
        $relevantFieldNames = array_intersect(array_keys($relevantFieldNames), $allFieldNames);
        foreach ($record as $fieldName => $fieldValue) {
            if (!in_array($fieldName, $relevantFieldNames, true)) {
                continue;
            }
            if ($fieldName === $typeField) {
                continue;
            }
            $properties[$fieldName] = $fieldValue;
        }
        [$properties, $systemProperties] = $this->extractSystemInformation(
            $tcaConfig['ctrl'],
            $rawRecord,
            $properties,
        );
        return new Record($rawRecord, $properties, $systemProperties);
    }

    /**
     * Gets the requested TCA sub-schema defined in TCA "types" section.
     * Unavailable sub-schemas fall back to the default types "0" and "1".
     * If even fallbacks are unavailable, this method errors out.
     */
    public function getSubSchemaConfig(array $tcaForTable, ?string $subSchemaName): array
    {
        if ($subSchemaName === null) {
            $subSchemaName = '0';
        }
        if (isset($tcaForTable['types'][$subSchemaName])) {
            return $tcaForTable['types'][$subSchemaName];
        }
        if (!isset($tcaForTable['types']['0']) && !isset($tcaForTable['types']['1'])) {
            throw new \UnexpectedValueException(
                'Neither 0 nor 1 are defined as fallback type for TCA table. Requested sub-schema was "' . $subSchemaName . '".',
                1715269835
            );
        }
        // Fallback types.
        return $tcaForTable['types']['0'] ?? $tcaForTable['types']['1'];
    }

    public function findRelevantFieldsForSubSchema(array $tcaForTable, ?string $subSchemaName): array
    {
        $fields = [];
        $subSchemaConfig = $this->getSubSchemaConfig($tcaForTable, $subSchemaName);
        $showItemArray = GeneralUtility::trimExplode(',', $subSchemaConfig['showitem']);
        foreach ($showItemArray as $showItemFieldString) {
            // The maximum amount of semicolons as delimiters is 3 for palettes.
            // Appending three semicolons ensures the array spread syntax always fills the variables.
            // 1. normal column name, keyword "--palette--" or keyword "--div--"
            // 2. Alternative label
            // 3. palette name
            [$fieldName, $fieldLabel, $paletteName] = GeneralUtility::trimExplode(';', $showItemFieldString . ';;;');
            if ($fieldName === '--div--') {
                // tabs are not of interest here
                continue;
            }
            if ($fieldName === '--palette--' && !empty($paletteName)) {
                // showitem references to a palette field. unpack the palette and process
                // label overrides that may be in there.
                if (!isset($tcaForTable['palettes'][$paletteName]['showitem'])) {
                    // No palette with this name found? Skip it.
                    continue;
                }
                $palettesArray = GeneralUtility::trimExplode(
                    ',',
                    $tcaForTable['palettes'][$paletteName]['showitem']
                );
                foreach ($palettesArray as $aPalettesString) {
                    // The showitem string in palettes only allows simple columns
                    // with an alternative label and the special keyword "--linebreak--".
                    [$fieldName, $fieldLabel] = GeneralUtility::trimExplode(';', $aPalettesString . ';;');
                    if ($fieldName === '--linebreak--') {
                        continue;
                    }
                    if (isset($tcaForTable['columns'][$fieldName])) {
                        $fields[$fieldName] = $this->getFinalFieldConfiguration($fieldName, $tcaForTable, $subSchemaConfig, $fieldLabel);
                    }
                }
            } elseif (isset($tcaForTable['columns'][$fieldName])) {
                $fields[$fieldName] = $this->getFinalFieldConfiguration($fieldName, $tcaForTable, $subSchemaConfig, $fieldLabel);
            }
        }
        return $fields;
    }

    /**
     * Handles the label and possible columnsOverrides
     */
    public function getFinalFieldConfiguration(string $fieldName, array $schemaConfiguration, array $subSchemaConfiguration, ?string $fieldLabel = null): array
    {
        $fieldConfiguration = $schemaConfiguration['columns'][$fieldName] ?? [];
        if (isset($subSchemaConfiguration['columnsOverrides'][$fieldName])) {
            $fieldConfiguration = array_replace_recursive($fieldConfiguration, $subSchemaConfiguration['columnsOverrides'][$fieldName]);
        }
        if (!empty($fieldLabel)) {
            $fieldConfiguration['label'] = $fieldLabel;
        }
        return $fieldConfiguration;
    }

    protected function extractComputedProperties(array &$record): ComputedProperties
    {
        $computedProperties = new ComputedProperties(
            $record['_ORIG_uid'] ?? null,
            $record['_LOCALIZED_UID'] ?? null,
            $record['_REQUESTED_OVERLAY_LANGUAGE'] ?? null,
            $record['_TRANSLATION_SOURCE'] ?? null
        );
        unset(
            $record['_ORIG_uid'],
            $record['_LOCALIZED_UID'],
            $record['_REQUESTED_OVERLAY_LANGUAGE'],
            $record['_TRANSLATION_SOURCE']
        );
        return $computedProperties;
    }

    protected function extractSystemInformation(array $ctrl, RawRecord $rawRecord, array $properties): array
    {
        // Language information.
        $systemProperties = [];
        $languageField = $ctrl['languageField'] ?? null;
        if ($languageField !== null) {
            $transOrigPointerField = $ctrl['transOrigPointerField'] ?? null;
            $translationSourceField = $ctrl['translationSource'] ?? null;
            $systemProperties['language'] = new LanguageInfo(
                (int)$rawRecord[$languageField],
                $transOrigPointerField ? (int)$rawRecord[$transOrigPointerField] : null,
                $translationSourceField ? (int)$rawRecord[$translationSourceField] : null,
            );
            unset($properties[$languageField]);
            if ($transOrigPointerField !== null) {
                unset($properties[$transOrigPointerField]);
            }
            if ($translationSourceField !== null) {
                unset($properties[$translationSourceField]);
            }
            if (isset($ctrl['transOrigDiffSourceField'])) {
                unset($properties[$ctrl['transOrigDiffSourceField']]);
            }
            unset($properties['l10n_state']);
        }

        // Workspaces.
        if ($ctrl['versioningWS'] ?? false) {
            $systemProperties['version'] = new VersionInfo(
                (int)$rawRecord['t3ver_wsid'],
                (int)$rawRecord['t3ver_oid'],
                VersionState::tryFrom((int)$rawRecord['t3ver_state']),
                (int)$rawRecord['t3ver_stage'],
            );
            unset(
                $properties['t3ver_wsid'],
                $properties['t3ver_oid'],
                $properties['t3ver_state'],
                $properties['t3ver_stage']
            );
        }

        // System fields.
        if (($ctrl['delete'] ?? false) && isset($rawRecord[$ctrl['delete']])) {
            $systemProperties['isDeleted'] = (bool)$rawRecord[$ctrl['delete']];
            unset($properties[$ctrl['delete']]);
        }
        if (($ctrl['crdate'] ?? false) && isset($rawRecord[$ctrl['crdate']])) {
            $systemProperties['createdAt'] = (new \DateTimeImmutable())->setTimestamp($rawRecord[$ctrl['crdate']]);
            unset($properties[$ctrl['crdate']]);
        }
        if (($ctrl['tstamp'] ?? false) && isset($rawRecord[$ctrl['tstamp']])) {
            $systemProperties['lastUpdatedAt'] = (new \DateTimeImmutable())->setTimestamp(
                $rawRecord[$ctrl['tstamp']]
            );
            unset($properties[$ctrl['tstamp']]);
        }
        if (($ctrl['descriptionColumn'] ?? false) && array_key_exists($ctrl['descriptionColumn'], $rawRecord->toArray())) {
            $systemProperties['description'] = $rawRecord[$ctrl['descriptionColumn']];
            unset($properties[$ctrl['descriptionColumn']]);
        }
        if (($ctrl['sortby'] ?? false) && isset($rawRecord[$ctrl['sortby']])) {
            $systemProperties['sorting'] = $rawRecord[$ctrl['sortby']];
            unset($properties[$ctrl['sortby']]);
        }
        if (($ctrl['editlock'] ?? false) && isset($rawRecord[$ctrl['editlock']])) {
            $systemProperties['isLockedForEditing'] = (bool)$rawRecord[$ctrl['editlock']];
            unset($properties[$ctrl['editlock']]);
        }
        foreach ($ctrl['enablecolumns'] ?? [] as $columnType => $fieldName) {
            if (!isset($rawRecord[$fieldName])) {
                continue;
            }
            switch ($columnType) {
                case 'disabled':
                    $systemProperties['isDisabled'] = (bool)$rawRecord[$fieldName];
                    break;
                case 'starttime':
                    $systemProperties['publishAt'] = (new \DateTimeImmutable())->setTimestamp($rawRecord[$fieldName]);
                    break;
                case 'endtime':
                    $systemProperties['publishUntil'] = (new \DateTimeImmutable())->setTimestamp($rawRecord[$fieldName]);
                    break;
                case 'fe_group':
                    $systemProperties['userGroupRestriction'] = GeneralUtility::intExplode(
                        ',',
                        $rawRecord[$fieldName],
                        true
                    );
                    break;
            }
            unset($properties[$fieldName]);
        }
        $systemProperties = new SystemProperties(
            $systemProperties['language'] ?? null,
            $systemProperties['version'] ?? null,
            $systemProperties['isDeleted'] ?? null,
            $systemProperties['isDisabled'] ?? null,
            $systemProperties['isLockedForEditing'] ?? null,
            $systemProperties['createdAt'] ?? null,
            $systemProperties['lastUpdatedAt'] ?? null,
            $systemProperties['publishAt'] ?? null,
            $systemProperties['publishUntil'] ?? null,
            $systemProperties['userGroupRestriction'] ?? null,
            $systemProperties['sorting'] ?? null,
            $systemProperties['description'] ?? null,
        );
        return [$properties, $systemProperties];
    }
}
