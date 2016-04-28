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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Database select step.
 * This step is only rendered if database is mysql. With dbal,
 * database name is submitted by previous step already.
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
     * @return array<\TYPO3\CMS\Install\Status\StatusInterface>
     */
    public function execute()
    {
        $result = array();
        $this->initializeDatabaseConnection();
        $postValues = $this->postValues['values'];
        $localConfigurationPathValuePairs = array();
        /** @var $configurationManager \TYPO3\CMS\Core\Configuration\ConfigurationManager */
        $configurationManager = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ConfigurationManager::class);
        $canProceed = true;
        if ($postValues['type'] === 'new') {
            $newDatabaseName = $postValues['new'];
            if ($this->isValidDatabaseName($newDatabaseName)) {
                $createDatabaseResult = $this->databaseConnection->admin_query('CREATE DATABASE ' . $newDatabaseName . ' CHARACTER SET utf8');
                if ($createDatabaseResult) {
                    $localConfigurationPathValuePairs['DB/Connections/Default/dbname'] = $newDatabaseName;
                } else {
                    /** @var $errorStatus \TYPO3\CMS\Install\Status\ErrorStatus */
                    $errorStatus = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\ErrorStatus::class);
                    $errorStatus->setTitle('Unable to create database');
                    $errorStatus->setMessage(
                        'Database with name ' . $newDatabaseName . ' could not be created.' .
                        ' Either your database name contains a reserved keyword or your database' .
                        ' user does not have sufficient permissions to create it.' .
                        ' Please choose an existing (empty) database or contact administration.'
                    );
                    $result[] = $errorStatus;
                }
            } else {
                /** @var $errorStatus \TYPO3\CMS\Install\Status\ErrorStatus */
                $errorStatus = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\ErrorStatus::class);
                $errorStatus->setTitle('Database name not valid');
                $errorStatus->setMessage(
                    'Given database name must be shorter than fifty characters' .
                    ' and consist solely of basic latin letters (a-z), digits (0-9), dollar signs ($)' .
                    ' and underscores (_).'
                );
                $result[] = $errorStatus;
            }
        } elseif ($postValues['type'] === 'existing' && !empty($postValues['existing'])) {
            // Only store database information when it's empty
            $this->databaseConnection->setDatabaseName($postValues['existing']);
            try {
                $this->databaseConnection->sql_select_db();
                $existingTables = $this->databaseConnection->admin_get_tables();
                if (!empty($existingTables)) {
                    $errorStatus = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\ErrorStatus::class);
                    $errorStatus->setTitle('Selected database is not empty!');
                    $errorStatus->setMessage(
                        sprintf('Cannot use database "%s"', $postValues['existing'])
                        . ', because it has tables already. Please select a different database or choose to create one!'
                    );
                    $result[] = $errorStatus;
                }
            } catch (\RuntimeException $e) {
                $errorStatus = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\ErrorStatus::class);
                $errorStatus->setTitle('Could not connect to selected database!');
                $errorStatus->setMessage(
                    sprintf('Could not connect to database "%s"', $postValues['existing'])
                    . '! Make sure it really exists and your database user has the permissions to select it!'
                );
                $result[] = $errorStatus;
            }
            $isInitialInstallation = $configurationManager->getConfigurationValueByPath('SYS/isInitialInstallationInProgress');
            if (!$isInitialInstallation || empty($result)) {
                $localConfigurationPathValuePairs['DB/Connections/Default/dbname'] = $postValues['existing'];
            }
            // check if database charset is utf-8
            $defaultDatabaseCharset = $this->getDefaultDatabaseCharset();
            // also allow utf8mb4
            if (substr($defaultDatabaseCharset, 0, 4) !== 'utf8') {
                $errorStatus = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\ErrorStatus::class);
                $errorStatus->setTitle('Invalid Charset');
                $errorStatus->setMessage(
                    'Your database uses character set "' . $defaultDatabaseCharset . '", ' .
                    'but only "utf8" is supported with TYPO3. You probably want to change this before proceeding.'
                );
                $result[] = $errorStatus;
                $canProceed = false;
            }
        } else {
            /** @var $errorStatus \TYPO3\CMS\Install\Status\ErrorStatus */
            $errorStatus = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\ErrorStatus::class);
            $errorStatus->setTitle('No Database selected');
            $errorStatus->setMessage('You must select a database.');
            $result[] = $errorStatus;
        }

        if ($canProceed && !empty($localConfigurationPathValuePairs)) {
            $configurationManager->setLocalConfigurationValuesByPathValuePairs($localConfigurationPathValuePairs);
        }

        return $result;
    }

    /**
     * Step needs to be executed if database is not set or can
     * not be selected.
     *
     * @return bool
     */
    public function needsExecution()
    {
        $this->initializeDatabaseConnection();
        $result = true;
        if ((string)$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['dbname'] !== '') {
            $this->databaseConnection->setDatabaseName($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['dbname']);
            try {
                $selectResult = $this->databaseConnection->sql_select_db();
                if ($selectResult === true) {
                    $result = false;
                }
            } catch (\RuntimeException $e) {
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
        /** @var $configurationManager \TYPO3\CMS\Core\Configuration\ConfigurationManager */
        $configurationManager = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ConfigurationManager::class);
        $isInitialInstallationInProgress = $configurationManager->getConfigurationValueByPath('SYS/isInitialInstallationInProgress');
        $this->view->assign('databaseList', $this->getDatabaseList($isInitialInstallationInProgress));
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
        $this->initializeDatabaseConnection();
        $databaseArray = $this->databaseConnection->admin_get_dbs();
        // Remove mysql organizational tables from database list
        $reservedDatabaseNames = array('mysql', 'information_schema', 'performance_schema');
        $allPossibleDatabases = array_diff($databaseArray, $reservedDatabaseNames);

        // If we are upgrading we show *all* databases the user has access to
        if ($initialInstallation === false) {
            return $allPossibleDatabases;
        } else {
            // In first installation we show all databases but disable not empty ones (with tables)
            $databases = array();
            foreach ($allPossibleDatabases as $database) {
                $this->databaseConnection->setDatabaseName($database);
                $this->databaseConnection->sql_select_db();
                $existingTables = $this->databaseConnection->admin_get_tables();
                $databases[] = array(
                    'name' => $database,
                    'tables' => count($existingTables),
                );
            }
            return $databases;
        }
    }

    /**
     * Initialize database connection
     *
     * @return void
     */
    protected function initializeDatabaseConnection()
    {
        $this->databaseConnection = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\DatabaseConnection::class);
        $this->databaseConnection->setDatabaseUsername($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['user']);
        $this->databaseConnection->setDatabasePassword($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['password']);
        $this->databaseConnection->setDatabaseHost($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['host']);
        $this->databaseConnection->setDatabasePort($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['port']);
        $this->databaseConnection->setDatabaseSocket($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['unix_socket']);
        $this->databaseConnection->sql_pconnect();
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
     * @return string
     */
    protected function getDefaultDatabaseCharset()
    {
        $result = $this->databaseConnection->admin_query('SHOW VARIABLES LIKE "character_set_database"');
        $row = $this->databaseConnection->sql_fetch_assoc($result);

        $key = $row['Variable_name'];
        $value = $row['Value'];
        $databaseCharset = '';

        if ($key == 'character_set_database') {
            $databaseCharset = $value;
        }

        return $databaseCharset;
    }
}
