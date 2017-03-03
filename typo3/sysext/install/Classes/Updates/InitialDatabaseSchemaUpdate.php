<?php
namespace TYPO3\CMS\Install\Updates;

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

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\DBAL\Schema\Table;

/**
 * Contains the update class to create tables, fields and keys to comply to the database schema
 */
class InitialDatabaseSchemaUpdate extends AbstractDatabaseSchemaUpdate
{
    /**
     * Constructor function.
     */
    public function __construct()
    {
        parent::__construct();
        $this->title = 'Update database schema: Create tables and fields';
    }

    /**
     * Checks if an update is needed
     *
     * @param string &$description The description for the update
     * @return bool TRUE if an update is needed, FALSE otherwise
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\UnexpectedSignalReturnValueTypeException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     */
    public function checkForUpdate(&$description)
    {
        $description = 'There are tables or fields in the database which need to be created.<br /><br />' .
        'You have to run this update wizard before you can run any other update wizard to make sure all ' .
        'needed tables and fields are present.';

        $databaseDifferences = $this->getDatabaseDifferences();
        foreach ($databaseDifferences as $schemaDiff) {
            // A new table is required, early return
            if (!empty($schemaDiff->newTables)) {
                return true;
            }

            // A new field or index is required
            foreach ($schemaDiff->changedTables as $changedTable) {
                if (!empty($changedTable->addedColumns)) {
                    return true;
                }

                // Ignore new indexes that work on columns that need changes
                foreach ($changedTable->addedIndexes as $indexName => $addedIndex) {
                    // Strip MySQL prefix length information to get real column names
                    $indexColumns = array_map(
                        function ($columnName) {
                            return preg_replace('/\(\d+\)$/', '', $columnName);
                        },
                        $addedIndex->getColumns()
                    );
                    $columnChanges = array_intersect($indexColumns, array_keys($changedTable->changedColumns));
                    if (!empty($columnChanges)) {
                        unset($changedTable->addedIndexes[$indexName]);
                    }
                }

                if (!empty($changedTable->addedIndexes)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Second step: Show tables, fields and keys to be created
     *
     * @param string $inputPrefix input prefix, all names of form fields are prefixed with this
     * @return string HTML output
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\UnexpectedSignalReturnValueTypeException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     */
    public function getUserInput($inputPrefix): string
    {
        $result = '';
        $addedTables = '';
        $addedFields = '';
        $addedIndexes = '';

        $databaseDifferences = $this->getDatabaseDifferences();
        foreach ($databaseDifferences as $schemaDiff) {
            $addedTables .= $this->getAddedTableInformation($schemaDiff);
            $addedFields .= $this->getAddedFieldInformation($schemaDiff);
            $addedIndexes .= $this->getAddedIndexInformation($schemaDiff);
        }

        $result .= $this->renderList('Add the following tables:', $addedTables);
        $result .= $this->renderList('Add the following fields to tables:', $addedFields);
        $result .= $this->renderList('Add the following keys to tables:', $addedIndexes);

        return $result;
    }

    /**
     * Performs the database update.
     *
     * @param array &$dbQueries Queries done in this update
     * @param string &$customMessage Custom message
     * @return bool TRUE on success, FALSE on error
     */
    public function performUpdate(array &$dbQueries, &$customMessage): bool
    {
        $statements = $this->getDatabaseDefinition();
        $result = $this->schemaMigrationService->install($statements, true);

        // Extract all statements stored in the keys, independent of error status
        $dbQueries = array_merge($dbQueries, array_keys($result));

        // Only keep error messages
        $result = array_filter($result);

        $customMessage = implode(
            LF,
            array_map(
                function (string $message) {
                    return 'SQL-ERROR: ' . htmlspecialchars($message);
                },
                $result
            )
        );

        return empty($result);
    }

    /**
     * Return HTML list items for added tables
     *
     * @param \Doctrine\DBAL\Schema\SchemaDiff $schemaDiff
     * @return string
     */
    protected function getAddedTableInformation(SchemaDiff $schemaDiff): string
    {
        $items = array_map(
            function (Table $table) {
                return $this->renderTableListItem($table->getName());
            },
            $schemaDiff->newTables
        );

        return trim(implode(LF, $items));
    }

    /**
     * Return HTML list items for fields added to tables
     *
     * @param \Doctrine\DBAL\Schema\SchemaDiff $schemaDiff
     * @return string
     */
    protected function getAddedFieldInformation(SchemaDiff $schemaDiff): string
    {
        $items = [];

        foreach ($schemaDiff->changedTables as $changedTable) {
            $columns = array_map(
                function (Column $column) use ($changedTable) {
                    return $this->renderFieldListItem($changedTable->name, $column->getName());
                },
                $changedTable->addedColumns
            );

            $items[] = implode(LF, $columns);
        }

        return trim(implode(LF, $items));
    }

    /**
     * Return HTML list items for indexes added to tables
     *
     * @param \Doctrine\DBAL\Schema\SchemaDiff $schemaDiff
     * @return string
     */
    protected function getAddedIndexInformation(SchemaDiff $schemaDiff): string
    {
        $items = [];

        foreach ($schemaDiff->changedTables as $changedTable) {
            $indexes = array_map(
                function (Index $index) use ($changedTable) {
                    return $this->renderFieldListItem($changedTable->name, $index->getName());
                },
                $changedTable->addedIndexes
            );

            $items[] = implode(LF, $indexes);
        }

        return trim(implode(LF, $items));
    }
}
