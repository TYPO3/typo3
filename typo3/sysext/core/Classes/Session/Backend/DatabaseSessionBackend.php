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

use Doctrine\DBAL\Exception as DBALException;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Session\Backend\Exception\SessionNotCreatedException;
use TYPO3\CMS\Core\Session\Backend\Exception\SessionNotFoundException;
use TYPO3\CMS\Core\Session\Backend\Exception\SessionNotUpdatedException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class DatabaseSessionBackend
 *
 * This session backend requires the 'table' configuration option. If the backend is used to holds non-authenticated
 * sessions (default in frontend application), the 'ses_userid' configuration option must be set to `0`.
 */
class DatabaseSessionBackend implements SessionBackendInterface, HashableSessionBackendInterface
{
    /**
     * @var array
     */
    protected $configuration = [];

    /**
     * @var bool Indicates whether the ses_userid is set to `0` in the sessions table
     */
    protected $hasAnonymousSessions = false;

    /**
     * Initializes the session backend
     *
     * @param string $identifier Name of the session type, e.g. FE or BE
     * @param array $configuration
     * @internal To be used only by SessionManager
     */
    public function initialize(string $identifier, array $configuration)
    {
        $this->hasAnonymousSessions = (bool)($configuration['has_anonymous'] ?? false);
        $this->configuration = $configuration;
    }

    /**
     * Checks if the configuration is valid
     *
     * @return bool
     * @throws \InvalidArgumentException
     * @internal To be used only by SessionManager
     */
    public function validateConfiguration(): bool
    {
        if (empty($this->configuration['table'])) {
            throw new \InvalidArgumentException(
                'The session backend "' . static::class . '" needs a "table" configuration.',
                1442996707
            );
        }
        return true;
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
        $query = $this->getQueryBuilder();
        $query->select('*')
            ->from($this->configuration['table'])
            ->where($query->expr()->eq('ses_id', $query->createNamedParameter($this->hash($sessionId), \PDO::PARAM_STR)));
        $result = $query->executeQuery()->fetchAssociative();
        if (!is_array($result)) {
            throw new SessionNotFoundException(
                'The session with identifier ' . $sessionId . ' was not found ',
                1481885483
            );
        }
        return $result;
    }

    /**
     * Delete a session record
     *
     * @param string $sessionId
     * @return bool true if the session was deleted, false it session could not be found
     */
    public function remove(string $sessionId): bool
    {
        $query = $this->getQueryBuilder();
        $query->delete($this->configuration['table'])
            ->where(
                $query->expr()->orX(
                    $query->expr()->eq('ses_id', $query->createNamedParameter($this->hash($sessionId), \PDO::PARAM_STR)),
                    $query->expr()->eq('ses_id', $query->createNamedParameter($sessionId, \PDO::PARAM_STR))
                )
            );

        return (bool)$query->executeStatement();
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
        $sessionId = $this->hash($sessionId);
        $sessionData['ses_id'] = $sessionId;
        $sessionData['ses_tstamp'] = $GLOBALS['EXEC_TIME'] ?? time();

        try {
            $this->getConnection()->insert(
                $this->configuration['table'],
                $sessionData,
                ['ses_data' => \PDO::PARAM_LOB]
            );
        } catch (DBALException $e) {
            throw new SessionNotCreatedException(
                'Session could not be written to database: ' . $e->getMessage(),
                1481895005,
                $e
            );
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
        $sessionData['ses_id'] = $hashedSessionId;
        $sessionData['ses_tstamp'] = $GLOBALS['EXEC_TIME'] ?? time();

        try {
            // allow 0 records to be affected, happens when no columns where changed
            $this->getConnection()->update(
                $this->configuration['table'],
                $sessionData,
                ['ses_id' => $hashedSessionId],
                ['ses_data' => \PDO::PARAM_LOB]
            );
        } catch (DBALException $e) {
            throw new SessionNotUpdatedException(
                'Session with id ' . $sessionId . ' could not be updated: ' . $e->getMessage(),
                1481889220,
                $e
            );
        }
        return $sessionData;
    }

    /**
     * Garbage Collection
     *
     * @param int $maximumLifetime maximum lifetime of authenticated user sessions, in seconds.
     * @param int $maximumAnonymousLifetime maximum lifetime of non-authenticated user sessions, in seconds. If set to 0, non-authenticated sessions are ignored.
     */
    public function collectGarbage(int $maximumLifetime, int $maximumAnonymousLifetime = 0)
    {
        $query = $this->getQueryBuilder();

        $query->delete($this->configuration['table'])
            ->where($query->expr()->lt('ses_tstamp', (int)($GLOBALS['EXEC_TIME'] - (int)$maximumLifetime)))
            ->andWhere($this->hasAnonymousSessions ? $query->expr()->neq('ses_userid', 0) : ' 1 = 1');
        $query->executeStatement();

        if ($maximumAnonymousLifetime > 0 && $this->hasAnonymousSessions) {
            $query = $this->getQueryBuilder();
            $query->delete($this->configuration['table'])
                ->where($query->expr()->lt('ses_tstamp', (int)($GLOBALS['EXEC_TIME'] - (int)$maximumAnonymousLifetime)))
                ->andWhere($query->expr()->eq('ses_userid', 0));
            $query->executeStatement();
        }
    }

    /**
     * List all sessions
     *
     * @return array Return a list of all user sessions. The list may be empty
     */
    public function getAll(): array
    {
        $query = $this->getQueryBuilder();
        $query->select('*')->from($this->configuration['table']);
        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * @return QueryBuilder
     */
    protected function getQueryBuilder(): QueryBuilder
    {
        return $this->getConnection()->createQueryBuilder();
    }

    /**
     * @return Connection
     */
    protected function getConnection(): Connection
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->configuration['table']);
    }
}
