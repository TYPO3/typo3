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

namespace TYPO3\CMS\Core\Tests\Fixtures\Crypto\PasswordHashing;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashInterface;

/**
 * A special noop password "hashing" algorithm to be used in tests only - it is unsecure and optimized for speed.
 *
 * @internal
 */
class NoopPasswordHash implements PasswordHashInterface
{
    protected const PREFIX = '$SHA1$';

    public function __construct()
    {
        if (!Environment::getContext()->isTesting()) {
            throw new \LogicException(
                sprintf('The password hashing algorithm %s must not be used outside of testing context!', __CLASS__),
                1655551062
            );
        }
    }

    public function checkPassword(string $plainPW, string $saltedHashPW): bool
    {
        return $this->getHashedPassword($plainPW) === $saltedHashPW;
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function getHashedPassword(string $password)
    {
        return self::PREFIX . sha1($password);
    }

    public function isHashUpdateNeeded(string $passString): bool
    {
        return false;
    }

    public function isValidSaltedPW(string $saltedPW): bool
    {
        return !strncmp(self::PREFIX, $saltedPW, strlen(self::PREFIX));
    }

    public static function registerNoopPasswordHash(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['availablePasswordHashAlgorithms'][__CLASS__] = __CLASS__;
    }

    public static function unregisterNoopPasswordHash(): void
    {
        unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['availablePasswordHashAlgorithms'][__CLASS__]);
    }
}
