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

    /**
     * @param 'new'|'edit' $command
     */
    public function __construct(
        private readonly ?AccessDeniedException $exception,
        private readonly string $tableName,
        private readonly string $command,
        private readonly array $databaseRow,
    ) {
        $this->userHasAccess = $this->exception === null;
    }

    /**
     * Allows user access to the editing form
     */
    public function allowUserAccess(): void
    {
        $this->userHasAccess = true;
    }

    /**
     * Denies user access to the editing form
     */
    public function denyUserAccess(): void
    {
        $this->userHasAccess = false;
    }

    /**
     * Returns the current user access state
     */
    public function doesUserHaveAccess(): bool
    {
        return $this->userHasAccess;
    }

    /**
     * If Core's DataProvider previously denied access, this returns the corresponding
     * exception, `null` otherwise
     */
    public function getAccessDeniedException(): ?AccessDeniedException
    {
        return $this->exception;
    }

    /**
     * Returns the table name of the record in question
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * Returns the requested command, either `new` or `edit`
     * @return 'new'|'edit'
     */
    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * Returns the record's database row
     */
    public function getDatabaseRow(): array
    {
        return $this->databaseRow;
    }
}
