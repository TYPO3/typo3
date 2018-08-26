<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Adminpanel\Middleware;

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
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Adminpanel\Controller\MainController;
use TYPO3\CMS\Adminpanel\Utility\StateUtility;
use TYPO3\CMS\Adminpanel\View\AdminPanelView;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * PSR-15 Middleware to initialize the admin panel
 *
 * @internal
 */
class AdminPanelInitiator implements MiddlewareInterface
{
    /**
     * Initialize the adminPanel if
     * - backend user is logged in
     * - at least one adminpanel functionality is enabled
     * - admin panel is open
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (StateUtility::isActivatedForUser() && StateUtility::isOpen()) {
            $request = $request->withAttribute('adminPanelRequestId', substr(md5(uniqid('', true)), 0, 13));
            $adminPanelController = GeneralUtility::makeInstance(
                MainController::class
            );
            $adminPanelController->initialize($request);
            // legacy handling
            $beUser = $GLOBALS['BE_USER'];
            $beUser->adminPanel = GeneralUtility::makeInstance(AdminPanelView::class);
            $beUser->extAdmEnabled = true;
        }
        return $handler->handle($request);
    }
}
