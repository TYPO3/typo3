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

namespace TYPO3\CMS\Core\Routing;

use Symfony\Component\Routing\Matcher\RedirectableUrlMatcherInterface;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RouteCollection as SymfonyRouteCollection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal
 */
class BestUrlMatcher extends UrlMatcher
{
    protected function matchCollection(string $pathinfo, SymfonyRouteCollection $routes): array
    {
        $matchedRoutes = $this->preMatchCollection($pathinfo, $routes);
        $matches = count($matchedRoutes);
        if ($matches === 0) {
            return [];
        }
        if ($matches === 1) {
            return $matchedRoutes[0]->getRouteResult();
        }
        usort($matchedRoutes, [$this, 'sortMatchedRoutes']);
        return array_shift($matchedRoutes)->getRouteResult();
    }

    /**
     * Tries to match a URL with a set of routes.
     * Basically all code has been duplicated from `UrlMatcher::matchCollection`, the difference is
     * it does not just return the first match, but return all possible matches for further reduction.
     *
     * @param string $pathinfo The path info to be parsed
     * @return list<MatchedRoute>
     */
    protected function preMatchCollection(string $pathinfo, SymfonyRouteCollection $routes): array
    {
        $matchedRoutes = [];

        // HEAD and GET are equivalent as per RFC
        $method = $this->context->getMethod();
        if ($method === 'HEAD') {
            $method = 'GET';
        }
        $supportsTrailingSlash = $method === 'GET' && $this instanceof RedirectableUrlMatcherInterface;
        $trimmedPathinfo = rtrim($pathinfo, '/') ?: '/';

        foreach ($routes as $name => $route) {
            $compiledRoute = $route->compile();
            $staticPrefix = rtrim($compiledRoute->getStaticPrefix(), '/');
            $requiredMethods = $route->getMethods();

            // check the static prefix of the URL first. Only use the more expensive preg_match when it matches
            if ($staticPrefix !== '' && !str_starts_with($trimmedPathinfo, $staticPrefix)) {
                continue;
            }
            $regex = $compiledRoute->getRegex();

            $pos = strrpos($regex, '$');
            $hasTrailingSlash = $regex[$pos - 1] === '/';
            $regex = substr_replace($regex, '/?$', $pos - $hasTrailingSlash, 1 + $hasTrailingSlash);

            if (!preg_match($regex, $pathinfo, $matches)) {
                continue;
            }

            $hasTrailingVar = $trimmedPathinfo !== $pathinfo && preg_match('#\{[\w\x80-\xFF]+\}/?$#', $route->getPath());

            if ($hasTrailingVar && ($hasTrailingSlash || (null === $m = $matches[\count($compiledRoute->getPathVariables())] ?? null) || '/' !== ($m[-1] ?? '/')) && preg_match($regex, $trimmedPathinfo, $m)) {
                if ($hasTrailingSlash) {
                    $matches = $m;
                } else {
                    $hasTrailingVar = false;
                }
            }

            $hostMatches = [];
            if ($compiledRoute->getHostRegex() && !preg_match($compiledRoute->getHostRegex(), $this->context->getHost(), $hostMatches)) {
                continue;
            }

            $attributes = $this->getAttributes($route, $name, array_replace($matches, $hostMatches));

            $status = $this->handleRouteRequirements($pathinfo, $name, $route, $attributes);

            if ($status[0] === self::REQUIREMENT_MISMATCH) {
                continue;
            }

            if ($pathinfo !== '/' && !$hasTrailingVar && $hasTrailingSlash === ($trimmedPathinfo === $pathinfo)) {
                if ($supportsTrailingSlash && (!$requiredMethods || \in_array('GET', $requiredMethods))) {
                    return $this->allow = $this->allowSchemes = [];
                }
                continue;
            }

            if ($route->getSchemes() && !$route->hasScheme($this->context->getScheme())) {
                $this->allowSchemes = array_merge($this->allowSchemes, $route->getSchemes());
                continue;
            }

            if ($requiredMethods && !\in_array($method, $requiredMethods)) {
                $this->allow = array_merge($this->allow, $requiredMethods);
                continue;
            }

            $matchedRoute = GeneralUtility::makeInstance(
                MatchedRoute::class,
                $route,
                array_replace($attributes, $status[1] ?? [])
            );
            $matchedRoutes[] = $matchedRoute->withPathMatches($matches)->withHostMatches($hostMatches);
        }

        return $matchedRoutes;
    }

    /**
     * Sorts the best matching route result to the beginning
     */
    protected function sortMatchedRoutes(MatchedRoute $a, MatchedRoute $b): int
    {
        if ($a->getFallbackScore() !== $b->getFallbackScore()) {
            // sort fallbacks to the end
            return $a->getFallbackScore() <=> $b->getFallbackScore();
        }
        if ($b->getHostMatchScore() !== $a->getHostMatchScore()) {
            // sort more specific host matches to the beginning
            return $b->getHostMatchScore() <=> $a->getHostMatchScore();
        }
        // index `1` refers to the array index containing the corresponding `tail` match
        // @todo not sure, whether `tail` can be defined generic, it's hard coded in `SiteMatcher`
        if ($b->getPathMatchScore(1) !== $a->getPathMatchScore(1)) {
            return $b->getPathMatchScore(1) <=> $a->getPathMatchScore(1);
        }
        // fallback for behavior prior to issue #93240, using reverse sorted site identifier
        // (side note: site identifier did not contain any URL relevant information)
        return $b->getSiteIdentifier() <=> $a->getSiteIdentifier();
    }
}
