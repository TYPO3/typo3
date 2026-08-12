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
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Attribute\AsAuthenticator;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
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
use TYPO3\CMS\Core\SysLog\Action\Login as SystemLogLoginAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\SysLog\Type as SystemLogType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Authenticates a backend user from an existing user session, without
 * running the legacy authentication service chain. Also processes the
 * logout request parameter the same way the legacy chain did, and
 * challenges sessions that have not passed a required multi-factor
 * authentication yet.
 *
 * @internal not part of TYPO3 Core API yet, still experimental as part of the
 *           authentication pipeline groundwork
 */
#[AsAuthenticator(realm: 'backend', priority: 90)]
final class SessionAuthenticator implements AuthenticatorInterface
{
    public function __construct(
        private readonly LegacyAuthServiceDetector $legacyAuthServiceDetector,
        private readonly LocalIdentityProvider $identityProvider,
        private readonly LegacyUserPrincipalFactory $principalFactory,
        private readonly MfaChallengeResponseFactory $mfaChallengeResponseFactory,
        private readonly LoggerInterface $logger,
    ) {}

    public function authenticate(ServerRequestInterface $request, Realm $realm): AuthenticationResult
    {
        if ($this->legacyAuthServiceDetector->requiresLegacyServiceChain()) {
            return new NotApplicable();
        }
        // A login submission without a session is left to the
        // PasswordAuthenticator, so the user it established while a second
        // factor is pending is not replaced by an anonymous one
        if (!isset($request->getCookieParams()[BackendUserAuthentication::getCookieName()])
            && PasswordAuthenticator::isLoginSubmission($request)
        ) {
            return new NotApplicable();
        }
        $user = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        // The global must be available very early, because parts of the
        // process (e.g. logging) trigger code which relies on it.
        $GLOBALS['BE_USER'] = $user;
        $sessionManager = UserSessionManager::create('BE');
        $session = $sessionManager->createFromRequestOrAnonymous($request, $user->name);

        // Active logout (eg. with "logout" button), processed on any route,
        // but only from the body of a POST request like a login
        $requestedLoginType = $request->getMethod() === 'POST'
            ? LoginType::tryFrom(StringUtility::filter($request->getParsedBody()['login_status'] ?? null, ''))
            : null;
        if ($requestedLoginType === LoginType::LOGOUT) {
            $userRecord = $session->getUserId() > 0 ? $this->identityProvider->findBackendUserByUid((int)$session->getUserId()) : null;
            $username = (string)($userRecord['username'] ?? '');
            // The log entry and the logout events refer to the user of the session
            $user->initializeFromPipeline($session, $userRecord);
            if ($user->writeStdLog) {
                $user->writelog(SystemLogType::LOGIN, SystemLogLoginAction::LOGOUT, SystemLogErrorClassification::MESSAGE, null, 'User \'%s\' logged out', [$username], '', 0);
            }
            $this->logger->info('User {username} logged out. Id: {session}', [
                'username' => $username,
                'session' => sha1($session->getIdentifier()),
            ]);
            $user->logoff();
            return new NotApplicable();
        }

        if ($session->isAnonymous()) {
            $user->initializeFromPipeline($session, null);
            return new NotApplicable();
        }
        $userRecord = $this->identityProvider->findBackendUserByUid((int)$session->getUserId());
        if ($userRecord === null) {
            $user->initializeFromPipeline($session, null);
            return new NotApplicable();
        }
        if ($sessionManager->hasExpired($session)) {
            // Destroy the expired session, effectively logging out the user
            $user->initializeFromPipeline($session, null);
            $user->logoff();
            return new NotApplicable();
        }
        // Refresh the session timestamp unless explicitly skipped
        $skipSessionUpdate = (bool)($request->getQueryParams()['skipSessionUpdate'] ?? false);
        if (!$skipSessionUpdate) {
            $session = $sessionManager->updateSessionTimestamp($session);
        }
        $userRecord['is_online'] = $session->getLastUpdated();
        $user->initializeFromPipeline($session, $userRecord);

        $mfaChallenge = $this->mfaChallengeResponseFactory->createChallengeIfRequired($user, $request);
        if ($mfaChallenge !== null) {
            return $mfaChallenge;
        }
        if ($this->mfaChallengeResponseFactory->isMfaPending($user)) {
            // The MFA route and anonymous routes are processed with the
            // partially authenticated user, but without a principal.
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
}
