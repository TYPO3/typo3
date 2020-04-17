<?php

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

namespace TYPO3\CMS\Scheduler\Tests\Unit\Task;

use TYPO3\CMS\Core\Cache\Backend\AbstractBackend;
use TYPO3\CMS\Core\Cache\Backend\NullBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\CachingFrameworkGarbageCollectionTask;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class CachingFrameworkGarbageCollectionTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @test
     */
    public function executeCallsCollectGarbageOfConfiguredBackend()
    {
        $cache = $this->createMock(VariableFrontend::class);
        $cache->expects(self::any())->method('getIdentifier')->willReturn('cache');
        $cache->expects(self::atLeastOnce())->method('collectGarbage');
        $mockCacheManager = new CacheManager();
        $mockCacheManager->registerCache($cache);
        GeneralUtility::setSingletonInstance(CacheManager::class, $mockCacheManager);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] = [
            'cache' => [
                'frontend' => VariableFrontend::class,
                'backend' => AbstractBackend::class,
            ]
        ];
        /** @var \TYPO3\CMS\Scheduler\Task\CachingFrameworkGarbageCollectionTask|\PHPUnit\Framework\MockObject\MockObject $subject */
        $subject = $this->getMockBuilder(CachingFrameworkGarbageCollectionTask::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $subject->selectedBackends = [AbstractBackend::class];
        $subject->execute();
    }

    /**
     * @test
     */
    public function executeDoesNotCallCollectGarbageOfNotConfiguredBackend()
    {
        $cache = $this->createMock(VariableFrontend::class);
        $cache->expects(self::any())->method('getIdentifier')->willReturn('cache');
        $cache->expects(self::never())->method('collectGarbage');
        $mockCacheManager = new CacheManager();
        $mockCacheManager->registerCache($cache);
        GeneralUtility::setSingletonInstance(CacheManager::class, $mockCacheManager);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] = [
            'cache' => [
                'frontend' => VariableFrontend::class,
                'backend' => AbstractBackend::class,
            ]
        ];
        /** @var \TYPO3\CMS\Scheduler\Task\CachingFrameworkGarbageCollectionTask|\PHPUnit\Framework\MockObject\MockObject $subject */
        $subject = $this->getMockBuilder(CachingFrameworkGarbageCollectionTask::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $subject->selectedBackends = [NullBackend::class];
        $subject->execute();
    }
}
