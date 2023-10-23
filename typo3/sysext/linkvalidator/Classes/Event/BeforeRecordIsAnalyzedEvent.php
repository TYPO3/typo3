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

namespace TYPO3\CMS\Linkvalidator\Event;

use TYPO3\CMS\Linkvalidator\LinkAnalyzer;

/**
 * Event that is fired to modify results (= add results) or modify the record before the linkanalyzer analyzes
 * the record.
 */
final class BeforeRecordIsAnalyzedEvent
{
    public function __construct(
        private readonly string $tableName,
        private array $record,
        private readonly array $fields,
        private readonly LinkAnalyzer $linkAnalyzer,
        private array $results
    ) {}

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getRecord(): array
    {
        return $this->record;
    }

    public function setRecord(array $record): void
    {
        $this->record = $record;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function getResults(): array
    {
        return $this->results;
    }

    public function setResults(array $results): void
    {
        $this->results = $results;
    }

    public function getLinkAnalyzer(): LinkAnalyzer
    {
        return $this->linkAnalyzer;
    }
}
