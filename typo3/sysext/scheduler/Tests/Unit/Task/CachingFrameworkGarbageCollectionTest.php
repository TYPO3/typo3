<?php
namespace TYPO3\CMS\Scheduler\Tests\Unit\Task;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case
 */
class CachingFrameworkGarbageCollectionTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var array
     */
    protected $singletonInstances = [];

    /**
     * Set up
     */
    protected function setUp()
    {
        $this->singletonInstances = \TYPO3\CMS\Core\Utility\GeneralUtility::getSingletonInstances();
    }

    /**
     * Reset singleton instances
     */
    protected function tearDown()
    {
        \TYPO3\CMS\Core\Utility\GeneralUtility::resetSingletonInstances($this->singletonInstances);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function executeCallsCollectGarbageOfConfiguredBackend()
    {
        $cache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\StringFrontend::class, [], [], '', false);
        $cache->expects($this->any())->method('getIdentifier')->will($this->returnValue('cache'));
        $cache->expects($this->atLeastOnce())->method('collectGarbage');
        $mockCacheManager = new \TYPO3\CMS\Core\Cache\CacheManager();
        $mockCacheManager->registerCache($cache);
        GeneralUtility::setSingletonInstance(\TYPO3\CMS\Core\Cache\CacheManager::class, $mockCacheManager);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] = [
            'cache' => [
                'frontend' => \TYPO3\CMS\Core\Cache\Frontend\StringFrontend::class,
                'backend' => \TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class,
            ]
        ];
        /** @var \TYPO3\CMS\Scheduler\Task\CachingFrameworkGarbageCollectionTask|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMock(\TYPO3\CMS\Scheduler\Task\CachingFrameworkGarbageCollectionTask::class, ['dummy'], [], '', false);
        $subject->selectedBackends = [\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class];
        $subject->execute();
    }

    /**
     * @test
     */
    public function executeDoesNotCallCollectGarbageOfNotConfiguredBackend()
    {
        $cache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\StringFrontend::class, [], [], '', false);
        $cache->expects($this->any())->method('getIdentifier')->will($this->returnValue('cache'));
        $cache->expects($this->never())->method('collectGarbage');
        $mockCacheManager = new \TYPO3\CMS\Core\Cache\CacheManager();
        $mockCacheManager->registerCache($cache);
        GeneralUtility::setSingletonInstance(\TYPO3\CMS\Core\Cache\CacheManager::class, $mockCacheManager);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] = [
            'cache' => [
                'frontend' => \TYPO3\CMS\Core\Cache\Frontend\StringFrontend::class,
                'backend' => \TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class,
            ]
        ];
        /** @var \TYPO3\CMS\Scheduler\Task\CachingFrameworkGarbageCollectionTask|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMock(\TYPO3\CMS\Scheduler\Task\CachingFrameworkGarbageCollectionTask::class, ['dummy'], [], '', false);
        $subject->selectedBackends = [\TYPO3\CMS\Core\Cache\Backend\NullBackend::class];
        $subject->execute();
    }
}
