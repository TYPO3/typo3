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

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ConsumedByWorkerStamp;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messenger\Event\BeforeMessengerDoctrineCloseEvent;
use TYPO3\CMS\Core\Messenger\Middleware\DoctrineCloseConnectionMiddleware;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DoctrineCloseConnectionMiddlewareTest extends UnitTestCase
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

    private function connection(bool $sqlite = false): Connection&MockObject
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn(
            $sqlite ? $this->createMock(SQLitePlatform::class) : $this->createMock(AbstractPlatform::class),
        );
        return $connection;
    }

    private function connectionPoolWith(Connection $connection): ConnectionPool&MockObject
    {
        $connectionPool = $this->createMock(ConnectionPool::class);
        $connectionPool->method('getOpenConnectionNames')->willReturn(['Default']);
        $connectionPool->method('getConnectionByName')->willReturnMap([['Default', $connection]]);
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
        $connection->expects($this->never())->method('close');
        $envelope = new Envelope(new \stdClass());

        $middleware = new DoctrineCloseConnectionMiddleware($this->connectionPoolWith($connection), $this->passThroughDispatcher());
        $middleware->handle($envelope, $this->stackReturning(static fn(Envelope $e): Envelope => $e));
    }

    #[Test]
    public function closesOpenConnectionAfterHandling(): void
    {
        $connection = $this->connection();
        $connection->expects($this->once())->method('close');
        $envelope = new Envelope(new \stdClass(), [new ConsumedByWorkerStamp()]);

        $middleware = new DoctrineCloseConnectionMiddleware($this->connectionPoolWith($connection), $this->passThroughDispatcher());
        $middleware->handle($envelope, $this->stackReturning(static fn(Envelope $e): Envelope => $e));
    }

    #[Test]
    public function closesOpenConnectionEvenWhenHandlingThrows(): void
    {
        $connection = $this->connection();
        $connection->expects($this->once())->method('close');
        $envelope = new Envelope(new \stdClass(), [new ConsumedByWorkerStamp()]);

        $middleware = new DoctrineCloseConnectionMiddleware($this->connectionPoolWith($connection), $this->passThroughDispatcher());
        $this->expectException(\RuntimeException::class);
        $middleware->handle($envelope, $this->stackReturning(static function (): Envelope {
            throw new \RuntimeException('handling failed', 1721400000);
        }));
    }

    #[Test]
    public function skipEventPreventsClose(): void
    {
        $connection = $this->connection();
        $connection->expects($this->never())->method('close');
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(static function (object $event): object {
            self::assertInstanceOf(BeforeMessengerDoctrineCloseEvent::class, $event);
            $event->skip();
            return $event;
        });
        $envelope = new Envelope(new \stdClass(), [new ConsumedByWorkerStamp()]);

        $middleware = new DoctrineCloseConnectionMiddleware($this->connectionPoolWith($connection), $dispatcher);
        $middleware->handle($envelope, $this->stackReturning(static fn(Envelope $e): Envelope => $e));
    }

    #[Test]
    public function sqliteConnectionIsNotClosed(): void
    {
        $connection = $this->connection(sqlite: true);
        $connection->expects($this->never())->method('close');
        // The event is not even dispatched for a skipped (SQLite) connection.
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->never())->method('dispatch');
        $envelope = new Envelope(new \stdClass(), [new ConsumedByWorkerStamp()]);

        $middleware = new DoctrineCloseConnectionMiddleware($this->connectionPoolWith($connection), $dispatcher);
        $middleware->handle($envelope, $this->stackReturning(static fn(Envelope $e): Envelope => $e));
    }
}
