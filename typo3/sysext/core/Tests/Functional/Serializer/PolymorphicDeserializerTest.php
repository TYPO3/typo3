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
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Header\HeaderInterface;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\RawMessage;
use TYPO3\CMS\Core\Serializer\Exception\PolymorphicDeserializerException;
use TYPO3\CMS\Core\Serializer\PolymorphicDeserializer;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PolymorphicDeserializerTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    private PolymorphicDeserializer $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new PolymorphicDeserializer();
    }

    #[Test]
    public function spooledMailMessageCanBeDeserialized(): void
    {
        $payload = file_get_contents(__DIR__ . '/Fixtures/BoE4HIpmXv.message');
        $result = $this->subject->deserialize($payload, [
            SentMessage::class,
            RawMessage::class,
            Envelope::class,
            Address::class,
            Headers::class,
            HeaderInterface::class,
        ]);
        self::assertInstanceOf(SentMessage::class, $result);
    }

    public static function spooledMailMessageCannotBeDeserializedDataProvider(): \Generator
    {
        yield [
            file_get_contents(__DIR__ . '/Fixtures/BoE4HIpmXv.message'),
            'Invalid class name "TYPO3\CMS\Core\Mail\FluidEmail" found in payload',
            1767987405,
        ];
        yield [
            's:foo:broken',
            'Syntax error in payload, unable to de-serialize: unserialize(): Error at offset 0 of 12 bytes',
            1768212616,
        ];
    }

    #[Test]
    #[DataProvider('spooledMailMessageCannotBeDeserializedDataProvider')]
    public function spooledMailMessageCannotBeDeserialized(string $payload, string $expectedExceptionMessage, int $expectedExceptionCode): void
    {
        $this->expectException(PolymorphicDeserializerException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        $this->expectExceptionCode($expectedExceptionCode);

        $result = $this->subject->deserialize($payload, [SentMessage::class]);
        self::assertInstanceOf(SentMessage::class, $result);
    }

    public static function canParseClassNamesDataProvider(): iterable
    {
        yield 'simple example' => [
            'a:2:{i:0;O:10:"ValidClass":0:{}i:1;s:21:" O:12:"InvalidClass":0:{} ";i:2;O:333:"IncorrectLengthClass":0:{}}',
            ['ValidClass'],
        ];
        yield 'serialized mail message' => [
            file_get_contents(__DIR__ . '/Fixtures/BoE4HIpmXv.message'),
            [
                \Symfony\Component\Mailer\SentMessage::class,
                \TYPO3\CMS\Core\Mail\FluidEmail::class,
                \Symfony\Component\Mime\Header\Headers::class,
                \Symfony\Component\Mime\Header\MailboxListHeader::class,
                \Symfony\Component\Mime\Address::class,
                \Symfony\Component\Mime\Header\MailboxListHeader::class,
                \Symfony\Component\Mime\Address::class,
                \Symfony\Component\Mime\Header\UnstructuredHeader::class,
                \Symfony\Component\Mime\Header\UnstructuredHeader::class,
                \Symfony\Component\Mime\RawMessage::class,
                \Symfony\Component\Mailer\DelayedEnvelope::class,
            ],
        ];
    }

    #[Test]
    #[DataProvider('canParseClassNamesDataProvider')]
    public function canParseClassNames(string $payload, array $expectedClassNames): void
    {
        self::assertEquals($expectedClassNames, $this->subject->parseClassNames($payload));
    }

    #[Test]
    public function falseValueCanBeDeserialized(): void
    {
        $payload = 'b:0;';
        $result = $this->subject->deserialize($payload, []);

        self::assertFalse($result);
    }
}
