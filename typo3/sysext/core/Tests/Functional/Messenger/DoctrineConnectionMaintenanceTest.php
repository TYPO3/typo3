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

namespace TYPO3\CMS\Core\Tests\Functional\Messenger;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\ConsumedByWorkerStamp;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\TestMessageHandler\Maintenance\DoctrineMaintenanceRecorder;
use TYPO3Tests\TestMessageHandler\Maintenance\MaintenanceTriggerMessage;

final class DoctrineConnectionMaintenanceTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_message_handler',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        DoctrineMaintenanceRecorder::reset();
    }

    /**
     * Establishes the "Default" connection so the maintenance middlewares have an
     * open connection to act on.
     */
    private function openDefaultConnection(): Connection
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
        $connection->executeQuery($connection->getDatabasePlatform()->getDummySelectSQL())->free();
        self::assertTrue($connection->isConnected());
        return $connection;
    }

    #[Test]
    #[Group('not-sqlite')]
    public function openConnectionsArePingedBeforeAndClosedAfterAWorkerConsumedMessage(): void
    {
        $connection = $this->openDefaultConnection();

        $this->get(MessageBusInterface::class)->dispatch(new Envelope(new MaintenanceTriggerMessage(), [
            new ReceivedStamp('test'),
            new ConsumedByWorkerStamp(),
        ]));

        self::assertSame(1, DoctrineMaintenanceRecorder::$handledCount);
        self::assertContains(ConnectionPool::DEFAULT_CONNECTION_NAME, DoctrineMaintenanceRecorder::$pingedConnections);
        self::assertContains(ConnectionPool::DEFAULT_CONNECTION_NAME, DoctrineMaintenanceRecorder::$closedConnections);
        // The close middleware actually closed the connection after handling.
        self::assertFalse($connection->isConnected());
    }

    #[Test]
    #[Group('not-mariadb')]
    #[Group('not-mysql')]
    #[Group('not-postgres')]
    public function sqliteConnectionsAreNotMaintainedForAWorkerConsumedMessage(): void
    {
        $connection = $this->openDefaultConnection();

        $this->get(MessageBusInterface::class)->dispatch(new Envelope(new MaintenanceTriggerMessage(), [
            new ReceivedStamp('test'),
            new ConsumedByWorkerStamp(),
        ]));

        self::assertSame(1, DoctrineMaintenanceRecorder::$handledCount);
        // Embedded SQLite is skipped: no ping/close events, connection stays open
        // (closing an in-memory database would discard all data).
        self::assertSame([], DoctrineMaintenanceRecorder::$pingedConnections);
        self::assertSame([], DoctrineMaintenanceRecorder::$closedConnections);
        self::assertTrue($connection->isConnected());
    }

    #[Test]
    public function connectionsAreLeftUntouchedForSynchronouslyHandledMessage(): void
    {
        $connection = $this->openDefaultConnection();

        // No ConsumedByWorkerStamp: synchronous handling, the middlewares stay inert.
        $this->get(MessageBusInterface::class)->dispatch(new Envelope(new MaintenanceTriggerMessage(), [
            new ReceivedStamp('test'),
        ]));

        self::assertSame(1, DoctrineMaintenanceRecorder::$handledCount);
        self::assertSame([], DoctrineMaintenanceRecorder::$pingedConnections);
        self::assertSame([], DoctrineMaintenanceRecorder::$closedConnections);
        self::assertTrue($connection->isConnected());
    }
}
