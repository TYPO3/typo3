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

namespace TYPO3\CMS\Core\Tests\Functional\Database\Middleware;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class CustomPlatformDriverMiddlewareTest extends FunctionalTestCase
{
    #[Test]
    public function driverMiddlewareIsRegistered(): void
    {
        $testConnectionPool = new class () extends ConnectionPool {
            public function callGetOrderedConnectionDriverMiddlewareConfiguration(string $connectionName): array
            {
                return $this->getOrderedConnectionDriverMiddlewareConfiguration(
                    $connectionName,
                    $this->getConnectionParams($connectionName)
                );
            }
        };
        $driverMiddlewares = $testConnectionPool->callGetOrderedConnectionDriverMiddlewareConfiguration(ConnectionPool::DEFAULT_CONNECTION_NAME);
        self::assertArrayHasKey('typo3/core/custom-platform-driver-middleware', $driverMiddlewares);
    }
}
