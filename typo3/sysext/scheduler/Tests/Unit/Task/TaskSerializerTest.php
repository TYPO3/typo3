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

namespace TYPO3\CMS\Scheduler\Tests\Unit\Task;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\CMS\Scheduler\Service\TaskService;
use TYPO3\CMS\Scheduler\Task\TaskSerializer;
use TYPO3\CMS\Scheduler\Tests\Unit\Task\Fixtures\TestTask;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TaskSerializerTest extends UnitTestCase
{
    public static function dataIsReconstitutedDataProvider(): array
    {
        $parameters = ['any' => [
            'subtypes' => bin2hex(random_bytes(10)),
        ]];
        $testTask = new TestTask();
        $testTask->setTaskUid(13);
        $testTask->setExecutionTime(1743662825);
        $testTask->setTaskParameters($parameters);
        $testTask->setLogger(new NullLogger());

        return [
            'Regular task' => [
                [
                    'uid' => 13,
                    'task_group' => 0,
                    'description' => '',
                    'nextexecution' => 1743662825,
                    'disable' => 0,
                    'execution_details' => '',
                    'tasktype' => get_class($testTask),
                    'parameters' => json_encode($parameters),
                ],
                $testTask,
            ],
        ];
    }

    #[DataProvider('dataIsReconstitutedDataProvider')]
    #[Test]
    public function dataIsReconstituted(array $data, TestTask $expectation): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][TestTask::class] = [
            'extension' => 'scheduler',
        ];
        $container = new Container();
        $container->set(TestTask::class, new TestTask());
        $subject = new TaskSerializer(
            $container,
            new TaskService(),
            $this->createMock(CommandRegistry::class),
        );
        $result = $subject->deserialize($data);
        $result->setLogger(new NullLogger());
        self::assertInstanceOf(TestTask::class, $result);
        self::assertEquals($expectation, $result);
    }

    public static function classNameIsResolvedDataProvider(): array
    {
        $missingTaskData = 'O:29:"TYPO3\CMS\Testing\MissingTask":0:{}';
        $missingTask = unserialize($missingTaskData, ['allowed_classes' => false]);

        return [
            'stdClass' => [
                new \stdClass(),
                \stdClass::class,
            ],
            'MissingTask' => [
                $missingTask,
                'TYPO3\CMS\Testing\MissingTask',
            ],
        ];
    }

    #[DataProvider('classNameIsResolvedDataProvider')]
    #[Test]
    public function classNameIsResolved(?object $task, ?string $expectation): void
    {
        $taskSerializer = new TaskSerializer(
            $this->createMock(ContainerInterface::class),
            $this->createMock(TaskService::class),
            $this->createMock(CommandRegistry::class),
        );
        self::assertSame($expectation, $taskSerializer->resolveClassName($task));
    }

    public static function classNameIsExtractedDataProvider(): array
    {
        return [
            'from object serialization' => [
                'O:29:"TYPO3\CMS\Testing\MissingTask":0:{}',
                'TYPO3\CMS\Testing\MissingTask',
            ],
            'from (invalid) array serialization #1' => [
                'a:1:{i:0;O:29:"TYPO3\CMS\Testing\MissingTask":0:{}}',
                null,
            ],
            'from (invalid) array serialization #2' => [
                'a:1:{s:4:"I-am";s:8:"an-array";}',
                null,
            ],
        ];
    }

    #[DataProvider('classNameIsExtractedDataProvider')]
    #[Test]
    public function classNameIsExtracted(string $serializedTask, ?string $expectation): void
    {
        $taskSerializer = new TaskSerializer(
            $this->createMock(ContainerInterface::class),
            $this->createMock(TaskService::class),
            $this->createMock(CommandRegistry::class),
        );
        self::assertSame($expectation, $taskSerializer->extractClassName($serializedTask));
    }
}
