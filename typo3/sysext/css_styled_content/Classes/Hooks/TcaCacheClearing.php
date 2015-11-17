<?php
namespace TYPO3\CMS\CssStyledContent\Hooks;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Utility to clear the TCA cache
 */
class TcaCacheClearing
{
    /**
     * Flush the cache_core cache to remove cached TCA
     *
     * @return void
     */
    public static function clearTcaCache()
    {
        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        $cacheManager->getCache('cache_core')->flush();
    }
}
