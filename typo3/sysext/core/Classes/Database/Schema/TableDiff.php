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

/**
 * Helper methods to handle raw SQL input and transform it into individual statements
 * for further processing.
 *
 * @internal
 */
class TableDiff extends \Doctrine\DBAL\Schema\TableDiff
{
    /**
     * Platform specific table options
     *
     * @var array
     */
    protected $tableOptions = [];

    /**
     * Getter for table options.
     */
    public function getTableOptions(): array
    {
        return $this->tableOptions;
    }

    /**
     * Setter for table options
     */
    public function setTableOptions(array $tableOptions): TableDiff
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

    public function isEmpty(): bool
    {
        return count($this->addedColumns) === 0
            && count($this->changedColumns) === 0
            && count($this->removedColumns) === 0
            && count($this->renamedColumns) === 0
            && count($this->addedIndexes) === 0
            && count($this->changedIndexes) === 0
            && count($this->removedIndexes) === 0
            && count($this->renamedIndexes) === 0
            && count($this->addedForeignKeys) === 0
            && count($this->changedForeignKeys) === 0
            && count($this->removedForeignKeys) === 0
            // @todo Recheck this after proper table rename strategy has been evolved. Related to doctrine/dbal 3.5 changes.
            && $this->newName === false
            // @todo doctrine/dbal 3.5 deprecated schema events, thus a new way to provide table option has to
            //       be found and implemented. Recheck this afterwards.
            && $this->tableOptions === [];
    }
}
