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
 * Event which is fired before a record in a language should be "language overlaid",
 * that is: Finding a translation for a given record.
 */
final class BeforeRecordLanguageOverlayEvent
{
    public function __construct(
        private readonly string $table,
        private array $record,
        private LanguageAspect $languageAspect
    ) {}

    public function getTable(): string
    {
        return $this->table;
    }

    public function getRecord(): array
    {
        return $this->record;
    }

    public function setRecord(array $record): void
    {
        $this->record = $record;
    }

    public function getLanguageAspect(): LanguageAspect
    {
        return $this->languageAspect;
    }

    public function setLanguageAspect(LanguageAspect $languageAspect): void
    {
        $this->languageAspect = $languageAspect;
    }
}
