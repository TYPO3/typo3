<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Middleware;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Usually called after the route object is resolved, however, this is not possible yet as this happens
 * within the RequestHandler/RouteDispatcher right now and should go away.
 *
 * This middleware checks for a "id" parameter. If present, it adds a site information to this page ID.
 *
 * Very useful for all "Web" related modules to resolve all available languages for a site.
 */
class SiteResolver implements MiddlewareInterface
{
    /**
     * Resolve the site information by checking the page ID ("id" parameter) which is typically used in BE modules
     * of type "web".
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $finder = GeneralUtility::makeInstance(SiteFinder::class);
        $site = null;
        $pageId = (int)($request->getQueryParams()['id'] ?? $request->getParsedBody()['id'] ?? 0);

        // Check if we have a _GET/_POST parameter for "id", then a site information can be resolved based.
        if ($pageId > 0) {
            try {
                $site = $finder->getSiteByPageId($pageId);
                $request = $request->withAttribute('site', $site);
                $GLOBALS['TYPO3_REQUEST'] = $request;
            } catch (SiteNotFoundException $e) {
            }
        }
        return $handler->handle($request);
    }
}
