<?php
namespace TYPO3\CMS\Backend\Routing;

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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\Exception\ResourceNotFoundException;

/**
 * Implementation of a class for adding routes, collecting throughout the Bootstrap
 * to register all sorts of Backend Routes, and to fetch the main Collection in order
 * to resolve a route (see ->match() and ->matchRequest()).
 *
 * Ideally, the Router is solely instantiated and accessed via the Bootstrap, the RequestHandler and the UriBuilder.
 *
 * See \TYPO3\CMS\Backend\Http\RequestHandler for more details on route matching() and Bootstrap->initializeBackendRouting().
 *
 * The architecture is inspired by the Symfony Routing Component.
 */
class Router implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * All routes used in the Backend
     *
     * @var Route[]
     */
    protected $routes = [];

    /**
     * Adds a new route with the identifiers
     *
     * @param string $routeIdentifier
     * @param Route $route
     */
    public function addRoute($routeIdentifier, $route)
    {
        $this->routes[$routeIdentifier] = $route;
    }

    /**
     * Fetch all registered routes, only use in UriBuilder
     *
     * @return Route[]
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Tries to match a URL path with a set of routes.
     *
     * @param string $pathInfo The path info to be parsed
     * @return Route the first Route object found
     * @throws ResourceNotFoundException If the resource could not be found
     */
    public function match($pathInfo)
    {
        foreach ($this->routes as $routeIdentifier => $route) {
            // This check is done in a simple way as there are no parameters yet (get parameters only)
            if ($route->getPath() === $pathInfo) {
                // Store the name of the Route in the _identifier option so the token can be checked against that
                $route->setOption('_identifier', $routeIdentifier);
                return $route;
            }
        }
        throw new ResourceNotFoundException('The requested resource "' . $pathInfo . '" was not found.', 1425389240);
    }

    /**
     * Tries to match a URI against the registered routes
     *
     * @param ServerRequestInterface $request
     * @return Route the first Route object found
     */
    public function matchRequest(ServerRequestInterface $request)
    {
        return $this->match($request->getAttribute('routePath'));
    }
}
