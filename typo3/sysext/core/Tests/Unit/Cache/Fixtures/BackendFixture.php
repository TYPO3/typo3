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

use TYPO3\CMS\Core\Cache\Backend\BackendInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;

class BackendFixture implements BackendInterface
{
    public function setCache(FrontendInterface $cache): void {}

    public function set(string $entryIdentifier, string $data, array $tags = [], ?int $lifetime = null): void {}

    public function get(string $entryIdentifier): mixed
    {
        return null;
    }

    public function has(string $entryIdentifier): bool
    {
        return false;
    }

    public function remove(string $entryIdentifier): bool
    {
        return false;
    }

    public function flush(): void {}

    public function collectGarbage(): void {}
}
