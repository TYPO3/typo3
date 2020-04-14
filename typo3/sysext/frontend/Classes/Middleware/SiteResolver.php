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

namespace TYPO3\CMS\Frontend\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Routing\SiteMatcher;
use TYPO3\CMS\Core\Routing\SiteRouteResult;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

/**
 * Identifies if a site is configured for the request, based on "id" and "L" GET/POST parameters, or the requested
 * string.
 *
 * If a site is found, the request is populated with the found language+site objects. If none is found, the main magic
 * is handled by the PageResolver middleware.
 */
class SiteResolver implements MiddlewareInterface
{
    /**
     * @var SiteMatcher
     */
    protected $matcher;

    public function __construct(SiteMatcher $matcher)
    {
        $this->matcher = $matcher;
    }

    /**
     * Resolve the site/language information by checking the page ID or the URL.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var SiteRouteResult $routeResult */
        $routeResult = $this->matcher->matchRequest($request);
        $request = $request->withAttribute('site', $routeResult->getSite());
        $request = $request->withAttribute('language', $routeResult->getLanguage());
        $request = $request->withAttribute('routing', $routeResult);
        if ($routeResult->getLanguage() instanceof SiteLanguage) {
            Locales::setSystemLocaleFromSiteLanguage($routeResult->getLanguage());
        }
        return $handler->handle($request);
    }
}
