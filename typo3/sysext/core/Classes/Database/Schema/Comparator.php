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

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\BlobType;
use Doctrine\DBAL\Types\TextType;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Compares two Schemas and returns an instance of SchemaDiff.
 *
 * @internal
 */
class Comparator extends \Doctrine\DBAL\Schema\Comparator
{
    /**
     * @var AbstractPlatform|null
     */
    protected $databasePlatform;

    /**
     * Comparator constructor.
     *
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform
     */
    public function __construct(AbstractPlatform $platform = null)
    {
        $this->databasePlatform = $platform;
    }

    /**
     * Returns the difference between the tables $fromTable and $toTable.
     *
     * If there are no differences this method returns the boolean false.
     *
     * @param \Doctrine\DBAL\Schema\Table $fromTable
     * @param \Doctrine\DBAL\Schema\Table $toTable
     * @return false|\Doctrine\DBAL\Schema\TableDiff|\TYPO3\CMS\Core\Database\Schema\TableDiff
     * @throws \InvalidArgumentException
     */
    public function diffTable(Table $fromTable, Table $toTable)
    {
        $newTableOptions = array_merge($fromTable->getOptions(), $toTable->getOptions());
        $optionDiff = ArrayUtility::arrayDiffAssocRecursive($newTableOptions, $fromTable->getOptions(), true);
        $tableDifferences = parent::diffTable($fromTable, $toTable);

        // No changed table options, return parent result
        if (count($optionDiff) === 0) {
            return $tableDifferences;
        }

        if ($tableDifferences === false) {
            $tableDifferences = GeneralUtility::makeInstance(TableDiff::class, $fromTable->getName());
            $tableDifferences->fromTable = $fromTable;
        } else {
            $renamedColumns = $tableDifferences->renamedColumns;
            $renamedIndexes = $tableDifferences->renamedIndexes;
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
            $tableDifferences->renamedColumns = $renamedColumns;
            $tableDifferences->renamedIndexes = $renamedIndexes;
        }

        // Set the table options to be parsed in the AlterTable event.
        $tableDifferences->setTableOptions($optionDiff);

        return $tableDifferences;
    }

    /**
     * Returns the difference between the columns $column1 and $column2
     * by first checking the doctrine diffColumn. Extend the Doctrine
     * method by taking into account MySQL TINY/MEDIUM/LONG type variants.
     *
     * @param \Doctrine\DBAL\Schema\Column $column1
     * @param \Doctrine\DBAL\Schema\Column $column2
     * @return array
     */
    public function diffColumn(Column $column1, Column $column2)
    {
        $changedProperties = parent::diffColumn($column1, $column2);

        // Only MySQL has variable length versions of TEXT/BLOB
        if (!$this->databasePlatform instanceof MySqlPlatform) {
            return $changedProperties;
        }

        $properties1 = $column1->toArray();
        $properties2 = $column2->toArray();

        if ($properties1['type'] instanceof BlobType || $properties1['type'] instanceof TextType) {
            // Doctrine does not provide a length for LONGTEXT/LONGBLOB columns
            $length1 = $properties1['length'] ?: 2147483647;
            $length2 = $properties2['length'] ?: 2147483647;

            if ($length1 !== $length2) {
                $changedProperties[] = 'length';
            }
        }

        return array_unique($changedProperties);
    }
}
