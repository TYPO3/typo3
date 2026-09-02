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

namespace TYPO3\CMS\Scheduler\Tests\Unit\Command;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Scheduler\Command\SchedulerExecuteCommand;
use TYPO3\CMS\Scheduler\Domain\Repository\SchedulerTaskRepository;
use TYPO3\CMS\Scheduler\Scheduler;
use TYPO3\CMS\Scheduler\Service\TaskService;
use TYPO3\CMS\Scheduler\Tests\Unit\Task\Fixtures\TestTask;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SchedulerExecuteCommandTest extends UnitTestCase
{
    #[Test]
    public function explicitlySelectedTaskIsResolvedOnlyOnce(): void
    {
        $task = new TestTask();
        $task->setTaskUid(42);
        $taskRepository = $this->createMock(SchedulerTaskRepository::class);
        $taskRepository->expects($this->once())->method('findByUid')->with(42)->willReturn($task);
        $taskService = $this->createMock(TaskService::class);
        $taskService->expects($this->once())->method('getTaskDetailsFromTask')->with($task)->willReturn(['title' => 'Test task']);
        $scheduler = $this->createMock(Scheduler::class);
        $scheduler->expects($this->once())->method('executeTask')->with($task);
        $subject = new SchedulerExecuteCommand(new Context(), $taskRepository, $taskService, $scheduler);
        $ioProperty = new \ReflectionProperty($subject, 'io');
        $ioProperty->setValue($subject, new SymfonyStyle(new ArrayInput([]), new BufferedOutput()));

        $getTasksToRun = new \ReflectionMethod($subject, 'getTasksToRun');
        $tasks = $getTasksToRun->invoke($subject, [], [42]);
        $runTasks = new \ReflectionMethod($subject, 'runTasks');
        $runTasks->invoke($subject, array_keys($tasks), [], $tasks);
    }
}
