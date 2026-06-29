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

namespace TYPO3\CMS\Core\Serializer;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Crypto\HashAlgo;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Exception\Crypto\InvalidHashStringException;
use TYPO3\CMS\Core\Serializer\Exception\DeserializerException;

/**
 * @internal Only to be used by TYPO3 core
 */
#[Autoconfigure(public: true)]
final readonly class AuthenticatedMessageDeserializer
{
    private const HASH_ALGO = HashAlgo::SHA3_384;

    public function __construct(
        private HashService $hashService,
        private DeserializationService $deserializationService,
    ) {}

    public function serialize(mixed $payload, string $additionalSecret): string
    {
        return $this->hashService->appendHmac(
            serialize($payload),
            $additionalSecret,
            self::HASH_ALGO
        );
    }

    public function deserialize(string $payload, string $additionalSecret): mixed
    {
        try {
            $serialized = $this->hashService->validateAndStripHmac(
                $payload,
                $additionalSecret,
                self::HASH_ALGO
            );
        } catch (InvalidHashStringException $e) {
            $classNames = $this->deserializationService->parseClassNames($payload);
            // in case the payload does not contain any class names, continue with
            // a secure deserialization attempt, not allowing any class names
            if ($classNames === []) {
                return @unserialize($payload, ['allowed_classes' => false]);
            }
            throw new DeserializerException(
                'Authenticated Message Deserialization failed',
                1780317744,
                $e
            );
        }
        // explicitly allowing all classes here after successful HMAC validation
        /* @phpstan-ignore unserialize.allowedClasses.insecure (Integrity check already happens via HMAC validation) */
        return unserialize($serialized, ['allowed_classes' => true]);
    }
}
