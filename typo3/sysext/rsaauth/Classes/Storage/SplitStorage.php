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

use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * This class contains a "split" storage for the data. It keeps part of the data
 * in the database, part in the session.
 */
class SplitStorage extends AbstractStorage
{
    /**
     * @var DatabaseConnection;
     */
    protected $databaseConnection;

    /**
     * Creates an instance of this class. It checks and initializes PHP
     * sessions if necessary.
     *
     * @param DatabaseConnection $databaseConnection A database connection may be injected here
     */
    public function __construct(DatabaseConnection $databaseConnection = null)
    {
        if (session_id() === '') {
            session_start();
        }
        $this->databaseConnection = $databaseConnection ?: $GLOBALS['TYPO3_DB'];
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
            // Remove expired keys (more than 30 minutes old)
            $this->databaseConnection->exec_DELETEquery('tx_rsaauth_keys', 'crdate<' . ($GLOBALS['EXEC_TIME'] - 30 * 60));
            // Get our value
            $row = $this->databaseConnection->exec_SELECTgetSingleRow('key_value', 'tx_rsaauth_keys', 'uid=' . $keyId);
            if (is_array($row)) {
                $result = $keyPart1 . $row['key_value'];
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
        if ($key == null) {
            // Remove existing key
            list($keyId) = $_SESSION['tx_rsaauth_key'];
            if (MathUtility::canBeInterpretedAsInteger($keyId)) {
                $this->databaseConnection->exec_DELETEquery('tx_rsaauth_keys', 'uid=' . $keyId);
                unset($_SESSION['tx_rsaauth_key']);
                if (empty($_SESSION)) {
                    $sessionName = session_name();
                    $sessionCookie = session_get_cookie_params();
                    session_destroy();
                    // By using setcookie with the second parameter set to false we actually delete the cookie
                    setcookie($sessionName, false, $sessionCookie['lifetime'], $sessionCookie['path'], $sessionCookie['domain'], $sessionCookie['secure']);
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
            $this->databaseConnection->exec_INSERTquery('tx_rsaauth_keys', [
                'pid' => 0,
                'crdate' => $time,
                'key_value' => $keyPart2
            ]);
            $keyId = $this->databaseConnection->sql_insert_id();
            // Store another part in session
            $_SESSION['tx_rsaauth_key'] = [$keyId, $keyPart1];
        }
        // Remove expired keys (more than 30 minutes old)
        $this->databaseConnection->exec_DELETEquery('tx_rsaauth_keys', 'crdate<' . ($GLOBALS['EXEC_TIME'] - 30 * 60));
    }
}
