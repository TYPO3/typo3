<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Middleware;

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
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Initializes the Backend Router and also loads ext_tables.php from all extensions, as this is the place
 * where all modules register their routes to the router afterwards.
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
        // Check if a module URL is requested and deprecate this call
        $moduleName = $request->getQueryParams()['M'] ?? $request->getParsedBody()['M'] ?? null;
        // Allow the login page to be displayed if routing is not used and on index.php
        // (consolidate RouteDispatcher::evaluateReferrer() when changing 'login' to something different)
        $pathToRoute = $request->getQueryParams()['route'] ?? $request->getParsedBody()['route'] ?? $moduleName ?? '/login';

        if ($moduleName !== null) {
            // backwards compatibility for old module names
            // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
            $router = GeneralUtility::makeInstance(Router::class);
            foreach ($router->getRoutes() as $routeIdentifier => $route) {
                if ($routeIdentifier === $moduleName) {
                    $pathToRoute = $route->getPath();
                    break;
                }
            }

            trigger_error('Calling the TYPO3 Backend with "M" GET parameter will be removed in TYPO3 v10.0,'
                . ' the calling code calls this script with "&M=' . $moduleName . '" and needs to be adapted'
                . ' to use the TYPO3 API.', E_USER_DEPRECATED);
        }

        Bootstrap::initializeBackendRouter();
        Bootstrap::loadExtTables();

        // Add the route path to the request
        $request = $request->withAttribute('routePath', $pathToRoute);

        return $handler->handle($request);
    }
}
