<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Crypto\PasswordHashing;

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
 * Interface for implementing salts that compose the password-hash string
 * themselves.
 *
 * @deprecated and will be removed in TYPO3 v10.0.
 */
interface ComposedPasswordHashInterface extends PasswordHashInterface
{
    /**
     * Returns length of required salt.
     *
     * @return int Length of required salt
     */
    public function getSaltLength(): int;

    /**
     * Method determines if a given string is a valid salt
     *
     * @param string $salt String to check
     * @return bool TRUE if it's valid salt, otherwise FALSE
     */
    public function isValidSalt(string $salt): bool;
}
