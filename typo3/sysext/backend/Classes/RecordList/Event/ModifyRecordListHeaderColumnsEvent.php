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

namespace TYPO3\CMS\Backend\RecordList\Event;

use TYPO3\CMS\Backend\RecordList\DatabaseRecordList;

/**
 * An event to modify the header columns for a table in the RecordList
 */
final class ModifyRecordListHeaderColumnsEvent
{
    /**
     * Additional header attributes for the table header row
     *
     * @var string[]
     */
    private array $headerAttributes = [];

    /**
     * @param array<int> $recordIds
     */
    public function __construct(
        private array $columns,
        private readonly string $table,
        private readonly array $recordIds,
        private readonly DatabaseRecordList $recordList
    ) {}

    /**
     * Add a new column or override an existing one. Latter is only possible,
     * in case $columnName is given. Otherwise, the column will be added with
     * a numeric index, which is generally not recommended.
     *
     * Note: Due to the behaviour of DatabaseRecordList, just adding a column
     * does not mean that it is also displayed. The internal $fieldArray needs
     * to be adjusted as well. This method only adds the column to the data array.
     * Therefore, this method should mainly be used to edit existing columns, e.g.
     * change their label.
     */
    public function setColumn(string $column, string $columnName = ''): void
    {
        if ($columnName !== '') {
            $this->columns[$columnName] = $column;
        } else {
            $this->columns[] = $column;
        }
    }

    /**
     * Whether the column exists
     */
    public function hasColumn(string $columnName): bool
    {
        return (bool)($this->columns[$columnName] ?? false);
    }

    /**
     * Get column by its name
     *
     * @return string|null The column or NULL if the column does not exist
     */
    public function getColumn(string $columnName): ?string
    {
        return $this->columns[$columnName] ?? null;
    }

    /**
     * Remove column by its name
     *
     * @return bool Whether the column could be removed - Will therefore
     *              return FALSE if the column to remove does not exist.
     */
    public function removeColumn(string $columnName): bool
    {
        if (!isset($this->columns[$columnName])) {
            return false;
        }
        unset($this->columns[$columnName]);
        return true;
    }

    public function setColumns(array $columns): void
    {
        $this->columns = $columns;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function setHeaderAttributes(array $headerAttributes): void
    {
        $this->headerAttributes = $headerAttributes;
    }

    public function getHeaderAttributes(): array
    {
        return $this->headerAttributes;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getRecordIds(): array
    {
        return $this->recordIds;
    }

    /**
     * Returns the current DatabaseRecordList instance.
     *
     * @todo Might be replaced by a DTO in the future
     */
    public function getRecordList(): DatabaseRecordList
    {
        return $this->recordList;
    }
}
