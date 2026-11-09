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

namespace TYPO3\CMS\Lowlevel\Tests\Unit\Inspector;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Lowlevel\Inspector\Marker;
use TYPO3\CMS\Lowlevel\Inspector\ResolvedLabel;
use TYPO3\CMS\Lowlevel\Inspector\StyledText;
use TYPO3\CMS\Lowlevel\Inspector\ValueFormatter;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ValueFormatterTest extends UnitTestCase
{
    public static function quoteDataProvider(): array
    {
        return [
            'safe characters stay unquoted' => ['typo3/cms-frontend/timetracker', 'typo3/cms-frontend/timetracker'],
            'integer' => [0, '0'],
            'empty key' => ['', "''"],
            'space' => ['a b', "'a b'"],
            'backslash' => ['TYPO3\\CMS\\Foo', "'TYPO3\\CMS\\Foo'"],
            'single quote' => ["package 'typo3/cms-backend'", "\"package 'typo3/cms-backend'\""],
            'double quote and dollar with single quote' => ["it's \"\$x\"", "\"it's \\\"\\\$x\\\"\""],
            'control characters' => ["a\nb'\\", "\$'a\\nb\\'\\\\'"],
            'delete' => ["a\x7Fb", "\$'a\\x7Fb'"],
            'C1 control character' => ["a\u{9B}b", "\$'a\\u009bb'"],
            'bidi override' => ["a\u{202E}b", "\$'a\\u202eb'"],
        ];
    }

    #[DataProvider('quoteDataProvider')]
    #[Test]
    public function quoteCreatesPasteableShellSegments(string|int $segment, string $expected): void
    {
        self::assertSame($expected, new ValueFormatter()->quote($segment));
    }

    public static function valueDataProvider(): array
    {
        return [
            'string' => ['header', '"header"', StyledText::STYLE_STRING],
            'slashes and unicode unescaped' => ['a/ü', '"a/ü"', StyledText::STYLE_STRING],
            'integer' => [255, '255', StyledText::STYLE_NUMBER],
            'float keeps fraction' => [1.0, '1.0', StyledText::STYLE_NUMBER],
            'boolean' => [false, 'false', StyledText::STYLE_NUMBER],
            'null' => [null, 'null', StyledText::STYLE_NULL],
            'empty array' => [[], '[]', StyledText::STYLE_NULL],
            'delete escaped' => ["a\x7Fb", '"a\u007fb"', StyledText::STYLE_STRING],
            'C1 control character escaped' => ["\u{9B}31mred", '"\u009b31mred"', StyledText::STYLE_STRING],
            'bidi controls escaped' => ["\u{61C}\u{200E}\u{200F}\u{202A}\u{202E}\u{2066}\u{2069}", '"\u061c\u200e\u200f\u202a\u202e\u2066\u2069"', StyledText::STYLE_STRING],
            'neighbours of escaped characters kept' => ["\u{A0}\u{200D}\u{202F}\u{2065}", "\"\u{A0}\u{200D}\u{202F}\u{2065}\"", StyledText::STYLE_STRING],
            'blinded secret' => ['******', '"******"', StyledText::STYLE_SECRET],
            'marker unquoted' => [new Marker('INF'), 'INF', StyledText::STYLE_MARKER],
            'resolved label' => [new ResolvedLabel('Header', 'LLL:EXT:a/b.xlf:c'), '"Header"', StyledText::STYLE_STRING],
        ];
    }

    #[DataProvider('valueDataProvider')]
    #[Test]
    public function valueIsJsonWithStyle(mixed $value, string $expectedText, string $expectedStyle): void
    {
        self::assertSame([[$expectedText, $expectedStyle]], new ValueFormatter()->value($value)->segments);
    }

    #[Test]
    public function inlineWritesJsonWithMarkersUnquoted(): void
    {
        $text = new ValueFormatter()->inline([
            'a' => [1, 'x'],
            'b' => new Marker('*RECURSION*'),
            'c' => new ResolvedLabel('Header', 'LLL:EXT:a/b.xlf:c'),
            'd' => [],
        ]);
        self::assertSame('{"a":[1,"x"],"b":*RECURSION*,"c":"Header","d":[]}', $text->plain());
        self::assertContains(['*RECURSION*', StyledText::STYLE_MARKER], $text->segments);
    }

    #[Test]
    public function inlineEscapesUnsafeCharactersInKeysAndValues(): void
    {
        self::assertSame('{"k\u009b":"\u202ex"}', new ValueFormatter()->inline(["k\u{9B}" => "\u{202E}x"])->plain());
    }

    #[Test]
    public function jsonIsPrettyPrintedWithUnsafeCharactersEscaped(): void
    {
        $data = ["k\u{9B}" => ["\e[31m", "\u{202E}x", 'a/ü']];
        $json = new ValueFormatter()->json($data);
        self::assertSame('{' . "\n" . '    "k\u009b": [' . "\n" . '        "\u001b[31m",' . "\n" . '        "\u202ex",' . "\n" . '        "a/ü"' . "\n" . '    ]' . "\n" . '}', $json);
        self::assertSame($data, json_decode($json, true));
    }

    #[Test]
    public function escapeUnsafeCharactersKeepsPlainTextAndInvalidUtf8(): void
    {
        self::assertSame('a\u001b[1m b\u009b ü ' . "\xC2", new ValueFormatter()->escapeUnsafeCharacters("a\e[1m b\u{9B} ü \xC2"));
    }

    #[Test]
    public function inlineStopsOnceWiderThanLimit(): void
    {
        $text = new ValueFormatter()->inline(range(1, 1000), 10);
        self::assertGreaterThan(10, $text->width());
        self::assertLessThan(20, $text->width());
    }

    #[Test]
    public function toPlainDataReplacesMarkersAndLabels(): void
    {
        self::assertSame(
            ['a' => 'INF', 'b' => ['Header']],
            new ValueFormatter()->toPlainData(['a' => new Marker('INF'), 'b' => [new ResolvedLabel('Header', 'LLL:x:y')]])
        );
    }

    #[Test]
    public function countResolvedLabelsCountsWholeSubtree(): void
    {
        $label = new ResolvedLabel('a', 'b');
        self::assertSame(3, new ValueFormatter()->countResolvedLabels([$label, ['x' => $label, 'y' => [$label, 'z']]]));
    }
}
