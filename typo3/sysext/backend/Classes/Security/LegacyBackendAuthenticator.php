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

namespace TYPO3\CMS\Backend\Security;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Attribute\AsAuthenticator;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\Mfa\MfaRequiredException;
use TYPO3\CMS\Core\Authentication\Pipeline\Authenticated;
use TYPO3\CMS\Core\Authentication\Pipeline\AuthenticationResult;
use TYPO3\CMS\Core\Authentication\Pipeline\AuthenticatorInterface;
use TYPO3\CMS\Core\Authentication\Pipeline\Challenge;
use TYPO3\CMS\Core\Authentication\Pipeline\LegacyUserPrincipalFactory;
use TYPO3\CMS\Core\Authentication\Pipeline\LoginRateLimiterGuard;
use TYPO3\CMS\Core\Authentication\Pipeline\NotApplicable;
use TYPO3\CMS\Core\Authentication\Pipeline\Realm;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Performs backend authentication through the legacy user authentication
 * object and its T3_SERVICES chain, exposed as a pipeline authenticator.
 * A pending multi-factor requirement is translated into a Challenge
 * outcome, an established user is handed over as "legacyUser" attribute
 * of the Authenticated outcome.
 *
 * @internal not part of TYPO3 Core API, only exists for the migration period
 *           towards native pipeline authenticators
 */
#[AsAuthenticator(realm: 'backend')]
final class LegacyBackendAuthenticator implements AuthenticatorInterface
{
    public function __construct(
        private readonly LoginRateLimiterGuard $rateLimiterGuard,
        private readonly MfaChallengeResponseFactory $mfaChallengeResponseFactory,
        private readonly LegacyUserPrincipalFactory $principalFactory,
    ) {}

    public function authenticate(ServerRequestInterface $request, Realm $realm): AuthenticationResult
    {
        /** @var Route|null $route */
        $route = $request->getAttribute('route');
        $isAjaxCall = (bool)($route?->getOption('ajax') ?? false);
        $mfaRequested = $route?->getOption('_identifier') === 'auth_mfa';

        $user = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        // The global must be available very early, because the authentication
        // process triggers code which relies on it. See: #45625
        $GLOBALS['BE_USER'] = $user;
        $rateLimiter = $this->rateLimiterGuard->ensureLoginRateLimit($user, $request);
        try {
            $user->start($request);
        } catch (MfaRequiredException $mfaRequiredException) {
            // If MFA is required and we are not already on the "auth_mfa"
            // route, force the user to it for further authentication.
            if (!$mfaRequested && ($route === null || $route->getAccess()->requiresAuthentication())) {
                if ($isAjaxCall) {
                    return new Challenge(new Response(statusCode: 401));
                }
                return new Challenge(
                    $this->mfaChallengeResponseFactory->createRedirect(
                        'auth_mfa',
                        $user,
                        $request,
                        ['identifier' => $mfaRequiredException->getProvider()->getIdentifier()]
                    )
                );
            }
            // The second factor is still pending: no principal is established.
            // The legacy user object stays available in $GLOBALS['BE_USER'] for
            // the MFA and anonymous routes. The password was correct, so the
            // rate limiter is reset as before.
            if ($user->getUserId() !== null) {
                $rateLimiter?->reset();
            }
            return new NotApplicable();
        }
        if ($user->getUserId() === null) {
            return new NotApplicable();
        }
        // The principal carries the resolved groups of the user
        $user->fetchGroupData();
        $principal = $this->principalFactory->fromUser($user, $realm);
        if ($principal === null) {
            return new NotApplicable();
        }
        // Reset the limiter after successful login
        $rateLimiter?->reset();
        return new Authenticated($principal, ['legacyUser' => $user]);
    }
}
