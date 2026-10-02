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

namespace TYPO3\CMS\Scheduler\Tests\Functional\Domain\Repository;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Scheduler\Domain\Repository\SchedulerTaskRepository;
use TYPO3\CMS\Scheduler\Execution;
use TYPO3\CMS\Scheduler\Service\TaskService;
use TYPO3\CMS\Scheduler\Task\ExecuteSchedulableCommandTask;
use TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask;
use TYPO3\CMS\Scheduler\Task\TaskStatus;
use TYPO3\CMS\Scheduler\Tests\Functional\Task\Fixtures\SmallBatchTableGarbageCollectionTask;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SchedulerTaskRepositoryTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'scheduler',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/FindNextExecutableTask.csv');
    }

    #[Test]
    public function addPersistsTaskParameters(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][SmallBatchTableGarbageCollectionTask::class] = [
            'extension' => 'scheduler',
            'title' => 'Small Batch Table Garbage Collection',
            'description' => 'Test fixture',
        ];

        $subject = $this->get(SchedulerTaskRepository::class);

        $task = new SmallBatchTableGarbageCollectionTask();
        $task->setDescription('My test task');
        $task->setExecution(Execution::createSingleExecution(1234567890));
        $task->setTaskParameters([
            'all_tables' => false,
            'selected_tables' => 'sys_log',
            'number_of_days' => 30,
        ]);

        self::assertTrue($subject->add($task));
        self::assertGreaterThan(0, $task->getTaskUid());

        $row = $this->getConnectionPool()
            ->getConnectionForTable('tx_scheduler_task')
            ->fetchAssociative('SELECT * FROM tx_scheduler_task WHERE uid = ?', [$task->getTaskUid()]);

        self::assertNotFalse($row);
        self::assertSame(SmallBatchTableGarbageCollectionTask::class, $row['tasktype']);
        self::assertSame('My test task', $row['description']);
        self::assertSame([
            'all_tables' => false,
            'number_of_days' => 30,
            'selected_tables' => 'sys_log',
        ], json_decode($row['parameters'], true, 512, JSON_THROW_ON_ERROR));
    }

    #[Test]
    public function addPersistsNativeTaskParameters(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $subject = $this->get(SchedulerTaskRepository::class);

        $task = new TableGarbageCollectionTask();
        $task->setDescription('My native test task');
        $task->setExecution(Execution::createSingleExecution(1234567890));
        $task->setTaskParameters([
            'all_tables' => false,
            'selected_tables' => 'sys_log',
            'number_of_days' => 30,
        ]);

        self::assertTrue($subject->add($task));
        self::assertGreaterThan(0, $task->getTaskUid());

        $row = $this->getConnectionPool()
            ->getConnectionForTable('tx_scheduler_task')
            ->fetchAssociative('SELECT * FROM tx_scheduler_task WHERE uid = ?', [$task->getTaskUid()]);

        self::assertNotFalse($row);
        self::assertSame(TableGarbageCollectionTask::class, $row['tasktype']);
        self::assertSame('My native test task', $row['description']);
        self::assertSame(0, (int)$row['all_tables']);
        self::assertSame('sys_log', $row['selected_tables']);
        self::assertSame(30, (int)$row['number_of_days']);
    }

    #[Test]
    public function addPersistsCommandTaskParameters(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $subject = $this->get(SchedulerTaskRepository::class);

        $task = $this->get(TaskService::class)->createNewTask('referenceindex:update');
        $task->setDescription('My command test task');
        $task->setExecution(Execution::createSingleExecution(1234567890));
        $task->setTaskParameters([
            'options' => ['check' => true],
        ]);

        self::assertTrue($subject->add($task));

        $persistedTask = $subject->findByUid($task->getTaskUid());
        self::assertInstanceOf(ExecuteSchedulableCommandTask::class, $persistedTask);
        self::assertTrue($persistedTask->getOptions()['check']);
        self::assertTrue($persistedTask->getOptionValues()['check']);
    }

    #[Test]
    public function updatePersistsNativeTaskParameters(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $subject = $this->get(SchedulerTaskRepository::class);

        $task = new TableGarbageCollectionTask();
        $task->setExecution(Execution::createSingleExecution(1234567890));
        $task->setTaskParameters([
            'all_tables' => false,
            'selected_tables' => 'sys_log',
            'number_of_days' => 30,
        ]);
        self::assertTrue($subject->add($task));

        $task->setTaskParameters([
            'all_tables' => false,
            'selected_tables' => 'sys_log',
            'number_of_days' => 99,
        ]);
        self::assertTrue($subject->update($task));

        $row = $this->getConnectionPool()
            ->getConnectionForTable('tx_scheduler_task')
            ->fetchAssociative('SELECT number_of_days FROM tx_scheduler_task WHERE uid = ?', [$task->getTaskUid()]);
        self::assertSame(99, (int)$row['number_of_days']);
    }

    #[Test]
    public function findNextExecutableTaskReturnsByPriorityDescFirst(): void
    {
        // All fixture tasks have nextexecution in the past relative to this value
        $GLOBALS['EXEC_TIME'] = 9999999999;

        $subject = $this->get(SchedulerTaskRepository::class);

        $task = $subject->findNextExecutableTask();

        // Task 3 has priority=150 (High) and should win over tasks 1 and 2
        self::assertNotNull($task);
        self::assertSame(3, $task->getTaskUid());
    }

    #[Test]
    public function findNextExecutableTaskUsesNextExecutionAsTiebreakerForEqualPriority(): void
    {
        // All fixture tasks have nextexecution in the past relative to this value
        $GLOBALS['EXEC_TIME'] = 9999999999;

        $subject = $this->get(SchedulerTaskRepository::class);

        // Disable task 3 (High priority) so only Regular-priority tasks 2 and 4 compete
        $this->getConnectionPool()
            ->getConnectionForTable('tx_scheduler_task')
            ->update('tx_scheduler_task', ['disable' => 1], ['uid' => 3]);

        $task = $subject->findNextExecutableTask();

        // Task 4 has priority=100 and nextexecution=1500; task 2 has priority=100 and nextexecution=2000
        // Task 4 is more overdue and should be picked first
        self::assertNotNull($task);
        self::assertSame(4, $task->getTaskUid());
    }

    #[Test]
    public function getGroupedTasksResolvesTaskStatuses(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/TaskStatuses.csv');
        // The "late" flag compares nextexecution against the current time; fixtures use
        // 500 (overdue) and 5000000 (future) around this fixed point.
        $this->get(Context::class)->setAspect('date', new DateTimeAspect(new \DateTimeImmutable('@1000000')));

        $groupedTasks = $this->get(SchedulerTaskRepository::class)->getGroupedTasks();

        // Collect the statuses of the fixture tasks (uids 11-16); the tasks imported in
        // setUp() are not relevant here.
        $statusesByTaskUid = [];
        foreach ($groupedTasks['taskGroupsWithTasks'] as $group) {
            foreach ($group['tasks'] as $task) {
                if ($task['uid'] >= 11) {
                    $statusesByTaskUid[$task['uid']] = $task['statuses'];
                }
            }
        }
        ksort($statusesByTaskUid);

        self::assertEquals(
            [
                11 => [
                    new TaskStatus(
                        type: 'late',
                        severity: ContextualFeedbackSeverity::WARNING,
                        state: 'warning',
                        label: 'scheduler.messages:status.late'
                    ),
                ],
                12 => [
                    new TaskStatus(
                        type: 'disabled',
                        severity: ContextualFeedbackSeverity::NOTICE,
                        state: 'disabled',
                        label: 'scheduler.messages:status.disabled'
                    ),
                ],
                13 => [
                    new TaskStatus(
                        type: 'running',
                        severity: ContextualFeedbackSeverity::INFO,
                        state: 'running',
                        label: 'scheduler.messages:status.running'
                    ),
                ],
                14 => [
                    new TaskStatus(
                        type: 'late',
                        severity: ContextualFeedbackSeverity::WARNING,
                        state: 'warning',
                        label: 'scheduler.messages:status.late'
                    ),
                    new TaskStatus(
                        type: 'failure',
                        severity: ContextualFeedbackSeverity::ERROR,
                        state: 'danger',
                        label: 'scheduler.messages:status.failure',
                        message: 'scheduler.messages:msg.executionFailureReport',
                        messageArguments: [1234, 'Boom']
                    ),
                ],
                15 => [
                    new TaskStatus(
                        type: 'disabledByGroup',
                        severity: ContextualFeedbackSeverity::NOTICE,
                        state: 'disabled',
                        label: 'scheduler.messages:status.disabledByGroup'
                    ),
                ],
                16 => [
                    new TaskStatus(
                        type: 'failure',
                        severity: ContextualFeedbackSeverity::ERROR,
                        state: 'default',
                        label: 'scheduler.messages:status.failure',
                        message: 'scheduler.messages:msg.executionFailureDefault'
                    ),
                ],
            ],
            $statusesByTaskUid,
        );
    }
}
