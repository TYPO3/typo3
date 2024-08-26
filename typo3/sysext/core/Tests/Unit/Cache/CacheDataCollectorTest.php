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
use TYPO3\CMS\Core\Cache\CacheDataCollector;
use TYPO3\CMS\Core\Cache\CacheTag;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CacheDataCollectorTest extends UnitTestCase
{
    #[Test]
    public function getCacheTags(): void
    {
        $cacheDataCollector = new CacheDataCollector();
        self::assertSame([], $cacheDataCollector->getCacheTags());
        $cacheTag = new CacheTag('pages_12345');
        $cacheDataCollector = new CacheDataCollector();
        $cacheDataCollector->addCacheTags($cacheTag);
        $cacheTags = $cacheDataCollector->getCacheTags();
        self::assertCount(1, $cacheTags);
        self::assertSame([$cacheTag], $cacheTags);
    }

    #[Test]
    public function addSingleCacheTag(): void
    {
        $cacheDataCollector = new CacheDataCollector();
        $cacheTag = new CacheTag('pages_12345');
        $cacheDataCollector->addCacheTags($cacheTag);
        $cacheTags = $cacheDataCollector->getCacheTags();
        self::assertCount(1, $cacheTags);
        self::assertSame([$cacheTag], $cacheTags);
    }

    #[Test]
    public function addMultipleCacheTag(): void
    {
        $cacheDataCollector = new CacheDataCollector();
        $cacheTag1 = new CacheTag('pages_12345');
        $cacheTag2 = new CacheTag('pages_123456');
        $cacheDataCollector->addCacheTags($cacheTag1, $cacheTag2);
        $cacheTags = $cacheDataCollector->getCacheTags();
        self::assertCount(2, $cacheTags);
        self::assertSame([$cacheTag1, $cacheTag2], $cacheTags);
    }

    #[Test]
    public function addCacheTags(): void
    {
        $cacheDataCollector = new CacheDataCollector();
        $cacheTag1 = new CacheTag('pages_12345');
        $cacheTag2 = new CacheTag('pages_123456');
        $cacheDataCollector->addCacheTags($cacheTag1, $cacheTag2);
        $cacheTags = $cacheDataCollector->getCacheTags();
        self::assertCount(2, $cacheTags);
        self::assertSame([$cacheTag1, $cacheTag2], $cacheTags);
    }

    #[Test]
    public function addSameCacheTagTwice(): void
    {
        $cacheDataCollector = new CacheDataCollector();
        $cacheTag = new CacheTag('pages_12345');
        $cacheDataCollector->addCacheTags($cacheTag, $cacheTag);
        $cacheTags = $cacheDataCollector->getCacheTags();
        self::assertCount(1, $cacheTags);
        self::assertSame([$cacheTag], $cacheTags);
    }

    #[Test]
    public function removeCacheTag(): void
    {
        $cacheDataCollector = new CacheDataCollector();
        $cacheTag1 = new CacheTag('pages_12345');
        $cacheTag2 = new CacheTag('pages_12346');
        $cacheDataCollector->addCacheTags($cacheTag1, $cacheTag2);
        $cacheTags = $cacheDataCollector->getCacheTags();
        self::assertCount(2, $cacheTags);
        $cacheDataCollector->removeCacheTags($cacheTag1);
        $cacheTags = $cacheDataCollector->getCacheTags();
        self::assertCount(1, $cacheTags);
        self::assertSame([$cacheTag2], $cacheTags);
    }

    #[Test]
    public function resolveDefaultLifetimeIfEmpty(): void
    {
        $cacheDataCollector = new CacheDataCollector();
        self::assertSame(PHP_INT_MAX, $cacheDataCollector->resolveLifetime());
    }

    #[Test]
    public function resolveDefaultLifetime(): void
    {
        $cacheDataCollector = new CacheDataCollector();
        $cacheTag = new CacheTag('pages_12345');
        $cacheDataCollector->addCacheTags($cacheTag);
        self::assertSame(PHP_INT_MAX, $cacheDataCollector->resolveLifetime());
    }

    #[Test]
    public function resolveMinimumLifetime(): void
    {
        $cacheDataCollector = new CacheDataCollector();
        $cacheTag1 = new CacheTag('pages_12345');
        $cacheTag2 = new CacheTag('pages_123456', 2592000);
        $cacheTag3 = new CacheTag('pages_1234567', 3600);
        $cacheDataCollector->addCacheTags($cacheTag1, $cacheTag2, $cacheTag3);
        self::assertSame(3600, $cacheDataCollector->resolveLifetime());
    }
}
