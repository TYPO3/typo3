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

namespace TYPO3\CMS\Core\Cache\Event;

use TYPO3\CMS\Core\Cache\Flush\CacheFlushingContext;
use TYPO3\CMS\Core\Cache\Flush\CacheFlushSkipReason;

/**
 * Dispatched once for every changed record, after the pages, cache tags and
 * cache commands related to it have been resolved from the page tree and
 * Page TSconfig "TCEMAIN.clearCache_*" and "TCEMAIN.clearCacheCmd".
 *
 * Page ids are flushed by their "pageId_<uid>" cache tag. A record skipped
 * by "TCEMAIN.clearCache_disable" or because a workspace version changed
 * comes without page ids and tags, but still with its cache commands.
 *
 * The event is also dispatched by CacheFlusher::resolve(), which flushes
 * nothing. Listeners must only change what is flushed, follow-up work
 * belongs into a listener of AfterCacheFlushedEvent.
 */
final class BeforeRecordRelatedCachesAreFlushedEvent
{
    /**
     * @param int $pageId The page the record is located on, for "pages" the default language page itself. 0 if skipped.
     * @param int $originalPageId The page whose Page TSconfig applies, the former location of a moved or deleted record
     * @param array $pageTsConfig Page TSconfig "TCEMAIN."
     * @param list<int> $pageIds
     * @param list<string> $tags
     * @param list<string> $commands
     */
    public function __construct(
        public readonly string $table,
        public readonly int $uid,
        public readonly int $pageId,
        public readonly int $originalPageId,
        public readonly array $pageTsConfig,
        public readonly ?CacheFlushSkipReason $skipReason,
        public readonly CacheFlushingContext $context,
        private array $pageIds,
        private array $tags,
        private array $commands,
    ) {}

    /**
     * @return list<int>
     */
    public function getPageIds(): array
    {
        return $this->pageIds;
    }

    /**
     * @param list<int> $pageIds
     */
    public function setPageIds(array $pageIds): void
    {
        $this->pageIds = array_values(array_unique(array_map(intval(...), $pageIds)));
    }

    public function addPageId(int $pageId): void
    {
        if (!in_array($pageId, $this->pageIds, true)) {
            $this->pageIds[] = $pageId;
        }
    }

    /**
     * @return list<string>
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @param list<string> $tags
     */
    public function setTags(array $tags): void
    {
        $this->tags = array_values(array_unique(array_map(strval(...), $tags)));
    }

    public function addTag(string $tag): void
    {
        if (!$this->hasTag($tag)) {
            $this->tags[] = $tag;
        }
    }

    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags, true);
    }

    /**
     * @return list<string>
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * @param list<string> $commands
     */
    public function setCommands(array $commands): void
    {
        $this->commands = array_values(array_unique(array_map(strval(...), $commands)));
    }
}
