<?php
declare(strict_types=1);
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

use Doctrine\DBAL\Schema\Table;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Compares two Schemas and returns an instance of SchemaDiff.
 *
 * @internal
 */
class Comparator extends \Doctrine\DBAL\Schema\Comparator
{
    /**
     * Returns the difference between the tables $fromTable and $toTable.
     *
     * If there are no differences this method returns the boolean false.
     *
     * @param \Doctrine\DBAL\Schema\Table $fromTable
     * @param \Doctrine\DBAL\Schema\Table $toTable
     * @return bool|\Doctrine\DBAL\Schema\TableDiff|\TYPO3\CMS\Core\Database\Schema\TableDiff
     * @throws \InvalidArgumentException
     */
    public function diffTable(Table $fromTable, Table $toTable)
    {
        $newTableOptions = array_merge($fromTable->getOptions(), $toTable->getOptions());
        $optionDiff = array_diff_assoc($newTableOptions, $fromTable->getOptions());
        $tableDifferences = parent::diffTable($fromTable, $toTable);

        // No changed table options, return parent result
        if (count($optionDiff) === 0) {
            return $tableDifferences;
        }

        if ($tableDifferences === false) {
            $tableDifferences = GeneralUtility::makeInstance(TableDiff::class, $fromTable->getName());
            $tableDifferences->fromTable = $fromTable;
        } else {
            // Rebuild TableDiff with enhanced TYPO3 TableDiff class
            $tableDifferences = GeneralUtility::makeInstance(
                TableDiff::class,
                $tableDifferences->name,
                $tableDifferences->addedColumns,
                $tableDifferences->changedColumns,
                $tableDifferences->removedColumns,
                $tableDifferences->addedIndexes,
                $tableDifferences->changedIndexes,
                $tableDifferences->removedIndexes,
                $tableDifferences->fromTable
            );
        }

        // Set the table options to be parsed in the AlterTable event.
        $tableDifferences->setTableOptions($optionDiff);

        return $tableDifferences;
    }
}
