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

namespace TYPO3\CMS\Core\Tests\Functional\Database\Fixtures\DriverMiddlewares;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;
use TYPO3\CMS\Core\Database\Middleware\UsableForConnectionInterface;

final class DropForConnectionNamedSecondTestDriverMiddleware implements Middleware, UsableForConnectionInterface
{
    public function wrap(Driver $driver): Driver
    {
        // noop - not decorating (wrapping) the driver
        return $driver;
    }

    public function canBeUsedForConnection(string $identifier, array $connectionParams): bool
    {
        return $identifier !== 'second';
    }
}
