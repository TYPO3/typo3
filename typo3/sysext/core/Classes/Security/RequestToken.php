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

/**
 * @internal
 */
class RequestToken
{
    use JwtTrait;

    public const PARAM_NAME = '__RequestToken';
    public const HEADER_NAME = 'X-TYPO3-RequestToken';

    public readonly string $scope;
    public readonly \DateTimeImmutable $time;
    /**
     * @var array<int|string, mixed>
     */
    public readonly array $params;

    /**
     * Identifier that was used for signing, filled when decoding.
     */
    private ?SecretIdentifier $signingSecretIdentifier = null;

    public static function create(string $scope): self
    {
        return GeneralUtility::makeInstance(self::class, $scope);
    }

    public static function fromHashSignedJwt(string $jwt, SigningSecretInterface|SigningSecretResolver $secret): self
    {
        // invokes resolver to retrieve corresponding secret
        // a hint was stored in the `kid` (keyId) property of the JWT header
        if ($secret instanceof SigningSecretResolver) {
            try {
                $kid = (string)self::decodeJwtHeader($jwt, 'kid');
                $identifier = SecretIdentifier::fromJson($kid);
                $secret = $secret->findByIdentifier($identifier);
            } catch (\Throwable $t) {
                throw new RequestTokenException('Could not reconstitute request token', 1664202134, $t);
            }
            if ($secret === null) {
                throw new RequestTokenException('Could not reconstitute request token', 1664202135);
            }
        }

        try {
            $payload = self::decodeJwt($jwt, self::createSigningSecret($secret, RequestToken::class), true);
            $subject = GeneralUtility::makeInstance(
                self::class,
                $payload['scope'] ?? '',
                \DateTimeImmutable::createFromFormat(\DateTimeImmutable::RFC3339, $payload['time'] ?? null),
                $payload['params'] ?? []
            );
            $subject->signingSecretIdentifier = $secret->getSigningIdentifier();
            return $subject;
        } catch (\Throwable $t) {
            throw new RequestTokenException('Could not reconstitute request token', 1651771352, $t);
        }
    }

    public function __construct(string $scope, \DateTimeImmutable $time = null, array $params = [])
    {
        $this->scope = $scope;
        // drop microtime, second is the minimum date-interval
        $this->time = \DateTimeImmutable::createFromFormat(
            \DateTimeImmutable::RFC3339,
            ($time ?? new \DateTimeImmutable())->format(\DateTimeImmutable::RFC3339)
        );
        $this->params = $params;
    }

    public function toHashSignedJwt(SigningSecretInterface $secret): string
    {
        $payload = [
            'scope' => $this->scope,
            'time' => $this->time->format(\DateTimeImmutable::RFC3339),
            'params' => $this->params,
        ];
        return self::encodeHashSignedJwt(
            $payload,
            self::createSigningSecret($secret, RequestToken::class),
            $secret->getSigningIdentifier()
        );
    }

    public function withParams(array $params): self
    {
        return GeneralUtility::makeInstance(self::class, $this->scope, $this->time, $params);
    }

    public function withMergedParams(array $params): self
    {
        return $this->withParams(array_merge_recursive($this->params, $params));
    }

    public function getSigningSecretIdentifier(): ?SecretIdentifier
    {
        return $this->signingSecretIdentifier;
    }
}
