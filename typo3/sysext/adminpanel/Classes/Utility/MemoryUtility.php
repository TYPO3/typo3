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

namespace TYPO3\CMS\Adminpanel\Utility;

/**
 * Helper class to keep track of memory consumption in loggers to reduce risk of running out of memory
 *
 * @internal
 */
class MemoryUtility
{
    private const float MINIMAL_PERCENT_OF_FREE_MEMORY = 0.30;

    /**
     * Checks memory usage used in current process - this is no guarantee for not running out of memory
     * but should prevent memory exhaustion due to the admin panel in most cases.
     * The loggers will stop logging once the amount of free memory falls below the threshold (see
     * MINIMAL_PERCENT_OF_FREE_MEMORY const).
     */
    public static function isMemoryConsumptionTooHigh(): bool
    {
        $memoryLimit = ini_parse_quantity((string)ini_get('memory_limit'));
        $freeMemory = $memoryLimit - memory_get_usage(true);

        return $memoryLimit > 0 && $freeMemory < (self::MINIMAL_PERCENT_OF_FREE_MEMORY * $memoryLimit);
    }
}
