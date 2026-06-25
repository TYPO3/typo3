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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Middleware\CustomPdoDriverResultMiddleware;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class CustomPdoDriverResultMiddlewareTest extends FunctionalTestCase
{
    public static function getValidDrivers(): array
    {
        return [
            ['driver' => 'pdo_sqlite'],
            ['driver' => 'pdo_pgsql'],
            ['driver' => 'pdo_mysql'],
        ];
    }

    #[DataProvider('getValidDrivers')]
    #[Test]
    public function driverMiddlewareIsRegisteredForValidDrivers(string $driver): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['second'] = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'];
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['second']['driver'] = $driver;
        $driverMiddlewares = $this->callGetOrderedConnectionDriverMiddlewareConfiguration('second');
        self::assertArrayHasKey('typo3/core/custom-pdo-driver-result-middleware', $driverMiddlewares);
    }

    #[DataProvider('getValidDrivers')]
    #[Test]
    public function driverMiddlewareIsDiscardedIfDisabled(string $driver): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['second'] = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'];
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['second']['driver'] = $driver;
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['second']['driverMiddlewares'] ??= [];
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['second']['driverMiddlewares']['typo3/core/custom-pdo-driver-result-middleware'] = [
            'disabled' => true,
        ];
        $driverMiddlewares = $this->callGetDriverMiddlewares('second');
        foreach ($driverMiddlewares as $driverMiddleware) {
            self::assertIsObject($driverMiddleware);
            self::assertNotInstanceOf(CustomPdoDriverResultMiddleware::class, $driverMiddleware);
        }
    }

    public static function getInvalidDrivers(): array
    {
        return [
            ['driver' => 'mysqli'],
        ];
    }

    #[DataProvider('getInvalidDrivers')]
    #[Test]
    public function driverMiddlewareIsDiscardedForInvalidDrivers(string $driver): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['second'] = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'];
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['second']['driver'] = $driver;
        $driverMiddlewares = $this->callGetOrderedConnectionDriverMiddlewareConfiguration('second');
        self::assertArrayNotHasKey('typo3/core/custom-pdo-driver-result-middleware', $driverMiddlewares);
    }

    private function callGetOrderedConnectionDriverMiddlewareConfiguration(string $connectionName): array
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

    private function callGetDriverMiddlewares(string $connectionName): array
    {
        $connectionPool = $this->get(ConnectionPool::class);
        $getDriverMiddlewaresReflection = new \ReflectionMethod(
            $connectionPool,
            'getDriverMiddlewares'
        );
        $getConnectionParamsReflection = new \ReflectionMethod(
            $connectionPool,
            'getConnectionParams'
        );
        return $getDriverMiddlewaresReflection->invoke(
            $connectionPool,
            'second',
            $getConnectionParamsReflection->invoke($connectionPool, $connectionName),
        );
    }
}
