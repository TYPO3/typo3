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
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Attribute\AsAuthenticator;
use TYPO3\CMS\Core\Authentication\Event\AfterUserLoggedInEvent;
use TYPO3\CMS\Core\Authentication\Event\LoginAttemptFailedEvent;
use TYPO3\CMS\Core\Authentication\LocalIdentityProvider;
use TYPO3\CMS\Core\Authentication\LoginType;
use TYPO3\CMS\Core\Authentication\Pipeline\Authenticated;
use TYPO3\CMS\Core\Authentication\Pipeline\AuthenticationResult;
use TYPO3\CMS\Core\Authentication\Pipeline\AuthenticatorInterface;
use TYPO3\CMS\Core\Authentication\Pipeline\Failure;
use TYPO3\CMS\Core\Authentication\Pipeline\LegacyAuthServiceDetector;
use TYPO3\CMS\Core\Authentication\Pipeline\LegacyUserPrincipalFactory;
use TYPO3\CMS\Core\Authentication\Pipeline\LoginRateLimiterGuard;
use TYPO3\CMS\Core\Authentication\Pipeline\NotApplicable;
use TYPO3\CMS\Core\Authentication\Pipeline\Realm;
use TYPO3\CMS\Core\Security\PasswordCredentialVerifier;
use TYPO3\CMS\Core\Security\PasswordVerification;
use TYPO3\CMS\Core\Session\UserSession;
use TYPO3\CMS\Core\Session\UserSessionManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * Authenticates a frontend user by username and password submitted with a
 * "logintype=login" request (e.g. the felogin plugin form), without running
 * the legacy authentication service chain. The storage page restriction is
 * taken from the signed request token, as provided by the felogin form via
 * the BeforeRequestTokenProcessedEvent listener.
 *
 * @internal not part of TYPO3 Core API yet, still experimental as part of the
 *           authentication pipeline groundwork
 */
#[AsAuthenticator(realm: 'frontend', priority: 100)]
final class FrontendPasswordAuthenticator implements AuthenticatorInterface
{
    public function __construct(
        private readonly LegacyAuthServiceDetector $legacyAuthServiceDetector,
        private readonly LocalIdentityProvider $identityProvider,
        private readonly LegacyUserPrincipalFactory $principalFactory,
        private readonly LoginRateLimiterGuard $rateLimiterGuard,
        private readonly PasswordCredentialVerifier $credentialVerifier,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger,
    ) {}

