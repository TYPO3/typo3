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

namespace TYPO3\CMS\Install\Service\Session;

use TYPO3\CMS\Install\Exception;

final class RedisSessionHandler implements \SessionHandlerInterface
{
    private \Redis $redis;

    /**
     * @param array{user?: string, pass?: string} $authentication
     */
    public function __construct(
        /**
         * time (minutes) to expire an unused session
         */
        private readonly int $expirationTimeInMinutes,
        private readonly string $host = '127.0.0.1',
        private readonly int $port = 6379,
        private readonly int $database = 0,
        private readonly array $authentication = [],
    ) {
        $this->redis = new \Redis();
        $this->redis->connect($this->host, $this->port);

        if (!empty($this->authentication)) {
            $this->redis->auth($this->authentication);
        }

        $this->redis->select($this->database);
    }

    public function close(): bool
    {
        return true;
    }

    /**
     * @throws Exception
     */
    public function destroy(string $id): bool
    {
        $sessionHash = $this->getSessionHash($id);
        $this->redis->del($sessionHash);

        return true;
    }

    public function gc(int $max_lifetime): int
    {
        // garbage collection is handled by Redis itself, so we do not need to do anything here
        return 0;
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    /**
     * @throws Exception
     */
    public function read(string $id): string
    {
        $sessionHash = $this->getSessionHash($id);

        $data = $this->redis->get($sessionHash);

        // return empty string here to not use default php session handler behavior, because that will lead to an error
        return $data === false ? '' : $data;
    }

    /**
     * @throws Exception
     */
    public function write(string $id, string $data): bool
    {
        $sessionHash = $this->getSessionHash($id);

        return $this->redis->setex($sessionHash, $this->expirationTimeInMinutes * 60, $data);
    }

    /**
     * Returns a session hash, which can only be calculated by the server.
     * Used to store our session files without exposing the session ID.
     *
     * @param string $sessionId An alternative session ID. Defaults to our current session ID
     * @throws \TYPO3\CMS\Install\Exception
     */
    private function getSessionHash(string $sessionId = ''): string
    {
        if (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])) {
            throw new \TYPO3\CMS\Install\Exception(
                'No encryption key set to secure session',
                1751729886
            );
        }
        if (!$sessionId) {
            $sessionId = (string)($this->getSessionId() ?: '');
        }
        return md5($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] . '|' . $sessionId);
    }

    /**
     * Returns the session ID of the running session.
     */
    public function getSessionId(): string|false
    {
        return session_id();
    }
}
