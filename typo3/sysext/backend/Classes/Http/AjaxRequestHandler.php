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
use TYPO3\CMS\Backend\Routing\Exception\InvalidRequestTokenException;
use TYPO3\CMS\Backend\Routing\Exception\ResourceNotFoundException;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Http\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * AJAX dispatcher
 *
 * Main entry point for AJAX calls in the TYPO3 Backend. Based on ?route=/ajax/* of the outside application.
 * Before doing the basic BE-related set up of this request (see the additional calls on $this->bootstrap inside
 * handleRequest()), some AJAX-calls can be made without a valid user, which is determined here.
 *
 * AJAX Requests are typically registered within EXT:myext/Configuration/Backend/AjaxRoutes.php
 */
class AjaxRequestHandler implements RequestHandlerInterface
{
    /**
     * Instance of the current TYPO3 bootstrap
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * List of requests that don't need a valid BE user
     * @var array
     */
    protected $publicAjaxRoutes = [
        '/ajax/login',
        '/ajax/logout',
        '/ajax/login/refresh',
        '/ajax/login/timedout',
        '/ajax/rsa/publickey'
    ];

    /**
     * Constructor handing over the bootstrap and the original request
     *
     * @param Bootstrap $bootstrap
     */
    public function __construct(Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    /**
     * Handles any AJAX request in the TYPO3 Backend
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        // First get the name of the route
        $routePath = $request->getParsedBody()['route'] ?? $request->getQueryParams()['route'] ?? '';
        $request = $request->withAttribute('routePath', $routePath);

        $proceedIfNoUserIsLoggedIn = $this->isLoggedInBackendUserRequired($routePath);
        $this->boot($proceedIfNoUserIsLoggedIn);

        // Backend Routing - check if a valid route is there, and dispatch
        return $this->dispatch($request);
    }

    /**
     * This request handler can handle any backend request having
     * an /ajax/ request
     *
     * @param ServerRequestInterface $request
     * @return bool If the request is an AJAX backend request, TRUE otherwise FALSE
     */
    public function canHandleRequest(ServerRequestInterface $request): bool
    {
        $routePath = $request->getParsedBody()['route'] ?? $request->getQueryParams()['route'] ?? '';
        return strpos($routePath, '/ajax/') === 0;
    }

    /**
     * Returns the priority - how eager the handler is to actually handle the request.
     *
     * @return int The priority of the request handler.
     */
    public function getPriority(): int
    {
        return 80;
    }

    /**
     * Check if the user is required for the request
     * If we're trying to do an ajax login, don't require a user
     *
     * @param string $routePath the Route path to check against, something like '
     * @return bool whether the request can proceed without a login required
     */
    protected function isLoggedInBackendUserRequired(string $routePath): bool
    {
        return in_array($routePath, $this->publicAjaxRoutes, true);
    }

    /**
     * Start the Backend bootstrap part
     *
     * @param bool $proceedIfNoUserIsLoggedIn a flag if a backend user is required
     */
    protected function boot(bool $proceedIfNoUserIsLoggedIn)
    {
        $this->bootstrap
            ->checkLockedBackendAndRedirectOrDie($proceedIfNoUserIsLoggedIn)
            ->checkBackendIpOrDie()
            ->checkSslBackendAndRedirectIfNeeded()
            ->initializeBackendRouter()
            ->loadExtTables()
            ->initializeBackendUser()
            ->initializeBackendAuthentication($proceedIfNoUserIsLoggedIn)
            ->initializeLanguageObject()
            ->initializeBackendTemplate()
            ->endOutputBufferingAndCleanPreviousOutput()
            ->initializeOutputCompression()
            ->sendHttpHeaders();
    }

    /**
     * Creates a response object with JSON headers automatically, and then dispatches to the correct route
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface $response
     * @throws ResourceNotFoundException if no valid route was found
     * @throws InvalidRequestTokenException if the request could not be verified
     */
    protected function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Response $response */
        $response = GeneralUtility::makeInstance(Response::class, 'php://temp', 200, [
            'Content-Type' => 'application/json; charset=utf-8',
            'X-JSON' => 'true'
        ]);

        /** @var RouteDispatcher $dispatcher */
        $dispatcher = GeneralUtility::makeInstance(RouteDispatcher::class);
        return $dispatcher->dispatch($request, $response);
    }
}
