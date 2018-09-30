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
 * This class implements the 'bcrypt' flavour of the php password api.
 *
 * Hashes are identified by the prefix '$2y$'.
 *
 * To workaround the limitations of bcrypt (accepts not more than 72
 * chars and truncates on NUL bytes), the plain password is pre-hashed
 * before the actual password-hash is generated/verified.
 *
 * @see PASSWORD_BCRYPT in https://secure.php.net/manual/en/password.constants.php
 */
class BcryptPasswordHash implements PasswordHashInterface
{
    /**
     * Prefix for the password hash
     */
    protected const PREFIX = '$2y$';

    /**
     * Raise default PHP cost (10). At the time of this writing, this leads to
     * 150-200ms computing time on a casual I7 CPU.
     *
     * Note the default values are set again in 'setOptions' below if needed.
     *
     * @var array
     */
    protected $options = [
        'cost' => 12,
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
        // Check options for validity
        if (isset($options['cost'])) {
            if (!$this->isValidBcryptCost((int)$options['cost'])) {
                throw new \InvalidArgumentException(
                    'cost must not be lower than ' . PASSWORD_BCRYPT_DEFAULT_COST . ' or higher than 31',
                    1533902002
                );
            }
            $newOptions['cost'] = (int)$options['cost'];
        }
        $this->options = $newOptions;
    }

    /**
     * Returns true if sha384 for pre-hashing and bcrypt itself is available.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return defined('PASSWORD_BCRYPT')
            && PASSWORD_BCRYPT
            && function_exists('hash')
            && function_exists('hash_algos')
            && in_array('sha384', hash_algos());
    }

    /**
     * Checks if a given plaintext password is correct by comparing it with
     * a given salted hashed password.
     *
     * @param string $plainPW plain text password to compare with salted hash
     * @param string $saltedHashPW Salted hash to compare plain-text password with
     * @return bool
     */
    public function checkPassword(string $plainPW, string $saltedHashPW): bool
    {
        return password_verify($this->processPlainPassword($plainPW), $saltedHashPW);
    }

    /**
     * Extend parent method to workaround bcrypt limitations.
     *
     * @param string $password Plaintext password to create a salted hash from
     * @param string $salt Deprecated optional custom salt to use
     * @return string Salted hashed password
     */
    public function getHashedPassword(string $password, string $salt = null)
    {
        if ($salt !== null) {
            trigger_error(static::class . ': using a custom salt is deprecated in PHP password api and thus ignored.', E_USER_DEPRECATED);
        }
        $hashedPassword = null;
        if ($password !== '') {
            $password = $this->processPlainPassword($password);
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT, $this->options);
            if (!is_string($hashedPassword) || empty($hashedPassword)) {
                throw new InvalidPasswordHashException('Cannot generate password, probably invalid options', 1517174114);
            }
        }
        return $hashedPassword;
    }

    /**
     * Determines if a given string is a valid salted hashed password.
     *
     * @param string $saltedPW String to check
     * @return bool TRUE if it's valid salted hashed password, otherwise FALSE
     */
    public function isValidSaltedPW(string $saltedPW): bool
    {
        $result = false;
        $passwordInfo = password_get_info($saltedPW);
        // Validate the cost value, password_get_info() does not check it
        $cost = (int)substr($saltedPW, 4, 2);
        if (isset($passwordInfo['algo'])
            && $passwordInfo['algo'] === PASSWORD_BCRYPT
            && strncmp($saltedPW, static::PREFIX, strlen(static::PREFIX)) === 0
            && $this->isValidBcryptCost($cost)
        ) {
            $result = true;
        }
        return $result;
    }
    /**
     * Checks whether a user's hashed password needs to be replaced with a new hash.
     *
     * @param string $passString Salted hash to check if it needs an update
     * @return bool TRUE if salted hash needs an update, otherwise FALSE
     */
    public function isHashUpdateNeeded(string $passString): bool
    {
        return password_needs_rehash($passString, PASSWORD_BCRYPT, $this->options);
    }

    /**
     * The plain password is processed through sha384 and then base64
     * encoded. This will produce a 64 characters input to use with
     * password_* functions, which has some advantages:
     * 1. It is close to the (bcrypt-) maximum of 72 character keyspace
     * 2. base64 will never produce NUL bytes (bcrypt truncates on NUL bytes)
     * 3. sha384 is resistant to length extension attacks
     *
     * @param string $password
     * @return string
     */
    protected function processPlainPassword(string $password): string
    {
        return base64_encode(hash('sha384', $password, true));
    }

    /**
     * @see https://github.com/php/php-src/blob/php-7.2.0/ext/standard/password.c#L441-L444
     * @param int $cost
     * @return bool
     */
    protected function isValidBcryptCost(int $cost): bool
    {
        return $cost >= PASSWORD_BCRYPT_DEFAULT_COST && $cost <= 31;
    }

    /**
     * @return array
     * @deprecated and will be removed in TYPO3 v10.0.
     */
    public function getOptions(): array
    {
        trigger_error('This method will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        return $this->options;
    }

    /**
     * Set new memory_cost, time_cost, and thread values.
     *
     * @param array $options
     * @deprecated and will be removed in TYPO3 v10.0.
     */
    public function setOptions(array $options): void
    {
        trigger_error('This method will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        $newOptions = [];

        // Check options for validity, else use hard coded defaults
        if (isset($options['cost'])) {
            if (!$this->isValidBcryptCost((int)$options['cost'])) {
                throw new \InvalidArgumentException(
                    'cost must not be lower than ' . PASSWORD_BCRYPT_DEFAULT_COST . ' or higher than 31',
                    1526042084
                );
            }
            $newOptions['cost'] = (int)$options['cost'];
        } else {
            $newOptions['cost'] = 12;
        }

        $this->options = $newOptions;
    }
}
