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
 * Class that implements PBKDF2 salted hashing based on PHP's
 * hash_pbkdf2() function.
 */
class Pbkdf2PasswordHash implements PasswordHashInterface
{
    use PublicMethodDeprecationTrait;

    /**
     * @var array
     */
    private $deprecatedPublicMethods = [
        'isValidSalt' => 'Using Pbkdf2PasswordHash::isValidSalt() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'base64Encode' => 'Using Pbkdf2PasswordHash::base64Encode() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'base64Decode' => 'Using Pbkdf2PasswordHash::base64Decode() is deprecated and will not be possible anymore in TYPO3 v10.0.',
    ];

    /**
     * Prefix for the password hash.
     */
    protected const PREFIX = '$pbkdf2-sha256$';

    /**
     * @var array The default log2 number of iterations for password stretching.
     */
    protected $options = [
        'hash_count' => 25000
    ];

    /**
     * Keeps a string for mapping an int to the corresponding
     * base 64 character.
     *
     * @deprecated and will be removed in TYPO3 v10.0.
     */
    const ITOA64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    /**
     * The default number of iterations for password stretching.
     *
     * @deprecated and will be removed in TYPO3 v10.0.
     */
    const HASH_COUNT = 25000;

    /**
     * The default maximum allowed number of iterations for password stretching.
     *
     * @deprecated and will be removed in TYPO3 v10.0.
     */
    const MAX_HASH_COUNT = 10000000;

    /**
     * The default minimum allowed number of iterations for password stretching.
     *
     * @deprecated and will be removed in TYPO3 v10.0.
     */
    const MIN_HASH_COUNT = 1000;

    /**
     * Constructor sets options if given
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $newOptions = $this->options;
        if (isset($options['hash_count'])) {
            if ((int)$options['hash_count'] < 1000 || (int)$options['hash_count'] > 10000000) {
                throw new \InvalidArgumentException(
                    'hash_count must not be lower than 1000 or bigger than 10000000',
                    1533903544
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
        return $this->isValidSalt($saltedHashPW) && hash_equals($this->getHashedPasswordInternal($plainPW, $saltedHashPW), $saltedHashPW);
    }

    /**
     * Returns whether all prerequisites for the hashing methods are matched
     *
     * @return bool Method available
     */
    public function isAvailable(): bool
    {
        return function_exists('hash_pbkdf2');
    }

