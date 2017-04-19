<?php
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
 * Main entry point for AJAX calls in the TYPO3 Backend. Based on ?ajaxId of the outside application.
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
    protected $publicAjaxIds = [
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
     * @return NULL|\Psr\Http\Message\ResponseInterface
     */
    public function handleRequest(ServerRequestInterface $request)
    {
        // First get the ajaxID
        $ajaxID = isset($request->getParsedBody()['ajaxID']) ? $request->getParsedBody()['ajaxID'] : $request->getQueryParams()['ajaxID'];
        $request = $request->withAttribute('routePath', $ajaxID);
        $proceedIfNoUserIsLoggedIn = $this->isLoggedInBackendUserRequired($ajaxID);
        $this->boot($proceedIfNoUserIsLoggedIn);

        // Backend Routing - check if a valid route is there, and dispatch
        return $this->dispatch($request);
    }

    /**
     * This request handler can handle any backend request having
     * an ajaxID as parameter (see Application.php in EXT:backend)
     *
     * @param ServerRequestInterface $request
     * @return bool If the request is an AJAX backend request, TRUE otherwise FALSE
     */
    public function canHandleRequest(ServerRequestInterface $request)
    {
        return $request->getAttribute('isAjaxRequest', false);
    }

    /**
     * Returns the priority - how eager the handler is to actually handle the request.
     *
     * @return int The priority of the request handler.
     */
    public function getPriority()
    {
        return 80;
    }

    /**
     * Check if the user is required for the request
     * If we're trying to do an ajax login, don't require a user
     *
     * @param string $ajaxId the Ajax ID to check against
     * @return bool whether the request can proceed without a login required
     */
    protected function isLoggedInBackendUserRequired($ajaxId)
    {
        return in_array($ajaxId, $this->publicAjaxIds, true);
    }

    /**
     * Start the Backend bootstrap part
     *
     * @param bool $proceedIfNoUserIsLoggedIn a flag if a backend user is required
     */
    protected function boot($proceedIfNoUserIsLoggedIn)
    {
        $this->bootstrap
            ->checkLockedBackendAndRedirectOrDie($proceedIfNoUserIsLoggedIn)
            ->checkBackendIpOrDie()
            ->checkSslBackendAndRedirectIfNeeded()
            ->initializeBackendRouter()
            ->loadBaseTca()
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
    protected function dispatch(ServerRequestInterface $request)
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
