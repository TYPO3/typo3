<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Install\Service;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Exception\ConnectionException;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Platform\PlatformInformation;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\PasswordPolicy\PasswordPolicyAction;
use TYPO3\CMS\Core\PasswordPolicy\PasswordPolicyValidator;
use TYPO3\CMS\Core\PasswordPolicy\Validator\Dto\ContextData;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Configuration\Exception;
use TYPO3\CMS\Install\Database\PermissionsCheck;
use TYPO3\CMS\Install\SystemEnvironment\DatabaseCheck;
use TYPO3\CMS\Install\Updates\DatabaseRowsUpdateWizard;

/**
 * Service class helping to manage database related settings and operations required to set up TYPO3
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 *
 * @phpstan-import-type Params from DriverManager
 */
class SetupDatabaseService
{
    protected array $validDrivers = [
        'mysqli',
        'pdo_mysql',
        'pdo_pgsql',
        'pdo_sqlite',
    ];

    public function __construct(
        private readonly LateBootService $lateBootService,
        private readonly ConfigurationManager $configurationManager,
        private readonly PermissionsCheck $databasePermissionsCheck,
        private readonly Registry $registry,
    ) {}

    /**
     * @param array $values
     * @return array
     */
    public function setDefaultConnectionSettings(array $values): array
    {
        $messages = [];
        if (($values['availableSet'] ?? '') === 'configurationFromEnvironment') {
            $defaultConnectionSettings = $this->getDatabaseConfigurationFromEnvironment();
        } else {
            if (isset($values['driver'])) {
                if (in_array($values['driver'], $this->validDrivers, true)) {
                    $defaultConnectionSettings['driver'] = $values['driver'];
                } else {
                    $messages[] = new FlashMessage(
                        'Given driver must be one of ' . implode(', ', $this->validDrivers),
                        'Database driver unknown',
                        ContextualFeedbackSeverity::ERROR
                    );
                }
            }
            if (isset($values['username'])) {
                $value = $values['username'];
                if (strlen($value) <= 50) {
                    $defaultConnectionSettings['user'] = $value;
                } else {
                    $messages[] = new FlashMessage(
                        'Given username must be shorter than fifty characters.',
                        'Database username not valid',
                        ContextualFeedbackSeverity::ERROR
                    );
                }
            }
            if (isset($values['password'])) {
                $defaultConnectionSettings['password'] = $values['password'];
            }
            if (isset($values['host'])) {
                $value = $values['host'];
                if ($this->isValidDbHost($value)) {
                    $defaultConnectionSettings['host'] = $value;
                } else {
                    $messages[] = new FlashMessage(
                        'Given host is not alphanumeric (a-z, A-Z, 0-9 or _-.:) or longer than 255 characters.',
                        'Database host not valid',
                        ContextualFeedbackSeverity::ERROR
                    );
                }
            }
            if (isset($values['port']) && $values['host'] !== 'localhost') {
                $value = (int)$values['port'];
                if ($this->isValidDbPort($value)) {
                    $defaultConnectionSettings['port'] = (int)$value;
                } else {
                    $messages[] = new FlashMessage(
                        'Given port is not numeric or within range 1 to 65535.',
                        'Database port not valid',
                        ContextualFeedbackSeverity::ERROR
                    );
                }
            }
            if (isset($values['socket']) && $values['socket'] !== '') {
                if (@file_exists($values['socket'])) {
                    $defaultConnectionSettings['unix_socket'] = $values['socket'];
                } else {
                    $messages[] = new FlashMessage(
                        'Given socket location does not exist on server.',
                        'Socket does not exist',
                        ContextualFeedbackSeverity::ERROR
                    );
                }
            }
            if (isset($values['database'])) {
                $value = $values['database'];
                if ($this->isValidDbName($value)) {
                    $defaultConnectionSettings['dbname'] = $value;
                } else {
                    $messages[] = new FlashMessage(
                        'Given database name must be shorter than fifty characters.',
                        'Database name not valid',
                        ContextualFeedbackSeverity::ERROR
                    );
                }
            }
            // For sqlite a db path is automatically calculated
            if (isset($values['driver']) && $values['driver'] === 'pdo_sqlite') {
                $dbFilename = '/cms-' . (new Random())->generateRandomHexString(8) . '.sqlite';
                // If the var/ folder exists outside of document root, put it into var/sqlite/
                // Otherwise simply into typo3conf/
                if (Environment::getProjectPath() !== Environment::getPublicPath()) {
                    GeneralUtility::mkdir_deep(Environment::getVarPath() . '/sqlite');
                    $defaultConnectionSettings['path'] = Environment::getVarPath() . '/sqlite' . $dbFilename;
                } else {
                    $defaultConnectionSettings['path'] = Environment::getConfigPath() . $dbFilename;
                }
            }
            // For mysql, set utf8mb4 as default charset
            if (isset($values['driver']) && in_array($values['driver'], ['mysqli', 'pdo_mysql'])) {
                $defaultConnectionSettings['charset'] = 'utf8mb4';
                $defaultConnectionSettings['tableoptions'] = [
                    'charset' => 'utf8mb4',
                    'collate' => 'utf8mb4_unicode_ci',
                ];
            }
        }

        $success = false;
        if (!empty($defaultConnectionSettings)) {
            // Test connection settings and write to config if connect is successful
            try {
                $connectionParams = $defaultConnectionSettings;
                $connectionParams['wrapperClass'] = Connection::class;
                if (!isset($connectionParams['charset'])) {
                    // utf-8 as default for non mysql
                    $connectionParams['charset'] = 'utf-8';
                }
                $connection = DriverManager::getConnection($connectionParams);
                if ($connection->getWrappedConnection() !== null) {
                    $connection->executeQuery($connection->getDatabasePlatform()->getDummySelectSQL());
                    $success = true;
                }
            } catch (DBALException $e) {
                $messages[] = new FlashMessage(
                    'Connecting to the database with given settings failed: ' . $e->getMessage(),
                    'Database connect not successful',
                    ContextualFeedbackSeverity::ERROR
                );
            }
            $localConfigurationPathValuePairs = [];
            foreach ($defaultConnectionSettings as $settingsName => $value) {
                $localConfigurationPathValuePairs['DB/Connections/Default/' . $settingsName] = $value;
            }
            // Remove full default connection array
            $this->configurationManager->removeLocalConfigurationKeysByPath(['DB/Connections/Default']);
            // Write new values
            $this->configurationManager->setLocalConfigurationValuesByPathValuePairs($localConfigurationPathValuePairs);
        }

        return [$success, $messages];
    }

