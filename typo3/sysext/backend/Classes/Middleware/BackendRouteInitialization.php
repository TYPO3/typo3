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

namespace TYPO3\CMS\Backend\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Core\Bootstrap;

/**
 * Loads ext_tables.php from all extensions, as this is the place
 * where all modules register their routes to the router
 * (additionally to those routes which are loaded in dependency
 * injection factories from Configuration/Backend/{,Ajax}Routes.php).
 *
 * The route path is added to the request as attribute "routePath".
 *
 * @internal
 */
class BackendRouteInitialization implements MiddlewareInterface
{
    /**
     * Resolve the &route (or &M) GET/POST parameter, and also the Router object.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Allow the login page to be displayed if routing is not used and on index.php
        // (consolidate RouteDispatcher::evaluateReferrer() when changing 'login' to something different)
        $pathToRoute = $request->getQueryParams()['route'] ?? $request->getParsedBody()['route'] ?? '/login';

        // Backend Routes from Configuration/Backend/{,Ajax}Routes.php will be implicitly loaded thanks to DI.
        // Load ext_tables.php files to add routes from ExtensionManagementUtility::addModule() calls.
        Bootstrap::loadExtTables();

        // Add the route path to the request
        $request = $request->withAttribute('routePath', $pathToRoute);

        return $handler->handle($request);
    }
}
