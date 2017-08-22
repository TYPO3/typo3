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
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Http\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * General RequestHandler for the TYPO3 Backend. This is used for all Backend requests except for CLI
 * or AJAX calls.
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
    /**
     * Instance of the current TYPO3 bootstrap
     * @var Bootstrap
     */
    protected $bootstrap;

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
     * Handles any backend request
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handleRequest(ServerRequestInterface $request)
    {
        // Allow the login page to be displayed if routing is not used and on index.php
        $pathToRoute = (string)$request->getQueryParams()['route'] ?: '/login';
        $request = $request->withAttribute('routePath', $pathToRoute);

        // skip the BE user check on the login page
        // should be handled differently in the future by checking the Bootstrap directly
        $this->boot($pathToRoute === '/login');

        // Check if the router has the available route and dispatch.
        try {
            return $this->dispatch($request);
        } catch (InvalidRequestTokenException $e) {
            // When token was invalid redirect to login
            $url = GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . TYPO3_mainDir;
            \TYPO3\CMS\Core\Utility\HttpUtility::redirect($url);
        }
    }

    /**
     * Does the main work for setting up the backend environment for any Backend request
     *
     * @param bool $proceedIfNoUserIsLoggedIn option to allow to render the request even if no user is logged in
     */
    protected function boot($proceedIfNoUserIsLoggedIn)
    {
        $this->bootstrap
            ->checkLockedBackendAndRedirectOrDie()
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
     * This request handler can handle any backend request (but not CLI).
     *
     * @param ServerRequestInterface $request
     * @return bool If the request is not a CLI script, TRUE otherwise FALSE
     */
    public function canHandleRequest(ServerRequestInterface $request)
    {
        return TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_BE && !(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI);
    }

    /**
     * Returns the priority - how eager the handler is to actually handle the
     * request.
     *
     * @return int The priority of the request handler.
     */
    public function getPriority()
    {
        return 50;
    }

    /**
     * Dispatch the request to the appropriate controller through the Backend Dispatcher which resolves the routing
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws InvalidRequestTokenException if the request could not be verified
     * @throws \InvalidArgumentException when a route is found but the target of the route cannot be called
     */
    protected function dispatch($request)
    {
        /** @var Response $response */
        $response = GeneralUtility::makeInstance(Response::class);
        /** @var RouteDispatcher $dispatcher */
        $dispatcher = GeneralUtility::makeInstance(RouteDispatcher::class);
        return $dispatcher->dispatch($request, $response);
    }
}