    /**
     * Try to fetch db credentials from a .env file and see if connect works
     *
     * @return array Empty array if no file is found or connect is not successful, else working credentials
     */
    public function getDatabaseConfigurationFromEnvironment(): array
    {
        /** @var Params $envCredentials */
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
                $connection = DriverManager::getConnection($connectionParams);
                if ($connection->getWrappedConnection() !== null) {
                    $connection->executeQuery($connection->getDatabasePlatform()->getDummySelectSQL());
                    return $envCredentials;
                }
            } catch (DBALException $e) {
                return [];
            }
        }
        return [];
    }

    public function isValidDbHost(string $name): bool
    {
        return preg_match('/^[a-zA-Z0-9_\\.-]+(:.+)?$/', $name) && strlen($name) <= 255;
    }

    public function isValidDbPort(int $number): bool
    {
        return preg_match('/^[0-9]+(:.+)?$/', (string)$number) && $number > 0 && $number <= 65535;
    }

    public function isValidDbName(string $name): bool
    {
        return strlen($name) <= 50;
    }

    public function getBackendUserPasswordValidationErrors(string $password): array
    {
        $passwordPolicy = $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordPolicy'] ?? 'default';
        $passwordPolicyValidator = GeneralUtility::makeInstance(
            PasswordPolicyValidator::class,
            PasswordPolicyAction::NEW_USER_PASSWORD,
            is_string($passwordPolicy) ? $passwordPolicy : ''
        );
        $contextData = new ContextData();
        $passwordPolicyValidator->isValidPassword($password, $contextData);

        return $passwordPolicyValidator->getValidationErrors();
    }

    /**
     * Returns list of available databases (with access-check based on username/password)
     *
     * @return array List of available databases
     * @throws DBALException
     */
    public function getDatabaseList(): array
    {
        $connectionParams = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME];
        unset($connectionParams['dbname']);

        // Establishing the connection using the Doctrine DriverManager directly
        // as we need a connection without selecting a database right away. Otherwise
        // an invalid database name would lead to exceptions which would prevent
        // changing the currently configured database.
        $connection = DriverManager::getConnection($connectionParams);
        $databaseArray = $connection->createSchemaManager()->listDatabases();
        $connection->close();

        // Remove organizational tables from database list
        $reservedDatabaseNames = ['mysql', 'information_schema', 'performance_schema'];
        $allPossibleDatabases = array_diff($databaseArray, $reservedDatabaseNames);

        // In first installation we show all databases but disable not empty ones (with tables)
        $databases = [];
        foreach ($allPossibleDatabases as $databaseName) {
            // Reestablishing the connection for each database since there is no
            // portable way to switch databases on the same Doctrine connection.
            // Directly using the Doctrine DriverManager here to avoid messing with
            // the $GLOBALS database configuration array.
            try {
                $connectionParams['dbname'] = $databaseName;
                $connection = DriverManager::getConnection($connectionParams);

                $databases[] = [
                    'name' => $databaseName,
                    'tables' => count($connection->createSchemaManager()->listTableNames()),
                    'readonly' => false,
                ];
                $connection->close();
            } catch (ConnectionException $exception) {
                $databases[] = [
                    'name' => $databaseName,
                    'tables' => 0,
                    'readonly' => true,
                ];
                // we ignore a connection exception here.
                // if this happens here, the show tables was successful
                // but the connection failed because of missing permissions.
            }
        }

        return $databases;
    }

    public function checkDatabaseSelect(): bool
    {
        $success = false;
        if ((string)($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['dbname'] ?? '') !== ''
            || (string)($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['path'] ?? '') !== ''
        ) {
            try {
                $connection = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
                if ($connection->getWrappedConnection() !== null) {
                    $connection->executeQuery($connection->getDatabasePlatform()->getDummySelectSQL());
                    $success = true;
                }
            } catch (DBALException $e) {
            }
        }

        return $success;
    }

    /**
     * @param string $name
     * @throws DBALException
     */
    public function createDatabase(string $name): void
    {
        $platform = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME)
            ->getDatabasePlatform();
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
        $connection->exec(
            PlatformInformation::getDatabaseCreateStatementWithCharset(
                $platform,
                $connection->quoteIdentifier($name)
            )
        );
        $this->configurationManager
            ->setLocalConfigurationValueByPath('DB/Connections/Default/dbname', $name);
    }

    /**
     * Test connection with given credentials and return exception message if exception thrown
     */
    public function isDatabaseConnectSuccessful(): bool
    {
        try {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
            if ($connection->getWrappedConnection() !== null) {
                $connection->executeQuery($connection->getDatabasePlatform()->getDummySelectSQL());
                return true;
            }
        } catch (DBALException $e) {
        }
        return false;
    }

    /**
     * Check system/settings.php for required database settings:
     * - 'username' and 'password' are mandatory, but may be empty
     * - if 'driver' is pdo_sqlite and 'path' is set, its ok, too
     *
     * @return bool TRUE if required settings are present
     */
    public function isDatabaseConfigurationComplete(): bool
    {
        $configurationComplete = true;
        if (!isset($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['user'])) {
            $configurationComplete = false;
        }
        if (!isset($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['password'])) {
            $configurationComplete = false;
        }
        if (isset($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['driver'])
            && $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['driver'] === 'pdo_sqlite'
            && !empty($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['path'])
        ) {
            $configurationComplete = true;
        }
        return $configurationComplete;
    }

    /**
     * Returns configured socket, if set.
     */
    public function getDatabaseConfiguredMysqliSocket(): string
    {
        return $this->getDefaultSocketFor('mysqli.default_socket');
    }

    /**
     * Returns configured socket, if set.
     */
    public function getDatabaseConfiguredPdoMysqlSocket(): string
    {
        return $this->getDefaultSocketFor('pdo_mysql.default_socket');
    }

    /**
     * Returns configured socket, if set.
     */
    private function getDefaultSocketFor(string $phpIniSetting): string
    {
        $socket = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['unix_socket'] ?? '';
        if ($socket === '') {
            // If no configured socket, use default php socket
            $defaultSocket = (string)ini_get($phpIniSetting);
            if ($defaultSocket !== '') {
                $socket = $defaultSocket;
            }
        }
        return $socket;
    }

    /**
     * Creates a new database on the default connection
     *
     * @param string $dbName name of database
     */
    public function createNewDatabase(string $dbName): FlashMessage
    {
        try {
            $this->createDatabase($dbName);
        } catch (DBALException $e) {
            return new FlashMessage(
                'Database with name "' . $dbName . '" could not be created.'
                . ' Either your database name contains a reserved keyword or your database'
                . ' user does not have sufficient permissions to create it or the database already exists.'
                . ' Please choose an existing (empty) database, choose another name or contact administration.',
                'Unable to create database',
                ContextualFeedbackSeverity::ERROR
            );
        }
        return new FlashMessage(
            '',
            'Database created'
        );
    }

    /**
     * Checks whether an existing database on the default connection
     * can be used for a TYPO3 installation. The database name is only
     * persisted to the local configuration if the database is empty.
     *
     * @param string $dbName name of the database
     */
    public function checkExistingDatabase(string $dbName): FlashMessage
    {
        $result = new FlashMessage('');
        $localConfigurationPathValuePairs = [];

        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['dbname'] = $dbName;
        try {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);

            if (!empty($connection->createSchemaManager()->listTableNames())) {
                $result = new FlashMessage(
                    sprintf('Cannot use database "%s"', $dbName)
                    . ', because it already contains tables. Please select a different database or choose to create one!',
                    'Selected database is not empty!',
                    ContextualFeedbackSeverity::ERROR
                );
            }
        } catch (\Exception $e) {
            $result = new FlashMessage(
                sprintf('Could not connect to database "%s"', $dbName)
                . '! Make sure it really exists and your database user has the permissions to select it!',
                'Could not connect to selected database!',
                ContextualFeedbackSeverity::ERROR
            );
        }

        if ($result->getSeverity() === ContextualFeedbackSeverity::OK) {
            $localConfigurationPathValuePairs['DB/Connections/Default/dbname'] = $dbName;

            $this->configurationManager->setLocalConfigurationValuesByPathValuePairs($localConfigurationPathValuePairs);
        }

        return $result;
    }

    /**
     * Create tables and import static rows
     *
     * @return FlashMessage[]
     */
    public function importDatabaseData(): array
    {
        // Will load ext_localconf and ext_tables. This is pretty safe here since we are
        // in first install (database empty), so it is very likely that no extension is loaded
        // that could trigger a fatal at this point.
        $container = $this->lateBootService->loadExtLocalconfDatabaseAndExtTables();

        $sqlReader = $container->get(SqlReader::class);
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

            $message = new FlashMessage(
                'Query:' . LF . ' ' . $statement . LF . 'Error:' . LF . ' ' . $message,
                'Database query failed!',
                ContextualFeedbackSeverity::ERROR
            );
        }
        return array_values($results);
    }

    public function checkRequiredDatabasePermissions(): array
    {
        try {
            return $this->databasePermissionsCheck
                ->checkCreateAndDrop()
                ->checkAlter()
                ->checkIndex()
                ->checkCreateTemporaryTable()
                ->checkInsert()
                ->checkSelect()
                ->checkUpdate()
                ->checkDelete()
                ->getMessages();
        } catch (Exception $exception) {
            return $this->databasePermissionsCheck->getMessages();
        }
    }

    public function checkDatabaseRequirementsForDriver(string $databaseDriverName): FlashMessageQueue
    {
        $databaseCheck = GeneralUtility::makeInstance(DatabaseCheck::class);
        try {
            $databaseDriverClassName = DatabaseCheck::retrieveDatabaseDriverClassByDriverName($databaseDriverName);

            $databaseCheck->checkDatabasePlatformRequirements($databaseDriverClassName);
            $databaseCheck->checkDatabaseDriverRequirements($databaseDriverClassName);

            return $databaseCheck->getMessageQueue();
        } catch (\TYPO3\CMS\Install\Exception $exception) {
            $flashMessageQueue = new FlashMessageQueue('database-check-requirements');
            $flashMessageQueue->enqueue(
                new FlashMessage(
                    '',
                    $exception->getMessage(),
                    ContextualFeedbackSeverity::INFO
                )
            );
            return $flashMessageQueue;
        }
    }

    public function getDriverOptions(): array
    {
        $hasAtLeastOneOption = false;
        $activeAvailableOption = '';

        $driverOptions = [];

        if (DatabaseCheck::isMysqli()) {
            $hasAtLeastOneOption = true;
            $driverOptions['hasMysqliManualConfiguration'] = true;
            $mysqliManualConfigurationOptions = [
                'driver' => 'mysqli',
                'username' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['user'] ?? '',
                'password' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['password'] ?? '',
                'port' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['port'] ?? 3306,
                'database' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['dbname'] ?? '',
            ];
            $host = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['host'] ?? '127.0.0.1';
            if ($host === 'localhost') {
                $host = '127.0.0.1';
            }
            $mysqliManualConfigurationOptions['host'] = $host;
            $driverOptions['mysqliManualConfigurationOptions'] = $mysqliManualConfigurationOptions;
            $activeAvailableOption = 'mysqliManualConfiguration';

            $driverOptions['hasMysqliSocketManualConfiguration'] = true;
            $driverOptions['mysqliSocketManualConfigurationOptions'] = [
                'driver' => 'mysqli',
                'username' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['user'] ?? '',
                'password' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['password'] ?? '',
                'socket' => $this->getDatabaseConfiguredMysqliSocket(),
                'database' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['dbname'] ?? '',
            ];
            if (($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['driver'] ?? '') === 'mysqli'
                && ($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['host'] ?? '') === 'localhost') {
                $activeAvailableOption = 'mysqliSocketManualConfiguration';
            }
        }

        if (DatabaseCheck::isPdoMysql()) {
            $hasAtLeastOneOption = true;
            $driverOptions['hasPdoMysqlManualConfiguration'] = true;
            $pdoMysqlManualConfigurationOptions = [
                'driver' => 'pdo_mysql',
                'username' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['user'] ?? '',
                'password' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['password'] ?? '',
                'port' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['port'] ?? 3306,
                'database' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['dbname'] ?? '',
            ];
            $host = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['host'] ?? '127.0.0.1';
            if ($host === 'localhost') {
                $host = '127.0.0.1';
            }
            $pdoMysqlManualConfigurationOptions['host'] = $host;
            $driverOptions['pdoMysqlManualConfigurationOptions'] = $pdoMysqlManualConfigurationOptions;

            // preselect PDO MySQL only if mysqli is not present
            if (!DatabaseCheck::isMysqli()) {
                $activeAvailableOption = 'pdoMysqlManualConfiguration';
            }

            $driverOptions['hasPdoMysqlSocketManualConfiguration'] = true;
            $driverOptions['pdoMysqlSocketManualConfigurationOptions'] = [
                'driver' => 'pdo_mysql',
                'username' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['user'] ?? '',
                'password' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['password'] ?? '',
                'socket' => $this->getDatabaseConfiguredPdoMysqlSocket(),
                'database' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['dbname'] ?? '',
            ];
            if (($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['driver'] ?? '') === 'pdo_mysql'
                && $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['host'] === 'localhost') {
                $activeAvailableOption = 'pdoMysqlSocketManualConfiguration';
            }
        }

        if (DatabaseCheck::isPdoPgsql()) {
            $hasAtLeastOneOption = true;
            $driverOptions['hasPostgresManualConfiguration'] = true;
            $driverOptions['postgresManualConfigurationOptions'] = [
                'driver' => 'pdo_pgsql',
                'username' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['user'] ?? '',
                'password' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['password'] ?? '',
                'host' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['host'] ?? '127.0.0.1',
                'port' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['port'] ?? 5432,
                'database' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['dbname'] ?? '',
            ];
            if (($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['driver'] ?? '') === 'pdo_pgsql') {
                $activeAvailableOption = 'postgresManualConfiguration';
            }
        }
        if (DatabaseCheck::isPdoSqlite()) {
            $hasAtLeastOneOption = true;
            $driverOptions['hasSqliteManualConfiguration'] = true;
            $driverOptions['sqliteManualConfigurationOptions'] = [
                'driver' => 'pdo_sqlite',
            ];
            if (($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['driver'] ?? '') === 'pdo_sqlite') {
                $activeAvailableOption = 'sqliteManualConfiguration';
            }
        }

        if (!empty($this->getDatabaseConfigurationFromEnvironment())) {
            $hasAtLeastOneOption = true;
            $activeAvailableOption = 'configurationFromEnvironment';
            $driverOptions['hasConfigurationFromEnvironment'] = true;
        }

        return array_merge($driverOptions, [
            'hasAtLeastOneOption' => $hasAtLeastOneOption,
            'activeAvailableOption' => $activeAvailableOption,
        ]);
    }

    public function markWizardsDone(ContainerInterface $container): void
    {
        foreach ($container->get(UpgradeWizardsService::class)->getNonRepeatableUpgradeWizards() as $className) {
            $this->registry->set('installUpdate', $className, 1);
        }
        $this->registry->set('installUpdateRows', 'rowUpdatersDone', GeneralUtility::makeInstance(DatabaseRowsUpdateWizard::class)->getAvailableRowUpdater());
    }
}
