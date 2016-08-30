<?php
namespace TYPO3\CMS\Core\Tests\Unit\Cache\Fixtures;

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

use TYPO3\CMS\Core\Cache\Backend\BackendInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;

/**
 * Backend fixture to test cache manager.
 */
class BackendFixture implements BackendInterface
{
    public function setCache(FrontendInterface $cache)
    {
    }

    public function set($entryIdentifier, $data, array $tags = [], $lifetime = null)
    {
    }

    public function get($entryIdentifier)
    {
    }

    public function has($entryIdentifier)
    {
    }

    public function remove($entryIdentifier)
    {
    }

    public function flush()
    {
    }

    public function collectGarbage()
    {
    }
}
