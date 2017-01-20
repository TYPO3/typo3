<?php

namespace TYPO3\CMS\Install\SystemEnvironment\DatabasePlatform;

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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Status;
use TYPO3\CMS\Install\SystemEnvironment\CheckInterface;

/**
 * Check database configuration status for PostgreSQL
 *
 * This class is a hardcoded requirement check for the database server.
 *
 * The status messages and title *must not* include HTML, use plain
 * text only. The return values of this class are not bound to HTML
 * and can be used in different scopes (eg. as json array).
 */
class PostgreSqlCheck implements CheckInterface
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
     * Get all status information as array with status objects
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     * @throws \InvalidArgumentException
     */
    public function getStatus(): array
    {
        $statusArray = [];
        $defaultConnection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
        if (strpos($defaultConnection->getServerVersion(), 'PostgreSQL') !== 0) {
            return $statusArray;
        }

        $statusArray[] = $this->checkPostgreSqlVersion($defaultConnection);
        $statusArray[] = $this->checkLibpqVersion();
        return $statusArray;
    }

    /**
     * Check minimum PostgreSQL version
     *
     * @param Connection Connection to the database to be checked
     * @return Status\StatusInterface
     */
    protected function checkPostgreSqlVersion($connection): Status\StatusInterface
    {
        preg_match('/PostgreSQL ((\d+\.)*(\d+\.)*\d+)/', $connection->getServerVersion(), $match);
        $currentPostgreSqlVersion = $match[1];
        if (version_compare($currentPostgreSqlVersion, $this->minimumPostgreSQLVerion, '<')) {
            $status = new Status\ErrorStatus();
            $status->setTitle('PostgreSQL Server version is unsupported');
            $status->setMessage(
                'Your PostgreSQL version ' . $currentPostgreSqlVersion . ' is not supported. TYPO3 CMS does not run' .
                ' with this version. The minimum supported PostgreSQL version is ' . $this->minimumPostgreSQLVerion
            );
        } else {
            $status = new Status\OkStatus();
            $status->setTitle('PostgreSQL Server version is supported');
        }

        return $status;
    }

    /**
     * Check the version of ligpq within the PostgreSQL driver
     *
     * @return Status\StatusInterface
     */
    protected function checkLibpqVersion(): Status\StatusInterface
    {
        if (!defined('PGSQL_LIBPQ_VERSION_STR')) {
            $status = new Status\WarningStatus();
            $status->setTitle('PostgreSQL libpq version cannot be determined');
            $status->setMessage(
                'It is not possible to retrieve your PostgreSQL libpq version. Please check the version' .
                ' in the "phpinfo" area of the "System environment" module in the install tool manually.' .
                ' This should be found in section "pdo_pgsql".' .
                ' You should have at least the following version of  PostgreSQL libpq installed: ' .
                $this->minimumLibPQVersion
            );
        } else {
            preg_match('/PostgreSQL ((\d+\.)*(\d+\.)*\d+)/', \PGSQL_LIBPQ_VERSION_STR, $match);
            $currentPostgreSqlLibpqVersion = $match[1];

            if (version_compare($currentPostgreSqlLibpqVersion, $this->minimumLibPQVersion, '<')) {
                $status = new Status\ErrorStatus();
                $status->setTitle('PostgreSQL libpq version is unsupported');
                $status->setMessage(
                    'Your PostgreSQL libpq version "' . $currentPostgreSqlLibpqVersion . '" is unsupported.' .
                    ' TYPO3 CMS does not run with this version. The minimum supported libpq version is ' .
                    $this->minimumLibPQVersion
                );
            } else {
                $status = new Status\OkStatus();
                $status->setTitle('PostgreSQL libpq version is supported');
            }
        }
        return $status;
    }
}
