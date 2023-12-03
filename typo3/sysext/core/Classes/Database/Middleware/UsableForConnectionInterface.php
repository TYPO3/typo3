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

use Doctrine\DBAL\Driver\Middleware as DoctrineDriverMiddleware;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Custom driver middleware can implement this interface to decide per connection and
 * connection configuration if it should be used or not. For example, registering a
 * global driver middleware which only takes affect on connections using a specific
 * driver like `pdo_sqlite`.
 *
 * Usually this should be a rare case and mostly a driver middleware can be simply
 * configured as a connection middleware directly, which leaves this more or less
 * a special implementation detail for the TYPO3 core.
 */
interface UsableForConnectionInterface extends DoctrineDriverMiddleware
{
    /**
     * Return true if the driver middleware should be used for the concrete connection.
     *
     * @see ConnectionPool::getDriverMiddlewares()
     */
    public function canBeUsedForConnection(string $identifier, array $connectionParams): bool;
}
