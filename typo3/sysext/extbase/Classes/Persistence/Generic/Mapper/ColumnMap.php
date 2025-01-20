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
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap\Relation;

/**
 * A column map to map a column configured in $TCA on a property of a domain object.
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
final readonly class ColumnMap
{
    /**
     * @param string $columnName Name of the DB column
     * @param TableColumnType $type TCA column type like "input", "inline"
     * @param string|null $dateTimeStorageFormat Alternative DataTime format instead of using unix timestamps. Allowed: "date", "datetime", "time"
     * @param Relation|null $typeOfRelation Extbase "Relation" enum if any
     * @param string|null $childTableName TCA "foreign_table" if any, @todo: Does not consider group "allowed" for multi table relations
     * @param string|null $relationTableName TCA "MM" if any
     * @param array $relationTableMatchFields TCA "MM_match_fields" if any in MM, TCA "foreign_match_fields" if any
     * @param string|null $parentKeyFieldName TCA "uid_local" or "uid_foreign" with TCA "MM" depending on "opposite",
     *                                        TCA "foreign_field" with TCA "foreign_table" relations
     * @param string|null $parentTableFieldName TCA "foreign_table_field" with TCA "foreign_table" relations
     * @param string|null $childKeyFieldName TCA "uid_local" or "uid_foreign" with TCA "MM" depending on "opposite"
     * @param string|null $childSortByFieldName Name of the field results from child's table are sorted by:
     *                                          TCA "sorting" or "sorting_foreign" with TCA "MM" depending on TCA "opposite" situation,
     *                                          TCA "foreign_sortby" with TCA "foreign_table"
     * @param string|null $childTableDefaultSortings name of the fields with direction results from child's table are sorted by default:
     *                                               TCA "foreign_default_sortby" with TCA "foreign_table"
     */
    public function __construct(
        public string $columnName,
        public TableColumnType $type,
        public ?string $dateTimeStorageFormat = null,
        public ?Relation $typeOfRelation = Relation::NONE,
        public ?string $childTableName = null,
        public ?string $relationTableName = null,
        public array $relationTableMatchFields = [],
        public ?string $parentKeyFieldName = null,
        public ?string $parentTableFieldName = null,
        public ?string $childKeyFieldName = null,
        public ?string $childSortByFieldName = null,
        public ?string $childTableDefaultSortings = null,
        public bool $isNullable = false,
    ) {}

    // Getters below could be removed but don't harm much and kept as b/w compat for now.

    public function getTypeOfRelation(): Relation
    {
        return $this->typeOfRelation;
    }

    public function getColumnName(): string
    {
        return $this->columnName;
    }

    public function getChildTableName(): ?string
    {
        return $this->childTableName;
    }

    public function getChildTableDefaultSortings(): ?string
    {
        return $this->childTableDefaultSortings;
    }

    public function getChildSortByFieldName(): ?string
    {
        return $this->childSortByFieldName;
    }

    public function getRelationTableName(): ?string
    {
        return $this->relationTableName;
    }

    public function getRelationTableMatchFields(): array
    {
        return $this->relationTableMatchFields;
    }

    public function getParentKeyFieldName(): ?string
    {
        return $this->parentKeyFieldName;
    }

    public function getParentTableFieldName(): ?string
    {
        return $this->parentTableFieldName;
    }

    public function getChildKeyFieldName(): ?string
    {
        return $this->childKeyFieldName;
    }

    public function getDateTimeStorageFormat(): ?string
    {
        return $this->dateTimeStorageFormat;
    }

    public function getType(): TableColumnType
    {
        return $this->type;
    }

    public function isNullable(): bool
    {
        return $this->isNullable;
    }
}
