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

require_once 'backend/class.t3lib_cache_backend_mockbackend.php';

/**
 * Testcase for the Cache Factory
 *
 * This file is a backport from FLOW3
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage tests
 */
class t3lib_cache_FactoryTest extends tx_phpunit_testcase {

	/**
	 * Sets up this testcase
	 *
	 * @return void
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function setUp() {
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheBackends']['t3lib_cache_backend_MockBackend'] = 't3lib_cache_backend_MockBackend';
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function createReturnsInstanceOfTheSpecifiedCacheFrontend() {
		$backend = $this->getMock('t3lib_cache_backend_NullBackend', array(), array(), '', FALSE);
		$cache = $this->getMock('t3lib_cache_frontend_VariableFrontend', array(), array(), '', FALSE);

		$mockCacheManager = $this->getMock('t3lib_cache_Manager', array('registerCache'), array(), '', FALSE);
		$factory = new t3lib_cache_Factory();
		$factory->setCacheManager($mockCacheManager);

		$cache = $factory->create('TYPO3_Cache_FactoryTest_Cache', 't3lib_cache_frontend_VariableFrontend', 't3lib_cache_backend_NullBackend');
		$this->assertInstanceOf('t3lib_cache_frontend_VariableFrontend', $cache);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function createInjectsAnInstanceOfTheSpecifiedBackendIntoTheCacheFrontend() {
		$backend = $this->getMock('t3lib_cache_backend_FileBackend', array(), array(), '', FALSE);
		$cache = $this->getMock('t3lib_cache_frontend_VariableFrontend', array(), array(), '', FALSE);

		$mockCacheManager = $this->getMock('t3lib_cache_Manager', array('registerCache'), array(), '', FALSE);
		$factory = new t3lib_cache_Factory();
		$factory->setCacheManager($mockCacheManager);

		$factory->create('TYPO3_Cache_FactoryTest_Cache', 't3lib_cache_frontend_VariableFrontend', 't3lib_cache_backend_FileBackend');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function createPassesBackendOptionsToTheCreatedBackend() {
		$someValue = microtime();
		$backendOptions = array('someOption' => $someValue);

		$cache = $this->getMock('t3lib_cache_frontend_VariableFrontend', array(), array(), '', FALSE);

		$mockCacheManager = $this->getMock('t3lib_cache_Manager', array('registerCache'), array(), '', FALSE);
		$factory = new t3lib_cache_Factory();
		$factory->setCacheManager($mockCacheManager);

		$cache = $factory->create('TYPO3_Cache_FactoryTest_Cache', 't3lib_cache_frontend_VariableFrontend', 't3lib_cache_backend_MockBackend', $backendOptions);

		$this->assertEquals($someValue, $cache->getBackend()->getSomeOption(), 'create() did not pass the backend options to the backend.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
/*
 	Not working yet

	public function createRegistersTheCacheAtTheCacheManager() {
		$cacheIdentifier = 'TYPO3_Cache_FactoryTest_Cache';
		$backend = $this->getMock('t3lib_cache_backend_NullBackend', array(), array(), '', FALSE);
		$cache = $this->getMock('t3lib_cache_frontend_VariableFrontend', array('getCache'), array($cacheIdentifier, $backend), '', true);
		$cache->getBackend()->setCache($cache);

		$mockCacheManager = $this->getMock('t3lib_cache_Manager', array('registerCache'), array(), '', FALSE);
		$mockCacheManager->expects($this->once())->method('registerCache')->with($cache);
#		$mockCacheManager->expects($this->once())->method('registerCache')->with('t3lib_cache_frontend_VariableFrontend');
		$factory = new t3lib_cache_Factory();
		$factory->setCacheManager($mockCacheManager);

		$factory->create($cacheIdentifier, 't3lib_cache_frontend_VariableFrontend', 't3lib_cache_backend_NullBackend');
	}
*/
}

?>