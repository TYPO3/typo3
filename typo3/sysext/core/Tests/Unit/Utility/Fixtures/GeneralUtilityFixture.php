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

namespace TYPO3\CMS\Core\Tests\Unit\Utility\Fixtures;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class GeneralUtilityFixture
 */
class GeneralUtilityFixture extends GeneralUtility
{
    public static int $isAllowedHostHeaderValueCallCount = 0;

    /**
     * Tracks number of calls done to this method
     *
     * @param string $hostHeaderValue Host name without port
     * @return bool
     */
    public static function isAllowedHostHeaderValue($hostHeaderValue): bool
    {
        self::$isAllowedHostHeaderValueCallCount++;
        return parent::isAllowedHostHeaderValue($hostHeaderValue);
    }

    /**
     * @param bool $allowHostHeaderValue
     */
    public static function setAllowHostHeaderValue(bool $allowHostHeaderValue): void
    {
        static::$allowHostHeaderValue = $allowHostHeaderValue;
    }

    /**
     * For testing we must not generally allow HTTP Host headers
     *
     * @return bool
     */
    protected static function isInternalRequestType(): bool
    {
        return false;
    }

    /**
     * Resets the internal computed class name cache.
     */
    public static function resetFinalClassNameCache(): void
    {
        static::$finalClassNameCache = [];
    }
}
