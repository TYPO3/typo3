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
 * Describes what should be flushed: changed records, pages, cache tags or
 * cache commands as known from "TCEMAIN.clearCacheCmd".
 */
final readonly class CacheFlushDemand
{
    /**
     * @param array<string, array<int, list<int>>> $records table => uid => pages the record was located on before it was moved or deleted
     * @param list<int> $pageIds
     * @param list<string> $tags
     * @param list<string> $commands
     */
    private function __construct(
        public array $records = [],
        public array $pageIds = [],
        public array $tags = [],
        public array $commands = [],
    ) {}

    public static function create(): self
    {
        return new self();
    }

    /**
     * Flushes the caches of the page a record is located on, including
     * the configuration of "TCEMAIN.clearCache_*" and "TCEMAIN.clearCacheCmd".
     *
     * @param int|null $originalPageId The page the record was located on before it was moved or deleted
     */
    public static function forRecord(string $table, int $uid, ?int $originalPageId = null): self
    {
        return self::create()->withRecord($table, $uid, $originalPageId);
    }

    /**
     * Flushes the cache tags "pageId_<uid>" of the pages only, without
     * resolving related pages or Page TSconfig. Use forRecord('pages', $uid)
     * to flush a page the way DataHandler does when it is saved.
     */
    public static function forPageTagsByPageIds(int ...$pageIds): self
    {
        return new self(pageIds: array_values(array_unique($pageIds)));
    }

    public static function forTags(string ...$tags): self
    {
        return new self(tags: array_values(array_unique(array_filter($tags, static fn(string $tag): bool => $tag !== ''))));
    }

    /**
     * A cache command as known from "TCEMAIN.clearCacheCmd": "pages", "all",
     * a page id or "cacheTag:<tag>". Other commands are passed on untouched,
     * so event listeners can react to them.
     */
    public static function forCommand(int|string $command): self
    {
        $command = trim((string)$command);
        return new self(commands: $command === '' ? [] : [$command]);
    }

    /**
     * @param int|null $originalPageId The page the record was located on before it was moved or deleted
     */
    public function withRecord(string $table, int $uid, ?int $originalPageId = null): self
    {
        $records = $this->records;
        $records[$table][$uid] ??= [];
        if ($originalPageId !== null && !in_array($originalPageId, $records[$table][$uid], true)) {
            $records[$table][$uid][] = $originalPageId;
        }
        return new self($records, $this->pageIds, $this->tags, $this->commands);
    }

    public function isEmpty(): bool
    {
        return $this->records === [] && $this->pageIds === [] && $this->tags === [] && $this->commands === [];
    }
}
