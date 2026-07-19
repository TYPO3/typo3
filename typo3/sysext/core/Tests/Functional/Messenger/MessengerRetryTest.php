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

use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\TestMessageHandler\Maintenance\MaintenanceTriggerMessage;

final class MessengerRetryTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_message_handler',
    ];

    private function queueCount(string $queueName): int
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
        return $connection->count('*', 'sys_messenger_messages', ['queue_name' => $queueName]);
    }

    /**
     * @param list<\Symfony\Component\Messenger\Stamp\StampInterface> $stamps
     */
    private function dispatchWorkerFailure(array $stamps = []): void
    {
        $this->get(EventDispatcherInterface::class)->dispatch(new WorkerMessageFailedEvent(
            new Envelope(new MaintenanceTriggerMessage(), $stamps),
            'doctrine',
            new \RuntimeException('handling failed', 1721400000),
        ));
    }

    #[Test]
    public function failedMessageIsRetriedOnItsTransportWhenTheStrategyAllows(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['failure_transport'] = 'failed';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['transports']['doctrine']['retry_strategy'] = [
            'max_retries' => 3,
            'delay' => 1000,
            'multiplier' => 2,
        ];

        $this->dispatchWorkerFailure();

        // Re-sent to its own transport (the "doctrine" transport uses queue_name
        // "default"), and therefore not handed to the failure transport.
        self::assertSame(1, $this->queueCount('default'));
        self::assertSame(0, $this->queueCount('failed'));
    }

    #[Test]
    public function exhaustedRetriesAreHandedToTheFailureTransport(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['failure_transport'] = 'failed';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['transports']['doctrine']['retry_strategy'] = [
            'max_retries' => 1,
        ];

        // Already redelivered once: the retry budget (max_retries = 1) is used up.
        $this->dispatchWorkerFailure([new RedeliveryStamp(1)]);

        self::assertSame(0, $this->queueCount('default'));
        self::assertSame(1, $this->queueCount('failed'));
    }

    #[Test]
    public function messageIsNotRetriedWhenNoStrategyIsConfigured(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['failure_transport'] = 'failed';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['transports'] = [];

        $this->dispatchWorkerFailure();

        // No retry strategy: straight to the failure transport, nothing re-sent.
        self::assertSame(0, $this->queueCount('default'));
        self::assertSame(1, $this->queueCount('failed'));
    }

    #[Test]
    public function recoverableExceptionWithoutForcedRetryHonoursMaxRetries(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['failure_transport'] = 'failed';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['transports']['doctrine']['retry_strategy'] = [
            'max_retries' => 1,
        ];

        $this->get(EventDispatcherInterface::class)->dispatch(new WorkerMessageFailedEvent(
            new Envelope(new MaintenanceTriggerMessage(), [new RedeliveryStamp(1)]),
            'doctrine',
            new RecoverableMessageHandlingException('temporarily failing', 1793480001, forceRetry: false),
        ));

        self::assertSame(0, $this->queueCount('default'));
        self::assertSame(1, $this->queueCount('failed'));
    }
}
