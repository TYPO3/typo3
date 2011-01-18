<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2011 Ingo Renner <ingo@typo3.org>
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
 * Testcase for the Cache Manager
 *
 * This file is a backport from FLOW3
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage tests
 * @version $Id$
 */
class t3lib_cache_ManagerTest extends tx_phpunit_testcase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function initializeCreatesAndRegistersAllCachesDefinedInTheCachesConfiguration() {
		$mockCacheFactory = $this->getMock('t3lib_cache_Factory', array(), array(), '', FALSE);
		$mockCacheFactory->expects($this->at(1))->method('create')->with('cache1', 't3lib_cache_frontend_VariableFrontend', 't3lib_cache_backend_FileBackend', array());
		$mockCacheFactory->expects($this->at(2))->method('create')->with('cache2', 't3lib_cache_frontend_StringFrontend', 't3lib_cache_backend_NullBackend', array('foo' => 'bar'));

		$cacheConfigurations = array(
			'cache1' => array(
				'frontend' => 't3lib_cache_frontend_VariableFrontend',
				'backend' => 't3lib_cache_backend_FileBackend',
				'backendOptions' => array(),
			),
			'cache2' => array(
				'frontend' => 't3lib_cache_frontend_StringFrontend',
				'backend' => 't3lib_cache_backend_NullBackend',
				'backendOptions' => array('foo' => 'bar')
			),
		);

		$manager = new t3lib_cache_Manager();
		$manager->setCacheConfigurations($cacheConfigurations);
		$manager->setCacheFactory($mockCacheFactory);
		$manager->initialize();
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 * @expectedException t3lib_cache_exception_DuplicateIdentifier
	 */
	public function managerThrowsExceptionOnCacheRegistrationWithAlreadyExistingIdentifier() {
		$manager = new t3lib_cache_Manager();
		$backend = $this->getMock('t3lib_cache_backend_AbstractBackend', array(), array(), '', FALSE);

		$cache1 = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache1->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('test'));

		$cache2 = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache2->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('test'));

		$manager->registerCache($cache1);
		$manager->registerCache($cache2);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function managerReturnsThePreviouslyRegisteredCache() {
		$manager = new t3lib_cache_Manager();
		$backend = $this->getMock('t3lib_cache_backend_AbstractBackend', array(), array(), '', FALSE);

		$cache1 = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache1->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('cache1'));

		$cache2 = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache2->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('cache2'));

		$manager->registerCache($cache1);
		$manager->registerCache($cache2);

		$this->assertSame($cache2, $manager->getCache('cache2'), 'The cache returned by getCache() was not the same I registered.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 * @expectedException t3lib_cache_exception_NoSuchCache
	 */
	public function getCacheThrowsExceptionForNonExistingIdentifier() {
		$manager = new t3lib_cache_Manager();
		$backend = $this->getMock('t3lib_cache_backend_AbstractBackend', array(), array(), '', FALSE);
		$cache = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('someidentifier'));

		$manager->registerCache($cache);
		$manager->getCache('someidentifier');

		$manager->getCache('doesnotexist');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function hasCacheReturnsCorrectResult() {
		$manager = new t3lib_cache_Manager();
		$backend = $this->getMock('t3lib_cache_backend_AbstractBackend', array(), array(), '', FALSE);
		$cache1 = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache1->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('cache1'));
		$manager->registerCache($cache1);

		$this->assertTrue($manager->hasCache('cache1'), 'hasCache() did not return TRUE.');
		$this->assertFalse($manager->hasCache('cache2'), 'hasCache() did not return FALSE.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function flushCachesByTagCallsTheFlushByTagMethodOfAllRegisteredCaches() {
		$manager = new t3lib_cache_Manager();

		$backend = $this->getMock('t3lib_cache_backend_AbstractBackend', array(), array(), '', FALSE);

		$cache1 = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache1->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('cache1'));
		$cache1->expects($this->once())->method('flushByTag')->with($this->equalTo('theTag'));
		$manager->registerCache($cache1);

		$cache2 = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache2->expects($this->once())->method('flushByTag')->with($this->equalTo('theTag'));
		$manager->registerCache($cache2);

		$manager->flushCachesByTag('theTag');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function flushCachesCallsTheFlushMethodOfAllRegisteredCaches() {
		$manager = new t3lib_cache_Manager();
		$backend = $this->getMock('t3lib_cache_backend_AbstractBackend', array(), array(), '', FALSE);

		$cache1 = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache1->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('cache1'));
		$cache1->expects($this->once())->method('flush');
		$manager->registerCache($cache1);

		$cache2 = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache2->expects($this->once())->method('flush');
		$manager->registerCache($cache2);

		$manager->flushCaches();
	}

}

?>