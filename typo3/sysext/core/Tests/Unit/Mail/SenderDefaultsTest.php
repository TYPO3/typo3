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

namespace TYPO3\CMS\Core\Tests\Unit\Mail;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Mail\SenderDefaults;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SenderDefaultsTest extends UnitTestCase
{
    public static function fromMailSettingsDataProvider(): \Generator
    {
        yield 'address and name' => [
            ['defaultMailFromAddress' => 'sender@example.org', 'defaultMailFromName' => 'Sender'],
            new Address('sender@example.org', 'Sender'),
            null,
        ];
        yield 'address without name' => [
            ['defaultMailFromAddress' => 'sender@example.org', 'defaultMailFromName' => ''],
            new Address('sender@example.org'),
            null,
        ];
        yield 'non-string name is ignored' => [
            ['defaultMailFromAddress' => 'sender@example.org', 'defaultMailFromName' => ['Sender']],
            new Address('sender@example.org'),
            null,
        ];
        yield 'reply-to address and name' => [
            ['defaultMailFromAddress' => 'sender@example.org', 'defaultMailReplyToAddress' => 'reply@example.org', 'defaultMailReplyToName' => 'Reply'],
            new Address('sender@example.org'),
            new Address('reply@example.org', 'Reply'),
        ];
        yield 'reply-to address without name' => [
            ['defaultMailFromAddress' => 'sender@example.org', 'defaultMailReplyToAddress' => 'reply@example.org', 'defaultMailReplyToName' => ''],
            new Address('sender@example.org'),
            new Address('reply@example.org'),
        ];
        yield 'invalid reply-to address is ignored' => [
            ['defaultMailFromAddress' => 'sender@example.org', 'defaultMailReplyToAddress' => 'reply', 'defaultMailReplyToName' => 'Reply'],
            new Address('sender@example.org'),
            null,
        ];
    }

    #[DataProvider('fromMailSettingsDataProvider')]
    #[Test]
    public function fromMailSettingsReadsConfiguredSender(array $mailSettings, Address $expectedFrom, ?Address $expectedReplyTo): void
    {
        $subject = SenderDefaults::fromMailSettings($mailSettings);
        self::assertEquals($expectedFrom, $subject->from);
        self::assertEquals($expectedReplyTo, $subject->replyTo);
    }

    #[Test]
    public function fromMailSettingsFallsBackToNoReplyAddressWithoutValidSender(): void
    {
        $subject = SenderDefaults::fromMailSettings(['defaultMailFromAddress' => 'invalid', 'defaultMailFromName' => 'Sender']);
        self::assertStringStartsWith('no-reply@', $subject->from->getAddress());
        self::assertSame('Sender', $subject->from->getName());
    }
}
