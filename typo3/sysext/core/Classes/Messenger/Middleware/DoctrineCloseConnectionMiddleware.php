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

use Doctrine\DBAL\Platforms\SQLitePlatform;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ConsumedByWorkerStamp;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messenger\Event\BeforeMessengerDoctrineCloseEvent;

/**
 * Closes the open database connections after a worker handled a received message,
 * so the next message starts with a fresh connection in long-running
 * "messenger:consume" workers.
 *
 * Only messages consumed asynchronously by a worker (carrying a
 * ConsumedByWorkerStamp) are handled; a synchronous dispatch never touches
 * connections. Embedded SQLite connections are skipped (closing them is pointless
 * and, for an in-memory database, would discard all data). Connections are closed
 * even when handling throws, and individual connections can be excluded via
 * BeforeMessengerDoctrineCloseEvent.
 *
 * @internal not part of TYPO3 Core API
 */
final readonly class DoctrineCloseConnectionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ConnectionPool $connectionPool,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        if ($envelope->last(ConsumedByWorkerStamp::class) === null) {
            // Only asynchronous consumption by a worker needs connection
            // maintenance; a synchronous dispatch is left untouched.
            return $stack->next()->handle($envelope, $stack);
        }

        try {
            return $stack->next()->handle($envelope, $stack);
        } finally {
            // Resolved after handling, so connections opened while handling the
            // message are closed too; already-open connections are returned from
            // the pool without opening a new one.
            foreach ($this->connectionPool->getOpenConnectionNames() as $connectionName) {
                $connection = $this->connectionPool->getConnectionByName($connectionName);
                if ($connection->getDatabasePlatform() instanceof SQLitePlatform) {
                    // Closing an embedded SQLite connection is pointless and, for
                    // an in-memory database, would discard all data.
                    continue;
                }
                $event = new BeforeMessengerDoctrineCloseEvent($connectionName);
                $this->eventDispatcher->dispatch($event);
                if ($event->shouldSkip()) {
                    continue;
                }
                $connection->close();
            }
        }
    }
}
