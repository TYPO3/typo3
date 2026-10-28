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
 * The resolved outcome of a CacheFlushDemand: the cache tags (flushed in
 * the "pages" group) and cache groups that will be flushed, and the records
 * and cache commands they were resolved from.
 */
final class CacheFlushPlan
{
    /** @var array<string, true> */
    private array $tags = [];
    /** @var array<string, true> */
    private array $groups = [];
    /** @var array<string, CacheFlushSkipReason> */
    private array $skippedGroups = [];
    /** @var list<CacheFlushRecord> */
    private array $records = [];
    /** @var array<string, true> */
    private array $commands = [];

    public function __construct(
        public readonly CacheFlushingContext $context,
    ) {}

    public function addTag(string $tag): void
    {
        if ($tag !== '') {
            $this->tags[$tag] = true;
        }
    }

    public function removeTag(string $tag): void
    {
        unset($this->tags[$tag]);
    }

    public function hasTag(string $tag): bool
    {
        return isset($this->tags[$tag]);
    }

    /**
     * @return list<string>
     */
    public function getTags(): array
    {
        return array_map(strval(...), array_keys($this->tags));
    }

    /**
     * Adds a cache group regardless of the permissions of the context.
     * "all" flushes all caches.
     */
    public function addGroup(string $group): void
    {
        $this->groups[$group] = true;
        unset($this->skippedGroups[$group]);
    }

    public function removeGroup(string $group): void
    {
        unset($this->groups[$group]);
    }

    public function hasGroup(string $group): bool
    {
        return isset($this->groups[$group]);
    }

    /**
     * @return list<string>
     */
    public function getGroups(): array
    {
        return array_map(strval(...), array_keys($this->groups));
    }

    public function skipGroup(string $group, CacheFlushSkipReason $reason): void
    {
        unset($this->groups[$group]);
        $this->skippedGroups[$group] = $reason;
    }

    /**
     * @return array<string, CacheFlushSkipReason>
     */
    public function getSkippedGroups(): array
    {
        return $this->skippedGroups;
    }

    public function addRecord(CacheFlushRecord $record): void
    {
        $this->records[] = $record;
    }

    /**
     * @return list<CacheFlushRecord>
     */
    public function getRecords(): array
    {
        return $this->records;
    }

    /**
     * True if a record of the table was part of the demand, even if it was skipped.
     */
    public function hasRecordOfTable(string $table): bool
    {
        foreach ($this->records as $record) {
            if ($record->table === $table) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return bool false if the command was already part of the plan
     */
    public function addCommand(string $command): bool
    {
        if (isset($this->commands[$command])) {
            return false;
        }
        $this->commands[$command] = true;
        return true;
    }

    /**
     * @return list<string>
     */
    public function getCommands(): array
    {
        return array_map(strval(...), array_keys($this->commands));
    }
}
