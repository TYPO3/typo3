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
use Doctrine\DBAL\DriverManager;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Status\ErrorStatus;
use TYPO3\CMS\Install\Status\OkStatus;

/**
 * Database select step.
 * This step is only rendered if database is mysql.
 */
class DatabaseSelect extends AbstractStepAction
{
    /**
     * @var \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected $databaseConnection = null;

    /**
     * Create database if needed, save selected db name in configuration
     *
     * @return \TYPO3\CMS\Install\Status\StatusInterface[]
     */
    public function execute()
    {
        $postValues = $this->postValues['values'];
        if ($postValues['type'] === 'new') {
            $status = $this->createNewDatabase($postValues['new']);
            if ($status instanceof ErrorStatus) {
                return [ $status ];
            }
        } elseif ($postValues['type'] === 'existing' && !empty($postValues['existing'])) {
            $status = $this->checkExistingDatabase($postValues['existing']);
            if ($status instanceof ErrorStatus) {
                return [ $status ];
            }
        } else {
            $errorStatus = GeneralUtility::makeInstance(ErrorStatus::class);
            $errorStatus->setTitle('No Database selected');
            $errorStatus->setMessage('You must select a database.');
            return [ $errorStatus ];
        }
        return [];
    }

    /**
     * Step needs to be executed if database is not set or can
     * not be selected.
     *
     * @return bool
     */
    public function needsExecution()
    {
        $result = true;
        if ((string)$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['dbname'] !== '') {
            try {
                $pingResult = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME)
                    ->ping();
                if ($pingResult === true) {
                    $result = false;
                }
            } catch (DBALException $e) {
            }
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
        $errors = [];
        /** @var $configurationManager ConfigurationManager */
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
        $isInitialInstallationInProgress = $configurationManager
            ->getConfigurationValueByPath('SYS/isInitialInstallationInProgress');
        try {
            $this->view->assign('databaseList', $this->getDatabaseList($isInitialInstallationInProgress));
        } catch (\Exception $exception) {
            $errors[] = $exception->getMessage();
        }
        $this->view->assign('errors', $errors);
        $this->view->assign('isInitialInstallationInProgress', $isInitialInstallationInProgress);
        $this->assignSteps();
        return $this->view->render();
    }

    /**
     * Returns list of available databases (with access-check based on username/password)
     *
     * @param bool $initialInstallation TRUE if first installation is in progress, FALSE if upgrading or usual access
     * @return array List of available databases
     */
    protected function getDatabaseList($initialInstallation)
    {
        $connectionParams = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME];
        unset($connectionParams['dbname']);

        // Establishing the connection using the Doctrine DriverManager directly
        // as we need a connection without selecting a database right away. Otherwise
        // an invalid database name would lead to exceptions which would prevent
        // changing the currently configured database.
        $connection = DriverManager::getConnection($connectionParams);
        $databaseArray = $connection->getSchemaManager()->listDatabases();
        $connection->close();

        // Remove organizational tables from database list
        $reservedDatabaseNames = ['mysql', 'information_schema', 'performance_schema'];
        $allPossibleDatabases = array_diff($databaseArray, $reservedDatabaseNames);

        // If we are upgrading we show *all* databases the user has access to
        if ($initialInstallation === false) {
            return $allPossibleDatabases;
        }

        // In first installation we show all databases but disable not empty ones (with tables)
        $databases = [];
        foreach ($allPossibleDatabases as $databaseName) {
            // Reestablising the connection for each database since there is no
            // portable way to switch databases on the same Doctrine connection.
            // Directly using the Doctrine DriverManager here to avoid messing with
            // the $GLOBALS database configuration array.
            $connectionParams['dbname'] = $databaseName;
            $connection = DriverManager::getConnection($connectionParams);

            $databases[] = [
                'name' => $databaseName,
                'tables' => count($connection->getSchemaManager()->listTableNames()),
            ];
            $connection->close();
        }