    public function authenticate(ServerRequestInterface $request, Realm $realm): AuthenticationResult
    {
        $user = $request->getAttribute('frontend.user');
        // Credentials are only accepted from the body of a POST request
        if (!$user instanceof FrontendUserAuthentication
            || $this->legacyAuthServiceDetector->requiresLegacyServiceChain()
            || $request->getMethod() !== 'POST'
            || LoginType::tryFrom(StringUtility::filter($request->getParsedBody()['logintype'] ?? null, '')) !== LoginType::LOGIN
        ) {
            return new NotApplicable();
        }
        $sessionManager = UserSessionManager::create('FE');
        $session = $sessionManager->createFromRequestOrAnonymous($request, $user->name);
        if (!$session->isAnonymous() && $sessionManager->hasExpired($session)) {
            // An expired session is destroyed and the login proceeds with a
            // new anonymous session, as in the legacy chain
            $user->initializeFromPipeline($session, null);
            $user->logoff();
            $session = $sessionManager->createAnonymousSession();
        }
        if (!$session->isAnonymous()) {
            // An existing user session always wins over submitted credentials
            return new NotApplicable();
        }
        // Listeners of the events dispatched below may access the session
        $user->initializeFromPipeline($session, null);
        $rateLimiter = $this->rateLimiterGuard->ensureLoginRateLimit($user, $request);
        // The "stay logged in" decision influences the session and cookie type
        $user->evaluatePermanentLogin($request);

        if (!$this->credentialVerifier->requestTokenMatches($user, $request)) {
            $this->logger->warning('Missing or invalid request token during login from {ip}', [
                'ip' => $request->getAttribute('normalizedParams')->getRemoteAddress(),
            ]);
            // Announce the failed login attempt, since the credentials are never
            // evaluated when the request token is missing or invalid
            $this->eventDispatcher->dispatch(
                new LoginAttemptFailedEvent($user, $request, $this->sanitizedLoginData($user, $request))
            );
            return new NotApplicable();
        }
        // Submitted credentials are trimmed, as the stored values are
        $username = $this->getSubmittedUsername($request);
        $password = trim(StringUtility::filter($request->getParsedBody()['pass'] ?? null, ''));
        if ($username === '' || $password === '') {
            $this->logger->warning('Login-attempt from {ip}, for username "{username}" with an empty password!', [
                'ip' => $request->getAttribute('normalizedParams')->getRemoteAddress(),
                'username' => $username,
            ]);
            return $this->createFailure($user, $session, $request, 'empty-credentials');
        }
        $userRecord = $this->identityProvider->findFrontendUserByUsername($username, $this->resolveStoragePageIds($user));
        if ($userRecord === null) {
            $this->logger->warning('Login-attempt from {ip}, username "{username}" not found!', [
                'ip' => $request->getAttribute('normalizedParams')->getRemoteAddress(),
                'username' => $username,
            ]);
            // Mimic password hashing to mitigate observable timing discrepancies
            // @link https://cwe.mitre.org/data/definitions/208.html
            $this->credentialVerifier->mimicPasswordHashing($user);
            return $this->createFailure($user, $session, $request, 'unknown-username');
        }
        $verification = $this->credentialVerifier->verify($user, (string)($userRecord['password'] ?? ''), $password);
        if ($verification === PasswordVerification::NoSuitableHashMethod) {
            $this->logger->warning('Login-attempt from {ip}, username "{username}", no suitable hash method found!', [
                'ip' => $request->getAttribute('normalizedParams')->getRemoteAddress(),
                'username' => $username,
            ]);
            return $this->createFailure($user, $session, $request, 'no-suitable-hash-method');
        }
        if ($verification === PasswordVerification::Invalid) {
            $this->logger->warning('Login-attempt from {ip}, username "{username}", password not accepted!', [
                'ip' => $request->getAttribute('normalizedParams')->getRemoteAddress(),
                'username' => $username,
            ]);
            return $this->createFailure($user, $session, $request, 'invalid-credentials');
        }
        if ($verification === PasswordVerification::VerifiedWithOutdatedHash) {
            $this->identityProvider->updateFrontendUserPasswordHash((int)$userRecord['uid'], $this->credentialVerifier->hashPassword($user, $password));
            $this->logger->notice('Automatic password update for frontend user', ['userId' => (int)$userRecord['uid']]);
        }

        // Elevate the session to the authenticated user, preserving collected session data
        $sessionData = $session->getData();
        $session = $sessionManager->elevateToFixatedUserSession($session, (int)$userRecord['uid'], $user->isPermanentLogin());
        if ($sessionData !== []) {
            $session->overrideData($sessionData);
        }
        $this->identityProvider->updateFrontendUserLastLogin((int)$userRecord['uid'], $GLOBALS['EXEC_TIME']);
        $userRecord['lastlogin'] = $GLOBALS['EXEC_TIME'];
        $user->initializeFromPipeline($session, $userRecord, true);
        $user->assertMfaRequirementsMet();
        $user->fetchGroupData($request);

        $this->logger->info('User {username} logged in from {ip}', [
            'username' => $username,
            'ip' => $request->getAttribute('normalizedParams')->getRemoteAddress(),
        ]);

        if (!$user->createUserAspect()->isLoggedIn()) {
            // A frontend user without any resolved groups does not count as logged in
            return new NotApplicable();
        }
        // Reset the limiter after successful login and announce the active login
        $rateLimiter?->reset();
        $this->eventDispatcher->dispatch(new AfterUserLoggedInEvent($user, $request));

        $principal = $this->principalFactory->fromUser($user, $realm);
        if ($principal === null) {
            return new NotApplicable();
        }
        return new Authenticated($principal);
    }

    /**
     * The storage page restriction as derived from the signed request token
     * (see PasswordCredentialVerifier::requestTokenMatches()) and the
     * checkFeUserPid configuration.
     */
    private function resolveStoragePageIds(FrontendUserAuthentication $user): ?array
    {
        if ($user->checkPid && $user->checkPid_value !== null && $user->checkPid_value !== '') {
            return GeneralUtility::intExplode(',', (string)$user->checkPid_value, true);
        }
        return null;
    }

    private function createFailure(
        FrontendUserAuthentication $user,
        UserSession $session,
        ServerRequestInterface $request,
        string $reason
    ): Failure {
        $user->initializeFromPipeline($session, null);
        $user->fetchGroupData($request);
        $this->eventDispatcher->dispatch(
            new LoginAttemptFailedEvent($user, $request, $this->sanitizedLoginData($user, $request))
        );
        return new Failure($reason);
    }

    private function sanitizedLoginData(FrontendUserAuthentication $user, ServerRequestInterface $request): array
    {
        return [
            'status' => LoginType::LOGIN->value,
            'uname' => $this->getSubmittedUsername($request),
            'uident' => '********',
            'uident_text' => '********',
            'permanent' => $user->isPermanentLogin(),
        ];
    }

    private function getSubmittedUsername(ServerRequestInterface $request): string
    {
        return trim(StringUtility::filter($request->getParsedBody()['user'] ?? null, ''));
    }
}
