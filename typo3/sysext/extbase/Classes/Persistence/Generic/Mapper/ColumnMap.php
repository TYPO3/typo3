<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Extbase\Persistence\Generic\Mapper;

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

/**
 * A column map to map a column configured in $TCA on a property of a domain object.
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class ColumnMap
{
    /**
     * Constants reflecting the type of relation
     */
    const RELATION_NONE = 'RELATION_NONE';
    const RELATION_HAS_ONE = 'RELATION_HAS_ONE';
    const RELATION_HAS_MANY = 'RELATION_HAS_MANY';
    const RELATION_BELONGS_TO_MANY = 'RELATION_BELONGS_TO_MANY';
    const RELATION_HAS_AND_BELONGS_TO_MANY = 'RELATION_HAS_AND_BELONGS_TO_MANY';

    /**
     * Constants reflecting how the relation information is stored
     */
    const RELATION_PARENT_FOREIGN_KEY = 'RELATION_PARENT_FOREIGN_KEY';
    const RELATION_CHILD_FOREIGN_KEY = 'RELATION_CHILD_FOREIGN_KEY';
    const RELATION_PARENT_CSV = 'RELATION_PARENT_CSV';
    const RELATION_INTERMEDIATE_TABLE = 'RELATION_INTERMEDIATE_TABLE';

    /**
     * Constants reflecting the loading strategy
     */
    const STRATEGY_EAGER = 'eager';
    const STRATEGY_LAZY_PROXY = 'proxy';
    const STRATEGY_LAZY_STORAGE = 'storage';

    /**
     * The property name corresponding to the table name
     *
     * @var string
     */
    protected $propertyName;

    /**
     * The column name
     *
     * @var string
     */
    protected $columnName;

    /**
     * todo: Check if this property should support null. If not, set default value.
     * The type of relation
     *
     * @var string|null
     */
    protected $typeOfRelation;

    /**
     * todo: property is not in use, can be dropped
     * The name of the child's class
     *
     * @var string
     */
    protected $childClassName;

    /**
     * todo: Check if this property should support null. If not, set default value.
     * The name of the child's table
     *
     * @var string|null
     */
    protected $childTableName;

    /**
     * todo: Check if this property should support null. If not, set default value.
     * The where clause to narrow down the selected child records
     *
     * @var string|null
     */
    protected $childTableWhereStatement;

    /**
     * todo: Check if this property should support null. If not, set default value.
     * The name of the field the results from the child's table are sorted by
     *
     * @var string|null
     */
    protected $childSortByFieldName;

    /**
     * todo: Check if this property should support null. If not, set default value.
     * The name of the relation table
     *
     * @var string|null
     */
    protected $relationTableName;

    /**
     * todo: Check if this property should support null. If not, set default value.
     * The name of the column  of the relation table holding the page id
     *
     * @var string|null
     */
    protected $relationTablePageIdColumnName;

    /**
     * todo: Check if this property should support null. If not, set default value.
     * An array of field => value pairs to both insert and match against when writing/reading MM relations
     *
     * @var array|null
     */
    protected $relationTableMatchFields;

    /**
     * todo: Check if this property should support null. If not, set default value.
     * Array of field=>value pairs to insert when writing new MM relations
     *
     * @var array|null
     */
    protected $relationTableInsertFields;

    /**
     * todo: Check if this property should support null. If not, set default value.
     * The where clause to narrow down the selected relation table records
     *
     * @var string|null
     */
    protected $relationTableWhereStatement;

    /**
     * todo: Check if this property should support null. If not, set default value.
     * The name of the field holding the parents key
     *
     * @var string|null
     */
    protected $parentKeyFieldName;

    /**
     * todo: Check if this property should support null. If not, set default value.
     * The name of the field holding the name of the table of the parent's records
     *
     * @var string|null
     */
    protected $parentTableFieldName;

    /**
     * todo: Check if this property should support null. If not, set default value.
     * The name of the field holding the children key
     *
     * @var string|null
     */
    protected $childKeyFieldName;

    /**
     * todo: Check if this property should support null. If not, set default value.
     * Alternative format for storing DataTime formats
     * (instead of using unix-time stamps). Allowed values
     * are 'date', 'datetime' and 'time'
     *
     * @var string|null
     */
    protected $dateTimeStorageFormat;

    /**
     * todo: Check if this property should support null. If not, set default value.
     * @var \TYPO3\CMS\Core\DataHandling\TableColumnType|null
     */
    protected $type;

    /**
     * todo: Check if this property should support null. If not, set default value.
     * @var \TYPO3\CMS\Core\DataHandling\TableColumnSubType|null
     */
    protected $internalType;

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
     * todo: The property name of column maps should be immutable, therefore this setter must be dropped
     * @param string $propertyName
     */
    public function setPropertyName(string $propertyName): void
    {
        $this->propertyName = $propertyName;
    }

    /**
     * @return string
     */
    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    /**
     * todo: The column name of column maps should be immutable, therefore this setter must be dropped
     * @param string $columnName
     */
    public function setColumnName(string $columnName): void
    {
        $this->columnName = $columnName;
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
     * @param string|null $childTableWhereStatement
     */
    public function setChildTableWhereStatement(?string $childTableWhereStatement): void
    {
        $this->childTableWhereStatement = $childTableWhereStatement;
    }

    /**
     * @return string|null
     */
    public function getChildTableWhereStatement(): ?string
    {
        return $this->childTableWhereStatement;
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
     * @param string|null $relationTableWhereStatement
     */
    public function setRelationTableWhereStatement(?string $relationTableWhereStatement): void
    {
        $this->relationTableWhereStatement = $relationTableWhereStatement;
    }

    /**
     * @return string|null
     */
    public function getRelationTableWhereStatement(): ?string
    {
        return $this->relationTableWhereStatement;
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
     * @param \TYPO3\CMS\Core\DataHandling\TableColumnSubType $internalType
     */
    public function setInternalType(\TYPO3\CMS\Core\DataHandling\TableColumnSubType $internalType): void
    {
        $this->internalType = $internalType;
    }

    /**
     * @return \TYPO3\CMS\Core\DataHandling\TableColumnSubType|null
     */
    public function getInternalType(): ?\TYPO3\CMS\Core\DataHandling\TableColumnSubType
    {
        return $this->internalType;
    }

    /**
     * @param \TYPO3\CMS\Core\DataHandling\TableColumnType $type
     */
    public function setType(\TYPO3\CMS\Core\DataHandling\TableColumnType $type): void
    {
        $this->type = $type;
    }

    /**
     * @return \TYPO3\CMS\Core\DataHandling\TableColumnType|null
     */
    public function getType(): ?\TYPO3\CMS\Core\DataHandling\TableColumnType
    {
        return $this->type;
    }
}
