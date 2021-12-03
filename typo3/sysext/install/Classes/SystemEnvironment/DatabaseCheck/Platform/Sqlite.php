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
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class Sqlite extends AbstractPlatform
{

    /**
     * Get all status information as array with status objects
     *
     * @return FlashMessageQueue
     * @throws \InvalidArgumentException
     * @throws \Doctrine\DBAL\Exception
     */
    public function getStatus(): FlashMessageQueue
    {
        $defaultConnection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
        if (strpos($defaultConnection->getServerVersion(), 'sqlite') !== 0) {
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
        // TODO: Implement getDefaultDatabaseCharset() method.
    }

    /**
     * Checks the character set of the database server and reports an info if it is not utf-8.
     *
     * @param Connection $connection to the database to be checked
     */
    public function checkDefaultDatabaseServerCharset(Connection $connection): void
    {
        // TODO: Implement getDefaultDatabaseServerCharset() method.
    }

    /**
     * Validate the database name
     * SQLite does not have any limitation for the length of the database name,
     * but must start with a letter or _
     *
     * @param string $databaseName
     * @return bool
     */
    public static function isValidDatabaseName(string $databaseName): bool
    {
        return (bool)preg_match('/^[A-Za-z_\/][a-zA-Z0-9\$\/_.-]*$/', $databaseName);
    }

    protected function checkDatabaseName(Connection $connection): void
    {
        if (static::isValidDatabaseName((string)$connection->getDatabase())) {
            return;
        }

        $this->messageQueue->enqueue(
            new FlashMessage(
                'The given database name must consist solely of basic latin letters (a-z), digits (0-9)'
                . ' and underscores (_).',
                'Database name not valid',
                FlashMessage::ERROR
            )
        );
    }
}
