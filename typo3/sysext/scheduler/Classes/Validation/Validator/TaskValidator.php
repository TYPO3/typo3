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

namespace TYPO3\CMS\Scheduler\Validation\Validator;

use TYPO3\CMS\Scheduler\Task\AbstractTask;

class TaskValidator
{
    /**
     * This method encapsulates a very simple test for the purpose of clarity.
     * Registered tasks are stored in the database along with a serialized task object.
     * When a registered task is fetched, its object is unserialized.
     * At that point, if the class corresponding to the object is not available anymore
     * (e.g. because the extension providing it has been uninstalled),
     * the unserialization will produce an incomplete object.
     * This test checks whether the unserialized object is of the right (parent) class or not.
     *
     * @param mixed $value The object to test
     * @return bool TRUE if object is a task, FALSE otherwise
     */
    public function isValid(mixed $value): bool
    {
        return $value instanceof AbstractTask
            && $value->getExecution()
            && get_class($value->getExecution()) !== \__PHP_Incomplete_Class::class;
    }
}
