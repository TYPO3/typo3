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

namespace TYPO3\CMS\Core\Tests\Unit\Messenger\Middleware;

use Doctrine\DBAL\Exception as DoctrineDBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ConsumedByWorkerStamp;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messenger\Event\BeforeMessengerDoctrinePingEvent;
use TYPO3\CMS\Core\Messenger\Middleware\DoctrinePingConnectionMiddleware;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DoctrinePingConnectionMiddlewareTest extends UnitTestCase
{
    /**
     * @param callable(Envelope): Envelope $next
     */
    private function stackReturning(callable $next): StackInterface
    {
        $nextMiddleware = $this->createMock(MiddlewareInterface::class);
        $nextMiddleware->method('handle')->willReturnCallback($next);
        $stack = $this->createMock(StackInterface::class);
        $stack->method('next')->willReturn($nextMiddleware);
        return $stack;
    }

    /**
     * A "Default" connection whose dummy-select ping optionally throws on its first
     * call (to simulate a stale connection).
     */
    private function connection(?\Throwable $firstPingThrows = null): Connection&MockObject
    {
        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('getDummySelectSQL')->willReturn('SELECT 1');
        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $calls = 0;
        $connection->method('executeQuery')->willReturnCallback(function () use (&$calls, $firstPingThrows): Result {
            $calls++;
            if ($calls === 1 && $firstPingThrows !== null) {
                throw $firstPingThrows;
            }
            return $this->createMock(Result::class);
        });
        return $connection;
    }

    private function connectionPoolWith(Connection $connection): ConnectionPool&MockObject
    {
        $connectionPool = $this->createMock(ConnectionPool::class);
        $connectionPool->method('getOpenConnectionNames')->willReturn(['Default']);
        $connectionPool->method('getConnectionByName')->with('Default')->willReturn($connection);
        return $connectionPool;
    }

    private function passThroughDispatcher(): EventDispatcherInterface
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnArgument(0);
        return $dispatcher;
    }

    #[Test]
    public function passesThroughWithoutConsumedByWorkerStamp(): void
    {
        $connection = $this->connection();
        $connection->expects($this->never())->method('executeQuery');
        $handled = false;
        $envelope = new Envelope(new \stdClass());

        $middleware = new DoctrinePingConnectionMiddleware($this->connectionPoolWith($connection), $this->passThroughDispatcher(), new NullLogger());
        $middleware->handle($envelope, $this->stackReturning(function (Envelope $e) use (&$handled): Envelope {
            $handled = true;
            return $e;
        }));

        self::assertTrue($handled);
    }

    #[Test]
    public function pingsOpenConnectionWhenConsumedByWorker(): void
    {
        $connection = $this->connection();
        $connection->expects($this->once())->method('executeQuery');
        $connection->expects($this->never())->method('close');
        $envelope = new Envelope(new \stdClass(), [new ConsumedByWorkerStamp()]);

        $middleware = new DoctrinePingConnectionMiddleware($this->connectionPoolWith($connection), $this->passThroughDispatcher(), new NullLogger());
        $middleware->handle($envelope, $this->stackReturning(static fn(Envelope $e): Envelope => $e));
    }

    #[Test]
    public function reconnectsStaleConnection(): void
    {
        $staleException = new class ('gone away') extends \RuntimeException implements DoctrineDBALException {};
        $connection = $this->connection($staleException);
        // First dummy select throws, connection is closed, second dummy select succeeds.
        $connection->expects($this->exactly(2))->method('executeQuery');
        $connection->expects($this->once())->method('close');
        $envelope = new Envelope(new \stdClass(), [new ConsumedByWorkerStamp()]);

        $middleware = new DoctrinePingConnectionMiddleware($this->connectionPoolWith($connection), $this->passThroughDispatcher(), new NullLogger());
        $middleware->handle($envelope, $this->stackReturning(static fn(Envelope $e): Envelope => $e));
    }

    #[Test]
    public function skipEventPreventsPing(): void
    {
        $connection = $this->connection();
        $connection->expects($this->never())->method('executeQuery');
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(static function (object $event): object {
            self::assertInstanceOf(BeforeMessengerDoctrinePingEvent::class, $event);
            $event->skip();
            return $event;
        });
        $envelope = new Envelope(new \stdClass(), [new ConsumedByWorkerStamp()]);

        $middleware = new DoctrinePingConnectionMiddleware($this->connectionPoolWith($connection), $dispatcher, new NullLogger());
        $middleware->handle($envelope, $this->stackReturning(static fn(Envelope $e): Envelope => $e));
    }

    #[Test]
    public function sqliteConnectionIsNotPinged(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($this->createMock(SQLitePlatform::class));
        $connection->expects($this->never())->method('executeQuery');
        // The event is not even dispatched for a skipped (SQLite) connection.
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->never())->method('dispatch');
        $envelope = new Envelope(new \stdClass(), [new ConsumedByWorkerStamp()]);

        $middleware = new DoctrinePingConnectionMiddleware($this->connectionPoolWith($connection), $dispatcher, new NullLogger());
        $middleware->handle($envelope, $this->stackReturning(static fn(Envelope $e): Envelope => $e));
    }
}
