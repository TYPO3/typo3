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

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\InvalidBackendException;
use TYPO3\CMS\Core\Cache\Exception\InvalidCacheException;
use TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend;
use TYPO3\CMS\Core\Tests\Unit\Cache\Fixtures\BackendConfigurationOptionFixture;
use TYPO3\CMS\Core\Tests\Unit\Cache\Fixtures\BackendDefaultFixture;
use TYPO3\CMS\Core\Tests\Unit\Cache\Fixtures\BackendFixture;
use TYPO3\CMS\Core\Tests\Unit\Cache\Fixtures\BackendInitializeObjectFixture;
use TYPO3\CMS\Core\Tests\Unit\Cache\Fixtures\FrontendBackendInstanceFixture;
use TYPO3\CMS\Core\Tests\Unit\Cache\Fixtures\FrontendDefaultFixture;
use TYPO3\CMS\Core\Tests\Unit\Cache\Fixtures\FrontendFixture;
use TYPO3\CMS\Core\Tests\Unit\Cache\Fixtures\FrontendIdentifierFixture;
use TYPO3\CMS\Core\Tests\Unit\Cache\Fixtures\FrontendInitializeObjectFixture;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Testcase for the TYPO3\CMS\Core\Cache\CacheManager
 *
 * This file is a backport from FLOW3
 */
class CacheManagerTest extends UnitTestCase
{
    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Cache\Exception\DuplicateIdentifierException
     */
    public function managerThrowsExceptionOnCacheRegistrationWithAlreadyExistingIdentifier()
    {
        $manager = new CacheManager();
        $cache1 = $this->getMock(AbstractFrontend::class, array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', false);
        $cache1->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('test'));
        $cache2 = $this->getMock(AbstractFrontend::class, array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', false);
        $cache2->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('test'));
        $manager->registerCache($cache1);
        $manager->registerCache($cache2);
    }

