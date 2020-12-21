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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Session\Backend\Exception\SessionNotCreatedException;
use TYPO3\CMS\Core\Session\Backend\Exception\SessionNotFoundException;
use TYPO3\CMS\Core\Session\Backend\Exception\SessionNotUpdatedException;

/**
 * Class RedisSessionBackend
 *
 * This session backend takes these optional configuration options: 'hostname' (default '127.0.0.1'),
 * 'database' (default 0), 'port' (default 3679) and 'password' (no default value).
 */
class RedisSessionBackend implements SessionBackendInterface, HashableSessionBackendInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var array
     */
    protected $configuration = [];

    /**
     * Indicates whether the server is connected
     *
     * @var bool
     */
    protected $connected = false;

    /**
     * Used as instance independent identifier
     * (e.g. if multiple installations write into the same database)
     *
     * @var string
     */
    protected $applicationIdentifier = '';

    /**
     * Instance of the PHP redis class
     *
     * @var \Redis
     */
    protected $redis;

    /**
     * @var string
     */
    protected $identifier;

    /**
     * Initializes the session backend
     *
     * @param string $identifier Name of the session type, e.g. FE or BE
     * @param array $configuration
     * @internal To be used only by SessionManager
     */
    public function initialize(string $identifier, array $configuration)
    {
        $this->redis = new \Redis();

        $this->configuration = $configuration;
        $this->identifier = $identifier;
        $this->applicationIdentifier = 'typo3_ses_'
            . $identifier . '_'
            . sha1($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']) . '_';
    }

    /**
     * Checks if the configuration is valid
     *
     * @throws \InvalidArgumentException
     * @internal To be used only by SessionManager
     */
    public function validateConfiguration()
    {
        if (!extension_loaded('redis')) {
            throw new \RuntimeException(
                'The PHP extension "redis" must be installed and loaded in order to use the redis session backend.',
                1481269826
            );
        }

        if (isset($this->configuration['database'])) {
            if (!is_int($this->configuration['database'])) {
                throw new \InvalidArgumentException(
                    'The specified database number is of type "' . gettype($this->configuration['database']) .
                    '" but an integer is expected.',
                    1481270871
                );
            }

            if ($this->configuration['database'] < 0) {
                throw new \InvalidArgumentException(
                    'The specified database "' . $this->configuration['database'] . '" must be greater or equal than zero.',
                    1481270923
                );
            }
        }
    }

    public function hash(string $sessionId): string
    {
        // The sha1 hash ensures we have good length for the key.
        $key = sha1($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] . 'core-session-backend');
        return hash_hmac('sha256', $sessionId, $key);
    }

    /**
     * Read session data
     *
     * @param string $sessionId
     * @return array Returns the session data
     * @throws SessionNotFoundException
     */
    public function get(string $sessionId): array
    {
        $this->initializeConnection();

        $hashedSessionId = $this->hash($sessionId);
        $rawData = $this->redis->get($this->getSessionKeyName($hashedSessionId));
        if ($rawData !== false) {
            $decodedValue = json_decode($rawData, true);
            if (is_array($decodedValue)) {
                return $decodedValue;
            }
        }
        throw new SessionNotFoundException('Session could not be fetched from redis', 1481885583);
    }

    /**
     * Delete a session record
     *
     * @param string $sessionId
     *
     * @return bool
     */
    public function remove(string $sessionId): bool
    {
        $this->initializeConnection();
        return $this->redis->del($this->getSessionKeyName($this->hash($sessionId))) >= 1;
    }

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
    public function set(string $sessionId, array $sessionData): array
    {
        $this->initializeConnection();

        $hashedSessionId = $this->hash($sessionId);
        $sessionData['ses_id'] = $hashedSessionId;
        $sessionData['ses_tstamp'] = $GLOBALS['EXEC_TIME'] ?? time();

        // nx will not allow overwriting existing keys
        $jsonString = json_encode($sessionData);
        $wasSet = is_string($jsonString) && $this->redis->set(
            $this->getSessionKeyName($hashedSessionId),
            $jsonString,
            ['nx']
        );

        if (!$wasSet) {
            throw new SessionNotCreatedException('Session could not be written to Redis', 1481895647);
        }

        return $sessionData;
    }

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
    public function update(string $sessionId, array $sessionData): array
    {
        $hashedSessionId = $this->hash($sessionId);
        try {
            $sessionData = array_merge($this->get($sessionId), $sessionData);
        } catch (SessionNotFoundException $e) {
            throw new SessionNotUpdatedException('Cannot update non-existing record', 1484389971, $e);
        }
        $sessionData['ses_id'] = $hashedSessionId;
        $sessionData['ses_tstamp'] = $GLOBALS['EXEC_TIME'] ?? time();

        $key = $this->getSessionKeyName($hashedSessionId);
        $jsonString = json_encode($sessionData);
        $wasSet = is_string($jsonString) && $this->redis->set($key, $jsonString);

        if (!$wasSet) {
            throw new SessionNotUpdatedException('Session could not be updated in Redis', 1481896383);
        }

        return $sessionData;
    }

    /**
     * Garbage Collection
     *
     * @param int $maximumLifetime maximum lifetime of authenticated user sessions, in seconds.
     * @param int $maximumAnonymousLifetime maximum lifetime of non-authenticated user sessions, in seconds. If set to 0, nothing is collected.
     */
    public function collectGarbage(int $maximumLifetime, int $maximumAnonymousLifetime = 0)
    {
        foreach ($this->getAll() as $sessionRecord) {
            if (!($sessionRecord['ses_userid'] ?? false)) {
                if ($maximumAnonymousLifetime > 0 && ($sessionRecord['ses_tstamp'] + $maximumAnonymousLifetime) < $GLOBALS['EXEC_TIME']) {
                    $this->redis->del($this->getSessionKeyName($sessionRecord['ses_id']));
                }
            } else {
                if (($sessionRecord['ses_tstamp'] + $maximumLifetime) < $GLOBALS['EXEC_TIME']) {
                    $this->redis->del($this->getSessionKeyName($sessionRecord['ses_id']));
                }
            }
        }
    }

    /**
     * Initializes the redis backend
     *
     * @throws \RuntimeException if access to redis with password is denied or if database selection fails
     */
    protected function initializeConnection()
    {
        if ($this->connected) {
            return;
        }

        try {
            $this->connected = $this->redis->pconnect(
                $this->configuration['hostname'] ?? '127.0.0.1',
                $this->configuration['port'] ?? 6379,
                0.0,
                $this->identifier
            );
        } catch (\RedisException $e) {
            $this->logger->alert('Could not connect to redis server.', ['exception' => $e]);
        }

        if (!$this->connected) {
            throw new \RuntimeException(
                'Could not connect to redis server at ' . $this->configuration['hostname'] . ':' . $this->configuration['port'],
                1482242961
            );
        }

        if (isset($this->configuration['password'])
            && $this->configuration['password'] !== ''
            && !$this->redis->auth($this->configuration['password'])
        ) {
            throw new \RuntimeException(
                'The given password was not accepted by the redis server.',
                1481270961
            );
        }

        if (isset($this->configuration['database'])
            && $this->configuration['database'] > 0
            && !$this->redis->select($this->configuration['database'])
        ) {
            throw new \RuntimeException(
                'The given database "' . $this->configuration['database'] . '" could not be selected.',
                1481270987
            );
        }
    }

    /**
     * List all sessions
     *
     * @return array Return a list of all user sessions. The list may be empty.
     */
    public function getAll(): array
    {
        $this->initializeConnection();

        $keys = [];
        // Initialize our iterator to null, needed by redis->scan
        $iterator = null;
        $this->redis->setOption(\Redis::OPT_SCAN, (string)\Redis::SCAN_RETRY);
        $pattern = $this->getSessionKeyName('*');
        // retry when we get no keys back, redis->scan returns a chunk (array) of keys per iteration
        while (($keyChunk = $this->redis->scan($iterator, $pattern)) !== false) {
            foreach ($keyChunk as $key) {
                $keys[] = $key;
            }
        }

        $encodedSessions = $this->redis->mGet($keys);
        if (!is_array($encodedSessions)) {
            return [];
        }

        $sessions = [];
        foreach ($encodedSessions as $session) {
            if (is_string($session)) {
                $decodedSession = json_decode($session, true);
                if ($decodedSession) {
                    $sessions[] = $decodedSession;
                }
            }
        }

        return $sessions;
    }

    /**
     * @param string $sessionId
     * @return string
     */
    protected function getSessionKeyName(string $sessionId): string
    {
        return $this->applicationIdentifier . $sessionId;
    }

    /**
     * @return int
     */
    protected function getSessionTimeout(): int
    {
        return (int)($GLOBALS['TYPO3_CONF_VARS'][$this->identifier]['sessionTimeout'] ?? 86400);
    }
}
