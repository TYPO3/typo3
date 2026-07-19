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

namespace TYPO3\CMS\Core\Tests\Functional\Command;

use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\ErrorDetailsStamp;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use TYPO3\CMS\Core\Command\FailedMessagesRetryCommand;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\TestMessageHandler\Failure\PartiallyFailingMessage;
use TYPO3Tests\TestMessageHandler\Failure\PartiallyFailingMessageSucceedingHandler;
use TYPO3Tests\TestMessageHandler\Failure\RetryFailingMessage;
use TYPO3Tests\TestMessageHandler\Maintenance\DoctrineMaintenanceRecorder;
use TYPO3Tests\TestMessageHandler\Maintenance\MaintenanceTriggerMessage;

final class MessengerFailedRetryCommandTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_message_handler',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['failure_transport'] = 'failed';
        DoctrineMaintenanceRecorder::reset();
        PartiallyFailingMessageSucceedingHandler::$handledCount = 0;
    }

    private function connection(): Connection
    {
        return $this->get(ConnectionPool::class)->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
    }

    private function park(object $message): void
    {
        $this->parkEnvelope(new Envelope($message), new \RuntimeException('handling failed', 1721400000));
    }

    private function parkEnvelope(Envelope $envelope, \Throwable $throwable): void
    {
        $this->get(EventDispatcherInterface::class)->dispatch(new WorkerMessageFailedEvent($envelope, 'default', $throwable));
    }

    private function firstFailedEnvelope(): Envelope
    {
        $row = $this->connection()
            ->select(['body'], 'sys_messenger_messages', ['queue_name' => 'failed'])
            ->fetchAssociative();
        return new PhpSerializer()->decode(['body' => $row['body']]);
    }

    private function failedCount(): int
    {
        return $this->connection()->count('*', 'sys_messenger_messages', ['queue_name' => 'failed']);
    }

    private function firstFailedId(): string
    {
        return (string)$this->connection()
            ->select(['id'], 'sys_messenger_messages', ['queue_name' => 'failed'])
            ->fetchOne();
    }

    #[Test]
    public function retrySuccessRunsTheHandlerAndRemovesTheMessage(): void
    {
        $this->park(new MaintenanceTriggerMessage());
        $id = $this->firstFailedId();

        $tester = new CommandTester($this->get(FailedMessagesRetryCommand::class));
        $tester->execute(['id' => [$id], '--force' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertSame(1, DoctrineMaintenanceRecorder::$handledCount);
        self::assertSame(0, $this->failedCount());
    }

    #[Test]
    public function retryThatFailsAgainKeepsTheMessageParked(): void
    {
        $this->park(new RetryFailingMessage());
        $id = $this->firstFailedId();

        $tester = new CommandTester($this->get(FailedMessagesRetryCommand::class));
        $tester->execute(['id' => [$id], '--force' => true]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('failed again', $tester->getDisplay());
        self::assertSame(1, $this->failedCount());
        $envelope = $this->firstFailedEnvelope();
        self::assertSame('permanently failing handler', $envelope->last(ErrorDetailsStamp::class)?->getExceptionMessage());
        self::assertSame(1, $envelope->last(RedeliveryStamp::class)?->getRetryCount());
    }

    #[Test]
    public function retryAllWithForceRetriesEveryPendingMessage(): void
    {
        $this->park(new MaintenanceTriggerMessage());
        $this->park(new MaintenanceTriggerMessage());
        self::assertSame(2, $this->failedCount());

        $tester = new CommandTester($this->get(FailedMessagesRetryCommand::class));
        $tester->execute(['--force' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertSame(2, DoctrineMaintenanceRecorder::$handledCount);
        self::assertSame(0, $this->failedCount());
    }

    #[Test]
    public function retryFailsForAnUnknownId(): void
    {
        $tester = new CommandTester($this->get(FailedMessagesRetryCommand::class));
        $tester->execute(['id' => ['999999'], '--force' => true]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('was not found', $tester->getDisplay());
    }

    #[Test]
    public function retrySkipsHandlersThatSucceededBefore(): void
    {
        try {
            $this->get(MessageBusInterface::class)->dispatch(new PartiallyFailingMessage(), [new ReceivedStamp('default')]);
            self::fail('The failing handler must throw.');
        } catch (HandlerFailedException $exception) {
            $this->parkEnvelope($exception->getEnvelope(), $exception);
        }
        self::assertSame(1, PartiallyFailingMessageSucceedingHandler::$handledCount);

        $tester = new CommandTester($this->get(FailedMessagesRetryCommand::class));
        $tester->execute(['id' => [$this->firstFailedId()], '--force' => true]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertSame(1, PartiallyFailingMessageSucceedingHandler::$handledCount);
        self::assertSame(1, $this->failedCount());
    }

    #[Test]
    public function retryCommandIsRegisteredButNotOfferedAsSchedulerTask(): void
    {
        $commandRegistry = $this->get(CommandRegistry::class);

        self::assertTrue($commandRegistry->has('messenger:failed:retry'));
        self::assertArrayNotHasKey('messenger:failed:retry', $commandRegistry->getSchedulableCommandsConfiguration());
    }
}
