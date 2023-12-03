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

namespace TYPO3\CMS\Core\Database\Middleware;

use Doctrine\DBAL\Driver as DoctrineDriver;
use Doctrine\DBAL\Driver\Middleware as DoctrineDriverMiddleware;
use TYPO3\CMS\Core\Database\Driver\CustomPdoResultDriverDecorator;

/**
 * The `php-ext PDO` based pdo_* driver can return column data in query result sets as type `resource`, where the
 * `mysqli` based driver returns type `string` instead. Dealing with data of type `resources`, for special driver,
 * is not a well known technical detail in the broader php and TYPO3 developer community. Therefore, the TYPO3 core
 * provided custom pdo_* driver implementation to provide a specific `DriverResult` class, which resolves this issue
 * by converting type `resource` column data directly to string in `\TYPO3\CMS\Core\Database\Driver\DriverResult`
 * within the method `mapResourceToString()` and uses it in the related methods.
 *
 * With the Doctrine DBAL Driver Middleware features the custom drivers could be reduced and the required DriverResult
 * set added in a cleaner way. As this comes with minor performance impact, the custom DriverResult set needs to be
 * plumbed only to the absolutely required drivers - and the reason for the conditional usage restricted with method
 * `canBeUsedForConnection()`.
 *
 * @see \TYPO3\CMS\Core\Database\Driver\DriverResult::mapResourceToString()
 *
 * @internal this implementation is not part of TYPO3's Public API.
 */
final class CustomPdoDriverResultMiddleware implements DoctrineDriverMiddleware, UsableForConnectionInterface
{
    public function wrap(DoctrineDriver $driver): DoctrineDriver
    {
        return new CustomPdoResultDriverDecorator($driver);
    }

    public function canBeUsedForConnection(string $identifier, array $connectionParams): bool
    {
        return match ($connectionParams['driver'] ?? '') {
            'pdo_sqlite', 'pdo_pgsql', 'pdo_mysql' => true,
            default => false,
        };
    }
}
