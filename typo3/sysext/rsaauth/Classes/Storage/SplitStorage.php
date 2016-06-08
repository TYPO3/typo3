<?php
namespace TYPO3\CMS\Rsaauth\Storage;

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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * This class contains a "split" storage for the data. It keeps part of the data
 * in the database, part in the session.
 */
class SplitStorage extends AbstractStorage
{
    /**
     * Creates an instance of this class. It checks and initializes PHP
     * sessions if necessary.
     */
    public function __construct()
    {
        if (session_id() === '') {
            session_start();
        }
    }

    /**
     * Obtains a key from the database
     *
     * @return string The key or NULL
     * @see \TYPO3\CMS\Rsaauth\Storage\AbstractStorage::get()
     */
    public function get()
    {
        $result = null;
        list($keyId, $keyPart1) = $_SESSION['tx_rsaauth_key'];
        if (MathUtility::canBeInterpretedAsInteger($keyId)) {
            $this->removeExpiredKeys();

            // Get our value
            $keyValue = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('tx_rsaauth_keys')
                ->select(['key_value'], 'tx_rsaauth_keys', ['uid' => $keyId])
                ->fetchColumn();

            if ($keyValue !== false) {
                $result = $keyPart1 . $keyValue;
            }
        }

        return $result;
    }

    /**
     * Adds a key to the storage or removes existing key
     *
     * @param string $key The key
     * @return void
     * @see \TYPO3\CMS\Rsaauth\Storage\AbstractStorage::put()
     */
    public function put($key)
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_rsaauth_keys');
        if ($key == null) {
            // Remove existing key
            list($keyId) = $_SESSION['tx_rsaauth_key'];
            if (MathUtility::canBeInterpretedAsInteger($keyId)) {
                $connection->delete(
                    'tx_rsaauth_keys',
                    ['uid' => $keyId]
                );
                unset($_SESSION['tx_rsaauth_key']);
                if (empty($_SESSION)) {
                    $sessionName = session_name();
                    $sessionCookie = session_get_cookie_params();
                    session_destroy();
                    // By using setcookie with the second parameter set to false we actually delete the cookie
                    setcookie(
                        $sessionName,
                        false,
                        $sessionCookie['lifetime'],
                        $sessionCookie['path'],
                        $sessionCookie['domain'],
                        $sessionCookie['secure']
                    );
                }
            }
        } else {
            // Add key
            // Get split point. First part is always smaller than the second
            // because it goes to the file system
            $keyLength = strlen($key);
            $splitPoint = rand((int)($keyLength / 10), (int)($keyLength / 2));
            // Get key parts
            $keyPart1 = substr($key, 0, $splitPoint);
            $keyPart2 = substr($key, $splitPoint);
            // Store part of the key in the database
            //
            // Notice: we may not use TCEmain below to insert key part into the
            // table because TCEmain requires a valid BE user!
            $time = $GLOBALS['EXEC_TIME'];
            $connection->insert(
                'tx_rsaauth_keys',
                [
                    'pid' => 0,
                    'crdate' => $time,
                    'key_value' => $keyPart2
                ]
            );
            $keyId = $connection->lastInsertId();
            // Store another part in session
            $_SESSION['tx_rsaauth_key'] = array($keyId, $keyPart1);
        }

        $this->removeExpiredKeys();
    }

    /**
     * Remove expired keys (more than 30 minutes old).
     *
     * @return int The number of expired keys that have been removed
     */
    protected function removeExpiredKeys(): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_rsaauth_keys');
        $count = $queryBuilder->delete('tx_rsaauth_keys')
            ->where($queryBuilder->expr()->lt('crdate', ($GLOBALS['EXEC_TIME'] - 30 * 60)))
            ->execute();

        return (int)$count;
    }
}
