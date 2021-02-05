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
use TYPO3\CMS\Backend\Routing\Exception\MethodNotAllowedException;
use TYPO3\CMS\Backend\Routing\Exception\ResourceNotFoundException;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Loads ext_tables.php from all extensions, as this is the place
 * where all modules register their routes to the router
 * (additionally to those routes which are loaded in dependency
 * injection factories from Configuration/Backend/{,Ajax}Routes.php).
 *
 * The route path is then matched inside the Router and then handed into the request.
 *
 * After this middleware, a "Route" object is available as attribute in the Request object.
 *
 * @internal
 */
class BackendRouteInitialization implements MiddlewareInterface
{
    /**
     * @var Router
     */
    protected $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Resolve the &route (or &M) GET/POST parameter, and also resolves a Route object
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Backend Routes from Configuration/Backend/{,Ajax}Routes.php will be implicitly loaded thanks to DI.
        // Load ext_tables.php files to add routes from ExtensionManagementUtility::addModule() calls.
        Bootstrap::loadExtTables();

        try {
            $route = $this->router->matchRequest($request);
            $request = $request->withAttribute('route', $route);
            $request = $request->withAttribute('target', $route->getOption('target'));
            // add the GET parameter "route" for backwards-compatibility
            $queryParams = $request->getQueryParams();
            $queryParams['route'] = $route->getPath();
            $request = $request->withQueryParams($queryParams);
        } catch (MethodNotAllowedException $e) {
            return new Response(null, 405);
        } catch (ResourceNotFoundException $e) {
            // Route not found in system
            $uri = GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute('login');
            return new RedirectResponse($uri);
        }

        return $handler->handle($request);
    }
}
