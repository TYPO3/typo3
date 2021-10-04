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

namespace TYPO3\CMS\Core\Routing\Enhancer;

/**
 * Helper for processing various variables within a Route Enhancer
 */
class VariableProcessor
{
    protected const LEVEL_DELIMITER = '__';
    protected const ARGUMENT_SEPARATOR = '/';
    protected const VARIABLE_PATTERN = '#\{(?P<modifier>!)?(?P<name>[^}]+)\}#';

    /**
     * @var array
     */
    protected $hashes = [];

    /**
     * @var array
     */
    protected $nestedValues = [];

    /**
     * @param string $value
     * @return string
     */
    protected function addHash(string $value): string
    {
        if (strlen($value) < 31 && !preg_match('#[^\w]#', $value)) {
            return $value;
        }
        // removing one bit, e.g. for enforced route prefix `{!value}`
        $hash = substr(md5($value), 0, -1);
        // Symfony Route Compiler requires first literal to be non-integer
        if ($hash[0] === (string)(int)$hash[0]) {
            $hash[0] = str_replace(
                range('0', '9'),
                range('o', 'x'),
                $hash[0]
            );
        }
        $this->hashes[$hash] = $value;
        return $hash;
    }

    /**
     * @param string $hash
     * @return string
     * @throws \OutOfRangeException
     */
    protected function resolveHash(string $hash): string
    {
        if (strlen($hash) < 31) {
            return $hash;
        }
        if (!isset($this->hashes[$hash])) {
            throw new \OutOfRangeException(
                'Hash not resolvable',
                1537633463
            );
        }
        return $this->hashes[$hash];
    }

    /**
     * @param string $value
     * @return string
     */
    protected function addNestedValue(string $value): string
    {
        if (!str_contains($value, static::ARGUMENT_SEPARATOR)) {
            return $value;
        }
        $nestedValue = str_replace(
            static::ARGUMENT_SEPARATOR,
            static::LEVEL_DELIMITER,
            $value
        );
        $this->nestedValues[$nestedValue] = $value;
        return $nestedValue;
    }

    /**
     * @param string $value
     * @return string
     */
    protected function resolveNestedValue(string $value): string
    {
        if (!str_contains($value, static::LEVEL_DELIMITER)) {
            return $value;
        }
        return $this->nestedValues[$value] ?? $value;
    }

    /**
     * @param string $routePath
     * @param string|null $namespace
     * @param array $arguments
     * @return string
     */
    public function deflateRoutePath(string $routePath, string $namespace = null, array $arguments = []): string
    {
        if (!preg_match_all(static::VARIABLE_PATTERN, $routePath, $matches)) {
            return $routePath;
        }

        $replace = [];
        $search = array_values($matches[0]);
        $deflatedNames = $this->deflateValues($matches['name'], $namespace, $arguments);
        foreach ($deflatedNames as $index => $deflatedName) {
            $modifier = $matches['modifier'][$index] ?? '';
            $replace[] = '{' . $modifier . $deflatedName . '}';
        }
        return str_replace($search, $replace, $routePath);
    }

    /**
     * @param string $routePath
     * @param string|null $namespace
     * @param array $arguments
     * @return string
     */
    public function inflateRoutePath(string $routePath, string $namespace = null, array $arguments = []): string
    {
        if (!preg_match_all(static::VARIABLE_PATTERN, $routePath, $matches)) {
            return $routePath;
        }

        $replace = [];
        $search = array_values($matches[0]);
        $inflatedNames = $this->inflateValues($matches['name'], $namespace, $arguments);
        foreach ($inflatedNames as $index => $inflatedName) {
            $modifier = $matches['modifier'][$index] ?? '';
            $replace[] = '{' . $modifier . $inflatedName . '}';
        }
        return str_replace($search, $replace, $routePath);
    }

    /**
     * Deflates (flattens) route/request parameters for a given namespace.
     *
     * @param array $parameters
     * @param string $namespace
     * @param array $arguments
     * @return array
     */
    public function deflateNamespaceParameters(array $parameters, string $namespace, array $arguments = []): array
    {
        if (empty($namespace) || empty($parameters[$namespace])) {
            return $parameters;
        }
        // prefix items of namespace parameters and apply argument mapping
        $namespaceParameters = $this->deflateKeys($parameters[$namespace], $namespace, $arguments, false);
        // deflate those array items
        $namespaceParameters = $this->deflateArray($namespaceParameters);
        unset($parameters[$namespace]);
        // merge with remaining array items
        return array_merge($parameters, $namespaceParameters);
    }

    /**
     * Inflates (unflattens) route/request parameters.
     *
     * @param array $parameters
     * @param string $namespace
     * @param array $arguments
     * @return array
     */
    public function inflateNamespaceParameters(array $parameters, string $namespace, array $arguments = []): array
    {
        if (empty($namespace) || empty($parameters)) {
            return $parameters;
        }

        $parameters = $this->inflateArray($parameters, $namespace, $arguments);
        // apply argument mapping on items of inflated namespace parameters
        if (!empty($parameters[$namespace]) && !empty($arguments)) {
            $parameters[$namespace] = $this->inflateKeys($parameters[$namespace], null, $arguments, false);
        }
        return $parameters;
    }

    /**
     * Deflates (flattens) route/request parameters for a given namespace.
     *
     * @param array $parameters
     * @param array $arguments
     * @return array
     */
    public function deflateParameters(array $parameters, array $arguments = []): array
    {
        $parameters = $this->deflateKeys($parameters, null, $arguments, false);
        return $this->deflateArray($parameters);
    }

