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

namespace TYPO3\CMS\Core\Database\Schema;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff as DoctrineTableDiff;

/**
 * Based on the doctrine/dbal implementation restoring direct property access
 *  and adding further helper methods.
 *
 * @internal not part of public core API.
 */
class TableDiff extends DoctrineTableDiff
{
    /**
     * $newTableName is a TYPO3 internal addition to handle renames at a later point.
     */
    public ?string $newName = null;

    /**
     * Constructs a TableDiff object.
     *
     * @param array<string, Column>       $addedColumns
     * @param array<string, ColumnDiff>   $changedColumns
     * @param array<string, Column>       $droppedColumns
     * @param array<string, Index>        $addedIndexes
     * @param array<string, Index>        $modifiedIndexes
     * @param array<string, Index>        $droppedIndexes
     * @param array<string, Index>        $renamedIndexes
     * @param array<ForeignKeyConstraint> $addedForeignKeys
     * @param array<ForeignKeyConstraint> $modifiedForeignKeys
     * @param array<ForeignKeyConstraint> $droppedForeignKeys
     * @param array<int|string, mixed>    $tableOptions
     *
     * @internal The diff can be only instantiated by a {@see Comparator}.
     *
     * @todo Consider to change from array to typed collections with array access support.
     */
    public function __construct(
        public Table $oldTable,
        public array $addedColumns = [],
        public array $changedColumns = [],
        public array $droppedColumns = [],
        public array $addedIndexes = [],
        public array $modifiedIndexes = [],
        public array $droppedIndexes = [],
        public array $renamedIndexes = [],
        public array $addedForeignKeys = [],
        public array $modifiedForeignKeys = [],
        public array $droppedForeignKeys = [],
        public array $tableOptions = [],
    ) {
        // NOTE: parent::__construct() not called by intention.
    }

    /**
     * Getter for table options.
     *
     * @return array<int|string, mixed>
     */
    public function getTableOptions(): array
    {
        return $this->tableOptions;
    }

    /**
     * Setter for table options
     *
     * @param array<int|string, mixed> $tableOptions
     */
    public function setTableOptions(array $tableOptions): self
    {
        $this->tableOptions = $tableOptions;
        return $this;
    }

    /**
     * Check if a table options has been set.
     */
    public function hasTableOption(string $optionName): bool
    {
        return array_key_exists($optionName, $this->tableOptions);
    }

    public function getTableOption(string $optionName): string
    {
        if ($this->hasTableOption($optionName)) {
            return (string)$this->tableOptions[$optionName];
        }

        return '';
    }

    public function getOldTable(): Table
    {
        return $this->oldTable;
    }

    /** @return array<string, Column> */
    public function getAddedColumns(): array
    {
        return $this->addedColumns;
    }

    /** @return array<string, ColumnDiff> */
    public function getChangedColumns(): array
    {
        return $this->changedColumns;
    }

    /** @return array<string, Column> */
    public function getDroppedColumns(): array
    {
        return $this->droppedColumns;
    }

    /** @return array<string, Index> */
    public function getAddedIndexes(): array
    {
        return $this->addedIndexes;
    }

    /** @return array<string, Index> */
    public function getModifiedIndexes(): array
    {
        return $this->modifiedIndexes;
    }

    /** @return array<string, Index> */
    public function getDroppedIndexes(): array
    {
        return $this->droppedIndexes;
    }

    /** @return array<string,Index> */
    public function getRenamedIndexes(): array
    {
        return $this->renamedIndexes;
    }

    /** @return array<ForeignKeyConstraint> */
    public function getAddedForeignKeys(): array
    {
        return $this->addedForeignKeys;
    }

    /** @return array<ForeignKeyConstraint> */
    public function getModifiedForeignKeys(): array
    {
        return $this->modifiedForeignKeys;
    }

    /** @return array<ForeignKeyConstraint> */
    public function getDroppedForeignKeys(): array
    {
        return $this->droppedForeignKeys;
    }

