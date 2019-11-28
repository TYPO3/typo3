<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Install\SystemEnvironment\DatabaseCheck\Platform;

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

/**
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class SqlSrv extends AbstractPlatform
{
    /**
     * SQL Server has a more complex naming schema for the collation.
     * For more information visit:
     * https://docs.microsoft.com/en-us/sql/relational-databases/collations/collation-and-unicode-support
     *
     * Thus we need to check, whether the charset set here is part of the collation.
     *
     * @var array
     */
    protected $databaseCharsetToCheck = [
        '_UTF8',
    ];

    /**
     * SQL Server has a more complex naming schema for the collation.
     * For more information visit:
     * https://docs.microsoft.com/en-us/sql/relational-databases/collations/collation-and-unicode-support
     *
     * Thus we need to check, whether the charset set here is part of the collation.
     *
     * @var array
     */
    protected $databaseServerCharsetToCheck = [
        '_UTF8'
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
        $defaultConnection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
        if (strpos($defaultConnection->getServerVersion(), 'mssql') !== 0) {
            return $this->messageQueue;
        }

        $this->checkDefaultDatabaseCharset($defaultConnection);
        $this->checkDefaultDatabaseServerCharset($defaultConnection);
        $this->checkDatabaseName($defaultConnection);

        return $this->messageQueue;
    }

    /**
     * Checks the character set of the database and reports an error if it is not utf-8.
     *
     * @param Connection $connection to the database to be checked
     */
    public function checkDefaultDatabaseCharset(Connection $connection): void
    {
        $defaultDatabaseCharset = $connection->executeQuery(
            'SELECT DATABASEPROPERTYEX(?,\'collation\')',
            [$connection->getDatabase()],
            [\PDO::PARAM_STR]
        )
            ->fetch(\PDO::FETCH_NUM);

        foreach ($this->databaseCharsetToCheck as $databaseCharsetToCheck) {
            if (!stripos($defaultDatabaseCharset[0], $databaseCharsetToCheck)) {
                $this->messageQueue->enqueue(new FlashMessage(
                    sprintf(
                        'Checking database character set failed, got key "%s" where "%s" is not part of the collation',
                        $defaultDatabaseCharset[0],
                        $databaseCharsetToCheck
                    ),
                    'SQL Server database character set check failed',
                    FlashMessage::ERROR
                ));
            } else {
                $this->messageQueue->enqueue(new FlashMessage(
                    '',
                    sprintf('SQL Server database uses %s. All good.', implode(' or ', $this->databaseCharsetToCheck))
                ));
            }
        }
    }

    /**
     * Checks the character set of the database server and reports an info if it is not utf-8.
     *
     * @param Connection $connection to the database to be checked
     */
    public function checkDefaultDatabaseServerCharset(Connection $connection): void
    {
        $defaultServerCharset = $connection->executeQuery('SELECT SERVERPROPERTY(\'Collation\')')
            ->fetch(\PDO::FETCH_NUM);

        foreach ($this->databaseServerCharsetToCheck as $databaseServerCharsetToCheck) {
            // is charset part of collation
            if (!stripos($defaultServerCharset[0], $databaseServerCharsetToCheck)) {
                $this->messageQueue->enqueue(new FlashMessage(
                    sprintf(
                        'Checking server character set failed, got key "%s" where "%s" is not part of the collation',
                        $defaultServerCharset[0],
                        $databaseServerCharsetToCheck
                    ),
                    'SQL Server database character set check failed',
                    FlashMessage::INFO
                ));
            } else {
                $this->messageQueue->enqueue(new FlashMessage(
                    '',
                    sprintf('SQL Server server default uses %s. All good.', implode(' or ', $this->databaseCharsetToCheck))
                ));
            }
        }
    }
}
