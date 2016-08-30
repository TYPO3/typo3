<?php
namespace TYPO3\CMS\Core\Tests\Unit\Core;

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
 * Testcase
 */
class BootstrapTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /////////////////////////////////////////
    // Tests concerning loadCachedTCA
    /////////////////////////////////////////

    /**
     * @test
     */
    public function loadCachedTcaRequiresCacheFileIfCacheEntryExists()
    {
        /** @var $bootstrapInstance \TYPO3\CMS\Core\Core\Bootstrap|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $bootstrapInstance = $this->getAccessibleMock(
            \TYPO3\CMS\Core\Core\Bootstrap::class,
            ['dummy'],
            [],
            '',
            false
        );
        $mockCache = $this->getMock(
            \TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class,
            ['getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'],
            [],
            '',
            false
        );
        $mockCacheManager = $this->getMock(
            \TYPO3\CMS\Core\Cache\CacheManager::class,
            ['getCache']
        );
        $mockCacheManager
            ->expects($this->any())
            ->method('getCache')
            ->will($this->returnValue($mockCache));
        $mockCache
            ->expects($this->any())
            ->method('has')
            ->will($this->returnValue(true));
        $mockCache
            ->expects($this->once())
            ->method('get');
        $bootstrapInstance->setEarlyInstance(\TYPO3\CMS\Core\Cache\CacheManager::class, $mockCacheManager);
        $bootstrapInstance->loadCachedTca();
    }

    /**
     * @test
     */
    public function loadCachedTcaSetsCacheEntryIfNoCacheEntryExists()
    {
        /** @var $bootstrapInstance \TYPO3\CMS\Core\Core\Bootstrap|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $bootstrapInstance = $this->getAccessibleMock(
            \TYPO3\CMS\Core\Core\Bootstrap::class,
            ['loadExtensionTables'],
            [],
            '',
            false
        );
        $mockCache = $this->getMock(
            \TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class,
            ['getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'],
            [],
            '',
            false
        );
        $mockCacheManager = $this->getMock(
            \TYPO3\CMS\Core\Cache\CacheManager::class,
            ['getCache']
        );
        $mockCacheManager
            ->expects($this->any())
            ->method('getCache')
            ->will($this->returnValue($mockCache));
        $mockCache
            ->expects($this->any())
            ->method('has')
            ->will($this->returnValue(false));
        $mockCache
            ->expects($this->once())
            ->method('set');
        $bootstrapInstance->setEarlyInstance(\TYPO3\CMS\Core\Cache\CacheManager::class, $mockCacheManager);
        $bootstrapInstance->loadCachedTca();
    }
}