    public function isEmpty(): bool
    {
        return count($this->getAddedColumns()) === 0
            && count($this->getChangedColumns()) === 0
            && count($this->getDroppedColumns()) === 0
            && count($this->getAddedIndexes()) === 0
            && count($this->getModifiedIndexes()) === 0
            && count($this->getDroppedIndexes()) === 0
            && count($this->getRenamedIndexes()) === 0
            && count($this->getAddedForeignKeys()) === 0
            && count($this->getModifiedForeignKeys()) === 0
            && count($this->getDroppedForeignKeys()) === 0
            // doctrine/dbal 4.x removed the newName. TYPO3 needs that to provide a rename to prefix logic before
            // really dropping tables instead. Therefore, we need to add here an empty check for the reintroduced
            // property.See for example: ConnectionMigrator->migrateUnprefixedRemovedTablesToRenames
            && $this->getNewName() !== null && $this->getNewName() !== ''
            && $this->getTableOptions() === [];
    }

    public function getNewName(): ?string
    {
        return $this->newName;
    }

    public static function ensure(DoctrineTableDiff|TableDiff $tableDiff): self
    {
        $diff = new self(
            // oldTable
            $tableDiff->getOldTable(),
            // addedColumns
            $tableDiff->getAddedColumns(),
            // changedColumns
            [],
            // droppedColumns
            $tableDiff->getDroppedColumns(),
            // addedIndexes
            $tableDiff->getAddedIndexes(),
            // modifiedIndexes
            [],
            // droppedIndexes
            $tableDiff->getDroppedIndexes(),
            // renamedIndexes
            $tableDiff->getRenamedIndexes(),
            // addedForeignKeys
            $tableDiff->getAddedForeignKeys(),
            // modifiedForeignKeys
            $tableDiff->getModifiedForeignKeys(),
            // droppedForeignKeys
            $tableDiff->getDroppedForeignKeys(),
            // tableOptions
            ($tableDiff instanceof TableDiff ? $tableDiff->tableOptions : []),
        );

        // doctrine/dbal 4+ removed the column name as array index for modified column definitions,
        // but we rely on it. Restore it !
        // Ensure to use custom ColumnDiff instance with more data and
        foreach ($tableDiff->getChangedColumns() as $changedColumn) {
            $diff->changedColumns[$changedColumn->getOldColumn()->getName()] = new ColumnDiff(
                // oldColumn
                $changedColumn->getOldColumn(),
                // newColumn
                $changedColumn->getNewColumn(),
            );
        }

        // doctrine/dbal 4+ removed the index name as array index for modified index definitions,
        // but we rely on it. Restore it !.
        foreach ($tableDiff->getModifiedIndexes() as $modifiedIndex) {
            $diff->modifiedIndexes[$modifiedIndex->getName()] = $modifiedIndex;
        }

        // Accumulate modified index separated into added and dropped information to modifiedIndexes again,
        // otherwise required drop action may not be executed before trying to add an existing index first.
        // Required for planned doctrine/dbal 4.3.0 change (deprecation) and currently breaking with an open
        // discussion to mitigate that before dbal release. We still prepare for this case to be on the safer
        // side here.
        // Needs to be done in a two-step strategy to avoid changing array while iterating over it.
        // - https://github.com/doctrine/dbal/pull/6831
        // - https://github.com/doctrine/dbal/issues/6880
        /**
         * @var array<int, array{added: Index, dropped: Index}> $transformIndexOperations
         */
        $transformIndexOperations = [];
        foreach ($diff->getAddedIndexes() as $addedIndex) {
            foreach ($diff->getDroppedIndexes() as $droppedIndex) {
                if ($droppedIndex->getName() === $addedIndex->getName()) {
                    $transformIndexOperations[] = [
                        'added' => $addedIndex,
                        'dropped' => $droppedIndex,
                    ];
                }
            }
        }
        foreach ($transformIndexOperations as $data) {
            $diff->unsetAddedIndex($data['added']);
            $diff->unsetDroppedIndex($data['dropped']);
            $diff->modifiedIndexes[$data['added']->getName()] = $data['added'];
        }

        return $diff;
    }

    /**
     * @internal This method exists only for compatibility with the current implementation of schema managers
     *           that modify the diff while processing it.
     */
    public function unsetAddedIndex(Index $index): void
    {
        $this->addedIndexes = array_filter(
            $this->addedIndexes,
            static function (Index $addedIndex) use ($index): bool {
                return $addedIndex !== $index;
            },
        );
    }

    /**
     * @internal This method exists only for compatibility with the current implementation of schema managers
     *           that modify the diff while processing it.
     */
    public function unsetDroppedIndex(Index $index): void
    {
        $this->droppedIndexes = array_filter(
            $this->droppedIndexes,
            static function (Index $droppedIndex) use ($index): bool {
                return $droppedIndex !== $index;
            },
        );
    }
}
