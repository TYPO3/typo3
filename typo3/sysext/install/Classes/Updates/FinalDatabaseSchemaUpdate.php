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

use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\SchemaDiff;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\ContextService;

/**
 * Contains the update class to create and alter tables, fields and keys to comply to the database schema
 */
class FinalDatabaseSchemaUpdate extends AbstractDatabaseSchemaUpdate
{
    /**
     * Constructor function.
     */
    public function __construct()
    {
        parent::__construct();
        $this->title = 'Update database schema: Modify tables and fields';
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
    public function checkForUpdate(&$description): bool
    {
        $contextService = GeneralUtility::makeInstance(ContextService::class);
        $description = 'There are tables or fields in the database which need to be changed.<br /><br />' .
        'This update wizard can be run only when there are no other update wizards left to make sure they have ' .
        'all needed fields unchanged.<br /><br />If you want to apply changes selectively, ' .
        '<a href="' . GeneralUtility::getIndpEnv('TYPO3_REQUEST_SCRIPT') . '?install[action]=importantActions&amp;install[context]=' .
        $contextService->getContextString() .
        '&amp;install[controller]=tool">go to Database Analyzer</a>.';

        $databaseDifferences = $this->getDatabaseDifferences();
        foreach ($databaseDifferences as $schemaDiff) {
            // A change for a table is required
            if (count($schemaDiff->changedTables) !== 0) {
                foreach ($schemaDiff->changedTables as $changedTable) {
                    if (!empty($changedTable->addedColumns) || !empty($changedTable->changedColumns)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Second step: Show tables, fields and keys to create or update
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
    public function getUserInput($inputPrefix)
    {
        $result = '';
        $changedFieldItems = '';
        $changedIndexItems = '';

        $databaseDifferences = $this->getDatabaseDifferences();
        foreach ($databaseDifferences as $schemaDiff) {
            $changedFieldItems .= $this->getChangedFieldInformation($schemaDiff);
            $changedIndexItems .= $this->getChangedIndexInformation($schemaDiff);
        }

        $result .= $this->renderList('Change the following fields in tables:', $changedFieldItems);
        $result .= $this->renderList('Change the following keys in tables:', $changedIndexItems);

        return $result;
    }

    /**
     * Performs the database update.
     *
     * @param array &$dbQueries Queries done in this update
     * @param string &$customMessage Custom message
     * @return bool TRUE on success, FALSE on error
     */
    public function performUpdate(array &$dbQueries, &$customMessage)
    {
        $statements = $this->getDatabaseDefinition();
        $result = $this->schemaMigrationService->install($statements, false);

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

        return count($result) === 0;
    }

    /**
     * Return HTML list items for fields added to tables
     *
     * @param \Doctrine\DBAL\Schema\SchemaDiff $schemaDiff
     * @return string
     */
    protected function getChangedFieldInformation(SchemaDiff $schemaDiff): string
    {
        $items = [];

        foreach ($schemaDiff->changedTables as $changedTable) {
            $columns = array_map(
                function (ColumnDiff $columnDiff) use ($changedTable) {
                    return $this->renderFieldListItem($changedTable->name, $columnDiff->column->getName());
                },
                $changedTable->changedColumns
            );

            $items[] = implode(LF, $columns);
        }

        return trim(implode(LF, $items));
    }

    /**
     * Return HTML list items for changed indexes on tables
     *
     * @param \Doctrine\DBAL\Schema\SchemaDiff $schemaDiff
     * @return string
     */
    protected function getChangedIndexInformation(SchemaDiff $schemaDiff): string
    {
        $items = [];

        foreach ($schemaDiff->changedTables as $changedTable) {
            $indexes = array_map(
                function (Index $index) use ($changedTable) {
                    return $this->renderFieldListItem($changedTable->name, $index->getName());
                },
                $changedTable->changedIndexes
            );

            $items[] = implode(LF, $indexes);
        }

        return trim(implode(LF, $items));
    }
}
