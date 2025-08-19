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

use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;

/**
 * A caching backend which forgets everything immediately
 */
class NullBackend implements PhpCapableBackendInterface, TaggableBackendInterface
{
    public function set(string $entryIdentifier, string $data, array $tags = [], ?int $lifetime = null): void {}

    public function get(string $entryIdentifier): false
    {
        return false;
    }

    public function has(string $entryIdentifier): false
    {
        return false;
    }

    public function remove(string $entryIdentifier): false
    {
        return false;
    }

    public function findIdentifiersByTag($tag): array
    {
        return [];
    }

    public function flush(): void {}

    public function flushByTag(string $tag): void {}

    public function flushByTags(array $tags): void {}

    public function setCache(FrontendInterface $cache): void {}

    public function collectGarbage(): void {}

    public function requireOnce(string $entryIdentifier): false
    {
        return false;
    }

    public function require(string $entryIdentifier): false
    {
        return false;
    }
}
