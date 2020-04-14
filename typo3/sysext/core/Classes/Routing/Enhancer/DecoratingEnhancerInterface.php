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

namespace TYPO3\CMS\Core\Routing\Enhancer;

use TYPO3\CMS\Core\Routing\RouteCollection;

/**
 * Decorates a route (or routes within a collection) with additional parameters.
 */
interface DecoratingEnhancerInterface extends EnhancerInterface
{
    /**
     * Gets pattern that can be used to redecorate (undecorate)
     * a potential previously decorated route path.
     *
     * Example:
     * + route path: 'first/second.html'
     * + redecoration pattern: '(?:\.html|\.json)$'
     * -> 'first/second' might be the redecorated route path after
     *    applying the redecoration pattern to preg_match/preg_replace
     *
     * @return string regular expression pattern
     */
    public function getRoutePathRedecorationPattern(): string;

    /**
     * Decorates route collection to be processed during URL resolving.
     * Executed before invoking routing enhancers.
     *
     * @param RouteCollection $collection
     * @param string $routePath URL path
     */
    public function decorateForMatching(RouteCollection $collection, string $routePath): void;

    /**
     * Decorates route collection during URL URL generation.
     * Executed before invoking routing enhancers.
     *
     * @param RouteCollection $collection
     * @param array $parameters query parameters
     */
    public function decorateForGeneration(RouteCollection $collection, array $parameters): void;
}
