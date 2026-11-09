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

namespace TYPO3\CMS\Lowlevel\Inspector;

use Symfony\Component\Console\Helper\Helper;

/**
 * Writes keys and values of normalized data: values as JSON, markers unquoted,
 * keys as shell-quoted path segments that can be pasted back as arguments.
 *
 * @internal
 */
final readonly class ValueFormatter
{
    private const int JSON_FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_INVALID_UTF8_SUBSTITUTE;
    private const string BLINDED = '******';
    private const string SAFE_SEGMENT = '#^[A-Za-z0-9_.,:@%+/-]+$#';
    /**
     * Characters a terminal may act on or that reorder the displayed text, and that json_encode()
     * leaves unescaped: DEL, C1 controls and the bidirectional formatting characters. Matched as
     * UTF-8 byte sequences, so that invalid UTF-8 in keys doesn't make the match fail.
     */
    private const string JSON_UNSAFE_CHARACTER = '\x7F|\xC2[\x80-\x9F]|\xD8\x9C|\xE2\x80[\x8E\x8F\xAA-\xAE]|\xE2\x81[\xA6-\xA9]';
    private const string UNSAFE_CHARACTER = '[\x00-\x1F]|' . self::JSON_UNSAFE_CHARACTER;

    /**
     * @param list<string|int> $keys
     */
    public function keys(array $keys): StyledText
    {
        return new StyledText([[implode(' ', array_map($this->quote(...), $keys)), StyledText::STYLE_KEY]]);
    }

    public function quote(string|int $segment): string
    {
        $segment = (string)$segment;
        if (preg_match(self::SAFE_SEGMENT, $segment)) {
            return $segment;
        }
        if (preg_match('/' . self::UNSAFE_CHARACTER . '/', $segment)) {
            return "$'" . preg_replace_callback(
                '/' . self::UNSAFE_CHARACTER . '|[\\\\\']/',
                fn(array $matches): string => match ($matches[0]) {
                    "\n" => '\n',
                    "\t" => '\t',
                    "\r" => '\r',
                    '\\' => '\\\\',
                    "'" => "\\'",
                    default => strlen($matches[0]) === 1 ? sprintf('\x%02X', ord($matches[0])) : $this->unicodeEscape($matches[0]),
                },
                $segment
            ) . "'";
        }
        if (!str_contains($segment, "'")) {
            return "'" . $segment . "'";
        }
        return '"' . addcslashes($segment, '\\"$`') . '"';
    }

    /**
     * A single value: a scalar, a marker, a resolved label or an empty array.
     */
    public function value(mixed $value): StyledText
    {
        $text = new StyledText();
        return match (true) {
            $value instanceof Marker => $text->append($value->text, StyledText::STYLE_MARKER),
            $value instanceof ResolvedLabel => $text->append($this->encode($value->text), StyledText::STYLE_STRING),
            $value === self::BLINDED => $text->append($this->encode($value), StyledText::STYLE_SECRET),
            is_string($value) => $text->append($this->encode($value), StyledText::STYLE_STRING),
            is_int($value), is_float($value), is_bool($value) => $text->append($this->encode($value), StyledText::STYLE_NUMBER),
            default => $text->append($this->encode($value), StyledText::STYLE_NULL),
        };
    }

    /**
     * A whole subtree on one line, as JSON with markers unquoted. Stops once the text
     * is wider than $limit, so a preview of a large subtree doesn't encode all of it.
     */
    public function inline(array $value, int $limit = PHP_INT_MAX): StyledText
    {
        $segments = [];
        $width = 0;
        foreach ($this->inlineSegments($value) as $segment) {
            $segments[] = $segment;
            $width += Helper::width($segment[0]);
            if ($width > $limit) {
                break;
            }
        }
        return new StyledText($segments);
    }

    /**
     * @return \Generator<array{string, string}>
     */
    private function inlineSegments(array $value): \Generator
    {
        $isList = array_is_list($value);
        yield [$isList ? '[' : '{', StyledText::STYLE_STRING];
        $first = true;
        foreach ($value as $key => $item) {
            if (!$first) {
                yield [',', StyledText::STYLE_STRING];
            }
            $first = false;
            if (!$isList) {
                yield [$this->encode((string)$key) . ':', StyledText::STYLE_STRING];
            }
            if (is_array($item)) {
                yield from $this->inlineSegments($item);
            } elseif ($item instanceof Marker || $item === self::BLINDED) {
                yield from $this->value($item)->segments;
            } else {
                yield [$this->encode($item instanceof ResolvedLabel ? $item->text : $item), StyledText::STYLE_STRING];
            }
        }
        yield [$isList ? ']' : '}', StyledText::STYLE_STRING];
    }

    public function isLeaf(mixed $value): bool
    {
        return !is_array($value) || $value === [];
    }

    public function countResolvedLabels(mixed $value): int
    {
        if ($value instanceof ResolvedLabel) {
            return 1;
        }
        if (!is_array($value)) {
            return 0;
        }
        return array_sum(array_map($this->countResolvedLabels(...), $value));
    }

    /**
     * Normalized data as plain PHP data for JSON: markers and resolved labels become strings.
     */
    public function toPlainData(mixed $value): mixed
    {
        return match (true) {
            $value instanceof Marker, $value instanceof ResolvedLabel => $value->text,
            is_array($value) => array_map($this->toPlainData(...), $value),
            default => $value,
        };
    }

    /**
     * Pretty-printed JSON with unsafe characters escaped.
     */
    public function json(mixed $data): string
    {
        return $this->escapeJsonUnsafeCharacters((string)json_encode($data, JSON_PRETTY_PRINT | self::JSON_FLAGS));
    }

    /**
     * Replaces unsafe characters in text that is not JSON by \uXXXX, as JSON escapes them.
     */
    public function escapeUnsafeCharacters(string $text): string
    {
        return $this->replaceByUnicodeEscape(self::UNSAFE_CHARACTER, $text);
    }

    /**
     * Within JSON, C0 controls are escaped already and line breaks may be part of the layout.
     */
    private function escapeJsonUnsafeCharacters(string $json): string
    {
        return $this->replaceByUnicodeEscape(self::JSON_UNSAFE_CHARACTER, $json);
    }

    private function replaceByUnicodeEscape(string $pattern, string $text): string
    {
        return (string)preg_replace_callback(
            '/' . $pattern . '/',
            fn(array $matches): string => $this->unicodeEscape($matches[0]),
            $text
        );
    }

    private function unicodeEscape(string $character): string
    {
        return sprintf('\u%04x', mb_ord($character, 'UTF-8'));
    }

    private function encode(mixed $value): string
    {
        return $this->escapeJsonUnsafeCharacters((string)json_encode($value, self::JSON_FLAGS));
    }
}
