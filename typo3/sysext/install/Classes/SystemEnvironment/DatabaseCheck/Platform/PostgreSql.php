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

namespace TYPO3\CMS\Install\SystemEnvironment\DatabaseCheck\Platform;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Check database configuration status for PostgreSQL
 *
 * This class is a hardcoded requirement check for the database server.
 *
 * The status messages and title *must not* include HTML, use plain
 * text only. The return values of this class are not bound to HTML
 * and can be used in different scopes (eg. as json array).
 *
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class PostgreSql extends AbstractPlatform
{
    /**
     * Minimum supported PostgreSQL Server version
     *
     * @var string
     */
    protected $minimumPostgreSQLVerion = '9.2';

    /**
     * Minimum supported libpq version
     * @var string
     */
    protected $minimumLibPQVersion = '9.0';

    /**
     * Charset of the database that should be fulfilled
     * @var array
     */
    protected $databaseCharsetToCheck = [
        'utf8',
    ];

    /**
     * Charset of the database server that should be fulfilled
     * @var array
     */
    protected $databaseServerCharsetToCheck = [
        'utf8',
    ];

    /**
     * Get all status information as array with status objects
     *
     * @return FlashMessageQueue
     * @throws \Doctrine\DBAL\Exception
     * @throws \InvalidArgumentException
     */
    public function getStatus(): FlashMessageQueue
    {
        $defaultConnection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
        if (strpos($defaultConnection->getServerVersion(), 'PostgreSQL') !== 0) {
            return $this->messageQueue;
        }

        $this->checkPostgreSqlVersion($defaultConnection);
        $this->checkLibpqVersion();
        $this->checkDefaultDatabaseCharset($defaultConnection);
        $this->checkDefaultDatabaseServerCharset($defaultConnection);
        $this->checkDatabaseName($defaultConnection);
        return $this->messageQueue;
    }

    /**
     * Check minimum PostgreSQL version
     *
     * @param Connection $connection to the database to be checked
     */
    protected function checkPostgreSqlVersion(Connection $connection)
    {
        preg_match('/PostgreSQL ((\d+\.)*(\d+\.)*\d+)/', $connection->getServerVersion(), $match);
        $currentPostgreSqlVersion = $match[1];
        if (version_compare($currentPostgreSqlVersion, $this->minimumPostgreSQLVerion, '<')) {
            $this->messageQueue->enqueue(new FlashMessage(
                'Your PostgreSQL version ' . $currentPostgreSqlVersion . ' is not supported. TYPO3 CMS does not run'
                . ' with this version. The minimum supported PostgreSQL version is ' . $this->minimumPostgreSQLVerion,
                'PostgreSQL Server version is unsupported',
                FlashMessage::ERROR
            ));
        } else {
            $this->messageQueue->enqueue(new FlashMessage(
                '',
                'PostgreSQL Server version is supported'
            ));
        }
    }

    /**
     * Check the version of ligpq within the PostgreSQL driver
     */
    protected function checkLibpqVersion()
    {
        if (!defined('PGSQL_LIBPQ_VERSION_STR')) {
            $this->messageQueue->enqueue(new FlashMessage(
                'It is not possible to retrieve your PostgreSQL libpq version. Please check the version'
                . ' in the "phpinfo" area of the "System environment" module in the install tool manually.'
                . ' This should be found in section "pdo_pgsql".'
                . ' You should have at least the following version of  PostgreSQL libpq installed: '
                . $this->minimumLibPQVersion,
                'PostgreSQL libpq version cannot be determined',
                FlashMessage::WARNING
            ));
        } else {
            preg_match('/PostgreSQL ((\d+\.)*(\d+\.)*\d+)/', \PGSQL_LIBPQ_VERSION_STR, $match);
            $currentPostgreSqlLibpqVersion = $match[1];

            if (version_compare($currentPostgreSqlLibpqVersion, $this->minimumLibPQVersion, '<')) {
                $this->messageQueue->enqueue(new FlashMessage(
                    'Your PostgreSQL libpq version "' . $currentPostgreSqlLibpqVersion . '" is unsupported.'
                    . ' TYPO3 CMS does not run with this version. The minimum supported libpq version is '
                    . $this->minimumLibPQVersion,
                    'PostgreSQL libpq version is unsupported',
                    FlashMessage::ERROR
                ));
            } else {
                $this->messageQueue->enqueue(new FlashMessage(
                    '',
                    'PostgreSQL libpq version is supported'
                ));
            }
        }
    }

    /**
     * Checks the character set of the database and reports an error if it is not utf-8.
     *
     * @param Connection $connection to the database to be checked
     */
    public function checkDefaultDatabaseCharset(Connection $connection): void
    {
        $defaultDatabaseCharset = $connection->executeQuery(
            'SELECT pg_catalog.pg_encoding_to_char(pg_database.encoding) from pg_database where datname = ?',
            [$connection->getDatabase()],
            [\PDO::PARAM_STR]
        )->fetchAssociative();

        if (!in_array(strtolower($defaultDatabaseCharset['pg_encoding_to_char']), $this->databaseCharsetToCheck, true)) {
            $this->messageQueue->enqueue(new FlashMessage(
                sprintf(
                    'Checking database character set failed, got key "%s" instead of "%s"',
                    $defaultDatabaseCharset['pg_encoding_to_char'],
                    implode(' or ', $this->databaseCharsetToCheck)
                ),
                'PostgreSQL database character set check failed',
                FlashMessage::ERROR
            ));
        } else {
            $this->messageQueue->enqueue(new FlashMessage(
                '',
                sprintf('PostgreSQL database uses %s. All good.', implode(' or ', $this->databaseCharsetToCheck))
            ));
        }
    }

    /**
     * Checks the character set of the database server and reports an info if it is not utf-8.
     *
     * @param Connection $connection to the database to be checked
     */
    public function checkDefaultDatabaseServerCharset(Connection $connection): void
    {
        $defaultServerCharset = $connection->executeQuery('SHOW SERVER_ENCODING')->fetchAssociative();

        if (!in_array(strtolower($defaultServerCharset['server_encoding']), $this->databaseCharsetToCheck, true)) {
            $this->messageQueue->enqueue(new FlashMessage(
                sprintf(
                    'Checking server character set failed, got key "%s" instead of "%s"',
                    $defaultServerCharset['server_encoding'],
                    implode(' or ', $this->databaseServerCharsetToCheck)
                ),
                'PostgreSQL database character set check failed',
                FlashMessage::INFO
            ));
        } else {
            $this->messageQueue->enqueue(new FlashMessage(
                '',
                sprintf('PostgreSQL server default uses %s. All good.', implode(' or ', $this->databaseCharsetToCheck))
            ));
        }
    }

    /**
     * Validate the database name
     *
     * @param string $databaseName
     * @return bool
     */
    public static function isValidDatabaseName(string $databaseName): bool
    {
        return strlen($databaseName) <= static::SCHEMA_NAME_MAX_LENGTH && preg_match('/^(?!pg_)[a-zA-Z0-9\$_]*$/', $databaseName);
    }

    protected function checkDatabaseName(Connection $connection): void
    {
        if (static::isValidDatabaseName((string)$connection->getDatabase())) {
            return;
        }

        $this->messageQueue->enqueue(
            new FlashMessage(
                'The given database name must not be longer than ' . static::SCHEMA_NAME_MAX_LENGTH . ' characters'
                . ' and consist solely of basic latin letters (a-z), digits (0-9), dollar signs ($)'
                . ' and underscores (_) and does not start with "pg_".',
                'Database name not valid',
                FlashMessage::ERROR
            )
        );
    }
}
