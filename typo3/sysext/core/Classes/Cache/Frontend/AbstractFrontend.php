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

namespace TYPO3\CMS\Core\Cache\Frontend;

use TYPO3\CMS\Core\Cache\Backend\BackendInterface;
use TYPO3\CMS\Core\Cache\Backend\TaggableBackendInterface;

abstract class AbstractFrontend implements FrontendInterface
{
    public function __construct(
        protected string $identifier,
        protected BackendInterface $backend
    ) {
        if (preg_match(self::PATTERN_ENTRYIDENTIFIER, $identifier) !== 1) {
            throw new \InvalidArgumentException('"' . $identifier . '" is not a valid cache identifier.', 1203584729);
        }
        $this->identifier = $identifier;
        $this->backend->setCache($this);
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getBackend(): BackendInterface
    {
        return $this->backend;
    }

    public function has(string $entryIdentifier): bool
    {
        if (!$this->isValidEntryIdentifier($entryIdentifier)) {
            throw new \InvalidArgumentException('"' . $entryIdentifier . '" is not a valid cache entry identifier.', 1233058486);
        }
        return $this->backend->has($entryIdentifier);
    }

    public function remove(string $entryIdentifier): bool
    {
        if (!$this->isValidEntryIdentifier($entryIdentifier)) {
            throw new \InvalidArgumentException('"' . $entryIdentifier . '" is not a valid cache entry identifier.', 1233058495);
        }
        return $this->backend->remove($entryIdentifier);
    }

    public function flush(): void
    {
        $this->backend->flush();
    }

    public function flushByTags(array $tags): void
    {
        if (!$this->backend instanceof TaggableBackendInterface) {
            return;
        }

        foreach ($tags as $tag) {
            if (!$this->isValidTag($tag)) {
                throw new \InvalidArgumentException('"' . $tag . '" is not a valid tag for a cache entry.', 1233057360);
            }
        }

        $this->backend->flushByTags($tags);
    }

    public function flushByTag(string $tag): void
    {
        if (!$this->backend instanceof TaggableBackendInterface) {
            return;
        }

        if (!$this->isValidTag($tag)) {
            throw new \InvalidArgumentException('"' . $tag . '" is not a valid tag for a cache entry.', 1233057359);
        }

        $this->backend->flushByTag($tag);
    }

    public function collectGarbage(): void
    {
        $this->backend->collectGarbage();
    }

    public function isValidEntryIdentifier(string $identifier): bool
    {
        return preg_match(self::PATTERN_ENTRYIDENTIFIER, $identifier) === 1;
    }

    public function isValidTag(string $tag): bool
    {
        return preg_match(self::PATTERN_TAG, $tag) === 1;
    }
}
