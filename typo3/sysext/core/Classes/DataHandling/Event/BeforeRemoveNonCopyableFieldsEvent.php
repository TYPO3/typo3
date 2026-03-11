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

namespace TYPO3\CMS\Core\DataHandling\Event;

/**
 * Event is dispatched before non-copyable fields are removed from a record. Listener can modify the list of
 * non-copyable fields (e.g. add custom fields, which should be ignored by DataHandler during copy/localization).
 *
 * @internal This event should only be used by experienced developers who understand the implications of
 *           DataHandler's field processing. Wrong usage will lead to data inconsistencies. The event is
 *           therefore declared as "use at your own risk, may change without notice".
 */
final class BeforeRemoveNonCopyableFieldsEvent
{
    public function __construct(
        private readonly string $table,
        private readonly array $row,
        private readonly string $callingOperation,
        private array $nonCopyableFields
    ) {}

    public function getTable(): string
    {
        return $this->table;
    }

    public function getCallingOperation(): string
    {
        return $this->callingOperation;
    }

    public function getRow(): array
    {
        return $this->row;
    }

    public function getNonCopyableFields(): array
    {
        return $this->nonCopyableFields;
    }

    public function setNonCopyableFields(array $nonCopyableFields): void
    {
        $this->nonCopyableFields = $nonCopyableFields;
    }
}
