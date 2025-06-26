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

namespace TYPO3\CMS\Core\DataHandling;

use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

/**
 * Resolves the default values of a new record from "TCAdefaults" of PageTSconfig and UserTSconfig
 * and from the TCA "default" configuration.
 *
 * For each field, the value of the first source defining one is used:
 *
 *  - PageTSconfig TCAdefaults of the record type
 *  - PageTSconfig TCAdefaults
 *  - UserTSconfig TCAdefaults of the record type
 *  - UserTSconfig TCAdefaults
 *  - TCA default of the record type (columnsOverrides)
 *  - TCA default
 *
 * The record type is taken from the incoming field array, falling back to the TCAdefaults of the
 * type field in PageTSconfig and UserTSconfig, and finally to the TCA default of the type field.
 * A record type defined by a field of a related record is not resolved.
 *
 * @internal
 */
final readonly class DefaultValuesResolver
{
    public function __construct(
        private TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    /**
     * @param array $incomingFieldArray Field values given for the new record
     * @param array $pageTsConfigTcaDefaults The "TCAdefaults." PageTSconfig of the page the record is created on
     * @param array $userTsConfigTcaDefaults The "TCAdefaults." UserTSconfig of the current backend user
     * @return array<string, mixed> Default values by field name
     */
    public function resolve(string $table, array $incomingFieldArray, array $pageTsConfigTcaDefaults, array $userTsConfigTcaDefaults): array
    {
        $schema = $this->tcaSchemaFactory->get($table);
        $pageTsDefaults = $pageTsConfigTcaDefaults[$table . '.'] ?? [];
        $userTsDefaults = $userTsConfigTcaDefaults[$table . '.'] ?? [];
        $recordType = '';
        $subSchema = null;
        if ($schema->supportsSubSchema() && !$schema->getSubSchemaTypeInformation()->isPointerToForeignFieldInForeignSchema()) {
            $typeFieldName = $schema->getSubSchemaTypeInformation()->getFieldName();
            $recordTypeCandidates = [
                $incomingFieldArray[$typeFieldName] ?? null,
                $pageTsDefaults[$typeFieldName] ?? null,
                $userTsDefaults[$typeFieldName] ?? null,
                $schema->hasField($typeFieldName) ? $schema->getField($typeFieldName)->getDefaultValue() : null,
            ];
            foreach ($recordTypeCandidates as $recordTypeCandidate) {
                if ($recordTypeCandidate !== null && $schema->hasSubSchema((string)$recordTypeCandidate)) {
                    $recordType = (string)$recordTypeCandidate;
                    $subSchema = $schema->getSubSchema($recordType);
                    break;
                }
            }
        }

        $defaultValues = [];
        foreach ($schema->getFields() as $field) {
            $fieldName = $field->getName();
            $subSchemaField = $subSchema?->hasField($fieldName) ? $subSchema->getField($fieldName) : null;
            if (isset($pageTsDefaults[$fieldName . '.']['types.'][$recordType])) {
                $defaultValues[$fieldName] = $pageTsDefaults[$fieldName . '.']['types.'][$recordType];
            } elseif (isset($pageTsDefaults[$fieldName])) {
                $defaultValues[$fieldName] = $pageTsDefaults[$fieldName];
            } elseif (isset($userTsDefaults[$fieldName . '.']['types.'][$recordType])) {
                $defaultValues[$fieldName] = $userTsDefaults[$fieldName . '.']['types.'][$recordType];
            } elseif (isset($userTsDefaults[$fieldName])) {
                $defaultValues[$fieldName] = $userTsDefaults[$fieldName];
            } elseif ($subSchemaField?->hasDefaultValue() && ($subSchemaField->getDefaultValue() !== null || $subSchemaField->isNullable())) {
                $defaultValues[$fieldName] = $subSchemaField->getDefaultValue();
            } elseif ($field->hasDefaultValue() && ($field->getDefaultValue() !== null || $field->isNullable())) {
                $defaultValues[$fieldName] = $field->getDefaultValue();
            }
        }
        return $defaultValues;
    }
}
