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

namespace TYPO3\CMS\Core\Tests\Unit\DataHandling\SoftReference;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class EmailSoftReferenceParserTest extends AbstractSoftReferenceParserTestCase
{
    public static function emailSoftReferenceParserTestDataProvider(): array
    {
        return [
            'Simple email address found' => [
                'content' => 'foo@bar.baz',
                'expectedContent' => 'foo@bar.baz',
                'expectedElements' => [
                    2 => [
                        'matchString' => 'foo@bar.baz',
                    ],
                ],
                'expectedHasMatched' => true,
            ],
            'Multiple email addresses found' => [
                'content' => 'This is my first email: foo@bar.baz and this is my second email: foo-_2@bar.baz',
                'expectedContent' => 'This is my first email: foo@bar.baz and this is my second email: foo-_2@bar.baz',
                'expectedElements' => [
                    2 => [
                        'matchString' => 'foo@bar.baz',
                    ],
                    5 => [
                        'matchString' => 'foo-_2@bar.baz',
                    ],
                ],
                'expectedHasMatched' => true,
            ],
            'Invalid emails are ignored' => [
                'content' => 'abc-@mail.com
                 abc..def@mail.com
                 .abc@mail.com
                 abc#def@mail.com
                 abc.def@mail.c
                 abc.def@mail#archive.com
                 abc.def@mail
                 abc.def@mail..com',
                'expectedContent' => '',
                'expectedElements' => [],
                'expectedHasMatched' => false,
            ],
            'E-Mails in html match' => [
                'content' => '<a href="mailto:foo@bar.de">foo@bar.baz</a>',
                'expectedContent' => '<a href="mailto:foo@bar.de">foo@bar.baz</a>',
                'expectedElements' => [
                    2 => [
                        'matchString' => 'foo@bar.de',
                    ],
                    5 => [
                        'matchString' => 'foo@bar.baz',
                    ],
                ],
                'expectedHasMatched' => true,
            ],
        ];
    }

    #[DataProvider('emailSoftReferenceParserTestDataProvider')]
    #[Test]
    public function emailSoftReferenceParserTest(string $content, string $expectedContent, array $expectedElements, bool $expectedHasMatched): void
    {
        $subject = $this->getParserByKey('email');
        $result = $subject->parse('be_users', 'email', 1, $content);
        self::assertEquals($expectedContent, $result->getContent());
        self::assertEquals($expectedElements, $result->getMatchedElements());
        self::assertEquals($expectedHasMatched, $result->hasMatched());
    }

    #[Test]
    public function emailSoftReferenceParserSubstituteTest(): void
    {
        $content = 'My email is: foo@bar.baz';
        $subject = $this->getParserByKey('email');
        $subject->setParserKey('email', ['subst']);
        $result = $subject->parse('be_users', 'email', 1, $content);
        $matchedElements = $result->getMatchedElements();
        self::assertArrayHasKey('subst', $matchedElements[2]);
        self::assertArrayHasKey('tokenID', $matchedElements[2]['subst']);
        unset($matchedElements[2]['subst']['tokenID']);

        $expected = [
            2 => [
                'matchString' => 'foo@bar.baz',
                'subst' => [
                    'type' => 'string',
                    'tokenValue' => 'foo@bar.baz',
                ],
            ],
        ];
        self::assertEquals($expected, $matchedElements);
    }
}
