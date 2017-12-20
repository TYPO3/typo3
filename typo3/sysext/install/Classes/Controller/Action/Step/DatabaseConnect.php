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
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Status\ErrorStatus;

/**
 * Database connect step:
 * - Needs execution if database credentials are not set or fail to connect
 * - Renders fields for database connection fields
 * - Sets database credentials in LocalConfiguration
 */
class DatabaseConnect extends AbstractStepAction
{
    /**
     * Execute database step:
     * - Set database connect credentials in LocalConfiguration
     *
     * @return array<\TYPO3\CMS\Install\Status\StatusInterface>
     */
    public function execute()
    {
        $result = [];
        $postValues = $this->postValues['values'];
        $defaultConnectionSettings = [];

        if ($postValues['availableSet'] === 'configurationFromEnvironment') {
            $defaultConnectionSettings = $this->getConfigurationFromEnvironment();
        } else {
            if (isset($postValues['driver'])) {
                $validDrivers = [
                    'mysqli',
                    'pdo_mysql',
                    'pdo_pgsql',
                    'mssql',
                ];
                if (in_array($postValues['driver'], $validDrivers, true)) {
                    $defaultConnectionSettings['driver'] = $postValues['driver'];
                } else {
                    $errorStatus = GeneralUtility::makeInstance(ErrorStatus::class);
                    $errorStatus->setTitle('Database driver unknown');
                    $errorStatus->setMessage('Given driver must be one of ' . implode(', ', $validDrivers));
                    $result[] = $errorStatus;
                }
            }
            if (isset($postValues['username'])) {
                $value = $postValues['username'];
                if (strlen($value) <= 50) {
                    $defaultConnectionSettings['user'] = $value;
                } else {
                    $errorStatus = GeneralUtility::makeInstance(ErrorStatus::class);
                    $errorStatus->setTitle('Database username not valid');
                    $errorStatus->setMessage('Given username must be shorter than fifty characters.');
                    $result[] = $errorStatus;
                }
            }
            if (isset($postValues['password'])) {
                $defaultConnectionSettings['password'] = $postValues['password'];
            }
            if (isset($postValues['host'])) {
                $value = $postValues['host'];
                if (preg_match('/^[a-zA-Z0-9_\\.-]+(:.+)?$/', $value) && strlen($value) <= 255) {
                    $defaultConnectionSettings['host'] = $value;
                } else {
                    $errorStatus = GeneralUtility::makeInstance(ErrorStatus::class);
                    $errorStatus->setTitle('Database host not valid');
                    $errorStatus->setMessage('Given host is not alphanumeric (a-z, A-Z, 0-9 or _-.:) or longer than 255 characters.');
                    $result[] = $errorStatus;
                }
            }
            if (isset($postValues['port']) && $postValues['host'] !== 'localhost') {
                $value = $postValues['port'];
                if (preg_match('/^[0-9]+(:.+)?$/', $value) && $value > 0 && $value <= 65535) {
                    $defaultConnectionSettings['port'] = (int)$value;
                } else {
                    $errorStatus = GeneralUtility::makeInstance(ErrorStatus::class);
                    $errorStatus->setTitle('Database port not valid');
                    $errorStatus->setMessage('Given port is not numeric or within range 1 to 65535.');
                    $result[] = $errorStatus;
                }
            }
            if (isset($postValues['socket']) && $postValues['socket'] !== '') {
                if (@file_exists($postValues['socket'])) {
                    $defaultConnectionSettings['unix_socket'] = $postValues['socket'];
                } else {
                    $errorStatus = GeneralUtility::makeInstance(ErrorStatus::class);
                    $errorStatus->setTitle('Socket does not exist');
                    $errorStatus->setMessage('Given socket location does not exist on server.');
                    $result[] = $errorStatus;
                }
            }
            if (isset($postValues['database'])) {
                $value = $postValues['database'];
                if (strlen($value) <= 50) {
                    $defaultConnectionSettings['dbname'] = $value;
                } else {
                    $errorStatus = GeneralUtility::makeInstance(ErrorStatus::class);
                    $errorStatus->setTitle('Database name not valid');
                    $errorStatus->setMessage('Given database name must be shorter than fifty characters.');
                    $result[] = $errorStatus;
                }
            }
        }

        if (!empty($defaultConnectionSettings)) {
            // Test connection settings and write to config if connect is successful
            try {
                $connectionParams = $defaultConnectionSettings;
                $connectionParams['wrapperClass'] = Connection::class;
                $connectionParams['charset'] = 'utf-8';
                DriverManager::getConnection($connectionParams)->ping();
            } catch (DBALException $e) {
                $errorStatus = GeneralUtility::makeInstance(ErrorStatus::class);
                $errorStatus->setTitle('Database connect not successful');
                $errorStatus->setMessage('Connecting to the database with given settings failed: ' . $e->getMessage());
                $result[] = $errorStatus;
            }
            $localConfigurationPathValuePairs = [];
            foreach ($defaultConnectionSettings as $settingsName => $value) {
                $localConfigurationPathValuePairs['DB/Connections/Default/' . $settingsName] = $value;
            }
            $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
            // Remove full default connection array
            $configurationManager->removeLocalConfigurationKeysByPath([ 'DB/Connections/Default' ]);
            // Write new values
            $configurationManager->setLocalConfigurationValuesByPathValuePairs($localConfigurationPathValuePairs);
        }

        return $result;
    }

