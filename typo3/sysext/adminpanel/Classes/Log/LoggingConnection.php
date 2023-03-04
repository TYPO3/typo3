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

namespace TYPO3\CMS\Adminpanel\Log;

use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement as DriverStatement;

/**
 * Part of the Doctrine SQL Logging Driver Adapter
 *
 * @internal
 */
final class LoggingConnection extends AbstractConnectionMiddleware
{
    private DoctrineSqlLogger $logger;

    public function __construct(ConnectionInterface $connection, DoctrineSqlLogger $logger)
    {
        parent::__construct($connection);

        $this->logger = $logger;
    }

    public function prepare(string $sql): DriverStatement
    {
        return new LoggingStatement(parent::prepare($sql), $this->logger, $sql);
    }

    public function query(string $sql): Result
    {
        $this->logger->startQuery($sql);
        $query = parent::query($sql);
        $this->logger->stopQuery();

        return $query;
    }

    public function exec(string $sql): int
    {
        $this->logger->startQuery($sql);
        $query = parent::exec($sql);
        $this->logger->stopQuery();

        return $query;
    }
}
