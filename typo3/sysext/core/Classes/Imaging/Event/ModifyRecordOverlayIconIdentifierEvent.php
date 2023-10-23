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

namespace TYPO3\CMS\Core\Imaging\Event;

/**
 * Listeners to this event are able to modify the overlay icon identifier of any record icon
 */
final class ModifyRecordOverlayIconIdentifierEvent
{
    public function __construct(
        private string $overlayIconIdentifier,
        private readonly string $table,
        private readonly array $row,
        private readonly array $status,
    ) {}

    public function setOverlayIconIdentifier(string $overlayIconIdentifier): void
    {
        $this->overlayIconIdentifier = $overlayIconIdentifier;
    }

    public function getOverlayIconIdentifier(): string
    {
        return $this->overlayIconIdentifier;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getRow(): array
    {
        return $this->row;
    }

    public function getStatus(): array
    {
        return $this->status;
    }
}
