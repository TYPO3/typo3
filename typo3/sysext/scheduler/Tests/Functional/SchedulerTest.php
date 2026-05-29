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

    private function getSingleAfterTaskExecutionEvent(): AfterTaskExecutionEvent
    {
        $events = array_values(array_filter(
            $this->dispatchedEvents,
            fn(object $e) => $e instanceof AfterTaskExecutionEvent
        ));
        return $events[0];
    }
}
