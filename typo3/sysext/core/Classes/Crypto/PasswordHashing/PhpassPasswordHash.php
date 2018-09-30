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
 * Class that implements PHPass salted hashing based on Drupal's
 * modified Openwall implementation.
 *
 * Derived from Drupal CMS
 * original license: GNU General Public License (GPL)
 *
 * PHPass should work on every system.
 * @see http://drupal.org/node/29706/
 * @see http://www.openwall.com/phpass/
 */
class PhpassPasswordHash implements PasswordHashInterface
{
    use PublicMethodDeprecationTrait;

    /**
     * @var array
     */
    private $deprecatedPublicMethods = [
        'isValidSalt' => 'Using PhpassPasswordHash::isValidSalt() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'base64Encode' => 'Using PhpassPasswordHash::base64Encode() is deprecated and will not be possible anymore in TYPO3 v10.0.',
    ];

    /**
     * Prefix for the password hash.
     */
    protected const PREFIX = '$P$';

    /**
     * @var array The default log2 number of iterations for password stretching.
     */
    protected $options = [
        'hash_count' => 14
    ];

    /**
     * Keeps a string for mapping an int to the corresponding
     * base 64 character.
     *
     * @deprecated and will be removed in TYPO3 v10.0.
     */
    const ITOA64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    /**
     * The default log2 number of iterations for password stretching.
     *
     * @deprecated and will be removed in TYPO3 v10.0.
     */
    const HASH_COUNT = 14;

    /**
     * The default maximum allowed log2 number of iterations for
     * password stretching.
     *
     * @deprecated and will be removed in TYPO3 v10.0.
     */
    const MAX_HASH_COUNT = 24;

    /**
     * The default minimum allowed log2 number of iterations for
     * password stretching.
     *
     * @deprecated and will be removed in TYPO3 v10.0.
     */
    const MIN_HASH_COUNT = 7;

    /**
     * Constructor sets options if given
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $newOptions = $this->options;
        if (isset($options['hash_count'])) {
            if ((int)$options['hash_count'] < 7 || (int)$options['hash_count'] > 24) {
                throw new \InvalidArgumentException(
                    'hash_count must not be lower than 7 or bigger than 24',
                    1533940454
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
     * @param string $plainPW Plain-text password to compare with salted hash
     * @param string $saltedHashPW Salted hash to compare plain-text password with
     * @return bool TRUE, if plain-text password matches the salted hash, otherwise FALSE
     */
    public function checkPassword(string $plainPW, string $saltedHashPW): bool
    {
        $hash = $this->cryptPassword($plainPW, $saltedHashPW);
        return $hash && hash_equals($hash, $saltedHashPW);
    }

    /**
     * Returns whether all prerequisites for the hashing methods are matched
     *
     * @return bool Method available
     */
    public function isAvailable(): bool
    {
        return true;
    }

