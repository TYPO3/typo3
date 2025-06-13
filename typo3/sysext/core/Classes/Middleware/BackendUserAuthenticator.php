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

namespace TYPO3\CMS\Core\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;

/**
 * Boilerplate to authenticate a backend user in the current workflow, can be used
 * for TYPO3 Backend and Frontend requests.
 *
 * The actual authentication and the selection if no-cache headers to responses should
 * be applied should still reside in the "process()" method which should be
 * extended by derivative classes.
 *
 * In derivative classes, the Context API can be used to detect, if a backend user is logged in
 * like this:
 *
 * ```
 * $response = $handler->handle($request);
 * if ($this->context->getAspect('backend.user')->isLoggedIn()) {
 *     return $this->applyHeadersToResponse($response);
 * }
 * ```
 *
 * @internal this class might get merged again with the subclasses
 */
abstract class BackendUserAuthenticator implements MiddlewareInterface
{
    public function __construct(protected Context $context) {}

    abstract public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface;

    /**
     * Adding headers to the response to avoid caching on the client side.
     * These headers will override any previous headers of these names sent.
     * Get the http headers to be sent if an authenticated user is available,
     * in order to disallow browsers to store the response on the client side.
     *
     * @return ResponseInterface the modified response object.
     */
    protected function applyHeadersToResponse(ResponseInterface $response): ResponseInterface
    {
        $headers = [
            'Expires' => 0,
            'Last-Modified' => gmdate('D, d M Y H:i:s') . ' GMT',
            'Cache-Control' => 'no-cache, no-store',
            // HTTP 1.0 compatibility, see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Pragma
            'Pragma' => 'no-cache',
        ];
        foreach ($headers as $headerName => $headerValue) {
            $response = $response->withHeader($headerName, (string)$headerValue);
        }
        return $response;
    }

    /**
     * Register the backend user as aspect
     */
    protected function setBackendUserAspect(?BackendUserAuthentication $user, ?int $alternativeWorkspaceId = null): void
    {
        $this->context->setAspect('backend.user', new UserAspect($user));
        $this->context->setAspect('workspace', new WorkspaceAspect($alternativeWorkspaceId ?? $user->workspace ?? 0));
    }
}
