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

namespace TYPO3\CMS\Backend\Tests\Unit\Preview;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Preview\RecordFieldPreviewProcessor;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Domain\RawRecord;
use TYPO3\CMS\Core\Domain\Record\ComputedProperties;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class RecordFieldPreviewProcessorTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    private RecordFieldPreviewProcessor $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $tcaSchemaFactory = $this->createMock(TcaSchemaFactory::class);
        $uriBuilder = $this->createMock(UriBuilder::class);
        $iconFactory = $this->createMock(IconFactory::class);

        $this->subject = new RecordFieldPreviewProcessor(
            $tcaSchemaFactory,
            $uriBuilder,
            $iconFactory
        );
    }

    public static function xssPayloadsForPrepareTextDataProvider(): iterable
    {
        // prepareText() uses strip_tags() first, so HTML tags are REMOVED
        // Then htmlspecialchars() is applied to escape any remaining special characters
        yield 'script tag - content preserved, tags stripped' => [
            '<script>alert("XSS")</script>',
            'alert(&quot;XSS&quot;)',
        ];
        yield 'img onerror - self-closing tag removed entirely' => [
            '<img src=x onerror=alert("XSS")>',
            '', // Empty string after strip_tags (but not null because input was not empty)
        ];
        yield 'svg onload - self-closing tag removed entirely' => [
            '<svg onload=alert("XSS")>',
            '',
        ];
        yield 'anchor with javascript protocol - text content preserved' => [
            '<a href="javascript:alert(\'XSS\')">click me</a>',
            'click me',
        ];
        yield 'div with event handler - text content preserved' => [
            '<div onmouseover="alert(\'XSS\')">hover text</div>',
            'hover text',
        ];
        yield 'nested tags - inner content preserved' => [
            '<p><strong>Bold</strong> and <em>italic</em></p>',
            'Bold and italic',
        ];
        // Note: strip_tags() interprets <, > as a malformed tag and removes it
        yield 'special characters after strip_tags - ampersand and quotes escaped' => [
            'Text with &, " and \' chars',
            'Text with &amp;, &quot; and &#039; chars',
        ];
        yield 'already encoded entities - double encoded' => [
            '&lt;script&gt;',
            '&lt;script&gt;', // strip_tags keeps these, htmlspecialchars with double_encode=false keeps them
        ];
    }

    #[Test]
    #[DataProvider('xssPayloadsForPrepareTextDataProvider')]
    public function prepareTextStripsTagsAndEscapesContent(string $payload, string $expected): void
    {
        $record = $this->createRecord(['bodytext' => $payload]);
        $result = $this->subject->prepareText($record, 'bodytext');

        self::assertSame($expected, $result);
    }

    #[Test]
    public function prepareTextNeverContainsExecutableScriptTags(): void
    {
        $payloads = [
            '<script>alert("XSS")</script>',
            '<SCRIPT>alert("XSS")</SCRIPT>',
            '<scr<script>ipt>alert("XSS")</scr</script>ipt>',
            '<<script>script>alert("XSS")<</script>/script>',
        ];

        foreach ($payloads as $payload) {
            $record = $this->createRecord(['bodytext' => $payload]);
            $result = $this->subject->prepareText($record, 'bodytext');

            // Result should never contain unescaped <script or </script
            if ($result !== null) {
                self::assertStringNotContainsString('<script', strtolower($result));
                self::assertStringNotContainsString('</script', strtolower($result));
            }
        }
    }

    public static function xssPayloadsForPreparePlainHtmlDataProvider(): iterable
    {
        // preparePlainHtml() does NOT strip tags, it ESCAPES them
        // This means < becomes &lt; and > becomes &gt;
        yield 'script tag - fully escaped' => [
            '<script>alert("XSS")</script>',
            '&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;',
        ];
        yield 'img onerror - fully escaped' => [
            '<img src=x onerror=alert("XSS")>',
            '&lt;img src=x onerror=alert(&quot;XSS&quot;)&gt;',
        ];
        yield 'svg onload - fully escaped' => [
            '<svg onload=alert("XSS")>',
            '&lt;svg onload=alert(&quot;XSS&quot;)&gt;',
        ];
        yield 'special characters - escaped' => [
            '<>&"\'',
            '&lt;&gt;&amp;&quot;&#039;',
        ];
        yield 'attribute breakout attempt - escaped' => [
            '" onclick="alert(\'XSS\')" data-x="',
            '&quot; onclick=&quot;alert(&#039;XSS&#039;)&quot; data-x=&quot;',
        ];
    }

    #[Test]
    #[DataProvider('xssPayloadsForPreparePlainHtmlDataProvider')]
    public function preparePlainHtmlEscapesAllHtmlCharacters(string $payload, string $expected): void
    {
        $record = $this->createRecord(['content' => $payload]);
        $result = $this->subject->preparePlainHtml($record, 'content');

        self::assertSame($expected, $result);
    }

    #[Test]
    public function preparePlainHtmlNeverContainsUnescapedAngleBrackets(): void
    {
        $payloads = [
            '<script>alert("XSS")</script>',
            '<img src=x onerror=alert("XSS")>',
            '<div onclick="evil()">',
            '"><script>alert(1)</script>',
        ];

        foreach ($payloads as $payload) {
            $record = $this->createRecord(['content' => $payload]);
            $result = $this->subject->preparePlainHtml($record, 'content');

            self::assertIsString($result);
            // The only < or > should be in the safe <br /> tag
            $withoutBr = str_replace('<br />', '', $result);
            self::assertStringNotContainsString('<', $withoutBr, 'Unescaped < found in: ' . $result);
            self::assertStringNotContainsString('>', $withoutBr, 'Unescaped > found in: ' . $result);
        }
    }

    #[Test]
    public function prepareTextPreservesNewlinesAsBrTags(): void
    {
        $input = "Line 1\nLine 2\nLine 3";
        $record = $this->createRecord(['bodytext' => $input]);

        $result = $this->subject->prepareText($record, 'bodytext');

        self::assertIsString($result);
        self::assertStringContainsString('<br', $result);
        self::assertStringContainsString('Line 1', $result);
        self::assertStringContainsString('Line 2', $result);
        self::assertStringContainsString('Line 3', $result);
    }

    #[Test]
    public function preparePlainHtmlPreservesNewlinesAsBrTags(): void
    {
        $input = "Line 1\nLine 2\nLine 3";
        $record = $this->createRecord(['content' => $input]);

        $result = $this->subject->preparePlainHtml($record, 'content');

        self::assertIsString($result);
        self::assertStringContainsString('<br />', $result);
        self::assertStringContainsString('Line 1', $result);
        self::assertStringContainsString('Line 2', $result);
        self::assertStringContainsString('Line 3', $result);
    }

    #[Test]
    public function preparePlainHtmlLimitsNumberOfLines(): void
    {
        $lines = [];
        for ($i = 1; $i <= 150; $i++) {
            $lines[] = "Line $i";
        }
        $input = implode("\n", $lines);
        $record = $this->createRecord(['content' => $input]);

        $result = $this->subject->preparePlainHtml($record, 'content', 50);

        self::assertIsString($result);
        self::assertStringContainsString('Line 50', $result);
        self::assertStringNotContainsString('Line 51', $result);
        self::assertStringNotContainsString('Line 150', $result);
    }

    #[Test]
    public function prepareTextTruncatesLongContent(): void
    {
        $input = str_repeat('A', 2000);
        $record = $this->createRecord(['bodytext' => $input]);

        $result = $this->subject->prepareText($record, 'bodytext', 100);

        self::assertIsString($result);
        // Should be truncated (100 chars + possible ellipsis)
        self::assertLessThan(150, strlen($result));
    }

    #[Test]
    public function prepareTextReturnsNullForEmptyField(): void
    {
        $record = $this->createRecord(['bodytext' => '']);
        $result = $this->subject->prepareText($record, 'bodytext');

        self::assertNull($result);
    }

    #[Test]
    public function prepareTextReturnsNullForNonExistentField(): void
    {
        $record = $this->createRecord([]);
        $result = $this->subject->prepareText($record, 'nonexistent');

        self::assertNull($result);
    }

    #[Test]
    public function preparePlainHtmlReturnsNullForEmptyField(): void
    {
        $record = $this->createRecord(['content' => '']);
        $result = $this->subject->preparePlainHtml($record, 'content');

        self::assertNull($result);
    }

    #[Test]
    public function preparePlainHtmlReturnsNullForNonExistentField(): void
    {
        $record = $this->createRecord([]);
        $result = $this->subject->preparePlainHtml($record, 'nonexistent');

        self::assertNull($result);
    }

    #[Test]
    public function prepareTextEscapesQuotesCorrectly(): void
    {
        $input = 'Text with "double" and \'single\' quotes';
        $record = $this->createRecord(['bodytext' => $input]);

        $result = $this->subject->prepareText($record, 'bodytext');

        self::assertIsString($result);
        // Double quotes should be escaped as &quot;
        self::assertStringContainsString('&quot;double&quot;', $result);
        // Single quotes should be escaped as &#039; (ENT_QUOTES flag)
        self::assertStringContainsString('&#039;single&#039;', $result);
    }

    #[Test]
    public function preparePlainHtmlEscapesAmpersandCorrectly(): void
    {
        $input = 'Text with & ampersand';
        $record = $this->createRecord(['content' => $input]);

        $result = $this->subject->preparePlainHtml($record, 'content');

        self::assertIsString($result);
        // Raw ampersand should be escaped
        self::assertStringContainsString('&amp;', $result);
        // Original & should not remain unescaped
        self::assertStringNotContainsString('& ampersand', $result);
    }

    #[Test]
    public function prepareTextHandlesUtf8ContentCorrectly(): void
    {
        $input = 'Ümlauts äöü and spëcial çhàracters 中文 العربية';
        $record = $this->createRecord(['bodytext' => $input]);

        $result = $this->subject->prepareText($record, 'bodytext');

        self::assertIsString($result);
        self::assertStringContainsString('Ümlauts', $result);
        self::assertStringContainsString('äöü', $result);
        self::assertStringContainsString('中文', $result);
        self::assertStringContainsString('العربية', $result);
    }

    #[Test]
    public function preparePlainHtmlHandlesUtf8ContentCorrectly(): void
    {
        $input = 'Ümlauts äöü and 日本語';
        $record = $this->createRecord(['content' => $input]);

        $result = $this->subject->preparePlainHtml($record, 'content');

        self::assertIsString($result);
        self::assertStringContainsString('Ümlauts', $result);
        self::assertStringContainsString('äöü', $result);
        self::assertStringContainsString('日本語', $result);
    }

    #[Test]
    public function prepareTextHandlesWhitespaceOnlyContentAfterStripTags(): void
    {
        // Tags are stripped leaving only whitespace, which after trim becomes empty
        $input = '<div>   </div>';
        $record = $this->createRecord(['bodytext' => $input]);

        $result = $this->subject->prepareText($record, 'bodytext');

        // After strip_tags: "   ", after trim: "" -> returns empty string (not null)
        // because the original input was not empty
        self::assertSame('', $result);
    }

    #[Test]
    public function preparePlainHtmlFiltersEmptyLines(): void
    {
        $input = "Line 1\n\n\nLine 2";
        $record = $this->createRecord(['content' => $input]);

        $result = $this->subject->preparePlainHtml($record, 'content');

        self::assertIsString($result);
        // Empty lines should be filtered out by trimExplode
        self::assertSame('Line 1<br />Line 2', $result);
    }

    #[Test]
    public function prepareTextHandlesNullByteInjection(): void
    {
        // Null bytes could potentially bypass security filters
        $input = "normal\x00<script>alert('XSS')</script>";
        $record = $this->createRecord(['bodytext' => $input]);

        $result = $this->subject->prepareText($record, 'bodytext');

        self::assertIsString($result);
        // Script tags must be stripped
        self::assertStringNotContainsString('<script', $result);
        self::assertStringNotContainsString('</script', $result);
    }

    #[Test]
    public function preparePlainHtmlHandlesNullByteInjection(): void
    {
        $input = "normal\x00<script>alert('XSS')</script>";
        $record = $this->createRecord(['content' => $input]);

        $result = $this->subject->preparePlainHtml($record, 'content');

        self::assertIsString($result);
        // Angle brackets must be escaped
        self::assertStringContainsString('&lt;script&gt;', $result);
    }

    #[Test]
    public function prepareTextHandlesDataUrlAttempt(): void
    {
        // Data URLs can be used for XSS in certain contexts
        $input = '<a href="data:text/html,<script>alert(1)</script>">click</a>';
        $record = $this->createRecord(['bodytext' => $input]);

        $result = $this->subject->prepareText($record, 'bodytext');

        self::assertIsString($result);
        // Only text content should remain
        self::assertSame('click', $result);
    }

    #[Test]
    public function preparePlainHtmlHandlesDataUrlAttempt(): void
    {
        $input = 'data:text/html,<script>alert(1)</script>';
        $record = $this->createRecord(['content' => $input]);

        $result = $this->subject->preparePlainHtml($record, 'content');

        self::assertIsString($result);
        // All angle brackets must be escaped
        self::assertStringNotContainsString('<script>', $result);
        self::assertStringContainsString('&lt;script&gt;', $result);
    }

    #[Test]
    public function prepareTextHandlesSvgXssVector(): void
    {
        $input = '<svg><animate onbegin="alert(1)" attributeName="x" dur="1s"></animate></svg>';
        $record = $this->createRecord(['bodytext' => $input]);

        $result = $this->subject->prepareText($record, 'bodytext');

        // All tags including SVG should be stripped
        self::assertStringNotContainsString('<svg', $result ?? '');
        self::assertStringNotContainsString('onbegin', $result ?? '');
    }

    #[Test]
    public function preparePlainHtmlHandlesSvgXssVector(): void
    {
        $input = '<svg><animate onbegin="alert(1)"></animate></svg>';
        $record = $this->createRecord(['content' => $input]);

        $result = $this->subject->preparePlainHtml($record, 'content');

        self::assertIsString($result);
        // SVG tags must be escaped, not executable
        self::assertStringContainsString('&lt;svg&gt;', $result);
        self::assertStringNotContainsString('<svg>', $result);
    }

    #[Test]
    public function prepareTextHandlesMixedEncodingAttempt(): void
    {
        // Mixed encoding attempts should not bypass escaping
        $input = '&#60;script&#62;alert(1)&#60;/script&#62;';
        $record = $this->createRecord(['bodytext' => $input]);

        $result = $this->subject->prepareText($record, 'bodytext');

        self::assertIsString($result);
        // HTML entities should be preserved (not decoded and re-executed)
        // After htmlspecialchars with double_encode=false, entities are preserved
        self::assertStringNotContainsString('<script>', $result);
    }

    private function createRecord(array $properties): RawRecord
    {
        return new RawRecord(
            1,
            1,
            $properties,
            new ComputedProperties(),
            'tt_content.text'
        );
    }
}
