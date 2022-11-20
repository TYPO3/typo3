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

namespace TYPO3\CMS\Core\Tests\Unit\Cache\Backend\Fixtures;

use TYPO3\CMS\Core\Cache\Backend\AbstractBackend;

/**
 * Fixture implementing one set option method.
 */
class ConcreteBackendFixture extends AbstractBackend
{
    protected string $someOption;

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

    public function findIdentifiersByTag($tag): void
    {
    }

    public function collectGarbage(): void
    {
    }

    public function setSomeOption($value): void
    {
        $this->someOption = $value;
    }

    public function getSomeOption(): string
    {
        return $this->someOption;
    }
}
