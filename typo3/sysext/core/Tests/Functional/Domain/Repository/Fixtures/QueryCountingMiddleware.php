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

namespace TYPO3\CMS\Core\Tests\Functional\Domain\Repository\Fixtures;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;

/**
 * Counts the statements sent to the database, to test that a query count does not
 * depend on the amount of records.
 */
final class QueryCountingMiddleware implements Middleware
{
    public static int $count = 0;

    public function wrap(Driver $driver): Driver
    {
        return new class ($driver) extends AbstractDriverMiddleware {
            public function connect(array $params): DriverConnection
            {
                return new class (parent::connect($params)) extends AbstractConnectionMiddleware {
                    public function prepare(string $sql): Statement
                    {
                        QueryCountingMiddleware::$count++;
                        return parent::prepare($sql);
                    }

                    public function query(string $sql): Result
                    {
                        QueryCountingMiddleware::$count++;
                        return parent::query($sql);
                    }

                    public function exec(string $sql): int|string
                    {
                        QueryCountingMiddleware::$count++;
                        return parent::exec($sql);
                    }
                };
            }
        };
    }
}
