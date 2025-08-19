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

namespace TYPO3\CMS\Core\Cache\Backend;

/**
 * A caching backend which stores cache entries during one script run.
 */
class TransientMemoryBackend extends AbstractBackend implements TaggableBackendInterface, TransientBackendInterface
{
    protected array $entries = [];

    protected array $tagsAndEntries = [];

    /**
     * @param mixed $data The data to be stored. mixed is allowed due to TransientBackendInterface
     */
    public function set(string $entryIdentifier, mixed $data, array $tags = [], $lifetime = null): void
    {
        $this->entries[$entryIdentifier] = $data;
        foreach ($tags as $tag) {
            $this->tagsAndEntries[$tag][$entryIdentifier] = true;
        }
    }

    public function get(string $entryIdentifier): mixed
    {
        return $this->entries[$entryIdentifier] ?? false;
    }

    public function has(string $entryIdentifier): bool
    {
        return isset($this->entries[$entryIdentifier]);
    }

    public function remove(string $entryIdentifier): bool
    {
        if (isset($this->entries[$entryIdentifier])) {
            unset($this->entries[$entryIdentifier]);
            foreach (array_keys($this->tagsAndEntries) as $tag) {
                if (isset($this->tagsAndEntries[$tag][$entryIdentifier])) {
                    unset($this->tagsAndEntries[$tag][$entryIdentifier]);
                }
            }
            return true;
        }
        return false;
    }

    public function findIdentifiersByTag(string $tag): array
    {
        if (isset($this->tagsAndEntries[$tag])) {
            return array_keys($this->tagsAndEntries[$tag]);
        }
        return [];
    }

    public function flush(): void
    {
        $this->entries = [];
        $this->tagsAndEntries = [];
    }

    public function flushByTag(string $tag): void
    {
        $identifiers = $this->findIdentifiersByTag($tag);
        foreach ($identifiers as $identifier) {
            $this->remove($identifier);
        }
    }

    public function flushByTags(array $tags): void
    {
        array_walk($tags, $this->flushByTag(...));
    }

    /**
     * No-op
     */
    public function collectGarbage(): void {}
}
