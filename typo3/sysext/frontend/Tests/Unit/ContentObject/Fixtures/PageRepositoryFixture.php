<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\ContentObject\Fixtures;

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

use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Fixture for TYPO3\CMS\Core\Utility\GeneralUtility
 */
class PageRepositoryFixture extends PageRepository
{
    public static $getHashCallCount = 0;
    public static $storeHashCallCount = 0;
    public static $dbCacheContent = null;

    public static function getHash($hash)
    {
        static::$getHashCallCount++;
        return static::$dbCacheContent;
    }

    public static function storeHash($hash, $data, $ident, $lifetime = 0)
    {
        static::$storeHashCallCount++;
    }

    public static function resetCallCount()
    {
        static::$getHashCallCount = 0;
        static::$storeHashCallCount = 0;
        static::$dbCacheContent = null;
    }
}
