<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Middleware;

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
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Initializes the backend user authentication object (BE_USER) and the global LANG object.
 *
 * @internal
 */
class BackendUserAuthenticator implements MiddlewareInterface
{
    /**
     * List of requests that don't need a valid BE user
     *
     * @var array
     */
    protected $publicRoutes = [
        '/login',
        '/login/frame',
        '/ajax/login',
        '/ajax/logout',
        '/ajax/login/preflight',
        '/ajax/login/refresh',
        '/ajax/login/timedout',
        '/ajax/rsa/publickey',
        '/ajax/core/requirejs',
    ];

    /**
     * Calls the bootstrap process to set up $GLOBALS['BE_USER'] AND $GLOBALS['LANG']
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $pathToRoute = $request->getAttribute('routePath', '/login');

        Bootstrap::initializeBackendUser();
        // @todo: once this logic is in this method, the redirect URL should be handled as response here
        Bootstrap::initializeBackendAuthentication($this->isLoggedInBackendUserRequired($pathToRoute));
        Bootstrap::initializeLanguageObject();
        // Register the backend user as aspect
        $this->setBackendUserAspect(GeneralUtility::makeInstance(Context::class), $GLOBALS['BE_USER']);

        return $handler->handle($request);
    }

    /**
     * Check if the user is required for the request
     * If we're trying to do a login or an ajax login, don't require a user
     *
     * @param string $routePath the Route path to check against, something like '
     * @return bool whether the request can proceed without a login required
     */
    protected function isLoggedInBackendUserRequired(string $routePath): bool
    {
        return in_array($routePath, $this->publicRoutes, true);
    }

    /**
     * Register the backend user as aspect
     *
     * @param Context $context
     * @param BackendUserAuthentication $user
     */
    protected function setBackendUserAspect(Context $context, BackendUserAuthentication $user)
    {
        $context->setAspect('backend.user', GeneralUtility::makeInstance(UserAspect::class, $user));
        $context->setAspect('workspace', GeneralUtility::makeInstance(WorkspaceAspect::class, $user->workspace));
    }
}
