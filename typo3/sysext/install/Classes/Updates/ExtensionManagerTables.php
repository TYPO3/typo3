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
     * @var NULL|\TYPO3\CMS\Install\Service\SqlSchemaMigrationService
     */
    protected $installToolSqlParser = null;

    /**
     * @return \TYPO3\CMS\Install\Service\SqlSchemaMigrationService
     */
    protected function getInstallToolSqlParser()
    {
        if ($this->installToolSqlParser === null) {
            $this->installToolSqlParser = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Service\SqlSchemaMigrationService::class);
        }

        return $this->installToolSqlParser;
    }

    /**
     * Gets all create, add and change queries from ext_tables.sql
     *
     * @return array
     */
    protected function getUpdateStatements()
    {
        $updateStatements = [];

        // Get all necessary statements for ext_tables.sql file
        $rawDefinitions = GeneralUtility::getUrl(ExtensionManagementUtility::extPath('extensionmanager') . '/ext_tables.sql');
        $fieldDefinitionsFromFile = $this->getInstallToolSqlParser()->getFieldDefinitions_fileContent($rawDefinitions);
        if (count($fieldDefinitionsFromFile)) {
            $fieldDefinitionsFromCurrentDatabase = $this->getInstallToolSqlParser()->getFieldDefinitions_database();
            $diff = $this->getInstallToolSqlParser()->getDatabaseExtra($fieldDefinitionsFromFile, $fieldDefinitionsFromCurrentDatabase);
            $updateStatements = $this->getInstallToolSqlParser()->getUpdateSuggestions($diff);
        }

        return $updateStatements;
    }

    /**
     * Checks if an update is needed
     *
     * @param string &$description The description for the update
     * @return bool Whether an update is needed (TRUE) or not (FALSE)
     */
    public function checkForUpdate(&$description)
    {
        $result = false;
        $description = 'Creates necessary database tables and adds static data for the Extension Manager.';

        // First check necessary database update
        $updateStatements = $this->getUpdateStatements();
        if (empty($updateStatements)) {
            // Get count of rows in repository database table
            $count = $this->getDatabaseConnection()->exec_SELECTcountRows('*', 'tx_extensionmanager_domain_model_repository');
            if ($count === 0) {
                $result = true;
            }
        } else {
            $result = true;
        }

        return $result;
    }

    /**
     * @param mixed &$customMessages Custom messages
     *
     * @return bool
     */
    protected function hasError(&$customMessages)
    {
        $result = false;
        if ($this->getDatabaseConnection()->sql_error()) {
            $customMessages .= '<br /><br />SQL-ERROR: ' . htmlspecialchars($this->getDatabaseConnection()->sql_error());
            $result = true;
        }

        return $result;
    }

    /**
     * Performs the database update.
     *
     * @param array &$dbQueries Queries done in this update
     * @param mixed &$customMessages Custom messages
     * @return bool Whether it worked (TRUE) or not (FALSE)
     */
    public function performUpdate(array &$dbQueries, &$customMessages)
    {
        $result = false;

        // First perform all create, add and change queries
        $updateStatements = $this->getUpdateStatements();
        foreach ((array)$updateStatements['add'] as $string) {
            $this->getDatabaseConnection()->admin_query($string);
            $dbQueries[] = $string;
            $result = ($result || $this->hasError($customMessages));
        }
        foreach ((array)$updateStatements['change'] as $string) {
            $this->getDatabaseConnection()->admin_query($string);
            $dbQueries[] = $string;
            $result = ($result || $this->hasError($customMessages));
        }
        foreach ((array)$updateStatements['create_table'] as $string) {
            $this->getDatabaseConnection()->admin_query($string);
            $dbQueries[] = $string;
            $result = ($result || $this->hasError($customMessages));
        }

        // Perform statis import anyway
        $rawDefinitions = GeneralUtility::getUrl(ExtensionManagementUtility::extPath('extensionmanager') . 'ext_tables_static+adt.sql');
        $statements = $this->getInstallToolSqlParser()->getStatementarray($rawDefinitions, 1);
        foreach ($statements as $statement) {
            if (trim($statement) !== '') {
                $this->getDatabaseConnection()->admin_query($statement);
                $dbQueries[] = $statement;
                $result = ($result || $this->hasError($customMessages));
            }
        }

        return !$result;
    }
}
