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

namespace TYPO3\CMS\Install\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\FormProtection\InstallToolFormProtection;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Install\Service\EnableFileService;

/**
 * Login controller
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class LoginController extends AbstractController
{
    /**
     * Render the "Create an "enable install tool file" action
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function showEnableInstallToolFileAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeStandaloneView($request, 'Login/ShowEnableInstallToolFile.html');
        return new JsonResponse([
            'success' => true,
            'html' => $view->render(),
        ]);
    }

    /**
     * Render login view
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function showLoginAction(ServerRequestInterface $request): ResponseInterface
    {
        $formProtection = FormProtectionFactory::get(InstallToolFormProtection::class);
        $view = $this->initializeStandaloneView($request, 'Login/ShowLogin.html');
        $view->assignMultiple([
            'siteName' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
            'loginToken' => $formProtection->generateToken('installTool', 'login'),
            'installToolEnableFilePermanent' => EnableFileService::isInstallToolEnableFilePermanent(),
        ]);
        return new JsonResponse([
            'success' => true,
            'html' => $view->render(),
        ]);
    }
}
