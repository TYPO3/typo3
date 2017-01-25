<?php
namespace TYPO3\CMS\Core\Utility;

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
 * Class to handle monitoring actions.
 */
class MonitorUtility
{
    /**
     * Checks peak memory usage and stores data in cache for use in the report module
     *
     * @return void
     */
    public static function peakMemoryUsage()
    {
        $peakUsage = memory_get_peak_usage(true);
        $memoryLimit = GeneralUtility::getBytesFromSizeMeasurement(ini_get('memory_limit'));
        if (is_float($memoryLimit) && $memoryLimit != 0) {
            if ($peakUsage / $memoryLimit >= 0.9) {
                /** @var $registry \TYPO3\CMS\Core\Registry */
                $registry = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Registry::class);
                $data = [
                    'used' => $peakUsage,
                    'tstamp' => $GLOBALS['EXEC_TIME'],
                    'url' => GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL')
                ];
                $registry->set('core', 'reports-peakMemoryUsage', $data);
            }
        }
    }
}
