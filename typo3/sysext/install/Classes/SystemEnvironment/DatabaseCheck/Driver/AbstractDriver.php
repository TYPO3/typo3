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

use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\SystemEnvironment\Check;
use TYPO3\CMS\Install\SystemEnvironment\CheckInterface;

/**
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
abstract class AbstractDriver implements CheckInterface
{
    /**
     * @var FlashMessageQueue
     */
    protected $messageQueue;

    public function __construct()
    {
        $this->messageQueue = new FlashMessageQueue('install-database-check-driver');
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
     * Check the required PHP extensions for this database platform
     * @param string $extension PHP extension name to check
     */
    protected function checkPhpExtensions(string $extension): void
    {
        $systemEnvironmentCheck = GeneralUtility::makeInstance(Check::class);
        $systemEnvironmentCheck->checkPhpExtension($extension);

        foreach ($systemEnvironmentCheck->getMessageQueue() as $message) {
            $this->messageQueue->addMessage($message);
        }
    }
}
