<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Adminpanel\Hooks;

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

use TYPO3\CMS\Adminpanel\Controller\MainController;
use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Hook to render the admin panel
 */
class RenderHook
{

    /**
     * Hook to render the admin panel
     * We use a hook this late in the project to make sure all data is collected and can be displayed
     *
     * As the main content is already rendered, we use a string replace on the content to append the adminPanel
     * to the HTML body.
     *
     * @param array $params
     * @param TypoScriptFrontendController $pObj
     */
    public function renderAdminPanel(array $params, TypoScriptFrontendController $pObj)
    {
        if ($pObj->isBackendUserLoggedIn() &&
            $GLOBALS['BE_USER'] instanceof FrontendBackendUserAuthentication &&
            (
                !$GLOBALS['BE_USER']->extAdminConfig['hide'] && $pObj->config['config']['admPanel']
            )
        ) {
            $mainController = GeneralUtility::makeInstance(MainController::class);
            $pObj->content = str_ireplace(
                '</body>',
                $mainController->render() . '</body>',
                $pObj->content
            );
        }
    }
}
