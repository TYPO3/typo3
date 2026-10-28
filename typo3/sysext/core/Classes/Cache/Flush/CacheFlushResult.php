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

namespace TYPO3\CMS\Core\Cache\Flush;

/**
 * What was flushed: cache tags of the "pages" group, cache groups, and the
 * records and cache commands this was resolved from, including what was
 * skipped and why.
 */
final readonly class CacheFlushResult
{
    /**
     * @param list<string> $tags
     * @param list<string> $groups "all" stands for all caches
     * @param array<string, CacheFlushSkipReason> $skippedGroups
     * @param list<CacheFlushRecord> $records
     * @param list<string> $commands
     */
    public function __construct(
        public CacheFlushingContext $context,
        public array $tags = [],
        public array $groups = [],
        public array $skippedGroups = [],
        public array $records = [],
        public array $commands = [],
    ) {}

    public static function fromPlan(CacheFlushPlan $plan): self
    {
        return new self(
            $plan->context,
            $plan->getTags(),
            $plan->getGroups(),
            $plan->getSkippedGroups(),
            $plan->getRecords(),
            $plan->getCommands(),
        );
    }

    /**
     * Ids of the pages whose caches were flushed by their "pageId_<uid>" tag.
     *
     * @return list<int>
     */
    public function getPageIds(): array
    {
        $pageIds = [];
        foreach ($this->tags as $tag) {
            if (preg_match('/^pageId_(\d+)$/', $tag, $matches)) {
                $pageIds[] = (int)$matches[1];
            }
        }
        return $pageIds;
    }

    /**
     * True if the group, or all caches, were flushed.
     */
    public function includesGroup(string $group): bool
    {
        return in_array($group, $this->groups, true) || in_array('all', $this->groups, true);
    }

    /**
     * @return list<CacheFlushRecord>
     */
    public function getFlushedRecords(): array
    {
        return array_values(array_filter($this->records, static fn(CacheFlushRecord $record): bool => !$record->isSkipped()));
    }

    /**
     * @return list<CacheFlushRecord>
     */
    public function getSkippedRecords(): array
    {
        return array_values(array_filter($this->records, static fn(CacheFlushRecord $record): bool => $record->isSkipped()));
    }
}
