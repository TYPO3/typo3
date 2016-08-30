<?php
namespace TYPO3\CMS\Core\Tests\Unit\Utility\AccessibleProxies;

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
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Accessible proxy with protected methods made public
 */
class ExtensionManagementUtilityAccessibleProxy extends ExtensionManagementUtility
{
    public static function setCacheManager(CacheManager $cacheManager = null)
    {
        static::$cacheManager = $cacheManager;
    }

    public static function getPackageManager()
    {
        return static::$packageManager;
    }

    public static function getExtLocalconfCacheIdentifier()
    {
        return parent::getExtLocalconfCacheIdentifier();
    }

    public static function loadSingleExtLocalconfFiles()
    {
        parent::loadSingleExtLocalconfFiles();
    }

    public static function getBaseTcaCacheIdentifier()
    {
        return parent::getBaseTcaCacheIdentifier();
    }

    public static function resetExtTablesWasReadFromCacheOnceBoolean()
    {
        self::$extTablesWasReadFromCacheOnce = false;
    }

    public static function createExtLocalconfCacheEntry()
    {
        parent::createExtLocalconfCacheEntry();
    }

    public static function createExtTablesCacheEntry()
    {
        parent::createExtTablesCacheEntry();
    }

    public static function getExtTablesCacheIdentifier()
    {
        return parent::getExtTablesCacheIdentifier();
    }

    public static function buildBaseTcaFromSingleFiles()
    {
        $GLOBALS['TCA'] = [];
    }

    public static function emitTcaIsBeingBuiltSignal(array $tca)
    {
    }

    public static function removeDuplicatesForInsertion($insertionList, $list = '')
    {
        return parent::removeDuplicatesForInsertion($insertionList, $list);
    }
}
