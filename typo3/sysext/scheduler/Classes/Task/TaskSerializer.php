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

use TYPO3\CMS\Scheduler\Exception\InvalidTaskException;

/**
 * Handles serialization of `AbstractTask` objects.
 *
 * @internal This is an internal API, avoid using it in custom implementations.
 */
class TaskSerializer
{
    /**
     * This method takes care of safely deserializing tasks from the database
     * and either returns a valid Task or throws an InvalidTaskException, which
     * holds information about the broken task.
     *
     * @throws InvalidTaskException
     */
    public function deserialize(string $serializedTask): AbstractTask
    {
        try {
            $task = @unserialize($serializedTask);
            if ($task === false) {
                throw new InvalidTaskException('The serialized task is corrupted', 1642956282);
            }
            if (!$task instanceof AbstractTask) {
                throw new InvalidTaskException('The deserialized task in not an instance of AbstractTask', 1642954501);
            }
            return $task;
        } catch (\BadMethodCallException $e) {
            // This can happen, if a Task has a dependency to a class with the BlockSerializationTrait.
            throw new InvalidTaskException($e->getMessage(), 1642938352);
        }
    }

    /**
     * @template T of object
     * @param T $task
     * @return class-string<T>|string
     */
    public function resolveClassName(object $task): string
    {
        $taskClass = get_class($task);
        if ($taskClass === '__PHP_Incomplete_Class') {
            $taskArray = json_decode((string)json_encode($task, 0, 1), true);
            $taskClass = (string)$taskArray['__PHP_Incomplete_Class_Name'];
        }
        return $taskClass;
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
