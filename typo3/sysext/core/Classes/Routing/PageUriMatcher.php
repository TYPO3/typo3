<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Routing;

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

use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use TYPO3\CMS\Core\Routing\Aspect\MappableProcessor;

/**
 * Internal class, which is similar to Symfony's Urlmatcher but without validating
 * - conditions / expression language
 * - host matches
 * - method checks
 * because this method only works in conjunction with PageRouter.
 *
 * @internal
 */
class PageUriMatcher
{
    /**
     * @var RouteCollection
     */
    protected $routes;

    /**
     * @var MappableProcessor
     */
    protected $mappableProcessor;

    public function __construct(RouteCollection $routes)
    {
        $this->routes = $routes;
        $this->mappableProcessor = new MappableProcessor();
    }

    /**
     * Matches a path segment against the route collection
     *
     * @param string $urlPath
     * @return array
     * @throws ResourceNotFoundException
     */
    public function match(string $urlPath)
    {
        if ($ret = $this->matchCollection(rawurldecode($urlPath), $this->routes)) {
            return $ret;
        }
        throw new ResourceNotFoundException(
            sprintf('No routes found for "%s".', $urlPath),
            1538156220
        );
    }

    /**
     * Tries to match a URL with a set of routes.
     *
     * @param string $urlPath The path info to be parsed
     * @param RouteCollection $routes The set of routes
     * @return array An array of parameters
     */
    protected function matchCollection(string $urlPath, RouteCollection $routes): ?array
    {
        foreach ($routes as $name => $route) {
            $urlPath = $this->getDecoratedRoutePath($route) ?? $urlPath;
            $compiledRoute = $route->compile();

            // check the static prefix of the URL first. Only use the more expensive preg_match when it matches
            if ('' !== $compiledRoute->getStaticPrefix() && 0 !== strpos($urlPath, $compiledRoute->getStaticPrefix())) {
                continue;
            }

            if (!preg_match($compiledRoute->getRegex(), $urlPath, $matches)) {
                continue;
            }

            // custom handling of Mappable instances
            if (!$this->mappableProcessor->resolve($route, $matches)) {
                continue;
            }

            return $this->getAttributes($route, $name, $matches);
        }
        return null;
    }

    /**
     * Resolves an optional route specific decorated route path that has been
     * assigned by DecoratingEnhancerInterface instances.
     *
     * @param Route $route
     * @return string|null
     */
    protected function getDecoratedRoutePath(Route $route): ?string
    {
        if (!$route->hasOption('_decoratedRoutePath')) {
            return null;
        }
        $urlPath = $route->getOption('_decoratedRoutePath');
        return rawurldecode($urlPath);
    }

    /**
     * Returns an array of values to use as request attributes.
     *
     * As this method requires the Route object, it is not available
     * in matchers that do not have access to the matched Route instance
     * (like the PHP and Apache matcher dumpers).
     *
     * @param Route $route The route we are matching against
     * @param string $name The name of the route
     * @param array $attributes An array of attributes from the matcher
     * @return array An array of parameters
     */
    protected function getAttributes(Route $route, string $name, array $attributes): array
    {
        $defaults = $route->getDefaults();
        if (isset($defaults['_canonical_route'])) {
            $name = $defaults['_canonical_route'];
            unset($defaults['_canonical_route']);
        }
        $attributes['_route'] = $name;
        // store applied default values in route options
        $relevantDefaults = array_intersect_key($defaults, array_flip($route->compile()->getPathVariables()));
        // option '_appliedDefaults' contains internal(!) values (default values are not mapped when resolving)
        // (keys used are deflated and need to be inflated later using VariableProcessor)
        $route->setOption('_appliedDefaults', array_diff_key($relevantDefaults, $attributes));
        // side note: $defaults can contain e.g. '_controller'
        return $this->mergeDefaults($attributes, $defaults);
    }

    /**
     * Get merged default parameters.
     *
     * @param array $params The parameters
     * @param array $defaults The defaults
     * @return array Merged default parameters
     */
    protected function mergeDefaults(array $params, array $defaults): array
    {
        foreach ($params as $key => $value) {
            if (!is_int($key) && null !== $value) {
                $defaults[$key] = $value;
            }
        }
        return $defaults;
    }
}
