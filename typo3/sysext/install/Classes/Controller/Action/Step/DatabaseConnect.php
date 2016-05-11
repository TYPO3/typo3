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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Database connect step:
 * - Needs execution if database credentials are not set or fail to connect
 * - Renders fields for database connection fields
 * - Sets database credentials in LocalConfiguration
 * - Loads / unloads ext:dbal and ext:adodb if requested
 */
class DatabaseConnect extends AbstractStepAction
{
    /**
     * Execute database step:
     * - Load / unload dbal & adodb
     * - Set database connect credentials in LocalConfiguration
     *
     * @return array<\TYPO3\CMS\Install\Status\StatusInterface>
     */
    public function execute()
    {
        $result = array();

        /** @var $configurationManager \TYPO3\CMS\Core\Configuration\ConfigurationManager */
        $configurationManager = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ConfigurationManager::class);

        $postValues = $this->postValues['values'];
        if (isset($postValues['loadDbal'])) {
            $result[] = $this->executeLoadDbalExtension();
        } elseif ($postValues['unloadDbal']) {
            $result[] = $this->executeUnloadDbalExtension();
        } elseif ($postValues['setDbalDriver']) {
            $driver = $postValues['setDbalDriver'];
            switch ($driver) {
                case 'mssql':
                case 'odbc_mssql':
                    $driverConfig = array(
                        'useNameQuote' => true,
                        'quoteClob' => false,
                    );
                    break;
                case 'oci8':
                    $driverConfig = array(
                        'driverOptions' => array(
                            'connectSID' => '',
                        ),
                    );
                    break;
            }
            $config = array(
                '_DEFAULT' => array(
                    'type' => 'adodb',
                    'config' => array(
                        'driver' => $driver,
                    )
                )
            );
            if (isset($driverConfig)) {
                $config['_DEFAULT']['config'] = array_merge($config['_DEFAULT']['config'], $driverConfig);
            }
            $configurationManager->setLocalConfigurationValueByPath('EXTCONF/dbal/handlerCfg', $config);
        } else {
            $localConfigurationPathValuePairs = array();

            if ($this->isDbalEnabled()) {
                $config = $configurationManager->getConfigurationValueByPath('EXTCONF/dbal/handlerCfg');
                $driver = $config['_DEFAULT']['config']['driver'];
                if ($driver === 'oci8') {
                    $config['_DEFAULT']['config']['driverOptions']['connectSID'] = ($postValues['type'] === 'sid');
                    $localConfigurationPathValuePairs['EXTCONF/dbal/handlerCfg'] = $config;
                }
            }

            if (isset($postValues['username'])) {
                $value = $postValues['username'];
                if (strlen($value) <= 50) {
                    $localConfigurationPathValuePairs['DB/Connections/Default/user'] = $value;
                } else {
                    /** @var $errorStatus \TYPO3\CMS\Install\Status\ErrorStatus */
                    $errorStatus = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\ErrorStatus::class);
                    $errorStatus->setTitle('Database username not valid');
                    $errorStatus->setMessage('Given username must be shorter than fifty characters.');
                    $result[] = $errorStatus;
                }
            }

