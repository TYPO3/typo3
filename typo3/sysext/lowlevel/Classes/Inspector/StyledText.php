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

use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Helper;

/**
 * A line of output as text segments with an optional console style each.
 *
 * @internal
 */
final readonly class StyledText
{
    public const string STYLE_KEY = 'inspect_key';
    public const string STYLE_STRING = 'inspect_string';
    public const string STYLE_NUMBER = 'inspect_number';
    public const string STYLE_NULL = 'inspect_null';
    public const string STYLE_DIM = 'inspect_dim';
    public const string STYLE_ELLIPSIS = 'inspect_ellipsis';
    public const string STYLE_SECRET = 'inspect_secret';
    public const string STYLE_MARKER = 'inspect_marker';

    /**
     * @param list<array{string, ?string}> $segments
     */
    public function __construct(
        public array $segments = [],
    ) {}

    public function append(string $text, ?string $style = null): self
    {
        return $text === '' ? $this : new self([...$this->segments, [$text, $style]]);
    }

    public function concat(self $other): self
    {
        return new self([...$this->segments, ...$other->segments]);
    }

    public function plain(): string
    {
        return implode('', array_column($this->segments, 0));
    }

    public function width(): int
    {
        return Helper::width($this->plain());
    }

    public function withStyle(string $from, string $to): self
    {
        return new self(array_map(
            static fn(array $segment): array => [$segment[0], $segment[1] === $from ? $to : $segment[1]],
            $this->segments
        ));
    }

    /**
     * Cuts the text so that it fits into $maxWidth including a trailing ellipsis.
     */
    public function truncate(int $maxWidth): self
    {
        $available = max(0, $maxWidth - 1);
        $segments = [];
        foreach ($this->segments as [$text, $style]) {
            $width = Helper::width($text);
            if ($width <= $available) {
                $segments[] = [$text, $style];
                $available -= $width;
                continue;
            }
            $cut = '';
            foreach (mb_str_split($text) as $character) {
                $characterWidth = Helper::width($character);
                if ($characterWidth > $available) {
                    break;
                }
                $cut .= $character;
                $available -= $characterWidth;
            }
            if ($cut !== '') {
                $segments[] = [$cut, $style];
            }
            break;
        }
        $segments[] = ['…', self::STYLE_ELLIPSIS];
        return new self($segments);
    }

    public function format(): string
    {
        $formatted = '';
        foreach ($this->segments as [$text, $style]) {
            $escaped = OutputFormatter::escape($text);
            $formatted .= $style === null ? $escaped : '<' . $style . '>' . $escaped . '</>';
        }
        return $formatted;
    }
}
