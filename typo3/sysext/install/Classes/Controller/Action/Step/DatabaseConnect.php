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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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

        /** @var $configurationManager \TYPO3\CMS\Core\Configuration\ConfigurationManager */
        $configurationManager = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ConfigurationManager::class);

        $postValues = $this->postValues['values'];

        $localConfigurationPathValuePairs = [];

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
            if (!$this->isConnectSuccessful()) {
                /** @var $errorStatus \TYPO3\CMS\Install\Status\ErrorStatus */
                $errorStatus = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\ErrorStatus::class);
                $errorStatus->setTitle('Database connect not successful');
                $errorStatus->setMessage('Connecting to the database with given settings failed. Please check.');
                $result[] = $errorStatus;
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
        if (!$this->isHostConfigured()) {
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
        $this->view
            ->assign('username', $this->getConfiguredUsername())
            ->assign('password', $this->getConfiguredPassword())
            ->assign('host', $this->getConfiguredHost())
            ->assign('port', $this->getConfiguredOrDefaultPort())
            ->assign('database', $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['dbname'] ?: '')
            ->assign('socket', $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['unix_socket'] ?: '')
            ->assign('renderConnectDetailsUsername', true)
            ->assign('renderConnectDetailsPassword', true)
            ->assign('renderConnectDetailsHost', true)
            ->assign('renderConnectDetailsPort', true)
            ->assign('renderConnectDetailsSocket', true);

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
            $port = 3306;
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
        try {
            GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionByName('Default')->ping();
        } catch (DBALException $e) {
            return false;
        }
        return true;
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
        $localConfigurationPathValuePairs = [];

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
