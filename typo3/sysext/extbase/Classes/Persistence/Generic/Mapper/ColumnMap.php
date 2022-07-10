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

use TYPO3\CMS\Core\DataHandling\TableColumnSubType;
use TYPO3\CMS\Core\DataHandling\TableColumnType;

/**
 * A column map to map a column configured in $TCA on a property of a domain object.
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class ColumnMap
{
    /**
     * @var string
     */
    public const RELATION_NONE = 'RELATION_NONE';

    /**
     * @var string
     */
    public const RELATION_HAS_ONE = 'RELATION_HAS_ONE';

    /**
     * @var string
     */
    public const RELATION_HAS_MANY = 'RELATION_HAS_MANY';

    /**
     * @var string
     */
    public const RELATION_BELONGS_TO_MANY = 'RELATION_BELONGS_TO_MANY';

    /**
     * @var string
     */
    public const RELATION_HAS_AND_BELONGS_TO_MANY = 'RELATION_HAS_AND_BELONGS_TO_MANY';

    /**
     * The property name corresponding to the table name
     *
     * @var string
     */
    private $propertyName;

    /**
     * The column name
     *
     * @var string
     */
    private $columnName;

    /**
     * todo: Check if this property should support null. If not, set default value.
     * The type of relation
     *
     * @var string|null
     */
    private $typeOfRelation;

    /**
     * todo: Check if this property should support null. If not, set default value.
     * The name of the child's table
     *
     * @see https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Select/Properties/ForeignTable.html
     * @var string|null
     */
    private $childTableName;

    /**
     * todo: Check if this property should support null. If not, set default value.
     * The name of the field the results from the child's table are sorted by
     *
     * @see https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Inline/Properties/ForeignSortby.html
     * @var string|null
     */
    private $childSortByFieldName;

    /**
     * todo: Check if this property should support null. If not, set default value.
     * The name of the relation table
     *
     * @see https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Inline/Properties/Mm.html
     * @var string|null
     */
    private $relationTableName;

    /**
     * todo: Check if this property should support null. If not, set default value.
     * The name of the column  of the relation table holding the page id
     *
     * @var string|null
     */
    private $relationTablePageIdColumnName;

    /**
     * todo: Check if this property should support null. If not, set default value.
     * An array of field => value pairs to both insert and match against when writing/reading MM relations
     *
     * @see https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Inline/Properties/ForeignMatchFields.html
     * @see https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Inline/Properties/Mm.html
     * @var array|null
     */
    private $relationTableMatchFields;

    /**
     * todo: Check if this property should support null. If not, set default value.
     * Array of field=>value pairs to insert when writing new MM relations
     *
     * @see https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Group/Properties/Mm.html#confval-MM_insert_fields(type=%3Egroup)
     * @var array|null
     */
    private $relationTableInsertFields;

    /**
     * todo: Check if this property should support null. If not, set default value.
     *
     * todo: Check if this property should be dropped as it's not in use. Basically we have to answer the question if
     * todo: MM_table_where should have any impact on Extbase at all.
     * The where clause to narrow down the selected relation table records
     *
     * @see https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Group/Properties/Mm.html#confval-MM_insert_fields(type=%3Egroup)
     * @var string|null
     */
    private $relationTableWhereStatement;

    /**
     * todo: Check if this property should support null. If not, set default value.
     * The name of the field holding the parents key
     *
     * @see https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Inline/Properties/ForeignField.html
     * @var string|null
     */
    private $parentKeyFieldName;

    /**
     * todo: Check if this property should support null. If not, set default value.
     * The name of the field holding the name of the table of the parent's records
     *
     * @see https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Inline/Properties/ForeignTableField.html
     * @var string|null
     */
    private $parentTableFieldName;

    /**
     * todo: Check if this property should support null. If not, set default value.
     * The name of the field holding the children key
     *
     * @var string|null
     */
    private $childKeyFieldName;

    /**
     * todo: Check if this property should support null. If not, set default value.
     * Alternative format for storing DataTime formats
     * (instead of using unix-time stamps). Allowed values
     * are 'date', 'datetime' and 'time'
     *
     * @see https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Datetime/Properties/DbType.html
     * @var string|null
     */
    private $dateTimeStorageFormat;

    /**
     * todo: Check if this property should support null. If not, set default value.
     * @var TableColumnType|null
     */
    private $type;

    /**
     * todo: Check if this property should support null. If not, set default value.
     * @var TableColumnSubType|null
     */
    private $internalType;

    /**
     * Constructs a Column Map
     *
     * @param string $columnName The column name
     * @param string $propertyName The property name
     */
    public function __construct(string $columnName, string $propertyName)
    {
        // @todo Enable aliases (tx_anotherextension_addedcolumn -> theAddedColumn)
        $this->columnName = $columnName;
        $this->propertyName = $propertyName;
    }

    /**
     * @param string $typeOfRelation
     */
    public function setTypeOfRelation(string $typeOfRelation): void
    {
        $this->typeOfRelation = $typeOfRelation;
    }

    /**
     * @return string
     */
    public function getTypeOfRelation(): string
    {
        return $this->typeOfRelation;
    }

    /**
     * @return string
     */
    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    /**
     * @return string
     */
    public function getColumnName(): string
    {
        return $this->columnName;
    }

    /**
     * @param string|null $childTableName
     */
    public function setChildTableName(?string $childTableName): void
    {
        $this->childTableName = $childTableName;
    }

    /**
     * @return string|null
     */
    public function getChildTableName(): ?string
    {
        return $this->childTableName;
    }

    /**
     * @param string|null $childSortByFieldName
     */
    public function setChildSortByFieldName(?string $childSortByFieldName): void
    {
        $this->childSortByFieldName = $childSortByFieldName;
    }

    /**
     * @return string|null
     */
    public function getChildSortByFieldName(): ?string
    {
        return $this->childSortByFieldName;
    }

    /**
     * @param string|null $relationTableName
     */
    public function setRelationTableName(?string $relationTableName): void
    {
        $this->relationTableName = $relationTableName;
    }

    /**
     * @return string|null
     */
    public function getRelationTableName(): ?string
    {
        return $this->relationTableName;
    }

    /**
     * @param string $relationTablePageIdColumnName
     */
    public function setRelationTablePageIdColumnName(string $relationTablePageIdColumnName): void
    {
        $this->relationTablePageIdColumnName = $relationTablePageIdColumnName;
    }

    /**
     * @return string|null
     */
    public function getRelationTablePageIdColumnName(): ?string
    {
        return $this->relationTablePageIdColumnName;
    }

    /**
     * @param array|null $relationTableMatchFields
     */
    public function setRelationTableMatchFields(?array $relationTableMatchFields): void
    {
        $this->relationTableMatchFields = $relationTableMatchFields;
    }

    /**
     * @return array|null
     */
    public function getRelationTableMatchFields(): ?array
    {
        return $this->relationTableMatchFields;
    }

    /**
     * @param array $relationTableInsertFields
     */
    public function setRelationTableInsertFields(array $relationTableInsertFields): void
    {
        $this->relationTableInsertFields = $relationTableInsertFields;
    }

    /**
     * @return array|null
     */
    public function getRelationTableInsertFields(): ?array
    {
        return $this->relationTableInsertFields;
    }

    /**
     * @param string|null $parentKeyFieldName
     */
    public function setParentKeyFieldName(?string $parentKeyFieldName): void
    {
        $this->parentKeyFieldName = $parentKeyFieldName;
    }

    /**
     * @return string|null
     */
    public function getParentKeyFieldName(): ?string
    {
        return $this->parentKeyFieldName;
    }

    /**
     * @param string|null $parentTableFieldName
     */
    public function setParentTableFieldName(?string $parentTableFieldName): void
    {
        $this->parentTableFieldName = $parentTableFieldName;
    }

    /**
     * @return string|null
     */
    public function getParentTableFieldName(): ?string
    {
        return $this->parentTableFieldName;
    }

    /**
     * @param string $childKeyFieldName
     */
    public function setChildKeyFieldName(string $childKeyFieldName): void
    {
        $this->childKeyFieldName = $childKeyFieldName;
    }

    /**
     * @return string|null
     */
    public function getChildKeyFieldName(): ?string
    {
        return $this->childKeyFieldName;
    }

    /**
     * @param string|null $dateTimeStorageFormat
     */
    public function setDateTimeStorageFormat(?string $dateTimeStorageFormat): void
    {
        $this->dateTimeStorageFormat = $dateTimeStorageFormat;
    }

    /**
     * @return string|null
     */
    public function getDateTimeStorageFormat(): ?string
    {
        return $this->dateTimeStorageFormat;
    }

    /**
     * @param TableColumnSubType $internalType
     */
    public function setInternalType(TableColumnSubType $internalType): void
    {
        $this->internalType = $internalType;
    }

    /**
     * @return TableColumnSubType|null
     */
    public function getInternalType(): ?TableColumnSubType
    {
        return $this->internalType;
    }

    /**
     * @param TableColumnType $type
     */
    public function setType(TableColumnType $type): void
    {
        $this->type = $type;
    }

    /**
     * @return TableColumnType|null
     */
    public function getType(): ?TableColumnType
    {
        return $this->type;
    }
}
