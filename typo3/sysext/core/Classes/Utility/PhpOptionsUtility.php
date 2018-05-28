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
     * @deprecated since TYPO3 v9.3, will be removed in TYPO3 v10.0. Use custom filter_var()/ini_get() functionality yourself.
     */
    public static function isSessionAutoStartEnabled()
    {
        trigger_error('The PhpOptionsUtility class will be removed in TYPO3 v10.0. Use custom filter_var()/ini_get() functionality yourself.', E_USER_DEPRECATED);
        return self::getIniValueBoolean('session.auto_start');
    }

    /**
     * Cast an on/off php ini value to boolean
     *
     * @param string $configOption
     * @return bool TRUE if the given option is enabled, FALSE if disabled
     * @deprecated since TYPO3 v9.3, will be removed in TYPO3 v10.0. Use custom filter_var()/ini_get() functionality yourself.
     */
    public static function getIniValueBoolean($configOption)
    {
        trigger_error('The PhpOptionsUtility class will be removed in TYPO3 v10.0. Use custom filter_va()/ini_get() functionality yourself.', E_USER_DEPRECATED);
        return filter_var(ini_get($configOption), FILTER_VALIDATE_BOOLEAN, [FILTER_REQUIRE_SCALAR, FILTER_NULL_ON_FAILURE]);
    }
}
