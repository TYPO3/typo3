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

namespace TYPO3\CMS\Backend\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Backend\Resource\PublicUrlPrefixer;
use TYPO3\CMS\Backend\Routing\Exception\InvalidRequestTokenException;
use TYPO3\CMS\Backend\Routing\Exception\MissingRequestTokenException;
use TYPO3\CMS\Backend\Routing\RouteRedirect;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Resource\Event\GeneratePublicUrlForResourceEvent;

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
class RequestHandler implements RequestHandlerInterface
{
    protected RouteDispatcher $dispatcher;

    protected UriBuilder $uriBuilder;

    protected ListenerProvider $listenerProvider;

    /**
     * @param RouteDispatcher $dispatcher
     */
    public function __construct(
        RouteDispatcher $dispatcher,
        UriBuilder $uriBuilder,
        ListenerProvider $listenerProvider
    ) {
        $this->dispatcher = $dispatcher;
        $this->uriBuilder = $uriBuilder;
        $this->listenerProvider = $listenerProvider;
    }

    /**
     * Sets the global GET and POST to the values, so if people access $_GET and $_POST
     * Within hooks starting NOW (e.g. cObject), they get the "enriched" data from query params.
     *
     * This needs to be run after the request object has been enriched with modified GET/POST variables.
     *
     * @param ServerRequestInterface $request
     * @internal this safety net will be removed in TYPO3 v11.0.
     */
    protected function resetGlobalsToCurrentRequest(ServerRequestInterface $request)
    {
        if ($request->getQueryParams() !== $_GET) {
            $queryParams = $request->getQueryParams();
            $_GET = $queryParams;
            $GLOBALS['HTTP_GET_VARS'] = $_GET;
        }
        if ($request->getMethod() === 'POST') {
            $parsedBody = $request->getParsedBody();
            if (is_array($parsedBody) && $parsedBody !== $_POST) {
                $_POST = $parsedBody;
                $GLOBALS['HTTP_POST_VARS'] = $_POST;
            }
        }
        $GLOBALS['TYPO3_REQUEST'] = $request;
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
        // Make sure all FAL resources have absolute URL paths
        $this->listenerProvider->addListener(
            GeneratePublicUrlForResourceEvent::class,
            PublicUrlPrefixer::class,
            'prefixWithSitePath'
        );
        // safety net to have the fully-added request object globally available as long as
        // there are Core classes that need the Request object but do not get it handed in
        $this->resetGlobalsToCurrentRequest($request);
        try {
            // Check if the router has the available route and dispatch.
            return $this->dispatcher->dispatch($request);
        } catch (MissingRequestTokenException $e) {
            // When token was missing, then redirect to login, but keep the current route as redirect after login
            $loginUrl = $this->uriBuilder->buildUriWithRedirect(
                'login',
                [],
                RouteRedirect::createFromRoute($request->getAttribute('route'), $request->getQueryParams())
            );
            return new RedirectResponse($loginUrl);
        } catch (InvalidRequestTokenException $e) {
            // When token was invalid, then redirect to login
            $loginForm = $this->uriBuilder->buildUriFromRoute('login');
            return new RedirectResponse($loginForm);
        }
    }
}
