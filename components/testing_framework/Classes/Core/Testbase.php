<?php
namespace TYPO3\TestingFramework\Core;

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
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This is a helper class used by unit, functional and acceptance test
 * environment builders.
 * It contains methods to create test environments.
 *
 * This class is for internal use only and may change wihtout further notice.
 *
 * Use the classes "UnitTestCase", "FunctionalTestCase" or "AcceptanceCoreEnvironment"
 * to indirectly benefit from this class in own extensions.
 */
class Testbase
{
    /**
     * This class must be called in CLI environment as a security measure
     * against path disclosures and other stuff. Check this within
     * constructor to make sure this check can't be circumvented.
     */
    public function __construct()
    {
        // Ensure cli only as security measure
        if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
            die('This script supports command line usage only. Please check your command.');
        }
    }

    /**
     * Makes sure error messages during the tests get displayed no matter what is set in php.ini.
     */
    public function enableDisplayErrors()
    {
        @ini_set('display_errors', 1);
    }

    /**
     * Defines a list of basic constants that are used by GeneralUtility and other
     * helpers during tests setup. Those are sanitized in SystemEnvironmentBuilder
     * to be not defined again.
     *
     * @see SystemEnvironmentBuilder::defineBaseConstants()
     */
    public function defineBaseConstants()
    {
        // A null, a tabulator, a linefeed, a carriage return, a substitution, a CR-LF combination
        defined('NUL') ?: define('NUL', chr(0));
        defined('TAB') ?: define('TAB', chr(9));
        defined('LF') ?: define('LF', chr(10));
        defined('CR') ?: define('CR', chr(13));
        defined('SUB') ?: define('SUB', chr(26));
        defined('CRLF') ?: define('CRLF', CR . LF);

        if (!defined('TYPO3_OS')) {
            // Operating system identifier
            // Either "WIN" or empty string
            $typoOs = '';
            if (!stristr(PHP_OS, 'darwin') && !stristr(PHP_OS, 'cygwin') && stristr(PHP_OS, 'win')) {
                $typoOs = 'WIN';
            }
            define('TYPO3_OS', $typoOs);
        }
    }

    /**
     * Defines the PATH_site and PATH_thisScript constant and sets $_SERVER['SCRIPT_NAME'].
     * For unit tests only
     */
    public function defineSitePath()
    {
        define('PATH_site', $this->getWebRoot());
        define('PATH_thisScript', PATH_site . 'typo3/cli_dispatch.phpsh');
        $_SERVER['SCRIPT_NAME'] = PATH_thisScript;

        if (!file_exists(PATH_thisScript)) {
            $this->exitWithMessage('Unable to determine path to entry script. Please check your path or set an environment variable \'TYPO3_PATH_ROOT\' to your root path.');
        }
    }

    /**
     * Defines the constant ORIGINAL_ROOT for the path to the original TYPO3 document root.
     * For functional / acceptance tests only
     * If ORIGINAL_ROOT already is defined, this method is a no-op.
     */
    public function defineOriginalRootPath()
    {
        if (!defined('ORIGINAL_ROOT')) {
            define('ORIGINAL_ROOT', $this->getWebRoot());
        }

        if (!file_exists(ORIGINAL_ROOT . 'typo3/cli_dispatch.phpsh')) {
            $this->exitWithMessage('Unable to determine path to entry script. Please check your path or set an environment variable \'TYPO3_PATH_ROOT\' to your root path.');
        }
    }

    /**
     * Define TYPO3_MODE to BE
     */
    public function defineTypo3ModeBe()
    {
        define('TYPO3_MODE', 'BE');
    }

    /**
     * Sets the environment variable TYPO3_CONTEXT to testing.
     */
    public function setTypo3TestingContext()
    {
        putenv('TYPO3_CONTEXT=Testing');
    }

    /**
     * Creates directories, recursively if required.
     *
     * @param string $directory Absolute path to directories to create
     * @throws Exception
     */
    public function createDirectory($directory)
    {
        if (is_dir($directory)) {
            return;
        }
        @mkdir($directory, 0777, true);
        clearstatcache();
        if (!is_dir($directory)) {
            throw new Exception('Directory "' . $directory . '" could not be created', 1404038665);
        }
    }

    /**
     * Checks whether given test instance exists in path and is younger than some minutes.
     * Used in functional tests
     *
     * @param string $instancePath Absolute path to test instance
     * @return bool
     */
    public function recentTestInstanceExists($instancePath)
    {
        if (@file_get_contents($instancePath . '/last_run.txt') <= (time() - 300)) {
            return false;
        } else {
            // Test instance exists and is pretty young -> re-use
            return true;
        }
    }

    /**
     * Remove test instance folder structure if it exists.
     * This may happen if a functional test before threw a fatal or is too old
     *
     * @param string $instancePath Absolute path to test instance
     * @throws Exception
     */
    public function removeOldInstanceIfExists($instancePath)
    {
        if (is_dir($instancePath)) {
            $success = GeneralUtility::rmdir($instancePath, true);
            if (!$success) {
                throw new Exception(
                    'Can not remove folder: ' . $instancePath,
                    1376657210
                );
            }
        }
    }

    /**
     * Create last_run.txt file within instance path containing timestamp of "now".
     * Used in functional tests to reuse an instance for multiple tests in one test case.
     *
     * @param string $instancePath Absolute path to test instance
     */
    public function createLastRunTextfile($instancePath)
    {
        // Store the time instance was created
        file_put_contents($instancePath . '/last_run.txt', time());
    }

    /**
     * Link TYPO3 CMS core from "parent" instance.
     * For functional and acceptance tests.
     *
     * @param string $instancePath Absolute path to test instance
     * @throws Exception
     */
    public function setUpInstanceCoreLinks($instancePath)
    {
        $linksToSet = [
            ORIGINAL_ROOT . 'typo3' => $instancePath . '/typo3',
            ORIGINAL_ROOT . 'index.php' => $instancePath . '/index.php'
        ];
        foreach ($linksToSet as $from => $to) {
            $success = symlink($from, $to);
            if (!$success) {
                throw new Exception(
                    'Creating link failed: from ' . $from . ' to: ' . $to,
                    1376657199
                );
            }
        }
    }

    /**
     * Link test extensions to the typo3conf/ext folder of the instance.
     * For functional and acceptance tests.
     *
     * @param string $instancePath Absolute path to test instance
     * @param array $extensionPaths Contains paths to extensions relative to document root
     * @throws Exception
     */
    public function linkTestExtensionsToInstance($instancePath, array $extensionPaths)
    {
        foreach ($extensionPaths as $extensionPath) {
            $absoluteExtensionPath = ORIGINAL_ROOT . $extensionPath;
            if (!is_dir($absoluteExtensionPath)) {
                throw new Exception(
                    'Test extension path ' . $absoluteExtensionPath . ' not found',
                    1376745645
                );
            }
            $destinationPath = $instancePath . '/typo3conf/ext/' . basename($absoluteExtensionPath);
            $success = symlink($absoluteExtensionPath, $destinationPath);
            if (!$success) {
                throw new Exception(
                    'Can not link extension folder: ' . $absoluteExtensionPath . ' to ' . $destinationPath,
                    1376657142
                );
            }
        }
    }

    /**
     * Link paths inside the test instance, e.g. from a fixture fileadmin subfolder to the
     * test instance fileadmin folder.
     * For functional and acceptance tests.
     *
     * @param string $instancePath Absolute path to test instance
     * @param array $pathsToLinkInTestInstance Contains paths as array of source => destination in key => value pairs of folders relative to test instance root
     * @throws Exception if a source path could not be found and on failing creating the symlink
     */
    public function linkPathsInTestInstance($instancePath, array $pathsToLinkInTestInstance)
    {
        foreach ($pathsToLinkInTestInstance as $sourcePathToLinkInTestInstance => $destinationPathToLinkInTestInstance) {
            $sourcePath = $instancePath . '/' . ltrim($sourcePathToLinkInTestInstance, '/');
            if (!file_exists($sourcePath)) {
                throw new Exception(
                    'Path ' . $sourcePath . ' not found',
                    1476109221
                );
            }
            $destinationPath = $instancePath . '/' . ltrim($destinationPathToLinkInTestInstance, '/');
            $success = symlink($sourcePath, $destinationPath);
            if (!$success) {
                throw new Exception(
                    'Can not link the path ' . $sourcePath . ' to ' . $destinationPath,
                    1389969623
                );
            }
        }
    }

    /**
     * Database settings for functional and acceptance tests can be either set by
     * environment variables (recommended), or from an existing LocalConfiguration as fallback.
     * The method fetches these.
     *
     * An unique name will be added to the database name later.
     *
     * @throws Exception
     * @return array [DB][host], [DB][username], ...
     */
    public function getOriginalDatabaseSettingsFromEnvironmentOrLocalConfiguration()
    {
        $databaseName = trim(getenv('typo3DatabaseName'));
        $databaseHost = trim(getenv('typo3DatabaseHost'));
        $databaseUsername = trim(getenv('typo3DatabaseUsername'));
        $databasePassword = getenv('typo3DatabasePassword');
        $databasePasswordTrimmed = trim($databasePassword);
        $databasePort = trim(getenv('typo3DatabasePort'));
        $databaseSocket = trim(getenv('typo3DatabaseSocket'));
        $databaseDriver = trim(getenv('typo3DatabaseDriver'));
        if ($databaseName || $databaseHost || $databaseUsername || $databasePassword || $databasePort || $databaseSocket) {
            // Try to get database credentials from environment variables first
            $originalConfigurationArray = [
                'DB' => [
                    'Connections' => [
                        'Default' => [
                            'driver' => 'mysqli'
                        ],
                    ],
                ],
            ];
            if ($databaseName) {
                $originalConfigurationArray['DB']['Connections']['Default']['dbname'] = $databaseName;
            }
            if ($databaseHost) {
                $originalConfigurationArray['DB']['Connections']['Default']['host'] = $databaseHost;
            }
            if ($databaseUsername) {
                $originalConfigurationArray['DB']['Connections']['Default']['user'] = $databaseUsername;
            }
            if ($databasePassword !== false) {
                $originalConfigurationArray['DB']['Connections']['Default']['password'] = $databasePasswordTrimmed;
            }
            if ($databasePort) {
                $originalConfigurationArray['DB']['Connections']['Default']['port'] = $databasePort;
            }
            if ($databaseSocket) {
                $originalConfigurationArray['DB']['Connections']['Default']['unix_socket'] = $databaseSocket;
            }
            if ($databaseDriver) {
                $originalConfigurationArray['DB']['Connections']['Default']['driver'] = $databaseDriver;
            }
        } elseif (file_exists(ORIGINAL_ROOT . 'typo3conf/LocalConfiguration.php')) {
            // See if a LocalConfiguration file exists in "parent" instance to get db credentials from
            $originalConfigurationArray = require ORIGINAL_ROOT . 'typo3conf/LocalConfiguration.php';
        } else {
            throw new Exception(
                'Database credentials for tests are neither set through environment'
                . ' variables, and can not be found in an existing LocalConfiguration file',
                1397406356
            );
        }
        return $originalConfigurationArray['DB'];
    }

    /**
     * Maximum length of database names is 64 chars in mysql. Test this is not exceeded
     * after a suffix has been added.
     *
     * @param string $originalDatabaseName Base name of the database
     * @param array $configuration "LocalConfiguration" array with DB settings
     * @throws Exception
     */
    public function testDatabaseNameIsNotTooLong($originalDatabaseName, array $configuration)
    {
        // Maximum database name length for mysql is 64 characters
        if (strlen($configuration['DB']['Connections']['Default']['dbname']) > 64) {
            $suffixLength = strlen($configuration['DB']['Connections']['Default']['dbname']) - strlen($originalDatabaseName);
            $maximumOriginalDatabaseName = 64 - $suffixLength;
            throw new Exception(
                'The name of the database that is used for the functional test (' . $originalDatabaseName . ')' .
                ' exceeds the maximum length of 64 character allowed by MySQL. You have to shorten your' .
                ' original database name to ' . $maximumOriginalDatabaseName . ' characters',
                1377600104
            );
        }
    }

    /**
     * Create LocalConfiguration.php file of the test instance.
     * For functional and acceptance tests.
     *
     * @param string $instancePath Absolute path to test instance
     * @param array $configuration Base configuration array
     * @param array $overruleConfiguration Overrule factory and base configuration
     * @throws Exception
     */
    public function setUpLocalConfiguration($instancePath, array $configuration, array $overruleConfiguration)
    {
        // Base of final LocalConfiguration is core factory configuration
        $finalConfigurationArray = require ORIGINAL_ROOT . 'typo3/sysext/core/Configuration/FactoryConfiguration.php';
        $finalConfigurationArray = array_replace_recursive($finalConfigurationArray, $configuration);
        $finalConfigurationArray = array_replace_recursive($finalConfigurationArray, $overruleConfiguration);
        $result = $this->writeFile(
            $instancePath . '/typo3conf/LocalConfiguration.php',
            '<?php' . chr(10) .
            'return ' .
            ArrayUtility::arrayExport(
                $finalConfigurationArray
            ) .
            ';'
        );
        if (!$result) {
            throw new Exception('Can not write local configuration', 1376657277);
        }
    }

    /**
     * Compile typo3conf/PackageStates.php containing default packages like core,
     * a test specific list of additional core extensions, and a list of
     * test extensions.
     * For functional and acceptance tests.
     *
     * @param string $instancePath Absolute path to test instance
     * @param array $defaultCoreExtensionsToLoad Default list of core extensions to load
     * @param array $additionalCoreExtensionsToLoad Additional core extensions to load
     * @param array $testExtensionPaths Paths to extensions relative to document root
     * @throws Exception
     */
    public function setUpPackageStates(
        $instancePath,
        array $defaultCoreExtensionsToLoad,
        array $additionalCoreExtensionsToLoad,
        array $testExtensionPaths
    ) {
        $packageStates = [
            'packages' => [],
            'version' => 5,
        ];

        // Register default list of extensions and set active
        foreach ($defaultCoreExtensionsToLoad as $extensionName) {
            $packageStates['packages'][$extensionName] = [
                'packagePath' => 'typo3/sysext/' . $extensionName . '/'
            ];
        }

        // Register additional core extensions and set active
        foreach ($additionalCoreExtensionsToLoad as $extensionName) {
            $packageStates['packages'][$extensionName] = [
                'packagePath' => 'typo3/sysext/' . $extensionName . '/'
            ];
        }

        // Activate test extensions that have been symlinked before
        foreach ($testExtensionPaths as $extensionPath) {
            $extensionName = basename($extensionPath);
            $packageStates['packages'][$extensionName] = [
                'packagePath' => 'typo3conf/ext/' . $extensionName . '/'
            ];
        }

        $result = $this->writeFile(
            $instancePath . '/typo3conf/PackageStates.php',
            '<?php' . chr(10) .
            'return ' .
            ArrayUtility::arrayExport(
                $packageStates
            ) .
            ';'
        );

        if (!$result) {
            throw new Exception('Can not write PackageStates', 1381612729);
        }
    }

    /**
     * Create a low level connection to dbms, without selecting the target database.
     * Drop existing database if it exists and create a new one.
     *
     * @param string $databaseName Database name of this test instance
     * @param string $originalDatabaseName Original database name before suffix was added
     * @throws \TYPO3\TestingFramework\Core\Exception
     */
    public function setUpTestDatabase($databaseName, $originalDatabaseName)
    {
        Bootstrap::getInstance()->initializeTypo3DbGlobal();

        // Drop database if exists. Directly using the Doctrine DriverManager to
        // work around connection caching in ConnectionPool
        $connectionParameters = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'];
        unset($connectionParameters['dbname']);
        $schemaManager = DriverManager::getConnection($connectionParameters)->getSchemaManager();

        if (in_array($databaseName, $schemaManager->listDatabases(), true)) {
            $schemaManager->dropDatabase($databaseName);
        }

        try {
            $schemaManager->createDatabase($databaseName);
        } catch (DBALException $e) {
            $user = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['user'];
            $host = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['host'];
            throw new Exception(
                'Unable to create database with name ' . $databaseName . '. This is probably a permission problem.'
                . ' For this instance this could be fixed executing:'
                . ' GRANT ALL ON `' . $originalDatabaseName . '_%`.* TO `' . $user . '`@`' . $host . '`;'
                . ' Original message thrown by database layer: ' . $e->getMessage(),
                1376579070
            );
        }
    }

    /**
     * Bootstrap basic TYPO3. This bootstraps TYPO3 far enough to initialize database afterwards.
     * For functional and acceptance tests.
     *
     * @param string $instancePath Absolute path to test instance
     */
    public function setUpBasicTypo3Bootstrap($instancePath)
    {
        $_SERVER['PWD'] = $instancePath;
        $_SERVER['argv'][0] = 'index.php';

        $classLoader = require rtrim(realpath($instancePath . '/typo3'), '\\/') . '/../vendor/autoload.php';
        Bootstrap::getInstance()
            ->initializeClassLoader($classLoader)
            ->setRequestType(TYPO3_REQUESTTYPE_BE | TYPO3_REQUESTTYPE_CLI)
            ->baseSetup()
            ->loadConfigurationAndInitialize(true)
            ->loadTypo3LoadedExtAndExtLocalconf(true)
            ->setFinalCachingFrameworkCacheConfiguration()
            ->defineLoggingAndExceptionConstants()
            ->unsetReservedGlobalVariables();
    }

    /**
     * Truncate all tables.
     * For functional and acceptance tests.
     *
     * @throws Exception
     */
    public function initializeTestDatabaseAndTruncateTables()
    {
        Bootstrap::getInstance()->initializeTypo3DbGlobal();

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
        $schemaManager = $connection->getSchemaManager();

        foreach ($schemaManager->listTables() as $table) {
            $connection->truncate($table->getName());
            self::resetTableSequences($connection, $table->getName());
        }
    }

    /**
     * Load ext_tables.php files.
     * For functional and acceptance tests.
     */
    public function loadExtensionTables()
    {
        Bootstrap::getInstance()->loadBaseTca()->loadExtTables();
    }

    /**
     * Create tables and import static rows.
     * For functional and acceptance tests.
     */
    public function createDatabaseStructure()
    {
        $schemaMigrationService = GeneralUtility::makeInstance(SchemaMigrator::class);
        $sqlReader = GeneralUtility::makeInstance(SqlReader::class);
        $sqlCode = $sqlReader->getTablesDefinitionString(true);

        $createTableStatements = $sqlReader->getCreateTableStatementArray($sqlCode);

        $schemaMigrationService->install($createTableStatements);

        $insertStatements = $sqlReader->getInsertStatementArray($sqlCode);
        $schemaMigrationService->importStaticData($insertStatements);
    }

    /**
     * Imports a data set represented as XML into the test database,
     *
     * @param string $path Absolute path to the XML file containing the data set to load
     * @throws \Doctrine\DBAL\DBALException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function importXmlDatabaseFixture($path)
    {
        if (!is_file($path)) {
            throw new \RuntimeException(
                'Fixture file ' . $path . ' not found',
                1376746261
            );
        }

        $fileContent = file_get_contents($path);
        // Disables the functionality to allow external entities to be loaded when parsing the XML, must be kept
        $previousValueOfEntityLoader = libxml_disable_entity_loader(true);
        $xml = simplexml_load_string($fileContent);
        libxml_disable_entity_loader($previousValueOfEntityLoader);
        $foreignKeys = [];

        /** @var $table \SimpleXMLElement */
        foreach ($xml->children() as $table) {
            $insertArray = [];

            /** @var $column \SimpleXMLElement */
            foreach ($table->children() as $column) {
                $columnName = $column->getName();
                $columnValue = null;

                if (isset($column['ref'])) {
                    list($tableName, $elementId) = explode('#', $column['ref']);
                    $columnValue = $foreignKeys[$tableName][$elementId];
                } elseif (isset($column['is-NULL']) && ($column['is-NULL'] === 'yes')) {
                    $columnValue = null;
                } else {
                    $columnValue = (string)$table->$columnName;
                }

                $insertArray[$columnName] = $columnValue;
            }

            $tableName = $table->getName();
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($tableName);
            $connection->insert(
                $tableName,
                $insertArray
            );
            static::resetTableSequences($connection, $tableName);

            if (isset($table['id'])) {
                $elementId = (string)$table['id'];
                $foreignKeys[$tableName][$elementId] = $connection->lastInsertId($tableName);
            }
        }
    }

    /**
     * Perform post processing of database tables after an insert has been performed.
     * Doing this once per insert is rather slow, but due to the soft reference behavior
     * this needs to be done after every row to ensure consistent results.
     *
     * @param \TYPO3\CMS\Core\Database\Connection $connection
     * @param string $tableName
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function resetTableSequences(Connection $connection, string $tableName)
    {
        if ($connection->getDatabasePlatform() instanceof PostgreSqlPlatform) {
            $queryBuilder = $connection->createQueryBuilder();
            $queryBuilder->getRestrictions()->removeAll();
            $row = $queryBuilder->select('PGT.schemaname', 'S.relname', 'C.attname', 'T.relname AS tablename')
                ->from('pg_class', 'S')
                ->from('pg_depend', 'D')
                ->from('pg_class', 'T')
                ->from('pg_attribute', 'C')
                ->from('pg_tables', 'PGT')
                ->where(
                    $queryBuilder->expr()->eq('S.relkind', $queryBuilder->quote('S')),
                    $queryBuilder->expr()->eq('S.oid', $queryBuilder->quoteIdentifier('D.objid')),
                    $queryBuilder->expr()->eq('D.refobjid', $queryBuilder->quoteIdentifier('T.oid')),
                    $queryBuilder->expr()->eq('D.refobjid', $queryBuilder->quoteIdentifier('C.attrelid')),
                    $queryBuilder->expr()->eq('D.refobjsubid', $queryBuilder->quoteIdentifier('C.attnum')),
                    $queryBuilder->expr()->eq('T.relname', $queryBuilder->quoteIdentifier('PGT.tablename')),
                    $queryBuilder->expr()->eq('PGT.tablename', $queryBuilder->quote($tableName))
                )
                ->setMaxResults(1)
                ->execute()
                ->fetch();

            if ($row !== false) {
                $connection->exec(
                    sprintf(
                        'SELECT SETVAL(%s, COALESCE(MAX(%s), 0)+1, FALSE) FROM %s',
                        $connection->quote($row['schemaname'] . '.' . $row['relname']),
                        $connection->quoteIdentifier($row['attname']),
                        $connection->quoteIdentifier($row['schemaname'] . '.' . $row['tablename'])
                    )
                );
            }
        }
    }

    /**
     * Returns the absolute path the TYPO3 document root.
     * This is the "original" document root, not the "instance" root for functional / acceptance tests.
     *
     * @return string the TYPO3 document root using Unix path separators
     */
    protected function getWebRoot()
    {
        if (getenv('TYPO3_PATH_ROOT')) {
            $webRoot = getenv('TYPO3_PATH_ROOT');
        } elseif (getenv('TYPO3_PATH_WEB')) {
            // @deprecated
            $webRoot = getenv('TYPO3_PATH_WEB');
        } else {
            $webRoot = getcwd();
        }
        return rtrim(strtr($webRoot, '\\', '/'), '/') . '/';
    }

    /**
     * Send http headers, echo out a text message and exit with error code
     *
     * @param string $message
     */
    protected function exitWithMessage($message)
    {
        echo $message . LF;
        exit(1);
    }

    /**
     * Writes $content to the file $file. This is a simplified version
     * of GeneralUtility::writeFile that does not fix permissions.
     *
     * @param string $file Filepath to write to
     * @param string $content Content to write
     * @return bool TRUE if the file was successfully opened and written to.
     */
    protected function writeFile($file, $content)
    {
        if ($fd = fopen($file, 'wb')) {
            $res = fwrite($fd, $content);
            fclose($fd);
            if ($res === false) {
                return false;
            }
            return true;
        }
        return false;
    }
}
