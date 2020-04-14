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
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Routing\RouteNotFoundException;
use TYPO3\CMS\Core\Routing\SiteRouteResult;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;

/**
 * Resolve the page ID based on TYPO3's routing functionality configured in a site.
 *
 * Processes the page ID, page type (typeNum) and other parameters built from queryArguments and routeParameters.
 * After this point we have an array, TSFE->page, which is the page-record of the current page, $TSFE->id.
 *
 * However, if there is a backend user logged in and he has NO access to this page (and the page is hidden),
 * then the ID is determined again and the backend user is not considered for the rest of the frontend request.
 */
class PageResolver implements MiddlewareInterface
{
    /**
     * Resolve the page ID
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $site = $request->getAttribute('site', null);

        if (!$site instanceof Site) {
            return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                $request,
                'No site configuration found.',
                ['code' => PageAccessFailureReasons::PAGE_NOT_FOUND]
            );
        }

        /** @var SiteRouteResult $previousResult */
        $previousResult = $request->getAttribute('routing', null);
        if (!$previousResult) {
            return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                $request,
                'The requested page does not exist',
                ['code' => PageAccessFailureReasons::PAGE_NOT_FOUND]
            );
        }

        // Check for the route arguments or Query Parameter ID
        try {
            /** @var PageArguments $pageArguments */
            $pageArguments = $site->getRouter()->matchRequest($request, $previousResult);
            $request = $request->withAttribute('routing', $pageArguments);
        } catch (RouteNotFoundException $e) {
            return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                $request,
                'The requested page does not exist',
                ['code' => PageAccessFailureReasons::PAGE_NOT_FOUND]
            );
        }

        if (!$pageArguments->getPageId()) {
            return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                $request,
                'The requested page does not exist',
                ['code' => PageAccessFailureReasons::PAGE_NOT_FOUND]
            );
        }

        // stop in case arguments are dirty (=defined twice in route and GET query parameters)
        if ($pageArguments->areDirty()) {
            return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                $request,
                'The requested URL is not distinct',
                ['code' => PageAccessFailureReasons::PAGE_NOT_FOUND]
            );
        }

        // merge the PageArguments with the request query parameters
        $queryParams = array_replace_recursive($request->getQueryParams(), $pageArguments->getArguments());
        $request = $request->withQueryParams($queryParams);

        return $handler->handle($request);
    }
}