    /**
     * Method creates a salted hash for a given plaintext password
     *
     * @param string $password Plaintext password to create a salted hash from
     * @param string $salt Deprecated optional custom salt with setting to use
     * @return string|null salted hashed password
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
            $saltedPW = $this->cryptPassword($password, $this->applySettingsToSalt($salt));
        }
        return $saltedPW;
    }

    /**
     * Checks whether a user's hashed password needs to be replaced with a new hash.
     *
     * This is typically called during the login process when the plain text
     * password is available. A new hash is needed when the desired iteration
     * count has changed through a change in the variable $hashCount or HASH_COUNT.
     *
     * @param string $passString Salted hash to check if it needs an update
     * @return bool TRUE if salted hash needs an update, otherwise FALSE
     */
    public function isHashUpdateNeeded(string $passString): bool
    {
        // Check whether this was an updated password.
        if (strncmp($passString, '$P$', 3) || strlen($passString) != 34) {
            return true;
        }
        // Check whether the iteration count used differs from the standard number.
        return $this->getCountLog2($passString) < $this->options['hash_count'];
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
     * Method applies settings (prefix, hash count) to a salt.
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
            // We encode the final log2 iteration count in base 64.
            $itoa64 = $this->getItoa64();
            $saltWithSettings = self::PREFIX . $itoa64[$this->options['hash_count']];
            $saltWithSettings .= $salt;
        }
        return $saltWithSettings;
    }

    /**
     * Hashes a password using a secure stretched hash.
     *
     * By using a salt and repeated hashing the password is "stretched". Its
     * security is increased because it becomes much more computationally costly
     * for an attacker to try to break the hash by brute-force computation of the
     * hashes of a large number of plain-text words or strings to find a match.
     *
     * @param string $password Plain-text password to hash
     * @param string $setting An existing hash or the output of getGeneratedSalt()
     * @return mixed A string containing the hashed password (and salt)
     */
    protected function cryptPassword(string $password, string $setting)
    {
        $saltedPW = null;
        $reqLenBase64 = $this->getLengthBase64FromBytes(6);
        // Retrieving settings with salt
        $setting = substr($setting, 0, strlen(self::PREFIX) + 1 + $reqLenBase64);
        $count_log2 = $this->getCountLog2($setting);
        // Hashes may be imported from elsewhere, so we allow != HASH_COUNT
        if ($count_log2 >= 7 && $count_log2 <= 24) {
            $salt = substr($setting, strlen(self::PREFIX) + 1, $reqLenBase64);
            // We must use md5() or sha1() here since they are the only cryptographic
            // primitives always available in PHP 5. To implement our own low-level
            // cryptographic function in PHP would result in much worse performance and
            // consequently in lower iteration counts and hashes that are quicker to crack
            // (by non-PHP code).
            $count = 1 << $count_log2;
            $hash = md5($salt . $password, true);
            do {
                $hash = md5($hash . $password, true);
            } while (--$count);
            $saltedPW = $setting . $this->base64Encode($hash, 16);
            // base64Encode() of a 16 byte MD5 will always be 22 characters.
            return strlen($saltedPW) == 34 ? $saltedPW : false;
        }
        return $saltedPW;
    }

    /**
     * Parses the log2 iteration count from a stored hash or setting string.
     *
     * @param string $setting Complete hash or a hash's setting string or to get log2 iteration count from
     * @return int Used hashcount for given hash string
     */
    protected function getCountLog2(string $setting): int
    {
        return strpos($this->getItoa64(), $setting[strlen(self::PREFIX)]);
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
        $reqLenBase64 = $this->getLengthBase64FromBytes(6);
        if (strlen($salt) >= $reqLenBase64) {
            // Salt with prefixed setting
            if (!strncmp('$', $salt, 1)) {
                if (!strncmp(self::PREFIX, $salt, strlen(self::PREFIX))) {
                    $isValid = true;
                    $salt = substr($salt, strrpos($salt, '$') + 2);
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
     * Method returns log2 number of iterations for password stretching.
     *
     * @return int log2 number of iterations for password stretching
     * @deprecated and will be removed in TYPO3 v10.0.
     */
    public function getHashCount(): int
    {
        trigger_error('This method will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        return $this->options['hash_count'];
    }

    /**
     * Method returns maximum allowed log2 number of iterations for password stretching.
     *
     * @return int Maximum allowed log2 number of iterations for password stretching
     * @deprecated and will be removed in TYPO3 v10.0.
     */
    public function getMaxHashCount(): int
    {
        trigger_error('This method will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        return 24;
    }

    /**
     * Method returns minimum allowed log2 number of iterations for password stretching.
     *
     * @return int Minimum allowed log2 number of iterations for password stretching
     * @deprecated and will be removed in TYPO3 v10.0.
     */
    public function getMinHashCount(): int
    {
        trigger_error('This method will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        return 7;
    }

    /**
     * Returns length of a Blowfish salt in bytes.
     *
     * @return int Length of a Blowfish salt in bytes
     * @deprecated and will be removed in TYPO3 v10.0.
     */
    public function getSaltLength(): int
    {
        trigger_error('This method will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        return 6;
    }

    /**
     * Returns setting string of PHPass salted hashes.
     *
     * @return string Setting string of PHPass salted hashes
     * @deprecated and will be removed in TYPO3 v10.0.
     */
    public function getSetting(): string
    {
        trigger_error('This method will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        return self::PREFIX;
    }

    /**
     * Method sets log2 number of iterations for password stretching.
     *
     * @param int $hashCount log2 number of iterations for password stretching to set
     * @deprecated and will be removed in TYPO3 v10.0.
     */
    public function setHashCount(int $hashCount = null)
    {
        trigger_error('This method will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        if ($hashCount >= 7 && $hashCount <= 24) {
            $this->options['hash_count'] = $hashCount;
        }
    }

    /**
     * Method sets maximum allowed log2 number of iterations for password stretching.
     *
     * @param int $maxHashCount Maximum allowed log2 number of iterations for password stretching to set
     * @deprecated and will be removed in TYPO3 v10.0.
     */
    public function setMaxHashCount(int $maxHashCount = null)
    {
        trigger_error('This method will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        // Empty, max hash count is hard coded to 24
    }

    /**
     * Method sets minimum allowed log2 number of iterations for password stretching.
     *
     * @param int $minHashCount Minimum allowed log2 number of iterations for password stretching to set
     * @deprecated and will be removed in TYPO3 v10.0.
     */
    public function setMinHashCount(int $minHashCount = null)
    {
        trigger_error('This method will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        // Empty, max hash count is hard coded to 7
    }
}
