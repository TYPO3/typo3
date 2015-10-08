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

/**
 * This class contains a session-based storage for private keys. This storage
 * is not secure enough because its implementation stores keys completely in the
 * PHP sessions. PHP sessions usually store data in the file system and it is
 * easy to extract. This storage is useful only as an example. It is better to
 * use "split" storage for keys.
 */
class SessionStorage extends AbstractStorage
{
    /**
     * Creates an instance of this class. It checks and initializes PHP
     * sessions if necessary.
     *
     * @return void
     */
    public function __construct()
    {
        if (!isset($_SESSION) || !is_array($_SESSION)) {
            session_start();
        }
    }

    /**
     * Obtains key from the session
     *
     * @return string The key or NULL
     * @see \TYPO3\CMS\Rsaauth\Storage\AbstractStorage::get()
     */
    public function get()
    {
        return isset($_SESSION['tx_rsaauth_key']) ? $_SESSION['tx_rsaauth_key'] : null;
    }

    /**
     * Puts key to the session
     *
     * @param string $key The key
     * @see \TYPO3\CMS\Rsaauth\Storage\AbstractStorage::put()
     */
    public function put($key)
    {
        $_SESSION['tx_rsaauth_key'] = $key;
    }
}
