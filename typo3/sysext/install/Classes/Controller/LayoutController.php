<?php
declare(strict_types=1);
namespace TYPO3\CMS\Install\Controller;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Install\Service\Exception\ConfigurationChangedException;
use TYPO3\CMS\Install\Service\SilentConfigurationUpgradeService;

/**
 * Layout controller
 *
 * Renders a first "load the Javascript in <head>" view, and the
 * main layout of the install tool in second action.
 */
class LayoutController extends AbstractController
{
    /**
     * The init action renders an HTML response with HTML view having <head> section
     * containing resources to main .js routing.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function initAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeStandaloneView($request, 'Layout/Init.html');
        $view->assignMultiple([
            // time is used as cache bust for js and css resources
            'time' => time(),
            'siteName' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
        ]);
        return new HtmlResponse(
            $view->render(),
            200,
            [
                'Cache-Control' => 'no-cache, must-revalidate',
                'Pragma' => 'no-cache'
            ]
        );
    }

    /**
     * Return a json response with the main HTML layout body: Toolbar, main menu and
     * doc header in standalone, doc header only in backend context.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function mainLayoutAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeStandaloneView($request, 'Layout/MainLayout.html');
        return new JsonResponse([
            'success' => true,
            'html' => $view->render(),
        ]);
    }

    /**
     * Execute silent configuration update. May be called multiple times until success = true is returned.
     *
     * @return ResponseInterface success = true if no change has been done
     */
    public function executeSilentConfigurationUpdateAction(): ResponseInterface
    {
        $silentUpdate = new SilentConfigurationUpgradeService();
        $success = true;
        try {
            $silentUpdate->execute();
        } catch (ConfigurationChangedException $e) {
            $success = false;
        }
        return new JsonResponse([
            'success' => $success,
        ]);
    }
}
