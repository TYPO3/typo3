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

namespace TYPO3\CMS\Frontend\ContentObject\Event;

use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Listeners to this Event are able to modify the final stdWrap content
 * and corresponding cache tags, before being stored in cache.
 *
 * Additionally, listeners are also able to change the cache key to be used
 * as well as the lifetime. Therefore, the whole configuration is available.
 */
final class BeforeStdWrapContentStoredInCacheEvent
{
    public function __construct(
        private ?string $content,
        private array $tags,
        private string $key,
        private ?int $lifetime,
        private readonly array $configuration,
        private readonly ContentObjectRenderer $contentObjectRenderer
    ) {}

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function setTags(array $tags): void
    {
        $this->tags = $tags;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    public function getLifetime(): ?int
    {
        return $this->lifetime;
    }

    public function setLifetime(?int $lifetime): void
    {
        $this->lifetime = $lifetime;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function getContentObjectRenderer(): ContentObjectRenderer
    {
        return $this->contentObjectRenderer;
    }
}
