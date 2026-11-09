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

/**
 * Turns provider data into plain arrays and scalars, following the rules of the
 * Configuration module: enums become their value or name, callables their name,
 * objects arrays of their properties. Values that cannot be shown as data become
 * a Marker, and cyclic objects are cut with "*RECURSION*".
 *
 * @internal
 */
final readonly class DataNormalizer
{
    public function normalize(mixed $value): mixed
    {
        return $this->normalizeValue($value, new \SplObjectStorage());
    }

    private function normalizeValue(mixed $value, \SplObjectStorage $ancestors): mixed
    {
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }
        if ($value instanceof \UnitEnum) {
            return $value->name;
        }
        if (is_float($value) && !is_finite($value)) {
            return new Marker(is_nan($value) ? 'NAN' : ($value > 0 ? 'INF' : '-INF'));
        }
        if (is_string($value)) {
            // Invalid UTF-8 is replaced by U+FFFD, as the module does via htmlspecialchars().
            return mb_check_encoding($value, 'UTF-8')
                ? $value
                : htmlspecialchars_decode(htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), ENT_QUOTES);
        }
        // Only arrays and objects: a string that happens to be a function name ("trim") is data.
        if ((is_array($value) || is_object($value)) && is_callable($value, false, $callableName)) {
            return new Marker($callableName);
        }
        if (is_object($value)) {
            if ($ancestors->offsetExists($value)) {
                return new Marker('*RECURSION*');
            }
            $ancestors->offsetSet($value);
            $result = $this->normalizeArray(
                $value instanceof \Traversable ? $this->iteratorEntries($value) : $this->objectProperties($value),
                $ancestors
            );
            $ancestors->offsetUnset($value);
            return $result;
        }
        if (is_array($value)) {
            return $this->normalizeArray($value, $ancestors);
        }
        if (is_resource($value) || gettype($value) === 'resource (closed)') {
            return new Marker((string)$value);
        }
        return $value;
    }

    private function normalizeArray(array $array, \SplObjectStorage $ancestors): array
    {
        $result = [];
        foreach ($array as $key => $item) {
            $result[$key] = $this->normalizeValue($item, $ancestors);
        }
        return $result;
    }

    private function iteratorEntries(\Traversable $iterator): array
    {
        $entries = [];
        foreach ($iterator as $key => $item) {
            $entries[is_int($key) || is_string($key) ? $key : get_debug_type($key)] = $item;
        }
        return $entries;
    }

    /**
     * Properties of an object like an (array) cast, with the null-byte prefixes of protected
     * and private properties removed. Private properties whose name occurs more than once in
     * the class hierarchy are prefixed with their declaring class, so none is lost.
     */
    private function objectProperties(object $object): array
    {
        $parsed = [];
        $nameCounts = [];
        foreach ((array)$object as $key => $item) {
            $key = (string)$key;
            $class = '';
            if (str_starts_with($key, "\0")) {
                [, $class, $key] = explode("\0", $key, 3);
            }
            $parsed[] = [$key, $class, $item];
            $nameCounts[$key] = ($nameCounts[$key] ?? 0) + 1;
        }
        $properties = [];
        foreach ($parsed as [$name, $class, $item]) {
            $isPrivate = $class !== '' && $class !== '*';
            $properties[$nameCounts[$name] > 1 && $isPrivate ? $class . '::' . $name : $name] = $item;
        }
        return $properties;
    }
}
