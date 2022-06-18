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

class EmailSoftReferenceParserTest extends AbstractSoftReferenceParserTest
{
    public function emailSoftReferenceParserTestDataProvider(): array
    {
        return [
            'Simple email address found' => [
                'foo@bar.baz',
                'content' => 'foo@bar.baz',
                'elements' => [
                    2 => [
                        'matchString' => 'foo@bar.baz',
                    ],
                ],
                'hasMatched' => true,
            ],
            'Multiple email addresses found' => [
                'This is my first email: foo@bar.baz and this is my second email: foo-_2@bar.baz',
                'content' => 'This is my first email: foo@bar.baz and this is my second email: foo-_2@bar.baz',
                'elements' => [
                    2 => [
                        'matchString' => 'foo@bar.baz',
                    ],
                    5 => [
                        'matchString' => 'foo-_2@bar.baz',
                    ],
                ],
                'hasMatched' => true,
            ],
            'Invalid emails are ignored' => [
                'abc-@mail.com
                 abc..def@mail.com
                 .abc@mail.com
                 abc#def@mail.com
                 abc.def@mail.c
                 abc.def@mail#archive.com
                 abc.def@mail
                 abc.def@mail..com',
                'content' => '',
                'elements' => [],
                'hasMatched' => false,
            ],
            'E-Mails in html match' => [
                '<a href="mailto:foo@bar.de">foo@bar.baz</a>',
                'content' => '<a href="mailto:foo@bar.de">foo@bar.baz</a>',
                'elements' => [
                    2 => [
                        'matchString' => 'foo@bar.de',
                    ],
                    5 => [
                        'matchString' => 'foo@bar.baz',
                    ],
                ],
                'hasMatched' => true,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider emailSoftReferenceParserTestDataProvider
     */
    public function emailSoftReferenceParserTest(string $content, string $expectedContent, array $expectedElements, bool $expectedHasMatched): void
    {
        $subject = $this->getParserByKey('email');
        $result = $subject->parse('be_users', 'email', 1, $content);
        self::assertSame($expectedContent, $result->getContent());
        self::assertSame($expectedElements, $result->getMatchedElements());
        self::assertSame($expectedHasMatched, $result->hasMatched());
    }

    /**
     * @test
     */
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
        self::assertSame($expected, $matchedElements);
    }
}
