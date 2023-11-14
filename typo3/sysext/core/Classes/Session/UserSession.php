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

use TYPO3\CMS\Core\Http\CookieScope;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Security\JwtTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Represents all information about a user's session.
 * A user session can be bound to a frontend / backend user, or an anonymous session based on session data stored
 * in the session backend.
 *
 * If a session is anonymous, it can be fixated by storing the session in the backend, but only if there
 * is data in the session.
 *
 * if a session is user-bound, it is automatically fixated.
 *
 * The `$isNew` flag is meant to show that this user session object was not
 * fetched from the session backend, but initialized in the first place by
 * the current request.
 *
 * The `$data` argument stores arbitrary data valid for the user's session.
 *
 * A permanent session is not issued by a session-based cookie but a
 * time-based cookie. The session might be persisted in the user's browser.
 */
class UserSession
{
    use JwtTrait;

    protected const SESSION_UPDATE_GRACE_PERIOD = 61;
    protected string $identifier;
    protected ?int $userId;
    protected int $lastUpdated;
    protected array $data;
    protected bool $wasUpdated = false;
    protected string $ipLock = '';
    protected bool $isNew = true;
    protected bool $isPermanent = false;

    protected function __construct(string $identifier, int $userId, int $lastUpdated, array $data = [])
    {
        $this->identifier = $identifier;
        $this->userId = $userId > 0 ? $userId : null;
        $this->lastUpdated = $lastUpdated;
        $this->data = $data;
    }

    /**
     * @return string the session ID. This is the `ses_id` respectively the `AbstractUserAuthentication->id`
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return ?int the user ID the session belongs to. Can also return `0` or `NULL` Which indicates an anonymous session. This is the `ses_userid`.
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * @return int the timestamp of the last session data update. This is the `ses_tstamp`.
     */
    public function getLastUpdated(): int
    {
        return $this->lastUpdated;
    }

    /**
     * Sets or updates session data value for a given `$key`. It is also
     * internally used if calling `AbstractUserAuthentication->setSessionData()`
     *
     * @param string $key The key whose value should be updated
     * @param mixed $value The value or `NULL` to unset the key
     */
    public function set(string $key, $value): void
    {
        if ($key === '') {
            throw new \InvalidArgumentException('Argument key must not be empty', 1484312516);
        }
        if ($value === null) {
            unset($this->data[$key]);
        } else {
            $this->data[$key] = $value;
        }
        $this->wasUpdated = true;
    }

    /**
     * Checks whether the session has data assigned
     */
    public function hasData(): bool
    {
        return $this->data !== [];
    }

    /**
     * Returns the session data for the given `$key` or `NULL` if the key does
     * not exist. It is internally used if calling
     * `AbstractUserAuthentication->getSessionData()`
     */
    public function get(string $key)
    {
        return $this->data[$key] ?? null;
    }

    /**
     * @return array the whole data array.
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Overrides the whole data array. Can also be used to unset the array.
     * This also sets the `$wasUpdated` pointer to `true`
     */
    public function overrideData(array $data): void
    {
        if ($this->data !== $data) {
            // Only set update flag if there is change in the $data array
            $this->wasUpdated = true;
        }

        $this->data = $data;
    }

    /**
     * Checks whether the session data has been updated
     */
    public function dataWasUpdated(): bool
    {
        return $this->wasUpdated;
    }

    /**
     * Checks if the user session is an anonymous one. This means, the
     * session does not belong to a logged-in user
     */
    public function isAnonymous(): bool
    {
        return $this->userId === 0 || $this->userId === null;
    }

    /**
     * @return string the `ipLock` state of the session
     */
    public function getIpLock(): string
    {
        return $this->ipLock;
    }

    /**
     * Checks whether the session is marked as new
     */
    public function isNew(): bool
    {
        return $this->isNew;
    }

    /**
     * Checks whether the session was marked as permanent
     */
    public function isPermanent(): bool
    {
        return $this->isPermanent;
    }

