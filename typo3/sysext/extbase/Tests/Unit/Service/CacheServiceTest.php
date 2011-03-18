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
	public function flushPageCacheUsesCacheManagerToFlushCacheOfSpecifiedPagesIfCachingFrameworkIsEnabled() {
		$mockCacheFrontend = $this->getMock('t3lib_cache_frontend_Frontend');
		$mockCacheFrontend->expects($this->at(0))->method('flushByTag')->with('pageId_1');
		$mockCacheFrontend->expects($this->at(1))->method('flushByTag')->with('pageId_2');
		$mockCacheFrontend->expects($this->at(2))->method('flushByTag')->with('pageId_3');

		$GLOBALS['typo3CacheManager']->expects($this->once())->method('getCache')->with('cache_pages')->will($this->returnValue($mockCacheFrontend));

		$this->cacheService->_set('useCachingFramework', TRUE);
		$this->cacheService->_call('flushPageCache', array(1,2,3));
	}

	/**
	 * @test
	 */
	public function flushPageCacheUsesCacheManagerToFlushCacheOfAllPagesIfCachingFrameworkIsEnabledAndPageIdsIsNull() {
		$mockCacheFrontend = $this->getMock('t3lib_cache_frontend_Frontend');
		$mockCacheFrontend->expects($this->once())->method('flush');

		$GLOBALS['typo3CacheManager']->expects($this->once())->method('getCache')->with('cache_pages')->will($this->returnValue($mockCacheFrontend));

		$this->cacheService->_set('useCachingFramework', TRUE);
		$this->cacheService->_call('flushPageCache');
	}

	/**
	 * @test
	 */
	public function flushPageCacheFlushesCacheOfSpecifiedPagesDirectlyIfCachingFrameworkIsDisabled() {
		$GLOBALS['TYPO3_DB']->expects($this->once())->method('cleanIntArray')->with(array(1,2,3))->will($this->returnValue(array(3,2,1)));
		$GLOBALS['TYPO3_DB']->expects($this->once())->method('exec_DELETEquery')->with(
			'cache_pages',
			'page_id IN (3,2,1)'
		);

		$this->cacheService->_set('useCachingFramework', FALSE);
		$this->cacheService->_call('flushPageCache', array(1,2,3));
	}

	/**
	 * test
	 */
	public function flushPageCacheFlushesCacheOfAllPagesDirectlyIfCachingFrameworkIsDisabled() {
		$GLOBALS['TYPO3_DB']->expects($this->once())->method('exec_TRUNCATEquery')->with('cache_pages');

		$this->cacheService->_set('useCachingFramework', FALSE);
		$this->cacheService->_call('flushPageCache');
	}

	/**
	 * @test
	 */
	public function flushPageSectionCacheUsesCacheManagerToFlushCacheOfSpecifiedPageSectionsIfCachingFrameworkIsEnabled() {
		$mockCacheFrontend = $this->getMock('t3lib_cache_frontend_Frontend');
		$mockCacheFrontend->expects($this->at(0))->method('flushByTag')->with('pageId_1');
		$mockCacheFrontend->expects($this->at(1))->method('flushByTag')->with('pageId_2');
		$mockCacheFrontend->expects($this->at(2))->method('flushByTag')->with('pageId_3');

		$GLOBALS['typo3CacheManager']->expects($this->once())->method('getCache')->with('cache_pagesection')->will($this->returnValue($mockCacheFrontend));

		$this->cacheService->_set('useCachingFramework', TRUE);
		$this->cacheService->_call('flushPageSectionCache', array(1,2,3));
	}

	/**
	 * @test
	 */
	public function flushPageSectionCacheUsesCacheManagerToFlushCacheOfAllPageSectionsIfCachingFrameworkIsEnabledAndPageIdsIsNull() {
		$mockCacheFrontend = $this->getMock('t3lib_cache_frontend_Frontend');
		$mockCacheFrontend->expects($this->once())->method('flush');

		$GLOBALS['typo3CacheManager']->expects($this->once())->method('getCache')->with('cache_pagesection')->will($this->returnValue($mockCacheFrontend));

		$this->cacheService->_set('useCachingFramework', TRUE);
		$this->cacheService->_call('flushPageSectionCache');
	}

	/**
	 * @test
	 */
	public function flushPageSectionCacheFlushesCacheOfSpecifiedPageSectionsDirectlyIfCachingFrameworkIsDisabled() {
		$GLOBALS['TYPO3_DB']->expects($this->once())->method('cleanIntArray')->with(array(1,2,3))->will($this->returnValue(array(3,2,1)));
		$GLOBALS['TYPO3_DB']->expects($this->once())->method('exec_DELETEquery')->with(
			'cache_pagesection',
			'page_id IN (3,2,1)'
		);

		$this->cacheService->_set('useCachingFramework', FALSE);
		$this->cacheService->_call('flushPageSectionCache', array(1,2,3));
	}

	/**
	 * test
	 */
	public function flushPageSectionCacheCacheFlushesCacheOfAllPageSectionsDirectlyIfCachingFrameworkIsDisabled() {
		$GLOBALS['TYPO3_DB']->expects($this->once())->method('exec_TRUNCATEquery')->with('cache_pagesection');

		$this->cacheService->_set('useCachingFramework', FALSE);
		$this->cacheService->_call('flushPageSectionCache');
	}

}
?>