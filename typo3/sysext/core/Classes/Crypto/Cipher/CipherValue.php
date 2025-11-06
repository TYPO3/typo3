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

use TYPO3\CMS\Core\Utility\StringUtility;

final readonly class CipherValue implements \Stringable
{
    public static function fromSerialized(string $value): self
    {
        $data = json_decode(StringUtility::base64urlDecode($value) ?: '', true);
        $nonce = StringUtility::base64urlDecode($data['nonce'] ?? '');
        $cipher = StringUtility::base64urlDecode($data['cipher'] ?? '');
        if (empty($nonce) || empty($cipher)) {
            throw new CipherException('Incorrect encoded message format', 1762450821);
        }
        return new self($nonce, $cipher);
    }

    public function __construct(public string $nonce, public string $cipher)
    {
        if (strlen($nonce) !== SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES) {
            throw new CipherException('Incorrect nonce byte length', 1762450477);
        }
    }

    public function __toString(): string
    {
        return $this->encode();
    }

    public function encode(): string
    {
        $data = [
            'nonce' => StringUtility::base64urlEncode($this->nonce),
            'cipher' => StringUtility::base64urlEncode($this->cipher),
        ];
        try {
            return StringUtility::base64urlEncode(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        } catch (\JsonException) {
            throw new CipherException('Failed to encode cipher value', 1763068727);
        }
    }
}
