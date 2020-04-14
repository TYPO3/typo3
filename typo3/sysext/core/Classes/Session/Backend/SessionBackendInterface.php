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

namespace TYPO3\CMS\Core\Session\Backend;

use TYPO3\CMS\Core\Session\Backend\Exception\SessionNotCreatedException;
use TYPO3\CMS\Core\Session\Backend\Exception\SessionNotFoundException;
use TYPO3\CMS\Core\Session\Backend\Exception\SessionNotUpdatedException;

/**
 * Interface SessionBackendInterface
 */
interface SessionBackendInterface
{
    /**
     * Initializes the session backend
     *
     * @param string $identifier Name of the session type, e.g. FE or BE
     * @param array $configuration
     * @internal To be used only by SessionManager
     */
    public function initialize(string $identifier, array $configuration);

    /**
     * Checks if the configuration is valid
     *
     * @throws \InvalidArgumentException
     * @internal To be used only by SessionManager
     */
    public function validateConfiguration();

    /**
     * List all sessions
     *
     * @return array Return a list of all user sessions. The list may be empty.
     */
    public function getAll(): array;

    /**
     * Read session data
     *
     * @param string $sessionId
     * @return array Returns the session data
     * @throws SessionNotFoundException
     */
    public function get(string $sessionId): array;

    /**
     * Delete a session record
     *
     * @param string $sessionId
     * @return bool true if the session was deleted, false it session could not be found
     */
    public function remove(string $sessionId): bool;

    /**
     * Write session data. This method prevents overriding existing session data.
     * ses_id will always be set to $sessionId and overwritten if existing in $sessionData
     * This method updates ses_tstamp automatically
     *
     * @param string $sessionId
     * @param array $sessionData
     * @return array The newly created session record.
     * @throws SessionNotCreatedException
     */
    public function set(string $sessionId, array $sessionData): array;

    /**
     * Updates the session data.
     * ses_id will always be set to $sessionId and overwritten if existing in $sessionData
     * This method updates ses_tstamp automatically
     *
     * @param string $sessionId
     * @param array $sessionData The session data to update. Data may be partial.
     * @return array $sessionData The newly updated session record.
     * @throws SessionNotUpdatedException
     */
    public function update(string $sessionId, array $sessionData): array;

    /**
     * Garbage Collection
     *
     * @param int $maximumLifetime maximum lifetime of authenticated user sessions, in seconds.
     * @param int $maximumAnonymousLifetime maximum lifetime of non-authenticated user sessions, in seconds. If set to 0, nothing is collected.
     */
    public function collectGarbage(int $maximumLifetime, int $maximumAnonymousLifetime = 0);
}
