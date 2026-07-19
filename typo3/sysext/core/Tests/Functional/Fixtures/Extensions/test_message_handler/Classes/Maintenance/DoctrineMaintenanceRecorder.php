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

namespace TYPO3Tests\TestMessageHandler\Maintenance;

/**
 * Static collector so the functional test can observe which connections the
 * messenger doctrine middlewares pinged / closed, and how often the message was
 * handled - without having to share a service instance with the listeners.
 */
final class DoctrineMaintenanceRecorder
{
    /** @var list<string> */
    public static array $pingedConnections = [];

    /** @var list<string> */
    public static array $closedConnections = [];

    public static int $handledCount = 0;

    public static function reset(): void
    {
        self::$pingedConnections = [];
        self::$closedConnections = [];
        self::$handledCount = 0;
    }
}
