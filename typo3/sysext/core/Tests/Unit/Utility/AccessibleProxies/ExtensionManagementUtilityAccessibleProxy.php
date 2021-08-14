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

namespace TYPO3\CMS\Core\Tests\Unit\Utility\AccessibleProxies;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Accessible proxy with protected methods made public
 */
class ExtensionManagementUtilityAccessibleProxy extends ExtensionManagementUtility
{
    public static function setCacheManager(CacheManager $cacheManager = null): void
    {
        static::$cacheManager = $cacheManager;
    }

    public static function getPackageManager(): PackageManager
    {
        return static::$packageManager;
    }

    public static function getExtLocalconfCacheIdentifier(): string
    {
        return parent::getExtLocalconfCacheIdentifier();
    }

    public static function loadSingleExtLocalconfFiles(): void
    {
        parent::loadSingleExtLocalconfFiles();
    }

    public static function getBaseTcaCacheIdentifier(): string
    {
        return parent::getBaseTcaCacheIdentifier();
    }

    public static function resetExtTablesWasReadFromCacheOnceBoolean(): void
    {
        self::$extTablesWasReadFromCacheOnce = false;
    }

    public static function createExtLocalconfCacheEntry(FrontendInterface $cache): void
    {
        parent::createExtLocalconfCacheEntry($cache);
    }

    public static function createExtTablesCacheEntry(FrontendInterface $cache): void
    {
        parent::createExtTablesCacheEntry($cache);
    }

    public static function getExtTablesCacheIdentifier(): string
    {
        return parent::getExtTablesCacheIdentifier();
    }

    public static function buildBaseTcaFromSingleFiles(): void
    {
        $GLOBALS['TCA'] = [];
    }

    public static function dispatchTcaIsBeingBuiltEvent(array $tca): void
    {
    }

    public static function removeDuplicatesForInsertion($insertionList, $list = ''): string
    {
        return parent::removeDuplicatesForInsertion($insertionList, $list);
    }
}
