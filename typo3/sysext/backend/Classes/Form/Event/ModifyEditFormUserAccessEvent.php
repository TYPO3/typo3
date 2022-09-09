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

namespace TYPO3\CMS\Backend\Form\Event;

use TYPO3\CMS\Backend\Form\Exception\AccessDeniedException;

/**
 * Listeners to this Event will be able to modify the user access
 * decision for using FormEngine to create or edit a record.
 */
final class ModifyEditFormUserAccessEvent
{
    private bool $userHasAccess;

    public function __construct(
        private readonly ?AccessDeniedException $exception,
        private readonly string $tableName,
        private readonly string $command,
        private readonly array $databaseRow,
    ) {
        $this->userHasAccess = $this->exception === null;
    }

    public function allowUserAccess(): void
    {
        $this->userHasAccess = true;
    }

    public function denyUserAccess(): void
    {
        $this->userHasAccess = false;
    }

    public function doesUserHaveAccess(): bool
    {
        return $this->userHasAccess;
    }

    public function getAccessDeniedException(): ?AccessDeniedException
    {
        return $this->exception;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function getDatabaseRow(): array
    {
        return $this->databaseRow;
    }
}
