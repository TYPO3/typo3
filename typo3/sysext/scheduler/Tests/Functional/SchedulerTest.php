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

namespace TYPO3\CMS\Scheduler\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Scheduler\Domain\Repository\SchedulerTaskRepository;
use TYPO3\CMS\Scheduler\Event\AfterTaskExecutionEvent;
use TYPO3\CMS\Scheduler\Scheduler;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Scheduler\Task\TaskSerializer;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SchedulerTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['scheduler'];

    private array $dispatchedEvents;
    private Scheduler $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatchedEvents = [];

        $recordingDispatcher = self::createStub(EventDispatcherInterface::class);
        $recordingDispatcher->method('dispatch')->willReturnCallback(function (object $event): object {
            $this->dispatchedEvents[] = $event;
            return $event;
        });

        $taskRepository = self::createStub(SchedulerTaskRepository::class);
        $taskRepository->method('addExecutionToTask')->willReturn(0);

        $this->subject = new Scheduler(
            new NullLogger(),
            self::createStub(TaskSerializer::class),
            $taskRepository,
            $recordingDispatcher,
            $this->get(Registry::class),
            $this->get(ConnectionPool::class),
            $this->get(ExtensionConfiguration::class),
        );
    }

    #[Test]
    public function executeTaskDispatchesEventWithSuccessOnSuccessfulExecution(): void
    {
        $task = new class extends AbstractTask {
            public function execute(): bool
            {
                return true;
            }
        };

        $this->subject->executeTask($task);

        $event = $this->getSingleAfterTaskExecutionEvent();
        self::assertTrue($event->isSuccess());
        self::assertNull($event->getException());
    }

    #[Test]
    public function executeTaskDispatchesEventWithExceptionOnFailedExecution(): void
    {
        $task = new class extends AbstractTask {
            public function execute(): bool
            {
                throw new \RuntimeException('Task failed intentionally', 1748500000);
            }
        };

        $threw = false;
        try {
            $this->subject->executeTask($task);
        } catch (\Throwable) {
            $threw = true;
        }

        self::assertTrue($threw, 'Exception must propagate after the event is dispatched');
        $event = $this->getSingleAfterTaskExecutionEvent();
        self::assertFalse($event->isSuccess());
        self::assertInstanceOf(\RuntimeException::class, $event->getException());
        self::assertSame(1748500000, $event->getException()->getCode());
    }

    #[Test]
    public function constructorRemovesExecutionsOlderThanDefaultMaximumLifetimeOfOneHour(): void
    {
        $now = $GLOBALS['EXEC_TIME'];
        $this->addTaskWithExecutions(1, [$now - 61 * 60]);
        $this->addTaskWithExecutions(2, [$now - 59 * 60]);

        $this->createScheduler();

        self::assertSame('', $this->getSerializedExecutions(1));
        self::assertSame((string)serialize([$now - 59 * 60]), $this->getSerializedExecutions(2));
    }

    #[Test]
    public function constructorRemovesExecutionsOlderThanConfiguredMaximumLifetime(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['scheduler']['maxLifetime'] = '10';
        $now = $GLOBALS['EXEC_TIME'];
        $this->addTaskWithExecutions(1, [$now - 11 * 60, $now - 9 * 60]);

        $this->createScheduler();

        self::assertSame(serialize([$now - 9 * 60]), $this->getSerializedExecutions(1));
    }

    private function createScheduler(): Scheduler
    {
        return new Scheduler(
            new NullLogger(),
            self::createStub(TaskSerializer::class),
            self::createStub(SchedulerTaskRepository::class),
            self::createStub(EventDispatcherInterface::class),
            $this->get(Registry::class),
            $this->get(ConnectionPool::class),
            $this->get(ExtensionConfiguration::class),
        );
    }

    private function addTaskWithExecutions(int $uid, array $executions): void
    {
        $this->get(ConnectionPool::class)->getConnectionForTable('tx_scheduler_task')->insert(
            'tx_scheduler_task',
            ['uid' => $uid, 'serialized_executions' => serialize($executions)],
            ['serialized_executions' => Connection::PARAM_LOB]
        );
    }

    private function getSerializedExecutions(int $uid): string
    {
        $value = $this->get(ConnectionPool::class)->getConnectionForTable('tx_scheduler_task')
            ->select(['serialized_executions'], 'tx_scheduler_task', ['uid' => $uid])
            ->fetchOne();
        // PostgreSQL returns bytea columns as stream resources
        return is_resource($value) ? (string)stream_get_contents($value) : (string)$value;
    }

    private function getSingleAfterTaskExecutionEvent(): AfterTaskExecutionEvent
    {
        $events = array_values(array_filter(
            $this->dispatchedEvents,
            fn(object $e) => $e instanceof AfterTaskExecutionEvent
        ));
        return $events[0];
    }
}
