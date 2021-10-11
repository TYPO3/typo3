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

namespace Unit\DataHandling\SoftReference;

use TYPO3\CMS\Core\Tests\Unit\DataHandling\SoftReference\AbstractSoftReferenceParserTest;

class UrlSoftReferenceParserTest extends AbstractSoftReferenceParserTest
{
    public function urlSoftReferenceParserTestDataProvider(): array
    {
        return [
            'Simple url matches' => [
                'https://foo-bar.baz',
                [
                    'content' => 'https://foo-bar.baz',
                    'elements' => [
                        2 => [
                            'matchString' => 'https://foo-bar.baz',
                        ],
                    ],
                ],
            ],
            'Valid characters by RFC 3986 match' => [
                'http://ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~:/?#[]@!$&\'()*+,;=.foo',
                [
                    'content' => 'http://ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~:/?#[]@!$&\'()*+,;=.foo',
                    'elements' => [
                        2 => [
                            'matchString' => 'http://ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~:/?#[]@!$&\'()*+,;=.foo',
                        ],
                    ],
                ],
            ],
            'URLs in content match' => [
                'Lorem ipsum https://foo-bar.baz dolor sit',
                [
                    'content' => 'Lorem ipsum https://foo-bar.baz dolor sit',
                    'elements' => [
                        2 => [
                            'matchString' => 'https://foo-bar.baz',
                        ],
                    ],
                ],
            ],
            'FTP URLs match' => [
                'ftp://foo-bar.baz',
                [
                    'content' => 'ftp://foo-bar.baz',
                    'elements' => [
                        2 => [
                            'matchString' => 'ftp://foo-bar.baz',
                        ],
                    ],
                ],
            ],
            'Full URLs match' => [
                'https://foo-bar.baz?foo=bar&baz=fizz#anchor',
                [
                    'content' => 'https://foo-bar.baz?foo=bar&baz=fizz#anchor',
                    'elements' => [
                        2 => [
                            'matchString' => 'https://foo-bar.baz?foo=bar&baz=fizz#anchor',
                        ],
                    ],
                ],
            ],
            'URL encoded URLs match' => [
                'https://foo-bar.baz?foo%3Dbar%26baz%3Dfi%20zz%23anchor',
                [
                    'content' => 'https://foo-bar.baz?foo%3Dbar%26baz%3Dfi%20zz%23anchor',
                    'elements' => [
                        2 => [
                            'matchString' => 'https://foo-bar.baz?foo%3Dbar%26baz%3Dfi%20zz%23anchor',
                        ],
                    ],
                ],
            ],
            'No space character after the last URL matches' => [
                '<p>Lorem Ipsum<br> https://foo.bar.baz/abc/def/ghi/.</p>',
                [
                    'content' => '<p>Lorem Ipsum<br> https://foo.bar.baz/abc/def/ghi/.</p>',
                    'elements' => [
                        2 => [
                            'matchString' => 'https://foo.bar.baz/abc/def/ghi/.',
                        ],
                    ],
                ],
            ],
            // The two cases below are handled by typolink_tag
            'URLs in anchor tag attributes do NOT match' => [
                '<a href="https://foo-bar.baz">some link</a>',
                null,
            ],
            'URLs in link tag attributes do NOT match' => [
                '<link href="https://foo-bar.baz/style.css" rel="stylesheet">',
                null,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider urlSoftReferenceParserTestDataProvider
     */
    public function urlSoftReferenceParserTest(string $content, ?array $expected): void
    {
        $subject = $this->getParserByKey('url');
        $result = $subject->parse('pages', 'url', 1, $content)->toNullableArray();
        self::assertSame($expected, $result);
    }

    /**
     * @test
     */
    public function urlSoftReferenceParserSubstituteTest(): void
    {
        $content = 'My website is: https://www.foo-bar.baz';
        $subject = $this->getParserByKey('url');
        $subject->setParserKey('url', ['subst']);
        $result = $subject->parse('pages', 'url', 1, $content)->toNullableArray();
        self::assertArrayHasKey('subst', $result['elements'][2]);
        self::assertArrayHasKey('tokenID', $result['elements'][2]['subst']);
        unset($result['elements'][2]['subst']['tokenID']);

        $expected = [
            2 => [
                'matchString' => 'https://www.foo-bar.baz',
                'subst' => [
                    'type' => 'string',
                    'tokenValue' => 'https://www.foo-bar.baz',
                ],
            ],
        ];
        self::assertSame($expected, $result['elements']);
    }
}
