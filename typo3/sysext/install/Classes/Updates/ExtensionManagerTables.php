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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Contains the update class for filling the basic repository record of the extension manager
 */
class ExtensionManagerTables extends AbstractUpdate
{
    /**
     * @var string
     */
    protected $title = 'Add the default Extension Manager database tables';

    /**
     * Gets all create, add and change queries from ext_tables.sql
     *
     * @return array
     * @throws \BadFunctionCallException
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\UnexpectedSignalReturnValueTypeException
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    protected function getUpdateStatements()
    {
        $updateStatements = [];

        $emTableStatements = $this->getTableStatements();

        if (count($emTableStatements)) {
            $schemaMigrationService = GeneralUtility::makeInstance(SchemaMigrator::class);
            $updateSuggestions = $schemaMigrationService->getUpdateSuggestions($emTableStatements);
            $updateStatements = array_merge_recursive(...array_values($updateSuggestions));
        }

        return $updateStatements;
    }

    /**
     * Get all CREATE TABLE statements from the ext_tables.sql file
     *
     * @return string[]
     * @throws \BadFunctionCallException
     * @throws \InvalidArgumentException
     */
    protected function getTableStatements(): array
    {
        $rawDefinitions = file_get_contents(ExtensionManagementUtility::extPath('extensionmanager', 'ext_tables.sql'));
        $sqlReader = GeneralUtility::makeInstance(SqlReader::class);
        return $sqlReader->getCreateTableStatementArray($rawDefinitions);
    }

    /**
     * Checks if an update is needed
     *
     * @param string &$description The description for the update
     * @return bool Whether an update is needed (TRUE) or not (FALSE)
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\UnexpectedSignalReturnValueTypeException
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     * @throws \RuntimeException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \Doctrine\DBAL\DBALException
     * @throws \BadFunctionCallException
     * @throws \InvalidArgumentException
     */
    public function checkForUpdate(&$description)
    {
        $result = false;
        $description = 'Creates necessary database tables and adds static data for the Extension Manager.';

        // First check necessary database update
        $updateStatements = array_filter($this->getUpdateStatements());
        if (count($updateStatements) === 0) {
            // Get count of rows in repository database table
            $count = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('tx_extensionmanager_domain_model_repository')
                ->count('*', 'tx_extensionmanager_domain_model_repository', []);

            if ($count === 0) {
                $result = true;
            }
        } else {
            $result = true;
        }

        return $result;
    }

    /**
     * Performs the database update.
     *
     * @param array &$dbQueries Queries done in this update
     * @param string &$customMessage Custom message
     * @return bool Whether it worked (TRUE) or not (FALSE)
     */
    public function performUpdate(array &$dbQueries, &$customMessage)
    {
        $result = true;
        $sqlReader = GeneralUtility::makeInstance(SqlReader::class);

        $createTableStatements = $this->getTableStatements();

        // First perform all create, add and change queries
        $schemaMigrationService = GeneralUtility::makeInstance(SchemaMigrator::class);
        $schemaMigrationService->install($createTableStatements);

        // Perform import of static data
        $rawDefinitions = file_get_contents(
            ExtensionManagementUtility::extPath('extensionmanager', 'ext_tables_static+adt.sql')
        );

        $insertStatements = $sqlReader->getInsertStatementArray($rawDefinitions);
        $results = $schemaMigrationService->importStaticData($insertStatements);

        foreach ($results as $statement => $errorMessage) {
            $dbQueries[] = $statement;
            if ($errorMessage) {
                $result = false;
                $customMessage .= '<br /><br />SQL-ERROR: ' . htmlspecialchars($errorMessage);
            }
        }

        return $result;
    }
}
