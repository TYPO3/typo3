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
 * This class implements the 'argon2i' flavour of the php password api.
 *
 * Hashes are identified by the prefix '$argon2i$'.
 *
 * The length of a argon2i password hash (in the form it is received from
 * PHP) depends on the environment.
 *
 * @see PASSWORD_ARGON2I in https://secure.php.net/manual/en/password.constants.php
 */
class Argon2iPasswordHash implements PasswordHashInterface
{
    /**
     * Prefix for the password hash.
     */
    protected const PREFIX = '$argon2i$';

    /**
     * The PHP defaults are rather low ('memory_cost' => 65536, 'time_cost' => 4, 'threads' => 1)
     * We raise that significantly by default. At the time of this writing, with the options
     * below, password_verify() needs about 130ms on an I7 6820 on 2 CPU's.
     *
     * We are not raising the amount of threads used, as that might lead to problems on various
     * systems - see #90612
     *
     * Note the default values are set again in 'setOptions' below if needed.
     *
     * @var array
     */
    protected $options = [
        'memory_cost' => 65536,
        'time_cost' => 16
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
        if (isset($options['memory_cost'])) {
            if ((int)$options['memory_cost'] < PASSWORD_ARGON2_DEFAULT_MEMORY_COST) {
                throw new \InvalidArgumentException(
                    'memory_cost must not be lower than ' . PASSWORD_ARGON2_DEFAULT_MEMORY_COST,
                    1533899612
                );
            }
            $newOptions['memory_cost'] = (int)$options['memory_cost'];
        }
        if (isset($options['time_cost'])) {
            if ((int)$options['time_cost'] < PASSWORD_ARGON2_DEFAULT_TIME_COST) {
                throw new \InvalidArgumentException(
                    'time_cost must not be lower than ' . PASSWORD_ARGON2_DEFAULT_TIME_COST,
                    1533899613
                );
            }
            $newOptions['time_cost'] = (int)$options['time_cost'];
        }
        if (isset($options['threads'])) {
            if ((int)$options['threads'] < PASSWORD_ARGON2_DEFAULT_THREADS) {
                throw new \InvalidArgumentException(
                    'threads must not be lower than ' . PASSWORD_ARGON2_DEFAULT_THREADS,
                    1533899614
                );
            }
            $newOptions['threads'] = (int)$options['threads'];
        }
        $this->options = $newOptions;
    }

    /**
     * Checks if a given plaintext password is correct by comparing it with
     * a given salted hashed password.
     *
     * @param string $plainPW plain text password to compare with salted hash
     * @param string $saltedHashPW Salted hash to compare plain-text password with
     * @return bool TRUE, if plaintext password is correct, otherwise FALSE
     */
    public function checkPassword(string $plainPW, string $saltedHashPW): bool
    {
        return password_verify($plainPW, $saltedHashPW);
    }

    /**
     * Returns true if PHP is compiled '--with-password-argon2' so
     * the hash algorithm is available.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return defined('PASSWORD_ARGON2I') && PASSWORD_ARGON2I;
    }

    /**
     * Creates a salted hash for a given plaintext password
     *
     * @param string $password Plaintext password to create a salted hash from
     * @param string $salt Deprecated optional custom salt to use
     * @return string|null Salted hashed password
     */
    public function getHashedPassword(string $password, string $salt = null)
    {
        if ($salt !== null) {
            trigger_error(static::class . ': using a custom salt is deprecated in PHP password api and ignored.', E_USER_DEPRECATED);
        }
        $hashedPassword = null;
        if ($password !== '') {
            $hashedPassword = password_hash($password, PASSWORD_ARGON2I, $this->options);
            if (!is_string($hashedPassword) || empty($hashedPassword)) {
                throw new InvalidPasswordHashException('Cannot generate password, probably invalid options', 1526052118);
            }
        }
        return $hashedPassword;
    }

    /**
     * Checks whether a user's hashed password needs to be replaced with a new hash,
     * for instance if options changed.
     *
     * @param string $passString Salted hash to check if it needs an update
     * @return bool TRUE if salted hash needs an update, otherwise FALSE
     */
    public function isHashUpdateNeeded(string $passString): bool
    {
        return password_needs_rehash($passString, PASSWORD_ARGON2I, $this->options);
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
        if (isset($passwordInfo['algo'])
            && $passwordInfo['algo'] === PASSWORD_ARGON2I
            && strncmp($saltedPW, static::PREFIX, strlen(static::PREFIX)) === 0
        ) {
            $result = true;
        }
        return $result;
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
        if (isset($options['memory_cost'])) {
            if ((int)$options['memory_cost'] < PASSWORD_ARGON2_DEFAULT_MEMORY_COST) {
                throw new \InvalidArgumentException(
                    'memory_cost must not be lower than ' . PASSWORD_ARGON2_DEFAULT_MEMORY_COST,
                    1526042080
                );
            }
            $newOptions['memory_cost'] = (int)$options['memory_cost'];
        } else {
            $newOptions['memory_cost'] = 16384;
        }

        if (isset($options['time_cost'])) {
            if ((int)$options['time_cost'] < PASSWORD_ARGON2_DEFAULT_TIME_COST) {
                throw new \InvalidArgumentException(
                    'time_cost must not be lower than ' . PASSWORD_ARGON2_DEFAULT_TIME_COST,
                    1526042081
                );
            }
            $newOptions['time_cost'] = (int)$options['time_cost'];
        } else {
            $newOptions['time_cost'] = 16;
        }

        if (isset($options['threads'])) {
            if ((int)$options['threads'] < PASSWORD_ARGON2_DEFAULT_THREADS) {
                throw new \InvalidArgumentException(
                    'threads must not be lower than ' . PASSWORD_ARGON2_DEFAULT_THREADS,
                    1526042082
                );
            }
            $newOptions['threads'] = (int)$options['threads'];
        } else {
            $newOptions['threads'] = 2;
        }

        $this->options = $newOptions;
    }
}
