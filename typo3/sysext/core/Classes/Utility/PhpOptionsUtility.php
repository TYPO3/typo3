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
 * Class to handle php environment specific options / functions
 */
class PhpOptionsUtility
{
    /**
     * Check if php session.auto_start is enabled
     *
     * @return bool TRUE if session.auto_start is enabled, FALSE if disabled
     */
    public static function isSessionAutoStartEnabled()
    {
        return self::getIniValueBoolean('session.auto_start');
    }

    /**
     * Cast an on/off php ini value to boolean
     *
     * @param string $configOption
     * @return bool TRUE if the given option is enabled, FALSE if disabled
     */
    public static function getIniValueBoolean($configOption)
    {
        return filter_var(ini_get($configOption), FILTER_VALIDATE_BOOLEAN, [FILTER_REQUIRE_SCALAR, FILTER_NULL_ON_FAILURE]);
    }
}
