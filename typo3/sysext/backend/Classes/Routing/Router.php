<?php

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

namespace TYPO3\CMS\Backend\Routing;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Route as SymfonyRoute;
use TYPO3\CMS\Backend\Routing\Exception\MethodNotAllowedException;
use TYPO3\CMS\Backend\Routing\Exception\ResourceNotFoundException;
use TYPO3\CMS\Core\Routing\RequestContextFactory;
use TYPO3\CMS\Core\Routing\RouteCollection;
use TYPO3\CMS\Core\SingletonInterface;

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
class Router implements SingletonInterface
{
    /**
     * All routes used in the TYPO3 Backend
     */
    protected RouteCollection $routeCollection;

    public function __construct(
        protected readonly RequestContextFactory $requestContextFactory
    ) {
        $this->routeCollection = new RouteCollection();
    }

    /**
     * Adds a new route with a given identifier
     */
    public function addRoute(string $routeIdentifier, Route $route, array $aliases = []): void
    {
        $symfonyRoute = new SymfonyRoute($route->getPath(), [], [], $route->getOptions());
        $symfonyRoute->setMethods($route->getMethods());
        $this->routeCollection->add($routeIdentifier, $symfonyRoute);
        foreach ($aliases as $aliasName) {
            $this->routeCollection->addAlias($aliasName, $routeIdentifier);
        }
    }

    public function addRouteCollection(RouteCollection $routeCollection): void
    {
        $this->routeCollection->addCollection($routeCollection);
    }

    /**
     * Fetch all registered routes, only use in UriBuilder. Does not care about aliases,
     * so be careful with using this method.
     *
     * @return Route[]
     */
    public function getRoutes(): iterable
    {
        return $this->routeCollection->getIterator();
    }

    public function hasRoute(string $routeName): bool
    {
        return $this->routeCollection->get($routeName) !== null;
    }

    /**
     * Returns a route by its identifier, or null if nothing found.
     *
     * @return SymfonyRoute|Route|null
     */
    public function getRoute(string $routeName)
    {
        return $this->routeCollection->get($routeName);
    }

    /**
     * @internal only use in Core, this should not be exposed
     */
    public function getRouteCollection(): RouteCollection
    {
        return $this->routeCollection;
    }

    /**
     * Tries to match a URL path with a set of routes.
     *
     * @param string $pathInfo The path info to be parsed
     * @return Route the first Route object found
     * @throws ResourceNotFoundException If the resource could not be found
     */
    public function match($pathInfo): Route
    {
        foreach ($this->routeCollection->getIterator() as $routeIdentifier => $route) {
            // This check is done in a simple way as there are no parameters yet (get parameters only)
            if ($route->getPath() === $pathInfo) {
                $routeResult = new Route($route->getPath(), $route->getOptions());
                // Store the name of the Route in the _identifier option so the token can be checked against that
                $routeResult->setOption('_identifier', $routeIdentifier);
                return $routeResult;
            }
        }

        throw new ResourceNotFoundException('The requested resource "' . $pathInfo . '" was not found.', 1425389240);
    }

    /**
     * Matches a PSR-7 Request and returns a RouteResult with parameters and the resolved route.
     */
    public function matchResult(ServerRequestInterface $request): RouteResult
    {
        $path = $request->getUri()->getPath();
        if (($normalizedParams = $request->getAttribute('normalizedParams')) !== null) {
            // Remove the directory name of the script from the path. This will usually be `/typo3` in this context.
            $path = substr($path, strlen(dirname($normalizedParams->getScriptName())));
        }
        if ($path === '' || $path === '/' || $path === '/index.php') {
            // Allow the login page to be displayed if routing is not used and on index.php
            // (consolidate RouteDispatcher::evaluateReferrer() when changing 'login' to something different)
            $path = '/login';
        }
        $requestContext = $this->requestContextFactory->fromBackendRequest($request);
        try {
            $result = (new UrlMatcher($this->routeCollection, $requestContext))->match($path);
            $matchedSymfonyRoute = $this->routeCollection->get($result['_route']);
            if ($matchedSymfonyRoute === null) {
                throw new ResourceNotFoundException('The requested resource "' . $path . '" was not found.', 1607596900);
            }
        } catch (\Symfony\Component\Routing\Exception\MethodNotAllowedException $e) {
            throw new MethodNotAllowedException($e->getMessage(), 1612649842);
        } catch (\Symfony\Component\Routing\Exception\ResourceNotFoundException $e) {
            throw new ResourceNotFoundException('The requested resource "' . $path . '" was not found.', 1612649840);
        }
        // Apply matched method to route
        $matchedOptions = $matchedSymfonyRoute->getOptions();
        $methods = $matchedOptions['methods'] ?? [];
        unset($matchedOptions['methods']);
        $route = new Route($matchedSymfonyRoute->getPath(), $matchedOptions);
        if (count($methods) > 0) {
            $route->setMethods($methods);
        }
        $route->setOption('_identifier', $result['_route']);
        unset($result['_route']);
        return new RouteResult($route, $result);
    }

    /**
     * Tries to match a URI against the registered routes.
     * Use ->matchResult() instead, as this method will be deprecated in the future.
     *
     * @return Route the first Route object found
     */
    public function matchRequest(ServerRequestInterface $request): Route
    {
        return $this->matchResult($request)->getRoute();
    }
}
