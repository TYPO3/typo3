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

namespace TYPO3\CMS\Frontend\Tests\Unit\Html;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ConsumableNonce;
use TYPO3\CMS\Frontend\Html\HtmlWorker;
use TYPO3\CMS\Frontend\Typolink\LinkFactory;
use TYPO3\CMS\Frontend\Typolink\LinkResult;
use TYPO3\CMS\Frontend\Typolink\UnableToLinkException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class HtmlWorkerTest extends UnitTestCase
{
    private function createSubject(): HtmlWorker
    {
        $linkFactory = self::createStub(LinkFactory::class);
        $linkFactory->method('createUri')->willReturnCallback(
            static function (string $uri): LinkResult {
                if ($uri === 't3://page?uid=1') {
                    return new LinkResult('page', 'https://typo3.localhost/');
                }
                if ($uri === 't3://page?uid=2') {
                    return new LinkResult('page', 'https://typo3.localhost/two')
                        ->withTarget('_blank')
                        ->withAttribute('rel', 'noopener');
                }
                throw new UnableToLinkException('Unable to link', 1763030000);
            }
        );
        return new HtmlWorker($linkFactory);
    }

    #[Test]
    public function toStringReturnsEmptyStringWithoutParsing(): void
    {
        self::assertSame('', (string)$this->createSubject());
    }

    public static function parseAndSerializeDataProvider(): array
    {
        return [
            'empty string' => [
                '',
                '',
            ],
            'text only' => [
                'just text',
                'just text',
            ],
            'multiple top-level nodes' => [
                'before<p>one</p>between<p>two</p>after',
                'before<p>one</p>between<p>two</p>after',
            ],
            'nested elements with attributes' => [
                '<div class="a" id="b"><span data-x="1">x</span></div>',
                '<div class="a" id="b"><span data-x="1">x</span></div>',
            ],
            'uppercase tags and attributes' => [
                '<P CLASS="x">text</P>',
                '<p class="x">text</p>',
            ],
            'single-quoted attribute' => [
                '<a href=\'https://typo3.localhost/\'>visit</a>',
                '<a href="https://typo3.localhost/">visit</a>',
            ],
            'void elements' => [
                'a<br>b<br/>c<img src="x.png" alt="">',
                'a<br>b<br>c<img src="x.png" alt="">',
            ],
            'boolean attribute' => [
                '<input type="checkbox" disabled checked="checked">',
                '<input type="checkbox" disabled="" checked="checked">',
            ],
            'angle brackets in attribute' => [
                '<span title="a &lt; b > c">x</span>',
                '<span title="a < b > c">x</span>',
            ],
            'self-closing custom element' => [
                '<custom-el/>after',
                '<custom-el>after</custom-el>',
            ],
            'table cell without table' => [
                '<td>cell</td>',
                'cell',
            ],
            'text directly in table' => [
                '<table>text<tr><td>cell</td></tr></table>',
                'text<table><tbody><tr><td>cell</td></tr></tbody></table>',
            ],
            'svg' => [
                '<svg viewBox="0 0 1 1"><path d="M0 0"/></svg>',
                '<svg viewBox="0 0 1 1"><path d="M0 0"></path></svg>',
            ],
            'unclosed paragraphs' => [
                '<p>one<p>two',
                '<p>one</p><p>two</p>',
            ],
            'special characters and entities in text' => [
                '&amp; &lt; &gt; &quot; &nbsp; &copy; ä',
                '&amp; &lt; &gt; " &nbsp; © ä',
            ],
            'special characters in attribute' => [
                '<span title="&quot;a&quot; &amp; b">x</span>',
                '<span title="&quot;a&quot; &amp; b">x</span>',
            ],
            'script content' => [
                '<script>if (a < b && c > d) { x = "</p>"; }</script>',
                '<script>if (a < b && c > d) { x = "</p>"; }</script>',
            ],
            'style content' => [
                '<style>a > b { content: "&"; }</style>',
                '<style>a > b { content: "&"; }</style>',
            ],
            'comment' => [
                '<!-- comment -->text',
                '<!-- comment -->text',
            ],
        ];
    }

    #[DataProvider('parseAndSerializeDataProvider')]
    #[Test]
    public function parseAndSerialize(string $html, string $expectation): void
    {
        self::assertSame($expectation, (string)$this->createSubject()->parse($html));
    }

    #[Test]
    public function parseReplacesPreviouslyParsedMarkup(): void
    {
        $subject = $this->createSubject();
        $subject->parse('<p>first</p>');
        self::assertSame('<p>second</p>', (string)$subject->parse('<p>second</p>'));
    }

    public static function addNonceAttributeDataProvider(): array
    {
        return [
            'no node names' => [
                [],
                '<style>a{}</style>',
                '<style>a{}</style>',
            ],
            'attribute-less style only' => [
                ['style'],
                '<style>a{}</style><style media="print">b{}</style><script>c</script>',
                '<style nonce="nonce-value">a{}</style><style media="print">b{}</style><script>c</script>',
            ],
            'nested style' => [
                ['style'],
                '<div><style>a{}</style></div>',
                '<div><style nonce="nonce-value">a{}</style></div>',
            ],
            'multiple node names' => [
                ['style', 'script'],
                '<style>a{}</style><script>c</script><script src="x.js"></script>',
                '<style nonce="nonce-value">a{}</style><script nonce="nonce-value">c</script><script src="x.js"></script>',
            ],
        ];
    }

    #[DataProvider('addNonceAttributeDataProvider')]
    #[Test]
    public function addNonceAttribute(array $nodeNames, string $html, string $expectation): void
    {
        $subject = $this->createSubject()->parse($html);
        self::assertSame($expectation, (string)$subject->addNonceAttribute('nonce-value', ...$nodeNames));
    }

    #[Test]
    public function addNonceAttributeConsumesNonce(): void
    {
        $nonce = new ConsumableNonce(str_repeat('n', 40));
        $subject = $this->createSubject()->parse('<style>a{}</style>');
        self::assertSame(
            '<style nonce="' . str_repeat('n', 40) . '">a{}</style>',
            (string)$subject->addNonceAttribute($nonce, 'style')
        );
        self::assertSame(1, $nonce->countInline());
    }

    public static function transformUriDataProvider(): array
    {
        return [
            'a.href' => [
                'a.href',
                '<a href="t3://page?uid=1" class="page">visit</a>',
                '<a href="https://typo3.localhost/" class="page">visit</a>',
            ],
            'no scheme is skipped' => [
                'a.href',
                '<a href="/path/visit.html">visit</a><a href="#top">top</a>',
                '<a href="/path/visit.html">visit</a><a href="#top">top</a>',
            ],
            'other node is ignored' => [
                'a.href',
                '<link href="t3://page?uid=1"><a href="t3://page?uid=1">visit</a>',
                '<link href="t3://page?uid=1"><a href="https://typo3.localhost/">visit</a>',
            ],
            'element without attribute is ignored' => [
                'a.href',
                '<a name="t3://page?uid=1">visit</a>',
                '<a name="t3://page?uid=1">visit</a>',
            ],
            'empty node name matches any element' => [
                '.href',
                '<a href="t3://page?uid=1">visit</a><link href="t3://page?uid=1">',
                '<a href="https://typo3.localhost/">visit</a><link href="https://typo3.localhost/">',
            ],
            'asterisk node name matches any element' => [
                '*.data-uri',
                '<div data-uri="t3://page?uid=1">x</div><span data-uri="t3://page?uid=1">y</span>',
                '<div data-uri="https://typo3.localhost/">x</div><span data-uri="https://typo3.localhost/">y</span>',
            ],
            'img.src receives href value' => [
                'img.src',
                '<img src="t3://page?uid=1" alt="">',
                '<img src="https://typo3.localhost/" alt="">',
            ],
            'additional link result attributes are added' => [
                'a.href',
                '<a href="t3://page?uid=2">visit</a>',
                '<a href="https://typo3.localhost/two" target="_blank" rel="noopener">visit</a>',
            ],
            'multiple selectors with whitespace' => [
                ' a.href , div.data-uri ',
                '<a href="t3://page?uid=1">a</a><div data-uri="t3://page?uid=1">b</div>',
                '<a href="https://typo3.localhost/">a</a><div data-uri="https://typo3.localhost/">b</div>',
            ],
            'nested elements' => [
                'a.href',
                '<ul><li><p>Go <a href="t3://page?uid=1">here</a></p></li></ul>',
                '<ul><li><p>Go <a href="https://typo3.localhost/">here</a></p></li></ul>',
            ],
            'invalid selector without dot is ignored' => [
                'a',
                '<a href="t3://page?uid=1">visit</a>',
                '<a href="t3://page?uid=1">visit</a>',
            ],
            'invalid selector with two dots is ignored' => [
                'a.href.x',
                '<a href="t3://page?uid=1">visit</a>',
                '<a href="t3://page?uid=1">visit</a>',
            ],
            'node name with selector syntax is not interpreted' => [
                'a]|//*[@x.href',
                '<a href="t3://page?uid=1" x="1">visit</a>',
                '<a href="t3://page?uid=1" x="1">visit</a>',
            ],
            'node and attribute names are matched case-insensitively' => [
                'A.HREF',
                '<a href="t3://page?uid=1">visit</a>',
                '<a href="https://typo3.localhost/">visit</a>',
            ],
            'empty selector' => [
                '',
                '<a href="t3://page?uid=1">visit</a>',
                '<a href="t3://page?uid=1">visit</a>',
            ],
        ];
    }

    #[DataProvider('transformUriDataProvider')]
    #[Test]
    public function transformUri(string $selector, string $html, string $expectation): void
    {
        $subject = $this->createSubject()->parse($html);
        self::assertSame($expectation, (string)$subject->transformUri($selector));
    }

    #[Test]
    public function transformUriWithoutParsingReturnsEmptyString(): void
    {
        self::assertSame('', (string)$this->createSubject()->transformUri('a.href'));
    }

    public static function transformUriOnFailureDataProvider(): array
    {
        $html = '<p>Go <a href="t3://page?uid=9876" class="x">visit <b>more</b></a> now</p>'
            . '<a href="t3://page?uid=9875">second</a>';
        return [
            'no flags' => [
                0,
                $html,
                $html,
            ],
            'remove tag' => [
                HtmlWorker::REMOVE_TAG_ON_FAILURE,
                $html,
                '<p>Go  now</p>',
            ],
            'remove attribute' => [
                HtmlWorker::REMOVE_ATTR_ON_FAILURE,
                $html,
                '<p>Go <a class="x">visit <b>more</b></a> now</p><a>second</a>',
            ],
            'remove enclosure' => [
                HtmlWorker::REMOVE_ENCLOSURE_ON_FAILURE,
                $html,
                '<p>Go visit <b>more</b> now</p>second',
            ],
            'remove tag takes precedence' => [
                HtmlWorker::REMOVE_TAG_ON_FAILURE | HtmlWorker::REMOVE_ATTR_ON_FAILURE | HtmlWorker::REMOVE_ENCLOSURE_ON_FAILURE,
                $html,
                '<p>Go  now</p>',
            ],
            'remove attribute takes precedence over enclosure' => [
                HtmlWorker::REMOVE_ATTR_ON_FAILURE | HtmlWorker::REMOVE_ENCLOSURE_ON_FAILURE,
                $html,
                '<p>Go <a class="x">visit <b>more</b></a> now</p><a>second</a>',
            ],
            'remove tag of consecutive elements' => [
                HtmlWorker::REMOVE_TAG_ON_FAILURE,
                '<a href="t3://page?uid=9876">a</a><a href="t3://page?uid=9875">b</a><a href="t3://page?uid=1">c</a>',
                '<a href="https://typo3.localhost/">c</a>',
            ],
            'remove enclosure of nested matching elements' => [
                HtmlWorker::REMOVE_ENCLOSURE_ON_FAILURE,
                '<div data-uri="t3://page?uid=9876">a<div data-uri="t3://page?uid=9875">b</div></div>',
                'a<div data-uri="t3://page?uid=9875">b</div>',
            ],
        ];
    }

    #[DataProvider('transformUriOnFailureDataProvider')]
    #[Test]
    public function transformUriOnFailure(int $flags, string $html, string $expectation): void
    {
        $selector = str_contains($html, 'data-uri') ? 'div.data-uri' : 'a.href';
        $subject = $this->createSubject()->parse($html);
        self::assertSame($expectation, (string)$subject->transformUri($selector, $flags));
    }
}
