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
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use TYPO3\CMS\Core\Command\ConsumeMessagesCommand;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\TestMessageHandler\Failure\OverrideFailureTransportListener;
use TYPO3Tests\TestMessageHandler\Maintenance\MaintenanceTriggerMessage;

final class FailureTransportTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_message_handler',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        OverrideFailureTransportListener::$overrides = [];
    }

    protected function tearDown(): void
    {
        OverrideFailureTransportListener::$overrides = [];
        parent::tearDown();
    }

    private function queueCount(string $queueName): int
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
        return $connection->count('*', 'sys_messenger_messages', ['queue_name' => $queueName]);
    }

    private function failedMessageCount(): int
    {
        return $this->queueCount('failed');
    }

    private function dispatchWorkerFailure(string $receiverName = 'default'): void
    {
        $this->get(EventDispatcherInterface::class)->dispatch(new WorkerMessageFailedEvent(
            new Envelope(new MaintenanceTriggerMessage()),
            $receiverName,
            new \RuntimeException('handling failed', 1721400000),
        ));
    }

    #[Test]
    public function failedMessageIsParkedInTheFailureTransportWhenConfigured(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['failure_transport'] = 'failed';

        self::assertSame(0, $this->failedMessageCount());
        $this->dispatchWorkerFailure();
        self::assertSame(1, $this->failedMessageCount());
    }

    #[Test]
    public function failedMessageIsNotParkedWhenNoFailureTransportIsConfigured(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['failure_transport'] = '';

        $this->dispatchWorkerFailure();
        self::assertSame(0, $this->failedMessageCount());
    }

    #[Test]
    public function perTransportFailureTransportOverridesTheGlobalOne(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['failure_transport'] = 'failed';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['transports']['doctrine']['failure_transport'] = 'testfailed';

        $this->dispatchWorkerFailure('doctrine');

        self::assertSame(1, $this->queueCount('testfailed'));
        self::assertSame(0, $this->queueCount('failed'));
    }

    #[Test]
    public function transportWithoutOverrideUsesTheGlobalFailureTransport(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['failure_transport'] = 'failed';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['transports']['doctrine']['failure_transport'] = 'testfailed';

        // "default" has no override, so it falls back to the global failure transport.
        $this->dispatchWorkerFailure('default');

        self::assertSame(1, $this->queueCount('failed'));
        self::assertSame(0, $this->queueCount('testfailed'));
    }

    #[Test]
    public function perTransportEmptyFailureTransportDisablesParkingForThatTransport(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['failure_transport'] = 'failed';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['transports']['doctrine']['failure_transport'] = '';

        $this->dispatchWorkerFailure('doctrine');

        self::assertSame(0, $this->queueCount('failed'));
        self::assertSame(0, $this->queueCount('testfailed'));
    }

    #[Test]
    public function resolveFailureTransportEventOverridesTheResolvedTransport(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['failure_transport'] = 'failed';
        OverrideFailureTransportListener::$overrides = ['default' => 'testfailed'];

        $this->dispatchWorkerFailure('default');

        self::assertSame(1, $this->queueCount('testfailed'));
        self::assertSame(0, $this->queueCount('failed'));
    }

    #[Test]
    public function resolveFailureTransportEventCanDisableParkingForAReceiver(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['failure_transport'] = 'failed';
        OverrideFailureTransportListener::$overrides = ['default' => null];

        $this->dispatchWorkerFailure('default');

        self::assertSame(0, $this->queueCount('failed'));
        self::assertSame(0, $this->queueCount('testfailed'));
    }

    #[Test]
    public function failureTransportIsNotConsumedByWorkers(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['failure_transport'] = 'failed';
        $command = $this->get(ConsumeMessagesCommand::class);

        self::assertSame(['doctrine'], $command->getDefinition()->getArgument('receivers')->getDefault());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(1605305002);
        new CommandTester($command)->execute(['receivers' => ['failed']], ['interactive' => false]);
    }

    #[Test]
    public function transportUsedAsFailureTransportOfAnotherOneKeepsItsOwnFailureHandling(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['failure_transport'] = 'failed';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['transports']['default']['failure_transport'] = 'doctrine';

        $this->dispatchWorkerFailure('doctrine');

        self::assertSame(1, $this->failedMessageCount());
    }

    #[Test]
    public function receiverIsNotSentToItselfAsFailureTransport(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['transports']['doctrine']['failure_transport'] = 'doctrine';

        $this->dispatchWorkerFailure('doctrine');

        self::assertSame(0, $this->queueCount('default'));
    }
}
