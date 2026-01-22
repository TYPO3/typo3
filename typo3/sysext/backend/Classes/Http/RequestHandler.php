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
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\RouteRedirect;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\Response;
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
     * Handles a backend request, after finishing running middlewares
     * Dispatch the request to the appropriate controller through the
     * Backend Dispatcher which resolves the routing
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Make sure all FAL resources have absolute URL paths
        $this->listenerProvider->addListener(
            GeneratePublicUrlForResourceEvent::class,
            PublicUrlPrefixer::class,
            'prefixWithSitePath'
        );

        /** @var Route $route */
        $route = $request->getAttribute('route');
        $isAjaxCall = (bool)($route->getOption('ajax') ?? false);

        // b/w compat
        $GLOBALS['TYPO3_REQUEST'] = $request;

        try {
            // Check if the router has the available route and dispatch.
            return $this->dispatcher->dispatch($request);
        } catch (MissingRequestTokenException $e) {
            if ($isAjaxCall) {
                return new Response(statusCode: 401);
            }
            // When token was missing, then redirect to login, but keep the current route as redirect after login
            $loginUrl = $this->uriBuilder->buildUriWithRedirect(
                'login',
                [],
                RouteRedirect::createFromRoute($request->getAttribute('route'), $request->getQueryParams())
            );
            return new RedirectResponse($loginUrl);
        } catch (InvalidRequestTokenException $e) {
            if ($isAjaxCall) {
                return new Response(statusCode: 401);
            }
            // When token was invalid, then redirect to login
            $loginForm = $this->uriBuilder->buildUriFromRoute('login');
            return new RedirectResponse($loginForm);
        }
    }
}
