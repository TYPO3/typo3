<?php
namespace TYPO3\CMS\Core\Tests\Unit\Cache;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Ingo Renner <ingo@typo3.org>
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

require_once 'Backend/MockBackend.php';

/**
 * Testcase for the TYPO3\CMS\Core\Cache\CacheFactory
 *
 * This file is a backport from FLOW3
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class FactoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function createReturnsInstanceOfTheSpecifiedCacheFrontend() {
		$mockCacheManager = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheManager', array('registerCache'), array(), '', FALSE);
		$factory = new \TYPO3\CMS\Core\Cache\CacheFactory('Testing', $mockCacheManager);
		$cache = $factory->create('TYPO3_Cache_FactoryTest_Cache', 'TYPO3\\CMS\\Core\\Cache\\Frontend\\VariableFrontend', 'TYPO3\\CMS\\Core\\Cache\\Backend\\NullBackend');
		$this->assertInstanceOf('TYPO3\\CMS\\Core\\Cache\\Frontend\\VariableFrontend', $cache);
	}

	/**
	 * @test
	 */
	public function createInjectsAnInstanceOfTheSpecifiedBackendIntoTheCacheFrontend() {
		$mockCacheManager = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheManager', array('registerCache'), array(), '', FALSE);
		$factory = new \TYPO3\CMS\Core\Cache\CacheFactory('Testing', $mockCacheManager);
		$cache = $factory->create('TYPO3_Cache_FactoryTest_Cache', 'TYPO3\\CMS\\Core\\Cache\\Frontend\\VariableFrontend', 'TYPO3\\CMS\\Core\\Cache\\Backend\\FileBackend');
		$this->assertInstanceOf('TYPO3\\CMS\\Core\\Cache\\Backend\\FileBackend', $cache->getBackend());
	}

	/**
	 * @test
	 */
	public function createRegistersTheCacheAtTheCacheManager() {
		$mockCacheManager = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheManager', array('registerCache'), array(), '', FALSE);
		$mockCacheManager->expects($this->once())->method('registerCache');
		$factory = new \TYPO3\CMS\Core\Cache\CacheFactory('Testing', $mockCacheManager);
		$factory->create('TYPO3_Cache_FactoryTest_Cache', 'TYPO3\\CMS\\Core\\Cache\\Frontend\\VariableFrontend', 'TYPO3\\CMS\\Core\\Cache\\Backend\\FileBackend');
	}

}

?>