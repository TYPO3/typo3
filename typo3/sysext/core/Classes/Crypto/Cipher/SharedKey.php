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

namespace TYPO3\CMS\Core\Crypto\Cipher;

/**
 * Holds the secret key to be used with XChaCha20-Poly1305
 * (and basically other Sodium-based algorithms), having 32 bytes.
 */
final readonly class SharedKey
{
    public function __construct(#[\SensitiveParameter] public string $value)
    {
        if (strlen($this->value) !== SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES) {
            throw new CipherException(
                sprintf(
                    'Length of key value must be %d bytes',
                    SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES
                ),
                1762508248
            );
        }
    }
}
