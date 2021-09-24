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

use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class that implements Blowfish salted hashing based on PHP's
 * crypt() function.
 *
 * Warning: Blowfish salted hashing with PHP's crypt() is not available
 * on every system.
 */
class BlowfishPasswordHash implements PasswordHashInterface
{
    /**
     * Prefix for the password hash.
     */
    protected const PREFIX = '$2a$';

    /**
     * @var array The default log2 number of iterations for password stretching.
     */
    protected $options = [
        'hash_count' => 7,
    ];

    /**
     * Constructor sets options if given
     *
     * @param array $options
     * @throws \InvalidArgumentException
     */
    public function __construct(array $options = [])
    {
        $newOptions = $this->options;
        if (isset($options['hash_count'])) {
            if ((int)$options['hash_count'] < 4 || (int)$options['hash_count'] > 17) {
                throw new \InvalidArgumentException(
                    'hash_count must not be lower than 4 or bigger than 17',
                    1533903545
                );
            }
            $newOptions['hash_count'] = (int)$options['hash_count'];
        }
        $this->options = $newOptions;
    }

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
        return (bool)CRYPT_BLOWFISH;
    }

    /**
     * Method creates a salted hash for a given plaintext password
     *
     * @param string $password plaintext password to create a salted hash from
     * @return string Salted hashed password
     */
    public function getHashedPassword(string $password)
    {
        $saltedPW = null;
        if (!empty($password)) {
            $salt = $this->getGeneratedSalt();
            $saltedPW = crypt($password, $this->applySettingsToSalt($salt));
        }
        return $saltedPW;
    }

    /**
     * Checks whether a user's hashed password needs to be replaced with a new hash.
     *
     * This is typically called during the login process when the plain text
     * password is available.  A new hash is needed when the desired iteration
     * count has changed through a change in the variable $hashCount or
     * HASH_COUNT.
     *
     * @param string $saltedPW Salted hash to check if it needs an update
     * @return bool TRUE if salted hash needs an update, otherwise FALSE
     */
    public function isHashUpdateNeeded(string $saltedPW): bool
    {
        // Check whether the iteration count used differs from the standard number.
        $countLog2 = $this->getCountLog2($saltedPW);
        return $countLog2 !== null && $countLog2 < $this->options['hash_count'];
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
        $randomBytes = GeneralUtility::makeInstance(Random::class)->generateRandomBytes(16);
        return $this->base64Encode($randomBytes, 16);
    }

    /**
     * Method applies settings (prefix, hash count) to a salt.
     *
     * @param string $salt A salt to apply setting to
     * @return string Salt with setting
     */
    protected function applySettingsToSalt(string $salt): string
    {
        $saltWithSettings = $salt;
        $reqLenBase64 = $this->getLengthBase64FromBytes(16);
        // salt without setting
        if (strlen($salt) == $reqLenBase64) {
            $saltWithSettings = self::PREFIX . sprintf('%02u', $this->options['hash_count']) . '$' . $salt;
        }
        return $saltWithSettings;
    }

    /**
     * Parses the log2 iteration count from a stored hash or setting string.
     *
     * @param string $setting Complete hash or a hash's setting string or to get log2 iteration count from
     * @return int Used hashcount for given hash string
     */
    protected function getCountLog2(string $setting): int
    {
        $countLog2 = null;
        $setting = substr($setting, strlen(self::PREFIX));
        $firstSplitPos = strpos($setting, '$');
        // Hashcount existing
        if ($firstSplitPos !== false && $firstSplitPos <= 2 && is_numeric(substr($setting, 0, $firstSplitPos))) {
            $countLog2 = (int)substr($setting, 0, $firstSplitPos);
        }
        return $countLog2;
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
     * Method determines if a given string is a valid salt.
     *
     * @param string $salt String to check
     * @return bool TRUE if it's valid salt, otherwise FALSE
     */
    protected function isValidSalt(string $salt): bool
    {
        $isValid = ($skip = false);
        $reqLenBase64 = $this->getLengthBase64FromBytes(16);
        if (strlen($salt) >= $reqLenBase64) {
            // Salt with prefixed setting
            if (!strncmp('$', $salt, 1)) {
                if (!strncmp(self::PREFIX, $salt, strlen(self::PREFIX))) {
                    $isValid = true;
                    $salt = substr($salt, (int)strrpos($salt, '$') + 1);
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
}
