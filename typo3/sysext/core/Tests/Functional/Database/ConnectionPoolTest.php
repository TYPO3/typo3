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

namespace TYPO3\CMS\Core\Tests\Functional\Database;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Tests\Functional\Database\Fixtures\DriverMiddlewares\DropForConnectionNamedSecondTestDriverMiddleware;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ConnectionPoolTest extends FunctionalTestCase
{
    #[Test]
    public function getOrderedConnectionDriverMiddlewareConfigurationDiscardsUnsuitableMiddlewaresImplementingUsableForConnectionInterface(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['second'] = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'];
        $GLOBALS['TYPO3_CONF_VARS']['DB']['globalDriverMiddlewares']['typo3tests/discard-for-second-connection'] = [
            'target' => DropForConnectionNamedSecondTestDriverMiddleware::class,
            'after' => [
                'typo3/core/custom-platform-driver-middleware',
            ],
        ];
        $testConnectionPool = new class () extends ConnectionPool {
            public function callGetOrderedConnectionDriverMiddlewareConfiguration(string $connectionName): array
            {
                return $this->getOrderedConnectionDriverMiddlewareConfiguration($connectionName, $this->getConnectionParams($connectionName));
            }
        };

        // cross-check that global test DriverMiddleware stays on default connection
        $driverMiddlewaresDefault = $testConnectionPool->callGetOrderedConnectionDriverMiddlewareConfiguration('Default');
        self::assertArrayHasKey('typo3/core/custom-platform-driver-middleware', $driverMiddlewaresDefault);
        self::assertArrayHasKey('typo3tests/discard-for-second-connection', $driverMiddlewaresDefault);

        // check that DriverMiddleware has been discarded for second connection
        $driverMiddlewaresSecond = $testConnectionPool->callGetOrderedConnectionDriverMiddlewareConfiguration('second');
        self::assertArrayHasKey('typo3/core/custom-platform-driver-middleware', $driverMiddlewaresSecond);
        self::assertArrayNotHasKey('typo3tests/discard-for-second-connection', $driverMiddlewaresSecond);
    }
}
