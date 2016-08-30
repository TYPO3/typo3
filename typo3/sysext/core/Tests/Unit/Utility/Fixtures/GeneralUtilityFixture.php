<?php
namespace TYPO3\CMS\Core\Tests\Unit\Utility\Fixtures;

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
 * Class GeneralUtilityFixture
 */
class GeneralUtilityFixture extends GeneralUtility
{
    const DEPRECATION_LOG_PATH = 'typo3temp/test_deprecation/test.log';

    /**
     * @var int
     */
    public static $isAllowedHostHeaderValueCallCount = 0;

    /**
     * Tracks number of calls done to this method
     *
     * @param string $hostHeaderValue Host name without port
     * @return bool
     */
    public static function isAllowedHostHeaderValue($hostHeaderValue)
    {
        self::$isAllowedHostHeaderValueCallCount++;
        return parent::isAllowedHostHeaderValue($hostHeaderValue);
    }

    /**
     * @param bool $allowHostHeaderValue
     */
    public static function setAllowHostHeaderValue($allowHostHeaderValue)
    {
        static::$allowHostHeaderValue = $allowHostHeaderValue;
    }

    /**
     * For testing we must not generally allow HTTP Host headers
     *
     * @return bool
     */
    protected static function isInternalRequestType()
    {
        return false;
    }

    /**
     * Parses HTTP headers and returns the content of the "Location" header
     * or the empty string if no such header found.
     *
     * @param string $content
     * @return string
     */
    public static function getRedirectUrlFromHttpHeaders($content)
    {
        return parent::getRedirectUrlFromHttpHeaders($content);
    }

    /**
     * Strips HTTP headers from the content.
     *
     * @param string $content
     * @return string
     */
    public static function stripHttpHeaders($content)
    {
        return parent::stripHttpHeaders($content);
    }

    /**
     * Gets the absolute path to the deprecation log file.
     *
     * @return string Absolute path to the deprecation log file
     */
    public static function getDeprecationLogFileName()
    {
        return PATH_site . static::DEPRECATION_LOG_PATH;
    }

    /**
     * Resets the internal computed class name cache.
     */
    public static function resetFinalClassNameCache()
    {
        static::$finalClassNameCache = [];
    }
}
