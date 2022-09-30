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

namespace TYPO3\CMS\Backend\View\Event;

use TYPO3\CMS\Backend\RecordList\DatabaseRecordList;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

/**
 * Use this Event to alter the database query when loading content for a page (usually in the list module)
 * before it is executed.
 */
final class ModifyDatabaseQueryForRecordListingEvent
{
    public function __construct(
        private QueryBuilder $queryBuilder,
        private string $table,
        private int $pageId,
        private array $fields,
        private int $firstResult,
        private int $maxResults,
        private DatabaseRecordList $recordList
    ) {
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    public function setQueryBuilder(QueryBuilder $queryBuilder): void
    {
        $this->queryBuilder = $queryBuilder;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getPageId(): int
    {
        return $this->pageId;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function getFirstResult(): int
    {
        return $this->firstResult;
    }

    public function getMaxResults(): int
    {
        return $this->maxResults;
    }

    public function getDatabaseRecordList(): DatabaseRecordList
    {
        return $this->recordList;
    }
}
