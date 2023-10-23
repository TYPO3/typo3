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

namespace TYPO3\CMS\Workspaces\Event;

/**
 * Event that is fired after a record has been published in a workspace.
 */
final class AfterRecordPublishedEvent
{
    public function __construct(
        private readonly string $table,
        private readonly int $recordId,
        private readonly int $workspaceId,
    ) {}

    /**
     * The table name of the record.
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * The uid of the record
     */
    public function getRecordId(): int
    {
        return $this->recordId;
    }

    /**
     * The workspace the record has been published in.
     */
    public function getWorkspaceId(): int
    {
        return $this->workspaceId;
    }
}
