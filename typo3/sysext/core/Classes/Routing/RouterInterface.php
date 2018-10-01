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

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * Base Router to be used all over the TYPO3 Core. Its base lies around PSR-7 requests + URIs, and special "RouteResult"
 * objects.
 */
interface RouterInterface
{
    /**
     * Generates an absolute URL
     */
    const ABSOLUTE_URL = 'url';

    /**
     * Generates an absolute path
     */
    const ABSOLUTE_PATH = 'absolute';

    /**
     * @param ServerRequestInterface $request
     * @param RouteResultInterface|null $previousResult
     * @return RouteResultInterface
     * @throws RouteNotFoundException
     */
    public function matchRequest(ServerRequestInterface $request, RouteResultInterface $previousResult = null): RouteResultInterface;

    /**
     * Builds a URI based on the $route and the given parameters.
     *
     * @param string|array $route either the route name, or for pages it is usually the array of a page record, or the page ID
     * @param array $parameters query parameters, specially reserved parameters are usually prefixed with "_"
     * @param string $fragment the section/fragment www.example.com/page/#fragment, WITHOUT the hash
     * @param string $type see the constants above.
     * @return UriInterface
     * @throws InvalidRouteArgumentsException
     */
    public function generateUri($route, array $parameters = [], string $fragment = '', string $type = self::ABSOLUTE_URL): UriInterface;
}
