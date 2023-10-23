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

namespace TYPO3\CMS\Core\Domain\Event;

use TYPO3\CMS\Core\Context\LanguageAspect;

/**
 * Event which is fired after a record was translated (or tried to be localized).
 */
final class AfterRecordLanguageOverlayEvent
{
    public function __construct(
        private readonly string $table,
        private readonly array $record,
        private array|null $localizedRecord,
        private bool $overlayingWasAttempted,
        private readonly LanguageAspect $languageAspect
    ) {}

    public function getTable(): string
    {
        return $this->table;
    }

    public function getRecord(): array
    {
        return $this->record;
    }

    public function getLanguageAspect(): LanguageAspect
    {
        return $this->languageAspect;
    }

    public function setLocalizedRecord(array|null $localizedRecord): void
    {
        $this->overlayingWasAttempted = true;
        $this->localizedRecord = $localizedRecord;
    }

    public function getLocalizedRecord(): array|null
    {
        return $this->localizedRecord;
    }

    /**
     * Determines if the overlay functionality happened, thus, returning the lo
     */
    public function overlayingWasAttempted(): bool
    {
        return $this->overlayingWasAttempted;
    }
}
