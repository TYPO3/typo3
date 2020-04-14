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

namespace TYPO3\CMS\Install\SystemEnvironment\DatabaseCheck\Driver;

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;

/**
 * Check database configuration status for MySQLi driver
 *
 * This class is a hardcoded requirement check for the database driver.
 *
 * The status messages and title *must not* include HTML, use plain
 * text only. The return values of this class are not bound to HTML
 * and can be used in different scopes (eg. as json array).
 *
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class Mysqli extends AbstractDriver
{
    /**
     * Get all status information as array with status objects
     *
     * @return FlashMessageQueue
     */
    public function getStatus(): FlashMessageQueue
    {
        $this->checkPhpExtensions('mysqli');
        $this->checkMysqliReconnectSetting();

        return $this->getMessageQueue();
    }

    /**
     * Verify that mysqli.reconnect is set to 0 in order to avoid improper reconnects
     */
    public function checkMysqliReconnectSetting()
    {
        $currentMysqliReconnectSetting = ini_get('mysqli.reconnect');
        if ($currentMysqliReconnectSetting === '1') {
            $this->getMessageQueue()->enqueue(new FlashMessage(
                'mysqli.reconnect=1' . LF
                . 'PHP is configured to automatically reconnect the database connection on disconnection.' . LF
                . ' Warning: If (e.g. during a long-running task) the connection is dropped and automatically reconnected, '
                . ' it may not be reinitialized properly (e.g. charset) and write mangled data to the database!',
                'PHP mysqli.reconnect is enabled',
                FlashMessage::ERROR
            ));
        } else {
            $this->getMessageQueue()->enqueue(new FlashMessage(
                '',
                'PHP mysqli.reconnect is fine'
            ));
        }
    }
}