    /**
     * Method creates a salted hash for a given plaintext password
     *
     * @param string $password plaintext password to create a salted hash from
     * @param string $salt Deprecated optional custom salt with setting to use
     * @return string|null Salted hashed password
     */
    public function getHashedPassword(string $password, string $salt = null)
    {
        if ($salt !== null) {
            trigger_error(static::class . ': using a custom salt is deprecated.', E_USER_DEPRECATED);
        }
        return $this->getHashedPasswordInternal($password, $salt);
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
     * Checks whether a user's hashed password needs to be replaced with a new hash.
     *
     * This is typically called during the login process when the plain text
     * password is available.  A new hash is needed when the desired iteration
     * count has changed through a change in the variable $this->options['hashCount'].
     *
     * @param string $saltedPW Salted hash to check if it needs an update
     * @return bool TRUE if salted hash needs an update, otherwise FALSE
     */
    public function isHashUpdateNeeded(string $saltedPW): bool
    {
        // Check whether this was an updated password.
        if (strncmp($saltedPW, self::PREFIX, strlen(self::PREFIX)) || !$this->isValidSalt($saltedPW)) {
            return true;
        }
        // Check whether the iteration count used differs from the standard number.
        $iterationCount = $this->getIterationCount($saltedPW);
        return $iterationCount !== null && $iterationCount < $this->options['hash_count'];
    }

    /**
     * Parses the log2 iteration count from a stored hash or setting string.
     *
     * @param string $setting Complete hash or a hash's setting string or to get log2 iteration count from
     * @return int|null Used hashcount for given hash string
     */
    protected function getIterationCount(string $setting)
    {
        $iterationCount = null;
        $setting = substr($setting, strlen(self::PREFIX));
        $firstSplitPos = strpos($setting, '$');
        // Hashcount existing
        if ($firstSplitPos !== false
            && $firstSplitPos <= strlen((string)10000000)
            && is_numeric(substr($setting, 0, $firstSplitPos))
        ) {
            $iterationCount = (int)substr($setting, 0, $firstSplitPos);
        }
        return $iterationCount;
    }

    /**
     * Method creates a salted hash for a given plaintext password
     *
     * @param string $password plaintext password to create a salted hash from
     * @param string $salt Optional custom salt with setting to use
     * @return string|null Salted hashed password
     */
    protected function getHashedPasswordInternal(string $password, string $salt = null)
    {
        $saltedPW = null;
        if ($password !== '') {
            $hashCount = $this->options['hash_count'];
            if (empty($salt) || !$this->isValidSalt($salt)) {
                $salt = $this->getGeneratedSalt();
            } else {
                $hashCount = $this->getIterationCount($salt);
                $salt = $this->getStoredSalt($salt);
            }
            $hash = hash_pbkdf2('sha256', $password, $salt, $hashCount, 0, true);
            $saltWithSettings = $salt;
            // salt without setting
            if (strlen($salt) === 16) {
                $saltWithSettings = self::PREFIX . sprintf('%02u', $hashCount) . '$' . $this->base64Encode($salt, 16);
            }
            $saltedPW = $saltWithSettings . '$' . $this->base64Encode($hash, strlen($hash));
        }
        return $saltedPW;
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
        return GeneralUtility::makeInstance(Random::class)->generateRandomBytes(16);
    }

    /**
     * Parses the salt out of a salt string including settings. If the salt does not include settings
     * it is returned unmodified.
     *
     * @param string $salt
     * @return string
     */
    protected function getStoredSalt(string $salt): string
    {
        if (!strncmp('$', $salt, 1)) {
            if (!strncmp(self::PREFIX, $salt, strlen(self::PREFIX))) {
                $saltParts = GeneralUtility::trimExplode('$', $salt, 4);
                $salt = $saltParts[2];
            }
        }
        return $this->base64Decode($salt);
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
                    $salt = substr($salt, strrpos($salt, '$') + 1);
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
     * Adapted version of base64_encoding for compatibility with python passlib. The output of this function is
     * is identical to base64_encode, except that it uses . instead of +, and omits trailing padding = and whitepsace.
     *
     * @param string $input The string containing bytes to encode.
     * @param int $count The number of characters (bytes) to encode.
     * @return string Encoded string
     */
    protected function base64Encode(string $input, int $count): string
    {
        $input = substr($input, 0, $count);
        return rtrim(str_replace('+', '.', base64_encode($input)), " =\r\n\t\0\x0B");
    }

    /**
     * Adapted version of base64_encoding for compatibility with python passlib. The output of this function is
     * is identical to base64_encode, except that it uses . instead of +, and omits trailing padding = and whitepsace.
     *
     * @param string $value
     * @return string
     */
    protected function base64Decode(string $value): string
    {
        return base64_decode(str_replace('.', '+', $value));
    }

    /**
     * Method returns number of iterations for password stretching.
     *
     * @return int number of iterations for password stretching
     * @deprecated and will be removed in TYPO3 v10.0.
     */
    public function getHashCount(): int
    {
        trigger_error('This method will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        return $this->options['hash_count'];
    }

    /**
     * Method returns maximum allowed number of iterations for password stretching.
     *
     * @return int Maximum allowed number of iterations for password stretching
     * @deprecated and will be removed in TYPO3 v10.0.
     */
    public function getMaxHashCount(): int
    {
        trigger_error('This method will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        return 10000000;
    }

    /**
     * Method returns minimum allowed number of iterations for password stretching.
     *
     * @return int Minimum allowed number of iterations for password stretching
     * @deprecated and will be removed in TYPO3 v10.0.
     */
    public function getMinHashCount(): int
    {
        trigger_error('This method will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        return 1000;
    }

    /**
     * Returns length of a PBKDF2 salt in bytes.
     *
     * @return int Length of a PBKDF2 salt in bytes
     * @deprecated and will be removed in TYPO3 v10.0.
     */
    public function getSaltLength(): int
    {
        trigger_error('This method will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        return 16;
    }

    /**
     * Returns setting string of PBKDF2 salted hashes.
     *
     * @return string Setting string of PBKDF2 salted hashes
     * @deprecated and will be removed in TYPO3 v10.0.
     */
    public function getSetting(): string
    {
        trigger_error('This method will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        return self::PREFIX;
    }

    /**
     * Method sets number of iterations for password stretching.
     *
     * @param int $hashCount number of iterations for password stretching to set
     * @deprecated and will be removed in TYPO3 v10.0.
     */
    public function setHashCount(int $hashCount = null)
    {
        trigger_error('This method will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        if ($hashCount >= 1000 && $hashCount <= 10000000) {
            $this->options['hash_count'] = $hashCount;
        }
    }

    /**
     * Method sets maximum allowed number of iterations for password stretching.
     *
     * @param int $maxHashCount Maximum allowed number of iterations for password stretching to set
     * @deprecated and will be removed in TYPO3 v10.0.
     */
    public function setMaxHashCount(int $maxHashCount = null)
    {
        trigger_error('This method will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        // Empty, max hash count is hard coded to 10000000
    }

    /**
     * Method sets minimum allowed number of iterations for password stretching.
     *
     * @param int $minHashCount Minimum allowed number of iterations for password stretching to set
     * @deprecated and will be removed in TYPO3 v10.0.
     */
    public function setMinHashCount(int $minHashCount = null)
    {
        trigger_error('This method will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        // Empty, max hash count is hard coded to 1000
    }
}
