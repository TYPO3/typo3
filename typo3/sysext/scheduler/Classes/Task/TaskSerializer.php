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

namespace TYPO3\CMS\Scheduler\Task;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Exception\InvalidTaskException;
use TYPO3\CMS\Scheduler\Execution;
use TYPO3\CMS\Scheduler\Service\TaskService;

/**
 * Handles serialization of `AbstractTask` objects.
 *
 * @internal This is an internal API, avoid using it in custom implementations.
 */
#[Autoconfigure(public: true)]
class TaskSerializer
{
    public function __construct(
        protected readonly ContainerInterface $container,
        protected readonly TaskService $taskService,
    ) {}

    /**
     * This method takes care of safely deserializing tasks from the database
     * and either returns a valid Task or throws an InvalidTaskException, which
     * holds information about the broken task.
     *
     * @throws InvalidTaskException
     */
    public function deserialize(array $row): AbstractTask
    {
        $taskType = $row['tasktype'] ?? '';
        if (!empty($taskType)) {
            // First, find the task object, from the registry
            // Second, recreate the execution object,
            // Then fill all the data of the new task object from the rest of the row
            if ($this->taskService->hasTaskType($taskType)) {
                try {
                    $taskObject = $this->container->get($taskType);
                } catch (ServiceNotFoundException) {
                    $taskObject = GeneralUtility::makeInstance($taskType);
                }
            } elseif (isset($this->taskService->getRegisteredCommands()[$taskType])) {
                /** @var ExecuteSchedulableCommandTask $taskObject */
                $taskObject = GeneralUtility::makeInstance(ExecuteSchedulableCommandTask::class);
                $taskObject->setTaskType($taskType);
            } else {
                throw new InvalidTaskException('Task type ' . $taskType . ' not found. Probably not registered?', 1742584362);
            }
            if (!$taskObject instanceof AbstractTask) {
                throw new InvalidTaskException('The deserialized task in not an instance of AbstractTask', 1642954501);
            }
            $taskObject->setTaskUid((int)$row['uid']);
            $taskObject->setTaskGroup((int)$row['task_group']);
            $taskObject->setTaskParameters(json_decode($row['parameters'] ?? '', true) ?: []);
            $taskObject->setDescription((string)$row['description']);
            $taskObject->setExecutionTime((int)$row['nextexecution']);
            $taskObject->setTaskGroup((int)$row['task_group']);
            $taskObject->setDisabled((bool)$row['disable']);
            $executionDetails = json_decode($row['execution_details'] ?? '', true);
            if ($executionDetails !== null) {
                $taskObject->setExecution(Execution::createFromDetails($executionDetails));
            }
            return $taskObject;
        }
        throw new InvalidTaskException('No task type given for task ID : ' . $row['uid'], 1740514192);
    }

    /**
     * If the task class couldn't be figured out from the unserialization (because of uninstalled extensions or exceptions),
     * try to find it in the serialized string with a simple preg match.
     */
    public function extractClassName(string $serializedTask): ?string
    {
        if (preg_match('/^O:[0-9]+:"(?P<classname>[^"]+)"/', $serializedTask, $matches) === 1) {
            return $matches['classname'];
        }
        return null;
    }
}
