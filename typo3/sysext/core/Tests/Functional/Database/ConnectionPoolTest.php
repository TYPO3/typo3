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
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Tests\Functional\Database\Fixtures\DriverMiddlewares\DropForConnectionNamedSecondTestDriverMiddleware;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ConnectionPoolTest extends FunctionalTestCase
{
    #[Test]
    public function getConnectionNamesReturnsConfiguredConnectionNames(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'] = [
            'Default' => [
                'aConfigDetail' => '',
            ],
            'klaus' => [
                'anotherConfigDetail' => '',
            ],
        ];
        self::assertSame(['Default', 'klaus'], $this->get(ConnectionPool::class)->getConnectionNames());
    }

    #[Test]
    public function getConnectionParamsParsesUrlDSN(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'] = [
            'Default' => [
                'url' => 'mysqli://user:password@host:3306/database',
            ],
        ];
        $connectionPool = $this->get(ConnectionPool::class);
        $subjectMethodReflection = new \ReflectionMethod($connectionPool, 'getConnectionParams');
        self::assertEquals(
            [
                'driver' => 'mysqli',
                'host' => 'host',
                'port' => 3306,
                'user' => 'user',
                'password' => 'password',
                'dbname' => 'database',
                'wrapperClass' => Connection::class,
                'charset' => 'utf8',
            ],
            $subjectMethodReflection->invoke($connectionPool, 'Default')
        );
    }

    #[Test]
    public function getConnectionParamsParsesUrlDSNAndOverridesParams(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'] = [
            'Default' => [
                'url' => 'mysqli://user:password@host:3306/database',
                'driver' => 'pdo_pgsql',
                'host' => 'foo',
                'port' => 1234,
                'user' => 'bar',
                'password' => 'PAZZW0RD!',
                'dbname' => 'to-be-overriden',
            ],
        ];
        $connectionPool = $this->get(ConnectionPool::class);
        $subjectMethodReflection = new \ReflectionMethod($connectionPool, 'getConnectionParams');
        self::assertEquals(
            [
                'driver' => 'mysqli',
                'host' => 'host',
                'port' => 3306,
                'user' => 'user',
                'password' => 'password',
                'dbname' => 'database',
                'wrapperClass' => Connection::class,
                'charset' => 'utf8',
            ],
            $subjectMethodReflection->invoke($connectionPool, 'Default')
        );
    }

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

        // cross-check that global test DriverMiddleware stays on default connection
        $driverMiddlewaresDefault = $this->callGetOrderedConnectionDriverMiddlewareConfiguration('Default');
        self::assertArrayHasKey('typo3/core/custom-platform-driver-middleware', $driverMiddlewaresDefault);
        self::assertArrayHasKey('typo3tests/discard-for-second-connection', $driverMiddlewaresDefault);

        // check that DriverMiddleware has been discarded for second connection
        $driverMiddlewaresSecond = $this->callGetOrderedConnectionDriverMiddlewareConfiguration('second');
        self::assertArrayHasKey('typo3/core/custom-platform-driver-middleware', $driverMiddlewaresSecond);
        self::assertArrayNotHasKey('typo3tests/discard-for-second-connection', $driverMiddlewaresSecond);
    }

    protected function callGetOrderedConnectionDriverMiddlewareConfiguration(string $connectionName): array
    {
        $connectionPool = $this->get(ConnectionPool::class);
        $getOrderedConnectionDriverMiddlewareConfigurationReflection = new \ReflectionMethod(
            $connectionPool,
            'getOrderedConnectionDriverMiddlewareConfiguration'
        );
        $getConnectionParamsReflection = new \ReflectionMethod(
            $connectionPool,
            'getConnectionParams'
        );
        return $getOrderedConnectionDriverMiddlewareConfigurationReflection->invoke(
            $connectionPool,
            $connectionName,
            $getConnectionParamsReflection->invoke($connectionPool, $connectionName),
        );
    }
}
