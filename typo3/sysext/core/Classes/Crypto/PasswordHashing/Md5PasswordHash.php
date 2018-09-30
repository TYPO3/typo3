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

use TYPO3\CMS\Core\Compatibility\PublicMethodDeprecationTrait;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class that implements MD5 salted hashing based on PHP's
 * crypt() function.
 *
 * MD5 salted hashing with PHP's crypt() should be available
 * on most of the systems.
 */
class Md5PasswordHash implements PasswordHashInterface
{
    use PublicMethodDeprecationTrait;

    /**
     * @var array
     */
    private $deprecatedPublicMethods = [
        'isValidSalt' => 'Using Md5PasswordHash::isValidSalt() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'base64Encode' => 'Using Md5PasswordHash::base64Encode() is deprecated and will not be possible anymore in TYPO3 v10.0.',
    ];

    /**
     * Prefix for the password hash.
     */
    protected const PREFIX = '$1$';

    /**
     * Keeps a string for mapping an int to the corresponding
     * base 64 character.
     *
     * @deprecated and will be removed in TYPO3 v10.0.
     */
    const ITOA64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    /**
     * Method checks if a given plaintext password is correct by comparing it with
     * a given salted hashed password.
     *
     * @param string $plainPW plain-text password to compare with salted hash
     * @param string $saltedHashPW salted hash to compare plain-text password with
     * @return bool TRUE, if plain-text password matches the salted hash, otherwise FALSE
     */
    public function checkPassword(string $plainPW, string $saltedHashPW): bool
    {
        $isCorrect = false;
        if ($this->isValidSalt($saltedHashPW)) {
            $isCorrect = \password_verify($plainPW, $saltedHashPW);
        }
        return $isCorrect;
    }

    /**
     * Returns whether all prerequisites for the hashing methods are matched
     *
     * @return bool Method available
     */
    public function isAvailable(): bool
    {
        return (bool)CRYPT_MD5;
    }

    /**
     * Method creates a salted hash for a given plaintext password
     *
     * @param string $password plaintext password to create a salted hash from
     * @param string $salt Deprecated optional custom salt with setting to use
     * @return string Salted hashed password
     */
    public function getHashedPassword(string $password, string $salt = null)
    {
        if ($salt !== null) {
            trigger_error(static::class . ': using a custom salt is deprecated.', E_USER_DEPRECATED);
        }
        $saltedPW = null;
        if (!empty($password)) {
            if (empty($salt) || !$this->isValidSalt($salt)) {
                $salt = $this->getGeneratedSalt();
            }
            $saltedPW = crypt($password, $this->applySettingsToSalt($salt));
        }
        return $saltedPW;
    }

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
    public function isHashUpdateNeeded(string $passString): bool
    {
        return false;
    }

    /**
     * Method determines if a given string is a valid salted hashed password.
     *
     * @param string $saltedPW String to check
     * @return bool TRUE if it's valid salted hashed password, otherwise FALSE
     */
    public function isValidSaltedPW(string $saltedPW): bool
    {
        $isValid = !strncmp(self::PREFIX, $saltedPW, strlen(self::PREFIX));
        if ($isValid) {
            $isValid = $this->isValidSalt($saltedPW);
        }
        return $isValid;
    }

    /**
     * Generates a random base 64-encoded salt prefixed and suffixed with settings for the hash.
     *
     * Proper use of salts may defeat a number of attacks, including:
     * - The ability to try candidate passwords against multiple hashes at once.
     * - The ability to use pre-hashed lists of candidate passwords.
     * - The ability to determine whether two users have the same (or different)
     * password without actually having to guess one of the passwords.
     *
     * @return string A character string containing settings and a random salt
     */
    protected function getGeneratedSalt(): string
    {
        $randomBytes = GeneralUtility::makeInstance(Random::class)->generateRandomBytes(6);
        return $this->base64Encode($randomBytes, 6);
    }

    /**
     * Method applies settings (prefix, suffix) to a salt.
     *
     * @param string $salt A salt to apply setting to
     * @return string Salt with setting
     */
    protected function applySettingsToSalt(string $salt): string
    {
        $saltWithSettings = $salt;
        $reqLenBase64 = $this->getLengthBase64FromBytes(6);
        // Salt without setting
        if (strlen($salt) == $reqLenBase64) {
            $saltWithSettings = self::PREFIX . $salt . '$';
        }
        return $saltWithSettings;
    }

    /**
     * Returns a string for mapping an int to the corresponding base 64 character.
     *
     * @return string String for mapping an int to the corresponding base 64 character
     */
    protected function getItoa64(): string
    {
        return './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    }

    /**
     * Method determines if a given string is a valid salt
     *
     * @param string $salt String to check
     * @return bool TRUE if it's valid salt, otherwise FALSE
     */
    protected function isValidSalt(string $salt): bool
    {
        $isValid = ($skip = false);
        $reqLenBase64 = $this->getLengthBase64FromBytes(6);
        if (strlen($salt) >= $reqLenBase64) {
            // Salt with prefixed setting
            if (!strncmp('$', $salt, 1)) {
                if (!strncmp(self::PREFIX, $salt, strlen(self::PREFIX))) {
                    $isValid = true;
                    $salt = substr($salt, strlen(self::PREFIX));
                } else {
                    $skip = true;
                }
            }
            // Checking base64 characters
            if (!$skip && strlen($salt) >= $reqLenBase64) {
                if (preg_match('/^[' . preg_quote($this->getItoa64(), '/') . ']{' . $reqLenBase64 . ',' . $reqLenBase64 . '}$/', substr($salt, 0, $reqLenBase64))) {
                    $isValid = true;
                }
            }
        }
        return $isValid;
    }

    /**
     * Encodes bytes into printable base 64 using the *nix standard from crypt().
     *
     * @param string $input The string containing bytes to encode.
     * @param int $count The number of characters (bytes) to encode.
     * @return string Encoded string
     */
    protected function base64Encode(string $input, int $count): string
    {
        $output = '';
        $i = 0;
        $itoa64 = $this->getItoa64();
        do {
            $value = ord($input[$i++]);
            $output .= $itoa64[$value & 63];
            if ($i < $count) {
                $value |= ord($input[$i]) << 8;
            }
            $output .= $itoa64[$value >> 6 & 63];
            if ($i++ >= $count) {
                break;
            }
            if ($i < $count) {
                $value |= ord($input[$i]) << 16;
            }
            $output .= $itoa64[$value >> 12 & 63];
            if ($i++ >= $count) {
                break;
            }
            $output .= $itoa64[$value >> 18 & 63];
        } while ($i < $count);
        return $output;
    }

    /**
     * Method determines required length of base64 characters for a given
     * length of a byte string.
     *
     * @param int $byteLength Length of bytes to calculate in base64 chars
     * @return int Required length of base64 characters
     */
    protected function getLengthBase64FromBytes(int $byteLength): int
    {
        // Calculates bytes in bits in base64
        return (int)ceil($byteLength * 8 / 6);
    }

    /**
     * Returns setting string of MD5 salted hashes.
     *
     * @return string Setting string of MD5 salted hashes
     * @deprecated and will be removed in TYPO3 v10.0.
     */
    public function getSetting(): string
    {
        trigger_error('This method will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        return self::PREFIX;
    }

    /**
     * Returns length of a MD5 salt in bytes.
     *
     * @return int Length of a MD5 salt in bytes
     * @deprecated and will be removed in TYPO3 v10.0.
     */
    public function getSaltLength(): int
    {
        trigger_error('This method will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        return 6;
    }
}
