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
 * The $isNew flag is meant to show that this user session object was not fetched from the session backend,
 * but initialized in the first place by the current request.
 *
 * The $data argument is to store any arbitrary data valid for the users' session.
 *
 * A permanent session means that the client is not issued a session-based cookie but a time-based cookie.
 * So the server-session survives the session of the browser.
 */
class UserSession
{
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
     * Get the user session identifier (the ses_id)
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Get the user id (ID of the user record to whom the session belongs)
     *
     * @return int
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * Get the timestamp of the last session data update
     *
     * @return int
     */
    public function getLastUpdated(): int
    {
        return $this->lastUpdated;
    }

    /**
     * Set / update a data value for a given key.
     * Throws an exception if the given key is empty.
     *
     * @param string $key The key whose value should be updated
     * @param mixed $value The value or NULL to unset the key
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
     * Check whether the session has data
     *
     * @return bool
     */
    public function hasData(): bool
    {
        return $this->data !== [];
    }

    /**
     * Return the data for the given key or an NULL if the key does not exist
     *
     * @param string $key
     * @return mixed
     */
    public function get(string $key)
    {
        return $this->data[$key] ?? null;
    }

    /**
     * Return the whole session data array.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Override the whole $data. Can be used to e.g. preserve session data
     * on login or to remove session data by providing an empty array.
     *
     * @param array $data
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
     * Check if session data was already updated
     *
     * @return bool
     */
    public function dataWasUpdated(): bool
    {
        return $this->wasUpdated;
    }

    /**
     * Check if the user session is an anonymous one.
     * This means, the session does not belong to a logged-in user.
     *
     * @return bool
     */
    public function isAnonymous(): bool
    {
        return $this->userId === 0 || $this->userId === null;
    }

    /**
     * Return the sessions ipLock state
     *
     * @return string
     */
    public function getIpLock(): string
    {
        return $this->ipLock;
    }

    /**
     * Check whether the session was marked as new on creation
     *
     * @return bool
     */
    public function isNew(): bool
    {
        return $this->isNew;
    }

    /**
     * Check whether the session was marked as permanent on creation
     *
     * @return bool
     */
    public function isPermanent(): bool
    {
        return $this->isPermanent;
    }

    /**
     * Use a gracetime-value to avoid updating a session-record too often
     *
     * @return bool
     */
    public function needsUpdate(): bool
    {
        return $GLOBALS['EXEC_TIME'] > ($this->lastUpdated + self::SESSION_UPDATE_GRACE_PERIOD);
    }

    /**
     * Create a new user session based on the provided session record
     *
     * @param string $id the session identifier
     * @param array $record
     * @param bool $markAsNew
     * @return UserSession
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
     * Create a non fixated user session. This means the
     * session does not belong to a logged-in user.
     *
     * @param string $identifier
     * @return UserSession
     */
    public static function createNonFixated(string $identifier): self
    {
        $userSession = new self($identifier, 0, $GLOBALS['EXEC_TIME'], []);
        $userSession->isPermanent = false;
        $userSession->isNew = true;
        return $userSession;
    }

    /**
     * Used internally to store data in the backend
     *
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
