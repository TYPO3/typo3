<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Database\Schema;

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
     *
     * @return array
     */
    public function getTableOptions(): array
    {
        return $this->tableOptions;
    }

    /**
     * Setter for table options
     *
     * @param array $tableOptions
     * @return \TYPO3\CMS\Core\Database\Schema\TableDiff
     */
    public function setTableOptions(array $tableOptions): TableDiff
    {
        $this->tableOptions = $tableOptions;

        return $this;
    }

    /**
     * Check if a table options has been set.
     *
     * @param string $optionName
     * @return bool
     */
    public function hasTableOption(string $optionName): bool
    {
        return array_key_exists($optionName, $this->tableOptions);
    }

    /**
     * @param string $optionName
     * @return string
     */
    public function getTableOption(string $optionName): string
    {
        if ($this->hasTableOption($optionName)) {
            return (string)$this->tableOptions[$optionName];
        }

        return '';
    }
}
