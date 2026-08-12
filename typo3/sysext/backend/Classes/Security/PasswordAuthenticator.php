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

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Attribute\AsAuthenticator;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\Event\BeforeRequestTokenProcessedEvent;
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
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\SecurityAspect;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Security\RequestToken;
use TYPO3\CMS\Core\Session\UserSession;
use TYPO3\CMS\Core\Session\UserSessionManager;
use TYPO3\CMS\Core\SysLog\Action\Login as SystemLogLoginAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\SysLog\Type as SystemLogType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Authenticates a backend user by username and password submitted to the
 * backend login endpoints, without running the legacy authentication
 * service chain. Credential processing is bound to the login routes -
 * other routes never evaluate submitted credentials on this path.
 *
 * @internal not part of TYPO3 Core API yet, still experimental as part of the
 *           authentication pipeline groundwork
 */
#[AsAuthenticator(realm: 'backend', priority: 100)]
final class PasswordAuthenticator implements AuthenticatorInterface
{
    private const LOGIN_ROUTES = ['login', 'ajax_login'];

    public function __construct(
        private readonly LegacyAuthServiceDetector $legacyAuthServiceDetector,
        private readonly LocalIdentityProvider $identityProvider,
        private readonly LegacyUserPrincipalFactory $principalFactory,
        private readonly MfaChallengeResponseFactory $mfaChallengeResponseFactory,
        private readonly LoginRateLimiterGuard $rateLimiterGuard,
        private readonly PasswordHashFactory $passwordHashFactory,
        private readonly ConnectionPool $connectionPool,
        private readonly Context $context,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Whether the request submits credentials on one of the login routes.
     * Credentials are only accepted from the body of a POST request.
     */
    public static function isLoginSubmission(ServerRequestInterface $request): bool
    {
        /** @var Route|null $route */
        $route = $request->getAttribute('route');
        if ($request->getMethod() !== 'POST' || !in_array($route?->getOption('_identifier'), self::LOGIN_ROUTES, true)) {
            return false;
        }
        $requestedLoginType = LoginType::tryFrom(
            StringUtility::filter($request->getParsedBody()['login_status'] ?? null, '')
        );
        return $requestedLoginType === LoginType::LOGIN;
    }

    public function authenticate(ServerRequestInterface $request, Realm $realm): AuthenticationResult
    {
        if ($this->legacyAuthServiceDetector->requiresLegacyServiceChain() || !self::isLoginSubmission($request)) {
            return new NotApplicable();
        }
        $user = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        // The global must be available very early, because parts of the
        // process (e.g. logging) trigger code which relies on it.
        $GLOBALS['BE_USER'] = $user;
        $sessionManager = UserSessionManager::create('BE');
        $session = $sessionManager->createFromRequestOrAnonymous($request, $user->name);
        if (!$session->isAnonymous() && $sessionManager->hasExpired($session)) {
            // An expired session is destroyed and the login proceeds with a
            // new anonymous session, as in the legacy chain
            $user->initializeFromPipeline($session, $this->identityProvider->findBackendUserByUid((int)$session->getUserId()));
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

        if (!$this->requestTokenMatchesScope($user, $request)) {
            $this->logger->warning('Missing or invalid request token during login');
            // Announce the failed login attempt, since the credentials are never
            // evaluated when the request token is missing or invalid
            $this->eventDispatcher->dispatch(
                new LoginAttemptFailedEvent($user, $request, $this->sanitizedLoginData($request))
            );
            return new NotApplicable();
        }

        // Submitted credentials are trimmed, as the stored values are
        $username = $this->getSubmittedUsername($request);
        $password = trim(StringUtility::filter($request->getParsedBody()['userident'] ?? null, ''));
        // Refuse login for _CLI users, if not processing a CLI request type
        if (stripos($username, '_CLI_') === 0) {
            throw new \RuntimeException('TYPO3 Fatal Error: You have tried to login using a CLI user. Access prohibited!', 1791581320);
        }
        if ($password === '') {
            $this->writeAttemptLog($user, 'Login-attempt from ###IP### for username \'%s\' with an empty password!', $username);
            $this->logger->warning('Login-attempt from {ip}, for username "{username}" with an empty password!', [
                'ip' => $request->getAttribute('normalizedParams')->getRemoteAddress(),
                'username' => $username,
            ]);
            return $this->createFailure($user, $session, $request, 'empty-password');
        }
        $userRecord = $this->identityProvider->findBackendUserByUsername($username);
        if ($userRecord === null) {
            $this->writeAttemptLog($user, 'Login-attempt from ###IP###, username \'%s\' not found!', $username);
            $this->logger->warning('Login-attempt from {ip}, username "{username}" not found!', [
                'ip' => $request->getAttribute('normalizedParams')->getRemoteAddress(),
                'username' => $username,
            ]);
            // Mimic password hashing to mitigate observable timing discrepancies
            // @link https://cwe.mitre.org/data/definitions/208.html
            $this->mimicPasswordHashing();
            return $this->createFailure($user, $session, $request, 'unknown-username');
        }
        $passwordHashInDatabase = (string)($userRecord['password'] ?? '');
        try {
            $hashInstance = $this->passwordHashFactory->get($passwordHashInDatabase, 'BE');
        } catch (InvalidPasswordHashException) {
            $this->writeAttemptLog($user, 'Login-attempt from ###IP###, username \'%s\', no suitable hash method found!', $username);
            $this->logger->warning('Login-attempt from {ip}, username "{username}", no suitable hash method found!', [
                'ip' => $request->getAttribute('normalizedParams')->getRemoteAddress(),
                'username' => $username,
            ]);
            return $this->createFailure($user, $session, $request, 'no-suitable-hash-method');
        }
        if (!$hashInstance->checkPassword($password, $passwordHashInDatabase)) {
            $this->writeAttemptLog($user, 'Login-attempt from ###IP###, username \'%s\', password not accepted!', $username);
            $this->logger->warning('Login-attempt from {ip}, username "{username}", password not accepted!', [
                'ip' => $request->getAttribute('normalizedParams')->getRemoteAddress(),
                'username' => $username,
            ]);
            return $this->createFailure($user, $session, $request, 'invalid-credentials');
        }
        // Don't catch InvalidPasswordHashException here: Only install tool should handle those configuration failures
        $defaultHashInstance = $this->passwordHashFactory->getDefaultHashInstance('BE');
        if ($hashInstance->isHashUpdateNeeded($passwordHashInDatabase) || $defaultHashInstance != $hashInstance) {
            // Lax object comparison intended: Rehash if old and new salt objects are not
            // instances of the same class.
            $this->updatePasswordHashInDatabase((int)$userRecord['uid'], $defaultHashInstance->getHashedPassword($password));
        }

        // Elevate the session to the authenticated user, preserving collected session data
        $sessionData = $session->getData();
        $session = $sessionManager->elevateToFixatedUserSession($session, (int)$userRecord['uid']);
        if ($sessionData !== []) {
            $session->overrideData($sessionData);
        }
        $userRecord = $this->updateLoginTimestamp($userRecord);
        $user->initializeFromPipeline($session, $userRecord, true);

        if ($user->writeStdLog) {
            $user->writelog(SystemLogType::LOGIN, SystemLogLoginAction::LOGIN, SystemLogErrorClassification::MESSAGE, null, 'User \'%s\' logged in from ###IP###', [$username], '', '');
        }
        $this->logger->info('User {username} logged in from {ip}', [
            'username' => $username,
            'ip' => $request->getAttribute('normalizedParams')->getRemoteAddress(),
        ]);
        // Reset the limiter after successful login
        $rateLimiter?->reset();

        $mfaChallenge = $this->mfaChallengeResponseFactory->createChallengeIfRequired($user, $request);
        if ($mfaChallenge !== null) {
            return $mfaChallenge;
        }
        if ($this->mfaChallengeResponseFactory->isMfaPending($user)) {
            // The login route is processed with the partially authenticated
            // user, but without a principal.
            return new NotApplicable();
        }
        // The principal carries the resolved groups of the user
        $user->fetchGroupData();
        $principal = $this->principalFactory->fromUser($user, $realm);
        if ($principal === null) {
            return new NotApplicable();
        }
        return new Authenticated($principal, ['legacyUser' => $user]);
    }

    /**
     * Validates the submitted request token (nonce) for the backend user
     * authentication scope, mirroring the legacy chain behavior including
     * the possibility to adjust the token via event.
     */
    private function requestTokenMatchesScope(BackendUserAuthentication $user, ServerRequestInterface $request): bool
    {
        $securityAspect = SecurityAspect::provideIn($this->context);
        $requestToken = $securityAspect->getReceivedRequestToken();

        $event = new BeforeRequestTokenProcessedEvent($user, $request, $requestToken);
        $this->eventDispatcher->dispatch($event);
        $requestToken = $event->getRequestToken();

        if (($requestToken->scope ?? null) !== 'core/user-auth/be') {
            return false;
        }
        if ($requestToken instanceof RequestToken && $requestToken->getSigningSecretIdentifier() !== null) {
            $securityAspect->getSigningSecretResolver()->revokeIdentifier(
                $requestToken->getSigningSecretIdentifier()
            );
        }
        return true;
    }

    private function createFailure(
        BackendUserAuthentication $user,
        UserSession $session,
        ServerRequestInterface $request,
        string $reason
    ): Failure {
        $user->initializeFromPipeline($session, null);
        $this->eventDispatcher->dispatch(
            new LoginAttemptFailedEvent($user, $request, $this->sanitizedLoginData($request))
        );
        return new Failure($reason);
    }

    private function sanitizedLoginData(ServerRequestInterface $request): array
    {
        return [
            'status' => LoginType::LOGIN->value,
            'uname' => $this->getSubmittedUsername($request),
            'uident' => '********',
            'uident_text' => '********',
        ];
    }

    private function writeAttemptLog(BackendUserAuthentication $user, string $details, string $username): void
    {
        if ($user->writeAttemptLog) {
            $user->writelog(SystemLogType::LOGIN, SystemLogLoginAction::ATTEMPT, SystemLogErrorClassification::SECURITY_NOTICE, null, $details, [$username]);
        }
    }

    private function getSubmittedUsername(ServerRequestInterface $request): string
    {
        return trim(StringUtility::filter($request->getParsedBody()['username'] ?? null, ''));
    }

    /**
     * Mimics password hashing for invalid authentication requests to mitigate
     * @link https://cwe.mitre.org/data/definitions/208.html: CWE-208: Observable Timing Discrepancy
     */
    private function mimicPasswordHashing(): void
    {
        try {
            $this->passwordHashFactory->getDefaultHashInstance('BE')->getHashedPassword(random_bytes(10));
        } catch (\Exception) {
            // no further processing here
        }
    }

    private function updatePasswordHashInDatabase(int $uid, string $newPasswordHash): void
    {
        $this->connectionPool->getConnectionForTable('be_users')->update(
            'be_users',
            ['password' => $newPasswordHash],
            ['uid' => $uid]
        );
        $this->logger->notice('Automatic password update for backend user', ['userId' => $uid]);
    }

    private function updateLoginTimestamp(array $userRecord): array
    {
        $this->connectionPool->getConnectionForTable('be_users')->update(
            'be_users',
            ['lastlogin' => $GLOBALS['EXEC_TIME']],
            ['uid' => (int)$userRecord['uid']]
        );
        $userRecord['lastlogin'] = $GLOBALS['EXEC_TIME'];
        return $userRecord;
    }
}
