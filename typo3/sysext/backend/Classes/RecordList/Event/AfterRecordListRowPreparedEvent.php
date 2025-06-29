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

namespace TYPO3\CMS\Backend\RecordList\Event;

use TYPO3\CMS\Backend\RecordList\DatabaseRecordList;
use TYPO3\CMS\Core\Domain\RecordInterface;

/**
 * An event to modify the record data for a table in the RecordList.
 */
final class AfterRecordListRowPreparedEvent
{
    public function __construct(
        private readonly string $table,
        private readonly RecordInterface $record,
        private array $data,
        private readonly DatabaseRecordList $recordList,
        private readonly ?string $recTitle,
        private readonly array|bool $lockInfo,
        private array $tagAttributes,
    ) {}

    public function getTable(): string
    {
        return $this->table;
    }

    public function getRecord(): RecordInterface
    {
        return $this->record;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function getRecTitle(): ?string
    {
        return $this->recTitle;
    }

    public function getLockInfo(): bool|array
    {
        return $this->lockInfo;
    }

    public function getRecordList(): DatabaseRecordList
    {
        return $this->recordList;
    }

    public function getTagAttributes(): array
    {
        return $this->tagAttributes;
    }

    public function setTagAttributes(array $tagAttributes): void
    {
        $this->tagAttributes = $tagAttributes;
    }
}