    /**
     * Step needs to be executed if database connection is not successful.
     *
     * @throws \TYPO3\CMS\Install\Controller\Exception\RedirectException
     * @return bool
     */
    public function needsExecution()
    {
        if ($this->isConnectSuccessful() && $this->isConfigurationComplete()) {
            return false;
        }
        return true;
    }

    /**
     * Executes the step
     *
     * @return string Rendered content
     */
    protected function executeAction()
    {
        $hasAtLeastOneOption = false;
        $activeAvailableOption = '';
        if (extension_loaded('mysqli')) {
            $hasAtLeastOneOption = true;
            $this->view->assign('hasMysqliManualConfiguration', true);
            $mysqliManualConfigurationOptions = [
                'username' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['user'] ?? '',
                'password' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['password'] ?? '',
                'port' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['port'] ?? 3306,
            ];
            $host = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['host'] ?? '127.0.0.1';
            if ($host === 'localhost') {
                $host = '127.0.0.1';
            }
            $mysqliManualConfigurationOptions['host'] = $host;
            $this->view->assign('mysqliManualConfigurationOptions', $mysqliManualConfigurationOptions);
            $activeAvailableOption = 'mysqliManualConfiguration';

            $this->view->assign('hasMysqliSocketManualConfiguration', true);
            $this->view->assign(
                'mysqliSocketManualConfigurationOptions',
                [
                    'username' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['user'] ?? '',
                    'password' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['password'] ?? '',
                    'socket' => $this->getConfiguredMysqliSocket(),
                ]
            );
            if ($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['driver'] === 'mysqli'
                && $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['host'] === 'localhost') {
                $activeAvailableOption = 'mysqliSocketManualConfiguration';
            }
        }
        if (extension_loaded('pdo_pgsql')) {
            $hasAtLeastOneOption = true;
            $this->view->assign('hasPostgresManualConfiguration', true);
            $this->view->assign(
                'postgresManualConfigurationOptions',
                [
                    'username' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['user'] ?? '',
                    'password' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['password'] ?? '',
                    'host' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['host'] ?? '127.0.0.1',
                    'port' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['port'] ?? 5432,
                    'database' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['dbname'] ?? '',
                ]
            );
            if ($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['driver'] === 'pdo_pgsql') {
                $activeAvailableOption = 'postgresManualConfiguration';
            }
        }

        if (!empty($this->getConfigurationFromEnvironment())) {
            $hasAtLeastOneOption = true;
            $activeAvailableOption = 'configurationFromEnvironment';
            $this->view->assign('hasConfigurationFromEnvironment', true);
        }

        $this->view->assign('hasAtLeastOneOption', $hasAtLeastOneOption);
        $this->view->assign('activeAvailableOption', $activeAvailableOption);

        $this->assignSteps();

        return $this->view->render();
    }

    /**
     * Test connection with given credentials
     *
     * @return bool true if connect was successful
     */
    protected function isConnectSuccessful()
    {
        return empty($this->isConnectSuccessfulWithExceptionMessage());
    }

    /**
     * Test connection with given credentials and return exception message if exception trown
     *
     * @return string
     */
    protected function isConnectSuccessfulWithExceptionMessage(): string
    {
        try {
            GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionByName('Default')->ping();
        } catch (DBALException $e) {
            return $e->getMessage();
        }
        return '';
    }

    /**
     * Check LocalConfiguration.php for required database settings:
     * - 'username' and 'password' are mandatory, but may be empty
     *
     * @return bool TRUE if required settings are present
     */
    protected function isConfigurationComplete()
    {
        $configurationComplete = true;
        if (!isset($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['user'])) {
            $configurationComplete = false;
        }
        if (!isset($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['password'])) {
            $configurationComplete = false;
        }
        return $configurationComplete;
    }

    /**
     * Returns configured socket, if set.
     *
     * @return string
     */
    protected function getConfiguredMysqliSocket()
    {
        $socket = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['unix_socket'] ?? '';
        if ($socket === '') {
            // If no configured socket, use default php socket
            $defaultSocket = (string)ini_get('mysqli.default_socket');
            if ($defaultSocket !== '') {
                $socket = $defaultSocket;
            }
        }
        return $socket;
    }

    /**
     * Try to fetch db credentials from a .env file and see if connect works
     *
     * @return array Empty array if no file is found or connect is not successful, else working credentials
     */
    protected function getConfigurationFromEnvironment(): array
    {
        $envCredentials = [];
        foreach (['driver', 'host', 'user', 'password', 'port', 'dbname', 'unix_socket'] as $value) {
            $envVar = 'TYPO3_INSTALL_DB_' . strtoupper($value);
            if (getenv($envVar) !== false) {
                $envCredentials[$value] = getenv($envVar);
            }
        }
        if (!empty($envCredentials)) {
            $connectionParams = $envCredentials;
            $connectionParams['wrapperClass'] = Connection::class;
            $connectionParams['charset'] = 'utf-8';
            try {
                DriverManager::getConnection($connectionParams)->ping();
                return $envCredentials;
            } catch (DBALException $e) {
                return [];
            }
        }
        return [];
    }
}
