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

/**
 * Testcase for the TYPO3\CMS\Core\Cache\CacheManager
 *
 * This file is a backport from FLOW3
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class CacheManagerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Core\Cache\Exception\DuplicateIdentifierException
	 */
	public function managerThrowsExceptionOnCacheRegistrationWithAlreadyExistingIdentifier() {
		$manager = new \TYPO3\CMS\Core\Cache\CacheManager();
		$cache1 = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache1->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('test'));
		$cache2 = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache2->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('test'));
		$manager->registerCache($cache1);
		$manager->registerCache($cache2);
	}

	/**
	 * @test
	 */
	public function managerReturnsThePreviouslyRegisteredCache() {
		$manager = new \TYPO3\CMS\Core\Cache\CacheManager();
		$cache1 = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache1->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('cache1'));
		$cache2 = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache2->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('cache2'));
		$manager->registerCache($cache1);
		$manager->registerCache($cache2);
		$this->assertSame($cache2, $manager->getCache('cache2'), 'The cache returned by getCache() was not the same I registered.');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
	 */
	public function getCacheThrowsExceptionForNonExistingIdentifier() {
		$manager = new \TYPO3\CMS\Core\Cache\CacheManager();
		$cache = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('someidentifier'));
		$manager->registerCache($cache);
		$manager->getCache('someidentifier');
		$manager->getCache('doesnotexist');
	}

	/**
	 * @test
	 */
	public function hasCacheReturnsCorrectResult() {
		$manager = new \TYPO3\CMS\Core\Cache\CacheManager();
		$cache1 = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache1->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('cache1'));
		$manager->registerCache($cache1);
		$this->assertTrue($manager->hasCache('cache1'), 'hasCache() did not return TRUE.');
		$this->assertFalse($manager->hasCache('cache2'), 'hasCache() did not return FALSE.');
	}

	/**
	 * @test
	 */
	public function flushCachesByTagCallsTheFlushByTagMethodOfAllRegisteredCaches() {
		$manager = new \TYPO3\CMS\Core\Cache\CacheManager();
		$cache1 = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache1->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('cache1'));
		$cache1->expects($this->once())->method('flushByTag')->with($this->equalTo('theTag'));
		$manager->registerCache($cache1);
		$cache2 = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache2->expects($this->once())->method('flushByTag')->with($this->equalTo('theTag'));
		$manager->registerCache($cache2);
		$manager->flushCachesByTag('theTag');
	}

	/**
	 * @test
	 */
	public function flushCachesCallsTheFlushMethodOfAllRegisteredCaches() {
		$manager = new \TYPO3\CMS\Core\Cache\CacheManager();
		$cache1 = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache1->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('cache1'));
		$cache1->expects($this->once())->method('flush');
		$manager->registerCache($cache1);
		$cache2 = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache2->expects($this->once())->method('flush');
		$manager->registerCache($cache2);
		$manager->flushCaches();
	}

	/**
	 * @test
	 */
	public function getCacheCreatesCacheInstanceWithGivenConfiguration() {
		$manager = new \TYPO3\CMS\Core\Cache\CacheManager();
		$cacheIdentifier = 'Test' . md5(uniqid(mt_rand(), TRUE));
		$cacheObjectName = 'testCache';
		$backendObjectName = 'testBackend';
		$backendOptions = array('foo');
		$configuration = array(
			$cacheIdentifier => array(
				'frontend' => $cacheObjectName,
				'backend' => $backendObjectName,
				'options' => $backendOptions
			)
		);
		$factory = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheFactory', array('create'), array(), '', FALSE);
		$factory->expects($this->once())->method('create')->with($cacheIdentifier, $cacheObjectName, $backendObjectName, $backendOptions);
		$manager->injectCacheFactory($factory);
		$manager->setCacheConfigurations($configuration);
		$manager->getCache($cacheIdentifier);
	}

	/**
	 * @test
	 */
	public function getCacheCreatesCacheInstanceWithFallbackToDefaultFrontend() {
		$manager = new \TYPO3\CMS\Core\Cache\CacheManager();
		$cacheIdentifier = 'Test' . md5(uniqid(mt_rand(), TRUE));
		$backendObjectName = 'testBackend';
		$backendOptions = array('foo');
		$configuration = array(
			$cacheIdentifier => array(
				'backend' => $backendObjectName,
				'options' => $backendOptions
			)
		);
		$factory = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheFactory', array('create'), array(), '', FALSE);
		$factory->expects($this->once())->method('create')->with($cacheIdentifier, 'TYPO3\\CMS\\Core\\Cache\\Frontend\\VariableFrontend', $backendObjectName, $backendOptions);
		$manager->injectCacheFactory($factory);
		$manager->setCacheConfigurations($configuration);
		$manager->getCache($cacheIdentifier);
	}

	/**
	 * @test
	 */
	public function getCacheCreatesCacheInstanceWithFallbackToDefaultBackend() {
		$manager = new \TYPO3\CMS\Core\Cache\CacheManager();
		$cacheIdentifier = 'Test' . md5(uniqid(mt_rand(), TRUE));
		$cacheObjectName = 'testCache';
		$backendOptions = array('foo');
		$configuration = array(
			$cacheIdentifier => array(
				'frontend' => $cacheObjectName,
				'options' => $backendOptions
			)
		);
		$factory = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheFactory', array('create'), array(), '', FALSE);
		$factory->expects($this->once())->method('create')->with($cacheIdentifier, $cacheObjectName, 'TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend', $backendOptions);
		$manager->injectCacheFactory($factory);
		$manager->setCacheConfigurations($configuration);
		$manager->getCache($cacheIdentifier);
	}

	/**
	 * @test
	 */
	public function getCacheCreatesCacheInstanceWithFallbackToDefaultBackenOptions() {
		$manager = new \TYPO3\CMS\Core\Cache\CacheManager();
		$cacheIdentifier = 'Test' . md5(uniqid(mt_rand(), TRUE));
		$cacheObjectName = 'testCache';
		$backendObjectName = 'testBackend';
		$configuration = array(
			$cacheIdentifier => array(
				'frontend' => $cacheObjectName,
				'backend' => $backendObjectName
			)
		);
		$factory = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheFactory', array('create'), array(), '', FALSE);
		$factory->expects($this->once())->method('create')->with($cacheIdentifier, $cacheObjectName, $backendObjectName, array());
		$manager->injectCacheFactory($factory);
		$manager->setCacheConfigurations($configuration);
		$manager->getCache($cacheIdentifier);
	}

}

?>