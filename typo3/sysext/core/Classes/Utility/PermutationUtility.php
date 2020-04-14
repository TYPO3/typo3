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

namespace TYPO3\CMS\Core\Utility;

/**
 * Class with helper functions for permuting items.
 */
class PermutationUtility
{
    /**
     * Combines string items of multiple arrays as cross-product into flat items.
     *
     * Example:
     * + meltStringItems([['a', 'b'], ['c', 'd'], ['e', 'f']])
     * + results into ['ace', 'acf', 'ade', 'adf', 'bce', 'bcf', 'bde', 'bdf']
     *
     * @param array[] $payload Distinct array that should be melted
     * @param string $previousResult Previous item results
     * @return array
     */
    public static function meltStringItems(array $payload, string $previousResult = ''): array
    {
        $results = [];
        $items = static::nextItems($payload);
        foreach ($items as $item) {
            $resultItem = $previousResult . static::asString($item);
            if (!empty($payload)) {
                $results = array_merge(
                    $results,
                    static::meltStringItems($payload, $resultItem)
                );
                continue;
            }
            $results[] = $resultItem;
        }
        return $results;
    }

    /**
     * Combines arbitrary items of multiple arrays as cross-product into flat items.
     *
     * Example:
     * + meltArrayItems(['a','b'], ['c','e'], ['f','g'])
     * + results into ['a', 'c', 'e'], ['a', 'c', 'f'], ['a', 'd', 'e'], ['a', 'd', 'f'],
     *                ['b', 'c', 'e'], ['b', 'c', 'f'], ['b', 'd', 'e'], ['b', 'd', 'f'],
     *
     * @param array[] $payload Distinct items that should be melted
     * @param array $previousResult Previous item results
     * @return array
     */
    public static function meltArrayItems(array $payload, array $previousResult = []): array
    {
        $results = [];
        $items = static::nextItems($payload);
        foreach ($items as $item) {
            $resultItems = $previousResult;
            $resultItems[] = $item;
            if (!empty($payload)) {
                $results = array_merge(
                    $results,
                    static::meltArrayItems($payload, $resultItems)
                );
                continue;
            }
            $results[] = $resultItems;
        }
        return $results;
    }

    protected static function nextItems(array &$payload): iterable
    {
        $items = array_shift($payload);
        if (is_iterable($items)) {
            return $items;
        }
        throw new \LogicException(
            sprintf('Expected iterable, got %s', gettype($items)),
            1578164101
        );
    }

    protected static function asString($item): string
    {
        if (is_string($item)) {
            return $item;
        }
        if (is_object($item) && method_exists($item, '__toString')) {
            return (string)$item;
        }
        throw new \LogicException(
            sprintf('Expected string, got %s', gettype($item)),
            1578164102
        );
    }
}
