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

namespace TYPO3\CMS\Scheduler\Event;

use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Listeners to this event receive information about a completed scheduler task execution,
 * including its success state and any thrown exception.
 */
final readonly class AfterTaskExecutionEvent
{
    public function __construct(
        private AbstractTask $task,
        private bool $success,
        private ?\Throwable $exception = null,
    ) {}

    public function getTask(): AbstractTask
    {
        return $this->task;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getException(): ?\Throwable
    {
        return $this->exception;
    }
}
