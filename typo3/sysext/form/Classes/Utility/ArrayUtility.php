<?php
declare(strict_types=1);
namespace TYPO3\CMS\Form\Utility;

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

use TYPO3\CMS\Form\Domain\Exception\TypeDefinitionNotValidException;

/**
 * Collection of static array utility functions
 *
 * Scope: frontend / backend
 * @internal
 */
class ArrayUtility
{

    /**
     * Validates the given $arrayToTest by checking if an element is not in $allowedArrayKeys.
     *
     * @param array $arrayToTest
     * @param array $allowedArrayKeys
     * @return void
     * @throws TypeDefinitionNotValidException if an element in $arrayToTest is not in $allowedArrayKeys
     * @internal
     */
    public static function assertAllArrayKeysAreValid(array $arrayToTest, array $allowedArrayKeys)
    {
        $notAllowedArrayKeys = array_keys(array_diff_key($arrayToTest, array_flip($allowedArrayKeys)));
        if (count($notAllowedArrayKeys) !== 0) {
            throw new TypeDefinitionNotValidException(sprintf('The options "%s" were not allowed (allowed were: "%s")', implode(', ', $notAllowedArrayKeys), implode(', ', $allowedArrayKeys)), 1325697085);
        }
    }

    /**
     * Sort keys from the current nesting level if all keys within the
     * current nesting level are integers.
     *
     * @param array $array
     * @return array
     * @internal
     */
    public static function sortNumericArrayKeysRecursive(array $array): array
    {
        if (count(array_filter(array_keys($array), 'is_string')) === 0) {
            ksort($array);
        }
        foreach ($array as $key => $value) {
            if (is_array($value) && !empty($value)) {
                $array[$key] = self::sortNumericArrayKeysRecursive($value);
            }
        }
        return $array;
    }

    /**
     * Reindex keys from the current nesting level if all keys within
     * the current nesting level are integers.
     *
     * @param array $array
     * @return array
     * @internal
     */
    public static function reIndexNumericArrayKeysRecursive(array $array): array
    {
        if (count(array_filter(array_keys($array), 'is_string')) === 0) {
            $array = array_values($array);
        }
        foreach ($array as $key => $value) {
            if (is_array($value) && !empty($value)) {
                $array[$key] = self::reIndexNumericArrayKeysRecursive($value);
            }
        }
        return $array;
    }

    /**
     * Recursively remove keys if their value are NULL.
     *
     * @param array $array
     * @return array the modified array
     * @internal
     */
    public static function removeNullValuesRecursive(array $array): array
    {
        $result = $array;
        foreach ($result as $key => $value) {
            if (is_array($value)) {
                $result[$key] = self::removeNullValuesRecursive($value);
            } elseif ($value === null) {
                unset($result[$key]);
            }
        }
        return $result;
    }

    /**
     * Recursively translate values.
     *
     * @param array $array
     * @return array the modified array
     * @internal
     */
    public static function stripTagsFromValuesRecursive(array $array): array
    {
        $result = $array;
        foreach ($result as $key => $value) {
            if (is_array($value)) {
                $result[$key] = self::stripTagsFromValuesRecursive($value);
            } else {
                if (!is_bool($value)) {
                    $result[$key] = strip_tags($value);
                }
            }
        }
        return $result;
    }

    /**
     * Recursively convert 'true' and 'false' strings to boolen values.
     *
     * @param array $array
     * @return array the modified array
     * @internal
     */
    public static function convertBooleanStringsToBooleanRecursive(array $array): array
    {
        $result = $array;
        foreach ($result as $key => $value) {
            if (is_array($value)) {
                $result[$key] = self::convertBooleanStringsToBooleanRecursive($value);
            } else {
                if ($value === 'true') {
                    $result[$key] = true;
                } elseif ($value === 'false') {
                    $result[$key] = false;
                }
            }
        }
        return $result;
    }
}