            if (isset($postValues['password'])) {
                $value = $postValues['password'];
                if (strlen($value) <= 50) {
                    $localConfigurationPathValuePairs['DB/Connections/Default/password'] = $value;
                } else {
                    /** @var $errorStatus \TYPO3\CMS\Install\Status\ErrorStatus */
                    $errorStatus = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\ErrorStatus::class);
                    $errorStatus->setTitle('Database password not valid');
                    $errorStatus->setMessage('Given password must be shorter than fifty characters.');
                    $result[] = $errorStatus;
                }
            }

            if (isset($postValues['host'])) {
                $value = $postValues['host'];
                if (preg_match('/^[a-zA-Z0-9_\\.-]+(:.+)?$/', $value) && strlen($value) <= 255) {
                    $localConfigurationPathValuePairs['DB/Connections/Default/host'] = $value;
                } else {
                    /** @var $errorStatus \TYPO3\CMS\Install\Status\ErrorStatus */
                    $errorStatus = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\ErrorStatus::class);
                    $errorStatus->setTitle('Database host not valid');
                    $errorStatus->setMessage('Given host is not alphanumeric (a-z, A-Z, 0-9 or _-.:) or longer than 255 characters.');
                    $result[] = $errorStatus;
                }
            }

            if (isset($postValues['port']) && $postValues['host'] !== 'localhost') {
                $value = $postValues['port'];
                if (preg_match('/^[0-9]+(:.+)?$/', $value) && $value > 0 && $value <= 65535) {
                    $localConfigurationPathValuePairs['DB/Connections/Default/port'] = (int)$value;
                } else {
                    /** @var $errorStatus \TYPO3\CMS\Install\Status\ErrorStatus */
                    $errorStatus = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\ErrorStatus::class);
                    $errorStatus->setTitle('Database port not valid');
                    $errorStatus->setMessage('Given port is not numeric or within range 1 to 65535.');
                    $result[] = $errorStatus;
                }
            }

            if (isset($postValues['socket']) && $postValues['socket'] !== '') {
                if (@file_exists($postValues['socket'])) {
                    $localConfigurationPathValuePairs['DB/Connections/Default/unix_socket'] = $postValues['socket'];
                } else {
                    /** @var $errorStatus \TYPO3\CMS\Install\Status\ErrorStatus */
                    $errorStatus = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\ErrorStatus::class);
                    $errorStatus->setTitle('Socket does not exist');
                    $errorStatus->setMessage('Given socket location does not exist on server.');
                    $result[] = $errorStatus;
                }
            }

            if (isset($postValues['database'])) {
                $value = $postValues['database'];
                if (strlen($value) <= 50) {
                    $localConfigurationPathValuePairs['DB/Connections/Default/dbname'] = $value;
                } else {
                    /** @var $errorStatus \TYPO3\CMS\Install\Status\ErrorStatus */
                    $errorStatus = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\ErrorStatus::class);
                    $errorStatus->setTitle('Database name not valid');
                    $errorStatus->setMessage('Given database name must be shorter than fifty characters.');
                    $result[] = $errorStatus;
                }
            }

            if (!empty($localConfigurationPathValuePairs)) {
                $configurationManager->setLocalConfigurationValuesByPathValuePairs($localConfigurationPathValuePairs);

                // After setting new credentials, test again and create an error message if connect is not successful
                // @TODO: This could be simplified, if isConnectSuccessful could be released from TYPO3_CONF_VARS
                // and fed with connect values directly in order to obsolete the bootstrap reload.
                \TYPO3\CMS\Core\Core\Bootstrap::getInstance()
                    ->populateLocalConfiguration()
                    ->disableCoreCache();
                if ($this->isDbalEnabled()) {
                    require(ExtensionManagementUtility::extPath('dbal') . 'ext_localconf.php');
                    GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->setCacheConfigurations($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);
                }
                if (!$this->isConnectSuccessful()) {
                    /** @var $errorStatus \TYPO3\CMS\Install\Status\ErrorStatus */
                    $errorStatus = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\ErrorStatus::class);
                    $errorStatus->setTitle('Database connect not successful');
                    $errorStatus->setMessage('Connecting to the database with given settings failed. Please check.');
                    $result[] = $errorStatus;
                }
            }
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
        if (!$this->isHostConfigured() && !$this->isDbalEnabled()) {
            $this->useDefaultValuesForNotConfiguredOptions();
            throw new \TYPO3\CMS\Install\Controller\Exception\RedirectException(
                'Wrote default settings to LocalConfiguration.php, redirect needed',
                1377611168
            );
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
        $isDbalEnabled = $this->isDbalEnabled();
        $this->view
            ->assign('isDbalEnabled', $isDbalEnabled)
            ->assign('username', $this->getConfiguredUsername())
            ->assign('password', $this->getConfiguredPassword())
            ->assign('host', $this->getConfiguredHost())
            ->assign('port', $this->getConfiguredOrDefaultPort())
            ->assign('database', $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['dbname'] ?: '')
            ->assign('socket', $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['unix_socket'] ?: '');

        if ($isDbalEnabled) {
            $this->view->assign('selectedDbalDriver', $this->getSelectedDbalDriver());
            $this->view->assign('dbalDrivers', $this->getAvailableDbalDrivers());
            $this->setDbalInputFieldsToRender();
        } else {
            $this->view
                ->assign('renderConnectDetailsUsername', true)
                ->assign('renderConnectDetailsPassword', true)
                ->assign('renderConnectDetailsHost', true)
                ->assign('renderConnectDetailsPort', true)
                ->assign('renderConnectDetailsSocket', true);
        }
        $this->assignSteps();

        return $this->view->render();
    }

    /**
     * Render connect port and label
     *
     * @return int Configured or default port
     */
    protected function getConfiguredOrDefaultPort()
    {
        $configuredPort = (int)$this->getConfiguredPort();
        if (!$configuredPort) {
            if ($this->isDbalEnabled()) {
                $driver = $this->getSelectedDbalDriver();
                switch ($driver) {
                    case 'postgres':
                        $port = 5432;
                        break;
                    case 'mssql':
                    case 'odbc_mssql':
                        $port = 1433;
                        break;
                    case 'oci8':
                        $port = 1521;
                        break;
                    default:
                        $port = 3306;
                }
            } else {
                $port = 3306;
            }
        } else {
            $port = $configuredPort;
        }
        return $port;
    }

    /**
     * Test connection with given credentials
     *
     * @return bool TRUE if connect was successful
     */
    protected function isConnectSuccessful()
    {
        /** @var $databaseConnection \TYPO3\CMS\Core\Database\DatabaseConnection */
        $databaseConnection = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\DatabaseConnection::class);

        if ($this->isDbalEnabled()) {
            // Set additional connect information based on dbal driver. postgres for example needs
            // database name already for connect.
            if (isset($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['dbname'])) {
                $databaseConnection->setDatabaseName($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['dbname']);
            }
        }

        $databaseConnection->setDatabaseUsername($this->getConfiguredUsername());
        $databaseConnection->setDatabasePassword($this->getConfiguredPassword());
        $databaseConnection->setDatabaseHost($this->getConfiguredHost());
        $databaseConnection->setDatabasePort($this->getConfiguredPort());
        $databaseConnection->setDatabaseSocket($this->getConfiguredSocket());

        $databaseConnection->initialize();

        return (bool)@$databaseConnection->sql_pconnect();
    }

    /**
     * Check LocalConfiguration.php for required database settings:
     * - 'host' is mandatory and must not be empty
     * - 'port' OR 'socket' is mandatory, but may be empty
     *
     * @return bool TRUE if host is set
     */
    protected function isHostConfigured()
    {
        $hostConfigured = true;
        if (empty($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['host'])) {
            $hostConfigured = false;
        }
        if (!isset($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['port'])
            && !isset($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['unix_socket'])
        ) {
            $hostConfigured = false;
        }
        return $hostConfigured;
    }

    /**
     * Check LocalConfiguration.php for required database settings:
     * - 'host' is mandatory and must not be empty
     * - 'port' OR 'socket' is mandatory, but may be empty
     * - 'username' and 'password' are mandatory, but may be empty
     *
     * @return bool TRUE if required settings are present
     */
    protected function isConfigurationComplete()
    {
        $configurationComplete = $this->isHostConfigured();
        if (!isset($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['user'])) {
            $configurationComplete = false;
        }
        if (!isset($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['password'])) {
            $configurationComplete = false;
        }
        return $configurationComplete;
    }

    /**
     * Write DB settings to LocalConfiguration.php, using default values.
     * With the switch from mysql to mysqli in 6.1, some mandatory settings were
     * added. This method tries to add those settings in case of an upgrade, and
     * pre-configures settings in case of a "new" install process.
     *
     * There are two different connection types:
     * - Unix domain socket. This may be available if mysql is running on localhost
     * - TCP/IP connection to some mysql system somewhere.
     *
     * Unix domain socket connections are quicker than TCP/IP, so it is
     * tested if a unix domain socket connection to localhost is successful. If not,
     * a default configuration for TCP/IP is used.
     *
     * @return void
     */
    protected function useDefaultValuesForNotConfiguredOptions()
    {
        $localConfigurationPathValuePairs = array();

        $localConfigurationPathValuePairs['DB/Connections/Default/host'] = $this->getConfiguredHost();

        // If host is "local" either by upgrading or by first install, we try a socket
        // connection first and use TCP/IP as fallback
        if ($localConfigurationPathValuePairs['DB/Connections/Default/host'] === 'localhost'
            || GeneralUtility::cmpIP($localConfigurationPathValuePairs['DB/Connections/Default/host'], '127.*.*.*')
            || (string)$localConfigurationPathValuePairs['DB/Connections/Default/host'] === ''
        ) {
            if ($this->isConnectionWithUnixDomainSocketPossible()) {
                $localConfigurationPathValuePairs['DB/Connections/Default/host'] = 'localhost';
                $localConfigurationPathValuePairs['DB/Connections/Default/unix_socket'] = $this->getConfiguredSocket();
            } else {
                if (!GeneralUtility::isFirstPartOfStr($localConfigurationPathValuePairs['DB/Connections/Default/host'], '127.')) {
                    $localConfigurationPathValuePairs['DB/Connections/Default/host'] = '127.0.0.1';
                }
            }
        }

        if (!isset($localConfigurationPathValuePairs['DB/Connections/Default/unix_socket'])) {
            // Make sure a default port is set if not configured yet
            // This is independent from any host configuration
            $port = $this->getConfiguredPort();
            if ($port > 0) {
                $localConfigurationPathValuePairs['DB/Connections/Default/port'] = $port;
            } else {
                $localConfigurationPathValuePairs['DB/Connections/Default/port'] = $this->getConfiguredOrDefaultPort();
            }
        }

        /** @var \TYPO3\CMS\Core\Configuration\ConfigurationManager $configurationManager */
        $configurationManager = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ConfigurationManager::class);
        $configurationManager->setLocalConfigurationValuesByPathValuePairs($localConfigurationPathValuePairs);
    }

    /**
     * Test if a unix domain socket can be opened. This does not
     * authenticate but only tests if a connect is successful.
     *
     * @return bool TRUE on success
     */
    protected function isConnectionWithUnixDomainSocketPossible()
    {
        $result = false;
        // Use configured socket
        $socket = (string)$this->getConfiguredSocket();
        if ($socket === '') {
            // If no configured socket, use default php socket
            $defaultSocket = (string)ini_get('mysqli.default_socket');
            if ($defaultSocket !== '') {
                $socket = $defaultSocket;
            }
        }
        if ($socket !== '') {
            $socketOpenResult = @fsockopen('unix://' . $socket);
            if ($socketOpenResult) {
                fclose($socketOpenResult);
                $result = true;
            }
        }
        return $result;
    }

    /**
     * Render fields required for successful connect based on dbal driver selection.
     * Hint: There is a code duplication in handle() and this method. This
     * is done by intention to keep this code area easy to maintain and understand.
     *
     * @return void
     */
    protected function setDbalInputFieldsToRender()
    {
        $driver = $this->getSelectedDbalDriver();
        switch ($driver) {
            case 'mssql':
            case 'odbc_mssql':
            case 'postgres':
                $this->view
                    ->assign('renderConnectDetailsUsername', true)
                    ->assign('renderConnectDetailsPassword', true)
                    ->assign('renderConnectDetailsHost', true)
                    ->assign('renderConnectDetailsPort', true)
                    ->assign('renderConnectDetailsDatabase', true);
                break;
            case 'oci8':
                $this->view
                    ->assign('renderConnectDetailsUsername', true)
                    ->assign('renderConnectDetailsPassword', true)
                    ->assign('renderConnectDetailsHost', true)
                    ->assign('renderConnectDetailsPort', true)
                    ->assign('renderConnectDetailsDatabase', true)
                    ->assign('renderConnectDetailsOracleSidConnect', true);
                $type = isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dbal']['handlerCfg']['_DEFAULT']['config']['driverOptions']['connectSID'])
                    ? $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dbal']['handlerCfg']['_DEFAULT']['config']['driverOptions']['connectSID']
                    : '';
                if ($type === true) {
                    $this->view->assign('oracleSidSelected', true);
                }
                break;
        }
    }

    /**
     * Returns a list of database drivers that are available on current server.
     *
     * @return array
     */
    protected function getAvailableDbalDrivers()
    {
        $supportedDrivers = $this->getSupportedDbalDrivers();
        $availableDrivers = array();
        $selectedDbalDriver = $this->getSelectedDbalDriver();
        foreach ($supportedDrivers as $abstractionLayer => $drivers) {
            foreach ($drivers as $driver => $info) {
                if (isset($info['combine']) && $info['combine'] === 'OR') {
                    $isAvailable = false;
                } else {
                    $isAvailable = true;
                }
                // Loop through each PHP module dependency to ensure it is loaded
                foreach ($info['extensions'] as $extension) {
                    if (isset($info['combine']) && $info['combine'] === 'OR') {
                        $isAvailable |= extension_loaded($extension);
                    } else {
                        $isAvailable &= extension_loaded($extension);
                    }
                }
                if ($isAvailable) {
                    if (!isset($availableDrivers[$abstractionLayer])) {
                        $availableDrivers[$abstractionLayer] = array();
                    }
                    $availableDrivers[$abstractionLayer][$driver] = array();
                    $availableDrivers[$abstractionLayer][$driver]['driver'] = $driver;
                    $availableDrivers[$abstractionLayer][$driver]['label'] = $info['label'];
                    $availableDrivers[$abstractionLayer][$driver]['selected'] = false;
                    if ($selectedDbalDriver === $driver) {
                        $availableDrivers[$abstractionLayer][$driver]['selected'] = true;
                    }
                }
            }
        }
        return $availableDrivers;
    }

    /**
     * Returns a list of DBAL supported database drivers, with a
     * user-friendly name and any PHP module dependency.
     *
     * @return array
     */
    protected function getSupportedDbalDrivers()
    {
        $supportedDrivers = array(
            'Native' => array(
                'mssql' => array(
                    'label' => 'Microsoft SQL Server',
                    'extensions' => array('mssql')
                ),
                'oci8' => array(
                    'label' => 'Oracle OCI8',
                    'extensions' => array('oci8')
                ),
                'postgres' => array(
                    'label' => 'PostgreSQL',
                    'extensions' => array('pgsql')
                )
            ),
            'ODBC' => array(
                'odbc_mssql' => array(
                    'label' => 'Microsoft SQL Server',
                    'extensions' => array('odbc', 'mssql')
                )
            )
        );
        return $supportedDrivers;
    }

    /**
     * Get selected dbal driver if any
     *
     * @return string Dbal driver or empty string if not yet selected
     */
    protected function getSelectedDbalDriver()
    {
        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dbal']['handlerCfg']['_DEFAULT']['config']['driver'])) {
            return $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dbal']['handlerCfg']['_DEFAULT']['config']['driver'];
        }
        return '';
    }

    /**
     * Adds dbal and adodb to list of loaded extensions
     *
     * @return \TYPO3\CMS\Install\Status\StatusInterface
     */
    protected function executeLoadDbalExtension()
    {
        if (!ExtensionManagementUtility::isLoaded('adodb')) {
            ExtensionManagementUtility::loadExtension('adodb');
        }
        if (!ExtensionManagementUtility::isLoaded('dbal')) {
            ExtensionManagementUtility::loadExtension('dbal');
        }
        /** @var $errorStatus \TYPO3\CMS\Install\Status\WarningStatus */
        $warningStatus = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\WarningStatus::class);
        $warningStatus->setTitle('Loaded database abstraction layer');
        return $warningStatus;
    }

    /**
     * Remove dbal and adodb from list of loaded extensions
     *
     * @return \TYPO3\CMS\Install\Status\StatusInterface
     */
    protected function executeUnloadDbalExtension()
    {
        if (ExtensionManagementUtility::isLoaded('adodb')) {
            ExtensionManagementUtility::unloadExtension('adodb');
        }
        if (ExtensionManagementUtility::isLoaded('dbal')) {
            ExtensionManagementUtility::unloadExtension('dbal');
        }
        // @TODO: Remove configuration from TYPO3_CONF_VARS['EXTCONF']['dbal']
        /** @var $errorStatus \TYPO3\CMS\Install\Status\WarningStatus */
        $warningStatus = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\WarningStatus::class);
        $warningStatus->setTitle('Removed database abstraction layer');
        return $warningStatus;
    }

    /**
     * Returns configured username, if set
     *
     * @return string
     */
    protected function getConfiguredUsername()
    {
        $username = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['user'] ?? '';
        return $username;
    }

    /**
     * Returns configured password, if set
     *
     * @return string
     */
    protected function getConfiguredPassword()
    {
        $password = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['password'] ?? '';
        return $password;
    }

    /**
     * Returns configured host with port split off if given
     *
     * @return string
     */
    protected function getConfiguredHost()
    {
        $host = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['host'] ?? '';
        $port = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['port'] ?? '';
        if (strlen($port) < 1 && substr_count($host, ':') === 1) {
            list($host) = explode(':', $host);
        }
        return $host;
    }

    /**
     * Returns configured port. Gets port from host value if port is not yet set.
     *
     * @return int
     */
    protected function getConfiguredPort()
    {
        $host = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['host'] ?? '';
        $port = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['port'] ?? '';
        if ($port === '' && substr_count($host, ':') === 1) {
            $hostPortArray = explode(':', $host);
            $port = $hostPortArray[1];
        }
        return (int)$port;
    }

    /**
     * Returns configured socket, if set
     *
     * @return string|NULL
     */
    protected function getConfiguredSocket()
    {
        $socket = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['unix_socket'] ?? '';
        return $socket;
    }
}
