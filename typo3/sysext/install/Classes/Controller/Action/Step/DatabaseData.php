<?php
namespace TYPO3\CMS\Install\Controller\Action\Step;

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

use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Install\Status\ErrorStatus;

/**
 * Populate base tables, insert admin user, set install tool password
 */
class DatabaseData extends AbstractStepAction
{
    /**
     * Import tables and data, create admin user, create install tool password
     *
     * @return \TYPO3\CMS\Install\Status\StatusInterface[]
     */
    public function execute()
    {
        $result = [];

        /** @var ConfigurationManager $configurationManager */
        $configurationManager = $this->objectManager->get(ConfigurationManager::class);

        $postValues = $this->postValues['values'];

        $username = (string)$postValues['username'] !== '' ? $postValues['username'] : 'admin';

        // Check password and return early if not good enough
        $password = $postValues['password'];
        if (strlen($password) < 8) {
            $errorStatus = $this->objectManager->get(ErrorStatus::class);
            $errorStatus->setTitle('Administrator password not secure enough!');
            $errorStatus->setMessage(
                'You are setting an important password here! It gives an attacker full control over your instance if cracked.' .
                ' It should be strong (include lower and upper case characters, special characters and numbers) and must be at least eight characters long.'
            );
            $result[] = $errorStatus;
            return $result;
        }

        // Set site name
        if (!empty($postValues['sitename'])) {
            $configurationManager->setLocalConfigurationValueByPath('SYS/sitename', $postValues['sitename']);
        }

        $result = $this->importDatabaseData();
        if (!empty($result)) {
            return $result;
        }

        // Insert admin user
        $adminUserFields = [
            'username' => $username,
            'password' => $this->getHashedPassword($password),
            'admin' => 1,
            'tstamp' => $GLOBALS['EXEC_TIME'],
            'crdate' => $GLOBALS['EXEC_TIME']
        ];
        if (false === $this->getDatabaseConnection()->exec_INSERTquery('be_users', $adminUserFields)) {
            $errorStatus = $this->objectManager->get(ErrorStatus::class);
            $errorStatus->setTitle('Administrator account not created!');
            $errorStatus->setMessage(
                'The administrator account could not be created. The following error occurred:' . LF .
                $this->getDatabaseConnection()->sql_error()
            );
            $result[] = $errorStatus;
            return $result;
        }

        // Set password as install tool password
        $configurationManager->setLocalConfigurationValueByPath('BE/installToolPassword', $this->getHashedPassword($password));

        // Mark the initial import as done
        $this->markImportDatabaseDone();

        return $result;
    }

    /**
     * Step needs to be executed if there are no tables in database
     *
     * @return bool
     */
    public function needsExecution()
    {
        $existingTables = $this->getDatabaseConnection()->admin_get_tables();
        if (empty($existingTables)) {
            $result = true;
        } else {
            $result = !$this->isImportDatabaseDone();
        }
        return $result;
    }

    /**
     * Executes the step
     *
     * @return string Rendered content
     */
    protected function executeAction()
    {
        $this->assignSteps();
        return $this->view->render();
    }

    /**
     * Create tables and import static rows
     *
     * @return \TYPO3\CMS\Install\Status\StatusInterface[]
     */
    protected function importDatabaseData()
    {
        $result = [];
        // Will load ext_localconf and ext_tables. This is pretty safe here since we are
        // in first install (database empty), so it is very likely that no extension is loaded
        // that could trigger a fatal at this point.
        $this->loadExtLocalconfDatabaseAndExtTables();

        // Import database data
        $database = $this->getDatabaseConnection();
        /** @var \TYPO3\CMS\Install\Service\SqlSchemaMigrationService $schemaMigrationService */
        $schemaMigrationService = $this->objectManager->get(\TYPO3\CMS\Install\Service\SqlSchemaMigrationService::class);
        /** @var \TYPO3\CMS\Install\Service\SqlExpectedSchemaService $expectedSchemaService */
        $expectedSchemaService = $this->objectManager->get(\TYPO3\CMS\Install\Service\SqlExpectedSchemaService::class);

        // Raw concatenated ext_tables.sql and friends string
        $expectedSchemaString = $expectedSchemaService->getTablesDefinitionString(true);
        $statements = $schemaMigrationService->getStatementArray($expectedSchemaString, true);
        list($_, $insertCount) = $schemaMigrationService->getCreateTables($statements, true);
        $fieldDefinitionsFile = $schemaMigrationService->getFieldDefinitions_fileContent($expectedSchemaString);
        $fieldDefinitionsDatabase = $schemaMigrationService->getFieldDefinitions_database();
        $difference = $schemaMigrationService->getDatabaseExtra($fieldDefinitionsFile, $fieldDefinitionsDatabase);
        $updateStatements = $schemaMigrationService->getUpdateSuggestions($difference);

        foreach (['add', 'change', 'create_table'] as $action) {
            $updateStatus = $schemaMigrationService->performUpdateQueries($updateStatements[$action], $updateStatements[$action]);
            if ($updateStatus !== true) {
                foreach ($updateStatus as $statementIdentifier => $errorMessage) {
                    $result[$updateStatements[$action][$statementIdentifier]] = $errorMessage;
                }
            }
        }

        if (empty($result)) {
            foreach ($insertCount as $table => $count) {
                $insertStatements = $schemaMigrationService->getTableInsertStatements($statements, $table);
                foreach ($insertStatements as $insertQuery) {
                    $insertQuery = rtrim($insertQuery, ';');
                    $database->admin_query($insertQuery);
                    if ($database->sql_error()) {
                        $result[$insertQuery] = $database->sql_error();
                    }
                }
            }
        }

        foreach ($result as $statement => &$message) {
            $errorStatus = $this->objectManager->get(ErrorStatus::class);
            $errorStatus->setTitle('Database query failed!');
            $errorStatus->setMessage(
                'Query:' . LF .
                ' ' . $statement . LF .
                'Error:' . LF .
                ' ' . $message
            );
            $message = $errorStatus;
        }

        return array_values($result);
    }

    /**
     * Persist the information that the initial import has been performed
     */
    protected function markImportDatabaseDone()
    {
        $this->objectManager->get(ConfigurationManager::class)
            ->setLocalConfigurationValueByPath('SYS/isInitialDatabaseImportDone', true);
    }

    /**
     * Checks if the initial import has been performed
     *
     * @return bool
     */
    protected function isImportDatabaseDone()
    {
        return $this->objectManager->get(ConfigurationManager::class)
            ->getConfigurationValueByPath('SYS/isInitialDatabaseImportDone');
    }
}
