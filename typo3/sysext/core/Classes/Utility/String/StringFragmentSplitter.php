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

namespace TYPO3\CMS\Core\Utility\String;

/**
 * Splits a string into RAW and EXPRESSION fragments.
 * EXPRESSION fragments are resolved by provided arbitrary regex pattern.
 *
 * @internal
 */
class StringFragmentSplitter
{
    /**
     * Raw string literals
     */
    public const TYPE_RAW = 'raw';

    /**
     * Literals used as expression
     */
    public const TYPE_EXPRESSION = 'expression';

    /**
     * Returns `null` in case there have not been any pattern matches,
     * if omitted an array containing only `raw` fragments is returned
     */
    public const FLAG_UNMATCHED_AS_NULL = 1;

    /**
     * @var list<StringFragmentPattern>
     */
    protected readonly array $patterns;

    public function __construct(StringFragmentPattern ...$patterns)
    {
        $this->patterns = $patterns;
    }

    /**
     * @param string $value to be split into `raw` and `expression` fragments
     * @param int $flags (optional) `FLAG_UNMATCHED_AS_NULL`
     */
    public function split(string $value, int $flags = 0): ?StringFragmentCollection
    {
        $pattern = chr(1) . implode('|', $this->preparePatterns()) . chr(1);
        $options = PREG_UNMATCHED_AS_NULL | PREG_OFFSET_CAPTURE | PREG_SET_ORDER;
        if (!preg_match_all($pattern, $value, $matches, $options)) {
            if (($flags & self::FLAG_UNMATCHED_AS_NULL) === self::FLAG_UNMATCHED_AS_NULL) {
                return null;
            }
            return new StringFragmentCollection(StringFragment::raw($value));
        }

        $collection = new StringFragmentCollection();
        foreach ($matches as $match) {
            // filters string keys (e.g. `expression_a1b2c3d4e5`) from matches, skips numeric indexes
            $types = array_filter(
                array_keys($match),
                static fn (int|string $type) => is_string($type) && $type !== ''
            );
            foreach ($types as $type) {
                $matchOffset = $match[$type][1];
                if ($matchOffset < 0) {
                    continue;
                }
                $matchValue = $match[$type][0];
                // matches contain only pattern matches, but no raw string literals - by comparing the
                // position of the collection with the current offset, missing raw literals are synchronized
                if ($collection->getLength() < $matchOffset) {
                    $gapValue = substr($value, $collection->getLength(), $matchOffset - $collection->getLength());
                    $collection = $collection->with(StringFragment::raw($gapValue));
                }
                $collection = $collection->with(StringFragment::expression($matchValue));
            }
        }
        // synchronize missing raw string literals
        // (at the end of the given value after previous expression)
        if ($collection->getLength() < strlen($value)) {
            $gapValue = substr($value, $collection->getLength());
            $collection = $collection->with(StringFragment::raw($gapValue));
        }
        return $collection;
    }

    /**
     * @return list<string>
     */
    protected function preparePatterns(): array
    {
        return array_map(
            static fn (StringFragmentPattern $pattern) => $pattern->compilePattern(),
            $this->patterns
        );
    }
}
