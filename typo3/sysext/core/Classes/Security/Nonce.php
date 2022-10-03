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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Number used once...
 *
 * @internal
 */
class Nonce implements SigningSecretInterface
{
    use JwtTrait;

    protected const MIN_BYTES = 40;

    public readonly string $b64;
    public readonly \DateTimeImmutable $time;

    public static function create(int $length = self::MIN_BYTES): self
    {
        return GeneralUtility::makeInstance(self::class, random_bytes(max(self::MIN_BYTES, $length)));
    }

    public static function fromHashSignedJwt(string $jwt): self
    {
        try {
            $payload = self::decodeJwt($jwt, self::createSigningKeyFromEncryptionKey(Nonce::class), true);
            return GeneralUtility::makeInstance(
                self::class,
                StringUtility::base64urlDecode($payload['nonce'] ?? ''),
                \DateTimeImmutable::createFromFormat(\DateTimeImmutable::RFC3339, $payload['time'] ?? null)
            );
        } catch (\Throwable $t) {
            throw new NonceException('Could not reconstitute nonce', 1651771351, $t);
        }
    }

    public function __construct(public readonly string $binary, \DateTimeImmutable $time = null)
    {
        if (strlen($this->binary) < self::MIN_BYTES) {
            throw new \LogicException(
                sprintf('Value must have at least %d bytes', self::MIN_BYTES),
                1651785134
            );
        }
        $this->b64 = StringUtility::base64urlEncode($this->binary);
        // drop microtime, second is the minimum date-interval
        $this->time = \DateTimeImmutable::createFromFormat(
            \DateTimeImmutable::RFC3339,
            ($time ?? new \DateTimeImmutable())->format(\DateTimeImmutable::RFC3339)
        );
    }

    public function getSigningIdentifier(): SecretIdentifier
    {
        return new SecretIdentifier('nonce', StringUtility::base64urlEncode(md5($this->binary, true)));
    }

    public function getSigningSecret(): string
    {
        return hash('sha256', $this->binary);
    }

    public function toHashSignedJwt(): string
    {
        $payload = [
            'nonce' => $this->b64,
            'time' => $this->time->format(\DateTimeImmutable::RFC3339),
        ];
        return self::encodeHashSignedJwt($payload, self::createSigningKeyFromEncryptionKey(Nonce::class));
    }
}
