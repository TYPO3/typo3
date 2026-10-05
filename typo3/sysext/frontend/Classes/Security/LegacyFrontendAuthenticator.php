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

namespace TYPO3\CMS\Frontend\Security;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Attribute\AsAuthenticator;
use TYPO3\CMS\Core\Authentication\Event\AfterUserLoggedInEvent;
use TYPO3\CMS\Core\Authentication\Pipeline\Authenticated;
use TYPO3\CMS\Core\Authentication\Pipeline\AuthenticationResult;
use TYPO3\CMS\Core\Authentication\Pipeline\AuthenticatorInterface;
use TYPO3\CMS\Core\Authentication\Pipeline\LegacyUserPrincipalFactory;
use TYPO3\CMS\Core\Authentication\Pipeline\LoginRateLimiterGuard;
use TYPO3\CMS\Core\Authentication\Pipeline\NotApplicable;
use TYPO3\CMS\Core\Authentication\Pipeline\Realm;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * Performs frontend authentication through the legacy user authentication
 * object and its T3_SERVICES chain, exposed as a pipeline authenticator.
 * The frontend user object to operate on is taken from the "frontend.user"
 * request attribute prepared by the FrontendUserAuthenticator middleware.
 *
 * @internal not part of TYPO3 Core API, only exists for the migration period
 *           towards native pipeline authenticators
 */
#[AsAuthenticator(realm: 'frontend')]
final class LegacyFrontendAuthenticator implements AuthenticatorInterface
{
    public function __construct(
        private readonly LoginRateLimiterGuard $rateLimiterGuard,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LegacyUserPrincipalFactory $principalFactory,
    ) {}

    public function authenticate(ServerRequestInterface $request, Realm $realm): AuthenticationResult
    {
        $frontendUser = $request->getAttribute('frontend.user');
        if (!$frontendUser instanceof FrontendUserAuthentication) {
            return new NotApplicable();
        }
        $rateLimiter = $this->rateLimiterGuard->ensureLoginRateLimit($frontendUser, $request);
        $frontendUser->start($request);
        // no matter if we have an active user we try to fetch matching groups which can
        // be set without an user (simulation for instance!)
        $frontendUser->fetchGroupData($request);

        if (!$frontendUser->createUserAspect()->isLoggedIn()) {
            return new NotApplicable();
        }
        if ($rateLimiter) {
            // Reset the limiter after successful login and announce the active login
            $rateLimiter->reset();
            $this->eventDispatcher->dispatch(new AfterUserLoggedInEvent($frontendUser, $request));
        }
        $principal = $this->principalFactory->fromUser($frontendUser, $realm);
        if ($principal === null) {
            return new NotApplicable();
        }
        return new Authenticated($principal);
    }
}