    /**
     * @test
     */
    public function managerReturnsThePreviouslyRegisteredCache()
    {
        $manager = new CacheManager();
        $cache1 = $this->getMock(AbstractFrontend::class, array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', false);
        $cache1->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('cache1'));
        $cache2 = $this->getMock(AbstractFrontend::class, array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', false);
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
        $manager = new CacheManager();
        $cache = $this->getMock(AbstractFrontend::class, array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', false);
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
        $manager = new CacheManager();
        $cache1 = $this->getMock(AbstractFrontend::class, array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', false);
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
        $manager = new CacheManager();
        $cache1 = $this->getMock(AbstractFrontend::class, array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', false);
        $cache1->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('cache1'));
        $cache1->expects($this->once())->method('flushByTag')->with($this->equalTo('theTag'));
        $manager->registerCache($cache1);
        $cache2 = $this->getMock(AbstractFrontend::class, array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', false);
        $cache2->expects($this->once())->method('flushByTag')->with($this->equalTo('theTag'));
        $manager->registerCache($cache2);
        $manager->flushCachesByTag('theTag');
    }

    /**
     * @test
     */
    public function flushCachesCallsTheFlushMethodOfAllRegisteredCaches()
    {
        $manager = new CacheManager();
        $cache1 = $this->getMock(AbstractFrontend::class, array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', false);
        $cache1->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('cache1'));
        $cache1->expects($this->once())->method('flush');
        $manager->registerCache($cache1);
        $cache2 = $this->getMock(AbstractFrontend::class, array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', false);
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
        $manager = new CacheManager();
        $manager->flushCachesInGroup('nonExistingGroup');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException
     */
    public function flushCachesInGroupByTagThrowsExceptionForNonExistingGroup()
    {
        $manager = new CacheManager();
        $manager->flushCachesInGroup('nonExistingGroup');
    }

    /**
     * @test
     */
    public function getCacheThrowsExceptionIfConfiguredFrontendDoesNotImplementFrontendInterface()
    {
        $manager = new CacheManager();
        $cacheIdentifier = 'aCache';
        $configuration = [
            $cacheIdentifier => [
                'frontend' => \stdClass::class,
                'backend' => BackendFixture::class,
                'options' => [],
            ],
        ];
        $manager->setCacheConfigurations($configuration);
        $this->expectException(InvalidCacheException::class);
        $this->expectExceptionCode(1464550984);
        $manager->getCache($cacheIdentifier);
    }

    /**
     * @test
     */
    public function getCacheThrowsExceptionIfConfiguredBackendDoesNotImplementBackendInterface()
    {
        $manager = new CacheManager();
        $cacheIdentifier = 'aCache';
        $configuration = [
            $cacheIdentifier => [
                'frontend' => FrontendFixture::class,
                'backend' => \stdClass::class,
                'options' => [],
            ],
        ];
        $manager->setCacheConfigurations($configuration);
        $this->expectException(InvalidBackendException::class);
        $this->expectExceptionCode(1464550977);
        $manager->getCache($cacheIdentifier);
    }

    /**
     * @test
     */
    public function getCacheCallsInitializeObjectOnFrontendInstance()
    {
        $manager = new CacheManager();
        $cacheIdentifier = 'aCache';
        $configuration = [
            $cacheIdentifier => [
                'backend' => BackendFixture::class,
                'frontend' => FrontendInitializeObjectFixture::class,
                'options' => [],
            ],
        ];
        $manager->setCacheConfigurations($configuration);
        // BackendInitializeObjectFixture throws exception if initializeObject() is called, so expect this
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1464553495);
        $manager->getCache($cacheIdentifier);
    }

    /**
     * @test
     */
    public function getCacheCallsInitializeObjectOnBackendInstance()
    {
        $manager = new CacheManager();
        $cacheIdentifier = 'aCache';
        $configuration = [
            $cacheIdentifier => [
                'backend' => BackendInitializeObjectFixture::class,
                'frontend' => FrontendFixture::class,
                'options' => [],
            ],
        ];
        $manager->setCacheConfigurations($configuration);
        // BackendInitializeObjectFixture throws exception if initializeObject() is called, so expect this
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1464552894);
        $manager->getCache($cacheIdentifier);
    }

    /**
     * @test
     */
    public function getCacheCreatesBackendWithGivenConfiguration()
    {
        $manager = new CacheManager();
        $cacheIdentifier = 'aCache';
        $configuration = [
            $cacheIdentifier => [
                'backend' => BackendConfigurationOptionFixture::class,
                'frontend' => FrontendFixture::class,
                'options' => [
                    'anOption' => 'anOptionValue',
                ],
            ],
        ];
        $manager->setCacheConfigurations($configuration);
        // BackendInitializeObjectFixture throws exception if initializeObject() is called, so expect this
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1464555007);
        $manager->getCache($cacheIdentifier);
    }

    /**
     * @test
     */
    public function getCacheCreatesCacheInstanceWithFallbackToDefaultFrontend()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|CacheManager $manager */
        $manager = $this->getAccessibleMock(CacheManager::class, ['dummy'], [], '', false);
        $cacheIdentifier = $this->getUniqueId('Test');
        $configuration = array(
            $cacheIdentifier => array(
                'backend' => BackendFixture::class,
                'options' => []
            )
        );
        $defaultCacheConfiguration = [
            'frontend' => FrontendDefaultFixture::class,
            'options' => [],
            'groups' => [],
        ];
        $manager->_set('defaultCacheConfiguration', $defaultCacheConfiguration);
        $manager->setCacheConfigurations($configuration);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1464555650);
        $manager->getCache($cacheIdentifier);
    }

    /**
     * @test
     */
    public function getCacheCreatesCacheInstanceWithFallbackToDefaultBackend()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|CacheManager $manager */
        $manager = $this->getAccessibleMock(CacheManager::class, ['dummy'], [], '', false);
        $cacheIdentifier = $this->getUniqueId('Test');
        $configuration = array(
            $cacheIdentifier => array(
                'frontend' => FrontendFixture::class,
                'options' => []
            )
        );
        $defaultCacheConfiguration = [
            'backend' => BackendDefaultFixture::class,
            'options' => [],
            'groups' => [],
        ];
        $manager->_set('defaultCacheConfiguration', $defaultCacheConfiguration);
        $manager->setCacheConfigurations($configuration);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1464556045);
        $manager->getCache($cacheIdentifier);
    }

    /**
     * @test
     */
    public function getCacheReturnsInstanceOfTheSpecifiedCacheFrontend()
    {
        $manager = new CacheManager();
        $cacheIdentifier = 'aCache';
        $configuration = [
            $cacheIdentifier => [
                'backend' => BackendFixture::class,
                'frontend' => FrontendFixture::class,
                'options' => [],
            ],
        ];
        $manager->setCacheConfigurations($configuration);
        $this->assertInstanceOf(FrontendFixture::class, $manager->getCache($cacheIdentifier));
    }

    /**
     * @test
     */
    public function getCacheGivesIdentifierToCacheFrontend()
    {
        $manager = new CacheManager();
        $cacheIdentifier = 'aCache';
        $configuration = [
            $cacheIdentifier => [
                'backend' => BackendFixture::class,
                'frontend' => FrontendIdentifierFixture::class,
                'options' => [],
            ],
        ];
        $manager->setCacheConfigurations($configuration);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1464555650);
        $manager->getCache($cacheIdentifier);
    }

    /**
     * @test
     */
    public function getCacheGivesBackendInstanceToCacheFrontend()
    {
        $manager = new CacheManager();
        $cacheIdentifier = 'aCache';
        $configuration = [
            $cacheIdentifier => [
                'backend' => BackendFixture::class,
                'frontend' => FrontendBackendInstanceFixture::class,
                'options' => [],
            ],
        ];
        $manager->setCacheConfigurations($configuration);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1464557160);
        $manager->getCache($cacheIdentifier);
    }
}
