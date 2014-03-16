<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Extbase Team
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Test case
 */
class CacheServiceTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Service\CacheService
	 */
	protected $cacheService;

	/**
	 * @var \TYPO3\CMS\Core\Cache\CacheManager|PHPUnit_Framework_MockObject_MockObject
	 */
	protected $cacheManagerMock;

	public function setUp() {
		$this->cacheService = new \TYPO3\CMS\Extbase\Service\CacheService();
		$this->cacheManagerMock = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheManager');
		$this->cacheService->injectCacheManager($this->cacheManagerMock);
	}

	/**
	 * @test
	 */
	public function clearPageCacheConvertsPageIdsToArray() {
		$this->cacheManagerMock->expects($this->once())->method('flushCachesInGroupByTag')->with('pages', 'pageId_123');
		$this->cacheService->clearPageCache(123);
	}

	/**
	 * @test
	 */
	public function clearPageCacheConvertsPageIdsToNumericArray() {
		$this->cacheManagerMock->expects($this->once())->method('flushCachesInGroupByTag')->with('pages', 'pageId_0');
		$this->cacheService->clearPageCache('Foo');
	}

	/**
	 * @test
	 */
	public function clearPageCacheDoesNotConvertPageIdsIfNoneAreSpecified() {
		$this->cacheManagerMock->expects($this->once())->method('flushCachesInGroup')->with('pages');
		$this->cacheService->clearPageCache();
	}

	/**
	 * @test
	 */
	public function clearPageCacheUsesCacheManagerToFlushCacheOfSpecifiedPages() {
		$this->cacheManagerMock->expects($this->at(0))->method('flushCachesInGroupByTag')->with('pages', 'pageId_1');
		$this->cacheManagerMock->expects($this->at(1))->method('flushCachesInGroupByTag')->with('pages', 'pageId_2');
		$this->cacheManagerMock->expects($this->at(2))->method('flushCachesInGroupByTag')->with('pages', 'pageId_3');
		$this->cacheService->clearPageCache(array(1, 2, 3));
	}

	/**
	 * @test
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function clearsCachesOfRegisteredPageIds() {
		$this->cacheManagerMock->expects($this->at(0))->method('flushCachesInGroupByTag')->with('pages', 'pageId_2');
		$this->cacheManagerMock->expects($this->at(1))->method('flushCachesInGroupByTag')->with('pages', 'pageId_15');
		$this->cacheManagerMock->expects($this->at(2))->method('flushCachesInGroupByTag')->with('pages', 'pageId_8');

		$this->cacheService->getPageIdStack()->push(8);
		$this->cacheService->getPageIdStack()->push(15);
		$this->cacheService->getPageIdStack()->push(2);

		$this->cacheService->clearCachesOfRegisteredPageIds();
	}

	/**
	 * @test
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function clearsCachesOfDuplicateRegisteredPageIdsOnlyOnce() {
		$this->cacheManagerMock->expects($this->at(0))->method('flushCachesInGroupByTag')->with('pages', 'pageId_2');
		$this->cacheManagerMock->expects($this->at(1))->method('flushCachesInGroupByTag')->with('pages', 'pageId_15');
		$this->cacheManagerMock->expects($this->at(2))->method('flushCachesInGroupByTag')->with('pages', 'pageId_8');
		$this->cacheManagerMock->expects($this->exactly(3))->method('flushCachesInGroupByTag');

		$this->cacheService->getPageIdStack()->push(8);
		$this->cacheService->getPageIdStack()->push(15);
		$this->cacheService->getPageIdStack()->push(15);
		$this->cacheService->getPageIdStack()->push(2);
		$this->cacheService->getPageIdStack()->push(2);

		$this->cacheService->clearCachesOfRegisteredPageIds();
	}
}
