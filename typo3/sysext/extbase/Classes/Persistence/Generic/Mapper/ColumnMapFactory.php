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
use TYPO3\CMS\Core\Schema\Field\DateTimeFieldType;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\Field\FolderFieldType;
use TYPO3\CMS\Core\Schema\Field\RelationalFieldTypeInterface;
use TYPO3\CMS\Core\Schema\RelationshipType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap\Relation;
use TYPO3\CMS\Extbase\Reflection\ClassSchema\Exception\NoPropertyTypesException;
use TYPO3\CMS\Extbase\Reflection\ClassSchema\Exception\NoSuchPropertyException;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;

/**
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
readonly class ColumnMapFactory
{
    public function __construct(
        private ReflectionService $reflectionService
    ) {}

    public function create(FieldTypeInterface $field, string $propertyName, string $className): ColumnMap
    {
        $columnMap = GeneralUtility::makeInstance(ColumnMap::class, $field->getName());
        try {
            $property = $this->reflectionService->getClassSchema($className)->getProperty($propertyName);
            $nonProxyPropertyTypes = $property->getFilteredTypes([$property, 'filterLazyLoadingProxyAndLazyObjectStorage']);

            if ($nonProxyPropertyTypes === []) {
                throw NoPropertyTypesException::create($className, $propertyName);
            }

            $primaryType = $nonProxyPropertyTypes[0];
            $type = $primaryType->getClassName() ?? $primaryType->getBuiltinType();

            $collectionValueType = null;
            if ($primaryType->isCollection() && $primaryType->getCollectionValueTypes() !== []) {
                $primaryCollectionValueType = $primaryType->getCollectionValueTypes()[0];
                $collectionValueType = $primaryCollectionValueType->getClassName() ?? $primaryCollectionValueType->getBuiltinType();
            }

            [$type, $elementType] = [$type, $collectionValueType];
        } catch (NoSuchPropertyException|NoPropertyTypesException $e) {
            [$type, $elementType] = [null, null];
        }
        // @todo Why type "input" - shouldn't we better throw an exception here?
        $columnMap->setType(TableColumnType::tryFrom($field->getType()) ?? TableColumnType::INPUT);
        if ($field instanceof DateTimeFieldType) {
            $columnMap->setDateTimeStorageFormat($field->getPersistenceType());
        }
        $columnMap = $this->setRelations($columnMap, $field, $type, $elementType);
        $columnMap->setIsNullable($field->isNullable());
        return $columnMap;
    }

    /**
     * This method tries to determine the type of relation to other tables and sets it based on
     * the $TCA column configuration
     */
    protected function setRelations(ColumnMap $columnMap, FieldTypeInterface $field, ?string $type, ?string $elementType): ColumnMap
    {
        $columnConfiguration = $field->getConfiguration();

        if (($field instanceof RelationalFieldTypeInterface) && $field->getRelationshipType() === RelationshipType::ManyToMany) {
            return $this->setManyToManyRelation($columnMap, $field);
        }

        if ($elementType !== null) {
            // The field might not be a RelationFieldType, e.g. for TCA type "passthrough" or type "select"
            // without items. However, the model defines a relation and therefore overrules the TCA schema lookup.
            // IMPORTANT: This also overrules any "maxitems" or "renderType" configuration!
            return $this->setOneToManyRelation($columnMap, $field);
        }

        if ($type !== null && strpbrk($type, '_\\') !== false) {
            // @todo: check the strpbrk function call. Seems to be a check for Tx_Foo_Bar style class names
            return $this->setOneToOneRelation($columnMap, $field);
        }

        if ($field instanceof FolderFieldType) {
            // Folder is a special case which always has a relation to one or many "folders".
            // In case "maxitems" is set to > 1 and relationship is not explicitly set to "*toOne"
            // it's HAS_MANY, in all other cases it's HAS_ONE. It can never belong to many.
            // @todo  we should get rid of the "maxitems" and rely purely on the evaluated relationship type
            if (!in_array((string)($columnConfiguration['relationship'] ?? ''), ['oneToOne', 'manyToOne'], true)
                && (!isset($columnConfiguration['maxitems']) || $columnConfiguration['maxitems'] > 1)
            ) {
                $columnMap->setTypeOfRelation(Relation::HAS_MANY);
            } else {
                $columnMap->setTypeOfRelation(Relation::HAS_ONE);
            }
            return $columnMap;
        }

        // @todo we should get rid of the "maxitems" and "renderType" cases here and rely purely on
        //       the evaluated relationship type -> to be consistent with all non extbase components.
        if ($field instanceof RelationalFieldTypeInterface
            && $field->getRelationshipType()->hasMany()
            && (
                !$field->isType(TableColumnType::GROUP, TableColumnType::SELECT)
                || ($field->isType(TableColumnType::GROUP) && (!isset($columnConfiguration['maxitems']) || $columnConfiguration['maxitems'] > 1))
                || ($field->isType(TableColumnType::SELECT) && (($columnConfiguration['renderType'] ?? '') !== 'selectSingle' || (int)($columnConfiguration['maxitems'] ?? 0) > 1))
            )
        ) {
            $columnMap->setTypeOfRelation(Relation::HAS_MANY);
            return $columnMap;
        }

        return $columnMap;
    }

    /**
     * This method sets the configuration for a 1:1 relation based on the $TCA column configuration
     */
    protected function setOneToOneRelation(ColumnMap $columnMap, FieldTypeInterface $field): ColumnMap
    {
        $columnConfiguration = $field->getConfiguration();
        $columnMap->setTypeOfRelation(Relation::HAS_ONE);
        // @todo "allowed" (type=group) is missing here -> can only be evaluated if single value
        // check if foreign_table is set, which usually won't be the case for type "group" fields
        if (!empty($columnConfiguration['foreign_table'])) {
            $columnMap->setChildTableName($columnConfiguration['foreign_table']);
        }
        // todo: don't update column map if value(s) isn't/aren't set.
        $columnMap->setChildSortByFieldName($columnConfiguration['foreign_sortby'] ?? null);
        $columnMap->setParentKeyFieldName($columnConfiguration['foreign_field'] ?? null);
        $columnMap->setParentTableFieldName($columnConfiguration['foreign_table_field'] ?? null);
        if (isset($columnConfiguration['foreign_match_fields']) && is_array($columnConfiguration['foreign_match_fields'])) {
            $columnMap->setRelationTableMatchFields($columnConfiguration['foreign_match_fields']);
        }
        return $columnMap;
    }

    /**
     * This method sets the configuration for a 1:n relation based on
     * the $TCA column configuration
     *
     * @internal
     */
    public function setOneToManyRelation(ColumnMap $columnMap, FieldTypeInterface $field): ColumnMap
    {
        $columnConfiguration = $field->getConfiguration();
        $columnMap->setTypeOfRelation(Relation::HAS_MANY);
        // @todo "allowed" (type=group) is missing here -> can only be evaluated if single value
        // check if foreign_table is set, which usually won't be the case for type "group" fields
        if (!empty($columnConfiguration['foreign_table'])) {
            $columnMap->setChildTableName($columnConfiguration['foreign_table']);
        }
        // todo: don't update column map if value(s) isn't/aren't set.
        $columnMap->setChildSortByFieldName($columnConfiguration['foreign_sortby'] ?? null);
        $columnMap->setChildTableDefaultSortings($columnConfiguration['foreign_default_sortby'] ?? null);
        $columnMap->setParentKeyFieldName($columnConfiguration['foreign_field'] ?? null);
        $columnMap->setParentTableFieldName($columnConfiguration['foreign_table_field'] ?? null);
        if (isset($columnConfiguration['foreign_match_fields']) && is_array($columnConfiguration['foreign_match_fields'])) {
            $columnMap->setRelationTableMatchFields($columnConfiguration['foreign_match_fields']);
        }
        return $columnMap;
    }

    /**
     * This method sets the configuration for a m:n relation based on the $TCA column configuration
     */
    protected function setManyToManyRelation(ColumnMap $columnMap, FieldTypeInterface $field): ColumnMap
    {
        $columnConfiguration = $field->getConfiguration();
        $columnMap->setTypeOfRelation(Relation::HAS_AND_BELONGS_TO_MANY);
        // @todo "allowed" (type=group) is missing here -> can only be evaluated if single value
        // check if foreign_table is set, which usually won't be the case for type "group" fields
        if ($columnConfiguration['foreign_table'] ?? false) {
            $columnMap->setChildTableName($columnConfiguration['foreign_table']);
        }
        // todo: don't update column map if value(s) isn't/aren't set.
        $columnMap->setRelationTableName($columnConfiguration['MM']);
        if (isset($columnConfiguration['MM_match_fields']) && is_array($columnConfiguration['MM_match_fields'])) {
            $columnMap->setRelationTableMatchFields($columnConfiguration['MM_match_fields']);
        }
        // todo: don't update column map if value(s) isn't/aren't set.
        if (!empty($columnConfiguration['MM_opposite_field'])) {
            $columnMap->setParentKeyFieldName('uid_foreign');
            $columnMap->setChildKeyFieldName('uid_local');
            $columnMap->setChildSortByFieldName('sorting_foreign');
        } else {
            $columnMap->setParentKeyFieldName('uid_local');
            $columnMap->setChildKeyFieldName('uid_foreign');
            $columnMap->setChildSortByFieldName('sorting');
        }
        return $columnMap;
    }
}
