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
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Routing\SiteMatcher;
use TYPO3\CMS\Core\Routing\SiteRouteResult;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;

/**
 * Identifies if a site is configured for the request, based on "id" and "L" GET/POST parameters, or the requested
 * string.
 *
 * If a site is found, the request is populated with the found language+site objects. If none is found, the main magic
 * is handled by the PageResolver middleware.
 */
class SiteResolver implements MiddlewareInterface
{
    public function __construct(
        protected readonly SiteMatcher $matcher,
        protected readonly LoggerInterface $logger,
        protected readonly ErrorController $errorController,
    ) {}

    /**
     * Resolve the site/language information by checking the page ID or the URL.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var SiteRouteResult $routeResult */
        $routeResult = $this->matcher->matchRequest($request);

        $site = $routeResult->getSite();
        if ($site instanceof Site && $site->invalidSets !== []) {
            $invalidSets = implode(', ', array_keys($site->invalidSets));
            $this->logger->error('Site {identifier} depends on unavailable sets: {invalidSets}', [
                'identifier' => $site->getIdentifier(),
                'invalidSets' => $invalidSets,
            ]);
            return $this->errorController->internalErrorAction(
                $request,
                sprintf(
                    'Site %s depends on unavailable sets: %s',
                    $site->getIdentifier(),
                    $invalidSets,
                ),
                ['code' => PageAccessFailureReasons::INVALID_SITE_SETS]
            );
        }

        $request = $request->withAttribute('site', $site);
        $request = $request->withAttribute('language', $routeResult->getLanguage());
        $request = $request->withAttribute('routing', $routeResult);
        if ($routeResult->getLanguage() instanceof SiteLanguage) {
            Locales::setSystemLocaleFromSiteLanguage($routeResult->getLanguage());
        }
        return $handler->handle($request);
    }
}
