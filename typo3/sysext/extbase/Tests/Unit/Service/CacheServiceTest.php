<?php

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

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Extbase\Service\CacheService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class CacheServiceTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Service\CacheService
     */
    protected $cacheService;

    /**
     * @var \TYPO3\CMS\Core\Cache\CacheManager|PHPUnit_Framework_MockObject_MockObject
     */
    protected $cacheManagerMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheService = new CacheService();
        $this->cacheManagerMock = $this->createMock(CacheManager::class);
        $this->cacheService->injectCacheManager($this->cacheManagerMock);
    }

    /**
     * @test
     */
    public function clearPageCacheConvertsPageIdsToArray()
    {
        $this->cacheManagerMock->expects(self::once())->method('flushCachesInGroupByTags')->with('pages', ['pageId_123']);
        $this->cacheService->clearPageCache(123);
    }

    /**
     * @test
     */
    public function clearPageCacheConvertsPageIdsToNumericArray()
    {
        $this->cacheManagerMock->expects(self::once())->method('flushCachesInGroupByTags')->with('pages', ['pageId_0']);
        $this->cacheService->clearPageCache('Foo');
    }

    /**
     * @test
     */
    public function clearPageCacheDoesNotConvertPageIdsIfNoneAreSpecified()
    {
        $this->cacheManagerMock->expects(self::once())->method('flushCachesInGroup')->with('pages');
        $this->cacheService->clearPageCache();
    }

    /**
     * @test
     */
    public function clearPageCacheUsesCacheManagerToFlushCacheOfSpecifiedPages()
    {
        $this->cacheManagerMock->expects(self::at(0))->method('flushCachesInGroupByTags')->with('pages', ['pageId_1', 'pageId_2', 'pageId_3']);
        $this->cacheService->clearPageCache([1, 2, 3]);
    }

    /**
     * @test
     */
    public function clearsCachesOfRegisteredPageIds()
    {
        $this->cacheManagerMock->expects(self::at(0))->method('flushCachesInGroupByTags')->with('pages', ['pageId_2', 'pageId_15', 'pageId_8']);

        $this->cacheService->getPageIdStack()->push(8);
        $this->cacheService->getPageIdStack()->push(15);
        $this->cacheService->getPageIdStack()->push(2);

        $this->cacheService->clearCachesOfRegisteredPageIds();
    }

    /**
     * @test
     */
    public function clearsCachesOfDuplicateRegisteredPageIdsOnlyOnce()
    {
        $this->cacheManagerMock->expects(self::at(0))->method('flushCachesInGroupByTags')->with('pages', ['pageId_2', 'pageId_15', 'pageId_8']);

        $this->cacheService->getPageIdStack()->push(8);
        $this->cacheService->getPageIdStack()->push(15);
        $this->cacheService->getPageIdStack()->push(15);
        $this->cacheService->getPageIdStack()->push(2);
        $this->cacheService->getPageIdStack()->push(2);

        $this->cacheService->clearCachesOfRegisteredPageIds();
    }
}
