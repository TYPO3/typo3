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
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;

/**
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
abstract class AbstractPlatform implements PlatformCheckInterface
{
    /**
     * @var FlashMessageQueue
     */
    protected $messageQueue;

    public function __construct()
    {
        $this->messageQueue = new FlashMessageQueue('install-database-check-platform');
    }

    public function getMessageQueue(): FlashMessageQueue
    {
        return $this->messageQueue;
    }

    /**
     * Get all status information as array with status objects
     *
     * @return FlashMessageQueue
     */
    public function getStatus(): FlashMessageQueue
    {
        return $this->messageQueue;
    }

    /**
     * Validate the database name
     *
     * @param string $databaseName
     * @return bool
     */
    public static function isValidDatabaseName(string $databaseName): bool
    {
        return strlen($databaseName) <= 50 && preg_match('/^[a-zA-Z0-9\$_]*$/', $databaseName);
    }

    protected function checkDatabaseName(Connection $connection): void
    {
        if (static::isValidDatabaseName($connection->getDatabase())) {
            return;
        }

        $this->messageQueue->enqueue(
            new FlashMessage(
                'Given database name must be shorter than fifty characters'
                . ' and consist solely of basic latin letters (a-z), digits (0-9), dollar signs ($)'
                . ' and underscores (_).',
                'Database name not valid',
                FlashMessage::ERROR
            )
        );
    }
}
