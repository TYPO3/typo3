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

namespace TYPO3\CMS\Backend\Search\Event;

use TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\SearchDemand;

/**
 * PSR-14 event to modify the incoming input about which tables should be searched for within
 * the live search results. This allows adding additional DB tables to be excluded / ignored, to
 * further limit the search result on certain page IDs or to modify the search query altogether.
 */
final class BeforeSearchInDatabaseRecordProviderEvent
{
    private array $ignoredTables = [];

    public function __construct(
        private array $searchPageIds,
        private SearchDemand $searchDemand
    ) {
    }

    public function getSearchPageIds(): array
    {
        return $this->searchPageIds;
    }

    public function setSearchPageIds(array $searchPageIds): void
    {
        $this->searchPageIds = $searchPageIds;
    }

    public function getSearchDemand(): SearchDemand
    {
        return $this->searchDemand;
    }

    public function setSearchDemand(SearchDemand $searchDemand): void
    {
        $this->searchDemand = $searchDemand;
    }

    public function ignoreTable(string $table): void
    {
        $this->ignoredTables[] = $table;
    }

    public function setIgnoredTables(array $tables): void
    {
        $this->ignoredTables = $tables;
    }

    public function isTableIgnored(string $table): bool
    {
        return in_array($table, $this->ignoredTables, true);
    }

    /**
     * @return string[]
     */
    public function getIgnoredTables(): array
    {
        return $this->ignoredTables;
    }
}