    /**
     * Checks whether the session has to be updated
     */
    public function needsUpdate(): bool
    {
        return $GLOBALS['EXEC_TIME'] > ($this->lastUpdated + self::SESSION_UPDATE_GRACE_PERIOD);
    }

    /**
     * Gets session ID wrapped in JWT to be used for emitting a new cookie.
     * `Cookie: <JWT(HS256, [identifier => <session-id>], <signature(encryption-key, cookie-domain)>)>`
     *
     * @param ?CookieScope $scope
     * @return string the session ID wrapped in JWT to be used for emitting a new cookie
     */
    public function getJwt(?CookieScope $scope = null): string
    {
        // @todo payload could be organized in a new `SessionToken` object
        return self::encodeHashSignedJwt(
            [
                'identifier' => $this->identifier,
                'time' => (new \DateTimeImmutable())->format(\DateTimeImmutable::RFC3339),
                'scope' => $scope,
            ],
            self::createSigningKeyFromEncryptionKey(UserSession::class)
        );
    }

    /**
     * Creates a new user session based on the provided session record
     *
     * @param string $id the session identifier
     */
    public static function createFromRecord(string $id, array $record, bool $markAsNew = false): self
    {
        $userSession = new self(
            $id,
            (int)($record['ses_userid'] ?? 0),
            (int)($record['ses_tstamp'] ?? 0),
            unserialize($record['ses_data'] ?? '', ['allowed_classes' => false]) ?: []
        );
        $userSession->ipLock = $record['ses_iplock'] ?? '';
        $userSession->isNew = $markAsNew;
        if (isset($record['ses_permanent'])) {
            $userSession->isPermanent = (bool)$record['ses_permanent'];
        }
        return $userSession;
    }

    /**
     * Creates a non fixated user session. This means the
     * session does not belong to a logged-in user
     */
    public static function createNonFixated(string $identifier): self
    {
        $userSession = new self($identifier, 0, $GLOBALS['EXEC_TIME'], []);
        $userSession->isPermanent = false;
        $userSession->isNew = true;
        return $userSession;
    }

    /**
     * Verifies and resolves the session ID from a submitted cookie value:
     * `Cookie: <JWT(HS256, [identifier => <session-id>], <signature(encryption-key, cookie-domain)>)>`
     *
     * @param string $cookieValue submitted cookie value
     * @param CookieScope $scope
     * @return non-empty-string|null session ID, null in case verification failed
     * @throws \Exception
     * @see getJwt()
     */
    public static function resolveIdentifierFromJwt(string $cookieValue, CookieScope $scope): ?string
    {
        if ($cookieValue === '') {
            return null;
        }

        $payload = self::decodeJwt($cookieValue, self::createSigningKeyFromEncryptionKey(UserSession::class));

        $identifier = !empty($payload->identifier) && is_string($payload->identifier) ? $payload->identifier : null;
        if ($identifier === null) {
            return null;
        }

        $domainScope = (string)($payload->scope->domain ?? '');
        $pathScope = (string)($payload->scope->path ?? '');
        if ($domainScope === '' || $pathScope === '') {
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(self::class);
            $logger->notice('A session cookie with out a domain scope has been used', ['cookieHash' => substr(sha1($cookieValue), 0, 12)]);
            return $identifier;
        }
        if ($domainScope !== $scope->domain || $pathScope !== $scope->path) {
            // invalid scope, the cookie jwt has been used on a wrong path or domain
            return null;
        }

        return $identifier;
    }

    /**
     * @internal Used internally to store data in the backend
     * @return array The session record as array
     */
    public function toArray(): array
    {
        $data = [
            'ses_id' => $this->identifier,
            'ses_data' => serialize($this->data),
            'ses_userid' => (int)$this->userId,
            'ses_iplock' => $this->ipLock,
            'ses_tstamp' => $this->lastUpdated,
        ];
        if ($this->isPermanent) {
            $data['ses_permanent'] = 1;
        }
        return $data;
    }
}
