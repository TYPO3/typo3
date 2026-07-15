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

namespace TYPO3\CMS\Core\Tests\Unit\Utility;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\MailUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class MailUtilityTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    public function breakLinesForEmailReturnsEmptyStringIfEmptyStringIsGiven(): void
    {
        self::assertEmpty(MailUtility::breakLinesForEmail(''));
    }

    #[Test]
    public function breakLinesForEmailReturnsOneLineIfCharWithIsNotExceeded(): void
    {
        $newlineChar = LF;
        $lineWidth = 76;
        $str = 'This text is not longer than 76 chars and therefore will not be broken.';
        $returnString = MailUtility::breakLinesForEmail($str, $newlineChar, $lineWidth);
        self::assertCount(1, explode($newlineChar, $returnString));
    }

    #[Test]
    public function breakLinesForEmailBreaksTextIfCharWithIsExceeded(): void
    {
        $newlineChar = LF;
        $lineWidth = 50;
        $str = 'This text is longer than 50 chars and therefore will be broken.';
        $returnString = MailUtility::breakLinesForEmail($str, $newlineChar, $lineWidth);
        self::assertCount(2, explode($newlineChar, $returnString));
    }

    #[Test]
    public function breakLinesForEmailBreaksTextWithNoSpaceFoundBeforeLimit(): void
    {
        $newlineChar = LF;
        $lineWidth = 10;
        // first space after 20 chars (more than $lineWidth)
        $str = 'abcdefghijklmnopqrst uvwxyz 123456';
        $returnString = MailUtility::breakLinesForEmail($str, $newlineChar, $lineWidth);
        self::assertEquals($returnString, 'abcdefghijklmnopqrst' . LF . 'uvwxyz' . LF . '123456');
    }

    #[Test]
    public function breakLinesForEmailBreaksTextIfLineIsLongerThanTheLineWidth(): void
    {
        $str = 'Mein Link auf eine News (Link: http://zzzzzzzzzzzzz.xxxxxxxxx.de/index.php?id=10&tx_ttnews%5Btt_news%5D=1&cHash=66f5af320da29b7ae1cda49047ca7358)';
        $returnString = MailUtility::breakLinesForEmail($str);
        self::assertEquals($returnString, 'Mein Link auf eine News (Link:' . LF . 'http://zzzzzzzzzzzzz.xxxxxxxxx.de/index.php?id=10&tx_ttnews%5Btt_news%5D=1&cHash=66f5af320da29b7ae1cda49047ca7358)');
    }

    /**
     * Data provider for parseAddressesTest
     */
    public static function parseAddressesProvider(): array
    {
        return [
            'name &ltemail&gt;' => ['name <email@example.org>', ['email@example.org' => 'name']],
            '&lt;email&gt;' => ['<email@example.org>', ['email@example.org']],
            '@localhost' => ['@localhost', []],
            '000@example.com' => ['000@example.com', ['000@example.com']],
            'email' => ['email@example.org', ['email@example.org']],
            'email1,email2' => ['email1@example.org,email2@example.com', ['email1@example.org', 'email2@example.com']],
            'name &ltemail&gt;,email2' => ['name <email1@example.org>,email2@example.com', ['email1@example.org' => 'name', 'email2@example.com']],
            '"last, first" &lt;name@example.org&gt;' => ['"last, first" <email@example.org>', ['email@example.org' => 'last, first']],
            'email,name &ltemail&gt;,"last, first" &lt;name@example.org&gt;' => [
                'email1@example.org, name <email2@example.org>, "last, first" <email3@example.org>',
                [
                    'email1@example.org',
                    'email2@example.org' => 'name',
                    'email3@example.org' => 'last, first',
                ],
            ],
            'empty string' => ['', []],
            'whitespace only' => ['   ', []],
            'bare word without domain' => ['localpart-only', []],
            'invalid address is skipped' => ['invalid,email@example.org', ['email@example.org']],
            'another invalid address is skipped' => ['invalid@, valid@example.org', ['valid@example.org']],
            'consecutive commas' => ['email1@example.org,,email2@example.com', ['email1@example.org', 'email2@example.com']],
            'trailing comma' => ['email@example.org,', ['email@example.org']],
            'address with plus detail' => ['user+tag@example.org', ['user+tag@example.org']],
            'whitespace around angle-addr' => ['name < email@example.org >', ['email@example.org' => 'name']],
            'folded header line' => ["name\r\n <email@example.org>", ['email@example.org' => 'name']],
            'display name with dots' => ['John Q. Public <jqp@example.org>', ['jqp@example.org' => 'John Q. Public']],
            'display name with umlauts' => ['Müller <m@example.org>', ['m@example.org' => 'Müller']],
            'quoted display name with umlauts and comma' => ['"Müller, Björn" <mb@example.org>', ['mb@example.org' => 'Müller, Björn']],
            'quoted-pairs adjacent to multi-byte characters' => [
                '"Grüße \\"Jürgen\\" Ötztal" <j@example.org>',
                ['j@example.org' => 'Grüße "Jürgen" Ötztal'],
            ],
            'multi-byte display name and comment' => [
                '日本語の名前 <jp@example.org>, ted@example.com (コメント, mit Komma)',
                ['jp@example.org' => '日本語の名前', 'ted@example.com'],
            ],
            'quoted-pair in display name' => ['"John \\"Johnny\\" Doe" <jd@example.org>', ['jd@example.org' => 'John "Johnny" Doe']],
            'quoted local part' => ['"john doe"@example.com', ['"john doe"@example.com']],
            'quoted local part with comma' => ['"doe, john"@example.com', ['"doe, john"@example.com']],
            'domain literal' => ['jdoe@[192.168.2.1]', ['jdoe@[192.168.2.1]']],
            'IPv6 domain literal' => ['user@[IPv6:2001:db8::1]', ['user@[IPv6:2001:db8::1]']],
            'comment after address' => ['ted@example.com (Ted Bloggs)', ['ted@example.com']],
            'comment after angle-addr' => ['name <email@example.org> (comment)', ['email@example.org' => 'name']],
            'nested comment' => ['ted@example.com (outer (inner) comment)', ['ted@example.com']],
            'group' => [
                'My Group: "Richard" <richard@example.org>, ted@example.com;',
                ['richard@example.org' => 'Richard', 'ted@example.com'],
            ],
            'empty group' => ['undisclosed-recipients:;', []],
            'group followed by address' => [
                'A Group: a@example.org;, b@example.org',
                ['a@example.org', 'b@example.org'],
            ],
            'group with quoted member names' => [
                'Managers: "last, first" <lf@example.org>, boss@example.org;',
                ['lf@example.org' => 'last, first', 'boss@example.org'],
            ],
            'multiple groups' => [
                'A: a@example.org;, B: b@example.org, "c" <c@example.org>;',
                ['a@example.org', 'b@example.org', 'c@example.org' => 'c'],
            ],
            'comment before address' => ['(Ted Bloggs) ted@example.com', ['ted@example.com']],
            'comment only' => ['(just a comment)', []],
            'comma inside comment' => [
                'ted@example.com (Hello, world), other@example.org',
                ['ted@example.com', 'other@example.org'],
            ],
            'escaped quote and comma in display name' => [
                '"John \\"a,b\\" Doe" <jd@example.org>',
                ['jd@example.org' => 'John "a,b" Doe'],
            ],
            'escaped backslash in display name' => ['"C:\\\\path" <p@example.org>', ['p@example.org' => 'C:\\path']],
            'angle brackets inside quoted display name' => [
                '"Contact <va@example.org>" <real@example.org>',
                ['real@example.org' => 'Contact <va@example.org>'],
            ],
            'display name with apostrophe' => ["Miles O'Brien <mob@example.org>", ['mob@example.org' => "Miles O'Brien"]],
            'obsolete route address' => ['<@relay1.example.com,@relay2.example.com:user@example.org>', ['user@example.org']],
            'route address with display name' => ['name <@relay.example.com:user@example.org>', ['user@example.org' => 'name']],
            'null byte in address' => ["user\0name@example.org", []],
            'null byte in display name' => ["Na\0me <email@example.org>", []],
            'null byte in quoted display name' => ["\"Na\0me\" <email@example.org>", []],
            'null byte in angle-addr' => ["name <email\0@example.org>", []],
            'null byte as list item' => ["a@example.org,\0, b@example.org", ['a@example.org', 'b@example.org']],
            'empty angle address' => ['<>', []],
            'missing closing angle bracket' => ['name <email@example.org', []],
            'display name without angle-addr' => ['name email@example.org', []],
        ];
    }

    #[DataProvider('parseAddressesProvider')]
    #[Test]
    public function parseAddressesTest(string $source, array $addressList): void
    {
        $returnArray = MailUtility::parseAddresses($source);
        self::assertEquals($addressList, $returnArray);
    }

    public static function replyToProvider(): array
    {
        return [
            'only address' => [
                ['defaultMailReplyToAddress' => 'noreply@example.org', 'defaultMailReplyToName' => ''],
                ['noreply@example.org'],
            ],
            'name and address' => [
                ['defaultMailReplyToAddress' => 'noreply@example.org', 'defaultMailReplyToName' => 'John Doe'],
                ['noreply@example.org' => 'John Doe'],
            ],
            'no address' => [
                ['defaultMailReplyToAddress' => '', 'defaultMailReplyToName' => ''],
                [],
            ],
            'invalid address' => [
                ['defaultMailReplyToAddress' => 'foo', 'defaultMailReplyToName' => ''],
                [],
            ],
        ];
    }

    #[DataProvider('replyToProvider')]
    #[Test]
    public function getSystemReplyToTest(array $configuration, array $expectedReplyTo): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['MAIL'] = $configuration;
        $returnArray = MailUtility::getSystemReplyTo();
        self::assertSame($expectedReplyTo, $returnArray);
    }
}
