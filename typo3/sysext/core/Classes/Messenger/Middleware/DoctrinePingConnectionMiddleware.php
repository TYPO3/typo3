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

namespace TYPO3\CMS\Core\Messenger\Middleware;

use Doctrine\DBAL\Exception as DoctrineDBALException;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ConsumedByWorkerStamp;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messenger\Event\BeforeMessengerDoctrinePingEvent;

/**
 * Pings the open database connections - and transparently reconnects stale ones -
 * before a worker handles a received message. This avoids "server has gone away"
 * failures in long-running "messenger:consume" workers whose connection went idle
 * between messages.
 *
 * Only messages consumed asynchronously by a worker (carrying a
 * ConsumedByWorkerStamp) are handled; a synchronous dispatch never touches
 * connections. Embedded SQLite connections are skipped (they are not server-based
 * and cannot go stale), and individual connections can be excluded via
 * BeforeMessengerDoctrinePingEvent.
 *
 * @internal not part of TYPO3 Core API
 */
final readonly class DoctrinePingConnectionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ConnectionPool $connectionPool,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger,
    ) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        if ($envelope->last(ConsumedByWorkerStamp::class) === null) {
            // Only asynchronous consumption by a worker needs connection
            // maintenance; a synchronous dispatch is left untouched.
            return $stack->next()->handle($envelope, $stack);
        }

        foreach ($this->connectionPool->getOpenConnectionNames() as $connectionName) {
            $connection = $this->connectionPool->getConnectionByName($connectionName);
            if ($connection->getDatabasePlatform() instanceof SQLitePlatform) {
                // Embedded SQLite connections are not server-based and cannot go
                // stale, so there is nothing to ping.
                continue;
            }
            $event = new BeforeMessengerDoctrinePingEvent($connectionName);
            $this->eventDispatcher->dispatch($event);
            if ($event->shouldSkip()) {
                continue;
            }
            $this->pingConnection($connection, $connectionName);
        }

        return $stack->next()->handle($envelope, $stack);
    }

    private function pingConnection(Connection $connection, string $connectionName): void
    {
        try {
            $this->executeDummySelect($connection);
        } catch (DoctrineDBALException) {
            $this->logger->info(
                'Database connection "{connection}" is stale, reconnecting before handling the message.',
                ['connection' => $connectionName],
            );
            $connection->close();
            // A closed connection reconnects lazily on the next query.
            $this->executeDummySelect($connection);
        }
    }

    private function executeDummySelect(Connection $connection): void
    {
        $connection->executeQuery($connection->getDatabasePlatform()->getDummySelectSQL())->free();
    }
}
