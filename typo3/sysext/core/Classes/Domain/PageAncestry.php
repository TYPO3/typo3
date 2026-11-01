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

namespace TYPO3\CMS\Core\Domain;

use TYPO3\CMS\Core\Domain\Record\PagePermissions;

/**
 * The ancestry of a page: the page itself and its parent pages up to the root level,
 * ordered from the page to the root level.
 *
 * Page objects are only created when they are accessed.
 *
 * @implements \IteratorAggregate<int, Page>
 */
final class PageAncestry implements \IteratorAggregate, \Countable
{
    /** @var array<int, Page> */
    private array $pages = [];

    /**
     * @param list<array<string, mixed>> $records Page records, ordered from the page to the root level
     * @param array<int, PagePermissions|null> $permissions Permission settings by page uid
     * @param \Closure(array<string, mixed>): Page $pageFactory
     * @internal Use PageAncestryResolver to create a page ancestry
     */
    public function __construct(
        private readonly int $pageId,
        private readonly array $records,
        private readonly array $permissions,
        private readonly \Closure $pageFactory,
    ) {}

    /**
     * @return \Generator<int, Page>
     */
    public function getIterator(): \Generator
    {
        foreach ($this->records as $index => $record) {
            yield $this->getPageAt($index);
        }
    }

    public function count(): int
    {
        return count($this->records);
    }

    /**
     * Returns the page the ancestry was resolved for, null if it does not exist.
     */
    public function getPage(): ?Page
    {
        return $this->records === [] ? null : $this->getPageAt(0);
    }

    /**
     * Whether the ancestry reaches the root level. It does not, if a page in between
     * is deleted or does not exist, or if the page tree contains a cycle.
     */
    public function isComplete(): bool
    {
        if ($this->pageId === 0) {
            return true;
        }
        if ($this->records === []) {
            return false;
        }
        return (int)$this->records[array_key_last($this->records)]['pid'] === 0;
    }

    /**
     * @return list<int> Page uids, ordered from the page to the root level
     */
    public function getPageIds(): array
    {
        return array_map(static fn(array $record): int => (int)$record['uid'], $this->records);
    }

    /**
     * Returns the permission settings of a page in the ancestry. For pages with a workspace
     * version, these are the settings of the live page.
     */
    public function getPermissions(int $pageId): ?PagePermissions
    {
        return $this->permissions[$pageId] ?? null;
    }

    /**
     * Returns the records in the structure of BackendUtility::BEgetRootLine(): keys count
     * from the root level, ordered from the page to the root level, and a complete ancestry
     * ends with a record for the virtual tree root with uid 0.
     *
     * @internal Only to be used to migrate consumers of BackendUtility::BEgetRootLine().
     */
    public function toLegacyRootLine(): array
    {
        $key = count($this->records) + ($this->isComplete() ? 1 : 0);
        $rootLine = [];
        foreach ($this->records as $record) {
            $rootLine[--$key] = $record;
        }
        if ($this->isComplete()) {
            $fieldNames = array_filter(
                array_keys($this->records[0] ?? ['pid' => null]),
                static fn(string $fieldName): bool => !str_starts_with($fieldName, '_')
            );
            $rootLine[0] = ['uid' => 0] + array_fill_keys($fieldNames, null);
        }
        return $rootLine;
    }

    private function getPageAt(int $index): Page
    {
        return $this->pages[$index] ??= ($this->pageFactory)($this->records[$index]);
    }
}
