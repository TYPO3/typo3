<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Http;

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
use Psr\Http\Server\RequestHandlerInterface as PsrRequestHandlerInterface;
use TYPO3\CMS\Backend\Routing\Exception\InvalidRequestTokenException;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * General RequestHandler for the TYPO3 Backend. This is used for all Backend requests, including AJAX routes.
 *
 * If a get/post parameter "route" is set, the Backend Routing is called and searches for a
 * matching route inside the Router. The corresponding controller / action is called then which returns the response.
 *
 * The following get/post parameters are evaluated here:
 *   - route
 *   - token
 */
class RequestHandler implements RequestHandlerInterface, PsrRequestHandlerInterface
{
    /**
     * Handles any backend request
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        return $this->handle($request);
    }

    /**
     * Handles a backend request, after finishing running middlewares
     * Dispatch the request to the appropriate controller through the
     * Backend Dispatcher which resolves the routing
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Use a custom pre-created response for AJAX calls
        // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0: No prepared $response to RouteDispatcher any longer
        if (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX) {
            $response = new Response('php://temp', 200, [
                'Content-Type' => 'application/json; charset=utf-8',
                'X-JSON' => 'true'
            ]);
        } else {
            $response = new Response();
        }
        try {
            // Check if the router has the available route and dispatch.
            $dispatcher = GeneralUtility::makeInstance(RouteDispatcher::class);
            return $dispatcher->dispatch($request, $response);
        } catch (InvalidRequestTokenException $e) {
            // When token was invalid redirect to login
            $url = GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . TYPO3_mainDir;
            return new RedirectResponse($url);
        }
    }

    /**
     * This request handler can handle any backend request.
     *
     * @param ServerRequestInterface $request
     * @return bool If the request is BE request TRUE otherwise FALSE
     */
    public function canHandleRequest(ServerRequestInterface $request): bool
    {
        return (bool)(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_BE);
    }

    /**
     * Returns the priority - how eager the handler is to actually handle the
     * request.
     *
     * @return int The priority of the request handler.
     */
    public function getPriority(): int
    {
        return 50;
    }
}
