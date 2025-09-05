<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Tests\Unit\Cache;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\DuplicateIdentifierException;
use TYPO3\CMS\Core\Cache\Exception\InvalidBackendException;
use TYPO3\CMS\Core\Cache\Exception\InvalidCacheException;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException;
use TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Tests\Unit\Cache\Fixtures\BackendConfigurationOptionFixture;
use TYPO3\CMS\Core\Tests\Unit\Cache\Fixtures\BackendDefaultFixture;
use TYPO3\CMS\Core\Tests\Unit\Cache\Fixtures\BackendFixture;
use TYPO3\CMS\Core\Tests\Unit\Cache\Fixtures\BackendInitializeObjectFixture;
use TYPO3\CMS\Core\Tests\Unit\Cache\Fixtures\FrontendBackendInstanceFixture;
use TYPO3\CMS\Core\Tests\Unit\Cache\Fixtures\FrontendDefaultFixture;
use TYPO3\CMS\Core\Tests\Unit\Cache\Fixtures\FrontendFixture;
use TYPO3\CMS\Core\Tests\Unit\Cache\Fixtures\FrontendIdentifierFixture;
use TYPO3\CMS\Core\Tests\Unit\Cache\Fixtures\FrontendInitializeObjectFixture;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CacheManagerTest extends UnitTestCase
{
    #[Test]
    public function managerThrowsExceptionOnCacheRegistrationWithAlreadyExistingIdentifier(): void
    {
        $this->expectException(DuplicateIdentifierException::class);
        $this->expectExceptionCode(1203698223);

        $manager = new CacheManager();
        $cache1 = $this->getMockBuilder(AbstractFrontend::class)
            ->onlyMethods(['getIdentifier', 'set', 'get', 'has', 'remove', 'flush', 'flushByTag'])
            ->disableOriginalConstructor()
            ->getMock();
        $cache1->expects($this->atLeastOnce())->method('getIdentifier')->willReturn('test');
        $cache2 = $this->getMockBuilder(AbstractFrontend::class)
            ->onlyMethods(['getIdentifier', 'set', 'get', 'has', 'remove', 'flush', 'flushByTag'])
            ->disableOriginalConstructor()
            ->getMock();
        $cache2->expects($this->atLeastOnce())->method('getIdentifier')->willReturn('test');
        $manager->registerCache($cache1);
        $manager->registerCache($cache2);
    }

    #[Test]
    public function managerReturnsThePreviouslyRegisteredCache(): void
    {
        $manager = new CacheManager();
        $cache1 = $this->getMockBuilder(AbstractFrontend::class)
            ->onlyMethods(['getIdentifier', 'set', 'get', 'has', 'remove', 'flush', 'flushByTag'])
            ->disableOriginalConstructor()
            ->getMock();
        $cache1->expects($this->atLeastOnce())->method('getIdentifier')->willReturn('cache1');
        $cache2 = $this->getMockBuilder(AbstractFrontend::class)
            ->onlyMethods(['getIdentifier', 'set', 'get', 'has', 'remove', 'flush', 'flushByTag'])
            ->disableOriginalConstructor()
            ->getMock();
        $cache2->expects($this->atLeastOnce())->method('getIdentifier')->willReturn('cache2');
        $manager->registerCache($cache1);
        $manager->registerCache($cache2);
        self::assertSame($cache2, $manager->getCache('cache2'), 'The cache returned by getCache() was not the same I registered.');
    }

    #[Test]
    public function getCacheThrowsExceptionForNonExistingIdentifier(): void
    {
        $this->expectException(NoSuchCacheException::class);
        $this->expectExceptionCode(1203699034);

        $manager = new CacheManager();
        $cache = $this->getMockBuilder(AbstractFrontend::class)
            ->onlyMethods(['getIdentifier', 'set', 'get', 'has', 'remove', 'flush', 'flushByTag'])
            ->disableOriginalConstructor()
            ->getMock();
        $cache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn('someidentifier');
        $manager->registerCache($cache);
        $manager->getCache('someidentifier');
        $manager->getCache('doesnotexist');
    }

    #[Test]
    public function hasCacheReturnsCorrectResult(): void
    {
        $manager = new CacheManager();
        $cache1 = $this->getMockBuilder(AbstractFrontend::class)
            ->onlyMethods(['getIdentifier', 'set', 'get', 'has', 'remove', 'flush', 'flushByTag'])
            ->disableOriginalConstructor()
            ->getMock();
        $cache1->expects($this->atLeastOnce())->method('getIdentifier')->willReturn('cache1');
        $manager->registerCache($cache1);
        self::assertTrue($manager->hasCache('cache1'), 'hasCache() did not return TRUE.');
        self::assertFalse($manager->hasCache('cache2'), 'hasCache() did not return FALSE.');
    }

    #[Test]
    public function flushCachesByTagCallsTheFlushByTagMethodOfAllRegisteredCaches(): void
    {
        $manager = new CacheManager();
        $cache1 = $this->getMockBuilder(AbstractFrontend::class)
            ->onlyMethods(['getIdentifier', 'set', 'get', 'has', 'remove', 'flush', 'flushByTag'])
            ->disableOriginalConstructor()
            ->getMock();
        $cache1->expects($this->atLeastOnce())->method('getIdentifier')->willReturn('cache1');
        $cache1->expects($this->once())->method('flushByTag')->with(self::equalTo('theTag'));
        $manager->registerCache($cache1);
        $cache2 = $this->getMockBuilder(AbstractFrontend::class)
            ->onlyMethods(['getIdentifier', 'set', 'get', 'has', 'remove', 'flush', 'flushByTag'])
            ->disableOriginalConstructor()
            ->getMock();
        $cache2->expects($this->once())->method('flushByTag')->with(self::equalTo('theTag'));
        $manager->registerCache($cache2);
        $manager->flushCachesByTag('theTag');
    }

    #[Test]
    public function flushCachesByTagsCallsTheFlushByTagsMethodOfAllRegisteredCaches(): void
    {
        $manager = new CacheManager();
        $cache1 = $this->getMockBuilder(AbstractFrontend::class)
            ->onlyMethods(['getIdentifier', 'set', 'get', 'has', 'remove', 'flush', 'flushByTags'])
            ->disableOriginalConstructor()
            ->getMock();
        $cache1->expects($this->atLeastOnce())->method('getIdentifier')->willReturn('cache1');
        $cache1->expects($this->once())->method('flushByTags')->with(self::equalTo(['theTag']));
        $manager->registerCache($cache1);
        $cache2 = $this->getMockBuilder(AbstractFrontend::class)
            ->onlyMethods(['getIdentifier', 'set', 'get', 'has', 'remove', 'flush', 'flushByTags'])
            ->disableOriginalConstructor()
            ->getMock();
        $cache2->expects($this->once())->method('flushByTags')->with(self::equalTo(['theTag']));
        $manager->registerCache($cache2);
        $manager->flushCachesByTags(['theTag']);
    }

    #[Test]
    public function flushCachesCallsTheFlushMethodOfAllRegisteredCaches(): void
    {
        $manager = new CacheManager();
        $cache1 = $this->getMockBuilder(AbstractFrontend::class)
            ->onlyMethods(['getIdentifier', 'set', 'get', 'has', 'remove', 'flush', 'flushByTag'])
            ->disableOriginalConstructor()
            ->getMock();
        $cache1->expects($this->atLeastOnce())->method('getIdentifier')->willReturn('cache1');
        $cache1->expects($this->once())->method('flush');
        $manager->registerCache($cache1);
        $cache2 = $this->getMockBuilder(AbstractFrontend::class)
            ->onlyMethods(['getIdentifier', 'set', 'get', 'has', 'remove', 'flush', 'flushByTag'])
            ->disableOriginalConstructor()
            ->getMock();
        $cache2->expects($this->once())->method('flush');
        $manager->registerCache($cache2);
        $manager->flushCaches();
    }

    #[Test]
    public function flushCachesInGroupThrowsExceptionForNonExistingGroup(): void
    {
        $this->expectException(NoSuchCacheGroupException::class);
        $this->expectExceptionCode(1390334120);

        $manager = new CacheManager();
        $manager->flushCachesInGroup('nonExistingGroup');
    }

    #[Test]
    public function flushCachesInGroupByTagThrowsExceptionForNonExistingGroup(): void
    {
        $this->expectException(NoSuchCacheGroupException::class);
        $this->expectExceptionCode(1390334120);

        $manager = new CacheManager();
        $manager->flushCachesInGroup('nonExistingGroup');
    }

    #[Test]
    public function getCacheThrowsExceptionIfConfiguredFrontendDoesNotImplementFrontendInterface(): void
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

    #[Test]
    public function getCacheThrowsExceptionIfConfiguredBackendDoesNotImplementBackendInterface(): void
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

    #[Test]
    public function getCacheCallsInitializeObjectOnFrontendInstance(): void
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

    #[Test]
    public function getCacheCallsInitializeObjectOnBackendInstance(): void
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

    #[Test]
    public function getCacheCreatesBackendWithGivenConfiguration(): void
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
        // BackendConfigurationOptionFixture throws exception if initializeObject() is called, so expect this
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1464555007);
        $manager->getCache($cacheIdentifier);
    }

    #[Test]
    public function getCacheCreatesCacheInstanceWithFallbackToDefaultFrontend(): void
    {
        $manager = $this->getAccessibleMock(CacheManager::class, null, [], '', false);
        $cacheIdentifier = StringUtility::getUniqueId('Test');
        $configuration = [
            $cacheIdentifier => [
                'backend' => BackendFixture::class,
                'options' => [],
            ],
        ];
        $defaultCacheConfiguration = [
            'frontend' => FrontendDefaultFixture::class,
            'options' => [],
            'groups' => [],
        ];
        $manager->_set('defaultCacheConfiguration', $defaultCacheConfiguration);
        $manager->setCacheConfigurations($configuration);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1476109149);
        $manager->getCache($cacheIdentifier);
    }

    #[Test]
    public function getCacheCreatesCacheInstanceWithFallbackToDefaultBackend(): void
    {
        $manager = $this->getAccessibleMock(CacheManager::class, null, [], '', false);
        $cacheIdentifier = StringUtility::getUniqueId('Test');
        $configuration = [
            $cacheIdentifier => [
                'frontend' => FrontendFixture::class,
                'options' => [],
            ],
        ];
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

    #[Test]
    public function getCacheReturnsInstanceOfTheSpecifiedCacheFrontend(): void
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
        self::assertInstanceOf(FrontendFixture::class, $manager->getCache($cacheIdentifier));
    }

    #[Test]
    public function getCacheGivesIdentifierToCacheFrontend(): void
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

    #[Test]
    public function getCacheGivesBackendInstanceToCacheFrontend(): void
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

    #[Test]
    public function flushCachesInGroupByTagsWithEmptyTagsArrayDoesNotFlushCaches(): void
    {
        $manager = $this->getAccessibleMock(CacheManager::class, null, [], '', false);
        $cacheIdentifier = 'aTest';

        $cacheGroups = [
            'group1' => [$cacheIdentifier],
            'group2' => [$cacheIdentifier],
        ];
        $manager->_set('cacheGroups', $cacheGroups);

        $frontendMock = $this->createMock(FrontendFixture::class);

        $caches = [
            $cacheIdentifier => $frontendMock,
        ];
        $manager->_set('caches', $caches);

        $frontendMock->expects($this->never())->method('flushByTags');

        $configuration = [
            $cacheIdentifier => [
                'frontend' => $frontendMock,
                'backend' => BackendFixture::class,
                'options' => [],
                'groups' => ['group1', 'group2'],
            ],
        ];
        $manager->setCacheConfigurations($configuration);
        $manager->flushCachesInGroupByTags('group2', []);
    }

    #[Test]
    public function flushCachesInGroupByTagsDeletesByTag(): void
    {
        $manager = $this->getAccessibleMock(CacheManager::class, null, [], '', false);
        $cacheIdentifier = 'aTest';

        $cacheGroups = [
            'group1' => [$cacheIdentifier],
            'group2' => [$cacheIdentifier],
        ];
        $manager->_set('cacheGroups', $cacheGroups);

        $frontendMock = $this->createMock(FrontendFixture::class);

        $caches = [
            $cacheIdentifier => $frontendMock,
        ];
        $manager->_set('caches', $caches);

        $tags = ['tag1', 'tag2'];
        $frontendMock->expects($this->once())->method('flushByTags')->with($tags);

        $configuration = [
            $cacheIdentifier => [
                'frontend' => $frontendMock,
                'backend' => BackendFixture::class,
                'options' => [],
                'groups' => ['group1', 'group2'],
            ],
        ];
        $manager->setCacheConfigurations($configuration);
        $manager->flushCachesInGroupByTags('group2', $tags);
    }

    #[Test]
    public function setCacheConfigurationsThrowsExceptionIfConfiguredCacheDoesNotHaveAnIdentifier(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1596980032);

        $manager = $this->getAccessibleMock(CacheManager::class, null);
        $manager->setCacheConfigurations([
            '' => [
                'frontend' => VariableFrontend::class,
                'backend' => Typo3DatabaseBackend::class,
                'options' => [
                    'compression' => true,
                ],
                'groups' => ['pages'],
            ],
        ]);
    }
}
