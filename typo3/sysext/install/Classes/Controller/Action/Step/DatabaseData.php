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

use Doctrine\DBAL\DBALException;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Schema\Exception\StatementException;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);

        $postValues = $this->postValues['values'];

        $username = (string)$postValues['username'] !== '' ? $postValues['username'] : 'admin';

        // Check password and return early if not good enough
        $password = $postValues['password'];
        if (strlen($password) < 8) {
            $errorStatus = GeneralUtility::makeInstance(ErrorStatus::class);
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

        try {
            $result = $this->importDatabaseData();
            if (!empty($result)) {
                return $result;
            }
        } catch (StatementException $exception) {
            $errorStatus = GeneralUtility::makeInstance(ErrorStatus::class);
            $errorStatus->setTitle('Import of database data could not be performed');
            $errorStatus->setMessage(
                'Error detected in SQL statement:' . LF .
                $exception->getMessage()
            );
            $result[] = $errorStatus;
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
        $databaseConnection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('be_users');
        try {
            $databaseConnection->insert('be_users', $adminUserFields);
        } catch (DBALException $exception) {
            $errorStatus = GeneralUtility::makeInstance(ErrorStatus::class);
            $errorStatus->setTitle('Administrator account not created!');
            $errorStatus->setMessage(
                'The administrator account could not be created. The following error occurred:' . LF .
                $exception->getPrevious()->getMessage()
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
        $existingTables = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionByName('Default')
            ->getSchemaManager()
            ->listTableNames();
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
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\UnexpectedSignalReturnValueTypeException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     */
    protected function importDatabaseData()
    {
        // Will load ext_localconf and ext_tables. This is pretty safe here since we are
        // in first install (database empty), so it is very likely that no extension is loaded
        // that could trigger a fatal at this point.
        $this->loadExtLocalconfDatabaseAndExtTables();

        $sqlReader = GeneralUtility::makeInstance(SqlReader::class);
        $sqlCode = $sqlReader->getTablesDefinitionString(true);

        $schemaMigrationService = GeneralUtility::makeInstance(SchemaMigrator::class);
        $createTableStatements = $sqlReader->getCreateTableStatementArray($sqlCode);

        $results = $schemaMigrationService->install($createTableStatements);

        // Only keep statements with error messages
        $results = array_filter($results);
        if (count($results) === 0) {
            $insertStatements = $sqlReader->getInsertStatementArray($sqlCode);
            $results = $schemaMigrationService->importStaticData($insertStatements);
        }

        foreach ($results as $statement => &$message) {
            if ($message === '') {
                unset($results[$statement]);
                continue;
            }

            $errorStatus = GeneralUtility::makeInstance(ErrorStatus::class);
            $errorStatus->setTitle('Database query failed!');
            $errorStatus->setMessage(
                'Query:' . LF .
                ' ' . $statement . LF .
                'Error:' . LF .
                ' ' . $message
            );
            $message = $errorStatus;
        }

        return array_values($results);
    }

    /**
     * Persist the information that the initial import has been performed
     */
    protected function markImportDatabaseDone()
    {
        GeneralUtility::makeInstance(ConfigurationManager::class)
            ->setLocalConfigurationValueByPath('SYS/isInitialDatabaseImportDone', true);
    }

    /**
     * Checks if the initial import has been performed
     *
     * @return bool
     */
    protected function isImportDatabaseDone()
    {
        return GeneralUtility::makeInstance(ConfigurationManager::class)
            ->getConfigurationValueByPath('SYS/isInitialDatabaseImportDone');
    }
}
