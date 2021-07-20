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

namespace TYPO3\CMS\Core\Session;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Authentication\IpLocker;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Session\Backend\Exception\SessionNotFoundException;
use TYPO3\CMS\Core\Session\Backend\SessionBackendInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The purpose of the UserSessionManager is to create new user session objects (acting as a factory),
 * depending on the need / request, and to fetch sessions from the Session Backend, effectively
 * encapsulating all calls to the SessionManager
 */
class UserSessionManager implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const SESSION_ID_LENGTH = 32;
    protected const GARBAGE_COLLECTION_LIFETIME = 86400;
    protected const LIFETIME_OF_ANONYMOUS_SESSION_DATA = 86400;

    /**
     * Session timeout (on the storage-side, used to know until a session (timestamp) is valid
     *
     * If >0: session-timeout in seconds.
     * If =0: Instant logout after login.
     *
     * @var int
     */
    protected int $sessionLifetime;

    protected int $garbageCollectionForAnonymousSessions = self::LIFETIME_OF_ANONYMOUS_SESSION_DATA;
    protected SessionBackendInterface $sessionBackend;
    protected IpLocker $ipLocker;

    /**
     * Constructor. Marked as internal, as it is recommended to use the factory method "create"
     *
     * @param SessionBackendInterface $sessionBackend
     * @param int $sessionLifetime
     * @param IpLocker $ipLocker
     * @internal
     */
    public function __construct(SessionBackendInterface $sessionBackend, int $sessionLifetime, IpLocker $ipLocker)
    {
        $this->sessionBackend = $sessionBackend;
        $this->sessionLifetime = $sessionLifetime;
        $this->ipLocker = $ipLocker;
    }

    protected function setGarbageCollectionTimeoutForAnonymousSessions(int $garbageCollectionForAnonymousSessions = 0): void
    {
        if ($garbageCollectionForAnonymousSessions > 0) {
            $this->garbageCollectionForAnonymousSessions = $garbageCollectionForAnonymousSessions;
        }
    }

    /**
     * sessionId is set to ses_id if a cookie is present in the request.
     * Otherwise a new anonymous session will start.
     *
     * @param ServerRequestInterface $request
     * @param string $cookieName
     * @return UserSession
     */
    public function createFromRequestOrAnonymous(ServerRequestInterface $request, string $cookieName): UserSession
    {
        $sessionId = (string)($request->getCookieParams()[$cookieName] ?? '');
        return $this->getSessionFromSessionId($sessionId) ?? $this->createAnonymousSession();
    }

    /**
     * sessionId is set to ses_id if a cookie is present in $_COOKIE.
     * Otherwise a new anonymous session will start.
     *
     * @param string $cookieName
     * @return UserSession
     */
    public function createFromGlobalCookieOrAnonymous(string $cookieName): UserSession
    {
        $sessionId = isset($_COOKIE[$cookieName]) ? stripslashes((string)$_COOKIE[$cookieName]) : '';
        return $this->getSessionFromSessionId($sessionId) ?? $this->createAnonymousSession();
    }

    /**
     * Creates a non-fixated session without a user logged in
     *
     * @return UserSession
     */
    public function createAnonymousSession(): UserSession
    {
        $randomSessionId = $this->createSessionId();
        return UserSession::createNonFixated($randomSessionId);
    }

    /**
     * Creates a new user session object from an existing session data.
     *
     * @param string $sessionId The session id to be looked up in the session backend
     * @return UserSession The created user session object
     * @internal this is only used as a bridge for existing methods, might be removed or renamed without further notice
     */
    public function createSessionFromStorage(string $sessionId): UserSession
    {
        $this->logger->debug('Fetch session with identifier {session}', ['session' => sha1($sessionId)]);
        $sessionRecord = $this->sessionBackend->get($sessionId);
        return UserSession::createFromRecord($sessionId, $sessionRecord);
    }

    /**
     * Check if a session has expired. This is the case if sessionLifetime is 0,
     * or current time greater than sessionLifetime plus last update time of the session.
     *
     * @param UserSession $session
     * @return bool
     */
    public function hasExpired(UserSession $session): bool
    {
        return $this->sessionLifetime === 0 || $GLOBALS['EXEC_TIME'] > $session->getLastUpdated() + $this->sessionLifetime;
    }

    /**
     * Check if a session will expire within the given grace period.
     *
     * @param UserSession $session
     * @param int $gracePeriod
     * @return bool
     */
    public function willExpire(UserSession $session, int $gracePeriod): bool
    {
        return $GLOBALS['EXEC_TIME'] >= ($session->getLastUpdated() + $this->sessionLifetime) - $gracePeriod;
    }

    /**
     * Persists an anonymous session without a user logged in,
     * in order to store session data between requests.
     *
     * @param UserSession $session The user session to fixate
     * @param bool $isPermanent If TRUE, the session will get the is_permanent flag
     * @return UserSession a new session object with an updated ses_tstamp (allowing to keep the session alive)
     *
     * @throws Backend\Exception\SessionNotCreatedException
     */
    public function fixateAnonymousSession(UserSession $session, bool $isPermanent = false): UserSession
    {
        $sessionIpLock = $this->ipLocker->getSessionIpLock((string)GeneralUtility::getIndpEnv('REMOTE_ADDR'));
        $sessionRecord = $session->toArray();
        $sessionRecord['ses_iplock'] = $sessionIpLock;
        // Ensure the user is not set, as this is always an anonymous session (see elevateToFixatedUserSession)
        $sessionRecord['ses_userid'] = 0;
        if ($isPermanent) {
            $sessionRecord['ses_permanent'] = 1;
        }
        // The updated session record now also contains an updated timestamp (ses_tstamp)
        $updatedSessionRecord = $this->sessionBackend->set($session->getIdentifier(), $sessionRecord);
        return $this->recreateUserSession($session, $updatedSessionRecord);
    }

    /**
     * Removes existing entries, creates and returns a new user session record
     *
     * @param UserSession $session The user session to recreate
     * @param int $userId The user id the session belongs to
     * @param bool $isPermanent If TRUE, the session will get the is_permanent flag
     * @return UserSession The newly created user session object
     *
     * @throws Backend\Exception\SessionNotCreatedException
     */
    public function elevateToFixatedUserSession(UserSession $session, int $userId, bool $isPermanent = false): UserSession
    {
        $sessionId = $session->getIdentifier();
        $this->logger->debug('Create session ses_id = {session}', ['session' => sha1($sessionId)]);
        // Delete any session entry first
        $this->sessionBackend->remove($sessionId);
        // Re-create session entry
        $sessionIpLock = $this->ipLocker->getSessionIpLock((string)GeneralUtility::getIndpEnv('REMOTE_ADDR'));
        $sessionRecord = [
            'ses_iplock' => $sessionIpLock,
            'ses_userid' => $userId,
            'ses_tstamp' => $GLOBALS['EXEC_TIME'],
            'ses_data' => '',
        ];
        if ($isPermanent) {
            $sessionRecord['ses_permanent'] = 1;
        }
        $sessionRecord = $this->sessionBackend->set($sessionId, $sessionRecord);
        return UserSession::createFromRecord($sessionId, $sessionRecord, true);
    }

    /**
     * Regenerate the session ID and transfer the session to new ID
     * Call this method whenever a user proceeds to a higher authorization level
     * e.g. when an anonymous session is now authenticated.
     *
     * @param string $sessionId The session id
     * @param array $existingSessionRecord If given, this session record will be used instead of fetching again
     * @param bool $anonymous If true session will be regenerated as anonymous session
     * @return UserSession
     *
     * @throws Backend\Exception\SessionNotCreatedException
     * @throws SessionNotFoundException
     */
    public function regenerateSession(
        string $sessionId,
        array $existingSessionRecord = [],
        bool $anonymous = false
    ): UserSession {
        if (empty($existingSessionRecord)) {
            $existingSessionRecord = $this->sessionBackend->get($sessionId);
        }
        if ($anonymous) {
            $existingSessionRecord['ses_userid'] = 0;
        }
        // Update session record with new ID
        $newSessionId = $this->createSessionId();
        $this->sessionBackend->set($newSessionId, $existingSessionRecord);
        $this->sessionBackend->remove($sessionId);
        return UserSession::createFromRecord($newSessionId, $existingSessionRecord, true);
    }

    /**
     * Updates the session timestamp for the given user session if
     * the session is marked as "needs update" (which means the current
     * timestamp is greater than "last updated + a specified gracetime-value".
     *
     * @param UserSession $session
     * @return UserSession a modified user session with a last updated value if needed
     * @throws Backend\Exception\SessionNotUpdatedException
     */
    public function updateSessionTimestamp(UserSession $session): UserSession
    {
        if ($session->needsUpdate()) {
            // Update the session timestamp by writing a dummy update. (Backend will update the timestamp)
            $this->sessionBackend->update($session->getIdentifier(), []);
            $session = $this->recreateUserSession($session);
        }
        return $session;
    }

    public function isSessionPersisted(UserSession $session): bool
    {
        return $this->getSessionFromSessionId($session->getIdentifier()) !== null;
    }

    public function removeSession(UserSession $session): void
    {
        $this->sessionBackend->remove($session->getIdentifier());
    }

    public function updateSession(UserSession $session): UserSession
    {
        $sessionRecord = $this->sessionBackend->update($session->getIdentifier(), $session->toArray());
        return $this->recreateUserSession($session, $sessionRecord);
    }

    public function collectGarbage(int $garbageCollectionProbability = 1): void
    {
        // If we're lucky we'll get to clean up old sessions
        if (random_int(0, mt_getrandmax()) % 100 <= $garbageCollectionProbability) {
            $this->sessionBackend->collectGarbage(
                $this->sessionLifetime > 0 ? $this->sessionLifetime : self::GARBAGE_COLLECTION_LIFETIME,
                $this->garbageCollectionForAnonymousSessions
            );
        }
    }

    /**
     * Creates a new session ID using a random with SESSION_ID_LENGTH as length
     *
     * @return string
     */
    protected function createSessionId(): string
    {
        return GeneralUtility::makeInstance(Random::class)->generateRandomHexString(self::SESSION_ID_LENGTH);
    }

    /**
     * Tries to fetch a user session form the session backend.
     * If non is given, an anonymous session will be created.
     *
     * @param string $id
     * @return UserSession|null The created user session object or null
     */
    protected function getSessionFromSessionId(string $id): ?UserSession
    {
        if ($id === '') {
            return null;
        }
        try {
            $sessionRecord = $this->sessionBackend->get($id);
            if ($sessionRecord === []) {
                return null;
            }
            // If the session does not match the current IP lock, it should be treated as invalid
            // and a new session should be created.
            if ($this->ipLocker->validateRemoteAddressAgainstSessionIpLock(
                (string)GeneralUtility::getIndpEnv('REMOTE_ADDR'),
                $sessionRecord['ses_iplock']
            )) {
                return UserSession::createFromRecord($id, $sessionRecord);
            }
        } catch (SessionNotFoundException $e) {
            return null;
        }

        return null;
    }

    /**
     * Create a UserSessionManager instance for the given login type. Has several optional arguments used for testing purposes
     * to inject dummy objects if needed.
     *
     * Ideally, this factory encapsulates all "TYPO3_CONF_VARS" options, so the actual object does not need to consider any
     * global state.
     *
     * @param string $loginType
     * @param int|null $sessionLifetime
     * @param SessionManager|null $sessionManager
     * @param IpLocker|null $ipLocker
     * @return static
     */
    public static function create(string $loginType, int $sessionLifetime = null, SessionManager $sessionManager = null, IpLocker $ipLocker = null): self
    {
        $sessionManager = $sessionManager ?? GeneralUtility::makeInstance(SessionManager::class);
        $ipLocker = $ipLocker ?? GeneralUtility::makeInstance(
            IpLocker::class,
            $GLOBALS['TYPO3_CONF_VARS'][$loginType]['lockIP'],
            $GLOBALS['TYPO3_CONF_VARS'][$loginType]['lockIPv6']
        );
        $lifetime = (int)($GLOBALS['TYPO3_CONF_VARS'][$loginType]['lifetime'] ?? 0);
        $sessionLifetime = $sessionLifetime ?? (int)$GLOBALS['TYPO3_CONF_VARS'][$loginType]['sessionTimeout'];
        if ($sessionLifetime > 0 && $sessionLifetime < $lifetime && $lifetime > 0) {
            // If server session timeout is non-zero but less than client session timeout: Copy this value instead.
            $sessionLifetime = $lifetime;
        }
        $object = GeneralUtility::makeInstance(
            self::class,
            $sessionManager->getSessionBackend($loginType),
            $sessionLifetime,
            $ipLocker
        );
        if ($loginType === 'FE') {
            $object->setGarbageCollectionTimeoutForAnonymousSessions((int)($GLOBALS['TYPO3_CONF_VARS']['FE']['sessionDataLifetime'] ?? 0));
        }
        return $object;
    }

    /**
     * Recreates `UserSession` object from existing session data - keeping `new` state.
     * This method shall be used to reflect updated low-level session data in corresponding `UserSession` object.
     *
     * @param UserSession $session
     * @param array|null $sessionRecord
     * @return UserSession
     * @throws SessionNotFoundException
     */
    protected function recreateUserSession(UserSession $session, array $sessionRecord = null): UserSession
    {
        return UserSession::createFromRecord(
            $session->getIdentifier(),
            $sessionRecord ?? $this->sessionBackend->get($session->getIdentifier()),
            $session->isNew() // keep state (required to emit e.g. cookies)
        );
    }
}
