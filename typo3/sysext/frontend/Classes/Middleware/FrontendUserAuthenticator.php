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

namespace TYPO3\CMS\Frontend\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use TYPO3\CMS\Core\Authentication\Pipeline\Authenticated;
use TYPO3\CMS\Core\Authentication\Pipeline\AuthenticatorManager;
use TYPO3\CMS\Core\Authentication\Pipeline\Challenge;
use TYPO3\CMS\Core\Authentication\Pipeline\Realm;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\SetCookieService;
use TYPO3\CMS\Core\Security\Authorization\PrincipalToken;
use TYPO3\CMS\Core\Session\UserSessionManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * Authenticates the request against the frontend realm of the authentication
 * pipeline, stores the frontend user object in the current request as
 * attribute, and provides the established principal as "frontend.principal"
 * request attribute and as token of the authorization checker while the
 * request is handled. A backend user of the frontend request is checked
 * with isGrantedForUser().
 */
readonly class FrontendUserAuthenticator implements MiddlewareInterface
{
    public function __construct(
        protected Context $context,
        protected AuthenticatorManager $authenticatorManager,
        protected TokenStorageInterface $tokenStorage,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $frontendUser = GeneralUtility::makeInstance(FrontendUserAuthentication::class);
        $request = $request->withAttribute('frontend.user', $frontendUser);

        $realm = Realm::frontend($request->getAttribute('site'));
        $result = $this->authenticatorManager->authenticate($request, $realm);
        if ($result instanceof Challenge) {
            // An interactive authentication step is required - the challenge
            // response initiates it.
            return $result->response;
        }

        // Register the frontend user as aspect and the principal within the request
        $this->context->setAspect('frontend.user', $frontendUser->createUserAspect());
        $principal = $result instanceof Authenticated ? $result->principal : null;
        if ($principal !== null) {
            $request = $request->withAttribute('frontend.principal', $principal);
        }

        // The authorization checker decides for the principal of this request,
        // the token of an enclosing request is restored afterwards
        $previousToken = $this->tokenStorage->getToken();
        $this->tokenStorage->setToken($principal !== null ? new PrincipalToken($principal) : null);
        try {
            $response = $handler->handle($request);
        } finally {
            $this->tokenStorage->setToken($previousToken);
        }

        // Store session data for fe_users
        $frontendUser->storeSessionData();
        $response = SetCookieService::create($frontendUser->name, $frontendUser->loginType)->applyCookieToResponse(
            $response,
            $frontendUser->getSession(),
            $frontendUser->getCookieBehavior(),
            $request->getAttribute('normalizedParams')
        );
        // Collect garbage in Frontend requests, which aren't fully cacheable (e.g. with cookies)
        if ($response->hasHeader('Set-Cookie')) {
            $this->sessionGarbageCollection();
        }

        return $response;
    }

    /**
     * Garbage collection for fe_sessions (with a probability)
     */
    protected function sessionGarbageCollection(): void
    {
        UserSessionManager::create('FE')->collectGarbage();
    }
}
