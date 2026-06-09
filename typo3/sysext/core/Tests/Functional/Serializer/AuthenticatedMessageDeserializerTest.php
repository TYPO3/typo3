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

namespace TYPO3\CMS\Core\Tests\Functional\Serializer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Serializer\AuthenticatedMessageDeserializer;
use TYPO3\CMS\Core\Serializer\Exception\DeserializerException;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class AuthenticatedMessageDeserializerTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    private AuthenticatedMessageDeserializer $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->get(AuthenticatedMessageDeserializer::class);
    }

    public static function dataIsRoundtrippedDataProvider(): array
    {
        return [
            'string' => ['hello world', 'test-secret'],
            'integer' => [42, 'test-secret'],
            'float' => [3.14, 'test-secret'],
            'null' => [null, 'test-secret'],
            'array' => [['a' => 1, 'b' => [2, 3]], 'test-secret'],
            'stdClass' => [new \stdClass(), 'test-secret'],
        ];
    }

    #[DataProvider('dataIsRoundtrippedDataProvider')]
    #[Test]
    public function dataIsRoundtripped(mixed $payload, string $secret): void
    {
        $serialized = $this->subject->serialize($payload, $secret);
        self::assertEquals($payload, $this->subject->deserialize($serialized, $secret));
    }

    #[Test]
    public function falseValueIsRoundtripped(): void
    {
        $serialized = $this->subject->serialize(false, 'test-secret');
        self::assertFalse($this->subject->deserialize($serialized, 'test-secret'));
    }

    #[Test]
    public function tamperedPayloadThrowsException(): void
    {
        $serialized = $this->subject->serialize(new \stdClass(), 'test-secret');
        // Prepend a byte to invalidate the HMAC while keeping a recognisable class token
        $tampered = 'X' . $serialized;
        $this->expectException(DeserializerException::class);
        $this->expectExceptionCode(1780317744);
        $this->subject->deserialize($tampered, 'test-secret');
    }

    #[Test]
    public function wrongAdditionalSecretThrowsException(): void
    {
        $serialized = $this->subject->serialize(new \stdClass(), 'secret-a');
        $this->expectException(DeserializerException::class);
        $this->expectExceptionCode(1780317744);
        $this->subject->deserialize($serialized, 'secret-b');
    }

    #[Test]
    public function unauthenticatedScalarIsDeserialized(): void
    {
        // A raw serialized scalar has no HMAC and no class tokens — falls back to
        // unserialize($payload, ['allowed_classes' => false])
        self::assertSame('hello', $this->subject->deserialize(serialize('hello'), 'any-secret'));
    }

    #[Test]
    public function unauthenticatedFalseValueIsDeserialized(): void
    {
        self::assertFalse($this->subject->deserialize(serialize(false), 'any-secret'));
    }

    #[Test]
    public function unauthenticatedObjectPayloadThrowsException(): void
    {
        // A raw serialized object without an HMAC contains class tokens and must be rejected
        $this->expectException(DeserializerException::class);
        $this->expectExceptionCode(1780317744);
        $this->subject->deserialize(serialize(new \stdClass()), 'any-secret');
    }
}
