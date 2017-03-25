<?php
namespace TYPO3\CMS\Compatibility7\Hooks;

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
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Hook to validate the TypoScript option
 * config.beLoginLinkIPList, config beLoginLinkIPList_login and config.beLoginLinkIPList_logout
 */
class BackendLoginLinkHook
{
    /**
     * Echoes a link to the BE login screen with redirect to the front-end
     * if the option config.beLoginLinkIPList is set.
     *
     * @param array $parameters left empty, not in use
     * @param TypoScriptFrontendController $parentObject
     */
    public function renderBackendLoginLink(array $parameters, TypoScriptFrontendController $parentObject)
    {
        if (empty($parentObject->config['config']['beLoginLinkIPList'])) {
            return;
        }
        if (!GeneralUtility::cmpIP(GeneralUtility::getIndpEnv('REMOTE_ADDR'), $parentObject->config['config']['beLoginLinkIPList'])) {
            return;
        }
        $label = !$parentObject->isBackendUserLoggedIn() ? $parentObject->config['config']['beLoginLinkIPList_login'] : $parentObject->config['config']['beLoginLinkIPList_logout'];
        if ($label) {
            if (!$parentObject->isBackendUserLoggedIn()) {
                $link = '<a href="' . htmlspecialchars((TYPO3_mainDir . 'index.php?redirect_url=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI')))) . '">' . $label . '</a>';
            } else {
                $link = '<a href="' . htmlspecialchars((TYPO3_mainDir . 'index.php?L=OUT&redirect_url=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI')))) . '">' . $label . '</a>';
            }
            echo $link;
        }
    }
}
