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
use TYPO3\CMS\Core\Command\FailedMessagesRemoveCommand;
use TYPO3\CMS\Core\Command\FailedMessagesShowCommand;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\TestMessageHandler\Maintenance\MaintenanceTriggerMessage;

final class MessengerFailedMessagesCommandsTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_message_handler',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['failure_transport'] = 'failed';
    }

    private function connection(): \TYPO3\CMS\Core\Database\Connection
    {
        return $this->get(ConnectionPool::class)->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
    }

    private function parkFailedMessage(): void
    {
        $this->get(EventDispatcherInterface::class)->dispatch(new WorkerMessageFailedEvent(
            new Envelope(new MaintenanceTriggerMessage()),
            'default',
            new \RuntimeException('handling failed', 1721400000),
        ));
    }

    private function failedCount(): int
    {
        return $this->connection()->count('*', 'sys_messenger_messages', ['queue_name' => 'failed']);
    }

    #[Test]
    public function showListsParkedMessages(): void
    {
        $this->parkFailedMessage();

        $tester = new CommandTester($this->get(FailedMessagesShowCommand::class));
        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString(MaintenanceTriggerMessage::class, $tester->getDisplay());
    }

    #[Test]
    public function removeDeletesAParkedMessage(): void
    {
        $this->parkFailedMessage();
        self::assertSame(1, $this->failedCount());

        $id = (int)$this->connection()->select(['id'], 'sys_messenger_messages', ['queue_name' => 'failed'])
            ->fetchOne();

        $tester = new CommandTester($this->get(FailedMessagesRemoveCommand::class));
        $tester->execute(['id' => [(string)$id], '--force' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertSame(0, $this->failedCount());
    }

    #[Test]
    public function commandsAreRegisteredButNotOfferedAsSchedulerTasks(): void
    {
        $commandRegistry = $this->get(CommandRegistry::class);
        $schedulable = $commandRegistry->getSchedulableCommandsConfiguration();

        self::assertTrue($commandRegistry->has('messenger:failed:show'));
        self::assertTrue($commandRegistry->has('messenger:failed:remove'));
        self::assertArrayNotHasKey('messenger:failed:show', $schedulable);
        self::assertArrayNotHasKey('messenger:failed:remove', $schedulable);
    }
}
