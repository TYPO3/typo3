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
 * This class implements the 'argon2id' flavour of the php password api.
 *
 * Hashes are identified by the prefix '$argon2id$'.
 *
 * The length of an argon2id password hash (in the form it is received from
 * PHP) depends on the environment.
 *
 * @see PASSWORD_ARGON2ID in https://secure.php.net/manual/en/password.constants.php
 */
class Argon2idPasswordHash extends AbstractArgon2PasswordHash
{
    public function getPasswordAlgorithmName(): string
    {
        return 'PASSWORD_ARGON2ID';
    }

    public function getPasswordHashPrefix(): string
    {
        return '$argon2id$';
    }
}
