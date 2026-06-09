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

use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\Psr7\FnStream;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\FormProtection\BackendFormProtection;
use TYPO3\CMS\Core\Serializer\DenyListDeserializer;
use TYPO3\CMS\Core\Serializer\Exception\DeserializerException;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DenyListDeserializerTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    private DenyListDeserializer $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['deserialization']['allowedClassNames'] = [Fixtures\ClassWithDestructor::class];
        $this->subject = $this->get(DenyListDeserializer::class);
    }

    #[Test]
    public function scalarPayloadIsDeserialized(): void
    {
        self::assertSame('hello', $this->subject->deserialize(serialize('hello')));
    }

    #[Test]
    public function falseValueIsDeserialized(): void
    {
        self::assertFalse($this->subject->deserialize(serialize(false)));
    }

    #[Test]
    public function allowedClassIsDeserialized(): void
    {
        self::assertInstanceOf(\stdClass::class, $this->subject->deserialize(serialize(new \stdClass())));
    }

    #[Test]
    public function malformedPayloadThrows(): void
    {
        $this->expectException(DeserializerException::class);
        $this->expectExceptionCode(1768212616);
        $this->subject->deserialize('s:foo:broken');
    }

    #[Test]
    public function knownGadgetIsBlocked(): void
    {
        // Craft the payload without instantiating the class (its __destruct() writes to disk)
        $className = FileCookieJar::class;
        $serialized = 'O:' . strlen($className) . ':"' . $className . '":0:{}';
        $this->expectException(DeserializerException::class);
        $this->expectExceptionCode(1778594101);
        $this->subject->deserialize($serialized);
    }

    #[Test]
    public function classWithWakeupIsBlocked(): void
    {
        // FnStream has a user-defined __wakeup() and must be blocked before unserialize runs
        $className = FnStream::class;
        $serialized = 'O:' . strlen($className) . ':"' . $className . '":0:{}';
        $this->expectException(DeserializerException::class);
        $this->expectExceptionCode(1778594101);
        $this->subject->deserialize($serialized);
    }

    #[Test]
    public function classWithOnlyBlockSerializationTraitIsAllowed(): void
    {
        // BackendFormProtection inherits BlockSerializationTrait's __wakeup (throws on deserialization)
        // but carries no other gadget methods — it must NOT be blocked by the deny-list.
        // The exception must come from BlockSerializationTrait::__wakeup (code 1588784142),
        // not from the deserializer deny-list (code 1778594101).
        $className = BackendFormProtection::class;
        $serialized = 'O:' . strlen($className) . ':"' . $className . '":0:{}';
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionCode(1588784142);
        $this->subject->deserialize($serialized);
    }

    public static function deserializePopulatesCacheWithHmacSignedEntryDataProvider(): iterable
    {
        yield 'stdClass' => ['stdClass', false];
        yield 'FileCookieJar' => [FileCookieJar::class, true];
    }

    #[Test]
    #[DataProvider('deserializePopulatesCacheWithHmacSignedEntryDataProvider')]
    public function deserializePopulatesCacheWithHmacSignedEntry(string $className, bool $denied): void
    {
        $serialized = 'O:' . strlen($className) . ':"' . $className . '":0:{}';
        try {
            $this->subject->deserialize($serialized);
        } catch (\Throwable) {
            // ignore the throwable
        }

        $cache = $this->get('cache.core');
        $cacheKey = 'DenyListDeserializer_' . hash('xxh128', $className);
        self::assertTrue(
            $cache->has($cacheKey),
            'Cache entry must be present after first encounter'
        );

        $entry = $cache->require($cacheKey);
        self::assertIsArray($entry);
        self::assertArrayHasKey('denied', $entry);
        self::assertArrayHasKey('hmac', $entry);
        self::assertSame($denied, $entry['denied']);

        // The stored HMAC must be valid and cover both the class name and the deny status
        $hashService = $this->get(HashService::class);
        $hmacPayload = sprintf('%s:%d', $className, $denied);
        self::assertTrue(
            $hashService->validateHmac($hmacPayload, DenyListDeserializer::class, $entry['hmac']),
            'HMAC of the cache entry must be valid'
        );
    }

    #[Test]
    public function allowedClassNamesAreConsidered(): void
    {
        // the class name was configured to be allowed in the `setUp` method of this test
        $className = Fixtures\ClassWithDestructor::class;
        $serialized = 'O:' . strlen($className) . ':"' . $className . '":0:{}';
        $result = $this->subject->deserialize($serialized);
        self::assertInstanceOf($className, $result);
    }
}
