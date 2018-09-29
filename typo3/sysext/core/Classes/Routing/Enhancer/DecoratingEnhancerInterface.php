<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Routing\Enhancer;

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

use TYPO3\CMS\Core\Routing\RouteCollection;

/**
 * Decorates a route (or routes within a collection) with additional parameters.
 */
interface DecoratingEnhancerInterface extends EnhancerInterface
{
    /**
     * Decorates route collection and modifies route parameters and the
     * URL path to be processed during URL resolving. Executed before invoking
     * routing enhancers.
     *
     * @param RouteCollection $collection
     * @param array $parameters reference to reconstituted parameters
     * @param string $routePath reference to URL path
     */
    public function decorateForMatching(RouteCollection $collection, array &$parameters, string &$routePath): void;

    /**
     * Decorates route collection and modifies route parameters during URL
     * URL generation. Executed before invoking routing enhancers.
     *
     * @param RouteCollection $collection
     * @param array $parameters reference to query parameters
     */
    public function decorateForGeneration(RouteCollection $collection, array &$parameters): void;
}
