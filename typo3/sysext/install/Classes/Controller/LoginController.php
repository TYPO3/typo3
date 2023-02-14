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
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Install\Service\EnableFileService;

/**
 * Login controller
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class LoginController extends AbstractController
{
    public function __construct(
        private readonly FormProtectionFactory $formProtectionFactory
    ) {
    }

    /**
     * Render the "Create an "enable install tool file" action
     */
    public function showEnableInstallToolFileAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeView($request);
        $path = str_replace(Environment::getPublicPath() . '/', '', dirname(EnableFileService::getBestLocationForInstallToolEnableFile())) . '/';
        $view->assign('enableInstallToolPath', $path);
        return new JsonResponse([
            'success' => true,
            'html' => $view->render('Login/ShowEnableInstallToolFile'),
        ]);
    }

    /**
     * Render login view
     */
    public function showLoginAction(ServerRequestInterface $request): ResponseInterface
    {
        $formProtection = $this->formProtectionFactory->createFromRequest($request);
        $view = $this->initializeView($request);
        $view->assignMultiple([
            'siteName' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
            'loginToken' => $formProtection->generateToken('installTool', 'login'),
            'installToolEnableFilePermanent' => EnableFileService::isInstallToolEnableFilePermanent(),
        ]);
        return new JsonResponse([
            'success' => true,
            'html' => $view->render('Login/ShowLogin'),
        ]);
    }
}
