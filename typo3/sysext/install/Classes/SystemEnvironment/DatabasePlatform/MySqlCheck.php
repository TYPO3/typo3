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
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Status;
use TYPO3\CMS\Install\SystemEnvironment\CheckInterface;

/**
 * Check database configuration status for MySQL server
 *
 * This class is a hardcoded requirement check for the database server.
 *
 * The status messages and title *must not* include HTML, use plain
 * text only. The return values of this class are not bound to HTML
 * and can be used in different scopes (eg. as json array).
 */
class MySqlCheck implements CheckInterface
{
    /**
     * Minimum supported MySQL version
     *
     * @var string
     */
    protected $minimumMySQLVersion = '5.5.0';

    /**
     * List of MySQL modes that are incompatible with TYPO3 CMS
     *
     * @var array
     */
    protected $incompatibleSqlModes = [
        'NO_BACKSLASH_ESCAPES'
    ];

    /**
     * Get all status information as array with status objects
     *
     * @return \TYPO3\CMS\Install\Status\StatusInterface[]
     * @throws \InvalidArgumentException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getStatus(): array
    {
        $statusArray = [];
        $defaultConnection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
        if (strpos($defaultConnection->getServerVersion(), 'MySQL') !== 0) {
            return $statusArray;
        }
        $statusArray[] = $this->checkMysqlVersion($defaultConnection);
        $statusArray[] = $this->checkInvalidSqlModes($defaultConnection);
        $statusArray[] = $this->checkMysqlDatabaseUtf8Status($defaultConnection);
        return $statusArray;
    }

    /**
     * Check if any SQL mode is set which is not compatible with TYPO3
     *
     * @param Connection Connection to the database to be checked
     * @return Status\StatusInterface
     */
    protected function checkInvalidSqlModes($connection)
    {
        $detectedIncompatibleSqlModes = $this->getIncompatibleSqlModes($connection);
        if (!empty($detectedIncompatibleSqlModes)) {
            $status = new Status\ErrorStatus();
            $status->setTitle('Incompatible SQL modes found!');
            $status->setMessage(
                'Incompatible SQL modes have been detected:' .
                ' ' . implode(', ', $detectedIncompatibleSqlModes) . '.' .
                ' The listed modes are not compatible with TYPO3 CMS.' .
                ' You have to change that setting in your MySQL environment' .
                ' or in $GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'setDBinit\']'
            );
        } else {
            $status = new Status\OkStatus();
            $status->setTitle('No incompatible SQL modes found.');
        }

        return $status;
    }

    /**
     * Check minimum MySQL version
     *
     * @param Connection Connection to the database to be checked
     * @return Status\StatusInterface
     */
    protected function checkMysqlVersion($connection)
    {
        preg_match('/MySQL ((\d+\.)*(\d+\.)*\d+)/', $connection->getServerVersion(), $match);
        $currentMysqlVersion = $match[1];
        if (version_compare($currentMysqlVersion, $this->minimumMySQLVersion, '<')) {
            $status = new Status\ErrorStatus();
            $status->setTitle('MySQL version too low');
            $status->setMessage(
                'Your MySQL version ' . $currentMysqlVersion . ' is too old. TYPO3 CMS does not run' .
                ' with this version. Update to at least MySQL ' . $this->minimumMySQLVersion
            );
        } else {
            $status = new Status\OkStatus();
            $status->setTitle('MySQL version is fine');
        }

        return $status;
    }

    /**
     * Checks the character set of the database and reports an error if it is not utf-8.
     *
     * @param Connection $connection to the database to be checked
     * @return Status\StatusInterface
     */
    protected function checkMysqlDatabaseUtf8Status(Connection $connection)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $connection->createQueryBuilder();
        $defaultDatabaseCharset = (string)$queryBuilder->select('DEFAULT_CHARACTER_SET_NAME')
            ->from('information_schema.SCHEMATA')
            ->where(
                $queryBuilder->expr()->eq(
                    'SCHEMA_NAME',
                    $queryBuilder->createNamedParameter($connection->getDatabase(), \PDO::PARAM_STR)
                )
            )
            ->setMaxResults(1)
            ->execute()
            ->fetchColumn();
        // also allow utf8mb4
        if (strpos($defaultDatabaseCharset, 'utf8') !== 0) {
            $status = new Status\ErrorStatus();
            $status->setTitle('MySQL database character set check failed');
            $status->setMessage(
                'Checking database character set failed, got key "'
                . $defaultDatabaseCharset . '" instead of "utf8" or "utf8mb4"'
            );
        } else {
            $status = new Status\OkStatus();
            $status->setTitle('Your database uses utf-8. All good.');
        }
        return $status;
    }

    /**
     * Returns an array with the current sql mode settings
     *
     * @param Connection Connection to the database to be checked
     * @return array Contains all configured SQL modes that are incompatible
     */
    protected function getIncompatibleSqlModes($connection)
    {
        $sqlModes = explode(',', $connection->executeQuery('SELECT @@SESSION.sql_mode;')
            ->fetch(0)['@@SESSION.sql_mode']);
        return array_intersect($this->incompatibleSqlModes, $sqlModes);
    }
}
