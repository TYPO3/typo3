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

namespace TYPO3\CMS\Backend\Toolbar;

/**
 * Interface for classes which hook into \TYPO3\CMS\Backend\Toolbar\ClearCacheToolbarItem and manipulate CacheMenuItems array
 * @deprecated since TYPO3 v11 LTS, will be removed in TYPO3 v12.0. Use the PSR-14-based ModifyClearCacheActionsEvent instead.
 */
interface ClearCacheActionsHookInterface
{
    /**
     * Modifies CacheMenuItems array
     *
     * @param array $cacheActions Array of CacheMenuItems
     * @param array $optionValues Array of AccessConfigurations-identifiers (typically used by userTS with options.clearCache.identifier)
     */
    public function manipulateCacheActions(&$cacheActions, &$optionValues);
}
