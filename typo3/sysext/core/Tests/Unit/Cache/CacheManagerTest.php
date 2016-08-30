<?php
namespace TYPO3\CMS\Core\Tests\Unit\Cache;

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

/**
 * Testcase for the TYPO3\CMS\Core\Cache\CacheManager
 *
 * This file is a backport from FLOW3
 */
class CacheManagerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Cache\Exception\DuplicateIdentifierException
     */
    public function managerThrowsExceptionOnCacheRegistrationWithAlreadyExistingIdentifier()
    {
        $manager = new \TYPO3\CMS\Core\Cache\CacheManager();
        $cache1 = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, ['getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'], [], '', false);
        $cache1->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('test'));
        $cache2 = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, ['getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'], [], '', false);
        $cache2->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('test'));
        $manager->registerCache($cache1);
        $manager->registerCache($cache2);
    }

    /**
     * @test
     */
    public function managerReturnsThePreviouslyRegisteredCache()
    {
        $manager = new \TYPO3\CMS\Core\Cache\CacheManager();
        $cache1 = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, ['getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'], [], '', false);
        $cache1->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('cache1'));
        $cache2 = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, ['getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'], [], '', false);
        $cache2->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('cache2'));
        $manager->registerCache($cache1);
        $manager->registerCache($cache2);
        $this->assertSame($cache2, $manager->getCache('cache2'), 'The cache returned by getCache() was not the same I registered.');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
     */
    public function getCacheThrowsExceptionForNonExistingIdentifier()
    {
        $manager = new \TYPO3\CMS\Core\Cache\CacheManager();
        $cache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, ['getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'], [], '', false);
        $cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('someidentifier'));
        $manager->registerCache($cache);
        $manager->getCache('someidentifier');
        $manager->getCache('doesnotexist');
    }

    /**
     * @test
     */
    public function hasCacheReturnsCorrectResult()
    {
        $manager = new \TYPO3\CMS\Core\Cache\CacheManager();
        $cache1 = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, ['getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'], [], '', false);
        $cache1->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('cache1'));
        $manager->registerCache($cache1);
        $this->assertTrue($manager->hasCache('cache1'), 'hasCache() did not return TRUE.');
        $this->assertFalse($manager->hasCache('cache2'), 'hasCache() did not return FALSE.');
    }

    /**
     * @test
     */
    public function flushCachesByTagCallsTheFlushByTagMethodOfAllRegisteredCaches()
    {
        $manager = new \TYPO3\CMS\Core\Cache\CacheManager();
        $cache1 = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, ['getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'], [], '', false);
        $cache1->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('cache1'));
        $cache1->expects($this->once())->method('flushByTag')->with($this->equalTo('theTag'));
        $manager->registerCache($cache1);
        $cache2 = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, ['getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'], [], '', false);
        $cache2->expects($this->once())->method('flushByTag')->with($this->equalTo('theTag'));
        $manager->registerCache($cache2);
        $manager->flushCachesByTag('theTag');
    }

    /**
     * @test
     */
    public function flushCachesCallsTheFlushMethodOfAllRegisteredCaches()
    {
        $manager = new \TYPO3\CMS\Core\Cache\CacheManager();
        $cache1 = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, ['getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'], [], '', false);
        $cache1->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('cache1'));
        $cache1->expects($this->once())->method('flush');
        $manager->registerCache($cache1);
        $cache2 = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, ['getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'], [], '', false);
        $cache2->expects($this->once())->method('flush');
        $manager->registerCache($cache2);
        $manager->flushCaches();
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException
     */
    public function flushCachesInGroupThrowsExceptionForNonExistingGroup()
    {
        $manager = new \TYPO3\CMS\Core\Cache\CacheManager();
        $manager->flushCachesInGroup('nonExistingGroup');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException
     */
    public function flushCachesInGroupByTagThrowsExceptionForNonExistingGroup()
    {
        $manager = new \TYPO3\CMS\Core\Cache\CacheManager();
        $manager->flushCachesInGroup('nonExistingGroup', 'someTag');
    }

    /**
     * @test
     */
    public function getCacheCreatesCacheInstanceWithGivenConfiguration()
    {
        $manager = new \TYPO3\CMS\Core\Cache\CacheManager();
        $cacheIdentifier = $this->getUniqueId('Test');
        $cacheObjectName = 'testCache';
        $backendObjectName = 'testBackend';
        $backendOptions = ['foo'];
        $configuration = [
            $cacheIdentifier => [
                'frontend' => $cacheObjectName,
                'backend' => $backendObjectName,
                'options' => $backendOptions
            ]
        ];
        $factory = $this->getMock(\TYPO3\CMS\Core\Cache\CacheFactory::class, ['create'], [], '', false);
        $factory->expects($this->once())->method('create')->with($cacheIdentifier, $cacheObjectName, $backendObjectName, $backendOptions);
        $manager->injectCacheFactory($factory);
        $manager->setCacheConfigurations($configuration);
        $manager->getCache($cacheIdentifier);
    }

    /**
     * @test
     */
    public function getCacheCreatesCacheInstanceWithFallbackToDefaultFrontend()
    {
        $manager = new \TYPO3\CMS\Core\Cache\CacheManager();
        $cacheIdentifier = $this->getUniqueId('Test');
        $backendObjectName = 'testBackend';
        $backendOptions = ['foo'];
        $configuration = [
            $cacheIdentifier => [
                'backend' => $backendObjectName,
                'options' => $backendOptions
            ]
        ];
        $factory = $this->getMock(\TYPO3\CMS\Core\Cache\CacheFactory::class, ['create'], [], '', false);
        $factory->expects($this->once())->method('create')->with($cacheIdentifier, \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class, $backendObjectName, $backendOptions);
        $manager->injectCacheFactory($factory);
        $manager->setCacheConfigurations($configuration);
        $manager->getCache($cacheIdentifier);
    }

    /**
     * @test
     */
    public function getCacheCreatesCacheInstanceWithFallbackToDefaultBackend()
    {
        $manager = new \TYPO3\CMS\Core\Cache\CacheManager();
        $cacheIdentifier = $this->getUniqueId('Test');
        $cacheObjectName = 'testCache';
        $backendOptions = ['foo'];
        $configuration = [
            $cacheIdentifier => [
                'frontend' => $cacheObjectName,
                'options' => $backendOptions
            ]
        ];
        $factory = $this->getMock(\TYPO3\CMS\Core\Cache\CacheFactory::class, ['create'], [], '', false);
        $factory->expects($this->once())->method('create')->with($cacheIdentifier, $cacheObjectName, \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class, $backendOptions);
        $manager->injectCacheFactory($factory);
        $manager->setCacheConfigurations($configuration);
        $manager->getCache($cacheIdentifier);
    }

    /**
     * @test
     */
    public function getCacheCreatesCacheInstanceWithFallbackToDefaultBackenOptions()
    {
        $manager = new \TYPO3\CMS\Core\Cache\CacheManager();
        $cacheIdentifier = $this->getUniqueId('Test');
        $cacheObjectName = 'testCache';
        $backendObjectName = 'testBackend';
        $configuration = [
            $cacheIdentifier => [
                'frontend' => $cacheObjectName,
                'backend' => $backendObjectName
            ]
        ];
        $factory = $this->getMock(\TYPO3\CMS\Core\Cache\CacheFactory::class, ['create'], [], '', false);
        $factory->expects($this->once())->method('create')->with($cacheIdentifier, $cacheObjectName, $backendObjectName, []);
        $manager->injectCacheFactory($factory);
        $manager->setCacheConfigurations($configuration);
        $manager->getCache($cacheIdentifier);
    }
}
