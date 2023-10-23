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

use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\DataHandling\TableColumnType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnsupportedRelationException;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap\Relation;
use TYPO3\CMS\Extbase\Reflection\ClassSchema\Exception\NoPropertyTypesException;
use TYPO3\CMS\Extbase\Reflection\ClassSchema\Exception\NoSuchPropertyException;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;

/**
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class ColumnMapFactory
{
    public function __construct(
        private readonly ReflectionService $reflectionService
    ) {}

    public function create(string $columnName, array $columnDefinition, string $propertyName, string $className): ColumnMap
    {
        $columnMap = GeneralUtility::makeInstance(ColumnMap::class, $columnName);
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
        $columnMap = $this->setType($columnMap, $columnDefinition['config']);
        $columnMap = $this->setRelations($columnMap, $columnDefinition['config'], $type, $elementType);
        return $this->setDateTimeStorageFormat($columnMap, $columnDefinition['config']);
    }

    /**
     * Set the table column type
     */
    protected function setType(ColumnMap $columnMap, array $columnConfiguration): ColumnMap
    {
        // todo: this method should only be called with proper arguments which means that the TCA integrity check should
        // todo: take place outside this method.

        $tableColumnType = $columnConfiguration['type'] ?? null;
        $columnMap->setType(TableColumnType::tryFrom($tableColumnType) ?? TableColumnType::INPUT);
        return $columnMap;
    }

    /**
     * This method tries to determine the type of type of relation to other tables and sets it based on
     * the $TCA column configuration
     *
     * @param ColumnMap $columnMap The column map
     * @param array|null $columnConfiguration The column configuration from $TCA
     */
    protected function setRelations(ColumnMap $columnMap, ?array $columnConfiguration, ?string $type, ?string $elementType): ColumnMap
    {
        if (!isset($columnConfiguration)) {
            return $columnMap;
        }

        if (isset($columnConfiguration['MM'])) {
            return $this->setManyToManyRelation($columnMap, $columnConfiguration);
        }

        if ($elementType !== null) {
            return $this->setOneToManyRelation($columnMap, $columnConfiguration);
        }

        if ($type !== null && strpbrk($type, '_\\') !== false) {
            // @todo: check the strpbrk function call. Seems to be a check for Tx_Foo_Bar style class names
            return $this->setOneToOneRelation($columnMap, $columnConfiguration);
        }

        if (
            isset($columnConfiguration['type'], $columnConfiguration['renderType'])
            && $columnConfiguration['type'] === 'select'
            && (
                $columnConfiguration['renderType'] !== 'selectSingle'
                || (isset($columnConfiguration['maxitems']) && $columnConfiguration['maxitems'] > 1)
            )
        ) {
            $columnMap->setTypeOfRelation(Relation::HAS_MANY);
            return $columnMap;
        }

        if (
            isset($columnConfiguration['type']) && ($columnConfiguration['type'] === 'group' || $columnConfiguration['type'] === 'folder')
            && (!isset($columnConfiguration['maxitems']) || $columnConfiguration['maxitems'] > 1)
        ) {
            $columnMap->setTypeOfRelation(Relation::HAS_MANY);
            return $columnMap;
        }

        return $columnMap;
    }

    /**
     * Sets datetime storage format based on $TCA column configuration.
     *
     * @param ColumnMap $columnMap The column map
     * @param array|null $columnConfiguration The column configuration from $TCA
     */
    protected function setDateTimeStorageFormat(ColumnMap $columnMap, array $columnConfiguration = null): ColumnMap
    {
        // todo: this method should only be called with proper arguments which means that the TCA integrity check should
        // todo: take place outside this method.

        if ($columnMap->getType() === TableColumnType::DATETIME
            && in_array($columnConfiguration['dbType'] ?? '', QueryHelper::getDateTimeTypes(), true)
        ) {
            $columnMap->setDateTimeStorageFormat($columnConfiguration['dbType']);
        }

        return $columnMap;
    }

    /**
     * This method sets the configuration for a 1:1 relation based on
     * the $TCA column configuration
     *
     * @param ColumnMap $columnMap The column map
     * @param array|null $columnConfiguration The column configuration from $TCA
     */
    protected function setOneToOneRelation(ColumnMap $columnMap, array $columnConfiguration = null): ColumnMap
    {
        // todo: this method should only be called with proper arguments which means that the TCA integrity check should
        // todo: take place outside this method.

        $columnMap->setTypeOfRelation(Relation::HAS_ONE);
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
     * @param ColumnMap $columnMap The column map
     * @param array|null $columnConfiguration The column configuration from $TCA
     *
     * @internal
     */
    public function setOneToManyRelation(ColumnMap $columnMap, array $columnConfiguration = null): ColumnMap
    {
        // todo: this method should only be called with proper arguments which means that the TCA integrity check should
        // todo: take place outside this method.

        $columnMap->setTypeOfRelation(Relation::HAS_MANY);
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
     * This method sets the configuration for a m:n relation based on
     * the $TCA column configuration
     *
     * @param ColumnMap $columnMap The column map
     * @param array|null $columnConfiguration The column configuration from $TCA
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnsupportedRelationException
     */
    protected function setManyToManyRelation(ColumnMap $columnMap, array $columnConfiguration = null): ColumnMap
    {
        // todo: this method should only be called with proper arguments which means that the TCA integrity check should
        // todo: take place outside this method.

        if (isset($columnConfiguration['MM'])) {
            $columnMap->setTypeOfRelation(Relation::HAS_AND_BELONGS_TO_MANY);
            // check if foreign_table is set, which usually won't be the case for type "group" fields
            if (!empty($columnConfiguration['foreign_table'])) {
                $columnMap->setChildTableName($columnConfiguration['foreign_table']);
            }
            // todo: don't update column map if value(s) isn't/aren't set.
            $columnMap->setRelationTableName($columnConfiguration['MM']);
            if (isset($columnConfiguration['MM_match_fields']) && is_array($columnConfiguration['MM_match_fields'])) {
                $columnMap->setRelationTableMatchFields($columnConfiguration['MM_match_fields']);
            }
            // @deprecated since v12. Remove in v13 with other MM_insert_fields places.
            if (isset($columnConfiguration['MM_insert_fields']) && is_array($columnConfiguration['MM_insert_fields'])) {
                $columnMap->setRelationTableInsertFields($columnConfiguration['MM_insert_fields']);
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
        } else {
            // todo: this else part is actually superfluous because \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory::setRelations
            // todo: only calls this method if $columnConfiguration['MM'] is set.

            throw new UnsupportedRelationException('The given information to build a many-to-many-relation was not sufficient. Check your TCA definitions. mm-relations with IRRE must have at least a defined "MM" or "foreign_selector".', 1268817963);
        }
        return $columnMap;
    }
}