        return $databases;
    }

    /**
     * Validate the database name against the lowest common denominator of valid identifiers across different DBMS
     *
     * @param string $databaseName
     * @return bool
     */
    protected function isValidDatabaseName($databaseName)
    {
        return strlen($databaseName) <= 50 && preg_match('/^[a-zA-Z0-9\$_]*$/', $databaseName);
    }

    /**
     * Retrieves the default character set of the database.
     *
     * @todo this function is MySQL specific. If the core has migrated to Doctrine it should be reexamined
     * whether this function and the check in $this->checkExistingDatabase could be deleted and utf8 otherwise
     * enforced (guaranteeing compatibility with other database servers).
     *
     * @param string $dbName
     * @return string
     */
    protected function getDefaultDatabaseCharset(string $dbName): string
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
        $queryBuilder = $connection->createQueryBuilder();
        $defaultDatabaseCharset = $queryBuilder->select('DEFAULT_CHARACTER_SET_NAME')
            ->from('information_schema.SCHEMATA')
            ->where(
                $queryBuilder->expr()->eq(
                    'SCHEMA_NAME',
                    $queryBuilder->createNamedParameter($dbName, \PDO::PARAM_STR)
                )
            )
            ->setMaxResults(1)
            ->execute()
            ->fetchColumn();

        return (string)$defaultDatabaseCharset;
    }

    /**
     * Creates a new database on the default connection
     *
     * @param string $dbName name of database
     *
     * @return \TYPO3\CMS\Install\Status\StatusInterface
     */
    protected function createNewDatabase($dbName)
    {
        if (!$this->isValidDatabaseName($dbName)) {
            $errorStatus = GeneralUtility::makeInstance(ErrorStatus::class);
            $errorStatus->setTitle('Database name not valid');
            $errorStatus->setMessage(
                'Given database name must be shorter than fifty characters' .
                ' and consist solely of basic latin letters (a-z), digits (0-9), dollar signs ($)' .
                ' and underscores (_).'
            );

            return $errorStatus;
        }

        try {
            GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME)
                ->getSchemaManager()
                ->createDatabase($dbName);
            GeneralUtility::makeInstance(ConfigurationManager::class)
                ->setLocalConfigurationValueByPath('DB/Connections/Default/dbname', $dbName);
        } catch (DBALException $e) {
            $errorStatus = GeneralUtility::makeInstance(ErrorStatus::class);
            $errorStatus->setTitle('Unable to create database');
            $errorStatus->setMessage(
                'Database with name "' . $dbName . '" could not be created.' .
                ' Either your database name contains a reserved keyword or your database' .
                ' user does not have sufficient permissions to create it or the database already exists.' .
                ' Please choose an existing (empty) database, choose another name or contact administration.'
            );
            return $errorStatus;
        }

        return GeneralUtility::makeInstance(OkStatus::class);
    }

    /**
     * Checks whether an existing database on the default connection
     * can be used for a TYPO3 installation. The database name is only
     * persisted to the local configuration if the database is empty.
     *
     * @param string $dbName name of the database
     * @return \TYPO3\CMS\Install\Status\StatusInterface
     */
    protected function checkExistingDatabase($dbName)
    {
        $result = GeneralUtility::makeInstance(OkStatus::class);
        $localConfigurationPathValuePairs = [];
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
        $isInitialInstallation = $configurationManager
            ->getConfigurationValueByPath('SYS/isInitialInstallationInProgress');

        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['dbname'] = $dbName;
        try {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);

            if ($isInitialInstallation && !empty($connection->getSchemaManager()->listTableNames())) {
                $errorStatus = GeneralUtility::makeInstance(ErrorStatus::class);
                $errorStatus->setTitle('Selected database is not empty!');
                $errorStatus->setMessage(
                    sprintf('Cannot use database "%s"', $dbName)
                    . ', because it already contains tables. '
                    . 'Please select a different database or choose to create one!'
                );
                $result = $errorStatus;
            }
        } catch (\Exception $e) {
            $errorStatus = GeneralUtility::makeInstance(ErrorStatus::class);
            $errorStatus->setTitle('Could not connect to selected database!');
            $errorStatus->setMessage(
                sprintf('Could not connect to database "%s"', $dbName)
                . '! Make sure it really exists and your database user has the permissions to select it!'
            );
            $result = $errorStatus;
        }

        if ($result instanceof OkStatus) {
            $localConfigurationPathValuePairs['DB/Connections/Default/dbname'] = $dbName;
        }

        // check if database charset is utf-8 - also allow utf8mb4
        $defaultDatabaseCharset = $this->getDefaultDatabaseCharset($dbName);
        if (substr($defaultDatabaseCharset, 0, 4) !== 'utf8') {
            $errorStatus = GeneralUtility::makeInstance(ErrorStatus::class);
            $errorStatus->setTitle('Invalid Charset');
            $errorStatus->setMessage(
                'Your database uses character set "' . $defaultDatabaseCharset . '", ' .
                'but only "utf8" is supported with TYPO3. You probably want to change this before proceeding.'
            );
            $result = $errorStatus;
        }

        if ($result instanceof OkStatus && !empty($localConfigurationPathValuePairs)) {
            $configurationManager->setLocalConfigurationValuesByPathValuePairs($localConfigurationPathValuePairs);
        }

        return $result;
    }
}
