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

namespace TYPO3\CMS\Core\DependencyInjection\Cache;

use TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend;

/**
 * @internal
 */
class ContainerBackend extends SimpleFileBackend
{
    public function flush()
    {
        // disable cache flushing
    }

    public function set($entryIdentifier, $data, array $tags = [], $lifetime = null)
    {
        // Remove stale cache files, once a new DI container was built
        parent::flush();
        parent::set($entryIdentifier, $data, $tags, $lifetime);
    }

    public function forceFlush(): void
    {
        parent::flush();
    }
}
