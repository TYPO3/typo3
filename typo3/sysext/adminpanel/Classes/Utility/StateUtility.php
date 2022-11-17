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

use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;

/**
 * Helper class to check if the admin panel is enabled and active from outside
 *
 * Useful for initialization, checks in early hooks or middleware implementations
 */
class StateUtility
{
    /**
     * Checks if adminPanel was configured to be shown
     */
    public static function isActivatedForUser(): bool
    {
        $beUser = $GLOBALS['BE_USER'] ?? null;
        if ($beUser instanceof FrontendBackendUserAuthentication) {
            $adminPanelConfiguration = $beUser->getTSConfig()['admPanel.'] ?? [];
            if (isset($adminPanelConfiguration['enable.'])) {
                // only enabled if at least one module is enabled.
                return (bool)array_filter($adminPanelConfiguration['enable.']);
            }
        }
        return false;
    }

    /**
     * Returns true if admin panel was activated
     * (switched "on" via GUI)
     */
    public static function isOpen(): bool
    {
        $beUser = $GLOBALS['BE_USER'] ?? null;
        return (bool)($beUser->uc['AdminPanel']['display_top'] ?? false);
    }

    public static function isActivatedInTypoScript(): bool
    {
        return (bool)($GLOBALS['TSFE']->config['config']['admPanel'] ?? false);
    }

    public static function isHiddenForUser(): bool
    {
        $beUser = $GLOBALS['BE_USER'] ?? null;
        return $beUser ? (bool)($beUser->getTSConfig()['admPanel.']['hide'] ?? false) : false;
    }
}
