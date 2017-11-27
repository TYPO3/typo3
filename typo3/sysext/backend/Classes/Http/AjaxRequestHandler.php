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
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Http\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * AJAX dispatcher
 *
 * Main entry point for AJAX calls in the TYPO3 Backend. Based on ?ajaxId of the outside application.
 * Before doing the basic BE-related set up of this request (see the additional calls on $this->bootstrap inside
 * handleRequest()), some AJAX-calls can be made without a valid user, which is determined here.
 * See $GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX'] and the Core APIs on how to register an AJAX call in the TYPO3 Backend.
 *
 * Due to legacy reasons, the actual logic is in EXT:core/Http/AjaxRequestHandler which will eventually
 * be moved into this class.
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
     * @return \Psr\Http\Message\ResponseInterface|null
     */
    public function handleRequest(ServerRequestInterface $request)
    {
        // First get the ajaxID
        $ajaxID = isset($request->getParsedBody()['ajaxID']) ? $request->getParsedBody()['ajaxID'] : $request->getQueryParams()['ajaxID'];
        $request = $request->withAttribute('routePath', $ajaxID);
        $proceedIfNoUserIsLoggedIn = $this->isLoggedInBackendUserRequired($ajaxID);
        $this->boot($proceedIfNoUserIsLoggedIn);

        try {
            // Backend Routing - check if a valid route is there, and dispatch
            return $this->dispatch($request);
        } catch (ResourceNotFoundException $e) {
            // no Route found, fallback to the traditional AJAX request
        }
        return $this->dispatchTraditionalAjaxRequest($request);
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

    /**
     * Calls the ajax callback method registered in TYPO3_CONF_VARS[BE][AJAX]
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    protected function dispatchTraditionalAjaxRequest($request)
    {
        GeneralUtility::deprecationLog('Using the traditional way for AJAX requests via $TYPO3_CONF_VARS[BE][AJAX] is discouraged. Use the Backend Routes logic instead.');
        $ajaxID = $request->getAttribute('routePath');
        // Finding the script path from the registry
        $ajaxRegistryEntry = isset($GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX'][$ajaxID]) ? $GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX'][$ajaxID] : null;
        $ajaxScript = null;
        $csrfTokenCheck = false;
        if ($ajaxRegistryEntry !== null && is_array($ajaxRegistryEntry) && isset($ajaxRegistryEntry['callbackMethod'])) {
            $ajaxScript = $ajaxRegistryEntry['callbackMethod'];
            $csrfTokenCheck = $ajaxRegistryEntry['csrfTokenCheck'];
        }

        // Instantiating the AJAX object
        /** @var \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj */
        $ajaxObj = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Http\AjaxRequestHandler::class, $ajaxID);
        $ajaxParams = ['request' => $request];

        // Evaluating the arguments and calling the AJAX method/function
        if (empty($ajaxID)) {
            $ajaxObj->setError('No valid ajaxID parameter given.');
        } elseif (empty($ajaxScript)) {
            $ajaxObj->setError('No backend function registered for ajaxID "' . $ajaxID . '".');
        } elseif ($csrfTokenCheck && !$this->isValidRequest($request)) {
            $ajaxObj->setError('Invalid CSRF token detected for ajaxID "' . $ajaxID . '", reload the backend of TYPO3');
        } else {
            $success = GeneralUtility::callUserFunction($ajaxScript, $ajaxParams, $ajaxObj, '', 1);
            if ($success === false) {
                $ajaxObj->setError('Registered backend function for ajaxID "' . $ajaxID . '" was not found.');
            }
        }

        // Outputting the content (and setting the X-JSON-Header)
        return $ajaxObj->render();
    }

    /**
     * Wrapper method for static form protection utility
     *
     * @return \TYPO3\CMS\Core\FormProtection\AbstractFormProtection
     */
    protected function getFormProtection()
    {
        return FormProtectionFactory::get();
    }

    /**
     * Checks if the request token is valid. This is checked to see if the route is really
     * created by the same instance. Should be called for all routes in the backend except
     * for the ones that don't require a login.
     *
     * @param ServerRequestInterface $request
     * @return bool
     * @see \TYPO3\CMS\Backend\Routing\UriBuilder where the token is generated.
     */
    protected function isValidRequest(ServerRequestInterface $request)
    {
        $token = (string)(isset($request->getParsedBody()['ajaxToken']) ? $request->getParsedBody()['ajaxToken'] : $request->getQueryParams()['ajaxToken']);
        return $this->getFormProtection()->validateToken($token, 'ajaxCall', $request->getAttribute('routePath'));
    }
}
