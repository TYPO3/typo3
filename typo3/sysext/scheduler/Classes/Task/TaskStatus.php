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

use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

/**
 * Represents a single status flag of a scheduler task (running, late, disabled, …),
 * shared by the scheduler backend module listing and the "scheduler:list" CLI command.
 *
 * @internal
 */
final readonly class TaskStatus
{
    /**
     * @param string $label domain:key reference of the badge label
     * @param string $message domain:key reference of the optional, more detailed message (e.g. the failure reason)
     * @param list<string|int> $messageArguments arguments for the message
     */
    public function __construct(
        public string $type,
        public ContextualFeedbackSeverity $severity,
        public string $state,
        public string $label,
        public string $message = '',
        public array $messageArguments = [],
    ) {}
}
