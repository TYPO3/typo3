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

use TYPO3\CMS\Core\Utility\DeprecationUtility;

/**
 * Class GeneralUtilityFixture
 */
class DeprecationUtilityFixture extends DeprecationUtility
{
    const DEPRECATION_LOG_PATH = 'typo3temp/test_deprecation/test.log';


    /**
     * Gets the absolute path to the deprecation log file.
     *
     * @return string Absolute path to the deprecation log file
     */
    public static function getDeprecationLogFileName()
    {
        return PATH_site . static::DEPRECATION_LOG_PATH;
    }
}
