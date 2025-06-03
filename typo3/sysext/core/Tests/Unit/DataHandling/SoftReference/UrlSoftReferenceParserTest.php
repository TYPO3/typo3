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

final class UrlSoftReferenceParserTest extends AbstractSoftReferenceParserTestCase
{
    public static function urlSoftReferenceParserTestDataProvider(): array
    {
        return [
            'Simple url matches' => [
                'content' => 'https://foo-bar.baz',
                'expectedContent' => 'https://foo-bar.baz',
                'expectedElements' => [
                    0 => [
                        'matchString' => 'https://foo-bar.baz',
                    ],
                ],
            ],
            'Valid characters by RFC 3986 match' => [
                'content' => 'http://ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~:/?#[]@!$&\'()*+,;=.foo',
                'expectedContent' => 'http://ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~:/?#[]@!$&\'()*+,;=.foo',
                'expectedElements' => [
                    0 => [
                        'matchString' => 'http://ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~:/?#[]@!$&\'()*+,;=.foo',
                    ],
                ],
            ],
            'URLs in content match' => [
                'content' => 'Lorem ipsum https://foo-bar.baz dolor sit',
                'expectedContent' => 'Lorem ipsum https://foo-bar.baz dolor sit',
                'expectedElements' => [
                    0 => [
                        'matchString' => 'https://foo-bar.baz',
                    ],
                ],
            ],
            'FTP URLs match' => [
                'content' => 'ftp://foo-bar.baz',
                'expectedContent' => 'ftp://foo-bar.baz',
                'expectedElements' => [
                    0 => [
                        'matchString' => 'ftp://foo-bar.baz',
                    ],
                ],
            ],
            'Full URLs match' => [
                'content' => 'https://foo-bar.baz?foo=bar&baz=fizz#anchor',
                'expectedContent' => 'https://foo-bar.baz?foo=bar&baz=fizz#anchor',
                'expectedElements' => [
                    0 => [
                        'matchString' => 'https://foo-bar.baz?foo=bar&baz=fizz#anchor',
                    ],
                ],
            ],
            'URL encoded URLs match' => [
                'content' => 'https://foo-bar.baz?foo%3Dbar%26baz%3Dfi%20zz%23anchor',
                'expectedContent' => 'https://foo-bar.baz?foo%3Dbar%26baz%3Dfi%20zz%23anchor',
                'expectedElements' => [
                    0 => [
                        'matchString' => 'https://foo-bar.baz?foo%3Dbar%26baz%3Dfi%20zz%23anchor',
                    ],
                ],
            ],
            'No space character after the last URL matches' => [
                'content' => '<p>Lorem Ipsum<br> https://foo.bar.baz/abc/def/ghi/.</p>',
                'expectedContent' => '<p>Lorem Ipsum<br> https://foo.bar.baz/abc/def/ghi/.</p>',
                'expectedElements' => [
                    0 => [
                        'matchString' => 'https://foo.bar.baz/abc/def/ghi/.',
                    ],
                ],
            ],
            // The two cases below are handled by typolink_tag
            'URLs in anchor tag attributes do NOT match' => [
                'content' => '<a href="https://foo-bar.baz">some link</a>',
                'expectedContent' => '',
                'expectedElements' => [],
            ],
            'URLs in link tag attributes do NOT match' => [
                'content' => '<link href="https://foo-bar.baz/style.css" rel="stylesheet">',
                'expectedContent' => '',
                'expectedElements' => [],
            ],
            'Domain with umlaut' => [
                'content' => 'https://fö-bar.baz/blah',
                'expectedContent' => 'https://fö-bar.baz/blah',
                'expectedElements' => [
                    0 => [
                        'matchString' => 'https://fö-bar.baz/blah',
                    ],
                ],
            ],
            'Domain with umlaut and uppercase' => [
                'content' => 'https://fö-bÄr.baz/blah',
                'expectedContent' => 'https://fö-bÄr.baz/blah',
                'expectedElements' => [
                    0 => [
                        'matchString' => 'https://fö-bÄr.baz/blah',
                    ],
                ],
            ],
            'Domain with umlaut and additional text' => [
                'content' => 'abc https://fö-bar.baz/blah hello',
                'expectedContent' => 'abc https://fö-bar.baz/blah hello',
                'expectedElements' => [
                    0 => [
                        'matchString' => 'https://fö-bar.baz/blah',
                    ],
                ],
            ],
            'Domain with umlaut - encoded (IDN converted into ASCII string, ACE form)' => [
                'content' => 'https://xn--f-bar-jua.baz/blah',
                'expectedContent' => 'https://xn--f-bar-jua.baz/blah',
                'expectedElements' => [
                    0 => [
                        'matchString' => 'https://xn--f-bar-jua.baz/blah',
                    ],
                ],
            ],
            'Text without domain but protocol' => [
                'content' => 'http://',
                'expectedContent' => '',
                'expectedElements' => [],
            ],
            'Text without domain but protocol in quotes' => [
                'content' => '"http://"',
                'expectedContent' => '',
                'expectedElements' => [],
            ],
            'Domain with multiple domains in a-tags' => [
                'content' => '<a href="t3://page?uid=1">https://domain.tld/aaa/</a><a href="t3://page?uid=2">https://www.domain.tl/bbb/ccc/</a><ul><li>Foo <a href="t3://page?uid=3">ftp://domain.tld/</a></li></ul>Foo',
                'expectedContent' => '<a href="t3://page?uid=1">https://domain.tld/aaa/</a><a href="t3://page?uid=2">https://www.domain.tl/bbb/ccc/</a><ul><li>Foo <a href="t3://page?uid=3">ftp://domain.tld/</a></li></ul>Foo',
                'expectedElements' => [
                    0 => [
                        'matchString' => 'https://domain.tld/aaa/',
                    ],
                    1 => [
                        'matchString' => 'https://www.domain.tl/bbb/ccc/',
                    ],
                    2 => [
                        'matchString' => 'ftp://domain.tld/',
                    ],
                ],
            ],
            'Multiple domains in a line, seperated in different ways' => [
                'content' => 'Domain list: https://domain.tld/aaa/ https://www.domain.tl/bbb/ccc/ aaa https://www.domain.tl/ddd/ <b>https://www.domain.tl/eee/</b>    https://www.domain.tl/fff/',
                'expectedContent' => 'Domain list: https://domain.tld/aaa/ https://www.domain.tl/bbb/ccc/ aaa https://www.domain.tl/ddd/ <b>https://www.domain.tl/eee/</b>    https://www.domain.tl/fff/',
                'expectedElements' => [
                    0 => [
                        'matchString' => 'https://domain.tld/aaa/',
                    ],
                    1 => [
                        'matchString' => 'https://www.domain.tl/bbb/ccc/',
                    ],
                    2 => [
                        'matchString' => 'https://www.domain.tl/ddd/',
                    ],
                    3 => [
                        'matchString' => 'https://www.domain.tl/eee/',
                    ],
                    4 => [
                        'matchString' => 'https://www.domain.tl/fff/',
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('urlSoftReferenceParserTestDataProvider')]
    #[Test]
    public function urlSoftReferenceParserTest(string $content, string $expectedContent, array $expectedElements): void
    {
        $subject = $this->getParserByKey('url');
        $result = $subject->parse('pages', 'url', 1, $content);
        self::assertSame($expectedContent, $result->getContent());
        self::assertEquals($expectedElements, $result->getMatchedElements());
    }

    #[Test]
    public function urlSoftReferenceParserSubstituteTest(): void
    {
        $content = 'My website is: https://www.foo-bar.baz';
        $subject = $this->getParserByKey('url');
        $subject->setParserKey('url', ['subst']);
        $result = $subject->parse('pages', 'url', 1, $content);
        $matchedElements = $result->getMatchedElements();
        self::assertArrayHasKey('subst', $matchedElements[0]);
        self::assertArrayHasKey('tokenID', $matchedElements[0]['subst']);
        unset($matchedElements[0]['subst']['tokenID']);

        $expected = [
            0 => [
                'matchString' => 'https://www.foo-bar.baz',
                'subst' => [
                    'type' => 'string',
                    'tokenValue' => 'https://www.foo-bar.baz',
                ],
            ],
        ];
        self::assertEquals($expected, $matchedElements);
    }
}
