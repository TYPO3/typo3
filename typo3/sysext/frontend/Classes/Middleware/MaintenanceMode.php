<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Frontend\Middleware;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\ErrorController;

/**
 * Checks whether the whole Frontend should be put into "Page unavailable mode"
 * unless the devIPMask matches the current visitor's IP.
 *
 * The global setting $GLOBALS['TYPO3_CONF_VARS']['FE']['pageUnavailable_force']
 * is used for turning on the maintenance mode.
 */
class MaintenanceMode implements MiddlewareInterface
{
    /**
     * Calls the "unavailableAction" of the error controller if the system is in maintenance mode.
     * This only applies if the REMOTE_ADDR does not match the devIpMask
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($GLOBALS['TYPO3_CONF_VARS']['FE']['pageUnavailable_force']
            && !GeneralUtility::cmpIP(
                $request->getAttribute('normalizedParams')->getRemoteAddress(),
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask']
            )
        ) {
            return GeneralUtility::makeInstance(ErrorController::class)->unavailableAction($request, 'This page is temporarily unavailable.');
        }
        // Continue the regular stack if no maintenance mode is active
        return $handler->handle($request);
    }
}
