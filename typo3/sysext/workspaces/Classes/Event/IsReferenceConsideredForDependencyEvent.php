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

use TYPO3\CMS\Workspaces\Dependency\DependencyCollectionAction;

/**
 * Event dispatched per sys_refindex row when evaluating workspace
 * dependencies. Listeners decide whether a given reference constitutes
 * a structural workspace dependency.
 *
 * References are opt-in: isDependency defaults to false and listeners
 * must explicitly mark relevant references as dependencies.
 */
final class IsReferenceConsideredForDependencyEvent
{
    private bool $isDependency = false;

    public function __construct(
        private readonly string $tableName,
        private readonly int $recordId,
        private readonly string $fieldName,
        private readonly string $referenceTable,
        private readonly int $referenceId,
        private readonly DependencyCollectionAction $action,
        private readonly int $workspaceId,
    ) {}

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getRecordId(): int
    {
        return $this->recordId;
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    public function getReferenceTable(): string
    {
        return $this->referenceTable;
    }

    public function getReferenceId(): int
    {
        return $this->referenceId;
    }

    public function getAction(): DependencyCollectionAction
    {
        return $this->action;
    }

    public function getWorkspaceId(): int
    {
        return $this->workspaceId;
    }

    public function setDependency(bool $isDependency): void
    {
        $this->isDependency = $isDependency;
    }

    public function isDependency(): bool
    {
        return $this->isDependency;
    }
}
