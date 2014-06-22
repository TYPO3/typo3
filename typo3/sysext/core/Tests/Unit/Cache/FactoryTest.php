<?php
namespace TYPO3\CMS\Core\Tests\Unit\Cache;

/**
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
