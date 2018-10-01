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
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\SystemEnvironment\CheckInterface;

/**
 * Check database configuration status for MySQL server
 *
 * This class is a hardcoded requirement check for the database server.
 *
 * The status messages and title *must not* include HTML, use plain
 * text only. The return values of this class are not bound to HTML
 * and can be used in different scopes (eg. as json array).
 *
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class MySqlCheck implements CheckInterface
{
    /**
     * @var FlashMessageQueue
     */
    protected $messageQueue;

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
     * @return FlashMessageQueue
     * @throws \InvalidArgumentException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getStatus(): FlashMessageQueue
    {
        $this->messageQueue = new FlashMessageQueue('install');
        $defaultConnection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
        if (strpos($defaultConnection->getServerVersion(), 'MySQL') !== 0) {
            return $this->messageQueue;
        }
        $this->checkMysqlVersion($defaultConnection);
        $this->checkInvalidSqlModes($defaultConnection);
        $this->checkMysqlDatabaseUtf8Status($defaultConnection);
        return $this->messageQueue;
    }

    /**
     * Check if any SQL mode is set which is not compatible with TYPO3
     *
     * @param Connection $connection to the database to be checked
     */
    protected function checkInvalidSqlModes(Connection $connection)
    {
        $detectedIncompatibleSqlModes = $this->getIncompatibleSqlModes($connection);
        if (!empty($detectedIncompatibleSqlModes)) {
            $this->messageQueue->enqueue(new FlashMessage(
                'Incompatible SQL modes have been detected:'
                    . ' ' . implode(', ', $detectedIncompatibleSqlModes) . '.'
                    . ' The listed modes are not compatible with TYPO3 CMS.'
                    . ' You have to change that setting in your MySQL environment'
                    . ' or in $GLOBALS[\'TYPO3_CONF_VARS\'][\'DB\'][\'Connections\'][\'Default\'][\'initCommands\']',
                'Incompatible SQL modes found!',
                FlashMessage::ERROR
            ));
        } else {
            $this->messageQueue->enqueue(new FlashMessage(
                '',
                'No incompatible SQL modes found.'
            ));
        }
    }

    /**
     * Check minimum MySQL version
     *
     * @param Connection $connection to the database to be checked
     */
    protected function checkMysqlVersion(Connection $connection)
    {
        preg_match('/MySQL ((\d+\.)*(\d+\.)*\d+)/', $connection->getServerVersion(), $match);
        $currentMysqlVersion = $match[1];
        if (version_compare($currentMysqlVersion, $this->minimumMySQLVersion, '<')) {
            $this->messageQueue->enqueue(new FlashMessage(
                'Your MySQL version ' . $currentMysqlVersion . ' is too old. TYPO3 CMS does not run'
                    . ' with this version. Update to at least MySQL ' . $this->minimumMySQLVersion,
                'MySQL version too low',
                FlashMessage::ERROR
            ));
        } else {
            $this->messageQueue->enqueue(new FlashMessage(
                '',
                'MySQL version is fine'
            ));
        }
    }

    /**
     * Checks the character set of the database and reports an error if it is not utf-8.
     *
     * @param Connection $connection to the database to be checked
     */
    protected function checkMysqlDatabaseUtf8Status(Connection $connection)
    {
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
            $this->messageQueue->enqueue(new FlashMessage(
                'Checking database character set failed, got key "'
                    . $defaultDatabaseCharset . '" instead of "utf8" or "utf8mb4"',
                'MySQL database character set check failed',
                FlashMessage::ERROR
            ));
        } else {
            $this->messageQueue->enqueue(new FlashMessage(
                '',
                'Your database uses utf-8. All good.'
            ));
        }
    }

    /**
     * Returns an array with the current sql mode settings
     *
     * @param Connection $connection to the database to be checked
     * @return array Contains all configured SQL modes that are incompatible
     */
    protected function getIncompatibleSqlModes(Connection $connection): array
    {
        $sqlModes = explode(',', $connection->executeQuery('SELECT @@SESSION.sql_mode;')
            ->fetch(0)['@@SESSION.sql_mode']);
        return array_intersect($this->incompatibleSqlModes, $sqlModes);
    }
}
