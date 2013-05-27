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
 * Testcase for class \TYPO3\CMS\Extbase\Service\CacheService
 */
class CacheServiceTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Service\CacheService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $cacheService;

	/**
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $typo3DbBackup;

	/**
	 * @var \TYPO3\CMS\Core\Cache\CacheManager
	 */
	protected $cacheManagerBackup;

	public function setUp() {
		$this->typo3DbBackup = $GLOBALS['TYPO3_DB'];
		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection');
		$this->cacheManagerBackup = $GLOBALS['typo3CacheManager'];
		$GLOBALS['typo3CacheManager'] = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheManager');
		$this->cacheService = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Service\\CacheService', array('dummy'));
	}

	public function tearDown() {
		$GLOBALS['TYPO3_DB'] = $this->typo3DbBackup;
		$GLOBALS['typo3CacheManager'] = $this->cacheManagerBackup;
	}

	/**
	 * @test
	 */
	public function clearPageCacheConvertsPageIdsToArray() {
		$cacheService = $this->getMock('TYPO3\\CMS\\Extbase\\Service\\CacheService', array('flushPageCache', 'flushPageSectionCache'));
		$cacheService->expects($this->once())->method('flushPageCache')->with(array(123));
		$cacheService->expects($this->once())->method('flushPageSectionCache')->with(array(123));
		$cacheService->clearPageCache(123);
	}

	/**
	 * @test
	 */
	public function clearPageCacheConvertsPageIdsToNumericArray() {
		$cacheService = $this->getMock('TYPO3\\CMS\\Extbase\\Service\\CacheService', array('flushPageCache', 'flushPageSectionCache'));
		$cacheService->expects($this->once())->method('flushPageCache')->with(array(0));
		$cacheService->expects($this->once())->method('flushPageSectionCache')->with(array(0));
		$cacheService->clearPageCache('Foo');
	}

	/**
	 * @test
	 */
	public function clearPageCacheDoesNotConvertPageIdsIfNoneAreSpecified() {
		$cacheService = $this->getMock('TYPO3\\CMS\\Extbase\\Service\\CacheService', array('flushPageCache', 'flushPageSectionCache'));
		$cacheService->expects($this->once())->method('flushPageCache')->with(NULL);
		$cacheService->expects($this->once())->method('flushPageSectionCache')->with(NULL);
		$cacheService->clearPageCache();
	}

	/**
	 * @test
	 */
	public function flushPageCacheUsesCacheManagerToFlushCacheOfSpecifiedPages() {
		$mockCacheFrontend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\FrontendInterface');
		$mockCacheFrontend->expects($this->at(0))->method('flushByTag')->with('pageId_1');
		$mockCacheFrontend->expects($this->at(1))->method('flushByTag')->with('pageId_2');
		$mockCacheFrontend->expects($this->at(2))->method('flushByTag')->with('pageId_3');
		$GLOBALS['typo3CacheManager']->expects($this->once())->method('getCache')->with('cache_pages')->will($this->returnValue($mockCacheFrontend));
		$this->cacheService->_call('flushPageCache', array(1, 2, 3));
	}

	/**
	 * @test
	 */
	public function flushPageCacheUsesCacheManagerToFlushCacheOfAllPagesIfPageIdsIsNull() {
		$mockCacheFrontend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\FrontendInterface');
		$mockCacheFrontend->expects($this->once())->method('flush');
		$GLOBALS['typo3CacheManager']->expects($this->once())->method('getCache')->with('cache_pages')->will($this->returnValue($mockCacheFrontend));
		$this->cacheService->_call('flushPageCache');
	}

	/**
	 * @test
	 */
	public function flushPageSectionCacheUsesCacheManagerToFlushCacheOfSpecifiedPageSections() {
		$mockCacheFrontend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\FrontendInterface');
		$mockCacheFrontend->expects($this->at(0))->method('flushByTag')->with('pageId_1');
		$mockCacheFrontend->expects($this->at(1))->method('flushByTag')->with('pageId_2');
		$mockCacheFrontend->expects($this->at(2))->method('flushByTag')->with('pageId_3');
		$GLOBALS['typo3CacheManager']->expects($this->once())->method('getCache')->with('cache_pagesection')->will($this->returnValue($mockCacheFrontend));
		$this->cacheService->_call('flushPageSectionCache', array(1, 2, 3));
	}

	/**
	 * @test
	 */
	public function flushPageSectionCacheUsesCacheManagerToFlushCacheOfAllPageSectionsIfPageIdsIsNull() {
		$mockCacheFrontend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\FrontendInterface');
		$mockCacheFrontend->expects($this->once())->method('flush');
		$GLOBALS['typo3CacheManager']->expects($this->once())->method('getCache')->with('cache_pagesection')->will($this->returnValue($mockCacheFrontend));
		$this->cacheService->_call('flushPageSectionCache');
	}

	/**
	 * @test
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function clearsCachesOfRegisteredPageIds() {

		$cacheService = $this->getMock('TYPO3\\CMS\\Extbase\\Service\\CacheService', array('flushPageCache', 'flushPageSectionCache'));
		$cacheService->getPageIdStack()->push(8);
		$cacheService->getPageIdStack()->push(15);
		$cacheService->getPageIdStack()->push(2);

		$cacheService->expects($this->once())->method('flushPageCache')->with(array(2,15,8));
		$cacheService->expects($this->once())->method('flushPageSectionCache')->with(array(2,15,8));
		$cacheService->clearCachesOfRegisteredPageIds();
	}

	/**
	 * @test
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function clearsCachesOfDuplicateRegisteredPageIdsOnlyOnce() {

		$cacheService = $this->getMock('TYPO3\\CMS\\Extbase\\Service\\CacheService', array('flushPageCache', 'flushPageSectionCache'));
		$cacheService->getPageIdStack()->push(8);
		$cacheService->getPageIdStack()->push(15);
		$cacheService->getPageIdStack()->push(15);
		$cacheService->getPageIdStack()->push(2);

		$cacheService->expects($this->once())->method('flushPageCache')->with(array(2, 15, 8));
		$cacheService->expects($this->once())->method('flushPageSectionCache')->with(array(2, 15, 8));
		$cacheService->clearCachesOfRegisteredPageIds();
	}
}

?>