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
use TYPO3\CMS\Core\Serializer\DeserializationService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DeserializationServiceTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

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
        $subject = new DeserializationService();
        self::assertEquals($expectedClassNames, $subject->parseClassNames($payload));
    }
}
