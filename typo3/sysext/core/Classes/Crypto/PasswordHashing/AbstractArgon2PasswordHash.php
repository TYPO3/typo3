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
 * This abstract class implements the 'argon2' flavour of the php password api.
 */
abstract class AbstractArgon2PasswordHash implements PasswordHashInterface, Argon2PasswordHashInterface
{
    /**
     * The PHP defaults are rather low ('memory_cost' => 65536, 'time_cost' => 4, 'threads' => 1)
     * We raise that significantly by default. At the time of this writing, with the options
     * below, password_verify() needs about 130ms on an I7 6820 on 2 CPU's (argon2i).
     *
     * We are not raising the amount of threads used, as that might lead to problems on various
     * systems - see #90612
     *
     * @var array
     */
    protected $options = [
        'memory_cost' => 65536,
        'time_cost' => 16,
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
            if (extension_loaded('sodium')) {
                // Libsodium does not support threads, so ignore the
                // options and force single-thread.
                $newOptions['threads'] = 1;
            } elseif ((int)$options['threads'] < PASSWORD_ARGON2_DEFAULT_THREADS) {
                throw new \InvalidArgumentException(
                    'threads must not be lower than ' . PASSWORD_ARGON2_DEFAULT_THREADS,
                    1533899614
                );
            } else {
                $newOptions['threads'] = (int)$options['threads'];
            }
        }
        $this->options = $newOptions;
    }

    /**
     * Returns password algorithm constant from name
     *
     * Since PHP 7.4 Password hashing algorithm identifiers
     * are nullable strings rather than integers.
     *
     * @return int|string|null
     */
    protected function getPasswordAlgorithm()
    {
        return constant($this->getPasswordAlgorithmName());
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
        return defined($this->getPasswordAlgorithmName()) && $this->getPasswordAlgorithm();
    }

    /**
     * Creates a salted hash for a given plaintext password
     *
     * @param string $password Plaintext password to create a salted hash from
     * @return string|null Salted hashed password
     */
    public function getHashedPassword(string $password)
    {
        $hashedPassword = null;
        if ($password !== '') {
            $hashedPassword = password_hash($password, $this->getPasswordAlgorithm(), $this->options);
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
        return password_needs_rehash($passString, $this->getPasswordAlgorithm(), $this->options);
    }

    /**
     * Determines if a given string is a valid salted hashed password.
     *
     * @param string $saltedPW String to check
     * @return bool TRUE if it's valid salted hashed password, otherwise FALSE
     */
    public function isValidSaltedPW(string $saltedPW): bool
    {
        $passwordInfo = password_get_info($saltedPW);

        return
            isset($passwordInfo['algo'])
            && $passwordInfo['algo'] === $this->getPasswordAlgorithm()
            && strncmp($saltedPW, $this->getPasswordHashPrefix(), strlen($this->getPasswordHashPrefix())) === 0;
    }
}
