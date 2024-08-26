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

namespace TYPO3\CMS\Extbase\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Service\CacheService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CacheServiceTest extends UnitTestCase
{
    private CacheService $subject;
    private CacheManager&MockObject $cacheManagerMock;

    protected function setUp(): void
    {
        parent::setUp();
        $configurationManager = $this->createMock(ConfigurationManagerInterface::class);
        $configurationManager->method('getConfiguration')->with(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK)->willReturn([]);
        $this->cacheManagerMock = $this->createMock(CacheManager::class);
        $this->subject = new CacheService($configurationManager, $this->cacheManagerMock, $this->createMock(ConnectionPool::class));
    }

    #[Test]
    public function clearPageCacheConvertsPageIdsToArray(): void
    {
        $this->cacheManagerMock->expects(self::once())->method('flushCachesInGroupByTags')->with('pages', ['pageId_123']);
        $this->subject->clearPageCache(123);
    }

    #[Test]
    public function clearPageCacheConvertsPageIdsToNumericArray(): void
    {
        $this->cacheManagerMock->expects(self::once())->method('flushCachesInGroupByTags')->with('pages', ['pageId_0']);
        $this->subject->clearPageCache('Foo');
    }

    #[Test]
    public function clearPageCacheDoesNotConvertPageIdsIfNoneAreSpecified(): void
    {
        $this->cacheManagerMock->expects(self::once())->method('flushCachesInGroup')->with('pages');
        $this->subject->clearPageCache();
    }

    #[Test]
    public function clearPageCacheUsesCacheManagerToFlushCacheOfSpecifiedPages(): void
    {
        $this->cacheManagerMock->expects(self::once())->method('flushCachesInGroupByTags')->with('pages', ['pageId_1', 'pageId_2', 'pageId_3']);
        $this->subject->clearPageCache([1, 2, 3]);
    }

    #[Test]
    public function clearsCachesOfRegisteredPageIds(): void
    {
        $this->cacheManagerMock->expects(self::once())->method('flushCachesInGroupByTags')->with('pages', ['pageId_2', 'pageId_15', 'pageId_8']);

        $this->subject->getCacheTagStack()->push(8);
        $this->subject->getCacheTagStack()->push(15);
        $this->subject->getCacheTagStack()->push(2);

        $this->subject->clearCachesOfRegisteredPageIds();
    }

    #[Test]
    public function clearsCachesOfRegisteredPageCacheTags(): void
    {
        $this->cacheManagerMock->expects(self::once())->method('flushCachesInGroupByTags')->with('pages', ['pageId_2', 'pageId_15', 'pageId_8']);

        $this->subject->getCacheTagStack()->push('pageId_8');
        $this->subject->getCacheTagStack()->push('pageId_15');
        $this->subject->getCacheTagStack()->push('pageId_2');

        $this->subject->clearCachesOfRegisteredPageIds();
    }

    #[Test]
    public function clearsCachesOfDuplicateRegisteredPageIdsOnlyOnce(): void
    {
        $this->cacheManagerMock->expects(self::once())->method('flushCachesInGroupByTags')->with('pages', ['pageId_2', 'pageId_15', 'pageId_8']);

        $this->subject->getCacheTagStack()->push(8);
        $this->subject->getCacheTagStack()->push(15);
        $this->subject->getCacheTagStack()->push(15);
        $this->subject->getCacheTagStack()->push(2);
        $this->subject->getCacheTagStack()->push(2);

        $this->subject->clearCachesOfRegisteredPageIds();
    }

    #[Test]
    public function clearsCachesOfDuplicateRegisteredPageCacheTagsOnlyOnce(): void
    {
        $this->cacheManagerMock->expects(self::once())->method('flushCachesInGroupByTags')->with('pages', ['pageId_2', 'pageId_15', 'pageId_8']);

        $this->subject->getCacheTagStack()->push('pageId_8');
        $this->subject->getCacheTagStack()->push('pageId_15');
        $this->subject->getCacheTagStack()->push('pageId_15');
        $this->subject->getCacheTagStack()->push('pageId_2');
        $this->subject->getCacheTagStack()->push('pageId_2');

        $this->subject->clearCachesOfRegisteredPageIds();
    }
}
