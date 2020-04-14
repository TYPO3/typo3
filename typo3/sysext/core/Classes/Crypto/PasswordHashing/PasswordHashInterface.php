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

namespace TYPO3\CMS\Core\Crypto\PasswordHashing;

/**
 * Interface with public methods needed to be implemented
 * in a salting hashing class.
 */
interface PasswordHashInterface
{
    /**
     * Method checks if a given plaintext password is correct by comparing it with
     * a given salted hashed password.
     *
     * @param string $plainPW plain-text password to compare with salted hash
     * @param string $saltedHashPW Salted hash to compare plain-text password with
     * @return bool TRUE, if plaintext password is correct, otherwise FALSE
     */
    public function checkPassword(string $plainPW, string $saltedHashPW): bool;

    /**
     * Returns whether all prerequisites for the hashing methods are matched
     *
     * @return bool Method available
     */
    public function isAvailable(): bool;

    /**
     * Method creates a salted hash for a given plaintext password
     *
     * @param string $password Plaintext password to create a salted hash from
     * @return string Salted hashed password
     */
    public function getHashedPassword(string $password);

    /**
     * Checks whether a user's hashed password needs to be replaced with a new hash.
     *
     * This is typically called during the login process when the plain text
     * password is available.  A new hash is needed when the desired iteration
     * count has changed through a change in the variable $hashCount or HASH_COUNT.
     *
     * @param string $passString Salted hash to check if it needs an update
     * @return bool TRUE if salted hash needs an update, otherwise FALSE
     */
    public function isHashUpdateNeeded(string $passString): bool;

    /**
     * Method determines if a given string is a valid salted hashed password.
     *
     * @param string $saltedPW String to check
     * @return bool TRUE if it's valid salted hashed password, otherwise FALSE
     */
    public function isValidSaltedPW(string $saltedPW): bool;
}
