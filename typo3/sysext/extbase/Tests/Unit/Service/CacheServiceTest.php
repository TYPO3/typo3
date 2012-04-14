<?php
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
 * Testcase for class Tx_Extbase_Service_CacheService
 *
 * @package Extbase
 * @subpackage extbase
 */

class Tx_Extbase_Tests_Unit_Service_CacheServiceTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @var Tx_Extbase_Service_CacheService
	 */
	protected $cacheService;

	/**
	 * @var t3lib_DB
	 */
	protected $typo3DbBackup;

	/**
	 * @var t3lib_cache_Manager
	 */
	protected $cacheManagerBackup;

	public function setUp() {
		$this->typo3DbBackup = $GLOBALS['TYPO3_DB'];
		$GLOBALS['TYPO3_DB'] = $this->getMock('t3lib_DB');

		$this->cacheManagerBackup = $GLOBALS['typo3CacheManager'];
		$GLOBALS['typo3CacheManager'] = $this->getMock('t3lib_cache_Manager');

		$this->cacheService = $this->getAccessibleMock('Tx_Extbase_Service_CacheService', array('dummy'));
	}

	public function tearDown() {
		$GLOBALS['TYPO3_DB'] = $this->typo3DbBackup;
		$GLOBALS['typo3CacheManager'] = $this->cacheManagerBackup;
	}

	/**
	 * @test
	 */
	public function clearPageCacheConvertsPageIdsToArray() {
		$cacheService = $this->getMock('Tx_Extbase_Service_CacheService', array('flushPageCache', 'flushPageSectionCache'));
		$cacheService->expects($this->once())->method('flushPageCache')->with(array(123));
		$cacheService->expects($this->once())->method('flushPageSectionCache')->with(array(123));
		$cacheService->clearPageCache(123);
	}

	/**
	 * @test
	 */
	public function clearPageCacheConvertsPageIdsToNumericArray() {
		$cacheService = $this->getMock('Tx_Extbase_Service_CacheService', array('flushPageCache', 'flushPageSectionCache'));
		$cacheService->expects($this->once())->method('flushPageCache')->with(array(0));
		$cacheService->expects($this->once())->method('flushPageSectionCache')->with(array(0));
		$cacheService->clearPageCache('Foo');
	}

	/**
	 * @test
	 */
	public function clearPageCacheDoesNotConvertPageIdsIfNoneAreSpecified() {
		$cacheService = $this->getMock('Tx_Extbase_Service_CacheService', array('flushPageCache', 'flushPageSectionCache'));
		$cacheService->expects($this->once())->method('flushPageCache')->with(NULL);
		$cacheService->expects($this->once())->method('flushPageSectionCache')->with(NULL);
		$cacheService->clearPageCache();
	}

	/**
	 * @test
	 */
	public function flushPageCacheUsesCacheManagerToFlushCacheOfSpecifiedPages() {
		$mockCacheFrontend = $this->getMock('t3lib_cache_frontend_Frontend');
		$mockCacheFrontend->expects($this->at(0))->method('flushByTag')->with('pageId_1');
		$mockCacheFrontend->expects($this->at(1))->method('flushByTag')->with('pageId_2');
		$mockCacheFrontend->expects($this->at(2))->method('flushByTag')->with('pageId_3');

		$GLOBALS['typo3CacheManager']->expects($this->once())->method('getCache')->with('cache_pages')->will($this->returnValue($mockCacheFrontend));

		$this->cacheService->_call('flushPageCache', array(1,2,3));
	}

	/**
	 * @test
	 */
	public function flushPageCacheUsesCacheManagerToFlushCacheOfAllPagesIfPageIdsIsNull() {
		$mockCacheFrontend = $this->getMock('t3lib_cache_frontend_Frontend');
		$mockCacheFrontend->expects($this->once())->method('flush');

		$GLOBALS['typo3CacheManager']->expects($this->once())->method('getCache')->with('cache_pages')->will($this->returnValue($mockCacheFrontend));

		$this->cacheService->_call('flushPageCache');
	}

	/**
	 * @test
	 */
	public function flushPageSectionCacheUsesCacheManagerToFlushCacheOfSpecifiedPageSections() {
		$mockCacheFrontend = $this->getMock('t3lib_cache_frontend_Frontend');
		$mockCacheFrontend->expects($this->at(0))->method('flushByTag')->with('pageId_1');
		$mockCacheFrontend->expects($this->at(1))->method('flushByTag')->with('pageId_2');
		$mockCacheFrontend->expects($this->at(2))->method('flushByTag')->with('pageId_3');

		$GLOBALS['typo3CacheManager']->expects($this->once())->method('getCache')->with('cache_pagesection')->will($this->returnValue($mockCacheFrontend));

		$this->cacheService->_call('flushPageSectionCache', array(1,2,3));
	}

	/**
	 * @test
	 */
	public function flushPageSectionCacheUsesCacheManagerToFlushCacheOfAllPageSectionsIfPageIdsIsNull() {
		$mockCacheFrontend = $this->getMock('t3lib_cache_frontend_Frontend');
		$mockCacheFrontend->expects($this->once())->method('flush');

		$GLOBALS['typo3CacheManager']->expects($this->once())->method('getCache')->with('cache_pagesection')->will($this->returnValue($mockCacheFrontend));

		$this->cacheService->_call('flushPageSectionCache');
	}

}
?>