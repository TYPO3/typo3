<?php

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

namespace TYPO3\CMS\Backend\LoginProvider;

use TYPO3\CMS\Backend\Authentication\PasswordReset;
use TYPO3\CMS\Backend\Controller\LoginController;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class UsernamePasswordLoginProvider
 * @internal This class is a specific Backend implementation and is not considered part of the Public TYPO3 API.
 */
class UsernamePasswordLoginProvider implements LoginProviderInterface
{
    /**
     * @param StandaloneView $view
     * @param PageRenderer $pageRenderer
     * @param LoginController $loginController
     * @throws \UnexpectedValueException
     */
    public function render(StandaloneView $view, PageRenderer $pageRenderer, LoginController $loginController)
    {
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/UserPassLogin');

        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates/UserPassLoginForm.html'));
        $request = $loginController->getCurrentRequest();
        if ($request !== null && $request->getAttribute('normalizedParams')->isHttps()) {
            $username = $request->getParsedBody()['u'] ?? $request->getQueryParams()['u'] ?? null;
            $password = $request->getParsedBody()['p'] ?? $request->getQueryParams()['p'] ?? null;
            $view->assignMultiple([
                'presetUsername' => $username,
                'presetPassword' => $password,
            ]);
        }

        $view->assign('enablePasswordReset', GeneralUtility::makeInstance(PasswordReset::class)->isEnabled());
    }
}
