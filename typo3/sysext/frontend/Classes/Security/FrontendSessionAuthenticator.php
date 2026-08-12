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

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Attribute\AsAuthenticator;
use TYPO3\CMS\Core\Authentication\LocalIdentityProvider;
use TYPO3\CMS\Core\Authentication\LoginType;
use TYPO3\CMS\Core\Authentication\Pipeline\Authenticated;
use TYPO3\CMS\Core\Authentication\Pipeline\AuthenticationResult;
use TYPO3\CMS\Core\Authentication\Pipeline\AuthenticatorInterface;
use TYPO3\CMS\Core\Authentication\Pipeline\LegacyAuthServiceDetector;
use TYPO3\CMS\Core\Authentication\Pipeline\LegacyUserPrincipalFactory;
use TYPO3\CMS\Core\Authentication\Pipeline\NotApplicable;
use TYPO3\CMS\Core\Authentication\Pipeline\Realm;
use TYPO3\CMS\Core\Session\UserSessionManager;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * Authenticates a frontend user from an existing user session, without
 * running the legacy authentication service chain. Also processes the
 * logout request parameter ("logintype=logout") the same way the legacy
 * chain did. Group data is resolved in any case, as frontend groups can
 * apply without a user (e.g. simulation).
 *
 * @internal not part of TYPO3 Core API yet, still experimental as part of the
 *           authentication pipeline groundwork
 */
#[AsAuthenticator(realm: 'frontend', priority: 90)]
final class FrontendSessionAuthenticator implements AuthenticatorInterface
{
    public function __construct(
        private readonly LegacyAuthServiceDetector $legacyAuthServiceDetector,
        private readonly LocalIdentityProvider $identityProvider,
        private readonly LegacyUserPrincipalFactory $principalFactory,
        private readonly LoggerInterface $logger,
    ) {}

    public function authenticate(ServerRequestInterface $request, Realm $realm): AuthenticationResult
    {
        $user = $request->getAttribute('frontend.user');
        if (!$user instanceof FrontendUserAuthentication || $this->legacyAuthServiceDetector->requiresLegacyServiceChain()) {
            return new NotApplicable();
        }
        $requestedLoginType = LoginType::tryFrom(
            StringUtility::filter($request->getParsedBody()['logintype'] ?? $request->getQueryParams()['logintype'] ?? null, '')
        );
        // As in the legacy chain: the storage page restriction only applies to
        // a login, the permanent login decision is evaluated on every request
        if ($requestedLoginType !== LoginType::LOGIN) {
            $user->checkPid_value = null;
        }
        $user->evaluatePermanentLogin($request);
        $sessionManager = UserSessionManager::create('FE');
        $session = $sessionManager->createFromRequestOrAnonymous($request, $user->name);

        // Active logout (eg. with "logout" button), processed on any page
        if ($requestedLoginType === LoginType::LOGOUT) {
            $userRecord = $session->getUserId() > 0 ? $this->identityProvider->findFrontendUserByUid((int)$session->getUserId()) : null;
            $this->logger->info('User {username} logged out. Id: {session}', [
                'username' => (string)($userRecord['username'] ?? ''),
                'session' => sha1($session->getIdentifier()),
            ]);
            $user->initializeFromPipeline($session, null);
            $user->logoff();
            $user->fetchGroupData($request);
            return new NotApplicable();
        }

        if ($session->isAnonymous()) {
            $user->initializeFromPipeline($session, null);
            $user->fetchGroupData($request);
            return new NotApplicable();
        }
        $userRecord = $this->identityProvider->findFrontendUserByUid((int)$session->getUserId());
        if ($userRecord === null) {
            $user->initializeFromPipeline($session, null);
            $user->fetchGroupData($request);
            return new NotApplicable();
        }
        if ($sessionManager->hasExpired($session)) {
            // Destroy the expired session, effectively logging out the user
            $user->initializeFromPipeline($session, null);
            $user->logoff();
            $user->fetchGroupData($request);
            return new NotApplicable();
        }
        // The online timestamp is taken before the session is refreshed, so the
        // user record is updated periodically, as in the legacy chain
        $userRecord['is_online'] = $session->getLastUpdated();
        // Refresh the session timestamp unless explicitly skipped
        $skipSessionUpdate = (bool)($request->getQueryParams()['skipSessionUpdate'] ?? false);
        if (!$skipSessionUpdate) {
            $session = $sessionManager->updateSessionTimestamp($session);
        }
        $user->initializeFromPipeline($session, $userRecord);
        $user->assertMfaRequirementsMet();
        $user->fetchGroupData($request);

        if (!$user->createUserAspect()->isLoggedIn()) {
            // A frontend user without any resolved groups does not count as logged in
            return new NotApplicable();
        }
        $principal = $this->principalFactory->fromUser($user, $realm);
        if ($principal === null) {
            return new NotApplicable();
        }
        return new Authenticated($principal);
    }
}
