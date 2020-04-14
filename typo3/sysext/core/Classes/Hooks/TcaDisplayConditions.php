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

namespace TYPO3\CMS\Core\Hooks;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Various display conditions to check for e.g. installed extensions or configuration settings used.
 *
 * @internal This class is a hook implementation and is not part of the TYPO3 Core API.
 */
class TcaDisplayConditions
{
    /**
     * Check if an extension is loaded.
     *
     * @param array $parameters
     * @return bool
     */
    public function isExtensionInstalled(array $parameters): bool
    {
        $extension = $parameters['conditionParameters'][0] ?? '';
        if (!empty($extension)) {
            return ExtensionManagementUtility::isLoaded($extension);
        }
        return false;
    }

    /**
     * Check if $GLOBALS['TYPO3_CONF_VARS']['BE']['ipLock'] is active
     *
     * @param array $parameters
     * @return bool
     */
    public function isIPLockEnabled(array $parameters): bool
    {
        $type = $parameters['conditionParameters'][0] ?? 'backend';
        if ($type === 'backend') {
            return (int)$GLOBALS['TYPO3_CONF_VARS']['BE']['ipLock'] > 0 || (int)$GLOBALS['TYPO3_CONF_VARS']['BE']['lockIPv6'] > 0;
        }
        if ($type === 'frontend') {
            return (int)$GLOBALS['TYPO3_CONF_VARS']['FE']['ipLock'] > 0 || (int)$GLOBALS['TYPO3_CONF_VARS']['FE']['lockIPv6'] > 0;
        }
        return false;
    }
}
