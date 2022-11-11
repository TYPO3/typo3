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

namespace TYPO3\CMS\Backend\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Routing\SiteMatcher;
use TYPO3\CMS\Core\Utility\MathUtility;

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
    public function __construct(
        private readonly SiteMatcher $siteMatcher
    ) {
    }

    /**
     * Resolve the site information by checking the page ID ("id" parameter) which is typically
     * used in BE modules of type "web".
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $pageId = ($request->getQueryParams()['id'] ?? $request->getParsedBody()['id'] ?? 0);
        if (!MathUtility::canBeInterpretedAsInteger($pageId)) {
            // @todo: The "filelist" module abuses "id" to carry a storage like "1:/" around. This
            //        should be changed. To *always* have a site attribute attached to the request,
            //        we for now resolve to zero here, leading to NullSite object.
            //        Change "filelist" module to no longer abuse "id" GET argument and throw an
            //        exception here if $pageUid can not be resolved to an int.
            $pageId = 0;
        }
        $pageId = (int)$pageId;
        $rootLine = null;
        if ($pageId > 0) {
            $rootLine = BackendUtility::BEgetRootLine($pageId);
        }
        $site = $this->siteMatcher->matchByPageId($pageId, $rootLine);
        $request = $request->withAttribute('site', $site);
        return $handler->handle($request);
    }
}
