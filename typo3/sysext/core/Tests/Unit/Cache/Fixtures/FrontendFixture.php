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

namespace TYPO3\CMS\Core\Tests\Unit\Cache\Fixtures;

use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;

/**
* Fixture implementing frontend
*/
class FrontendFixture implements FrontendInterface
{
    protected string $identifier;

    public function __construct($identifier)
    {
        $this->identifier = $identifier;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getBackend(): never
    {
        // This method is only there to implement the interface and must not be called.
        throw new \RuntimeException('testing', 1668942244);
    }

    public function set($entryIdentifier, $data, array $tags = [], $lifetime = null): void
    {
    }

    public function get($entryIdentifier)
    {
    }

    public function has($entryIdentifier)
    {
        return false;
    }

    public function remove($entryIdentifier)
    {
        return false;
    }

    public function flush(): void
    {
    }

    public function flushByTag($tag): void
    {
    }

    public function flushByTags(array $tags): void
    {
    }

    public function collectGarbage(): void
    {
    }

    public function isValidEntryIdentifier($identifier)
    {
        return true;
    }

    public function isValidTag($tag)
    {
        return true;
    }
}
