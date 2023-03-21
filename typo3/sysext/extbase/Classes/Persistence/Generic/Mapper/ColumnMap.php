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
     * The name of the fields with direction the results from the child's table are sorted by default
     *
     * @see https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Inline/Properties/ForeignDefaultSortby.html
     */
    private ?string $childTableDefaultSortings = null;

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

    private TableColumnType $type = TableColumnType::INPUT;

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

    public function setTypeOfRelation(string $typeOfRelation): void
    {
        $this->typeOfRelation = $typeOfRelation;
    }

    public function getTypeOfRelation(): string
    {
        return $this->typeOfRelation;
    }

    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    public function getColumnName(): string
    {
        return $this->columnName;
    }

    public function setChildTableName(?string $childTableName): void
    {
        $this->childTableName = $childTableName;
    }

    public function getChildTableName(): ?string
    {
        return $this->childTableName;
    }

    public function setChildTableDefaultSortings(?string $childTableDefaultSortings): void
    {
        $this->childTableDefaultSortings = $childTableDefaultSortings;
    }

    public function getChildTableDefaultSortings(): ?string
    {
        return $this->childTableDefaultSortings;
    }

    public function setChildSortByFieldName(?string $childSortByFieldName): void
    {
        $this->childSortByFieldName = $childSortByFieldName;
    }

    public function getChildSortByFieldName(): ?string
    {
        return $this->childSortByFieldName;
    }

    public function setRelationTableName(?string $relationTableName): void
    {
        $this->relationTableName = $relationTableName;
    }

    public function getRelationTableName(): ?string
    {
        return $this->relationTableName;
    }

    public function setRelationTablePageIdColumnName(string $relationTablePageIdColumnName): void
    {
        $this->relationTablePageIdColumnName = $relationTablePageIdColumnName;
    }

    public function getRelationTablePageIdColumnName(): ?string
    {
        return $this->relationTablePageIdColumnName;
    }

    public function setRelationTableMatchFields(?array $relationTableMatchFields): void
    {
        $this->relationTableMatchFields = $relationTableMatchFields;
    }

    public function getRelationTableMatchFields(): ?array
    {
        return $this->relationTableMatchFields;
    }

    public function setRelationTableInsertFields(array $relationTableInsertFields): void
    {
        $this->relationTableInsertFields = $relationTableInsertFields;
    }

    public function getRelationTableInsertFields(): ?array
    {
        return $this->relationTableInsertFields;
    }

    public function setParentKeyFieldName(?string $parentKeyFieldName): void
    {
        $this->parentKeyFieldName = $parentKeyFieldName;
    }

    public function getParentKeyFieldName(): ?string
    {
        return $this->parentKeyFieldName;
    }

    public function setParentTableFieldName(?string $parentTableFieldName): void
    {
        $this->parentTableFieldName = $parentTableFieldName;
    }

    public function getParentTableFieldName(): ?string
    {
        return $this->parentTableFieldName;
    }

    public function setChildKeyFieldName(string $childKeyFieldName): void
    {
        $this->childKeyFieldName = $childKeyFieldName;
    }

    public function getChildKeyFieldName(): ?string
    {
        return $this->childKeyFieldName;
    }

    public function setDateTimeStorageFormat(?string $dateTimeStorageFormat): void
    {
        $this->dateTimeStorageFormat = $dateTimeStorageFormat;
    }

    public function getDateTimeStorageFormat(): ?string
    {
        return $this->dateTimeStorageFormat;
    }

    public function setType(TableColumnType $type): void
    {
        $this->type = $type;
    }

    public function getType(): TableColumnType
    {
        return $this->type;
    }
}
