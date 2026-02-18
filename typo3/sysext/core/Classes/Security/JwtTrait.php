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

namespace TYPO3\CMS\Core\Security;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Trait providing support for JWT using symmetric hash signing.
 *
 * The benefit of using a trait in this particular case is, that defaults in `self::class`
 * (used as context during key derivation) are specific to a particular implementation.
 *
 * @internal
 */
trait JwtTrait
{
    private static function getDefaultSigningAlgorithm(): string
    {
        return 'HS256';
    }

    private static function deriveKey(
        #[\SensitiveParameter]
        string $baseKey,
        string $context,
    ): Key {
        $jwtAlgo = self::getDefaultSigningAlgorithm();
        [$hashAlgo, $length] = match ($jwtAlgo) {
            'HS256' => ['sha256', 32],
            'HS384' => ['sha384', 48],
            'HS512' => ['sha512', 64],
            default => throw new \InvalidArgumentException('Unsupported JWT algorithm: ' . $jwtAlgo, 1774954888),
        };
        return new Key(
            hash_hkdf(
                algo: $hashAlgo,
                key: $baseKey,
                length: $length,
                info: $context,
            ),
            $jwtAlgo
        );
    }

    private static function createSigningKeyFromEncryptionKey(string $context = self::class): Key
    {
        return self::deriveKey(
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] ?? '',
            $context === '' ? self::class : $context
        );
    }

    private static function createSigningSecret(SigningSecretInterface $secret, string $context = self::class): Key
    {
        return self::deriveKey(
            $secret->getSigningSecret(),
            $context === '' ? self::class : $context
        );
    }

    private static function encodeHashSignedJwt(array $payload, Key $key, ?SecretIdentifier $identifier = null): string
    {
        $keyId = $identifier !== null ? json_encode($identifier) : null;
        return JWT::encode($payload, $key->getKeyMaterial(), $key->getAlgorithm(), $keyId);
    }

    private static function decodeJwt(string $jwt, Key $key, bool $associative = false): \stdClass|array
    {
        $payload = JWT::decode($jwt, $key);
        return $associative ? json_decode(json_encode($payload), true) : $payload;
    }

    private static function decodeJwtHeader(string $jwt, string $property): mixed
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }
        $headerRaw = JWT::urlsafeB64Decode($parts[0]);
        if (($header = JWT::jsonDecode($headerRaw)) === null) {
            return null;
        }
        return $header->{$property} ?? null;
    }
}
