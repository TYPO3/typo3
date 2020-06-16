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

namespace TYPO3\CMS\Workspaces\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Workspaces\Authentication\PreviewUserAuthentication;

/**
 * Middleware to set the current page ID (for accessing the page) for the PreviewBackendUser,
 * so the preview user is allowed to actually access the page (even if it is hidden).
 *
 * @internal
 */
class WorkspacePreviewPermissions implements MiddlewareInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws \Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $pageArguments = $request->getAttribute('routing', null);
        if ($pageArguments instanceof PageArguments && $GLOBALS['BE_USER'] instanceof PreviewUserAuthentication) {
            $GLOBALS['BE_USER']->setWebmounts([$pageArguments->getPageId()]);
        }
        return $handler->handle($request);
    }
}
