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

namespace TYPO3\CMS\Extbase\Persistence\Generic\Mapper;

use TYPO3\CMS\Core\DataHandling\TableColumnType;
use TYPO3\CMS\Core\Schema\Field\CountryFieldType;
use TYPO3\CMS\Core\Schema\Field\DateTimeFieldType;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\Field\FolderFieldType;
use TYPO3\CMS\Core\Schema\Field\RelationalFieldTypeInterface;
use TYPO3\CMS\Core\Schema\RelationshipType;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap\Relation;
use TYPO3\CMS\Extbase\Reflection\ClassSchema\Exception\NoSuchPropertyException;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;

/**
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
readonly class ColumnMapFactory
{
    public function __construct(
        private ReflectionService $reflectionService,
    ) {}

    public function create(FieldTypeInterface $field, string $propertyName, string $className): ColumnMap
    {
        $propertyType = null;
        $propertyCollectionValueType = null;
        try {
            $property = $this->reflectionService->getClassSchema($className)->getProperty($propertyName);
            $nonProxyPropertyTypes = $property->getFilteredTypes([$property, 'filterLazyLoadingProxyAndLazyObjectStorage']);
            $primaryType = $nonProxyPropertyTypes[0] ?? null;
            $propertyType = $primaryType?->getClassName() ?? $primaryType?->getBuiltinType() ?? null;
            if ($primaryType?->isCollection() && $primaryType->getCollectionValueTypes() !== []) {
                $primaryCollectionValueType = $primaryType->getCollectionValueTypes()[0];
                $propertyCollectionValueType = $primaryCollectionValueType->getClassName() ?? $primaryCollectionValueType->getBuiltinType();
            }
        } catch (NoSuchPropertyException) {
            // $type and $propertyCollectionValueType kept null
        }

        // @todo: The relation related handling below smells fishy at various places. Some TCA
        //        details are ignored, some are at least opinionated, some are wrong. The combination
        //        of fetching details from TCA *and* the model class makes everything quite complex.
        //        This should be consolidated.
        //        Also, the mixture of extbase internal "Relation", core TableColumnType, plus
        //        core TcaSchema details is complex and should be simplified to what we really need.
        //        Last, ColumnMap is not fully used throughout extbase, various details tend to
        //        still access TCA details directly.
        //        In the end, we may be better off removing extbase "Relation" altogether and
        //        add TcaSchema $columnConfiguration to ColumnMap to sort out TCA details at
        //        the few places where needed directly? This would be more in-line with DataHandler
        //        as well and raises fewer state questions in consumers, which reduces complexity.

        $columnConfiguration = $field->getConfiguration();
        $columnName = $field->getName();
        $tableColumnType = TableColumnType::tryFrom($field->getType());

        if ($field instanceof DateTimeFieldType) {
            // TCA type="datetime" considers "dbtype" and is done.
            return new ColumnMap(
                columnName: $columnName,
                type: $tableColumnType,
                dateTimeStorageFormat: $field->getPersistenceType(),
                isNullable: $field->isNullable(),
            );
        }

        if (($field instanceof RelationalFieldTypeInterface) && $field->getRelationshipType() === RelationshipType::ManyToMany) {
            if (!isset($columnConfiguration['MM'])) {
                throw new \LogicException(
                    'TCA schema of column ' . $columnName . ' is "ManytoMany", but TCA config has no MM property set',
                    1733560101
                );
            }
            return new ColumnMap(
                columnName: $columnName,
                type: $tableColumnType,
                typeOfRelation: Relation::HAS_AND_BELONGS_TO_MANY,
                // @todo: This does not model TCA type="group" "allowed" property which can specify multi table child relations
                childTableName: $columnConfiguration['foreign_table'] ?? null,
                relationTableName: $columnConfiguration['MM'],
                relationTableMatchFields: is_array($columnConfiguration['MM_match_fields'] ?? false) ? $columnConfiguration['MM_match_fields'] : [],
                parentKeyFieldName: !empty($columnConfiguration['MM_opposite_field']) ? 'uid_foreign' : 'uid_local',
                childKeyFieldName: !empty($columnConfiguration['MM_opposite_field']) ? 'uid_local' : 'uid_foreign',
                childSortByFieldName: !empty($columnConfiguration['MM_opposite_field']) ? 'sorting_foreign' : 'sorting',
                isNullable: $field->isNullable(),
            );
        }

        if ($propertyCollectionValueType !== null) {
            // The field might not be a RelationFieldType, e.g. for TCA type "passthrough" or type "select"
            // without items. However, the model defines a relation and therefore overrules the TCA schema lookup.
            // This also overrules any "maxitems" or "renderType" configuration!
            return new ColumnMap(
                columnName: $columnName,
                type: $tableColumnType,
                typeOfRelation: Relation::HAS_MANY,
                childTableName: $columnConfiguration['foreign_table'] ?? null,
                relationTableMatchFields: is_array($columnConfiguration['foreign_match_fields'] ?? false) ? $columnConfiguration['foreign_match_fields'] : [],
                parentKeyFieldName: $columnConfiguration['foreign_field'] ?? null,
                parentTableFieldName: $columnConfiguration['foreign_table_field'] ?? null,
                childSortByFieldName: $columnConfiguration['foreign_sortby'] ?? null,
                childTableDefaultSortings: $columnConfiguration['foreign_default_sortby'] ?? null,
                isNullable: $field->isNullable(),
            );
        }

        if ($propertyType !== null && strpbrk($propertyType, '_\\') !== false) {
            // @todo: Check this. Seems to be a check for Tx_Foo_Bar style class names?!
            return new ColumnMap(
                columnName: $columnName,
                type: $tableColumnType,
                typeOfRelation: Relation::HAS_ONE,
                childTableName: $columnConfiguration['foreign_table'] ?? null,
                relationTableMatchFields: is_array($columnConfiguration['foreign_match_fields'] ?? false) ? $columnConfiguration['foreign_match_fields'] : [],
                parentKeyFieldName: $columnConfiguration['foreign_field'] ?? null,
                parentTableFieldName: $columnConfiguration['foreign_table_field'] ?? null,
                childSortByFieldName: $columnConfiguration['foreign_sortby'] ?? null,
                isNullable: $field->isNullable(),
            );
        }

        if ($field instanceof FolderFieldType) {
            // Folder is a special case which always has a relation to one or many "folders".
            // In case "maxitems" is set to > 1 and relationship is not explicitly set to "*toOne"
            // it's HAS_MANY, in all other cases it's HAS_ONE. It can never belong to many.
            // @todo: Get rid of the "maxitems" and rely purely on the evaluated relationship type
            // @todo: TCA type="folder" has no TCA property "relationship"!
            $relation = Relation::HAS_ONE;
            if (!in_array((string)($columnConfiguration['relationship'] ?? ''), ['oneToOne', 'manyToOne'], true)
                && (!isset($columnConfiguration['maxitems']) || $columnConfiguration['maxitems'] > 1)
            ) {
                $relation = Relation::HAS_MANY;
            }
            return new ColumnMap(
                columnName: $columnName,
                type: $tableColumnType,
                typeOfRelation: $relation,
                isNullable: $field->isNullable(),
            );
        }

        if ($field instanceof CountryFieldType) {
            $relation = Relation::HAS_ONE;
            return new ColumnMap(
                columnName: $columnName,
                type: $tableColumnType,
                typeOfRelation: $relation,
            );
        }

        if ($field instanceof RelationalFieldTypeInterface
            && $field->getRelationshipType()->hasMany()
            && (
                !$field->isType(TableColumnType::GROUP, TableColumnType::SELECT)
                || ($field->isType(TableColumnType::GROUP) && (!isset($columnConfiguration['maxitems']) || $columnConfiguration['maxitems'] > 1))
                || ($field->isType(TableColumnType::SELECT) && (($columnConfiguration['renderType'] ?? '') !== 'selectSingle' || (int)($columnConfiguration['maxitems'] ?? 0) > 1))
            )
        ) {
            return new ColumnMap(
                columnName: $columnName,
                type: $tableColumnType,
                typeOfRelation: Relation::HAS_MANY,
                isNullable: $field->isNullable(),
            );
        }

        return new ColumnMap(
            columnName: $columnName,
            type: $tableColumnType,
            isNullable: $field->isNullable(),
        );
    }
}