    /**
     * Inflates (unflattens) route/request parameters.
     *
     * @param array $parameters
     * @param array $arguments
     * @return array
     */
    public function inflateParameters(array $parameters, array $arguments = []): array
    {
        $parameters = $this->inflateArray($parameters, null, $arguments);
        return $this->inflateKeys($parameters, null, $arguments, false);
    }

    /**
     * Deflates keys names on the first level, now recursion into sub-arrays.
     * Can be used to adjust key names of route requirements, mappers, etc.
     *
     * @param array $items
     * @param string|null $namespace
     * @param array $arguments
     * @param bool $hash = true
     * @return array
     */
    public function deflateKeys(array $items, string $namespace = null, array $arguments = [], bool $hash = true): array
    {
        if (empty($items) || empty($arguments) && empty($namespace)) {
            return $items;
        }
        $keys = $this->deflateValues(array_keys($items), $namespace, $arguments, $hash);
        return array_combine(
            $keys,
            array_values($items)
        );
    }

    /**
     * Inflates keys names on the first level, now recursion into sub-arrays.
     * Can be used to adjust key names of route requirements, mappers, etc.
     *
     * @param array $items
     * @param string|null $namespace
     * @param array $arguments
     * @param bool $hash = true
     * @return array
     */
    public function inflateKeys(array $items, string $namespace = null, array $arguments = [], bool $hash = true): array
    {
        if (empty($items) || empty($arguments) && empty($namespace)) {
            return $items;
        }
        $keys = $this->inflateValues(array_keys($items), $namespace, $arguments, $hash);
        return array_combine(
            $keys,
            array_values($items)
        );
    }

    /**
     * Deflates plain values.
     *
     * @param array $values
     * @param string|null $namespace
     * @param array $arguments
     * @param bool $hash
     * @return array
     */
    protected function deflateValues(array $values, string $namespace = null, array $arguments = [], bool $hash = true): array
    {
        if (empty($values) || empty($arguments) && empty($namespace)) {
            return $values;
        }
        $namespacePrefix = $namespace ? $namespace . static::LEVEL_DELIMITER : '';
        $arguments = array_map('strval', $arguments);
        return array_map(
            function (string $value) use ($arguments, $namespacePrefix, $hash) {
                $value = $arguments[$value] ?? $value;
                $value = $this->addNestedValue($value);
                $value = $namespacePrefix . $value;
                if (!$hash) {
                    return $value;
                }
                return $this->addHash($value);
            },
            $values
        );
    }

    /**
     * Inflates plain values.
     *
     * @param array $values
     * @param string|null $namespace
     * @param array $arguments
     * @param bool $hash
     * @return array
     */
    protected function inflateValues(array $values, string $namespace = null, array $arguments = [], bool $hash = true): array
    {
        if (empty($values) || empty($arguments) && empty($namespace)) {
            return $values;
        }
        $arguments = array_map('strval', $arguments);
        $namespacePrefix = $namespace ? $namespace . static::LEVEL_DELIMITER : '';
        return array_map(
            function (string $value) use ($arguments, $namespacePrefix, $hash) {
                if ($hash) {
                    $value = $this->resolveHash($value);
                }
                if (!empty($namespacePrefix) && strpos($value, $namespacePrefix) === 0) {
                    $value = substr($value, strlen($namespacePrefix));
                }
                $value = $this->resolveNestedValue($value);
                $index = array_search($value, $arguments, true);
                return $index !== false ? $index : $value;
            },
            $values
        );
    }

    /**
     * Deflates (flattens) array having nested structures.
     *
     * @param array $array
     * @param string $prefix
     * @return array
     */
    protected function deflateArray(array $array, string $prefix = ''): array
    {
        $delimiter = static::LEVEL_DELIMITER;
        if ($prefix !== '' && substr($prefix, -strlen($delimiter)) !== $delimiter) {
            $prefix .= static::LEVEL_DELIMITER;
        }

        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_replace(
                    $result,
                    $this->deflateArray(
                        $value,
                        $prefix . $key . static::LEVEL_DELIMITER
                    )
                );
            } else {
                $deflatedKey = $this->addHash($prefix . $key);
                $result[$deflatedKey] = $value;
            }
        }
        return $result;
    }

    /**
     * Inflates (unflattens) an array into nested structures.
     *
     * @param array $array
     * @param string $namespace
     * @param array $arguments
     * @return array
     */
    protected function inflateArray(array $array, ?string $namespace, array $arguments): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $inflatedKey = $this->resolveHash((string)$key);
            // inflate nested values `namespace__any__nested` -> `namespace__any/nested`
            $inflatedKey = $this->inflateNestedValue($inflatedKey, $namespace, $arguments);
            $steps = explode(static::LEVEL_DELIMITER, $inflatedKey);
            $pointer = &$result;
            foreach ($steps as $step) {
                $pointer = &$pointer[$step];
            }
            $pointer = $value;
            unset($pointer);
        }
        return $result;
    }

    /**
     * @param string $value
     * @param string $namespace
     * @param array $arguments
     * @return string
     */
    protected function inflateNestedValue(string $value, ?string $namespace, array $arguments): string
    {
        $namespacePrefix = $namespace ? $namespace . static::LEVEL_DELIMITER : '';
        if (!empty($namespace) && strpos($value, $namespacePrefix) !== 0) {
            return $value;
        }
        $arguments = array_map('strval', $arguments);
        $possibleNestedValueKey = substr($value, strlen($namespacePrefix));
        $possibleNestedValue = $this->nestedValues[$possibleNestedValueKey] ?? null;
        if ($possibleNestedValue === null || !in_array($possibleNestedValue, $arguments, true)) {
            return $value;
        }
        return $namespacePrefix . $possibleNestedValue;
    }
}
