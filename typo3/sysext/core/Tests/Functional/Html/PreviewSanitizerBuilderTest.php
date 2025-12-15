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

namespace TYPO3\CMS\Core\Tests\Functional\Html;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Html\PreviewSanitizerBuilder;
use TYPO3\CMS\Core\Html\SanitizerBuilderFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PreviewSanitizerBuilderTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    public static function isSanitizedDataProvider(): array
    {
        return [
            '#010' => [
                '<a href="https://example.com">Link text</a>',
                'Link text',
            ],
            '#011' => [
                '<a href="https://example.com" class="button">Click here</a>',
                'Click here',
            ],
            '#012' => [
                '<p>Before <a href="https://example.com">link</a> after</p>',
                '<p>Before link after</p>',
            ],
            '#020' => [
                '<a href="javascript:alert(1)">XSS attempt</a>',
                'XSS attempt',
            ],
            '#021' => [
                '<a href="data:text/html,<script>alert(1)</script>">XSS</a>',
                'XSS',
            ],
            '#022' => [
                '<a onclick="alert(1)" href="https://example.com">XSS</a>',
                'XSS',
            ],
            '#023' => [
                '<a href="https://example.com" onerror="alert(1)">XSS</a>',
                'XSS',
            ],

            // Heading tags should be unwrapped
            '#030' => [
                '<h1>Heading 1</h1>',
                'Heading 1',
            ],
            '#031' => [
                '<h2>Heading 2</h2>',
                'Heading 2',
            ],
            '#032' => [
                '<h3>Heading 3</h3>',
                'Heading 3',
            ],
            '#033' => [
                '<h4>Heading 4</h4>',
                'Heading 4',
            ],
            '#034' => [
                '<h5>Heading 5</h5>',
                'Heading 5',
            ],
            '#035' => [
                '<h6>Heading 6</h6>',
                'Heading 6',
            ],
            '#036' => [
                '<p><h1>Nested heading</h1></p>',
                '<p></p>Nested heading',
            ],
            '#040' => [
                '<h1 class="title">Heading with class</h1>',
                'Heading with class',
            ],
            '#041' => [
                '<h2 id="section-1">Heading with ID</h2>',
                'Heading with ID',
            ],
            '#042' => [
                '<h3 data-section="intro">Heading with data attribute</h3>',
                'Heading with data attribute',
            ],
            '#050' => [
                '<A href="https://example.com">Link</A>',
                'Link',
            ],
            '#051' => [
                '<H1>Heading</H1>',
                'Heading',
            ],
            '#052' => [
                '<H2>Mixed Case</H2>',
                'Mixed Case',
            ],
            '#053' => [
                '<a HREF="https://example.com">Link</a>',
                'Link',
            ],
            '#060' => [
                '<h1><a href="https://example.com">Nested</a></h1>',
                '<a href="https://example.com">Nested</a>',
            ],
            '#061' => [
                '<a href="https://example.com"><h2>Reverse nested</h2></a>',
                '<h2>Reverse nested</h2>',
            ],
            '#062' => [
                '<h1><a href="https://example.com">Text <strong>bold</strong></a></h1>',
                '<a href="https://example.com">Text <strong>bold</strong></a>',
            ],
            '#070' => [
                '<p>Normal paragraph</p>',
                '<p>Normal paragraph</p>',
            ],
            '#071' => [
                '<div><strong>Bold</strong> and <em>italic</em></div>',
                '<div><strong>Bold</strong> and <em>italic</em></div>',
            ],
            '#072' => [
                '<ul><li>Item 1</li><li>Item 2</li></ul>',
                '<ul><li>Item 1</li><li>Item 2</li></ul>',
            ],
            '#073' => [
                '<blockquote>Quote text</blockquote>',
                '<blockquote>Quote text</blockquote>',
            ],
            '#080' => [
                '<h1 onclick="alert(1)">XSS Heading</h1>',
                'XSS Heading',
            ],
            '#081' => [
                '<h2 onload="alert(1)">XSS Heading</h2>',
                'XSS Heading',
            ],
            '#082' => [
                '<h3><script>alert(1)</script></h3>',
                '<script>alert(1)</script>',
            ],
            '#090' => [
                '<div><h1>Title</h1><p>Content with <a href="https://example.com">link</a></p></div>',
                '<div>Title<p>Content with link</p></div>',
            ],
            '#091' => [
                '<section><h2 class="title">Section</h2><p>Text</p></section>',
                '<section>Section<p>Text</p></section>',
            ],

            // Edge cases
            '#100' => [
                '<a href="https://example.com"></a>',
                '',
            ],
            '#101' => [
                '<h1></h1>',
                '',
            ],
            '#102' => [
                '<a href="https://example.com">   </a>',
                '   ',
            ],
            '#103' => [
                '<h1>   </h1>',
                '   ',
            ],
            '#110' => [
                '<a href="https://example.com">Link 1</a> <a href="https://example.com">Link 2</a>',
                'Link 1 Link 2',
            ],
            '#111' => [
                '<h1>First</h1><h2>Second</h2><h3>Third</h3>',
                'FirstSecondThird',
            ],
            '#120' => [
                '<a href="https://example.com"><img src="/image.jpg" alt="Image"></a>',
                '<img src="/image.jpg" alt="Image">',
            ],
            '#121' => [
                '<a href="https://example.com"><span class="icon">Icon</span> Text</a>',
                '<span class="icon">Icon</span> Text',
            ],
            '#130' => [
                '<unknown>content</unknown>',
                '&lt;unknown&gt;content&lt;/unknown&gt;',
            ],
            '#131' => [
                '<h1><unknown>nested</unknown></h1>',
                '<unknown>nested</unknown>',
            ],
            '#200' => [
                '<h1>Article Title</h1><p>This is a preview with <a href="/full-article">read more</a></p>',
                'Article Title<p>This is a preview with read more</p>',
            ],
            '#201' => [
                '<div class="preview"><h2>News Item</h2><p>Summary text</p></div>',
                '<div class="preview">News Item<p>Summary text</p></div>',
            ],
        ];
    }

    #[DataProvider('isSanitizedDataProvider')]
    #[Test]
    public function isSanitized(string $payload, string $expectation): void
    {
        $factory = new SanitizerBuilderFactory();
        $builder = $factory->build('preview');
        $sanitizer = $builder->build();
        self::assertSame($expectation, $sanitizer->sanitize($payload));
    }

    #[Test]
    public function sanitizerUnwrapsLinksAndHeadings(): void
    {
        $builder = new PreviewSanitizerBuilder();
        $sanitizer = $builder->build();

        $input = '<p>Text with <a href="https://example.com">link</a> and <h2>heading</h2></p>';
        $result = $sanitizer->sanitize($input);

        self::assertStringNotContainsString('<a', $result);
        self::assertStringNotContainsString('</a>', $result);
        self::assertStringNotContainsString('<h2', $result);
        self::assertStringNotContainsString('</h2>', $result);
        self::assertStringContainsString('link', $result);
        self::assertStringContainsString('heading', $result);
    }

    #[Test]
    public function sanitizerPreventXssInUnwrappedTags(): void
    {
        $builder = new PreviewSanitizerBuilder();
        $sanitizer = $builder->build();

        $xssPayloads = [
            '<a href="javascript:alert(1)">XSS</a>',
            '<a onclick="alert(1)">XSS</a>',
            '<h1 onclick="alert(1)">XSS</h1>',
            '<h2 onload="alert(1)">XSS</h2>',
        ];

        foreach ($xssPayloads as $payload) {
            $result = $sanitizer->sanitize($payload);

            self::assertStringNotContainsString('javascript:', $result);
            self::assertStringNotContainsString('onclick', $result);
            self::assertStringNotContainsString('onload', $result);
            self::assertStringNotContainsString('onerror', $result);
            self::assertStringContainsString('XSS', $result);
        }
    }

    #[Test]
    public function scriptTagBehavior(): void
    {
        $builder = new PreviewSanitizerBuilder();
        $sanitizer = $builder->build();

        self::assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', $sanitizer->sanitize('<script>alert(1)</script>'));
    }
}
