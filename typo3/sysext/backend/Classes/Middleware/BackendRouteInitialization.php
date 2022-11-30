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
use TYPO3\CMS\Core\Routing\RequestContextFactory;

/**
 * Injects the Router and tries to match the current request with a
 * configured backend route. The available backend routes were added
 * in the corresponding dependency injection factories, which load
 * and process the module and route configuration files
 *
 * - Configuration/Backend/{,Ajax}Routes.php
 * - Configuration/Backend/Modules.php
 *
 * from each extension.
 *
 * After this middleware, a "Route" object is available as attribute in the
 * Request object. Additionally, the request handler (e.g. a controller) is
 * available as "target" in the PSR-7 request object.
 *
 * @internal
 */
class BackendRouteInitialization implements MiddlewareInterface
{
    public function __construct(
        protected readonly Router $router,
        protected readonly UriBuilder $uriBuilder,
        protected readonly RequestContextFactory $requestContextFactory,
    ) {
    }

    /**
     * Resolve the &route (or &M) GET/POST parameter, and also resolves a Route object
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // @todo Find another place for this call, since it's not related to this middleware anymore
        Bootstrap::loadExtTables();
        $this->uriBuilder->setRequestContext($this->requestContextFactory->fromBackendRequest($request));

        try {
            $routeResult = $this->router->matchResult($request);
            $request = $request->withAttribute('routing', $routeResult);
            $request = $request->withAttribute('route', $routeResult->getRoute());
            $request = $request->withAttribute('target', $routeResult->getRoute()->getOption('target'));
        } catch (MethodNotAllowedException $e) {
            return new Response(null, 405);
        } catch (ResourceNotFoundException $e) {
            // Route not found in system
            $uri = $this->uriBuilder->buildUriFromRoute('login');
            return new RedirectResponse($uri);
        }

        return $handler->handle($request);
    }
}
