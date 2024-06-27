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

namespace TYPO3\CMS\Core\Schema;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A class to collect actual relations. Contains all information...
 * -> what is the target of a relation of a field
 * -> what is pointing to a specific schema
 *
 * @internal not part of TYPO3 API as it should not be exposed, although this is really cool and powerful.
 */
final class RelationMap
{
    public function __construct(
        protected array $relations = []
    ) {}

    public function add(string $fromTable, string $fromFieldName, array $fieldConfig, ?string $flexPointer = null): void
    {
        $fieldType = $fieldConfig['type'] ?? null;
        if ($fieldType === 'group') {
            $toTables = GeneralUtility::trimExplode(',', $fieldConfig['allowed'] ?? $fieldConfig['foreign_table'] ?? '');
            foreach ($toTables as $toTable) {
                if (isset($fieldConfig['MM'])) {
                    $this->addMMRelation(
                        $fromTable,
                        $fromFieldName,
                        $toTable,
                        $fieldConfig['MM'],
                        $fieldConfig['MM_opposite_field'] ?? null,
                        $flexPointer
                    );
                } else {
                    $this->addActiveRelationToTable($fromTable, $fromFieldName, $toTable, $flexPointer);
                }
            }
        } elseif (in_array($fieldType, ['select', 'inline', 'file', 'category'], true)) {
            if (isset($fieldConfig['MM'])) {
                $this->addMMRelation(
                    $fromTable,
                    $fromFieldName,
                    $fieldConfig['foreign_table'],
                    $fieldConfig['MM'],
                    $fieldConfig['MM_opposite_field'] ?? null,
                    $flexPointer
                );
            } elseif (isset($fieldConfig['foreign_table'], $fieldConfig['foreign_field'])) {
                $this->addActiveRelationWithField(
                    $fromTable,
                    $fromFieldName,
                    $fieldConfig['foreign_table'],
                    $fieldConfig['foreign_field'],
                    $flexPointer
                );
            } elseif (isset($fieldConfig['foreign_table'])) {
                $this->addActiveRelationToTable(
                    $fromTable,
                    $fromFieldName,
                    $fieldConfig['foreign_table'],
                    $flexPointer
                );
            }
            // @todo: I guess we also need to do the foreign_table_field option
        }
    }

    protected function addMMRelation(string $fromTable, string $fromField, string $toTable, string $mm, ?string $mmOppositeField = null, ?string $flexPointer = null): void
    {
        $this->relations[$fromTable][$fromField][] = [
            'target' => $toTable,
            'mm' => $mm,
            'mmOppositeField' => $mmOppositeField,
            'flexPointer' => $flexPointer,
        ];
    }

    protected function addActiveRelationWithField(string $fromTable, string $fromField, string $toTable, string $toField, ?string $flexPointer = null): void
    {
        $this->relations[$fromTable][$fromField][] = [
            'target' => $toTable,
            'targetField' => $toField,
            'flexPointer' => $flexPointer,
        ];
    }

    protected function addActiveRelationToTable(string $fromTable, string $fromField, string $toTable, ?string $flexPointer = null): void
    {
        $this->relations[$fromTable][$fromField][] = [
            'target' => $toTable,
            'flexPointer' => $flexPointer,
        ];
    }

    /**
     * @return ActiveRelation[]
     */
    public function getActiveRelations(string $tableName, string $fieldName): array
    {
        return array_map([$this, 'makeActiveRelation'], $this->relations[$tableName][$fieldName] ?? []);
    }

    protected function makeActiveRelation(array $relation): ActiveRelation
    {
        return new ActiveRelation($relation['mm'] ?? $relation['target'], $relation['mmOppositeField'] ?? $relation['targetField'] ?? null);
    }

    /**
     * Passive relations can never be pointed to a field within a FlexSchema
     */
    public function getPassiveRelations(string $tableName, ?string $fieldName = null): array
    {
        $relations = [];
        foreach ($this->relations as $fromTable => $fields) {
            foreach ($fields as $fromField => $relation) {
                foreach ($relation as $rel) {
                    // target table does not match
                    if (!in_array($rel['target'], [$tableName, '*'], true)) {
                        continue;
                    }
                    // restriction to field is set, if this is set, this must match the targetField
                    // otherwise we include all relations to the target table (regardless if it is attached to a field or not)
                    // because we want to get the passive relations for the table.
                    if ($fieldName !== null) {
                        if (!isset($rel['targetField']) || $rel['targetField'] !== $fieldName) {
                            continue;
                        }
                    }
                    $relations[] = new PassiveRelation($fromTable, $fromField, $rel['flexPointer'] ?? null);
                }
            }
        }
        return $relations;
    }
}
