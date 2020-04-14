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

use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Cache\Backend\NullBackend;

/**
 * This class only acts as shortcut to construct a cache frontend with a null backend.
 * It extends PhpFrontend to be sure it can also be used for all types of caches (also the one requiring a PhpFrontend like "core").
 * TODO: Instead a factory class should be introduced that replaces this class and \TYPO3\CMS\Core\Core\Bootstrap::createCache
 */
class NullFrontend extends PhpFrontend
{
    public function __construct(string $identifier)
    {
        $backend = new NullBackend(
            '',
            [
                'logger' => new NullLogger(),
            ]
        );
        parent::__construct($identifier, $backend);
    }

    public function set($entryIdentifier, $data, array $tags = [], $lifetime = null)
    {
        // Noop
    }
}
