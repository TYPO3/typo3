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

namespace TYPO3\CMS\Core\Tests\Unit\Cache;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\CacheTag;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CacheTagTest extends UnitTestCase
{
    #[Test]
    public function cacheTagIsStringable(): void
    {
        $cacheTag = new CacheTag('pages_12345');
        self::assertSame('pages_12345', $cacheTag->name);
    }

    #[Test]
    public function cacheTagHasDefaultLifeTime(): void
    {
        $cacheTag = new CacheTag('pages_12345');
        self::assertSame(PHP_INT_MAX, $cacheTag->lifetime);
    }

    #[Test]
    public function cacheTagLifeTimeIsSettable(): void
    {
        $cacheTag = new CacheTag('pages_12345', 3600);
        self::assertSame(3600, $cacheTag->lifetime);
    }
}
